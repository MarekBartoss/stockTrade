<!-- NAVIGATION -->
<nav class="bg-white border-b border-gray-200 px-6 h-20 flex items-center justify-between flex-shrink-0 z-20 relative">
    
    <!-- Logo -->
    <a href="market.php" class="text-3xl font-black tracking-tighter z-30 relative">TRADE.</a>
    
    <!-- DESKTOP MENU -->
    <div class="hidden md:flex gap-8 absolute left-1/2 -translate-x-1/2">
        <a href="market.php" class="py-2 font-bold <?php echo basename($_SERVER['PHP_SELF'])=='market.php'?'border-b-2 border-black':'text-gray-400'; ?>">Market</a>
        <a href="portfolio.php" class="py-2 font-bold <?php echo basename($_SERVER['PHP_SELF'])=='portfolio.php'?'border-b-2 border-black':'text-gray-400'; ?>">Portfolio</a>
        <a href="account.php" class="py-2 font-bold <?php echo basename($_SERVER['PHP_SELF'])=='account.php'?'border-b-2 border-black':'text-gray-400'; ?>">Account</a>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin.php" class="py-2 font-bold text-red-500 hover:text-red-700">Admin</a>
        <?php endif; ?>
    </div>

    <!-- DESKTOP RIGHT -->
    <div class="hidden md:flex items-center gap-4">
        <div class="text-right">
            <div class="text-xs text-gray-400 font-bold uppercase">Cash Balance</div>
            <div class="font-mono font-bold text-lg" id="navCash">---</div>
        </div>
        <button onclick="confirmLogout()" class="p-2 hover:bg-gray-100 rounded-full text-red-500" title="Logout">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </button>
    </div>

    <!-- MOBILE MENU BTN -->
    <button onclick="toggleMobileMenu()" class="md:hidden p-2 z-30 relative focus:outline-none">
        <svg id="menuIcon" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        <svg id="closeIcon" class="w-8 h-8 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>

    <!-- MOBILE DROPDOWN -->
    <div id="mobileMenu" class="absolute top-full left-0 w-full bg-white border-b border-gray-200 shadow-xl flex flex-col p-6 gap-6 hidden md:hidden opacity-0 transition-all duration-300 transform -translate-y-4 z-20">
        
        <div class="flex flex-col gap-4 text-xl">
            <a href="market.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF'])=='market.php'?'text-black':'text-gray-400'; ?>">Market</a>
            <a href="portfolio.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF'])=='portfolio.php'?'text-black':'text-gray-400'; ?>">Portfolio</a>
            <a href="account.php" class="font-bold <?php echo basename($_SERVER['PHP_SELF'])=='account.php'?'text-black':'text-gray-400'; ?>">Account</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="font-bold text-red-500">Admin Panel</a>
            <?php endif; ?>
        </div>

        <hr class="border-gray-100">

        <div>
            <div class="text-xs text-gray-400 font-bold uppercase mb-1">Available Cash</div>
            <div class="font-mono font-bold text-3xl" id="navCashMobile">---</div>
        </div>

        <hr class="border-gray-100">

        <button onclick="confirmLogout()" class="w-full py-3 bg-gray-100 text-red-500 font-bold rounded-xl flex items-center justify-center gap-2">
            Log Out
        </button>
    </div>

</nav>

<script>
    async function confirmLogout() {
        const sure = await window.showConfirm("Log Out", "Are you sure you want to sign out?");
        if (!sure) return;

        await fetch('api.php?action=logout');
        await window.showAlert("Logged Out", "See you next time!");
        location.href = 'auth.php';
    }

    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');
        const closeIcon = document.getElementById('closeIcon');

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            setTimeout(() => {
                menu.classList.remove('opacity-0', '-translate-y-4');
            }, 10);
            menuIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
        } else {
            menu.classList.add('opacity-0', '-translate-y-4');
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 300);
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        }
    }
</script>