<?php require 'header.php'; ?>
<!-- Manual Nav injection since it might redirect normal users in 'nav.php' later if we wanted -->
<nav class="bg-white border-b border-gray-200 px-6 h-20 flex items-center justify-between flex-shrink-0 z-20">
    <a href="market.php" class="text-3xl font-black tracking-tighter">TRADE.</a>
    <div class="font-bold uppercase tracking-widest text-xs bg-black text-white px-3 py-1 rounded">Admin Mode</div>
    <a href="market.php" class="text-sm font-bold underline">Exit</a>
</nav>

<main class="flex-1 overflow-y-auto p-8 relative">
    <div class="max-w-4xl mx-auto fade-in">
        <h2 class="text-4xl font-bold mb-8">Balance Requests</h2>
        
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-400">
                    <tr>
                        <th class="p-6">User</th>
                        <th class="p-6 text-right">Amount</th>
                        <th class="p-6 text-right">Date</th>
                        <th class="p-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="reqBody" class="divide-y divide-gray-100"></tbody>
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
            tr.innerHTML = `
                <td class="p-6 font-bold">${r.username}</td>
                <td class="p-6 text-right font-mono text-lg">$${parseFloat(r.amount).toLocaleString()}</td>
                <td class="p-6 text-right text-gray-400 text-sm">${r.created_at}</td>
                <td class="p-6 text-right flex justify-end gap-2">
                    <button onclick="handle(${r.id}, 'approved')" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg font-bold hover:bg-green-200">Approve</button>
                    <button onclick="handle(${r.id}, 'rejected')" class="bg-red-100 text-red-700 px-4 py-2 rounded-lg font-bold hover:bg-red-200">Reject</button>
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