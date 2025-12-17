<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'auth.php') {
    header("Location: auth.php");
    exit;
}
$is_first_load = !isset($_SESSION['has_seen_loader']);
if ($is_first_load) { $_SESSION['has_seen_loader'] = true; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>StockTrade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Space Grotesk', sans-serif; background: #f8fafc; color: #0f172a; }
        .fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .spinner { width: 40px; height: 40px; border: 4px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; }
        .spinner-dark { width: 40px; height: 40px; border: 4px solid #000; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal-backdrop { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    </style>
</head>
<body class="flex flex-col h-screen overflow-hidden">

    <?php if ($is_first_load): ?>
        <div id="globalLoader" class="fixed inset-0 z-[100] bg-black flex flex-col items-center justify-center text-white transition-opacity duration-500">
            <h1 class="text-6xl font-black tracking-tighter mb-4">TRADE.</h1>
            <div class="spinner mb-4"></div>
            <p class="text-gray-400 font-mono text-sm">Connecting to Alpha Vantage...</p>
        </div>
    <?php endif; ?>

    <!-- CONFIRM MODAL -->
    <div id="confirmModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 modal-backdrop transition-opacity duration-200">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl transform scale-95 modal-content transition-all duration-200 m-4" id="confirmBox">
            <h3 class="text-2xl font-bold mb-2" id="confirmTitle">Confirm</h3>
            <p class="text-gray-500 mb-8" id="confirmText">Are you sure?</p>
            <div class="flex gap-4">
                <button onclick="window.closeConfirm(false)" class="flex-1 py-3 rounded-xl border border-gray-200 font-bold hover:bg-gray-50 transition">Cancel</button>
                <button onclick="window.closeConfirm(true)" class="flex-1 py-3 rounded-xl bg-black text-white font-bold hover:bg-gray-800 transition shadow-lg">Confirm</button>
            </div>
        </div>
    </div>

    <!-- ALERT/SUCCESS MODAL (Supports HTML) -->
    <div id="alertModal" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 modal-backdrop transition-opacity duration-200">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl transform scale-95 modal-content transition-all duration-200 m-4 text-center" id="alertBox">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4" id="alertIcon">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-bold mb-2" id="alertTitle">Success</h3>
            <div class="text-gray-500 mb-6" id="alertText">Operation completed.</div>
            <button onclick="window.closeAlert()" class="w-full py-3 rounded-xl bg-black text-white font-bold hover:bg-gray-800 transition shadow-lg">Okay</button>
        </div>
    </div>

    <script>
        window.marketData = {};
        window.userInfo = { cash: 0 };

        async function initGlobal() {
            try {
                const userPromise = fetch('api.php?action=user_info').then(res => res.json());
                const pricesPromise = fetch('api.php?action=get_prices').then(res => res.json());

                const [userData, priceData] = await Promise.all([userPromise, pricesPromise]);

                window.userInfo = userData;
                window.marketData = priceData;
                
                updateUserUI();
                
                // CHECK FOR RATE LIMIT NOTIFICATION
                // Logic: If 'AAPL' (first stock) is simulated/mock due to limits
                const sample = window.marketData['AAPL'];
                if (sample && sample.rate_limit) {
                   // Check if user suppressed this warning
                   if (!sessionStorage.getItem('suppressApiWarning')) {
                       setTimeout(() => {
                           const msg = `
                               <p>The API call limit (5/min) was reached.</p>
                               <p class="text-xs mt-1 text-gray-400">Showing simulated data.</p>
                               <div class="mt-4 flex items-center justify-center gap-2 text-sm text-black font-bold">
                                   <input type="checkbox" id="dontShowAgain" class="w-4 h-4 rounded border-gray-300 text-black focus:ring-black">
                                   <label for="dontShowAgain">Don't show next time</label>
                               </div>
                           `;
                           window.showAlert("API Limit Reached", msg, true);
                           // Hook into the checkbox after rendering
                           setTimeout(() => {
                               document.getElementById('dontShowAgain')?.addEventListener('change', (e) => {
                                   if(e.target.checked) sessionStorage.setItem('suppressApiWarning', 'true');
                                   else sessionStorage.removeItem('suppressApiWarning');
                               });
                           }, 100);
                       }, 500);
                   }
                }

                if(window.renderPage) window.renderPage();

                removeLoader();
                setInterval(refreshPrices, 15000);

            } catch(e) {
                console.error("Init failed", e);
                removeLoader();
            }
        }

        function removeLoader() {
            const loader = document.getElementById('globalLoader');
            if(loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 300);
            }
        }
        setTimeout(removeLoader, 2000);

        async function refreshPrices() {
            try {
                const res = await fetch('api.php?action=get_prices');
                window.marketData = await res.json();
                if(window.renderPage) window.renderPage();
            } catch(e) { console.error(e); }
        }

        function updateUserUI() {
            const cashVal = '$' + parseFloat(window.userInfo.cash).toLocaleString(undefined, {minimumFractionDigits:2});
            const elDesktop = document.getElementById('navCash');
            if(elDesktop) elDesktop.innerText = cashVal;
            const elMobile = document.getElementById('navCashMobile');
            if(elMobile) elMobile.innerText = cashVal;
        }

        function getLogo(sym) {
            const domains = { 'AAPL': 'apple.com', 'MSFT': 'microsoft.com', 'GOOGL': 'google.com', 'AMZN': 'amazon.com', 'TSLA': 'tesla.com', 'NVDA': 'nvidia.com', 'META': 'meta.com', 'NFLX': 'netflix.com', 'AMD': 'amd.com', 'INTC': 'intel.com', 'SPY': 'ssga.com', 'QQQ': 'invesco.com', 'BTC-USD': 'bitcoin.org', 'ETH-USD': 'ethereum.org', 'COIN': 'coinbase.com', 'JPM': 'jpmorganchase.com', 'DIS': 'disney.com', 'WMT': 'walmart.com', 'SBUX': 'starbucks.com', 'NKE': 'nike.com' };
            if (domains[sym]) return `https://logo.clearbit.com/${domains[sym]}`;
            return `https://ui-avatars.com/api/?name=${sym}&background=000&color=fff&size=64`;
        }

        window.confirmCallback = null;
        window.showConfirm = function(title, text) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const box = document.getElementById('confirmBox');
                document.getElementById('confirmTitle').innerText = title;
                document.getElementById('confirmText').innerText = text;
                modal.classList.remove('hidden');
                setTimeout(() => { modal.classList.remove('opacity-0'); box.classList.remove('scale-95'); box.classList.add('scale-100'); }, 10);
                window.confirmCallback = (result) => {
                    box.classList.remove('scale-100'); box.classList.add('scale-95'); modal.classList.add('opacity-0');
                    setTimeout(() => { modal.classList.add('hidden'); resolve(result); }, 200);
                };
            });
        };
        window.closeConfirm = function(result) {
            if (window.confirmCallback) window.confirmCallback(result);
        };

        window.closeAlertCallback = null;
        window.showAlert = function(title, text, isError = false) {
            return new Promise((resolve) => {
                const modal = document.getElementById('alertModal');
                const box = document.getElementById('alertBox');
                const icon = document.getElementById('alertIcon');
                
                document.getElementById('alertTitle').innerText = title;
                // Use innerHTML instead of innerText to support HTML message (like checkboxes)
                document.getElementById('alertText').innerHTML = text;

                if(isError) {
                    icon.className = "w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4";
                    icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>';
                } else {
                    icon.className = "w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4";
                    icon.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>';
                }
                
                modal.classList.remove('hidden');
                setTimeout(() => { modal.classList.remove('opacity-0'); box.classList.remove('scale-95'); box.classList.add('scale-100'); }, 10);
                
                window.closeAlertCallback = () => {
                    box.classList.remove('scale-100'); box.classList.add('scale-95'); modal.classList.add('opacity-0');
                    setTimeout(() => { modal.classList.add('hidden'); resolve(); }, 200);
                };
            });
        };
        window.closeAlert = function() {
            if (window.closeAlertCallback) window.closeAlertCallback();
        };
    </script>
</body>
</html>