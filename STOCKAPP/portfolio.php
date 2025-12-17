<?php require 'header.php'; ?>
<?php require 'nav.php'; ?>

<main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24 relative">
    <div class="max-w-6xl mx-auto fade-in">
        <h2 class="text-3xl md:text-4xl font-bold mb-6 md:mb-8">My Portfolio</h2>
        
        <!-- Responsive Container: Block on mobile, Table on desktop -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm flex flex-col overflow-hidden" id="portfolioContainer">
            <table class="w-full text-left block md:table"> 
                <thead class="bg-gray-50 text-xs uppercase font-bold text-gray-400 hidden md:table-header-group">
                    <tr>
                        <th class="p-6">Asset</th>
                        <th class="p-6 text-right">Shares</th>
                        <th class="p-6 text-right">Avg Cost</th>
                        <th class="p-6 text-right">Current Price</th>
                        <th class="p-6 text-right">Return</th>
                        <th class="p-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="block md:table-row-group divide-y divide-gray-100" id="portfolioBody">
                    <tr><td colspan="6" class="p-6 text-center text-gray-400">Loading holdings...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State (Hidden by default) -->
        <div id="emptyState" class="hidden text-center py-20 text-gray-400 border-2 border-dashed border-gray-200 rounded-3xl">
            <div class="text-6xl mb-4">💼</div>
            <p>No active investments.</p>
            <a href="market.php" class="inline-block mt-4 text-black font-bold underline">Start Trading</a>
        </div>
    </div>
</main>

<script>
    let myHoldings = [];

    window.renderPage = function() {
        if(!myHoldings.length) return; 

        const tbody = document.getElementById('portfolioBody');
        tbody.innerHTML = '';

        myHoldings.forEach(h => {
            const sym = h.symbol;
            const market = window.marketData[sym];
            const avg = parseFloat(h.total_cost) / parseInt(h.quantity);
            const qty = parseInt(h.quantity);

            // If market data isn't loaded yet, show placeholder
            const currPrice = market ? market.c : avg; 
            const gain = (currPrice - avg) * qty;
            const gainPct = avg > 0 ? ((currPrice - avg) / avg) * 100 : 0;
            const color = gain >= 0 ? 'text-green-600' : 'text-red-600';

            const tr = document.createElement('tr');
            // Responsive Classes: Block on mobile (Card look), Table Row on Desktop
            tr.className = "block md:table-row border-b border-gray-100 last:border-0 p-6 md:p-0 hover:bg-gray-50 transition group";
            
            // We insert mobile labels (<span class="md:hidden ...">) that hide on desktop
            tr.innerHTML = `
                <td class="flex justify-between items-center md:table-cell md:p-6 pb-2 md:pb-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Asset</span>
                    <div class="flex items-center gap-3">
                        <img src="${getLogo(sym)}" class="w-8 h-8 rounded-full bg-gray-50 object-contain p-1" onerror="this.src='https://ui-avatars.com/api/?name=${sym}&background=000&color=fff'">
                        <span class="font-bold text-lg">${sym}</span>
                    </div>
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Shares</span>
                    <span class="font-mono">${qty}</span>
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Avg Cost</span>
                    <span class="font-mono text-gray-400">$${avg.toFixed(2)}</span>
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Current</span>
                    <span class="font-mono font-bold">$${currPrice.toFixed(2)}</span>
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Return</span>
                    <span class="font-bold ${color}">
                        ${gain >= 0 ? '+' : ''}$${gain.toFixed(2)} 
                        <span class="text-xs font-normal text-gray-400 ml-1">(${gainPct.toFixed(2)}%)</span>
                    </span>
                </td>
                <td class="md:table-cell md:text-right md:p-6 pt-4 md:pt-6">
                    <a href="detail.php?symbol=${sym}" class="block w-full md:inline-block md:w-auto bg-black text-white px-4 py-3 md:py-2 rounded-xl md:rounded-lg text-sm font-bold hover:bg-gray-800 text-center transition">Trade</a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    };

    window.onload = async function() {
        await initGlobal();
        // Fetch Holdings specifically for this page
        const res = await fetch('api.php?action=get_holdings');
        myHoldings = await res.json();
        
        // Handle empty state explicitly
        if(myHoldings.length === 0) {
             document.getElementById('portfolioContainer').classList.add('hidden');
             document.getElementById('emptyState').classList.remove('hidden');
        } else {
            renderPage();
        }
    };
</script>
</body>
</html>