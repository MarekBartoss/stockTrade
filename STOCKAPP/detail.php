<?php require 'header.php'; ?>
<?php require 'nav.php'; ?>
<?php 
$symbol = $_GET['symbol'] ?? 'AAPL'; 

// Server-side Logo Mapping for immediate rendering
$logo_map = [
    'AAPL' => 'apple.com', 'MSFT' => 'microsoft.com', 'GOOGL' => 'google.com', 'AMZN' => 'amazon.com',
    'TSLA' => 'tesla.com', 'NVDA' => 'nvidia.com', 'META' => 'meta.com', 'NFLX' => 'netflix.com',
    'AMD' => 'amd.com', 'INTC' => 'intel.com', 'SPY' => 'ssga.com', 'QQQ' => 'invesco.com',
    'BTC-USD' => 'bitcoin.org', 'ETH-USD' => 'ethereum.org', 'COIN' => 'coinbase.com',
    'JPM' => 'jpmorganchase.com', 'DIS' => 'disney.com', 'WMT' => 'walmart.com', 
    'SBUX' => 'starbucks.com', 'NKE' => 'nike.com'
];
$domain = $logo_map[$symbol] ?? null;
$logo_src = $domain ? "https://logo.clearbit.com/$domain" : "https://ui-avatars.com/api/?name=$symbol&background=000&color=fff&size=64";
?>

<main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24 relative">
    <!-- Mobile Back Button -->
    <a href="market.php" class="md:hidden inline-flex items-center gap-2 text-gray-500 mb-4 font-bold text-sm hover:text-black transition">
        <span>&larr;</span> Back to Market
    </a>

    <div class="max-w-6xl mx-auto fade-in flex flex-col md:flex-row gap-6 md:gap-8">
        
        <!-- CHART SECTION -->
        <div class="flex-1 bg-white p-4 md:p-6 rounded-3xl border border-gray-100 flex flex-col min-h-[400px] md:min-h-[500px]">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                <div class="flex items-center gap-4">
                    <!-- Added Logo with PHP Source -->
                    <img src="<?php echo $logo_src; ?>" 
                         class="w-12 h-12 md:w-16 md:h-16 rounded-full bg-gray-50 object-contain p-1" 
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo $symbol; ?>&background=000&color=fff'"
                         id="detailLogo">
                    
                    <div>
                        <h1 class="text-4xl md:text-6xl font-black tracking-tighter leading-tight" id="dispSym"><?php echo $symbol; ?></h1>
                        <div class="text-2xl md:text-3xl font-mono font-bold mt-1" id="dispPrice">---</div>
                    </div>
                </div>
                <div class="px-3 py-1 md:px-4 md:py-2 rounded-xl text-lg md:text-xl font-bold whitespace-nowrap" id="dispChange">---</div>
            </div>
            <div class="flex-1 relative w-full h-full min-h-[250px]">
                <canvas id="detailChart"></canvas>
            </div>
        </div>

        <!-- TRADE PANEL -->
        <div class="w-full md:w-96 bg-white p-6 md:p-8 rounded-3xl border border-gray-100 shadow-xl h-fit">
            <h3 class="text-2xl font-bold mb-2">Trade</h3>
            
            <!-- OWNED SHARES DISPLAY -->
            <div class="mb-6 flex justify-between items-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                <span class="text-xs font-bold uppercase text-gray-400">You Own</span>
                <span class="font-mono font-bold text-lg" id="userHoldingQty">0</span>
            </div>

            <div class="mb-6">
                <label class="text-xs font-bold uppercase text-gray-400">Quantity</label>
                <!-- FIXED BUTTONS: Wider and flex-wrap handled -->
                <div class="flex items-center gap-3 mt-2 w-full">
                    <button onclick="adjustQty(-1)" class="w-16 h-14 rounded-xl bg-gray-100 hover:bg-gray-200 font-bold text-2xl transition select-none text-gray-600 hover:text-black flex-shrink-0 flex items-center justify-center">-</button>
                    <input type="number" id="qty" value="1" min="1" class="flex-1 h-14 bg-gray-50 rounded-xl text-center text-2xl font-mono font-bold outline-none focus:ring-2 focus:ring-black border-none w-full min-w-0">
                    <button onclick="adjustQty(1)" class="w-16 h-14 rounded-xl bg-gray-100 hover:bg-gray-200 font-bold text-2xl transition select-none text-gray-600 hover:text-black flex-shrink-0 flex items-center justify-center">+</button>
                </div>
            </div>

            <div class="space-y-4 mb-8">
                <div class="flex justify-between font-bold text-lg">
                    <span>Estimated Cost</span>
                    <span id="totalVal">$0.00</span>
                </div>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>Cash Available</span>
                    <span id="userCashDisplay">---</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button onclick="trade('buy')" id="btnBuy" class="py-4 rounded-xl bg-black text-white font-bold hover:shadow-lg transition flex items-center justify-center gap-2">
                    BUY
                </button>
                <button onclick="trade('sell')" id="btnSell" class="py-4 rounded-xl border-2 border-black font-bold hover:bg-gray-50 transition flex items-center justify-center gap-2">
                    SELL
                </button>
            </div>
        </div>
    </div>

    <!-- LOCAL SUCCESS MODAL (Guaranteed to exist) -->
    <div id="tradeSuccessModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full shadow-2xl transform scale-95 transition-all duration-200 text-center m-4">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-bold mb-2">Trade Executed!</h3>
            <p class="text-gray-500 mb-6" id="successMsg">Transaction completed successfully.</p>
            <button onclick="closeSuccessModal()" class="w-full py-3 rounded-xl bg-black text-white font-bold hover:bg-gray-800 transition hover:shadow-lg">Awesome</button>
        </div>
    </div>
</main>

<script>
    const SYMBOL = "<?php echo $symbol; ?>";
    let chart;
    let currentPrice = 0;

    // 1. Initial Load
    window.onload = async function() {
        await initGlobal();
        document.getElementById('userCashDisplay').innerText = '$' + parseFloat(window.userInfo.cash).toFixed(2);
        
        // No need to load logo via JS anymore, handled by PHP. 
        // JS getLogo acts as fallback/utility for other pages if needed.

        try {
            const [cData, hData] = await Promise.all([
                fetch('api.php?action=get_candles', {method:'POST', body:JSON.stringify({symbol:SYMBOL})}).then(r=>r.json()),
                fetch('api.php?action=get_holding', {method:'POST', body:JSON.stringify({symbol:SYMBOL})}).then(r=>r.json())
            ]);
            
            // Check for Database Schema Errors explicitly
            if (hData.error) {
                window.showAlert("Database Error", hData.error + ". Please run the setup SQL.", true);
            } else {
                document.getElementById('userHoldingQty').innerText = hData.quantity || 0;
            }

            if(cData.s === 'ok') renderChart(cData);
            else console.error("Chart data unavailable");

        } catch(e) {
            console.error(e);
            window.showAlert("Connection Error", "Could not load stock details.", true);
        }
        
        renderPage();
    };

    // 2. Live Updates
    window.renderPage = function() {
        if(window.marketData[SYMBOL]) {
            const data = window.marketData[SYMBOL];
            currentPrice = data.c;
            
            document.getElementById('dispPrice').innerText = '$' + data.c.toFixed(2);
            
            const badge = document.getElementById('dispChange');
            // Fixed formatting to 2 decimal places
            badge.innerText = (data.d >= 0 ? '+' : '') + data.d.toFixed(2) + ` (${data.dp.toFixed(2)}%)`;
            badge.className = `px-3 py-1 md:px-4 md:py-2 rounded-xl text-lg md:text-xl font-bold ${data.d>=0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            
            calcTotal();
        }
    };

    // 3. Interactive Chart
    function renderChart(data) {
        const ctx = document.getElementById('detailChart').getContext('2d');
        const dates = data.t.map(t => new Date(t*1000).toLocaleDateString());
        
        if(chart) chart.destroy();

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    data: data.c,
                    borderColor: '#000',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    fill: true,
                    backgroundColor: (ctx) => {
                        const grad = ctx.chart.ctx.createLinearGradient(0,0,0,400);
                        grad.addColorStop(0, 'rgba(0,0,0,0.1)');
                        grad.addColorStop(1, 'rgba(0,0,0,0)');
                        return grad;
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { display: true, position: 'right' } }
            }
        });
    }

    document.getElementById('qty').addEventListener('input', calcTotal);
    function calcTotal() {
        const q = document.getElementById('qty').value;
        document.getElementById('totalVal').innerText = '$' + (q * currentPrice).toFixed(2);
    }

    function adjustQty(delta) {
        const el = document.getElementById('qty');
        let val = parseInt(el.value) || 0;
        val += delta;
        if(val < 1) val = 1;
        el.value = val;
        calcTotal();
    }

    async function trade(type) {
        const q = document.getElementById('qty').value;
        
        // Robust Confirm Logic (Uses global if available, falls back to native if buggy)
        let confirmed = false;
        if (window.showConfirm) {
            confirmed = await window.showConfirm(
                `Confirm ${type.toUpperCase()}`, 
                `Are you sure you want to ${type} ${q} shares of ${SYMBOL}?`
            );
        } else {
            confirmed = confirm(`Are you sure you want to ${type} ${q} shares of ${SYMBOL}?`);
        }

        if(!confirmed) return;

        // Loading State
        const btn = document.getElementById(type === 'buy' ? 'btnBuy' : 'btnSell');
        const originalText = btn.innerText;
        btn.innerHTML = `<div class="spinner w-5 h-5 border-2"></div> Processing`;
        btn.disabled = true;

        try {
            const res = await fetch('api.php?action=trade', {
                method: 'POST', 
                body: JSON.stringify({type, symbol:SYMBOL, quantity:q})
            });
            const data = await res.json();
            
            if(data.success) {
                // Show LOCAL Success Modal
                const modal = document.getElementById('tradeSuccessModal');
                document.getElementById('successMsg').innerText = `Successfully ${type==='buy'?'bought':'sold'} ${q} shares of ${SYMBOL}.`;
                
                modal.classList.remove('hidden');
                // Animation frame trick
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    modal.children[0].classList.remove('scale-95');
                    modal.children[0].classList.add('scale-100');
                }, 10);

            } else {
                // Error Alert
                if(window.showAlert) {
                    await window.showAlert("Trade Failed", data.error, true);
                } else {
                    alert(data.error);
                }
            }
        } catch(e) {
            alert("Connection Error. Please check your internet.");
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function closeSuccessModal() {
        location.reload();
    }
</script>