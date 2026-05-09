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
<body class="bg-[#fcfbf7] font-outfit min-h-screen" x-data="{ 
    sidebarOpen: true, 
    role: '<?php echo $role; ?>',
    activeTab: 'overview',
    branches: [],
    showBranchModal: false,
    showManagerModal: false,
    newBranch: { name: '', city: '', street: '', brgy: '', province: '', radius: 5 },
    newManager: { fname: '', lname: '', email: '', mobile: '', branch_id: '', password: 'password' },
    newMenu: { name: '', desc: '', price: 0, category: 'Chicken', size: 'Standard' },
    newStaff: { fname: '', lname: '', email: '', mobile: '', role: 'Kitchen Staff', password: 'password' },
    newRider: { fname: '', lname: '', email: '', mobile: '', password: 'password' },
    menuItems: [],
    showBranchModal: false,
    showManagerModal: false,
    showMenuModal: false,
    showStaffModal: false,
    showRiderModal: false,
    message: null,

    init() {
        if(this.role === 'System Admin') {
            this.fetchBranches();
            this.fetchMenu();
        }
        if(this.role === 'Branch Manager') {
            this.fetchBranchMenu();
        }
    },

    async fetchBranches() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branches' })
        });
        const data = await res.json();
        if(data.success) this.branches = data.data;
    },

    async fetchMenu() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_menu' })
        });
        const data = await res.json();
        if(data.success) this.menuItems = data.data;
    },

    async fetchBranchMenu() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_menu_availability' })
        });
        const data = await res.json();
        if(data.success) this.menuItems = data.data;
    },

    async toggleAvailability(menuId, currentStatus) {
        const newStatus = currentStatus === 'Y' ? 'N' : 'Y';
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'toggle_menu', menu_id: menuId, status: newStatus })
        });
        const data = await res.json();
        if(data.success) this.fetchBranchMenu();
    },

    async submitBranch() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_branch', ...this.newBranch })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchBranches();
            this.showBranchModal = false;
        }
    },

    async submitMenu() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_menu', ...this.newMenu })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchMenu();
            this.showMenuModal = false;
        }
    },

    async submitManager() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_manager', ...this.newManager })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.showManagerModal = false;
    },

    async submitStaff() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_staff', ...this.newStaff })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.showStaffModal = false;
    },

    async submitRider() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_rider', ...this.newRider })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.showRiderModal = false;
    }
}">

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
            <a href="#" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/20 transition-colors">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span x-show="sidebarOpen">Overview</span>
            </a>

            <?php if ($role === 'System Admin'): ?>
                <a href="#" @click="activeTab = 'branches'" :class="activeTab === 'branches' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="store" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Manage Branches</span>
                </a>
                <a href="#" @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="utensils-cross-lines" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Global Menu</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Branch Manager'): ?>
                <a href="#" @click="activeTab = 'riders'" :class="activeTab === 'riders' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="bike" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Manage Riders</span>
                </a>
                <a href="#" @click="activeTab = 'staff'" :class="activeTab === 'staff' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Kitchen Staff</span>
                </a>
                <a href="#" @click="activeTab = 'availability'" :class="activeTab === 'availability' ? 'bg-white/10' : ''" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Menu Availability</span>
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
        <div x-show="activeTab === 'overview'" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
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
                    <button @click="activeTab = 'branches'" class="bg-[#ffec00] text-black font-black px-6 py-3 rounded-2xl shadow-lg shadow-yellow-500/10 hover:scale-105 transition-transform flex items-center gap-2">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        <span>Start First Action</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Manage Branches Tab -->
        <?php if ($role === 'System Admin'): ?>
        <div x-show="activeTab === 'branches'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Branch Management</h2>
                <div class="flex gap-3">
                    <button @click="showBranchModal = true" class="bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] transition-colors">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Add Branch</span>
                    </button>
                    <button @click="showManagerModal = true" class="bg-[#ffec00] text-black px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#e6d400] transition-colors">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Create Manager</span>
                    </button>
                </div>
            </div>

            <!-- Branches Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="branch in branches" :key="branch.Brnch_ID">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 hover:border-[#006738] transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center">
                                <i data-lucide="store" class="w-6 h-6"></i>
                            </div>
                            <span :class="branch.Brnch_Status === 'Y' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full">
                                <span x-text="branch.Brnch_Status === 'Y' ? 'Active' : 'Closed'"></span>
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg mb-1" x-text="branch.Brnch_Name"></h3>
                        <p class="text-slate-500 text-sm mb-4" x-text="`${branch.Brnch_Street}, ${branch.Brnch_Brgy}, ${branch.Brnch_City}`"></p>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span x-text="`${branch.Brnch_Province} (${branch.Brnch_Radius}km radius)`"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="activeTab === 'menu'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Global Menu</h2>
                <button @click="showMenuModal = true" class="bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] transition-colors">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Add Item</span>
                </button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Category</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Price</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in menuItems" :key="item.Menu_ID">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="item.Menu_Name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="item.Menu_Size"></div>
                                    <div class="text-xs text-slate-400" x-text="item.Menu_Description"></div>
                                </td>
                                <td class="p-4 text-sm text-slate-600" x-text="item.Menu_Category"></td>
                                <td class="p-4 text-sm font-black text-[#006738]" x-text="'₱' + item.Menu_Price"></td>
                                <td class="p-4">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded-full bg-green-100 text-green-700">Active</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Admin Modals -->
        <div x-show="showMenuModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showMenuModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Add Global Menu Item</h3>
                    <button @click="showMenuModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Menu Name</label>
                        <input type="text" x-model="newMenu.name" placeholder="PM1 - Paa" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Description</label>
                        <textarea x-model="newMenu.desc" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none h-24"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Price</label>
                            <input type="number" x-model="newMenu.price" step="0.01" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Category</label>
                            <select x-model="newMenu.category" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                                <option>Chicken</option>
                                <option>Pork</option>
                                <option>Dessert</option>
                                <option>Drinks</option>
                                <option>Sides</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Size</label>
                        <select x-model="newMenu.size" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                            <option>Standard</option>
                            <option>1-pc</option>
                            <option>2-pc</option>
                            <option>Family Size</option>
                            <option>Solong</option>
                            <option>Sizling</option>
                        </select>
                    </div>
                    <button @click="submitMenu()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Save Product</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Branch Manager Tabs -->
        <?php if ($role === 'Branch Manager'): ?>
        <div x-show="activeTab === 'riders'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Rider Fleet</h2>
                <button @click="showRiderModal = true" class="bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] transition-colors">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Register Rider</span>
                </button>
            </div>
            <!-- Rider list could go here -->
        </div>

        <div x-show="activeTab === 'staff'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Kitchen Personnel</h2>
                <button @click="showStaffModal = true" class="bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] transition-colors">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Add Staff</span>
                </button>
            </div>
            <!-- Staff list could go here -->
        </div>

        <div x-show="activeTab === 'availability'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Menu Availability</h2>
                <p class="text-slate-500 text-sm">Toggle items based on current stock.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <template x-for="item in menuItems" :key="item.Menu_ID">
                    <div :class="item.Is_Available === 'Y' ? 'border-green-100 bg-white' : 'border-red-100 bg-red-50/10 opacity-75'" class="p-6 rounded-3xl border-2 transition-all">
                        <div class="flex justify-between items-start mb-4">
                            <span x-text="item.Menu_Category" class="text-[10px] font-black uppercase text-slate-400 bg-slate-100 px-2 py-1 rounded-lg"></span>
                            <button @click="toggleAvailability(item.Menu_ID, item.Is_Available)" 
                                    :class="item.Is_Available === 'Y' ? 'bg-[#006738]' : 'bg-red-500'" 
                                    class="w-12 h-6 rounded-full relative transition-colors">
                                <div :class="item.Is_Available === 'Y' ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full transition-transform"></div>
                            </button>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1" x-text="item.Menu_Name"></h3>
                        <p class="text-lg font-black text-[#006738]" x-text="'₱' + item.Menu_Price"></p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Branch Manager Modals -->
        <div x-show="showStaffModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showStaffModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Register Kitchen Staff</h3>
                    <button @click="showStaffModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" x-model="newStaff.fname" placeholder="First Name" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        <input type="text" x-model="newStaff.lname" placeholder="Last Name" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <input type="email" x-model="newStaff.email" placeholder="Email Address" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    <input type="text" x-model="newStaff.mobile" placeholder="Mobile Number" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    <button @click="submitStaff()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl">Create Staff Account</button>
                </div>
            </div>
        </div>

        <div x-show="showRiderModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showRiderModal = false">
                <div class="p-6 bg-[#ffec00] text-black flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Register Rider</h3>
                    <button @click="showRiderModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" x-model="newRider.fname" placeholder="First Name" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        <input type="text" x-model="newRider.lname" placeholder="Last Name" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <input type="email" x-model="newRider.email" placeholder="Rider Email" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    <input type="text" x-model="newRider.mobile" placeholder="Rider Mobile" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    <button @click="submitRider()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl">Create Rider Account</button>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <!-- Messages Toast -->
        <div x-show="message" x-transition x-cloak class="fixed bottom-8 right-8 z-[200]">
            <div :class="message?.success ? 'bg-green-600' : 'bg-red-600'" class="text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
                <i :data-lucide="message?.success ? 'check-circle' : 'alert-circle'" class="w-6 h-6"></i>
                <span x-text="message?.text" class="font-bold"></span>
                <button @click="message = null" class="ml-4 opacity-70 hover:opacity-100"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
