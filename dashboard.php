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
<body class="bg-[#fcfbf7] font-outfit min-h-screen overscroll-none" x-data="{ 
    sidebarOpen: true, 
    role: '<?php echo $role; ?>',
    activeTab: 'overview',
    branches: [],
    managers: [],
    staffMembers: [],
    riders: [],
    allUsers: [],
    workforce: [],
    orders: [],
    currentBranch: null,
    stats: { branches: 0, managers: 0, staff: 0, riders: 0 },
    reports: { dailySales: 0, totalOrders: 0, topItems: [] },
    newBranch: { name: '', city: '', street: '', brgy: '', province: '', radius: 5 },
    newManager: { fname: '', lname: '', email: '', mobile: '', branch_id: '', password: '' },
    newMenu: { name: '', desc: '', price: 0, category: 'Chicken', size: 'Standard' },
    newStaff: { fname: '', lname: '', email: '', mobile: '', role: 'Kitchen Staff', password: 'password' },
    newRider: { fname: '', lname: '', email: '', mobile: '', password: 'password' },
    menuItems: [],
    customerBranches: [],
    selectedBranch: null,
    customerMenu: [],
    customerOrders: [],
    riderDeliveries: [],
    profileData: { fname: '', lname: '', email: '', mobile: '' },
    addresses: [],
    showBranchPicker: true,
    orderItems: {}, // Track items by order ID
    cart: [],
    orderType: 'Delivery',
    paymentMethod: '',
    manualAddress: { province: 'Metro Manila', city: '', street: '', brgy: '', postal: '', landmark: '' },
    useCurrentAddress: true,
    showTrackingModal: false,
    selectedOrder: null,
    showBranchModal: false,
    currentCarousel: 0,
    carouselItems: [
        { title: 'Extra Creamy Halo-Halo', subtitle: 'HULING LASAP!', image: 'https://images.unsplash.com/photo-1582236314828-591a27e467cf?q=80&w=2000&auto=format&fit=crop', color: '#006738' },
        { title: 'PM1: Paa with Rice', subtitle: 'UNLI-RICE EXPERIENCE', image: 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?q=80&w=2000&auto=format&fit=crop', color: '#ffec00' },
        { title: 'Family Fiesta', subtitle: 'SHARE THE GRILL', image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=2000&auto=format&fit=crop', color: '#006738' }
    ],
    categories: [
        { name: 'Must Try!', icon: 'star', image: 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=400&auto=format&fit=crop', db: 'Chicken' },
        { name: 'Chicken Inasal', icon: 'flame', image: 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?q=80&w=400&auto=format&fit=crop', db: 'Chicken' },
        { name: 'Pork BBQ', icon: 'drumstick', image: 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=400&auto=format&fit=crop', db: 'Pork' },
        { name: 'Family Fiesta', icon: 'users', image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=400&auto=format&fit=crop', db: 'Family Fiesta' },
        { name: 'Buddy Fiesta', icon: 'user-2', image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=400&auto=format&fit=crop', db: 'Buddy Fiesta' },
        { name: 'Halo-Halo', icon: 'ice-cream', image: 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?q=80&w=400&auto=format&fit=crop', db: 'Dessert' },
        { name: 'Palabok', icon: 'soup', image: 'https://images.unsplash.com/photo-1512058560550-42749359a767?q=80&w=400&auto=format&fit=crop', db: 'Palabok' }
    ],
    selectedCategory: 'Chicken Inasal',
    searchQuery: '',
    showBranchModal: false,
    showManagerModal: false,
    showMenuModal: false,
    showStaffModal: false,
    showRiderModal: false,
    showProfileModal: false,
    showAddressModal: false,
    tempProfile: { fname: '', lname: '', mobile: '' },
    newAddress: { label: 'Home', province: 'Metro Manila', city: '', brgy: '', street: '', unit: '', building: '', landmark: '', postal: '' },
    message: null,

    closeAllModals() {
        this.showBranchModal = false;
        this.showManagerModal = false;
        this.showMenuModal = false;
        this.showStaffModal = false;
        this.showRiderModal = false;
        this.showProfileModal = false;
        this.showAddressModal = false;
    },

    init() {
        this.$watch('selectedCategory', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('currentCarousel', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('activeTab', () => this.$nextTick(() => lucide.createIcons()));
        
        if(this.role === 'System Admin') {
            this.fetchBranches();
            this.fetchMenu();
            this.fetchStats();
            this.fetchManagers();
            this.fetchStaff();
            this.fetchRiders();
            this.fetchAllUsers();
            this.fetchReports();
        }
        if(this.role === 'Branch Manager' || this.role === 'Kitchen Staff') {
            this.fetchBranchMenu();
            this.fetchWorkforce();
            this.fetchBranchStats();
            this.fetchBranchInfo();
            this.fetchOrders();
            if (this.role === 'Kitchen Staff') {
                this.activeTab = 'orders';
            }
        }
        if(this.role === 'Customer') {
            this.fetchCustomerBranches();
            this.fetchCustomerOrders();
            this.fetchProfile();
            if (!this.selectedBranch) {
                this.activeTab = 'order_now';
            }
            this.showBranchPicker = !this.selectedBranch;
        }
        if(this.role === 'Driver') {
            this.fetchRiderDeliveries();
        }
    },

    async fetchProfile() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_profile' })
        });
        const data = await res.json();
        if(data.success) {
            this.profileData = {
                fname: data.profile.Cust_FName,
                lname: data.profile.Cust_LName,
                email: data.profile.Cust_Email,
                mobile: data.profile.Cust_MobileNum
            };
            this.addresses = data.addresses;
        }
    },

    openEditProfile() {
        this.tempProfile = { ...this.profileData };
        this.showProfileModal = true;
    },

    async submitProfileUpdate() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_profile', ...this.tempProfile })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchProfile();
            this.showProfileModal = false;
        }
    },

    async submitAddress() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'add_address', ...this.newAddress })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchProfile();
            this.showAddressModal = false;
            this.newAddress = { label: 'Home', province: 'Metro Manila', city: '', brgy: '', street: '', unit: '', building: '', landmark: '', postal: '' };
        }
    },

    async deleteAddress(id) {
        if(!confirm('Delete this address?')) return;
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_address', id })
        });
        const data = await res.json();
        if(data.success) this.fetchProfile();
    },

    async fetchReports() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_reports' })
        });
        const data = await res.json();
        if(data.success) this.reports = data.data;
    },

    async fetchCustomerBranches() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branches' })
        });
        const data = await res.json();
        if(data.success) this.customerBranches = data.data;
    },

    async selectBranch(branchId) {
        const branch = this.customerBranches.find(b => b.Brnch_ID == branchId);
        this.selectedBranch = branch;
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branch_menu', branch_id: branchId })
        });
        const data = await res.json();
        if(data.success) {
            this.customerMenu = data.data;
            this.showBranchPicker = false;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    addToCart(item) {
        const existing = this.cart.find(i => i.Menu_ID === item.Menu_ID);
        if(existing) {
            existing.qty++;
        } else {
            this.cart.push({ ...item, qty: 1 });
        }
    },

    get cartTotal() {
        return this.cart.reduce((sum, item) => sum + (item.Menu_Price * item.qty), 0);
    },

    get isCartValid() {
        if (!this.cart || this.cart.length === 0) return false;
        if (this.orderType === 'Delivery') {
            if (this.cartTotal < 200) return false;
            if (!this.useCurrentAddress) {
                if (!this.manualAddress.street || !this.manualAddress.brgy || !this.manualAddress.city) return false;
            } else {
                if (!this.addresses || this.addresses.length === 0) return false;
                // Double check if the current address is not a dummy one
                const current = this.addresses[0];
                if (!current || current.Add_City === 'N/A' || current.Add_Street.includes('Branch Pickup')) return false;
            }
        }
        if (!this.paymentMethod) return false;
        return true;
    },

    async placeOrder() {
        // Double check validation before proceeding
        if(!this.isCartValid) {
            let reason = 'Please complete your order details.';
            if (!this.cart || this.cart.length === 0) reason = 'Your tray is empty. Add some items first!';
            else if (this.orderType === 'Delivery' && this.cartTotal < 200) reason = 'Minimum order for delivery is ₱200.';
            else if (!this.paymentMethod) reason = 'Please select a payment mode (COD or E-Wallet).';
            else if (this.orderType === 'Delivery') {
                if (this.useCurrentAddress) {
                    if (!this.addresses || this.addresses.length === 0) reason = 'No saved address found. Please enter one manually.';
                    else if (this.addresses[0].Add_City === 'N/A') reason = 'Your saved address is incomplete. Please enter one manually.';
                } else if (!this.manualAddress.street || !this.manualAddress.brgy || !this.manualAddress.city) {
                    reason = 'Please provide a complete manual address (Street, Barangay, and City).';
                }
            }
            this.message = { success: false, text: reason };
            return;
        }

        try {
            const res = await fetch('orders_api.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    action: 'place_order', 
                    branch_id: this.selectedBranch.Brnch_ID,
                    type: this.orderType,
                    items: this.cart.map(i => ({ menu_id: i.Menu_ID, qty: i.qty, price: i.Menu_Price })),
                    total: this.cartTotal,
                    name: this.profileData ? `${this.profileData.fname} ${this.profileData.lname}` : 'Guest User',
                    num: this.profileData ? this.profileData.mobile : '09000000000',
                    payment_method: this.paymentMethod,
                    address: this.useCurrentAddress ? null : this.manualAddress
                })
            });
            const data = await res.json();
            this.message = { success: data.success, text: data.message };
            if(data.success) {
                this.cart = [];
                this.activeTab = 'customer_orders';
                this.fetchCustomerOrders();
                // Reset order state for next time
                this.paymentMethod = '';
                this.manualAddress = { province: 'Metro Manila', city: '', street: '', brgy: '', landmark: '' };
            }
        } catch (e) {
            this.message = { success: false, text: 'Order failed: Connection error.' };
        }
    },

    async fetchCustomerOrders() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_customer_orders' })
        });
        const data = await res.json();
        if(data.success) this.customerOrders = data.data;
    },

    async fetchRiderDeliveries() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_rider_deliveries' })
        });
        const data = await res.json();
        if(data.success) {
            this.riderDeliveries = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async finishDelivery(orderId) {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'complete_delivery', order_id: orderId })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.fetchRiderDeliveries();
    },

    async fetchOrders() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_orders' })
        });
        const data = await res.json();
        if(data.success) {
            this.orders = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async assignRider(orderId, riderId) {
        if(!riderId) return;
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'assign_rider', order_id: orderId, rider_id: riderId })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchOrders();
        }
    },

    async updateOrderStatus(orderId, status) {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_order_status', order_id: orderId, status: status })
        });
        const data = await res.json();
        if(data.success) {
            this.fetchOrders();
        }
    },

    async fetchOrderItems(orderId) {
        if (this.orderItems[orderId]) {
            delete this.orderItems[orderId];
            return;
        }
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_order_items', order_id: orderId })
        });
        const data = await res.json();
        if(data.success) {
            this.orderItems[orderId] = data.data;
        }
    },

    async fetchBranchInfo() {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_branch_info' })
        });
        const data = await res.json();
        if(data.success) {
            this.currentBranch = data.branch;
            this.$nextTick(() => lucide.createIcons());
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

    async fetchStaff() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_staff' })
        });
        const data = await res.json();
        if(data.success) {
            this.staffMembers = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async fetchRiders() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_riders' })
        });
        const data = await res.json();
        if(data.success) {
            this.riders = data.data;
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

    async deleteBranch(id) {
        if(!confirm('Are you sure you want to delete this branch? This cannot be undone.')) return;
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_branch', id })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchBranches();
            this.fetchStats();
        }
    },

    async deleteManager(id) {
        if(!confirm('Are you sure you want to delete this manager account?')) return;
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_manager', id })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchManagers();
            this.fetchBranches();
            this.fetchStats();
        }
    },

    async deleteUser(id, source) {
        if(!confirm('Are you sure you want to delete this account? This action cannot be undone.')) return;
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_user', id, source })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchAllUsers();
            this.fetchStats();
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
            <div class="bg-white p-1 rounded-lg cursor-pointer" @click="activeTab = 'overview'">
                <img src="logo.png" class="w-8 h-8 object-contain" alt="Mang Inasal">
            </div>
            <span x-show="sidebarOpen" class="font-poppins font-black text-xl tracking-tight uppercase cursor-pointer" @click="activeTab = 'overview'" x-transition>Inasal</span>
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
                    <span x-show="sidebarOpen" class="font-bold text-sm">Branch Managers</span>
                </a>
                <a href="#" @click="activeTab = 'manage_staff'" 
                   :class="activeTab === 'manage_staff' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="users-2" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Branch Staff</span>
                </a>
                <a href="#" @click="activeTab = 'manage_riders'" 
                   :class="activeTab === 'manage_riders' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="bike" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Rider Fleet</span>
                </a>
                <a href="#" @click="activeTab = 'user_directory'" 
                   :class="activeTab === 'user_directory' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="book-user" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">User Directory</span>
                </a>
                <a href="#" @click="activeTab = 'customer_tracking'" 
                   :class="activeTab === 'customer_tracking' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="users-round" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Customers</span>
                </a>
                <a href="#" @click="activeTab = 'reports'" 
                   :class="activeTab === 'reports' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Reports</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Branch Manager'): ?>
                <a href="#" @click="activeTab = 'orders'" 
                   :class="activeTab === 'orders' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Orders</span>
                </a>
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
                <a href="#" @click="activeTab = 'orders'" 
                   :class="activeTab === 'orders' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Active Orders</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="list-check" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">Order History</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Driver'): ?>
                <a href="#" @click="activeTab = 'pending_deliveries'" 
                   :class="activeTab === 'pending_deliveries' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="navigation" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Active Deliveries</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen">My Route</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Customer'): ?>
                <a href="#" @click="activeTab = 'order_now'" 
                   :class="activeTab === 'order_now' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="utensils" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Order Now</span>
                </a>
                <a href="#" @click="activeTab = 'promos'" 
                   :class="activeTab === 'promos' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="tag" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Promos</span>
                </a>
                <a href="#" @click="activeTab = 'customer_orders'" 
                   :class="activeTab === 'customer_orders' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="history" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">My Orders</span>
                </a>
                <a href="#" @click="activeTab = 'profile'" 
                   :class="activeTab === 'profile' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Profile</span>
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
            <div class="flex items-center gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-black text-slate-800 font-poppins capitalize"><?php echo str_replace('_', ' ', $role); ?> Dashboard</h1>
                    <div class="flex items-center gap-4 mt-1">
                        <?php if ($role === 'Customer'): ?>
                            <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'text-[#006738] font-black' : 'text-slate-400 font-bold'" class="text-xs uppercase tracking-widest hover:text-[#006738] transition-colors">Home</button>
                            <button @click="activeTab = 'order_now'" :class="activeTab === 'order_now' ? 'text-[#006738] font-black' : 'text-slate-400 font-bold'" class="text-xs uppercase tracking-widest hover:text-[#006738] transition-colors">Menu</button>
                            <button @click="activeTab = 'order_now'; selectedBranch = null" :class="activeTab === 'order_now' && !selectedBranch ? 'text-[#006738] font-black' : 'text-slate-400 font-bold'" class="text-xs uppercase tracking-widest hover:text-[#006738] transition-colors">Stores</button>
                        <?php elseif (in_array($role, ['Branch Manager', 'Kitchen Staff'])): ?>
                            <div class="flex items-center gap-2 text-slate-500">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span class="text-sm font-bold" x-text="currentBranch ? currentBranch.Brnch_Name : 'Assigning Branch...'"></span>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-500">Welcome back, we're ready to grill!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($role === 'Customer'): ?>
                <div class="hidden lg:flex items-center gap-3 px-6 py-3 bg-white border border-slate-100 rounded-2xl shadow-sm cursor-pointer hover:border-green-100 transition-all">
                    <div class="w-8 h-8 bg-green-50 text-[#006738] rounded-lg flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] font-black uppercase text-slate-400 leading-none mb-1">Delivering to</p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-700" x-text="addresses[0] ? addresses[0].Add_Street + ', ' + addresses[0].Add_City : 'Select address...'"></span>
                            <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <template x-if="currentBranch && activeTab === 'overview'">
                    <div class="hidden sm:flex items-center gap-2 px-4 py-2 bg-white border border-slate-100 rounded-2xl shadow-sm animate-in fade-in slide-in-from-left-4 duration-500">
                        <div class="w-8 h-8 bg-green-50 text-[#006738] rounded-lg flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 leading-none mb-1">Assigned Branch</p>
                            <p class="text-xs font-bold text-slate-700" x-text="currentBranch.Brnch_Name + ' - ' + currentBranch.Brnch_City"></p>
                        </div>
                    </div>
                </template>
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
        <?php if ($role !== 'Customer'): ?>
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
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Managers</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.managers"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="users-2" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Branch Staff</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.staff"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-yellow-50 text-yellow-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="bike" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Riders</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.riders"></p>
                </div>
            <?php elseif ($role === 'Branch Manager'): ?>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all">
                    <div class="w-14 h-14 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Kitchen & Staff</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.staff"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden group">
                    <div class="w-14 h-14 bg-yellow-50 text-yellow-600 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="bike" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Rider Fleet</h3>
                    <p class="text-4xl font-black text-slate-800" x-text="stats.riders"></p>
                </div>
                <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden col-span-1 lg:col-span-2">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-7 h-7"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase text-slate-400 mb-1 tracking-widest">Today's Sales</p>
                            <h3 class="text-2xl font-black text-emerald-600" x-text="'₱' + parseFloat(stats.dailySales).toLocaleString()"></h3>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Daily Progress</span>
                            <span class="text-xs font-bold text-slate-600" x-text="Math.round((stats.dailySales / stats.dailyGoal) * 100) + '% of ₱' + stats.dailyGoal.toLocaleString()"></span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all duration-1000" :style="'width: ' + Math.min(100, (stats.dailySales / stats.dailyGoal) * 100) + '%'"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Dynamic Role Content -->
        <div x-show="activeTab === 'overview' && role !== 'Customer'" class="space-y-8">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                        <?php if($role === 'Branch Manager'): ?>
                            <button @click="activeTab = 'workforce'" class="flex items-center gap-5 p-6 bg-[#fcfbf7] rounded-[2rem] hover:bg-[#006738] hover:text-white transition-all group border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-green-900/10 cursor-pointer">
                                <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-[#006738] group-hover:bg-white/10 group-hover:text-white transition-colors shadow-sm">
                                    <i data-lucide="user-plus" class="w-7 h-7"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="font-black text-sm uppercase tracking-tight">Expand Team</h4>
                                    <p class="text-xs opacity-60">Add Personnel</p>
                                </div>
                            </button>
                            <button @click="activeTab = 'availability'" class="flex items-center gap-5 p-6 bg-[#fcfbf7] rounded-[2rem] hover:bg-[#ffec00] hover:text-black transition-all group border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-yellow-500/20 cursor-pointer">
                                <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center text-yellow-600 group-hover:bg-black/10 group-hover:text-black transition-colors shadow-sm">
                                    <i data-lucide="shopping-bag" class="w-7 h-7"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="font-black text-sm uppercase tracking-tight">Daily Menu</h4>
                                    <p class="text-xs opacity-60">Set Availability</p>
                                </div>
                            </button>

                            <!-- Rider Stats Leaderboard -->
                            <div class="lg:col-span-1 p-6 bg-white rounded-[2rem] border border-slate-100 shadow-sm">
                                <h4 class="text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest flex items-center gap-2">
                                    <i data-lucide="trophy" class="w-3 h-3 text-yellow-500"></i> Rider Performance
                                </h4>
                                <div class="space-y-3">
                                    <template x-for="rider in stats.riderPerformance" :key="rider.Rider_ID">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-green-500" :class="rider.stats > 0 ? 'bg-green-500' : 'bg-slate-200'"></div>
                                                <span class="text-xs font-bold text-slate-600" x-text="rider.Rider_FName"></span>
                                            </div>
                                            <span class="text-[10px] font-black text-[#006738]" x-text="rider.stats + ' Drops'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'overview' && role === 'Customer'" class="space-y-8" x-cloak>
            <!-- Hero Carousel -->
            <div class="relative w-full h-[400px] mb-12 rounded-[2.5rem] overflow-hidden group shadow-2xl">
                <template x-for="(item, index) in carouselItems" :key="index">
                    <div x-show="currentCarousel === index" 
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 scale-105"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute inset-0">
                        <img :src="item.image" class="w-full h-full object-cover brightness-75" :alt="item.title">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex flex-col justify-end p-12">
                            <h2 class="text-white text-5xl font-black font-poppins mb-2" x-text="item.title"></h2>
                            <p class="text-[#ffec00] text-xl font-black tracking-widest mb-8" x-text="item.subtitle"></p>
                            <button @click="activeTab = 'order_now'" class="bg-[#ffec00] text-black font-black px-8 py-4 rounded-full w-fit hover:scale-105 transition-transform shadow-xl shadow-yellow-500/20 uppercase tracking-widest text-sm">
                                Order Now
                            </button>
                        </div>
                    </div>
                </template>
                
                <!-- Carousel Controls -->
                <button @click="currentCarousel = (currentCarousel - 1 + carouselItems.length) % carouselItems.length" 
                        class="absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all opacity-0 group-hover:opacity-100">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button @click="currentCarousel = (currentCarousel + 1) % carouselItems.length" 
                        class="absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all opacity-0 group-hover:opacity-100">
                    <i data-lucide="chevron-right"></i>
                </button>
                
                <div class="absolute bottom-6 left-12 flex gap-2">
                    <template x-for="(item, index) in carouselItems" :key="index">
                        <button @click="currentCarousel = index" 
                                :class="currentCarousel === index ? 'w-10 bg-[#ffec00]' : 'w-2 bg-white/50'"
                                class="h-2 rounded-full transition-all duration-300"></button>
                    </template>
                </div>
            </div>

            <!-- Featured Menu (Categories) -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-2xl font-black text-slate-800 font-poppins capitalize">Featured Selection</h3>
                    <button class="text-green-600 font-bold flex items-center gap-1 hover:gap-2 transition-all">
                        View All <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex gap-8 overflow-x-auto pb-4 no-scrollbar">
                    <template x-for="cat in categories" :key="cat.name">
                        <div class="flex flex-col items-center gap-4 group cursor-pointer flex-shrink-0" @click="selectedCategory = cat.name; activeTab = 'order_now'">
                            <div :class="selectedCategory === cat.name ? 'bg-[#006738] text-white scale-110 shadow-xl shadow-green-900/20' : 'bg-white text-slate-400 border border-slate-100 hover:border-green-100'"
                                 class="w-24 h-24 rounded-full flex items-center justify-center transition-all duration-300 overflow-hidden p-2">
                                <template x-if="cat.image">
                                    <img :src="cat.image" :alt="cat.name" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML = '<i data-lucide=\'' + this.getAttribute('data-icon') + '\' class=\'w-10 h-10\'></i>'" :data-icon="cat.icon">
                                </template>
                                <template x-if="!cat.image">
                                    <i :data-lucide="cat.icon" class="w-10 h-10"></i>
                                </template>
                            </div>
                            <span :class="selectedCategory === cat.name ? 'text-[#006738] font-black' : 'text-slate-500 font-bold'"
                                  class="text-xs transition-colors" x-text="cat.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-2xl font-black text-slate-800 font-poppins mb-6">Nearby Branches</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <template x-for="branch in customerBranches.slice(0, 3)" :key="branch.Brnch_ID">
                        <div @click="selectBranch(branch.Brnch_ID); activeTab = 'order_now'" class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm hover:shadow-2xl hover:border-[#006738] transition-all cursor-pointer group">
                            <div class="w-16 h-16 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-inner">
                                <i data-lucide="map-pin" class="w-8 h-8"></i>
                            </div>
                            <h4 class="font-black text-slate-800 text-xl font-poppins mb-2" x-text="branch.Brnch_Name"></h4>
                            <p class="text-slate-400 text-sm font-medium mb-4 flex items-center gap-2">
                                <i data-lucide="navigation" class="w-4 h-4 text-[#006738]"></i>
                                <span x-text="branch.Brnch_City"></span>
                            </p>
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#006738] bg-green-50 px-3 py-1 rounded-full">Open Now</span>
                        </div>
                    </template>
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
                            <div class="flex items-center gap-2">
                                <span :class="branch.Brnch_Status === 'Y' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full">
                                    <span x-text="branch.Brnch_Status === 'Y' ? 'Active' : 'Closed'"></span>
                                </span>
                                <button @click="deleteBranch(branch.Brnch_ID)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg mb-1" x-text="branch.Brnch_Name"></h3>
                        <p class="text-slate-500 text-sm mb-2" x-text="`${branch.Brnch_Street}, ${branch.Brnch_Brgy}, ${branch.Brnch_City}`"></p>
                        
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                <i data-lucide="user" class="w-3 h-3"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-600" x-text="branch.fname ? `${branch.fname} ${branch.lname}` : 'No Manager Assigned'"></span>
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
                                <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="manager in managers" :key="manager.id">
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 font-bold text-slate-800" x-text="`${manager.fname} ${manager.lname}`"></td>
                                    <td class="p-4 text-sm text-slate-600" x-text="manager.email"></td>
                                    <td class="p-4">
                                        <span :class="manager.Brnch_Name ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="manager.Brnch_Name || 'Unassigned'"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button @click="deleteManager(manager.id)" class="text-red-400 hover:text-red-600 p-2 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
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
                <h2 class="text-xl font-black text-slate-800 font-poppins">Branch Managers</h2>
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
                        <template x-for="manager in managers" :key="manager.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="`${manager.fname} ${manager.lname}`"></div>
                                    <div class="text-[10px] text-slate-400" x-text="manager.mobile"></div>
                                </td>
                                <td class="p-4 text-sm text-slate-600" x-text="manager.email"></td>
                                <td class="p-4">
                                    <span :class="manager.Brnch_Name ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="manager.Brnch_Name || 'Unassigned'"></span>
                                </td>
                                <td class="p-4">
                                    <span :class="{
                                        'bg-green-100 text-green-700': manager.status === 'Active' || manager.status === 'Y',
                                        'bg-red-100 text-red-700': manager.status === 'Resigned' || manager.status === 'N',
                                        'bg-orange-100 text-orange-700': manager.status === 'Suspended'
                                    }" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" 
                                       x-text="(manager.status === 'Y' || manager.status === 'Active') ? 'Active' : (manager.status === 'N' || manager.status === 'Resigned' ? 'Resigned' : manager.status)">
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
                                            <button @click="updateManagerStatus(manager.id, 'Active'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-green-50 text-green-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-green-500"></div> Set as Active
                                            </button>
                                            <button @click="updateManagerStatus(manager.id, 'Suspended'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-orange-50 text-orange-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-orange-500"></div> Suspend Account
                                            </button>
                                            <button @click="updateManagerStatus(manager.id, 'Resigned'); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-red-50 text-red-600 flex items-center gap-3 transition-colors">
                                                <div class="w-2 h-2 rounded-full bg-red-500"></div> Mark as Resigned
                                            </button>
                                            <button @click="deleteManager(manager.id); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-red-100 text-red-700 flex items-center gap-3 transition-colors border-t border-slate-50 mt-1">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i> Delete Account
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

        <div x-show="activeTab === 'manage_staff'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins">Branch Workforce (Staff)</h2>
                    <p class="text-slate-500 text-sm italic">Overall view of kitchen and service staff per branch.</p>
                </div>
                <button @click="fetchStaff()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Email</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Branch</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Reports To</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="staff in staffMembers" :key="'staff'+staff.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="`${staff.fname} ${staff.lname}`"></div>
                                    <div class="text-[10px] text-slate-400" x-text="staff.mobile"></div>
                                </td>
                                <td class="p-4 text-sm text-slate-600" x-text="staff.email"></td>
                                <td class="p-4">
                                    <span class="bg-blue-100 text-blue-700 text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="staff.Brnch_Name"></span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2" x-show="staff.Mgr_FName">
                                        <div class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-[8px] font-bold text-slate-600" x-text="staff.Mgr_FName[0]"></div>
                                        <span class="text-xs font-bold text-slate-700" x-text="`${staff.Mgr_FName} ${staff.Mgr_LName}`"></span>
                                    </div>
                                    <span x-show="!staff.Mgr_FName" class="text-xs text-slate-400 italic">No Manager</span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded-full bg-green-100 text-green-700" x-text="staff.status"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="activeTab === 'manage_riders'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins">Rider Fleet (Global)</h2>
                    <p class="text-slate-500 text-sm italic">Tracking all delivery riders across the network.</p>
                </div>
                <button @click="fetchRiders()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Email</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Branch Assignment</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="rider in riders" :key="'rider'+rider.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="`${rider.fname} ${rider.lname}`"></div>
                                    <div class="text-[10px] text-slate-400" x-text="rider.mobile"></div>
                                </td>
                                <td class="p-4 text-sm text-slate-600" x-text="rider.email"></td>
                                <td class="p-4">
                                    <span class="bg-yellow-100 text-yellow-700 text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="rider.Brnch_Name"></span>
                                </td>
                                <td class="p-4">
                                    <span :class="rider.status === 'Y' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="rider.status === 'Y' ? 'Available' : 'Busy'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
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
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
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
                                <td class="p-4 text-right">
                                    <button @click="deleteUser(user.id, user.source)" 
                                            class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                            x-show="!(user.source === 'Admin' && user.id == <?php echo $user_id; ?>)">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Tracking Tab -->
        <div x-show="activeTab === 'customer_tracking'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Customer Tracking</h2>
                    <p class="text-slate-500 text-sm italic">Loyal grill enthusiasts and their account details.</p>
                </div>
                <button @click="fetchAllUsers()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Customer Name</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Contact Info</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="user in allUsers.filter(u => u.role === 'Customer')" :key="'cust' + user.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800" x-text="`${user.fname} ${user.lname}`"></div>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-1">
                                        <i data-lucide="calendar" class="w-3 h-3"></i>
                                        Registered Member
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-sm font-medium text-[#006738]" x-text="user.email"></div>
                                    <div class="text-[10px] text-slate-400" x-text="user.mobile || 'No mobile saved'"></div>
                                </td>
                                <td class="p-4 text-right">
                                    <button @click="deleteUser(user.id, 'Customer')" 
                                            class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="allUsers.filter(u => u.role === 'Customer').length === 0">
                            <tr>
                                <td colspan="3" class="p-12 text-center text-slate-400 italic">No customers registered yet.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reports Tab -->
        <div x-show="activeTab === 'reports'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">System Reports</h2>
                    <p class="text-slate-500 text-sm">Overview of system-wide performance and sales.</p>
                </div>
                <button @click="fetchReports()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Daily Sales Card -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 opacity-5 group-hover:scale-110 transition-transform">
                        <i data-lucide="trending-up" class="w-32 h-32 text-[#006738]"></i>
                    </div>
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-2 tracking-widest">Today's Sales</p>
                    <h3 class="text-4xl font-black text-[#006738] font-poppins" x-text="'₱' + parseFloat(reports.dailySales).toLocaleString()"></h3>
                    <p class="text-xs text-slate-400 mt-4 font-bold">Successfully completed orders today</p>
                </div>

                <!-- Total Orders Card -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 opacity-5 group-hover:scale-110 transition-transform">
                        <i data-lucide="shopping-bag" class="w-32 h-32 text-orange-500"></i>
                    </div>
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-2 tracking-widest">Orders Today</p>
                    <h3 class="text-4xl font-black text-slate-800 font-poppins" x-text="reports.totalOrders"></h3>
                    <p class="text-xs text-slate-400 mt-4 font-bold">Total inflow of orders including pending</p>
                </div>

                <!-- Best Seller Card -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm lg:col-span-1 md:col-span-2">
                    <h3 class="font-black text-slate-800 mb-6 flex items-center gap-2">
                        <i data-lucide="award" class="w-5 h-5 text-yellow-500"></i> Hot Selling Items
                    </h3>
                    <div class="space-y-3">
                        <template x-for="(item, index) in reports.topItems" :key="index">
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-green-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-lg bg-green-100 text-[#006738] flex items-center justify-center font-black text-[10px]" x-text="index + 1"></div>
                                    <p class="font-bold text-slate-700 text-sm" x-text="item.Menu_Name"></p>
                                </div>
                                <p class="text-[10px] font-black text-[#006738] bg-white px-2 py-1 rounded-lg border border-slate-100 shadow-sm" x-text="item.total_qty + ' units'"></p>
                            </div>
                        </template>
                        <template x-if="reports.topItems.length === 0">
                            <div class="py-10 text-center">
                                <p class="text-slate-400 italic text-sm">No data available yet</p>
                            </div>
                        </template>
                    </div>
                </div>
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
        <div x-show="showBranchModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showBranchModal = false">
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

        <div x-show="showManagerModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showManagerModal = false">
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

        <div x-show="showMenuModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
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
                                <option>Family Fiesta</option>
                                <option>Buddy Fiesta</option>
                                <option>Dessert</option>
                                <option>Palabok</option>
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
        <?php endif; ?>

        <?php if ($role === 'Branch Manager' || $role === 'Kitchen Staff'): ?>
        <div x-show="activeTab === 'orders'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Order Management</h2>
                    <p class="text-slate-500 text-sm">Monitor and dispatch orders for your branch.</p>
                </div>
                <button @click="fetchOrders()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-4">
                <template x-if="orders.length === 0">
                    <div class="bg-white border-2 border-dashed border-slate-100 rounded-[2rem] p-12 text-center">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                            <i data-lucide="clipboard-x" class="w-10 h-10"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-400 uppercase tracking-widest">No Active Orders</h3>
                        <p class="text-slate-400 text-sm italic">New orders from customers will appear here.</p>
                    </div>
                </template>
                <template x-for="order in orders" :key="order.Order_ID">
                    <div class="bg-white border-2 border-slate-50 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex flex-wrap justify-between items-start gap-4">
                            <div class="flex gap-4">
                                <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-[#006738] shadow-inner">
                                    <i data-lucide="package" class="w-7 h-7"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-black text-slate-800" x-text="order.Order_Code"></h3>
                                        <span :class="{
                                            'bg-orange-100 text-orange-700': order.Order_Stat === 'Pending',
                                            'bg-blue-100 text-blue-700': order.Order_Stat === 'Preparing',
                                            'bg-purple-100 text-purple-700': order.Order_Stat === 'Ready',
                                            'bg-green-100 text-green-700': order.Order_Stat === 'Delivering',
                                            'bg-slate-100 text-slate-700': order.Order_Stat === 'Completed'
                                        }" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="order.Order_Stat"></span>
                                    </div>
                                    <p class="text-xs text-slate-400 font-bold" x-text="`${order.Cust_FName} ${order.Cust_LName}`"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <p class="text-[10px] text-slate-300 uppercase tracking-tighter" x-text="order.Order_Type"></p>
                                        <span class="text-[10px] font-black text-[#006738] bg-green-50 px-2 py-0.5 rounded border border-green-100" x-text="order.Pay_Method"></span>
                                    </div>
                                </div>
                            </div>

                            <button @click="fetchOrderItems(order.Order_ID)" class="text-[10px] font-black uppercase text-[#006738] border border-green-100 px-3 py-1 rounded-lg hover:bg-green-50 transition-colors">
                                View Items
                            </button>

                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Total Amount</p>
                                    <p class="text-lg font-black text-[#006738]" x-text="'₱' + parseFloat(order.Order_Total_Amount).toFixed(2)"></p>
                                </div>
                                
                                <div class="flex gap-2">
                                    <!-- Transitions: Restricted to Kitchen Staff per user request -->
                                    <template x-if="order.Order_Stat === 'Pending' && role === 'Kitchen Staff'">
                                        <button @click="updateOrderStatus(order.Order_ID, 'Preparing')" class="p-3 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all font-bold text-xs">Start Cooking</button>
                                    </template>
                                    <template x-if="order.Order_Stat === 'Preparing' && role === 'Kitchen Staff'">
                                        <button @click="updateOrderStatus(order.Order_ID, 'Ready')" class="p-3 bg-purple-50 text-purple-600 rounded-xl hover:bg-purple-600 hover:text-white transition-all font-bold text-xs">Mark as Ready</button>
                                    </template>
                                    
                                    <!-- Dispatch Logic: Both roles can send to rider or collect -->
                                    <template x-if="order.Order_Stat === 'Ready' && order.Order_Type === 'Delivery' && (role === 'Kitchen Staff' || role === 'Branch Manager')">
                                        <div class="flex items-center gap-2">
                                            <select class="p-3 bg-[#fcfbf7] border-2 border-slate-100 rounded-xl text-xs font-bold outline-none focus:border-[#006738]" 
                                                    @change="assignRider(order.Order_ID, $event.target.value)">
                                                <option value="">Assign Rider</option>
                                                <template x-for="rider in workforce.filter(p => p.source === 'Rider')" :key="rider.id">
                                                    <option :value="rider.id" x-text="`${rider.fname} ${rider.lname} (${rider.active_orders > 0 ? rider.active_orders + ' orders' : 'Available'})`"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </template>

                                    <template x-if="order.Order_Stat === 'Ready' && order.Order_Type !== 'Delivery' && (role === 'Kitchen Staff' || role === 'Branch Manager')">
                                        <button @click="updateOrderStatus(order.Order_ID, 'Completed')" class="p-3 bg-[#006738] text-white rounded-xl hover:shadow-lg transition-all font-bold text-xs">Mark Collected</button>
                                    </template>

                                    <template x-if="order.Order_Stat === 'Delivering'">
                                        <div class="flex items-center gap-2 px-4 py-2 bg-green-50 text-[#006738] rounded-xl border border-green-100">
                                            <i data-lucide="bike" class="w-4 h-4"></i>
                                            <div class="text-left">
                                                <p class="text-[9px] font-black uppercase opacity-60">En-route</p>
                                                <p class="text-[10px] font-bold" x-text="order.Rider_FName"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details Expansion -->
                        <div x-show="orderItems[order.Order_ID]" x-collapse x-cloak class="mt-6 pt-6 border-t border-slate-50">
                            <div class="bg-slate-50/50 rounded-2xl p-4">
                                <h4 class="text-[10px] font-black uppercase text-slate-400 mb-3 tracking-widest">Kitchen Ticket</h4>
                                <div class="space-y-2">
                                    <template x-for="item in orderItems[order.Order_ID]" :key="item.OItem_ID">
                                        <div class="flex justify-between items-center text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 bg-white border border-slate-200 rounded flex items-center justify-center font-black text-[#006738] text-[10px]" x-text="item.OItem_Quantity + 'x'"></span>
                                                <span class="font-bold text-slate-700" x-text="item.Menu_Name"></span>
                                                <span class="text-[10px] font-black uppercase text-slate-400" x-text="item.Menu_Size"></span>
                                            </div>
                                            <span class="font-black text-slate-400 text-xs" x-text="'₱' + parseFloat(item.OItem_Unit_Price).toFixed(0)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === 'Branch Manager'): ?>
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
        <?php endif; ?>

        <!-- Rider Tab -->
        <?php if ($role === 'Driver'): ?>
        <div x-show="activeTab === 'pending_deliveries'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">My Deliveries</h2>
                    <p class="text-slate-500 text-sm">Active routes assigned to you.</p>
                </div>
                <button @click="fetchRiderDeliveries()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-for="order in riderDeliveries" :key="order.Order_ID">
                    <div class="bg-white border-2 border-slate-50 rounded-[2rem] p-8 shadow-sm hover:shadow-xl transition-all relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-5">
                            <i data-lucide="bike" class="w-32 h-32 text-[#006738]"></i>
                        </div>
                        
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-14 h-14 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center shadow-inner">
                                <i data-lucide="navigation" class="w-7 h-7"></i>
                            </div>
                            <div class="text-right">
                                <span class="bg-green-100 text-green-700 text-[10px] font-black uppercase px-3 py-1.5 rounded-full tracking-widest animate-pulse">In Delivery</span>
                                <p class="text-[10px] font-black text-slate-300 uppercase mt-2 tracking-tighter" x-text="order.Order_Code"></p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-8">
                            <div>
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Customer</p>
                                <p class="text-xl font-black text-slate-800 font-poppins" x-text="`${order.Cust_FName} ${order.Cust_LName}`"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Delivery Address</p>
                                <div class="flex items-start justify-between gap-4">
                                    <p class="text-sm font-bold text-slate-600 flex items-start gap-2">
                                        <i data-lucide="map-pin" class="w-4 h-4 mt-0.5 text-red-500"></i>
                                        <span x-text="`${order.Add_Street}, ${order.Add_City}`"></span>
                                    </p>
                                    <a :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(order.Add_Street + ', ' + order.Add_City)" target="_blank" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-blue-600">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-50 flex justify-between items-center">
                                <div>
                                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Payment Method</p>
                                    <p class="text-sm font-black text-[#006738]" x-text="order.Pay_Method"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Collect Amount</p>
                                    <p class="text-sm font-black text-slate-800" x-text="order.Pay_Method === 'Cash (COD)' ? '₱' + parseFloat(order.Pay_Amount).toFixed(0) : 'Already Paid'"></p>
                                </div>
                            </div>
                        </div>

                        <button @click="finishDelivery(order.Order_ID)" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:bg-black transition-all transform hover:-translate-y-1 flex items-center justify-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            Mark as Delivered
                        </button>
                    </div>
                </template>
                <template x-if="riderDeliveries.length === 0">
                    <div class="md:col-span-2 py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                        <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="inbox" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-black text-slate-400 text-xl font-poppins">No Active Deliveries</h3>
                        <p class="text-slate-300 text-sm mt-2">Check back later when orders are ready!</p>
                    </div>
                </template>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer Tabs -->
        <?php if ($role === 'Customer'): ?>
        <div x-show="activeTab === 'order_now' && !selectedBranch" x-cloak>
            <div class="mb-10 text-center">
                <h2 class="text-4xl font-black text-slate-800 font-poppins mb-2">Craving for Inasal?</h2>
                <p class="text-slate-500 italic">Select your preferred branch to start grilling!</p>
            </div>

            <!-- Branch Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <template x-for="branch in customerBranches" :key="branch.Brnch_ID">
                    <div @click="selectBranch(branch.Brnch_ID)" class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm hover:shadow-2xl hover:border-[#006738] transition-all cursor-pointer group">
                        <div class="w-16 h-16 bg-green-50 text-[#006738] rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-inner">
                            <i data-lucide="store" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 font-poppins mb-2" x-text="branch.Brnch_Name"></h3>
                        <p class="text-slate-500 text-sm mb-6 line-clamp-2" x-text="`${branch.Brnch_Street}, ${branch.Brnch_Brgy}, ${branch.Brnch_City}`"></p>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-slate-50">
                            <span class="text-xs font-black uppercase text-[#006738] tracking-widest">Order Now</span>
                            <div class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center group-hover:bg-[#ffec00] transition-colors">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Menu/Store View -->
        <div x-show="activeTab === 'order_now' && selectedBranch" x-cloak>
            <!-- Header for Store -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <button @click="selectedBranch = null; cart = []" class="w-12 h-12 bg-white rounded-2xl border border-slate-100 flex items-center justify-center text-slate-400 hover:text-[#006738] transition-all shadow-sm">
                        <i data-lucide="chevron-left" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h3 class="font-black text-2xl text-slate-800 font-poppins" x-text="selectedBranch?.Brnch_Name"></h3>
                        <p class="text-xs text-slate-400 font-bold flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-3 h-3 text-[#006738]"></i>
                            <span x-text="selectedBranch?.Brnch_City"></span>
                        </p>
                    </div>
                </div>
                
                <div class="flex-1 max-w-md relative group">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                    <input type="text" x-model="searchQuery" 
                           placeholder="Search your favorites..." 
                           class="w-full bg-white border border-slate-100 rounded-2xl py-4 pl-12 pr-4 text-slate-800 shadow-sm focus:border-[#006738] focus:ring-4 focus:ring-green-50 outline-none transition-all font-bold text-sm">
                </div>
            </div>

            <!-- Categories Pills -->
            <div class="flex gap-3 overflow-x-auto pb-4 no-scrollbar mb-8">
                <template x-for="cat in categories" :key="cat.name">
                    <button @click="selectedCategory = cat.name"
                            :class="selectedCategory === cat.name ? 'bg-[#006738] text-white' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50'"
                            class="px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest whitespace-nowrap transition-all shadow-sm">
                        <span x-text="cat.name"></span>
                    </button>
                </template>
            </div>

            <!-- Store Main Split -->
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="flex-1">
                    <h3 class="text-xl font-black text-slate-800 font-poppins mb-6 pb-2 border-b-2 border-green-50" x-text="selectedCategory"></h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-6">
                        <template x-for="item in customerMenu.filter(i => (!searchQuery || i.Menu_Name.toLowerCase().includes(searchQuery.toLowerCase())) && (i.Menu_Category === (categories.find(c => c.name === selectedCategory)?.db || '') || selectedCategory === 'Must Try!'))" :key="item.Menu_ID">
                            <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all group overflow-hidden relative">
                                <!-- Save Badge -->
                                <div class="absolute top-4 left-4 z-10">
                                    <span class="bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg">SAVE ₱15</span>
                                </div>
                                
                                <div class="w-full aspect-square bg-slate-50 rounded-3xl mb-6 overflow-hidden flex items-center justify-center text-[#006738] relative">
                                    <i data-lucide="utensils" class="w-16 h-16 opacity-5 group-hover:scale-125 transition-transform duration-500"></i>
                                    <div class="absolute inset-0 flex items-center justify-center p-4">
                                        <p class="text-[10px] text-slate-300 font-bold italic">Image Placeholder</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="font-black text-slate-800 font-poppins text-lg" x-text="item.Menu_Name"></h4>
                                        <p class="text-xs text-slate-400 font-medium line-clamp-2 mt-1" x-text="item.Menu_Description"></p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-slate-300">Price Starts</p>
                                            <p class="text-xl font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(0)"></p>
                                        </div>
                                        <button @click="addToCart(item)" class="w-12 h-12 bg-[#ffec00] text-black rounded-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all shadow-lg shadow-yellow-500/20">
                                            <i data-lucide="plus" class="w-6 h-6"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Shopping Tray (Cart) -->
                <div class="w-full lg:w-96">
                    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl p-8 sticky top-8">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 bg-[#ffec00] rounded-xl flex items-center justify-center shadow-lg shadow-yellow-500/20">
                                <i data-lucide="shopping-basket" class="w-5 h-5 text-black"></i>
                            </div>
                            <h3 class="font-black text-xl font-poppins">Your Tray</h3>
                        </div>

                        <div class="space-y-4 mb-8 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="group flex gap-4 bg-slate-50/50 p-4 rounded-3xl border border-slate-50 hover:bg-white hover:shadow-md transition-all">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex-shrink-0 flex items-center justify-center text-slate-300">
                                        <i data-lucide="utensils" class="w-6 h-6"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-black text-slate-800 truncate" x-text="item.Menu_Name"></p>
                                        <p class="text-[10px] font-black text-[#006738] mb-2" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(0)"></p>
                                        
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center bg-white rounded-lg border border-slate-100 p-1">
                                                <button @click="item.qty > 1 ? item.qty-- : cart.splice(index, 1)" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-red-500">-</button>
                                                <span class="w-8 text-center text-xs font-black text-slate-700" x-text="item.qty"></span>
                                                <button @click="item.qty++" class="w-6 h-6 flex items-center justify-center text-slate-400 hover:text-green-600">+</button>
                                            </div>
                                            <span class="text-sm font-black text-[#006738]" x-text="'₱' + (item.qty * item.Menu_Price).toFixed(0)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="cart.length === 0">
                                <div class="py-12 text-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                                        <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                                    </div>
                                    <p class="text-slate-400 italic text-sm">Your tray is empty.</p>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-4 pt-6 border-t border-slate-100">
                            <div class="grid grid-cols-2 gap-3">
                                <button @click="orderType = 'Delivery'" 
                                        :class="orderType === 'Delivery' ? 'bg-[#006738] text-white' : 'bg-slate-50 text-slate-400'"
                                        class="py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Delivery</button>
                                <button @click="orderType = 'Take-out'" 
                                        :class="orderType === 'Take-out' ? 'bg-[#006738] text-white' : 'bg-slate-50 text-slate-400'"
                                        class="py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Pickup</button>
                            </div>
                            
                            <div class="space-y-3">
                                <span class="font-black text-slate-400 uppercase text-[10px] tracking-widest pl-1">Payment Method</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="paymentMethod = 'Cash (COD)'" 
                                            :class="paymentMethod === 'Cash (COD)' ? 'border-[#006738] bg-green-50 text-[#006738]' : 'border-slate-100 text-slate-400 bg-white'"
                                            class="py-3 border-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Cash (COD)</button>
                                    <button @click="paymentMethod = 'E-Wallet'" 
                                            :class="paymentMethod === 'E-Wallet' ? 'border-[#006738] bg-green-50 text-[#006738]' : 'border-slate-100 text-slate-400 bg-white'"
                                            class="py-3 border-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">E-Wallet</button>
                                </div>
                            </div>

                            <div class="space-y-3" x-show="orderType === 'Delivery'">
                                <div class="flex justify-between items-center px-1">
                                    <span class="font-black text-slate-400 uppercase text-[10px] tracking-widest">Delivery Address</span>
                                    <button @click="useCurrentAddress = !useCurrentAddress" 
                                            class="text-[10px] font-black text-[#006738] underline"
                                            x-text="useCurrentAddress ? 'Change' : 'Use Current'"></button>
                                </div>
                                
                                <template x-if="useCurrentAddress">
                                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <p class="text-xs font-bold text-slate-600" x-text="addresses[0] ? addresses[0].Add_Street + ', ' + addresses[0].Add_City : 'No address set'"></p>
                                    </div>
                                </template>

                                <template x-if="!useCurrentAddress">
                                    <div class="space-y-2 animate-in fade-in slide-in-from-top-2">
                                        <input type="text" x-model="manualAddress.street" placeholder="Street / House No." class="w-full bg-slate-50 border border-slate-100 rounded-xl py-2 px-3 text-xs outline-none focus:border-[#006738]">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" x-model="manualAddress.brgy" placeholder="Barangay" class="w-full bg-slate-50 border border-slate-100 rounded-xl py-2 px-3 text-xs">
                                            <input type="text" x-model="manualAddress.city" placeholder="City" class="w-full bg-slate-50 border border-slate-100 rounded-xl py-2 px-3 text-xs">
                                        </div>
                                        <input type="text" x-model="manualAddress.landmark" placeholder="Nearest Landmark" class="w-full bg-slate-50 border border-slate-100 rounded-xl py-2 px-3 text-xs">
                                    </div>
                                </template>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="font-black text-slate-400 uppercase text-xs">Total Amount</span>
                                <span class="text-3xl font-black text-[#006738]" x-text="'₱' + parseFloat(cartTotal).toFixed(0)"></span>
                            </div>

                            <div class="space-y-4">
                                <button @click="placeOrder()" 
                                        :disabled="!isCartValid"
                                        :class="!isCartValid ? 'opacity-50 grayscale cursor-not-allowed' : 'hover:scale-[1.02] active:scale-95 shadow-green-900/10'"
                                        class="w-full bg-[#006738] text-white font-black py-5 rounded-[2rem] shadow-xl transition-all uppercase tracking-[0.2em] text-xs">
                                    Check Out Now
                                </button>
                                
                                <div class="space-y-1 text-center">
                                    <p x-show="orderType === 'Delivery' && cartTotal < 200" class="text-[10px] text-red-500 font-black uppercase tracking-widest leading-none">
                                        Min. ₱200 for delivery (Current: ₱<span x-text="cartTotal"></span>)
                                    </p>
                                    <p x-show="!paymentMethod" class="text-[10px] text-orange-500 font-black uppercase tracking-widest leading-none">
                                        Please select payment method
                                    </p>
                                    <p x-show="orderType === 'Delivery' && !useCurrentAddress && (!manualAddress.street || !manualAddress.city || !manualAddress.brgy)" class="text-[10px] text-orange-500 font-black uppercase tracking-widest leading-none">
                                        Please enter complete delivery address
                                    </p>
                                    <p x-show="orderType === 'Delivery' && useCurrentAddress && (!addresses || addresses.length === 0)" class="text-[10px] text-orange-500 font-black uppercase tracking-widest leading-none">
                                        Saved address missing. Use manual entry.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'customer_orders'" x-cloak>
            <div class="flex justify-between items-center mb-8">
                <div>
                   <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">My Orders</h2>
                   <p class="text-slate-500 text-sm">Track your cravings status live!</p>
                </div>
                <button @click="fetchCustomerOrders()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="order in customerOrders" :key="order.Order_ID">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-wrap justify-between items-center gap-4">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-[#006738]">
                                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-slate-800" x-text="order.Order_Code"></h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest" x-text="order.Order_Type"></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-8">
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase text-slate-300">Total</p>
                                <p class="font-black text-[#006738]" x-text="'₱' + parseFloat(order.Order_Total_Amount).toFixed(0)"></p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span :class="{
                                    'bg-orange-100 text-orange-700': order.Order_Stat === 'Pending',
                                    'bg-blue-100 text-blue-700': order.Order_Stat === 'Preparing',
                                    'bg-purple-100 text-purple-700': order.Order_Stat === 'Ready',
                                    'bg-green-100 text-green-700': order.Order_Stat === 'Delivering',
                                    'bg-slate-100 text-slate-800': order.Order_Stat === 'Completed'
                                }" class="text-[10px] font-black uppercase px-3 py-2 rounded-full min-w-[100px] text-center" x-text="order.Order_Stat"></span>
                                <button @click="selectedOrder = order; showTrackingModal = true" class="text-[10px] font-black text-[#006738] uppercase tracking-widest hover:underline px-2">View Receipt / Track</button>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="customerOrders.length === 0">
                    <div class="text-center py-20">
                        <p class="text-slate-400 italic">No orders yet. Let's get grilling!</p>
                    </div>
                </template>
            </div>

            <!-- Tracking & Receipt Modal -->
            <div x-show="showTrackingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 animate-in fade-in duration-300" x-cloak x-transition>
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden" @click.away="showTrackingModal = false">
                    <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-[#006738] text-white">
                        <div>
                            <h3 class="font-black text-xl font-poppins capitalize" x-text="'Tracking #' + (selectedOrder ? selectedOrder.Order_Code : '')"></h3>
                            <p class="text-xs font-bold text-white/70">Estimated Arrival: <span x-text="selectedOrder && selectedOrder.Dlvry_Current_ETA ? new Date(selectedOrder.Dlvry_Current_ETA).toLocaleTimeString() : 'N/A'"></span></p>
                        </div>
                        <button @click="showTrackingModal = false" class="bg-white/10 p-2 rounded-full hover:bg-white/20 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button>
                    </div>
                    
                    <div class="p-8 space-y-8">
                        <!-- Tracking Status -->
                        <div class="relative flex justify-between">
                            <div class="absolute top-5 left-0 right-0 h-1 bg-slate-100 z-0">
                                <div class="h-full bg-[#006738] transition-all duration-500" :style="{
                                    width: selectedOrder?.Order_Stat === 'Pending' ? '0%' :
                                           selectedOrder?.Order_Stat === 'Preparing' ? '25%' :
                                           selectedOrder?.Order_Stat === 'Ready' ? '50%' :
                                           selectedOrder?.Order_Stat === 'Delivering' ? '75%' : '100%'
                                }"></div>
                            </div>
                            <template x-for="step in ['Pending', 'Preparing', 'Ready', 'Delivering', 'Completed']">
                                <div class="relative z-10 flex flex-col items-center gap-2">
                                    <div :class="selectedOrder?.Order_Stat === step || (['Preparing', 'Ready', 'Delivering', 'Completed'].includes(selectedOrder?.Order_Stat) && step === 'Pending') || (['Ready', 'Delivering', 'Completed'].includes(selectedOrder?.Order_Stat) && step === 'Preparing') || (['Delivering', 'Completed'].includes(selectedOrder?.Order_Stat) && step === 'Ready') || (selectedOrder?.Order_Stat === 'Completed' && step === 'Delivering') ? 'bg-[#006738] text-white' : 'bg-white text-slate-300 border-2 border-slate-100'"
                                         class="w-10 h-10 rounded-full flex items-center justify-center text-[10px] font-black transition-all">
                                        <i x-show="selectedOrder?.Order_Stat === step" data-lucide="check" class="w-4 h-4"></i>
                                        <span x-show="selectedOrder?.Order_Stat !== step" x-text="step[0]"></span>
                                    </div>
                                    <span class="text-[8px] font-black uppercase text-slate-400" x-text="step"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-8 border-t border-b border-slate-50 py-8">
                            <div>
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Payment Mode</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                    </div>
                                    <span class="font-bold text-slate-800 text-sm" x-text="selectedOrder?.Pay_Method || 'Cash'"></span>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Rider Information</p>
                                <template x-if="selectedOrder?.Rider_FName">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
                                            <i data-lucide="bike" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 text-sm block" x-text="selectedOrder?.Rider_FName + ' ' + selectedOrder?.Rider_LName"></span>
                                            <span class="text-[10px] text-slate-400 font-bold" x-text="selectedOrder?.Rider_MobileNum"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedOrder?.Rider_FName">
                                    <p class="text-xs text-slate-400 italic">Finding nearest rider...</p>
                                </template>
                            </div>
                        </div>

                        <!-- Timing Details -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-800">Timeline</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-600">
                                    <span class="flex items-center gap-2"><i data-lucide="clock" class="w-3 h-3"></i> Pickup Time</span>
                                    <span x-text="selectedOrder?.Dlvry_Pickup_Time ? new Date(selectedOrder.Dlvry_Pickup_Time).toLocaleTimeString() : 'N/A'"></span>
                                </div>
                                <div class="flex items-center justify-between text-xs font-bold text-slate-600">
                                    <span class="flex items-center gap-2"><i data-lucide="map-pin" class="w-3 h-3"></i> Arrival Time</span>
                                    <span x-text="selectedOrder?.Dlvry_Arrival_Time ? new Date(selectedOrder.Dlvry_Arrival_Time).toLocaleTimeString() : 'In Transit'"></span>
                                </div>
                            </div>
                        </div>

                        <button @click="showTrackingModal = false" class="w-full bg-[#fcfbf7] text-slate-400 font-black py-4 rounded-2xl hover:bg-slate-100 transition-colors uppercase tracking-widest text-xs">Close Details</button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'promos'" x-cloak>
            <div class="mb-8">
                <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Active Promos</h2>
                <p class="text-slate-500 text-sm">Exclusive deals just for you!</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-[#006738] rounded-[2.5rem] p-8 text-white relative overflow-hidden group">
                    <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-white/10 rounded-full group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <span class="bg-[#ffec00] text-black text-[10px] font-black uppercase px-3 py-1 rounded-full mb-4 inline-block">Flash Deal</span>
                            <h3 class="text-3xl font-black font-poppins mb-2">P50 OFF</h3>
                            <p class="text-green-100 font-black">On all Family Fiesta meals!</p>
                        </div>
                        <div class="mt-8">
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-50 mb-2">Code: INASAL50</p>
                            <button class="bg-white text-[#006738] font-black px-6 py-2 rounded-xl text-xs uppercase tracking-widest hover:scale-105 transition-transform">Apply Now</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] border-2 border-slate-100 p-8 relative overflow-hidden group">
                    <div class="absolute inset-0 grayscale opacity-20 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-500">
                        <img src="https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover">
                    </div>
                    <div class="relative z-10 bg-white/90 backdrop-blur-sm p-6 rounded-3xl h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 font-poppins">Bigger & Juicier Pork BBQ</h3>
                            <p class="text-slate-500 text-sm mt-1">Try our improved marinade today!</p>
                        </div>
                        <div class="mt-8 flex justify-between items-center">
                            <span class="text-[#006738] font-black text-2xl">₱20 OFF</span>
                            <button @click="activeTab = 'order_now'; selectedCategory = 'Pork BBQ'" class="bg-[#006738] text-white font-black px-6 py-2 rounded-xl text-xs uppercase tracking-widest">Order Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'profile'" x-cloak>
            <div class="flex justify-between items-center mb-8">
                <div>
                   <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Account Profile</h2>
                   <p class="text-slate-500 text-sm">Manage your personal information and addresses.</p>
                </div>
                <button @click="openEditProfile()" class="flex items-center gap-2 bg-[#006738] text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-[#004d2a] transition-all">
                    <i data-lucide="user-cog" class="w-4 h-4"></i>
                    <span>Edit Profile</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Personal Info -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm transition-all h-fit">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-16 h-16 bg-green-50 text-[#006738] rounded-full flex items-center justify-center shadow-inner">
                            <i data-lucide="user" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-xl font-poppins" x-text="`${profileData.fname} ${profileData.lname}`"></h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Registered Customer</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 ml-1 mb-1 block">First Name</label>
                                <div class="bg-slate-50 p-4 rounded-2xl font-bold text-slate-600 text-sm" x-text="profileData.fname"></div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase text-slate-400 ml-1 mb-1 block">Last Name</label>
                                <div class="bg-slate-50 p-4 rounded-2xl font-bold text-slate-600 text-sm" x-text="profileData.lname"></div>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1 mb-1 block">Email Address</label>
                            <div class="bg-slate-50 p-4 rounded-2xl font-bold text-slate-600 text-sm" x-text="profileData.email"></div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1 mb-1 block">Mobile Number</label>
                            <div class="bg-slate-50 p-4 rounded-2xl font-bold text-slate-600 text-sm" x-text="profileData.mobile"></div>
                        </div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="bg-white p-8 rounded-[2.5rem] border-2 border-slate-50 shadow-sm transition-all h-fit">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-black text-slate-800 text-xl font-poppins flex items-center gap-2">
                            <i data-lucide="map-pinned" class="w-5 h-5 text-[#006738]"></i> Saved Addresses
                        </h3>
                        <button @click="showAddressModal = true" class="text-[10px] font-black uppercase bg-green-50 text-[#006738] px-3 py-1.5 rounded-lg hover:bg-green-100 transition-colors">
                            + Add New
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="addr in addresses" :key="addr.Add_ID">
                            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-start group">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-[10px] font-black uppercase bg-[#006738] text-white px-2 py-0.5 rounded shadow-sm" x-text="addr.Add_Label"></span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 leading-tight" x-text="`${addr.Add_Street}, ${addr.Add_Brgy || ''}`"></p>
                                    <p class="text-xs text-slate-500 mt-1" x-text="`${addr.Add_City}, ${addr.Add_Province} ${addr.Add_PostalCode || ''}`"></p>
                                    <p x-show="addr.Add_Building || addr.Add_UnitNum" class="text-[10px] text-slate-400 mt-1" x-text="`${addr.Add_UnitNum || ''} ${addr.Add_Building || ''}`"></p>
                                    <div x-show="addr.Add_Landmark" class="mt-2 flex items-center gap-1 text-[10px] text-orange-500 italic">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <span x-text="addr.Add_Landmark"></span>
                                    </div>
                                </div>
                                <button @click="deleteAddress(addr.Add_ID)" class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="addresses.length === 0">
                            <div class="py-12 text-center text-slate-400 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-100">
                                <i data-lucide="map" class="w-8 h-8 mx-auto mb-2 opacity-20"></i>
                                <p class="italic text-sm">No saved addresses yet.</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Branch Manager Modals -->
        <?php if ($role === 'Branch Manager' || $role === 'System Admin'): ?>
        <div x-show="showStaffModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showStaffModal = false">
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

        <div x-show="showRiderModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showRiderModal = false">
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

        <!-- Edit Profile Modal -->
        <div x-show="showProfileModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showProfileModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins">Edit Your Profile</h3>
                    <button @click="showProfileModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">First Name</label>
                            <input type="text" x-model="tempProfile.fname" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Last Name</label>
                            <input type="text" x-model="tempProfile.lname" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Mobile Number</label>
                        <input type="text" x-model="tempProfile.mobile" placeholder="09xxxxxxxxx" maxlength="11" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none">
                    </div>
                    <button @click="submitProfileUpdate()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- Add Address Modal -->
        <div x-show="showAddressModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden" @click.away="showAddressModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center border-b border-white/10">
                    <div>
                        <h3 class="font-black text-xl font-poppins">Add Delivery Address</h3>
                        <p class="text-[10px] text-green-100 uppercase tracking-widest mt-1">Specify where we should drop your grill fix!</p>
                    </div>
                    <button @click="showAddressModal = false"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-8 max-h-[80vh] overflow-y-auto space-y-6">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Address Label</label>
                        <div class="grid grid-cols-4 gap-2 mt-2">
                            <template x-for="l in ['Home', 'Work', 'Office', 'Other']">
                                <button @click="newAddress.label = l" 
                                        :class="newAddress.label === l ? 'bg-[#006738] text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                        class="py-2.5 rounded-xl text-xs font-black transition-all" x-text="l"></button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Province</label>
                            <input type="text" x-model="newAddress.province" placeholder="Metro Manila" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">City / Municipality</label>
                            <input type="text" x-model="newAddress.city" placeholder="Quezon City" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Barangay</label>
                            <input type="text" x-model="newAddress.brgy" placeholder="Brgy. 123" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Postal Code</label>
                            <input type="text" x-model="newAddress.postal" placeholder="1100" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Street / House No.</label>
                        <input type="text" x-model="newAddress.street" placeholder="123 Malakas St." class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Unit / Floor (Optional)</label>
                            <input type="text" x-model="newAddress.unit" placeholder="Unit 4B" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Building (Optional)</label>
                            <input type="text" x-model="newAddress.building" placeholder="Inasal Heights" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Landmark (Optional)</label>
                        <input type="text" x-model="newAddress.landmark" placeholder="Near the yellow gate" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-3 px-4 outline-none text-sm font-bold">
                    </div>

                    <button @click="submitAddress()" class="w-full bg-[#ffec00] text-black font-black py-5 rounded-2xl shadow-xl shadow-yellow-500/10 hover:scale-[1.02] transition-all mt-4">Save Delivery Address</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Branch Selection Overlay for Customers -->
        <div x-show="role === 'Customer' && !selectedBranch" 
             class="fixed inset-0 bg-[#006738] z-[100] flex flex-col items-center justify-center p-6"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-cloak>
            
            <div class="max-w-4xl w-full text-center">
                <div class="bg-white p-2 rounded-2xl w-24 h-24 mx-auto mb-8 shadow-2xl relative">
                    <img src="logo.png" class="w-full h-full object-contain" alt="Mang Inasal">
                    <div class="absolute -bottom-2 -right-2 bg-[#ffec00] text-black text-[10px] font-black px-2 py-1 rounded-lg shadow-lg rotate-12">NEW</div>
                </div>
                
                <h2 class="text-4xl md:text-6xl font-black text-white font-poppins mb-4 tracking-tight">KUNG SAAN ANG SARAP!</h2>
                <p class="text-white/80 text-lg md:text-xl font-bold mb-12">Please select a branch to view our menu and start your order.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[50vh] overflow-y-auto no-scrollbar p-4">
                    <template x-for="branch in customerBranches" :key="branch.Brnch_ID">
                        <div @click="selectBranch(branch.Brnch_ID); activeTab = 'order_now'" 
                             class="bg-white/10 backdrop-blur-md border-2 border-white/20 p-8 rounded-[2.5rem] hover:bg-white hover:border-[#ffec00] transition-all duration-300 cursor-pointer group text-left relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/5 rounded-full group-hover:scale-150 transition-transform"></div>
                            <div class="w-12 h-12 bg-[#ffec00] text-black rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg relative z-10">
                                <i data-lucide="store" class="w-6 h-6"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="text-xl font-black text-white group-hover:text-slate-800 font-poppins mb-2 transition-colors" x-text="branch.Brnch_Name"></h3>
                                <div class="flex items-center gap-2 text-white/60 group-hover:text-slate-500 text-sm font-medium transition-colors">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                                    <span x-text="branch.Brnch_City"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-12 text-white/40 text-xs font-black uppercase tracking-[0.2em]">
                    Authentic Filipino Grilled Chicken
                </div>
            </div>
        </div>

        <!-- Messages Toast -->
        <div x-show="message" x-transition x-cloak class="fixed bottom-8 right-8 z-[200]">
            <div :class="message?.success ? 'bg-green-600' : 'bg-red-600'" class="text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
                <i :data-lucide="message?.success ? 'check-circle' : 'alert-circle'" class="w-6 h-6"></i>
                <span x-text="message?.text" class="font-bold"></span>
                <button @click="message = null" class="ml-4 opacity-70 hover:opacity-100"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-[#006738] -mx-4 sm:-mx-6 lg:-mx-8 mt-12 pt-12 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-4">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">About Us</a>
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">FAQs</a>
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">Contact Us</a>
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">Terms and Conditions</a>
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">Corporate Information</a>
                        <a href="#" class="text-white text-sm font-bold hover:text-[#ffec00] transition-colors">Privacy Notice</a>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Google_Play_Store_badge_EN.svg/1200px-Google_Play_Store_badge_EN.svg.png" class="h-10 cursor-pointer rounded-lg">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/3c/Download_on_the_App_Store_Badge.svg/1200px-Download_on_the_App_Store_Badge.svg.png" class="h-10 cursor-pointer rounded-lg">
                    </div>
                </div>

                <p class="text-white text-[11px] font-medium mb-8">DTI Trustmark Application submitted with Reference No. 250922-111194080, pending approval</p>
                
                <div class="h-px bg-white/20 w-full mb-8"></div>

                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <p class="text-white text-xs font-medium">Copyright © 2025 - 2026. Mang Inasal Philippines, Inc. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-[#006738] hover:bg-[#ffec00] transition-all">
                            <i data-lucide="facebook" class="w-4 h-4 fill-current"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-[#006738] hover:bg-[#ffec00] transition-all">
                            <i data-lucide="instagram" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-[#006738] hover:bg-[#ffec00] transition-all">
                            <i data-lucide="youtube" class="w-4 h-4 fill-current"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
