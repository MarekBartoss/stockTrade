<?php require 'header.php'; ?>
<!-- Manual Nav injection since it might redirect normal users in 'nav.php' later if we wanted -->
<nav class="bg-white border-b border-gray-200 px-6 h-20 flex items-center justify-between flex-shrink-0 z-20">
    <a href="market.php" class="text-3xl font-black tracking-tighter">TRADE.</a>
    <div class="font-bold uppercase tracking-widest text-xs bg-black text-white px-3 py-1 rounded">Admin Mode</div>
    <a href="market.php" class="text-sm font-bold underline">Exit</a>
</nav>

<main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
    <div class="max-w-4xl mx-auto fade-in">
        <h2 class="text-3xl md:text-4xl font-bold mb-6 md:mb-8">Balance Requests</h2>
        
        <!-- Responsive Container -->
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
            <table class="w-full text-left block md:table">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-400 hidden md:table-header-group">
                    <tr>
                        <th class="p-6">User</th>
                        <th class="p-6 text-right">Amount</th>
                        <th class="p-6 text-right">Date</th>
                        <th class="p-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="reqBody" class="block md:table-row-group divide-y divide-gray-100"></tbody>
            </table>
        </div>
        <div id="emptyMsg" class="hidden text-center py-20 text-gray-400">No pending requests.</div>
    </div>
</main>

<script>
    window.onload = async function() {
        const res = await fetch('api.php?action=get_admin_requests');
        const reqs = await res.json();
        
        if(reqs.error) {
            await window.showAlert("Access Denied", "You are not an admin.", true);
            location.href = 'market.php';
            return;
        }

        const tbody = document.getElementById('reqBody');
        if(reqs.length === 0) document.getElementById('emptyMsg').classList.remove('hidden');

        reqs.forEach(r => {
            const tr = document.createElement('tr');
            // Responsive Classes: Block on Mobile, Table on Desktop
            tr.className = "block md:table-row p-6 md:p-0 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition";
            
            tr.innerHTML = `
                <td class="flex justify-between items-center md:table-cell md:p-6 font-bold text-lg md:text-base pb-2 md:pb-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">User</span>
                    ${r.username}
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 font-mono text-lg py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Amount</span>
                    $${parseFloat(r.amount).toLocaleString()}
                </td>
                <td class="flex justify-between items-center md:table-cell md:text-right md:p-6 text-gray-400 text-sm py-2 md:py-6">
                    <span class="md:hidden text-xs font-bold text-gray-400 uppercase">Date</span>
                    ${r.created_at}
                </td>
                <td class="flex justify-end gap-3 md:table-cell md:text-right md:p-6 pt-4 md:pt-6">
                    <button onclick="handle(${r.id}, 'approved')" class="flex-1 md:flex-none bg-green-100 text-green-700 px-4 py-3 md:py-2 rounded-xl md:rounded-lg font-bold hover:bg-green-200">Approve</button>
                    <button onclick="handle(${r.id}, 'rejected')" class="flex-1 md:flex-none bg-red-100 text-red-700 px-4 py-3 md:py-2 rounded-xl md:rounded-lg font-bold hover:bg-red-200">Reject</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function handle(id, status) {
        const sure = await window.showConfirm("Confirm Action", `Are you sure you want to mark this request as ${status}?`);
        if(!sure) return;
        
        const res = await fetch('api.php?action=handle_request', {
            method:'POST', body:JSON.stringify({request_id:id, status:status})
        });
        const d = await res.json();
        
        if(d.success) {
            await window.showAlert("Done", "Request updated.");
            location.reload();
        } else {
            await window.showAlert("Error", d.error, true);
        }
    }
</script>
</body>
</html>