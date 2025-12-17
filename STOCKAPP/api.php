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
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function sendJson($data) { echo json_encode($data); exit; }

function sendEmailOTP($email, $code) {
    $subject = "Your Verification Code - StockTrade";
    
    // HTML Email Template
    $msg = "
    <html>
    <head>
      <style>
        body { font-family: sans-serif; background-color: #f3f4f6; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: 800; letter-spacing: -1px; }
        .code { font-size: 32px; font-family: monospace; font-weight: bold; background: #f3f4f6; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 5px; }
        .footer { font-size: 12px; color: #6b7280; text-align: center; margin-top: 20px; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <div class='title'>TRADE.</div>
        </div>
        <p>Hello,</p>
        <p>Please use the following code to complete your login verification:</p>
        <div class='code'>$code</div>
        <p>This code will expire in 10 minutes.</p>
        <div class='footer'>If you didn't request this, you can safely ignore this email.</div>
      </div>
    </body>
    </html>
    ";

    // HTML Headers
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@devbartos.cz" . "\r\n";
    $headers .= "Reply-To: no-reply@devbartos.cz" . "\r\n";
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

    // 3. Check for Rate Limit or Error
    if (isset($data['Information']) || isset($data['Note'])) {
        return getMockQuote($symbol, true);
    }

    // 4. Process Response
    if (isset($data['Global Quote']) && !empty($data['Global Quote']['05. price'])) {
        $q = $data['Global Quote'];
        $clean = [
            'c' => (float)$q['05. price'],
            'd' => (float)$q['09. change'],
            'dp' => (float)str_replace('%', '', $q['10. change percent']),
            'is_mock' => false
        ];
        $_SESSION['price_cache'][$symbol] = ['time' => time(), 'data' => $clean];
        return $clean;
    }
    
    return getMockQuote($symbol, false);
}

function getMockQuote($symbol, $rateLimited = false) {
    $bases = [
        'AAPL'=>225, 'MSFT'=>415, 'GOOGL'=>175, 'AMZN'=>185, 'TSLA'=>240, 
        'NVDA'=>120, 'META'=>500, 'NFLX'=>650, 'AMD'=>150, 'INTC'=>30
    ];
    $base = $bases[$symbol] ?? 150.00;
    
    $seed = crc32($symbol) + floor(time() / 15);
    srand($seed);
    $change = (rand(-500, 500) / 100);
    
    return [
        'c' => $base + $change,
        'd' => $change,
        'dp' => ($change/$base)*100,
        'is_mock' => true,
        'rate_limit' => $rateLimited
    ];
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
    
    $base = 150;
    for($i=30; $i>=0; $i--) {
        $base += rand(-5, 5);
        $c[] = $base;
        $t[] = time() - ($i*86400);
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
                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
                $db->query("UPDATE users SET otp_code = '$otp', otp_expiry = '$expiry' WHERE id = " . $user['id']);
                sendEmailOTP($user['email'], $otp);
                
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
        $otp = ($role === 'admin') ? NULL : rand(100000, 999999);
        $expiry = ($role === 'admin') ? NULL : date("Y-m-d H:i:s", strtotime("+10 minutes"));
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, is_verified, otp_code, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $u, $e, $p, $role, $verified, $otp, $expiry);
        
        if ($stmt->execute()) {
            if ($role === 'admin') {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['role'] = 'admin';
                sendJson(['success' => true, 'requires_otp' => false]);
            } else {
                // FIXED: Sending real OTP variable instead of hardcoded 123456
                sendEmailOTP($e, $otp);
                sendJson(['success' => true, 'email' => $e]);
            }
        }
        sendJson(['success' => false, 'error' => 'Username/Email taken']);
    }

    if ($action === 'verify_otp') {
        $email = $input['email'];
        $code = $input['code'];
        $stmt = $db->prepare("SELECT id, otp_code, otp_expiry, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $user['otp_code'] === $code && strtotime($user['otp_expiry']) > time()) {
            $db->query("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = " . $user['id']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            sendJson(['success' => true]);
        }
        sendJson(['success' => false, 'error' => 'Invalid/Expired Code']);
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

    // --- MARKET DATA ---
    if ($action === 'get_prices') {
        $p = [];
        foreach($TRACKED_STOCKS as $i => $s) {
            // Fetch Real Data for first 2 stocks, Mock the rest
            if ($i < 2) {
                $p[$s] = getQuote($s);
            } else {
                $p[$s] = getMockQuote($s);
            }
        }
        sendJson($p);
    }

    // ... (Keep existing holding, candles, trade, favorites, admin logic unchanged) ...
    // Note: Re-paste the rest of the logic from previous version here if creating full file.
    // For this update I'm focusing on the fixes requested. I will include everything below.

    if ($action === 'get_holding') {
        $sym = strtoupper($input['symbol']);
        try {
            $stmt = $db->prepare("SELECT quantity FROM holdings WHERE user_id = ? AND symbol = ?");
            if (!$stmt) throw new Exception("Table 'holdings' missing");
            $stmt->bind_param("is", $uid, $sym);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            sendJson($res ? $res : ['quantity' => 0]);
        } catch (Exception $e) { sendJson(['quantity' => 0, 'error' => $e->getMessage()]); }
    }

    if ($action === 'get_candles') { sendJson(getCandles($input['symbol'])); }

    if ($action === 'trade') {
        $type = $input['type'];
        $sym = strtoupper($input['symbol']);
        $qty = (int)$input['quantity'];
        $q = getQuote($sym);
        $price = $q['c'];
        $total = $price * $qty;

        $db->query("START TRANSACTION");
        $stmt = $db->prepare("SELECT cash FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $cash = $stmt->get_result()->fetch_assoc()['cash'];

        if ($type === 'buy') {
            if ($cash < $total) { $db->query("ROLLBACK"); sendJson(['success'=>false, 'error'=>'Insufficient Funds']); }
            $db->query("UPDATE users SET cash = cash - $total WHERE id = $uid");
            $stmt = $db->prepare("INSERT INTO holdings (user_id, symbol, quantity, total_cost) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?, total_cost = total_cost + ?");
            $stmt->bind_param("isidid", $uid, $sym, $qty, $total, $qty, $total);
            $stmt->execute();
        } else { 
            $stmt = $db->prepare("SELECT quantity, total_cost FROM holdings WHERE user_id = ? AND symbol = ? FOR UPDATE");
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
        $stmt = $db->prepare("INSERT INTO transactions (user_id, symbol, type, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) { $stmt->bind_param("issidd", $uid, $sym, $type, $qty, $price, $total); $stmt->execute(); }
        $db->query("COMMIT");
        sendJson(['success' => true]);
    }

    if ($action === 'get_admin_requests') {
        if ($_SESSION['role'] !== 'admin') sendJson(['error' => 'Access Denied']);
        $sql = "SELECT r.*, u.username FROM balance_requests r JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at ASC";
        sendJson($db->query($sql)->fetch_all(MYSQLI_ASSOC));
    }

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

    if ($action === 'get_favorites') {
        $stmt = $db->prepare("SELECT symbol FROM favorites WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $favs = [];
        while($row = $res->fetch_assoc()) $favs[] = $row['symbol'];
        sendJson($favs);
    }

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

    if ($action === 'request_balance') {
        $amt = (float)$input['amount'];
        if($amt<=0) sendJson(['error'=>'Invalid amount']);
        $stmt = $db->prepare("INSERT INTO balance_requests (user_id, amount) VALUES (?, ?)");
        $stmt->bind_param("id", $uid, $amt);
        $stmt->execute();
        sendJson(['success'=>true]);
    }

    if ($action === 'get_my_requests') {
        $stmt = $db->prepare("SELECT * FROM balance_requests WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if ($action === 'get_holdings') {
        $stmt = $db->prepare("SELECT * FROM holdings WHERE user_id = ? AND quantity > 0");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if ($action === 'get_history') {
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 20");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        sendJson($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if ($action === 'change_password') {
        $curr = $input['current_password'];
        $new = $input['new_password'];
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (password_verify($curr, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $uid);
            $stmt->execute();
            sendJson(['success' => true]);
        }
        sendJson(['success' => false, 'error' => 'Incorrect current password']);
    }

} catch (Exception $e) {
    if(isset($db)) $db->query("ROLLBACK");
    sendJson(['success' => false, 'error' => "System Error: " . $e->getMessage()]);
}
?>