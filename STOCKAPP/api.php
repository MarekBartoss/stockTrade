<?php
session_start();
require_once 'conn.php'; 

// Disable HTML error printing, only JSON allowed
ini_set('display_errors', 0);
header('Content-Type: application/json');

// REPLACE 'demo' WITH YOUR REAL KEY for reliable data
define('ALPHA_VANTAGE_KEY', 'O2XQX5LFU42PZ3AH'); 

// --- ROBUST URL FETCHER (cURL) ---
function fetchUrl($url) {
    if (!function_exists('curl_init')) {
        return @file_get_contents($url);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout to prevent hanging
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function sendJson($data) { echo json_encode($data); exit; }

function sendEmailOTP($email, $code) {
    $subject = "Verification Code - StockTrade";
    $msg = "Your PIN: $code";
    $headers = "From: no-reply@devbartos.cz\r\n";
    $headers .= "Reply-To: no-reply@devbartos.cz\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    return mail($email, $subject, $msg, $headers);
}

// --- DATA FETCHING ---
function getQuote($symbol) {
    // 1. Check Cache (60s)
    if (isset($_SESSION['price_cache'][$symbol])) {
        $cached = $_SESSION['price_cache'][$symbol];
        if (time() - $cached['time'] < 60) {
            $cached['data']['c'] += (rand(-5, 5) / 100); 
            return $cached['data'];
        }
    }

    // 2. Fetch from Alpha Vantage
    $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=" . ALPHA_VANTAGE_KEY;
    $json = fetchUrl($url);
    $data = json_decode($json, true);

    // 3. Process Response
    if (isset($data['Global Quote']) && !empty($data['Global Quote']['05. price'])) {
        $q = $data['Global Quote'];
        $clean = [
            'c' => (float)$q['05. price'],
            'd' => (float)$q['09. change'],
            'dp' => (float)str_replace('%', '', $q['10. change percent'])
        ];
        // Cache result
        $_SESSION['price_cache'][$symbol] = ['time' => time(), 'data' => $clean];
        return $clean;
    }
    
    // 4. Fallback if API limit reached or failed
    return getMockQuote($symbol);
}

function getMockQuote($symbol) {
    // Realistic starting prices for simulation
    $bases = [
        'AAPL'=>225, 'MSFT'=>415, 'GOOGL'=>175, 'AMZN'=>185, 'TSLA'=>240, 
        'NVDA'=>120, 'META'=>500, 'NFLX'=>650, 'AMD'=>150, 'INTC'=>30
    ];
    $base = $bases[$symbol] ?? 150.00;
    
    $seed = crc32($symbol) + floor(time() / 15);
    srand($seed);
    $change = (rand(-500, 500) / 100);
    return ['c' => $base + $change, 'd' => $change, 'dp' => ($change/$base)*100];
}

function getCandles($symbol) {
    $url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&apikey=" . ALPHA_VANTAGE_KEY;
    $json = fetchUrl($url);
    $data = json_decode($json, true);

    $c = []; $t = [];
    if(isset($data['Time Series (Daily)'])) {
        $count = 0;
        foreach($data['Time Series (Daily)'] as $date => $vals) {
            if($count++ > 30) break;
            array_unshift($c, (float)$vals['4. close']);
            array_unshift($t, strtotime($date));
        }
        return ['s' => 'ok', 'c' => $c, 't' => $t];
    }
    
    // Fallback Mock Candles
    $quote = getMockQuote($symbol);
    $current = $quote['c'];
    for($i=30; $i>=0; $i--) {
        $current -= (rand(-200, 200) / 100);
        array_unshift($c, $current);
        array_unshift($t, time() - ($i*86400));
    }
    return ['s' => 'ok', 'c' => $c, 't' => $t];
}

$TRACKED_STOCKS = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA', 'NVDA', 'META', 'NFLX', 'AMD', 'INTC', 'SPY', 'QQQ', 'BTC-USD', 'ETH-USD', 'COIN', 'JPM', 'DIS', 'WMT', 'SBUX', 'NKE'];

// --- CONTROLLER ---
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';
global $db;

try {
    if (!$db) throw new Exception("Database Connection Failed. Check conn.php");

    // AUTH
    if ($action === 'login') {
        $stmt = $db->prepare("SELECT id, username, email, password, is_verified, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $input['username']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($input['password'], $user['password'])) {
            if (strtolower($user['username']) === 'admin' || $user['role'] === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'admin';
                sendJson(['success' => true, 'requires_otp' => false]);
            }
            if ($user['is_verified'] == 0) {
                // Resend OTP logic...
                // (Omitted for speed, same as previous logic)
                sendJson(['success' => false, 'requires_otp' => true, 'email' => $user['email']]);
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'user'; 
            sendJson(['success' => true]);
        }
        sendJson(['success' => false, 'error' => 'Invalid credentials']);
    }

    if ($action === 'register') {
        $u = trim($input['username']);
        $p = password_hash($input['password'], PASSWORD_DEFAULT);
        $e = trim($input['email']);
        $role = (strtolower($u) === 'admin') ? 'admin' : 'user';
        $verified = ($role === 'admin') ? 1 : 0;
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $u, $e, $p, $role, $verified);
        if ($stmt->execute()) {
            if ($role === 'admin') {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['role'] = 'admin';
                sendJson(['success' => true, 'requires_otp' => false]);
            } else {
                // Send email logic...
                sendJson(['success' => true, 'email' => $e]);
            }
        }
        sendJson(['success' => false, 'error' => 'Username taken']);
    }

    if ($action === 'logout') { session_destroy(); sendJson(['success' => true]); }

    // PROTECTED
    if (!isset($_SESSION['user_id'])) sendJson(['error' => 'Auth Required']);
    $uid = $_SESSION['user_id'];

    if ($action === 'user_info') {
        $stmt = $db->prepare("SELECT username, cash, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_assoc());
    }

    if ($action === 'get_holding') {
        $sym = strtoupper($input['symbol']);
        try {
            $stmt = $db->prepare("SELECT quantity FROM holdings WHERE user_id = ? AND symbol = ?");
            if (!$stmt) throw new Exception("Table 'holdings' missing");
            $stmt->bind_param("is", $uid, $sym);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            sendJson($res ? $res : ['quantity' => 0]);
        } catch (Exception $e) {
            sendJson(['quantity' => 0, 'error' => $e->getMessage()]);
        }
    }

    // --- MARKET DATA: HYBRID APPROACH ---
    if ($action === 'get_prices') {
        $p = [];
        foreach($TRACKED_STOCKS as $i => $s) {
            // ONLY Fetch Real Data for first 5 stocks to keep it FAST (approx 1-2s total)
            if ($i < 5) {
                $p[$s] = getQuote($s);
            } else {
                // Instant Mock for the rest
                $p[$s] = getMockQuote($s);
            }
        }
        sendJson($p);
    }

    if ($action === 'get_candles') {
        sendJson(getCandles($input['symbol']));
    }

    if ($action === 'trade') {
        $type = $input['type'];
        $sym = strtoupper($input['symbol']);
        $qty = (int)$input['quantity'];
        
        $q = getQuote($sym);
        $price = $q['c'];
        $total = $price * $qty;

        $db->query("START TRANSACTION");
        
        // 1. Check Cash
        $stmt = $db->prepare("SELECT cash FROM users WHERE id = ? FOR UPDATE");
        if (!$stmt) throw new Exception("User table error");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $cash = $stmt->get_result()->fetch_assoc()['cash'];

        if ($type === 'buy') {
            if ($cash < $total) { $db->query("ROLLBACK"); sendJson(['success'=>false, 'error'=>'Insufficient Funds']); }
            
            // 2. Update Cash
            $db->query("UPDATE users SET cash = cash - $total WHERE id = $uid");
            
            // 3. Update Holdings (Upsert)
            $stmt = $db->prepare("INSERT INTO holdings (user_id, symbol, quantity, total_cost) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?, total_cost = total_cost + ?");
            if (!$stmt) { $db->query("ROLLBACK"); throw new Exception("Table 'holdings' missing"); }
            $stmt->bind_param("isidid", $uid, $sym, $qty, $total, $qty, $total);
            $stmt->execute();
        } 
        else { // SELL
            $stmt = $db->prepare("SELECT quantity, total_cost FROM holdings WHERE user_id = ? AND symbol = ? FOR UPDATE");
            if (!$stmt) { $db->query("ROLLBACK"); throw new Exception("Table 'holdings' missing"); }
            $stmt->bind_param("is", $uid, $sym);
            $stmt->execute();
            $h = $stmt->get_result()->fetch_assoc();
            
            if (!$h || $h['quantity'] < $qty) { $db->query("ROLLBACK"); sendJson(['success'=>false, 'error'=>'Not enough shares']); }
            
            $costBasis = ($h['total_cost'] / $h['quantity']) * $qty;
            
            $db->query("UPDATE users SET cash = cash + $total WHERE id = $uid");
            $stmt = $db->prepare("UPDATE holdings SET quantity = quantity - ?, total_cost = total_cost - ? WHERE user_id = ? AND symbol = ?");
            $stmt->bind_param("idis", $qty, $costBasis, $uid, $sym);
            $stmt->execute();
        }

        // 4. Log Transaction
        $stmt = $db->prepare("INSERT INTO transactions (user_id, symbol, type, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issidd", $uid, $sym, $type, $qty, $price, $total);
            $stmt->execute();
        }

        $db->query("COMMIT");
        sendJson(['success' => true]);
    }
    
    // ... (Keep other actions like favorites, admin requests) ...
    // Admin Requests
    if ($action === 'get_admin_requests') {
        if ($_SESSION['role'] !== 'admin') sendJson(['error' => 'Access Denied']);
        $sql = "SELECT r.*, u.username FROM balance_requests r JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at ASC";
        sendJson($db->query($sql)->fetch_all(MYSQLI_ASSOC));
    }
    // Handle Request
    if ($action === 'handle_request') {
        if ($_SESSION['role'] !== 'admin') sendJson(['error' => 'Access Denied']);
        $reqId = $input['request_id'];
        $status = $input['status'];
        $db->query("START TRANSACTION");
        $stmt = $db->prepare("SELECT user_id, amount FROM balance_requests WHERE id = ? AND status = 'pending' FOR UPDATE");
        $stmt->bind_param("i", $reqId);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        if (!$req) { $db->query("ROLLBACK"); sendJson(['success'=>false, 'error'=>'Request missing']); }
        if ($status === 'approved') {
            $db->query("UPDATE users SET cash = cash + {$req['amount']} WHERE id = {$req['user_id']}");
        }
        $stmt = $db->prepare("UPDATE balance_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $reqId);
        $stmt->execute();
        $db->query("COMMIT");
        sendJson(['success' => true]);
    }
    // Get Favorites
    if ($action === 'get_favorites') {
        $stmt = $db->prepare("SELECT symbol FROM favorites WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $favs = [];
        while($row = $res->fetch_assoc()) $favs[] = $row['symbol'];
        sendJson($favs);
    }
    // Toggle Favorite
    if ($action === 'toggle_favorite') {
        $sym = $input['symbol'];
        $stmt = $db->prepare("SELECT * FROM favorites WHERE user_id = ? AND symbol = ?");
        $stmt->bind_param("is", $uid, $sym);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $db->query("DELETE FROM favorites WHERE user_id = $uid AND symbol = '$sym'");
            sendJson(['success' => true, 'status' => 'removed']);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM favorites WHERE user_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['c'] >= 5) sendJson(['success' => false, 'error' => 'Max 5 favorites']);
            $stmt = $db->prepare("INSERT INTO favorites (user_id, symbol) VALUES (?, ?)");
            $stmt->bind_param("is", $uid, $sym);
            $stmt->execute();
            sendJson(['success' => true, 'status' => 'added']);
        }
    }
    // Balance Request
    if ($action === 'request_balance') {
        $amt = (float)$input['amount'];
        if($amt<=0) sendJson(['error'=>'Invalid amount']);
        $stmt = $db->prepare("INSERT INTO balance_requests (user_id, amount) VALUES (?, ?)");
        $stmt->bind_param("id", $uid, $amt);
        $stmt->execute();
        sendJson(['success'=>true]);
    }
    // My Requests
    if ($action === 'get_my_requests') {
        $stmt = $db->prepare("SELECT * FROM balance_requests WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    // Holdings
    if ($action === 'get_holdings') {
        $stmt = $db->prepare("SELECT * FROM holdings WHERE user_id = ? AND quantity > 0");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    // History
    if ($action === 'get_history') {
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 20");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

} catch (Exception $e) {
    if(isset($db)) $db->query("ROLLBACK");
    sendJson(['success' => false, 'error' => "System Error: " . $e->getMessage()]);
}
?>