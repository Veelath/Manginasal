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
    managers: [],
    allUsers: [],
    workforce: [],
    stats: { branches: 0, managers: 0, staff: 0, riders: 0 },
    showBranchModal: false,
    showManagerModal: false,
    newBranch: { name: '', city: '', street: '', brgy: '', province: '', radius: 5 },
    newManager: { fname: '', lname: '', email: '', mobile: '', branch_id: '', password: '' },
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
            this.fetchStats();
            this.fetchManagers();
            this.fetchAllUsers();
        }
        if(this.role === 'Branch Manager') {
            this.fetchBranchMenu();
            this.fetchWorkforce();
            this.fetchBranchStats();
        }
    },

    async fetchBranchStats() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branch_stats' })
        });
        const data = await res.json();
        if(data.success) {
            this.stats.staff = data.stats.staff;
            this.stats.riders = data.stats.riders;
        }
    },

    async fetchWorkforce() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branch_workforce' })
        });
        const data = await res.json();
        if(data.success) {
            this.workforce = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchManagers() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_managers' })
        });
        const data = await res.json();
        if(data.success) {
            this.managers = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchAllUsers() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_all_users' })
        });
        const data = await res.json();
        if(data.success) {
            this.allUsers = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchStats() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_stats' })
        });
        const data = await res.json();
        if(data.success) this.stats = data.stats;
    },

    async fetchBranches() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branches' })
        });
        const data = await res.json();
        if(data.success) {
            this.branches = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchMenu() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_menu' })
        });
        const data = await res.json();
        if(data.success) {
            this.menuItems = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchBranchMenu() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_menu_availability' })
        });
        const data = await res.json();
        if(data.success) {
            this.menuItems = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
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

    async updateManagerStatus(id, status) {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_manager_status', id, status })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchManagers();
            this.fetchBranches();
        }
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
            this.fetchStats();
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
        if(data.success) {
            this.fetchStats();
            this.fetchManagers();
            this.fetchBranches(); 
            this.showManagerModal = false;
        }
    },

    async deletePerson(id, source) {
        if(!confirm('Are you sure you want to remove this person?')) return;
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_workforce', id, source })
        });
        const data = await res.json();
        if(data.success) {
            this.fetchWorkforce();
            this.fetchBranchStats();
        }
    },

    async submitStaff() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_staff', ...this.newStaff })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchWorkforce();
            this.fetchBranchStats();
            this.showStaffModal = false;
        }
    },

    async submitRider() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_rider', ...this.newRider })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchWorkforce();
            this.fetchBranchStats();
            this.showRiderModal = false;
        }
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
            <a href="#" @click="activeTab = 'overview'" 
               :class="activeTab === 'overview' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
               class="flex items-center gap-3 p-3 rounded-xl transition-all">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span x-show="sidebarOpen" class="font-bold text-sm">Dashboard</span>
            </a>

            <?php if ($role === 'System Admin'): ?>
                <a href="#" @click="activeTab = 'branches'" 
                   :class="activeTab === 'branches' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="store" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Branches</span>
                </a>
                <a href="#" @click="activeTab = 'menu'" 
                   :class="activeTab === 'menu' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="utensils-cross-lines" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Global Menu</span>
                </a>
                <a href="#" @click="activeTab = 'manage_managers'" 
                   :class="activeTab === 'manage_managers' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Managers</span>
                </a>
                <a href="#" @click="activeTab = 'user_directory'" 
                   :class="activeTab === 'user_directory' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="book-user" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">User Directory</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Branch Manager'): ?>
                <a href="#" @click="activeTab = 'workforce'" 
                   :class="activeTab === 'workforce' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="users-2" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Workforce</span>
                </a>
                <a href="#" @click="activeTab = 'availability'" 
                   :class="activeTab === 'availability' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Availability</span>
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
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="store" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Total Branches</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.branches"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Total Managers</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.managers"></p>
                </div>
            <?php elseif ($role === 'Branch Manager'): ?>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Kitchen & Staff</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.staff"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-yellow-50 text-yellow-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="bike" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Rider Fleet</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.riders"></p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Dynamic Role Content -->
        <div x-show="activeTab === 'overview'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden p-8 md:p-12 relative group">
                <div class="absolute top-0 right-0 p-12 opacity-5 pointer-events-none group-hover:opacity-10 transition-opacity">
                    <i data-lucide="flame" class="w-64 h-64 text-[#006738]"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-8">
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-[#006738] text-[10px] font-black uppercase tracking-widest rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            Branch Live
                        </span>
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-full">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            Updated Just Now
                        </span>
                    </div>
                    
                    <h2 class="text-4xl font-black text-slate-800 font-poppins mb-6">Branch Command Center</h2>
                    <p class="text-slate-400 max-w-xl text-lg leading-relaxed mb-10">
                        <?php if($role === 'Branch Manager'): ?>
                            Manage your restaurant operations with precision. Update menu availability, monitor your fleet, and grow your local team to keep the grill hot!
                        <?php else: ?>
                            Master overview of all Inasal branches. Monitor performance and maintain global menu consistency.
                        <?php endif; ?>
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if($role === 'Branch Manager'): ?>
                            <button @click="activeTab = 'workforce'" class="flex items-center gap-5 p-6 bg-[#fcfbf7] rounded-[2rem] hover:bg-[#006738] hover:text-white transition-all group border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-green-900/10">
                                <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-[#006738] group-hover:bg-white/10 group-hover:text-white transition-colors shadow-sm">
                                    <i data-lucide="user-plus" class="w-7 h-7"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="font-black text-sm uppercase tracking-tight">Expand Team</h4>
                                    <p class="text-xs opacity-60">Add Personnel</p>
                                </div>
                            </button>
                            <button @click="activeTab = 'availability'" class="flex items-center gap-5 p-6 bg-[#fcfbf7] rounded-[2rem] hover:bg-[#ffec00] hover:text-black transition-all group border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-yellow-500/20">
                                <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-yellow-600 group-hover:bg-black/10 group-hover:text-black transition-colors shadow-sm">
                                    <i data-lucide="shopping-bag" class="w-7 h-7"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="font-black text-sm uppercase tracking-tight">Daily Menu</h4>
                                    <p class="text-xs opacity-60">Set Availability</p>
                                </div>
                            </button>
                        <?php endif; ?>
                    </div>
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
                        <p class="text-slate-500 text-sm mb-2" x-text="`${branch.Brnch_Street}, ${branch.Brnch_Brgy}, ${branch.Brnch_City}`"></p>
                        
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                <i data-lucide="user" class="w-3 h-3"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-600" x-text="branch.Staff_FName ? `${branch.Staff_FName} ${branch.Staff_LName}` : 'No Manager Assigned'"></span>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span x-text="`${branch.Brnch_Province} (${branch.Brnch_Radius}km radius)`"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Managers Section -->
            <div class="mt-12">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-black text-slate-800 font-poppins">Branch Managers</h2>
                </div>
                <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Email</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Assigned Branch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="manager in managers" :key="manager.Staff_ID">
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 font-bold text-slate-800" x-text="`${manager.Staff_FName} ${manager.Staff_LName}`"></td>
                                    <td class="p-4 text-sm text-slate-600" x-text="manager.Staff_Email"></td>
                                    <td class="p-4">
                                        <span :class="manager.Brnch_Name ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="manager.Brnch_Name || 'Unassigned'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'manage_managers'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-slate-800 font-poppins">Manager Workforce</h2>
                <button @click="showManagerModal = true" class="bg-[#ffec00] text-black px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#e6d400] transition-colors">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Create Manager</span>
                </button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-visible">
                <div class="p-1 pb-[350px]">
                    <div class="overflow-x-auto overflow-y-visible">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Email</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Branch</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                        <template x-for="manager in managers" :key="manager.Staff_ID">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="`${manager.Staff_FName} ${manager.Staff_LName}`"></div>
                                    <div class="text-[10px] text-slate-400" x-text="manager.Staff_MobileNum"></div>
                                </td>
                                <td class="p-4 text-sm text-slate-600" x-text="manager.Staff_Email"></td>
                                <td class="p-4">
                                    <span :class="manager.Brnch_Name ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="manager.Brnch_Name || 'Unassigned'"></span>
                                </td>
                                <td class="p-4">
                                    <span :class="{
                                        'bg-green-100 text-green-700': manager.Staff_Status === 'Active' || manager.Staff_Status === 'Y',
                                        'bg-red-100 text-red-700': manager.Staff_Status === 'Resigned' || manager.Staff_Status === 'N',
                                        'bg-orange-100 text-orange-700': manager.Staff_Status === 'Suspended'
                                    }" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" 
                                       x-text="(manager.Staff_Status === 'Y' || manager.Staff_Status === 'Active') ? 'Active' : (manager.Staff_Status === 'N' || manager.Staff_Status === 'Resigned' ? 'Resigned' : manager.Staff_Status)">
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-2 relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-2 hover:bg-slate-100 rounded-lg transition-colors border border-transparent hover:border-slate-200">
                                            <i data-lucide="edit-3" class="w-4 h-4 text-slate-400"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" 
                                             class="absolute right-0 top-full mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl z-[999] py-2 w-48 animate-in fade-in slide-in-from-top-2 duration-200 origin-top-right"
                                             x-transition x-cloak>
                                            <div class="px-4 py-2 mb-1 text-[10px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">Update Account Status</div>
                                            <button @click="updateManagerStatus(manager.Staff_ID, 'Active'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-green-50 text-green-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-green-500"></div> Set as Active
                                            </button>
                                            <button @click="updateManagerStatus(manager.Staff_ID, 'Suspended'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-orange-50 text-orange-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-orange-500"></div> Suspend Account
                                            </button>
                                            <button @click="updateManagerStatus(manager.Staff_ID, 'Resigned'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-red-50 text-red-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-red-500"></div> Mark as Resigned
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Directory Tab -->
        <div x-show="activeTab === 'user_directory'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">User Directory</h2>
                    <p class="text-slate-500 text-sm italic">Complete list of registered personnel and customers.</p>
                </div>
                <button @click="fetchAllUsers()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Full Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Login / Email</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Account Type</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Database Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="user in allUsers" :key="user.source + user.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 font-bold text-slate-800" x-text="`${user.fname} ${user.lname}`"></td>
                                <td class="p-4 text-sm font-medium text-[#006738]" x-text="user.email"></td>
                                <td class="p-4">
                                    <span :class="{
                                        'bg-purple-100 text-purple-700': user.role === 'System Admin',
                                        'bg-blue-100 text-blue-700': user.role === 'Branch Manager',
                                        'bg-green-100 text-green-700': user.role === 'Customer',
                                        'bg-orange-100 text-orange-700': user.role === 'Driver' || user.role === 'Rider',
                                        'bg-slate-100 text-slate-700': user.role === 'Kitchen Staff'
                                    }" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="user.role"></span>
                                </td>
                                <td class="p-4">
                                    <div class="text-[10px] font-bold text-slate-300 uppercase tracking-widest" x-text="user.source"></div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTab === 'menu'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Global Menu</h2>
                    <p class="text-slate-500 text-sm">Master list of all Mang Inasal products.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="fetchMenu()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                    <button @click="showMenuModal = true" class="bg-[#006738] text-white px-5 py-3 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] shadow-lg shadow-green-900/10 transition-all active:scale-95">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Add Global Item</span>
                    </button>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Product Info</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Category</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Price</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="menuItems.length === 0">
                            <tr>
                                <td colspan="4" class="p-12 text-center text-slate-400 italic">No menu items found. Click "Add Global Item" to start.</td>
                            </tr>
                        </template>
                        <template x-for="item in menuItems" :key="item.Menu_ID">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="item.Menu_Name"></div>
                                    <div class="text-[10px] text-[#006738] font-black uppercase tracking-tighter" x-text="item.Menu_Size"></div>
                                    <div class="text-[11px] text-slate-400 mt-1 italic line-clamp-1" x-text="item.Menu_Description"></div>
                                </td>
                                <td class="p-4">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-slate-100 text-slate-600" x-text="item.Menu_Category"></span>
                                </td>
                                <td class="p-4 text-sm font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(2)"></td>
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
        <div x-show="showBranchModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200" @click.away="showBranchModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Add New Branch</h3>
                    <button @click="showBranchModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Branch Name</label>
                        <input type="text" x-model="newBranch.name" placeholder="Main Branch" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">City</label>
                            <input type="text" x-model="newBranch.city" placeholder="Manila" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Province</label>
                            <input type="text" x-model="newBranch.province" placeholder="Metro Manila" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Barangay</label>
                        <input type="text" x-model="newBranch.brgy" placeholder="Brgy 1" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Street</label>
                        <input type="text" x-model="newBranch.street" placeholder="P. Gomez St" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <button @click="submitBranch()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Create Branch</button>
                </div>
            </div>
        </div>

        <div x-show="showManagerModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200" @click.away="showManagerModal = false">
                <div class="p-6 bg-[#ffec00] text-black flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Create Manager Account</h3>
                    <button @click="showManagerModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">First Name</label>
                            <input type="text" x-model="newManager.fname" placeholder="Juan" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Last Name</label>
                            <input type="text" x-model="newManager.lname" placeholder="Dela Cruz" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                        <input type="email" x-model="newManager.email" placeholder="manager@inasal.com" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Mobile Number</label>
                            <input type="text" x-model="newManager.mobile" placeholder="09123456789" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Initial Password</label>
                            <input type="password" x-model="newManager.password" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Assign to Branch</label>
                        <select x-model="newManager.branch_id" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                            <option value="">Select Branch</option>
                            <template x-for="branch in branches" :key="branch.Brnch_ID">
                                <option :value="branch.Brnch_ID" x-text="branch.Brnch_Name"></option>
                            </template>
                        </select>
                    </div>
                    <button @click="submitManager()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Create Account</button>
                </div>
            </div>
        </div>

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
        <div x-show="activeTab === 'workforce'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Branch Workforce</h2>
                    <p class="text-slate-500 text-sm italic">Manage your delivery riders and restaurant staff.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="showStaffModal = true" class="bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] transition-colors">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Add Staff</span>
                    </button>
                    <button @click="showRiderModal = true" class="bg-[#ffec00] text-black px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-yellow-400 transition-colors">
                        <i data-lucide="bike" class="w-4 h-4"></i>
                        <span>Add Rider</span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Login / Email</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Role</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="person in workforce" :key="person.source + person.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4 font-bold text-slate-800" x-text="`${person.fname} ${person.lname}`"></td>
                                <td class="p-4 text-sm text-slate-600" x-text="person.email"></td>
                                <td class="p-4">
                                    <span :class="{
                                        'bg-blue-100 text-blue-700': person.role === 'Kitchen Staff',
                                        'bg-purple-100 text-purple-700': person.role === 'Staff',
                                        'bg-orange-100 text-orange-700': person.role === 'Driver' || person.role === 'Rider'
                                    }" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="person.role"></span>
                                </td>
                                <td class="p-4 text-right">
                                    <button @click="deletePerson(person.id, person.source)" class="text-red-400 hover:text-red-600 p-2 transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
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
                        <p class="text-lg font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(2)"></p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Branch Manager Modals -->
        <div x-show="showStaffModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200" @click.away="showStaffModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Register Personnel</h3>
                    <button @click="showStaffModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">First Name</label>
                            <input type="text" x-model="newStaff.fname" placeholder="Juan" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Last Name</label>
                            <input type="text" x-model="newStaff.lname" placeholder="Dela Cruz" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email / Login</label>
                        <input type="email" x-model="newStaff.email" placeholder="staff@inasal.com" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Role</label>
                            <select x-model="newStaff.role" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm">
                                <option>Kitchen Staff</option>
                                <option value="Staff">Counter Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Password</label>
                            <input type="password" x-model="newStaff.password" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <button @click="submitStaff()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Create Account</button>
                </div>
            </div>
        </div>

        <div x-show="showRiderModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4" x-cloak>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200" @click.away="showRiderModal = false">
                <div class="p-6 bg-[#ffec00] text-black flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize">Register Delivery Rider</h3>
                    <button @click="showRiderModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">First Name</label>
                            <input type="text" x-model="newRider.fname" placeholder="Pedro" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Last Name</label>
                            <input type="text" x-model="newRider.lname" placeholder="Penduko" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Email / Login</label>
                        <input type="email" x-model="newRider.email" placeholder="rider@inasal.com" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Password</label>
                        <input type="password" x-model="newRider.password" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <button @click="submitRider()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Register Rider</button>
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
