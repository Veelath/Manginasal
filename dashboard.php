<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'] ?? 'Customer';
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mang Inasal - Dashboard (<?php echo $role; ?>)</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#fcfbf7] font-outfit min-h-screen" x-data="{ sidebarOpen: true }">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-[#006738] text-white transition-all duration-300 z-50 overflow-hidden hidden md:flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <div class="bg-[#ffec00] p-2 rounded-lg">
                <i data-lucide="chef-hat" class="w-6 h-6 text-black"></i>
            </div>
            <span x-show="sidebarOpen" class="font-poppins font-black text-xl tracking-tight uppercase" x-transition>Inasal</span>
        </div>

        <nav class="flex-1 mt-6 px-4 space-y-2">
            <!-- Common Home -->
            <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span x-show="sidebarOpen">Overview</span>
            </a>

            <?php if ($role === 'System Admin'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="store" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Manage Branches</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="utensils-cross-lines" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Global Menu</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Branch Managers</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Branch Manager'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="bike" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Manage Riders</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Kitchen Staff</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Inventory Avail.</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Kitchen Staff'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Active Orders</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="list-check" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Order History</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Driver'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="navigation" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Pending Deliveries</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">My Route</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Customer'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="utensils" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Order Now</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="history" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">My Orders</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-white/10">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-red-500/20 text-red-100 transition-colors">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span x-show="sidebarOpen">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'" class="transition-all duration-300 p-4 md:p-8">
        
        <!-- Top Bar -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-black text-slate-800 font-poppins capitalize"><?php echo str_replace('_', ' ', $role); ?> Dashboard</h1>
                <p class="text-slate-500">Welcome back, we're ready to grill!</p>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 bg-white rounded-xl shadow-sm border text-slate-400 hover:text-[#006738]">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </button>
                <div class="w-10 h-10 bg-[#006738] rounded-xl flex items-center justify-center text-white font-bold">
                    <?php echo substr($role, 0, 1); ?>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php if ($role === 'System Admin'): ?>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center mb-4">
                        <i data-lucide="store" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider">Total Branches</h3>
                    <p class="text-3xl font-black text-slate-800">12</p>
                </div>
                <!-- Add more stats -->
            <?php elseif ($role === 'Branch Manager'): ?>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider">Staff Active</h3>
                    <p class="text-3xl font-black text-slate-800">8</p>
                </div>
            <?php endif; ?>

            <!-- Example common stat -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-4">
                    <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                </div>
                <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider">Today's Orders</h3>
                <p class="text-3xl font-black text-slate-800">42</p>
            </div>
        </section>

        <!-- Dynamic Role Content -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                <h2 class="font-bold text-slate-800">Operation Center</h2>
                <button class="text-xs font-black uppercase text-[#006738] tracking-widest hover:underline">View All Task</button>
            </div>
            
            <div class="p-8 min-h-[300px] flex flex-col items-center justify-center text-center">
                <div class="mb-4 text-slate-200">
                    <i data-lucide="construction" class="w-20 h-20"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 font-poppins">Feature in Development</h3>
                <p class="text-slate-500 max-w-md mx-auto mt-2">
                    As a <strong><?php echo $role; ?></strong>, you will soon be able to manage 
                    <?php 
                        if($role === 'System Admin') echo "branches and global menu settings.";
                        elseif($role === 'Branch Manager') echo "driver recruitment and kitchen staff schedules.";
                        elseif($role === 'Kitchen Staff') echo "incoming food orders in real-time.";
                        elseif($role === 'Driver') echo "delivery routes and earnings.";
                        else echo "your favorite inasal meals and track your orders.";
                    ?>
                </p>
                <div class="mt-8 flex gap-4">
                    <button class="bg-[#ffec00] text-black font-black px-6 py-3 rounded-2xl shadow-lg shadow-yellow-500/10 hover:scale-105 transition-transform flex items-center gap-2">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        <span>Start First Action</span>
                    </button>
                </div>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
