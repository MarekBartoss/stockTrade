<?php require 'header.php'; ?>
<?php require 'nav.php'; ?>

<main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24 relative">
    <div class="max-w-7xl mx-auto fade-in">
        <div class="flex justify-between items-end mb-6">
            <h2 class="text-4xl font-bold">Market</h2>
            <div class="text-sm text-gray-400 animate-pulse">Live Updates (15s)</div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="marketGrid">
            <!-- Loader Skeletons -->
            <div class="col-span-full text-center py-20 text-gray-300">Loading Market Data...</div>
        </div>
    </div>
</main>

<script>
    let myFavorites = [];

    // Helper: Toggle Favorite
    async function toggleStar(e, sym) {
        e.stopPropagation(); // Stop click from going to Detail page
        const res = await fetch('api.php?action=toggle_favorite', {method:'POST', body:JSON.stringify({symbol:sym})});
        const data = await res.json();
        
        if(data.success) {
            // Update local list
            if(data.status === 'added') myFavorites.push(sym);
            else myFavorites = myFavorites.filter(s => s !== sym);
            // Re-render to sort
            renderPage();
        } else {
            await window.showAlert("Limit Reached", data.error, true);
        }
    }

    window.renderPage = function() {
        const grid = document.getElementById('marketGrid');
        if (!window.marketData || Object.keys(window.marketData).length === 0) return;
        
        grid.innerHTML = '';

        // SORTING LOGIC: Favorites First
        const symbols = Object.keys(window.marketData).sort((a, b) => {
            const isFavA = myFavorites.includes(a);
            const isFavB = myFavorites.includes(b);
            if (isFavA && !isFavB) return -1;
            if (!isFavA && isFavB) return 1;
            return 0; // Keep original order otherwise
        });

        symbols.forEach(sym => {
            const data = window.marketData[sym];
            const color = data.d >= 0 ? 'text-green-600' : 'text-red-600';
            const bg = data.d >= 0 ? 'bg-green-100' : 'bg-red-100';
            const isFav = myFavorites.includes(sym);
            const starClass = isFav ? 'text-yellow-400 fill-current' : 'text-gray-300 hover:text-yellow-400';

            const card = document.createElement('div');
            card.className = "bg-white p-6 rounded-2xl border border-gray-100 flex flex-col justify-between h-40 cursor-pointer hover:shadow-lg transition group relative";
            card.onclick = () => location.href = `detail.php?symbol=${sym}`;
            
            card.innerHTML = `
                <div class="absolute top-4 right-4 z-10 p-2" onclick="toggleStar(event, '${sym}')">
                    <svg class="w-6 h-6 ${starClass} transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                </div>

                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <img src="${getLogo(sym)}" class="w-10 h-10 rounded-full bg-gray-50 object-contain p-1" onerror="this.src='https://ui-avatars.com/api/?name=${sym}'">
                        <div class="font-black text-2xl group-hover:underline">${sym}</div>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <div>
                        <div class="text-3xl font-mono font-bold tracking-tight">$${data.c.toFixed(2)}</div>
                        <div class="text-sm text-gray-400">Real-time</div>
                    </div>
                    <div class="text-xs font-bold ${color} ${bg} px-2 py-1 rounded mb-1">
                        ${data.d >= 0 ? '+' : ''}${data.dp.toFixed(2)}%
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    };

    window.onload = async function() {
        // Fetch Favorites First
        const fRes = await fetch('api.php?action=get_favorites');
        myFavorites = await fRes.json();
        
        await initGlobal();
    };
</script>
</body>
</html>