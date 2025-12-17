<?php require 'header.php'; ?>
<?php require 'nav.php'; ?>

<main class="flex-1 overflow-y-auto p-4 md:p-8 pb-24 relative">
    <div class="max-w-4xl mx-auto fade-in">
        <h2 class="text-3xl md:text-4xl font-bold mb-6 md:mb-8">Account</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mb-8">
            
            <!-- Left Column: Stats & Balance Request -->
            <div class="space-y-6 md:space-y-8">
                <!-- Stats -->
                <div class="bg-white p-6 md:p-8 rounded-3xl border border-gray-100 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-sm text-gray-400 font-bold uppercase">Username</div>
                        <div class="text-2xl font-black mb-4" id="accName">Loading...</div>
                        <div class="bg-black text-white p-6 rounded-2xl">
                            <div class="text-xs text-white/60 font-bold uppercase">Available Cash</div>
                            <div class="text-3xl font-mono font-bold mt-2" id="accCash">---</div>
                        </div>
                    </div>
                </div>

                <!-- Balance Request Form (Restored) -->
                <div class="bg-white p-6 md:p-8 rounded-3xl border border-gray-100 shadow-sm">
                    <h3 class="font-bold text-xl mb-4">Request Funds</h3>
                    <p class="text-gray-400 text-sm mb-4">Admins must approve your request.</p>
                    <div class="space-y-4">
                        <input type="number" id="reqAmount" placeholder="Amount (e.g. 5000)" class="w-full p-3 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:border-black transition">
                        <button onclick="requestBalance()" class="w-full bg-black text-white py-3 rounded-xl font-bold hover:scale-[1.02] transition">Submit Request</button>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-xs font-bold uppercase text-gray-400 mb-2">Recent Requests</h4>
                        <div id="reqList" class="space-y-2 text-sm max-h-40 overflow-y-auto"></div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Security (Password) -->
            <div class="bg-white p-6 md:p-8 rounded-3xl border border-gray-100 shadow-sm h-fit">
                <h3 class="font-bold text-xl mb-6">Security</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Current Password</label>
                        <input type="password" id="currPass" class="w-full mt-1 p-3 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:border-black transition">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">New Password</label>
                        <input type="password" id="newPass" class="w-full mt-1 p-3 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:border-black transition">
                    </div>
                    <button onclick="changePass()" id="btnPass" class="w-full bg-black text-white py-3 rounded-xl font-bold hover:scale-[1.02] transition">Update Password</button>
                </div>
            </div>
        </div>

        <!-- History Dropdown -->
        <details class="bg-white rounded-3xl border border-gray-100 shadow-sm group overflow-hidden">
            <summary class="flex justify-between items-center p-6 md:p-8 cursor-pointer list-none hover:bg-gray-50 transition">
                <h3 class="text-xl font-bold">Transaction History</h3>
                <span class="transform transition-transform duration-200 group-open:rotate-180">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </span>
            </summary>
            <div class="border-t border-gray-100">
                <table class="w-full text-left text-sm block md:table">
                    <thead class="bg-gray-50 font-bold text-gray-500 hidden md:table-header-group">
                        <tr><th class="p-4 md:p-6">Date</th><th class="p-4 md:p-6">Symbol</th><th class="p-4 md:p-6">Type</th><th class="p-4 md:p-6 text-right">Total</th></tr>
                    </thead>
                    <tbody id="historyList" class="block md:table-row-group divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </details>
    </div>
</main>

<script>
    window.onload = async function() {
        await initGlobal();
        document.getElementById('accName').innerText = window.userInfo.username || 'User';
        document.getElementById('accCash').innerText = '$' + parseFloat(window.userInfo.cash).toFixed(2);

        loadHistory();
        loadRequests();
    }

    async function loadRequests() {
        try {
            const res = await fetch('api.php?action=get_my_requests');
            const reqs = await res.json();
            const list = document.getElementById('reqList');
            list.innerHTML = '';
            
            if(!reqs || reqs.length === 0) {
                list.innerHTML = '<div class="text-gray-400 italic">No recent requests</div>';
                return;
            }
            
            reqs.forEach(r => {
                let color = 'text-yellow-600 bg-yellow-100';
                if(r.status === 'approved') color = 'text-green-600 bg-green-100';
                if(r.status === 'rejected') color = 'text-red-600 bg-red-100';
                
                list.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                        <span class="font-mono font-bold">$${parseFloat(r.amount).toLocaleString()}</span>
                        <span class="text-xs px-2 py-1 rounded font-bold uppercase ${color}">${r.status}</span>
                    </div>
                `;
            });
        } catch(e) { console.error("Error loading requests", e); }
    }

    async function requestBalance() {
        const amt = document.getElementById('reqAmount').value;
        if(!amt || amt <= 0) {
            await window.showAlert("Invalid Input", "Please enter a valid amount.", true);
            return;
        }
        
        const res = await fetch('api.php?action=request_balance', {method:'POST', body:JSON.stringify({amount:amt})});
        const d = await res.json();
        if(d.success) {
            document.getElementById('reqAmount').value = '';
            loadRequests();
            await window.showAlert("Request Submitted", "An admin will review it shortly.");
        } else {
            await window.showAlert("Error", d.error, true);
        }
    }

    async function changePass() {
        const curr = document.getElementById('currPass').value;
        const newP = document.getElementById('newPass').value;
        if(!curr || !newP) {
            await window.showAlert("Missing Fields", "Please fill in all fields.", true);
            return;
        }

        const btn = document.getElementById('btnPass');
        btn.innerText = "Updating...";
        btn.disabled = true;

        try {
            const res = await fetch('api.php?action=change_password', {
                method: 'POST', body: JSON.stringify({current_password: curr, new_password: newP})
            });
            const d = await res.json();
            
            if(d.success) {
                await window.showAlert("Success", "Password Changed Successfully");
                document.getElementById('currPass').value = '';
                document.getElementById('newPass').value = '';
            } else {
                await window.showAlert("Error", d.error, true);
            }
        } catch(e) {
            await window.showAlert("Connection Error", "Please try again later.", true);
        } finally {
            btn.innerText = "Update Password";
            btn.disabled = false;
        }
    }

    async function loadHistory() {
        try {
            const res = await fetch('api.php?action=get_history');
            const hist = await res.json();
            const tbody = document.getElementById('historyList');
            
            hist.forEach(h => {
                const tr = document.createElement('tr');
                tr.className = "block md:table-row p-6 md:p-0 hover:bg-gray-50 transition";
                tr.innerHTML = `
                    <td class="flex justify-between md:table-cell md:p-4 text-gray-400 pb-1 md:pb-6">
                        <span class="md:hidden font-bold uppercase text-xs text-gray-300">Date</span>
                        ${h.date.split(' ')[0]}
                    </td>
                    <td class="flex justify-between md:table-cell md:p-4 font-bold text-lg md:text-sm py-1 md:py-6">
                        <span class="md:hidden font-bold uppercase text-xs text-gray-300 font-normal self-center">Asset</span>
                        <div class="flex items-center gap-3">
                            <img src="${getLogo(h.symbol)}" class="w-6 h-6 rounded-full bg-gray-50 object-contain p-0.5" onerror="this.src='https://ui-avatars.com/api/?name=${h.symbol}&background=000&color=fff'">
                            <span>${h.symbol}</span>
                        </div>
                    </td>
                    <td class="flex justify-between md:table-cell md:p-4 uppercase font-bold ${h.type==='buy'?'text-green-600':'text-red-500'} py-1 md:py-6">
                        <span class="md:hidden font-bold uppercase text-xs text-gray-300 font-normal self-center">Action</span>
                        ${h.type}
                    </td>
                    <td class="flex justify-between md:table-cell md:p-4 text-right font-mono font-bold pt-1 md:pt-6">
                        <span class="md:hidden font-bold uppercase text-xs text-gray-300 font-normal self-center">Value</span>
                        $${parseFloat(h.total).toFixed(2)}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch(e) { console.error("History Error", e); }
    }
</script>