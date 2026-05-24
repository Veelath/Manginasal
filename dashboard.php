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
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
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
    newMenu: { name: '', desc: '', price: 0, category: 'Chicken', size: 'Standard', image: '' },
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
        { title: 'Juicy & Lami na Grilled Chicken!', subtitle: 'HULING LASAP!', image: 'hero1.jpg', color: '#006738' },
        { title: 'Experience Authentic Pinoy Flavors!', subtitle: 'UNLI-RICE EXPERIENCE', image: 'hero2.jpg', color: '#ffec00' },
        { title: 'Share the Joy with Fiesta Meals!', subtitle: 'SHARE THE GRILL', image: 'hero3.jpg', color: '#006738' }
    ],
    categories: [
        { name: 'Must Try!', icon: 'star', image: 'must-try.jpg', db: 'Chicken' },
        { name: 'Chicken Inasal', icon: 'flame', image: 'hero1.jpg', db: 'Chicken' },
        { name: 'Pork BBQ', icon: 'drumstick', image: 'pork.jpg', db: 'Pork' },
        { name: 'Family Fiesta', icon: 'users', image: 'fiesta.jpg', db: 'Family Fiesta' },
        { name: 'Buddy Fiesta', icon: 'user-2', image: 'fiesta.jpg', db: 'Buddy Fiesta' },
        { name: 'Halo-Halo', icon: 'ice-cream', image: 'hal.jpg', db: 'Dessert' },
        { name: 'Palabok', icon: 'soup', image: 'pal.jpg', db: 'Palabok' }
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
    showEditBranchModal: false,
    showEditMenuModal: false,
    showEditStaffModal: false,
    showEditRiderModal: false,
    showEditManagerModal: false,
    showCartTray: false,
    showMenuOptionsModal: false,
    selectedMenuItemForOptions: null,
    menuOptions: { size: 'Small', addonPrice: 0 },
    editingBranch: null,
    editingMenu: null,
    editingStaff: null,
    editingRider: null,
    editingManager: null,
    tempProfile: { fname: '', lname: '', mobile: '' },
    newAddress: { label: 'Home', province: 'Metro Manila', city: '', brgy: '', street: '', unit: '', building: '', landmark: '', postal: '' },
    message: null,
    showAddressPicker: false,
    selectedAddressId: null,
    etaInput: '',
    editEtaOrderId: null,
    riderHistory: [],
    kitchenSubTab: 'active',
    riderSubTab: 'active',
    showNotificationsDropdown: false,

    getNotifications() {
        if (this.role === 'Customer') {
            return (this.customerOrders || []).map(o => ({
                id: 'order_' + o.Order_ID + '_' + o.Order_Stat,
                title: 'Order #' + o.Order_ID + ' Update',
                body: o.Order_Stat === 'Pending' ? 'Your order is currently pending approval.' :
                      o.Order_Stat === 'Preparing' ? 'Our kitchen started grilling your food! Nuot sa buto sarap!' :
                      o.Order_Stat === 'Ready' ? 'Your order is hot and ready! Assigning a delivery rider now.' :
                      o.Order_Stat === 'Delivering' ? 'Our rider is delivering your order! Prepare your exact cash/payment.' :
                      o.Order_Stat === 'Completed' ? 'Order delivered successfully. Salamat sa pag-order!' : 'Order status: ' + o.Order_Stat,
                time: o.Order_Date ? new Date(o.Order_Date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Just now',
                unread: ['Pending', 'Preparing', 'Ready', 'Delivering'].includes(o.Order_Stat),
                action: () => { this.selectedOrder = o; this.activeTab = 'customer_orders'; this.showNotificationsDropdown = false; setTimeout(() => this.$nextTick(() => lucide.createIcons()), 100); }
            }));
        }
        else if (this.role === 'Branch Manager' || this.role === 'Kitchen Staff') {
            return (this.orders || []).slice(0, 15).map(o => ({
                id: 'b_order_' + o.Order_ID + '_' + o.Order_Stat,
                title: 'Order #' + o.Order_ID + ' (' + o.Order_Stat + ')',
                body: o.Order_Stat === 'Pending' ? 'New incoming order is waiting for kitchen approval!' :
                      o.Order_Stat === 'Preparing' ? 'Currently grilling that tasty chicken in the kitchen.' :
                      o.Order_Stat === 'Ready' ? 'Food cooked! Waiting to assign a rider.' : 'Out with delivery rider.',
                time: o.Order_Date ? new Date(o.Order_Date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Just now',
                unread: o.Order_Stat === 'Pending',
                action: () => { this.activeTab = 'orders'; this.showNotificationsDropdown = false; }
            }));
        }
        else if (this.role === 'Driver') {
            return (this.riderDeliveries || []).map(o => ({
                id: 'drv_order_' + o.Order_ID + '_' + o.Order_Stat,
                title: 'Active Delivery - Order #' + o.Order_ID,
                body: 'Deliver to: ' + o.Add_StreetName + ', ' + o.Add_City + '. ETA: ' + (o.Dlvry_Current_ETA ? new Date(o.Dlvry_Current_ETA).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Not set yet'),
                time: o.Order_Date ? new Date(o.Order_Date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Just now',
                unread: true,
                action: () => { this.activeTab = 'deliveries'; this.showNotificationsDropdown = false; }
            }));
        }
        else {
            return [
                {
                    id: 'admin_1',
                    title: 'System Online',
                    body: 'Mang Inasal Admin portal is operational. Monitoring active branches.',
                    time: 'Just now',
                    unread: false,
                    action: () => { this.activeTab = 'overview'; this.showNotificationsDropdown = false; }
                }
            ];
        }
    },

    compressAndResizeImage(file, maxDim = 500, quality = 0.8) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = (event) => {
                const img = new Image();
                img.src = event.target.result;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > height) {
                        if (width > maxDim) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        }
                    } else {
                        if (height > maxDim) {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    resolve(canvas.toDataURL('image/jpeg', quality));
                };
            };
        });
    },

    closeAllModals() {
        this.showBranchModal = false;
        this.showManagerModal = false;
        this.showMenuModal = false;
        this.showStaffModal = false;
        this.showRiderModal = false;
        this.showProfileModal = false;
        this.showAddressModal = false;
        this.showEditBranchModal = false;
        this.showEditMenuModal = false;
        this.showEditStaffModal = false;
        this.showEditRiderModal = false;
        this.showEditManagerModal = false;
    },

    init() {
        // Intercept all fetch requests to automatically retrieve and store Firebase/Firestore status information
        const originalFetch = window.fetch;
        const self = this;
        window.fetch = async function(...args) {
            const res = await originalFetch(...args);
            try {
                const clone = res.clone();
                const data = await clone.json();
                if (data && data.firebase_status) {
                    window.__lastFirebaseStatus = data.firebase_status;
                    if (self.message && typeof self.message === 'object') {
                        self.message.firebase_status = data.firebase_status;
                    }
                }
            } catch(e) {}
            return res;
        };

        // Attach watcher on dynamic toasts to auto-enrich actions with Firebase credentials status
        this.$watch('message', (newVal) => {
            if (newVal && typeof newVal === 'object' && !newVal.firebase_status && window.__lastFirebaseStatus) {
                newVal.firebase_status = window.__lastFirebaseStatus;
            }
        });

        this.fetchProfile();
        this.$watch('selectedCategory', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('currentCarousel', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('activeTab', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('showCartTray', () => this.$nextTick(() => lucide.createIcons()));
        this.$watch('showMenuOptionsModal', () => this.$nextTick(() => lucide.createIcons()));
        
        if(this.role === 'System Admin') {
            this.fetchBranches();
            this.fetchMenu();
            this.fetchStats();
            this.fetchManagers();
            this.fetchStaff();
            this.fetchRiders();
            this.fetchAllUsers();
            this.fetchReports();

            // Real-time stats update
            setInterval(() => {
                this.fetchStats();
                this.fetchReports();
            }, 10000); // 10 seconds for admin
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

            // Real-time stats update for branch
            setInterval(() => {
                this.fetchBranchStats();
                this.fetchOrders();
            }, 10000); // 10 seconds
        }
        if(this.role === 'Customer') {
            this.fetchCustomerBranches();
            this.fetchCustomerOrders();
            if (!this.selectedBranch) {
                this.activeTab = 'order_now';
            }
            this.showBranchPicker = !this.selectedBranch;

            // Real-time update for customer orders
            setInterval(() => {
                this.fetchCustomerOrders();
            }, 15000);
        }
        if(this.role === 'Driver') {
            this.fetchRiderDeliveries();
            this.fetchRiderHistory();
            // Real-time update for driver
            setInterval(() => {
                this.fetchRiderDeliveries();
            }, 10000);
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
        if(data.success) {
            this.customerBranches = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
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
        if (item.Stock_Qty !== undefined && item.Stock_Qty !== null) {
            const qtyInCart = this.cart.filter(i => i.Menu_ID === item.Menu_ID).reduce((acc, curr) => acc + curr.qty, 0);
            if (qtyInCart >= item.Stock_Qty) {
                this.message = { success: false, text: 'Sorry, branch inventory for this item is depleted.' };
                return;
            }
        }
        // Simple logic for categories that need options
        const needsOptions = item.Menu_Category === 'Dessert' || item.Menu_Name.toLowerCase().includes('drink') || item.Menu_Name.toLowerCase().includes('halo');
        
        if (needsOptions && !this.selectedMenuItemForOptions) {
            this.selectedMenuItemForOptions = { ...item };
            this.menuOptions = { size: 'Small', addonPrice: 0 };
            this.showMenuOptionsModal = true;
            return;
        }

        const price = parseFloat(item.Menu_Price) + (this.menuOptions?.addonPrice || 0);
        const name = this.menuOptions?.size ? `${item.Menu_Name} (${this.menuOptions.size})` : item.Menu_Name;
        
        // Use a composite key for cart items that have options
        const cartKey = `${item.Menu_ID}-${this.menuOptions?.size || 'Standard'}`;

        const existing = this.cart.find(i => i.cartKey === cartKey);
        if(existing) {
            existing.qty++;
        } else {
            this.cart.push({ 
                ...item, 
                Menu_Name: name,
                Menu_Price: price,
                qty: 1, 
                cartKey: cartKey,
                selectedSize: this.menuOptions?.size || null
            });
        }
        
        // Reset options
        this.selectedMenuItemForOptions = null;
        this.showMenuOptionsModal = false;
        this.menuOptions = { size: 'Small', addonPrice: 0 };
    },

    confirmOptions() {
        if (this.selectedMenuItemForOptions) {
            this.addToCart(this.selectedMenuItemForOptions);
        }
    },

    get cartTotal() {
        return this.cart.reduce((sum, item) => sum + (item.Menu_Price * item.qty), 0);
    },

    get isCartValid() {
        if (!this.cart || this.cart.length === 0) return false;
        if (this.orderType === 'Delivery') {
            if (this.cartTotal < 200) return false;
            if (!this.manualAddress.street || !this.manualAddress.brgy || !this.manualAddress.city) return false;
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
                    address_id: this.useCurrentAddress ? this.selectedAddressId : null,
                    address: this.useCurrentAddress ? null : {
                        ...this.manualAddress,
                        city: this.manualAddress.city || 'Standard Entry',
                        brgy: this.manualAddress.brgy || 'Standard Entry',
                        province: this.manualAddress.province || 'Metro Manila'
                    }
                })
            });
            const data = await res.json();
            this.message = { success: data.success, text: data.message };
            if(data.success) {
                this.cart = [];
                this.activeTab = 'customer_orders';
                this.showCartTray = false; // Close tray on success
                await this.fetchCustomerOrders();
                
                // Show tracking modal for the new order
                if (this.customerOrders.length > 0) {
                    this.selectedOrder = this.customerOrders[0];
                    this.showTrackingModal = true;
                }

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

    async fetchRiderHistory() {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_rider_history' })
        });
        const data = await res.json();
        if(data.success) {
            this.riderHistory = data.data;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async updateEta() {
        if(!this.editEtaOrderId || !this.etaInput) return;
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_eta', order_id: this.editEtaOrderId, eta: this.etaInput })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchRiderDeliveries();
            this.editEtaOrderId = null;
            this.etaInput = '';
        }
    },

    async finishDelivery(orderId) {
        const res = await fetch('orders_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'complete_delivery', order_id: orderId })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchRiderDeliveries();
            this.fetchRiderHistory();
        }
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
            this.stats = { ...this.stats, ...data.stats };
            
            // Fallback: if API returns 0 but local workforce has people, assume API is lagging or filtering differently
            if (this.stats.staff === 0 && this.workforce.some(p => p.source === 'Staff')) {
                this.stats.staff = this.workforce.filter(p => p.source === 'Staff').length;
            }
            if (this.stats.riders === 0 && this.workforce.some(p => p.source === 'Rider')) {
                this.stats.riders = this.workforce.filter(p => (p.source === 'Rider' || p.role === 'Driver')).length;
            }
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

    async updateStock(menuId, stockQty) {
        if (stockQty === undefined || stockQty === null || isNaN(stockQty) || stockQty < 0) {
            stockQty = 0;
        }
        await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_stock', menu_id: menuId, stock: stockQty })
        });
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
        const api = (this.role === 'System Admin') ? 'system_admin_api.php' : 'branch_manager_api.php';
        const res = await fetch(api, {
            method: 'POST',
            body: JSON.stringify({ action: 'create_menu', ...this.newMenu })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            if (this.role === 'System Admin') {
                this.fetchMenu();
            } else {
                this.fetchBranchMenu();
            }
            this.showMenuModal = false;
            this.newMenu = { name: '', desc: '', price: 0, category: 'Chicken', size: 'Standard', image: '' };
        }
    },

    async deleteMenu(id) {
        if(!confirm('Are you sure you want to delete this global menu item?')) return;
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_menu', id })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.fetchMenu();
    },

    async deleteBranchMenuItem(id) {
        if(!confirm('Are you sure you want to delete this branch menu item?')) return;
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_menu', id })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) this.fetchBranchMenu();
    },

    async updateMenuStatus(id, status) {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_menu_status', id, status })
        });
        const data = await res.json();
        if(data.success) this.fetchMenu();
    },

    async submitManager(stayOpen = false) {
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
            this.newManager = { fname: '', lname: '', email: '', mobile: '', branch_id: '', password: '' };
            if (!stayOpen) {
                this.showManagerModal = false;
            }
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

    openEditBranch(branch) { this.editingBranch = { ...branch }; this.showEditBranchModal = true; },
    async submitEditBranch() {
        const api = (this.role === 'System Admin') ? 'system_admin_api.php' : 'branch_manager_api.php';
        const res = await fetch(api, {
            method: 'POST',
            body: JSON.stringify({ action: 'update_branch', ...this.editingBranch })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            if (this.role === 'System Admin') {
                this.fetchBranches();
            } else {
                this.fetchBranchInfo();
            }
            this.showEditBranchModal = false;
        }
    },

    openEditMenu(item) { this.editingMenu = { ...item }; this.showEditMenuModal = true; },
    async submitEditMenu() {
        const api = (this.role === 'System Admin') ? 'system_admin_api.php' : 'branch_manager_api.php';
        const res = await fetch(api, {
            method: 'POST',
            body: JSON.stringify({ action: 'update_menu', ...this.editingMenu })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            if (this.role === 'System Admin') {
                this.fetchMenu();
            } else {
                this.fetchBranchMenu();
            }
            this.showEditMenuModal = false;
        }
    },

    openEditManager(manager) { this.editingManager = { ...manager }; this.showEditManagerModal = true; },
    async submitEditManager() {
        const res = await fetch('system_admin_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_manager', ...this.editingManager })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchManagers();
            this.fetchBranches();
            this.showEditManagerModal = false;
        }
    },

    openEditStaff(staff) { this.editingStaff = { ...staff }; this.showEditStaffModal = true; },
    async submitEditStaff() {
        const api = (this.role === 'System Admin') ? 'system_admin_api.php' : 'branch_manager_api.php';
        const res = await fetch(api, {
            method: 'POST',
            body: JSON.stringify({ action: 'update_staff', ...this.editingStaff })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            if (this.role === 'System Admin') {
                this.fetchStaff();
                this.fetchAllUsers();
            } else {
                this.fetchWorkforce();
                this.fetchBranchStats();
            }
            this.showEditStaffModal = false;
        }
    },

    openEditRider(rider) { this.editingRider = { ...rider }; this.showEditRiderModal = true; },
    async submitEditRider() {
        const api = (this.role === 'System Admin') ? 'system_admin_api.php' : 'branch_manager_api.php';
        const res = await fetch(api, {
            method: 'POST',
            body: JSON.stringify({ action: 'update_rider', ...this.editingRider })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            if (this.role === 'System Admin') {
                this.fetchRiders();
                this.fetchAllUsers();
            } else {
                this.fetchWorkforce();
                this.fetchBranchStats();
            }
            this.showEditRiderModal = false;
        }
    },

    async submitStaff(stayOpen = false) {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_staff', ...this.newStaff })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchWorkforce();
            this.fetchBranchStats();
            this.newStaff = { fname: '', lname: '', email: '', mobile: '', role: 'Kitchen Staff', password: '' };
            if (!stayOpen) {
                this.showStaffModal = false;
            }
        }
    },

    async submitRider(stayOpen = false) {
        const res = await fetch('branch_manager_api.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'create_rider', ...this.newRider })
        });
        const data = await res.json();
        this.message = { success: data.success, text: data.message };
        if(data.success) {
            this.fetchWorkforce();
            this.fetchBranchStats();
            this.newRider = { fname: '', lname: '', email: '', mobile: '', password: '' };
            if (!stayOpen) {
                this.showRiderModal = false;
            }
        }
    }
}">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-[#006738] text-white transition-all duration-300 z-50 overflow-hidden hidden md:flex flex-col">
        <div class="p-6 flex items-center gap-3">
            <!-- Sidebar Logo styled like index.php -->
            <div class="relative group cursor-pointer" @click="activeTab = 'overview'">
                <div class="inline-block bg-[#ffec00] p-1.5 sm:p-2 border-[2px] sm:border-[2.5px] border-black rounded-sm shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform group-hover:scale-105">
                    <div class="text-black font-poppins font-black leading-none tracking-tighter">
                        <span x-show="sidebarOpen" class="block text-[6px] sm:text-[8px] uppercase italic text-black">Mang</span>
                        <span class="block text-sm sm:text-xl uppercase text-[#ed1c24] -mt-0.5" :class="!sidebarOpen ? 'text-lg' : ''">Inasal</span>
                    </div>
                </div>
            </div>
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
                <a href="#" @click="activeTab = 'menu'; fetchMenu()" 
                   :class="activeTab === 'menu' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="cooking-pot" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Store Menus</span>
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
                    <span x-show="sidebarOpen" class="font-bold text-sm">Delivery Riders</span>
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
                <a href="#" @click="activeTab = 'profile'" 
                   :class="activeTab === 'profile' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Profile</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'Customer'): ?>
                <a href="#" @click="activeTab = 'order_now'" 
                   :class="activeTab === 'order_now' ? 'bg-[#ffec00] text-black shadow-lg shadow-yellow-500/10' : 'text-white/70 hover:bg-white/10 hover:text-white'" 
                   class="flex items-center gap-3 p-3 rounded-xl transition-all">
                    <i data-lucide="utensils" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" class="font-bold text-sm">Order Now</span>
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
                                <span class="text-sm font-bold" x-text="currentBranch ? currentBranch.Brnch_Name : 'Branch'"></span>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-500">Welcome back, we're ready to grill!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4 relative" @click.outside="showNotificationsDropdown = false">
                <!-- Notifications Bell wrapper -->
                <div class="relative">
                    <button @click="showNotificationsDropdown = !showNotificationsDropdown" class="p-2 bg-white rounded-xl shadow-sm border text-slate-400 hover:text-[#006738] relative transition-all active:scale-95 cursor-pointer flex items-center justify-center">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <!-- Unread alert bubble badge -->
                        <template x-if="getNotifications().filter(n => n.unread).length > 0">
                            <span class="absolute -top-1 -right-1 bg-[#ed1c24] text-white text-[9px] w-4.5 h-4.5 rounded-full flex items-center justify-center font-black animate-pulse" x-text="getNotifications().filter(n => n.unread).length"></span>
                        </template>
                    </button>

                    <!-- Notifications Dropdown Panel -->
                    <div x-show="showNotificationsDropdown" 
                         x-transition 
                         x-cloak 
                         class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 z-[150] overflow-hidden max-h-96 flex flex-col">
                        <div class="p-4 border-b border-slate-50 flex items-center justify-between bg-slate-50">
                            <span class="font-black text-slate-800 text-sm tracking-tight font-poppins">Notifications</span>
                            <template x-if="getNotifications().length > 0">
                                <span class="text-[10px] font-black uppercase text-[#006738] bg-green-50 px-2.5 py-1 rounded-full" x-text="getNotifications().length + ' Total'"></span>
                            </template>
                        </div>
                        <div class="overflow-y-auto divide-y divide-slate-50 flex-grow max-h-72">
                            <template x-for="notif in getNotifications()" :key="notif.id">
                                <div @click="notif.action()" class="p-4 hover:bg-slate-50 cursor-pointer transition-colors flex items-start gap-3 text-left">
                                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="notif.unread ? 'bg-[#ed1c24]' : 'bg-slate-300'"></div>
                                    <div class="flex-grow space-y-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <h4 class="font-bold text-slate-800 text-xs tracking-tight" x-text="notif.title"></h4>
                                            <span class="text-[9px] font-semibold text-slate-400 shrink-0" x-text="notif.time"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 leading-normal" x-text="notif.body"></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="getNotifications().length === 0">
                                <div class="p-8 text-center text-slate-400 space-y-2">
                                    <i data-lucide="bell-off" class="w-8 h-8 mx-auto text-slate-300"></i>
                                    <p class="text-xs font-bold leading-none">No notifications yet.</p>
                                    <p class="text-[10px] text-slate-400">Updates about orders or activities will appear here.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="w-10 h-10 bg-[#006738] rounded-xl flex items-center justify-center text-white font-bold">
                    <?php echo substr($role, 0, 1); ?>
                </div>
            </div>
        </header>

        

        <!-- Dynamic Role Content -->
        <div x-show="activeTab === 'overview' && role !== 'Customer'" class="space-y-8">
            <!-- Stats Grid - Moved here from above header as requested -->
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
                        <p class="text-4xl font-black text-slate-800" x-text="workforce.filter(p => p.source === 'Staff').length"></p>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden group">
                        <div class="w-14 h-14 bg-yellow-50 text-yellow-600 rounded-2xl flex items-center justify-center mb-6">
                            <i data-lucide="bike" class="w-7 h-7 font-bold"></i>
                        </div>
                        <h3 class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Delivery Riders</h3>
                        <p class="text-4xl font-black text-slate-800" x-text="workforce.filter(p => p.source === 'Rider' || p.role === 'Driver').length"></p>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-md transition-all relative overflow-hidden col-span-1 lg:col-span-2">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-7 h-7"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-1 tracking-widest">Today's Sales</p>
                                <h3 class="text-2xl font-black text-emerald-600" x-text="'₱' + parseFloat(stats.dailySales || 0).toLocaleString()"></h3>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Daily Progress</span>
                                <span class="text-xs font-bold text-slate-600" x-text="Math.round(((stats.dailySales || 0) / (stats.dailyGoal || 1)) * 100) + '% of ₱' + (stats.dailyGoal || 0).toLocaleString()"></span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 transition-all duration-1000" :style="'width: ' + Math.min(100, ((stats.dailySales || 0) / (stats.dailyGoal || 1)) * 100) + '%'"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
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
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent flex flex-col justify-end p-12">
                            <!-- Text removed by user request -->
                        </div>
                    </div>
                </template>
                
                <!-- Carousel Controls -->
                <button @click="currentCarousel = (currentCarousel - 1 + carouselItems.length) % carouselItems.length" 
                        class="absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/40 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-[#006738] transition-all z-20 shadow-lg">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button @click="currentCarousel = (currentCarousel + 1) % carouselItems.length" 
                        class="absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/40 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-[#006738] transition-all z-20 shadow-lg">
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
                                <button @click="openEditBranch(branch)" class="p-2 text-slate-400 hover:text-[#006738] hover:bg-green-50 rounded-lg transition-all">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
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
                                    <td class="p-4 text-right flex items-center justify-end gap-2">
                                        <button @click="openEditManager(manager)" class="text-slate-400 hover:text-[#006738] p-2 transition-colors">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
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
                                            <div class="px-4 py-2 mb-1 text-[10px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">Manage Account</div>
                                            <button @click="openEditManager(manager); open = false" class="w-full text-left px-4 py-3 text-xs font-bold hover:bg-blue-50 text-blue-600 flex items-center gap-3 transition-colors">
                                                <i data-lucide="user-cog" class="w-4 h-4"></i> Edit Details
                                            </button>
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
                                <td class="p-4 flex items-center justify-end gap-2">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded-full bg-green-100 text-green-700" x-text="staff.status"></span>
                                    <button @click="openEditStaff(staff)" class="text-slate-400 hover:text-[#006738] p-2 transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
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
                    <h2 class="text-xl font-black text-slate-800 font-poppins">Delivery Riders (Global)</h2>
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
                                <td class="p-4 flex items-center justify-end gap-2">
                                    <span :class="rider.status === 'Y' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-[10px] font-black uppercase px-2 py-1 rounded-full" x-text="rider.status === 'Y' ? 'Available' : 'Busy'"></span>
                                    <button @click="openEditRider(rider)" class="text-slate-400 hover:text-[#006738] p-2 transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
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
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Branch Menus Directory</h2>
                    <p class="text-slate-500 text-sm">Monitor all menu offerings across all active branches. Only Branch Managers can add or alter entries.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="fetchMenu()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Product Info</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Branch Origin</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Category</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Price</th>
                            <th class="p-4 text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="menuItems.length === 0">
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-400 italic">No menu items found.</td>
                            </tr>
                        </template>
                        <template x-for="item in menuItems" :key="item.Menu_ID">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                            <template x-if="item.Menu_Image">
                                                <img :src="item.Menu_Image" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.Menu_Image">
                                                <i data-lucide="utensils" class="w-4 h-4 text-slate-300"></i>
                                            </template>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800" x-text="item.Menu_Name"></div>
                                            <div class="text-[10px] text-[#006738] font-black uppercase tracking-tighter" x-text="item.Menu_Size"></div>
                                            <div class="text-[11px] text-slate-400 mt-1 italic line-clamp-1" x-text="item.Menu_Description"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <template x-if="item.Creator_Branch_Name">
                                        <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-orange-50 text-orange-700 border border-orange-100" x-text="item.Creator_Branch_Name"></span>
                                    </template>
                                    <template x-if="!item.Creator_Branch_Name">
                                        <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-green-50 text-green-700 border border-green-100">Core / Standard</span>
                                    </template>
                                </td>
                                <td class="p-4">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-slate-100 text-slate-600" x-text="item.Menu_Category"></span>
                                </td>
                                <td class="p-4 text-sm font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(2)"></td>
                                <td class="p-4">
                                    <span :class="item.Menu_Status === 'Y' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                          class="text-[10px] font-black uppercase px-3 py-1 rounded-full">
                                        <span x-text="item.Menu_Status === 'Y' ? 'Active' : 'Unavailable'"></span>
                                    </span>
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
                    <div class="flex flex-col gap-3">
                        <button @click="submitManager(false)" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Create Account</button>
                        <button @click="submitManager(true)" class="w-full bg-[#f1f5f1] text-[#006738] font-black py-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-sm">Save & Add Another</button>
                    </div>
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
                    <button @click="editingBranch = { ...currentBranch }; showEditBranchModal = true" class="bg-white text-slate-600 border border-slate-200 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-slate-50 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        <span>Edit Branch</span>
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
                                <td class="p-4 text-right flex items-center justify-end gap-2">
                                    <button @click="person.source === 'Staff' ? openEditStaff(person) : openEditRider(person)" class="flex items-center gap-1 text-[10px] font-black uppercase text-slate-400 hover:text-[#006738] p-2 transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        <span>Edit</span>
                                    </button>
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
                <div class="flex items-center gap-3">
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <button @click="kitchenSubTab = 'active'" :class="kitchenSubTab === 'active' ? 'bg-white shadow-sm text-[#006738]' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">Active</button>
                        <button @click="kitchenSubTab = 'history'" :class="kitchenSubTab === 'history' ? 'bg-white shadow-sm text-[#006738]' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">History</button>
                    </div>
                    <button @click="fetchOrders()" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Kitchen Stats Card -->
            <div x-show="kitchenSubTab === 'active'" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-[2rem] border-2 border-slate-50 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">New Orders</p>
                    <p class="text-2xl font-black text-orange-500" x-text="orders.filter(o => o.Order_Stat === 'Pending').length"></p>
                </div>
                <div class="bg-white p-5 rounded-[2rem] border-2 border-slate-50 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Preparing</p>
                    <p class="text-2xl font-black text-blue-500" x-text="orders.filter(o => o.Order_Stat === 'Preparing').length"></p>
                </div>
                <div class="bg-white p-5 rounded-[2rem] border-2 border-slate-50 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Ready/Pending Dispatch</p>
                    <p class="text-2xl font-black text-purple-500" x-text="orders.filter(o => o.Order_Stat === 'Ready').length"></p>
                </div>
                <div class="bg-white p-5 rounded-[2rem] border-2 border-slate-50 shadow-sm">
                    <p class="text-[10px] font-black uppercase text-slate-400 mb-1">In Delivery</p>
                    <p class="text-2xl font-black text-green-500" x-text="orders.filter(o => o.Order_Stat === 'Delivering').length"></p>
                </div>
            </div>

            <!-- Active Orders List -->
            <div x-show="kitchenSubTab === 'active'" class="space-y-4">
                <template x-if="orders.filter(o => o.Order_Stat !== 'Completed' && o.Order_Stat !== 'Cancelled').length === 0">
                    <div class="bg-white border-2 border-dashed border-slate-100 rounded-[2rem] p-12 text-center">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                            <i data-lucide="clipboard-x" class="w-10 h-10"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-400 uppercase tracking-widest">No Active Orders</h3>
                        <p class="text-slate-400 text-sm italic">New orders from customers will appear here.</p>
                    </div>
                </template>
                <template x-for="order in orders.filter(o => o.Order_Stat !== 'Completed' && o.Order_Stat !== 'Cancelled')" :key="order.Order_ID">
                    <div class="bg-white border-2 border-slate-50 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex flex-wrap justify-between items-start gap-4">
                            <!-- ... existing order card content ... -->
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
                                        <button @click="updateOrderStatus(order.Order_ID, 'Preparing')" class="p-3 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all font-bold text-xs">Start Preparing</button>
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

            <!-- Kitchen Order History -->
            <div x-show="kitchenSubTab === 'history'" class="space-y-4">
                <template x-if="orders.filter(o => o.Order_Stat === 'Completed').length === 0">
                    <div class="bg-white border-2 border-dashed border-slate-100 rounded-[2rem] p-12 text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                            <i data-lucide="history" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-400 uppercase tracking-widest">No History Yet</h3>
                    </div>
                </template>
                <div class="bg-white rounded-[2rem] border border-slate-100 overflow-hidden shadow-sm">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Order</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Customer</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Date/Time</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Method</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Amount</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <template x-for="order in orders.filter(o => o.Order_Stat === 'Completed')" :key="order.Order_ID">
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-black text-slate-800" x-text="order.Order_Code"></p>
                                        <p class="text-[10px] text-slate-400" x-text="order.Order_Type"></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-700 text-sm" x-text="`${order.Cust_FName} ${order.Cust_LName}`"></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs text-slate-600" x-text="new Date(order.Order_Date).toLocaleString()"></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-black text-[#006738] bg-green-50 px-2 py-1 rounded" x-text="order.Pay_Method"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-black text-slate-800" x-text="'₱' + parseFloat(order.Order_Total_Amount).toFixed(0)"></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button @click="fetchOrderItems(order.Order_ID)" class="p-2 text-[#006738] hover:bg-green-50 rounded-lg">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                                <template x-if="orderItems[order.Order_ID]">
                                    <tr class="bg-slate-50/30">
                                        <td colspan="6" class="px-6 py-4">
                                            <div class="space-y-1">
                                                <template x-for="item in orderItems[order.Order_ID]">
                                                    <div class="text-[10px] flex justify-between max-w-xs">
                                                        <span x-text="item.OItem_Quantity + 'x ' + item.Menu_Name"></span>
                                                        <span class="font-bold" x-text="'₱' + parseFloat(item.OItem_Unit_Price).toFixed(0)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === 'Branch Manager'): ?>
        <div x-show="activeTab === 'availability'" x-cloak>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">Branch Menu & Inventory</h2>
                    <p class="text-slate-500 text-sm">Update product availability, track remaining stock, and add branch-specific dishes.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="fetchBranchMenu()" class="p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl active:scale-95 transition-all text-slate-600">
                        <i data-lucide="refresh-cw" class="w-4.5 h-4.5"></i>
                    </button>
                    <button @click="showMenuModal = true" class="bg-[#006738] text-white px-5 py-3 rounded-2xl text-sm font-bold flex items-center gap-2 hover:bg-[#004d2a] shadow-lg shadow-green-900/10 transition-all active:scale-95">
                        <i data-lucide="plus-circle" class="w-4.5 h-4.5"></i>
                        <span>Add Menu Item</span>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <template x-for="item in menuItems" :key="item.Menu_ID">
                    <div :class="item.Is_Available === 'Y' && parseInt(item.Stock_Qty || 0) > 0 ? 'border-green-100 bg-white' : 'border-red-100 bg-red-50/10 opacity-75'" class="p-6 rounded-3xl border-2 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center text-slate-300">
                                    <template x-if="item.Menu_Image">
                                        <img :src="item.Menu_Image" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.Menu_Image">
                                        <i data-lucide="utensils" class="w-5 h-5"></i>
                                    </template>
                                </div>
                                <button @click="toggleAvailability(item.Menu_ID, item.Is_Available)" 
                                        :class="item.Is_Available === 'Y' ? 'bg-[#006738]' : 'bg-red-500'" 
                                        class="w-12 h-6 rounded-full relative transition-colors">
                                    <div :class="item.Is_Available === 'Y' ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full transition-transform"></div>
                                </button>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span x-text="item.Menu_Category" class="text-[10px] font-black uppercase text-slate-400 bg-slate-100 px-2 py-1 rounded-lg"></span>
                                <template x-if="item.Menu_Brnch_ID">
                                    <span class="text-[9px] font-black uppercase text-green-700 bg-green-100 px-2 py-1 rounded-lg">Custom</span>
                                </template>
                            </div>
                            <h3 class="font-bold text-slate-800 mb-1" x-text="item.Menu_Name"></h3>
                            <p class="text-xs text-slate-400 line-clamp-2 mb-2" x-text="item.Menu_Description || ''"></p>
                            <p class="text-lg font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(2)"></p>

                            <!-- Branch Stock & Inventory Counter -->
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase text-slate-400">Inventory Stock:</span>
                                <div class="flex items-center gap-1.5">
                                    <button @click="if(parseInt(item.Stock_Qty || 0) > 0) { item.Stock_Qty = parseInt(item.Stock_Qty) - 1; updateStock(item.Menu_ID, item.Stock_Qty); }" class="w-7 h-7 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 flex items-center justify-center font-bold cursor-pointer transition-colors">-</button>
                                    <input type="number" 
                                           x-model.number="item.Stock_Qty" 
                                           @change="if(isNaN(item.Stock_Qty) || item.Stock_Qty < 0) item.Stock_Qty = 0; updateStock(item.Menu_ID, item.Stock_Qty)" 
                                           class="w-11 text-center text-xs font-bold font-mono bg-slate-100 text-slate-800 border-0 rounded-lg p-1 outline-none">
                                    <button @click="item.Stock_Qty = parseInt(item.Stock_Qty || 0) + 1; updateStock(item.Menu_ID, item.Stock_Qty);" class="w-7 h-7 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 flex items-center justify-center font-bold cursor-pointer transition-colors">+</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions for editing and deleting the menu items -->
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">
                            <button @click="openEditMenu(item)" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-xl font-bold text-xs flex items-center justify-center gap-1 transition-all">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-600"></i>
                                <span>Edit</span>
                            </button>
                            <button @click="deleteBranchMenuItem(item.Menu_ID)" class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-bold text-xs flex items-center justify-center gap-1 transition-all">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Rider Delivery History -->
            <div x-show="riderSubTab === 'history'" class="space-y-4">
                <template x-if="riderHistory.length === 0">
                    <div class="py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                        <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="history" class="w-8 h-8"></i>
                        </div>
                        <h3 class="font-black text-slate-400 text-lg uppercase">No Delivery History</h3>
                    </div>
                </template>
                <div x-show="riderHistory.length > 0" class="bg-white rounded-[2rem] border border-slate-100 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Order</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Customer</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Address</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Arrived At</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Total</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <template x-for="delivery in riderHistory" :key="delivery.Order_ID">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 font-black" x-text="delivery.Order_Code"></td>
                                        <td class="px-6 py-4 font-bold" x-text="`${delivery.Cust_FName} ${delivery.Cust_LName}`"></td>
                                        <td class="px-6 py-4 text-xs text-slate-500" x-text="`${delivery.Add_Street}, ${delivery.Add_City}`"></td>
                                        <td class="px-6 py-4 text-xs" x-text="delivery.Dlvry_Arrival_Time ? new Date(delivery.Dlvry_Arrival_Time).toLocaleString() : 'N/A'"></td>
                                        <td class="px-6 py-4 font-black text-[#006738]" x-text="'₱' + parseFloat(delivery.Order_Total_Amount).toFixed(0)"></td>
                                        <td class="px-6 py-4">
                                            <span class="bg-green-100 text-green-700 text-[10px] font-black uppercase px-2 py-1 rounded-full">Delivered</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rider Tab -->
        <?php if ($role === 'Driver'): ?>
        <div x-show="activeTab === 'pending_deliveries'" x-cloak>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 font-poppins text-[#006738]">My Deliveries</h2>
                    <p class="text-slate-500 text-sm">Manage your routes and track history.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <button @click="riderSubTab = 'active'" :class="riderSubTab === 'active' ? 'bg-white shadow-sm text-[#006738]' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">Active</button>
                        <button @click="riderSubTab = 'history'" :class="riderSubTab === 'history' ? 'bg-white shadow-sm text-[#006738]' : 'text-slate-500'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">History</button>
                    </div>
                    <button @click="fetchRiderDeliveries(); fetchRiderHistory();" class="p-3 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Rider Stats -->
            <div x-show="riderSubTab === 'active'" class="mb-8">
                <div class="bg-[#006738] rounded-[2.5rem] p-8 text-white flex justify-between items-center shadow-xl overflow-hidden relative">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black uppercase opacity-60 tracking-widest mb-1">Active Deliveries</p>
                        <h3 class="text-4xl font-black font-poppins" x-text="riderDeliveries.length"></h3>
                    </div>
                    <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center backdrop-blur-sm">
                        <i data-lucide="navigation" class="w-10 h-10"></i>
                    </div>
                    <!-- Decorative patterns -->
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                </div>
            </div>

            <!-- Active Deliveries -->
            <div x-show="riderSubTab === 'active'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <div class="pt-4 border-t border-slate-50 space-y-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Payment Method</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-black text-[#006738]" x-text="order.Pay_Method"></p>
                                            <span :class="order.Pay_Status === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'" 
                                                  class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded" 
                                                  x-text="order.Pay_Status"></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Collect Amount</p>
                                        <p class="text-sm font-black text-slate-800" x-text="order.Pay_Method === 'Cash (COD)' ? '₱' + parseFloat(order.Pay_Amount).toFixed(0) : 'Already Paid'"></p>
                                    </div>
                                </div>

                                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-[10px] font-black uppercase text-slate-400">Current ETA</p>
                                        <p class="text-xs font-bold text-[#006738]" x-text="order.Dlvry_Current_ETA ? new Date(order.Dlvry_Current_ETA).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : 'Set ETA'"></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="time" x-model="etaInput" @click="editEtaOrderId = order.Order_ID" class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold outline-none focus:border-[#006738]">
                                        <button @click="editEtaOrderId = order.Order_ID; updateEta()" class="bg-[#ffec00] text-black px-3 py-1.5 rounded-lg text-xs font-black uppercase hover:bg-yellow-400 transition-colors">Update</button>
                                    </div>
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
            <div>
                <div class="flex-1">
                    <h3 class="text-xl font-black text-slate-800 font-poppins mb-6 pb-2 border-b-2 border-green-50" x-text="selectedCategory"></h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <template x-for="item in customerMenu.filter(i => (!searchQuery || i.Menu_Name.toLowerCase().includes(searchQuery.toLowerCase())) && (i.Menu_Category === (categories.find(c => c.name === selectedCategory)?.db || '') || selectedCategory === 'Must Try!'))" :key="item.Menu_ID">
                            <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all group overflow-hidden relative">
                                <!-- Save Badge -->
                                <div class="absolute top-4 left-4 z-10">
                                    <span class="bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg">SAVE ₱15</span>
                                </div>
                                
                                <div class="w-full aspect-square bg-slate-50 rounded-3xl mb-6 overflow-hidden flex items-center justify-center text-[#006738] relative">
                                    <template x-if="item.Menu_Image">
                                        <img :src="item.Menu_Image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    </template>
                                    <template x-if="!item.Menu_Image">
                                        <div class="flex flex-col items-center">
                                            <i data-lucide="utensils" class="w-16 h-16 opacity-5 group-hover:scale-125 transition-transform duration-500"></i>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                                            <template x-if="item.Creator_Branch_Name">
                                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-orange-50 text-orange-700 border border-orange-100 flex items-center gap-1">
                                                    <i data-lucide="map-pin" class="w-2.5 h-2.5"></i>
                                                    <span x-text="item.Creator_Branch_Name"></span>
                                                </span>
                                            </template>
                                            <template x-if="!item.Creator_Branch_Name">
                                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-green-50 text-green-700 border border-green-100 flex items-center gap-1">
                                                    <i data-lucide="shield-check" class="w-2.5 h-2.5"></i>
                                                    Core Menu
                                                </span>
                                            </template>
                                        </div>
                                        <h4 class="font-black text-slate-800 font-poppins text-lg" x-text="item.Menu_Name"></h4>
                                        <p class="text-xs text-slate-400 font-medium line-clamp-2 mt-1" x-text="item.Menu_Description"></p>
                                        <!-- Stock Level Badge -->
                                        <div class="mt-2 flex items-center">
                                            <template x-if="item.Stock_Qty !== undefined && item.Stock_Qty !== null">
                                                <span :class="item.Stock_Qty > 0 ? (item.Stock_Qty <= 10 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-green-50 text-green-700 border-green-200') : 'bg-red-50 text-red-700 border-red-200'"
                                                      class="text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full border">
                                                    <span x-text="item.Stock_Qty > 0 ? (item.Stock_Qty <= 10 ? '🔥 Limited: ' + item.Stock_Qty + ' left' : 'In Stock: ' + item.Stock_Qty) : '🚫 Out of Stock'"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-slate-300">Price Starts</p>
                                            <p class="text-xl font-black text-[#006738]" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(0)"></p>
                                        </div>
                                        <template x-if="item.Stock_Qty === undefined || item.Stock_Qty === null || item.Stock_Qty > 0">
                                            <button @click="addToCart(item)" class="w-12 h-12 bg-[#ffec00] text-black rounded-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all shadow-lg shadow-yellow-500/20">
                                                <i data-lucide="plus" class="w-6 h-6"></i>
                                            </button>
                                        </template>
                                        <template x-if="item.Stock_Qty !== undefined && item.Stock_Qty !== null && item.Stock_Qty <= 0">
                                            <span class="px-3 py-2 bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-wider rounded-xl">Sold Out</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Floating Cart Button -->
            <button @click="showCartTray = true" 
                    x-show="cart.length > 0"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-y-20 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    class="fixed bottom-10 right-10 w-20 h-20 bg-[#ffec00] rounded-full shadow-[0_20px_50px_rgba(237,28,36,0.2)] flex items-center justify-center z-50 group hover:scale-110 transition-transform active:scale-95">
                <i data-lucide="shopping-basket" class="w-8 h-8 text-black"></i>
                <div class="absolute -top-1 -right-1 bg-[#ed1c24] text-white text-[10px] font-black min-w-[24px] h-[24px] rounded-full flex items-center justify-center px-1 border-2 border-black" x-text="cart.reduce((s, i) => s + i.qty, 0)"></div>
                <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-black text-white text-[10px] font-black px-3 py-1 rounded-full whitespace-nowrap">VIEW YOUR TRAY</div>
            </button>

            <!-- Slide-over Cart Tray Overlay -->
            <div x-show="showCartTray" class="fixed inset-0 z-[60]" x-cloak>
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCartTray = false"></div>
                <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-white shadow-2xl overflow-hidden flex flex-col"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full">
                    
                    <!-- Cart Header -->
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-[#f8faf8]">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-[#ffec00] rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-500/20">
                                <i data-lucide="shopping-basket" class="w-6 h-6 text-black"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-2xl font-poppins text-slate-800">Your Tray</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="`${cart.length} unique items`"></p>
                            </div>
                        </div>
                        <button @click="showCartTray = false" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center hover:bg-white transition-colors">
                            <i data-lucide="x" class="w-5 h-5 text-slate-400"></i>
                        </button>
                    </div>

                    <!-- Cart Content -->
                    <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-5 bg-slate-50/50">
                        <!-- Food Items List -->
                        <div class="space-y-3">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="group flex gap-4 bg-white p-3.5 rounded-2xl border border-slate-100 hover:border-green-200 transition-all shadow-xs">
                                    <div class="w-14 h-14 bg-slate-50 rounded-xl flex-shrink-0 overflow-hidden flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <template x-if="item.Menu_Image">
                                            <img :src="item.Menu_Image" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.Menu_Image">
                                            <i data-lucide="utensils" class="w-6 h-6 text-slate-200"></i>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="min-w-0 col-span-1">
                                                <p class="text-sm font-black text-slate-800 truncate leading-tight" x-text="item.Menu_Name"></p>
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <span class="text-[9px] font-black uppercase text-green-700 bg-green-50 px-2 py-0.5 rounded-lg border border-green-100" x-text="item.size || 'Standard'"></span>
                                                    <span class="text-[10px] font-bold text-slate-400" x-text="'₱' + parseFloat(item.Menu_Price).toFixed(0)"></span>
                                                </div>
                                            </div>
                                            <button @click="cart.splice(index, 1)" class="text-slate-300 hover:text-red-500 p-1 rounded-lg hover:bg-red-50 transition-colors">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="flex items-center justify-between mt-2.5">
                                            <div class="flex items-center bg-slate-50 rounded-xl p-0.5 border border-slate-100">
                                                <button @click="item.qty > 1 ? item.qty-- : cart.splice(index, 1)" class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white text-slate-400 hover:text-red-500 text-xs font-black transition-all">-</button>
                                                <span class="w-8 text-center text-xs font-black text-slate-800" x-text="item.qty"></span>
                                                <button @click="item.qty++" class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white text-slate-400 hover:text-[#006738] text-xs font-black transition-all">+</button>
                                            </div>
                                            <span class="text-sm font-black text-[#006738]" x-text="'₱' + (item.qty * item.Menu_Price).toFixed(0)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Empty State -->
                        <template x-if="cart.length === 0">
                            <div class="py-20 text-center">
                                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                                    <i data-lucide="shopping-bag" class="w-12 h-12"></i>
                                </div>
                                <p class="text-slate-400 font-black uppercase text-sm tracking-widest">Nothing in here yet</p>
                                <button @click="showCartTray = false" class="mt-6 text-[#006738] font-black text-xs uppercase underline">Back to Menu</button>
                            </div>
                        </template>

                        <!-- Checkout Form Inside Scrollable Tray Area (coexisting with scroll) -->
                        <template x-if="cart.length > 0">
                            <div class="space-y-4 pt-2">
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                        <div class="w-full border-t border-slate-200"></div>
                                    </div>
                                    <div class="relative flex justify-center">
                                        <span class="bg-slate-50/50 px-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Checkout Options</span>
                                    </div>
                                </div>

                                <!-- Order Type Selector (Delivery / Pickup) -->
                                <div class="grid grid-cols-2 gap-2 bg-slate-200/50 p-1 rounded-2xl border border-slate-100">
                                    <button @click="orderType = 'Delivery'" 
                                            :class="orderType === 'Delivery' ? 'bg-[#006738] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                            class="py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                        Delivery
                                    </button>
                                    <button @click="orderType = 'Take-out'" 
                                            :class="orderType === 'Take-out' ? 'bg-[#006738] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                            class="py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                        Pickup
                                    </button>
                                </div>

                                <!-- Details Selector Box -->
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs space-y-4">
                                    <!-- Address inputs if Delivery selected -->
                                    <div x-show="orderType === 'Delivery'" class="space-y-3" x-collapse>
                                        <div class="flex justify-between items-center">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Delivery Address</p>
                                            <template x-if="addresses && addresses.length > 0">
                                                <button @click="useCurrentAddress = !useCurrentAddress" class="text-[9px] font-black text-[#006738] hover:underline uppercase tracking-wider">
                                                    <span x-text="useCurrentAddress ? 'Manual Entry' : 'Saved Address'"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- Saved Address Panel -->
                                        <template x-if="useCurrentAddress && addresses && addresses.length > 0">
                                            <div class="p-3 bg-green-50/50 border border-green-100 rounded-xl space-y-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-[9px] font-black text-green-700 uppercase">Primary Address</span>
                                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-[#006738]"></i>
                                                </div>
                                                <p class="text-xs font-bold text-slate-700" x-text="`${addresses[0].Add_StreetName}, Brgy. ${addresses[0].Add_Brgy || 'N/A'}, ${addresses[0].Add_City}`"></p>
                                                <p x-show="addresses[0].Add_Landmark" class="text-[10px] text-slate-400 font-medium" x-text="`Landmark: ${addresses[0].Add_Landmark}`"></p>
                                            </div>
                                        </template>

                                        <!-- Manual Entry Address Inputs -->
                                        <div x-show="!useCurrentAddress || !addresses || addresses.length === 0" class="space-y-2">
                                            <input type="text" x-model="manualAddress.street" placeholder="Street / House No." @input="useCurrentAddress = false"
                                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl py-3 px-4 text-xs font-bold text-slate-700 focus:bg-white focus:border-[#006738] outline-none transition-all">
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" x-model="manualAddress.brgy" placeholder="Barangay" @input="useCurrentAddress = false"
                                                       class="w-full bg-slate-50 border border-slate-100 rounded-xl py-3 px-4 text-xs font-bold text-slate-700 focus:bg-white focus:border-[#006738] outline-none transition-all">
                                                <input type="text" x-model="manualAddress.city" placeholder="City" @input="useCurrentAddress = false"
                                                       class="w-full bg-slate-50 border border-slate-100 rounded-xl py-3 px-4 text-xs font-bold text-slate-700 focus:bg-white focus:border-[#006738] outline-none transition-all">
                                            </div>
                                            <input type="text" x-model="manualAddress.landmark" placeholder="Landmark (Optional)" @input="useCurrentAddress = false"
                                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl py-3 px-4 text-xs font-bold text-slate-700 focus:bg-white focus:border-[#006738] outline-none transition-all">
                                        </div>
                                    </div>

                                    <!-- Payment Mode -->
                                    <div class="flex justify-between items-center pt-1" :class="orderType === 'Delivery' ? 'border-t border-slate-50 pt-3' : ''">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Payment</p>
                                            <p class="text-xs font-black text-slate-700" x-text="paymentMethod || 'Select Method'"></p>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <button @click="paymentMethod = 'Cash (COD)'" 
                                                    :class="paymentMethod === 'Cash (COD)' ? 'border-[#006738] bg-green-50 text-[#006738]' : 'border-slate-100 text-slate-400 bg-slate-50'"
                                                    class="py-2 px-3 rounded-lg border text-[9px] font-black transition-all active:scale-95">COD</button>
                                            <button @click="paymentMethod = 'E-Wallet'" 
                                                    :class="paymentMethod === 'E-Wallet' ? 'border-[#006738] bg-green-50 text-[#006738]' : 'border-slate-100 text-slate-400 bg-slate-50'"
                                                    class="py-2 px-3 rounded-lg border text-[9px] font-black transition-all active:scale-95">WALLET</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Sticky Sticky Thin Footer (No overflow squeeze) -->
                    <template x-if="cart.length > 0">
                        <div class="p-6 bg-[#f8faf8] border-t border-slate-100 space-y-4 shadow-[0_-20px_50px_rgba(0,0,0,0.02)]">
                            <div class="flex justify-between items-end px-1">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Grand Total</p>
                                    <p class="text-3xl font-black text-[#ed1c24] leading-none" x-text="'₱' + parseFloat(cartTotal).toFixed(0)"></p>
                                </div>
                                <div class="text-right">
                                    <p x-show="orderType === 'Delivery' && cartTotal < 200" class="text-[9px] text-red-500 font-black uppercase mb-1">Min ₱200 for delivery</p>
                                    <p class="text-[10px] font-bold text-slate-300">Inc. VAT & Fees</p>
                                </div>
                            </div>

                            <button @click="placeOrder()" 
                                    :disabled="!isCartValid"
                                    :class="!isCartValid ? 'opacity-50 grayscale cursor-not-allowed bg-slate-300 text-slate-500' : 'bg-[#006738] hover:scale-[1.01] active:scale-95 shadow-lg shadow-green-900/10 text-white'"
                                    class="w-full py-4 rounded-2xl transition-all duration-300 uppercase tracking-[0.1em] text-xs font-black flex items-center justify-center gap-2">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                <span>Confirm Order</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Menu Options Modal (Dynamic Pricing) - Slide Tray -->
            <div x-show="showMenuOptionsModal" class="fixed inset-0 z-[70]" x-cloak>
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showMenuOptionsModal = false; selectedMenuItemForOptions = null"></div>
                <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-white shadow-2xl overflow-hidden flex flex-col"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full">
                    
                    <div class="relative h-72 bg-slate-100 overflow-hidden">
                        <template x-if="selectedMenuItemForOptions?.Menu_Image">
                            <img :src="selectedMenuItemForOptions.Menu_Image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!selectedMenuItemForOptions?.Menu_Image">
                             <div class="w-full h-full flex items-center justify-center bg-slate-50">
                                <i data-lucide="utensils" class="w-20 h-20 text-slate-200"></i>
                             </div>
                        </template>
                        <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-black/20"></div>
                        <button @click="showMenuOptionsModal = false; selectedMenuItemForOptions = null" class="absolute top-6 right-6 w-10 h-10 bg-white/20 backdrop-blur-md text-white rounded-full flex items-center justify-center hover:bg-black/20 transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-8 flex-1 overflow-y-auto custom-scrollbar">
                        <div class="mb-10">
                            <h3 class="text-4xl font-black text-slate-800 font-poppins mb-3 tracking-tight" x-text="selectedMenuItemForOptions?.Menu_Name"></h3>
                            <p class="text-slate-500 text-sm leading-relaxed font-medium" x-text="selectedMenuItemForOptions?.Menu_Description"></p>
                        </div>

                        <div class="space-y-8">
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mb-6">Choose Your Size</h4>
                                <div class="space-y-4">
                                    <button @click="menuOptions = { size: 'Small', addonPrice: 0 }" 
                                            :class="menuOptions.size === 'Small' ? 'border-[#006738] bg-green-50' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                            class="w-full flex items-center justify-between p-6 border-2 rounded-3xl transition-all relative group shadow-sm">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors font-black text-sm" :class="menuOptions.size === 'Small' ? 'bg-[#006738] text-white' : 'bg-slate-50 text-slate-400'">
                                                SM
                                            </div>
                                            <span class="font-black text-slate-700 text-lg">Small</span>
                                        </div>
                                        <span class="text-xs font-black text-slate-300 uppercase italic">Free Upgrade</span>
                                    </button>

                                    <button @click="menuOptions = { size: 'Medium', addonPrice: 30 }" 
                                            :class="menuOptions.size === 'Medium' ? 'border-[#006738] bg-green-50' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                            class="w-full flex items-center justify-between p-6 border-2 rounded-3xl transition-all group shadow-sm">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors font-black text-sm" :class="menuOptions.size === 'Medium' ? 'bg-[#006738] text-white' : 'bg-slate-50 text-slate-400'">
                                                MD
                                            </div>
                                            <span class="font-black text-slate-700 text-lg">Medium</span>
                                        </div>
                                        <span class="text-sm font-black text-[#006738]">+ ₱30</span>
                                    </button>

                                    <button @click="menuOptions = { size: 'Large', addonPrice: 50 }" 
                                            :class="menuOptions.size === 'Large' ? 'border-[#006738] bg-green-50' : 'border-slate-100 hover:border-slate-200 bg-white'"
                                            class="w-full flex items-center justify-between p-6 border-2 rounded-3xl transition-all group shadow-sm">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors font-black text-sm" :class="menuOptions.size === 'Large' ? 'bg-[#006738] text-white' : 'bg-slate-50 text-slate-400'">
                                                LG
                                            </div>
                                            <span class="font-black text-slate-700 text-lg">Large</span>
                                        </div>
                                        <span class="text-sm font-black text-[#006738]">+ ₱50</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 bg-[#f8faf8] border-t border-slate-100 shadow-[0_-10px_40px_rgba(0,0,0,0.02)]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Amount</p>
                                <p class="text-4xl font-black text-[#ed1c24]" x-text="'₱' + (parseFloat(selectedMenuItemForOptions?.Menu_Price || 0) + menuOptions.addonPrice).toFixed(0)"></p>
                            </div>
                            <button @click="confirmOptions" class="bg-[#006738] text-white px-12 py-5 rounded-[2.5rem] font-black shadow-xl shadow-green-900/20 hover:scale-105 active:scale-95 transition-all text-xs uppercase tracking-[0.2em]">
                                Add to Tray
                            </button>
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
                            <p class="text-xs font-bold text-white/70">Estimated Arrival: <span x-text="selectedOrder && selectedOrder.Dlvry_Current_ETA ? new Date(selectedOrder.Dlvry_Current_ETA).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'N/A'"></span></p>
                        </div>
                        <button @click="showTrackingModal = false" class="bg-white/10 p-2 rounded-full hover:bg-white/20 transition-all"><i data-lucide="x" class="w-6 h-6"></i></button>
                    </div>
                    
                    <div class="p-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <!-- Enhanced Status Visualization -->
                        <div class="bg-white p-6 rounded-[2.5rem] border-4 border-slate-50 shadow-sm overflow-hidden relative">
                             <!-- Progress Bar Background Map-like feel -->
                             <div class="absolute inset-0 opacity-5 pointer-events-none">
                                 <div class="w-full h-full" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png')"></div>
                             </div>

                             <div class="relative z-10 flex flex-col gap-6">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 bg-[#ffec00] rounded-2xl flex items-center justify-center shadow-xl shadow-yellow-500/20 transform -rotate-3">
                                            <i :data-lucide="selectedOrder && selectedOrder.Order_Stat === 'Delivering' ? 'bike' : (selectedOrder?.Order_Stat === 'Completed' ? 'check-circle' : 'utensils')" class="w-8 h-8 text-black"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Current Status</p>
                                            <p class="text-2xl font-black text-[#006738] font-poppins italic uppercase" x-text="selectedOrder?.Order_Stat"></p>
                                        </div>
                                    </div>
                                    <div class="text-right" x-show="selectedOrder?.Dlvry_Current_ETA">
                                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Arriving At</p>
                                        <p class="text-xl font-black text-[#ed1c24] font-poppins" x-text="new Date(selectedOrder.Dlvry_Current_ETA).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })"></p>
                                    </div>
                                </div>
                                
                                <div class="space-y-6">
                                    <!-- Progress Line -->
                                    <div class="relative flex items-center px-2">
                                        <div class="absolute left-0 right-0 h-1.5 bg-slate-100 rounded-full"></div>
                                        <div class="absolute left-0 h-1.5 bg-[#006738] rounded-full transition-all duration-1000" 
                                             :style="`width: ${
                                                selectedOrder && selectedOrder.Order_Stat === 'Pending' ? '12.5%' : 
                                                selectedOrder && selectedOrder.Order_Stat === 'Preparing' ? '37.5%' :
                                                selectedOrder && selectedOrder.Order_Stat === 'Ready' ? '62.5%' :
                                                selectedOrder && selectedOrder.Order_Stat === 'Delivering' ? '87.5%' :
                                                selectedOrder && selectedOrder.Order_Stat === 'Completed' ? '100%' : '0%'
                                             }`"></div>
                                        
                                        <!-- Step Pins -->
                                        <div class="relative w-full flex justify-between">
                                            <div class="w-6 h-6 rounded-full border-4 border-white shadow-md flex items-center justify-center" :class="selectedOrder?.Order_Stat === 'Pending' ? 'bg-[#ffec00]' : (['Preparing','Ready','Delivering','Completed'].includes(selectedOrder?.Order_Stat) ? 'bg-[#006738]' : 'bg-slate-200')">
                                                <i data-lucide="clipboard-list" class="w-3 h-3 text-white"></i>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-4 border-white shadow-md flex items-center justify-center" :class="selectedOrder?.Order_Stat === 'Preparing' ? 'bg-[#ffec00]' : (['Ready','Delivering','Completed'].includes(selectedOrder?.Order_Stat) ? 'bg-[#006738]' : 'bg-slate-200')">
                                                <i data-lucide="flame" class="w-3 h-3 text-white"></i>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-4 border-white shadow-md flex items-center justify-center" :class="selectedOrder?.Order_Stat === 'Ready' ? 'bg-[#ffec00]' : (['Delivering','Completed'].includes(selectedOrder?.Order_Stat) ? 'bg-[#006738]' : 'bg-slate-200')">
                                                <i data-lucide="box" class="w-3 h-3 text-white"></i>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-4 border-white shadow-md flex items-center justify-center" :class="selectedOrder?.Order_Stat === 'Delivering' ? 'bg-[#ffec00]' : (selectedOrder?.Order_Stat === 'Completed' ? 'bg-[#006738]' : 'bg-slate-200')">
                                                <i data-lucide="bike" class="w-3 h-3 text-white"></i>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-4 border-white shadow-md flex items-center justify-center" :class="selectedOrder?.Order_Stat === 'Completed' ? 'bg-[#006738]' : 'bg-slate-200'">
                                                <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between text-[7px] font-black uppercase tracking-[0.2em] text-slate-400 px-1">
                                        <span>Ordered</span>
                                        <span>Preparing</span>
                                        <span>Ready</span>
                                        <span>On Way</span>
                                        <span>Enjoy!</span>
                                    </div>
                                </div>

                                <!-- Dynamic Status Description -->
                                <div class="bg-green-50 p-4 rounded-2xl border border-green-100 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-[#006738] shadow-sm">
                                        <i :data-lucide="selectedOrder?.Order_Stat === 'Preparing' ? 'chef-hat' : (selectedOrder?.Order_Stat === 'Delivering' ? 'truck' : 'info')" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-black text-slate-800" x-text="
                                            selectedOrder?.Order_Stat === 'Pending' ? 'Hang tight, we are confirming your order!' :
                                            selectedOrder?.Order_Stat === 'Preparing' ? 'Your Mang Inasal favorites are now grilling!' :
                                            selectedOrder?.Order_Stat === 'Ready' ? 'Order is ready and waiting for a rider.' :
                                            selectedOrder?.Order_Stat === 'Delivering' ? 'Your grill fix is on its way to you!' :
                                            'Deliciousness delivered! Hope you enjoy your meal.'
                                        "></h5>
                                        <p class="text-[10px] text-slate-500 font-bold" x-text="selectedOrder?.Order_Stat === 'Preparing' ? 'Nuot sa buto sarap is coming!' : ''"></p>
                                    </div>
                                </div>
                             </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-8 border-t border-b border-slate-50 py-8">
                            <div>
                                <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Payment Mode</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-sm" x-text="selectedOrder?.Pay_Method || 'Cash'"></span>
                                        <span :class="selectedOrder?.Pay_Status === 'Paid' ? 'text-green-500' : 'text-orange-500'" class="text-[10px] font-black uppercase" x-text="selectedOrder?.Pay_Status"></span>
                                    </div>
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

                        <!-- Receipt / Summary -->
                        <div class="bg-white rounded-[2rem] border-4 border-slate-50 p-6 space-y-6">
                            <div class="flex justify-between items-center px-1">
                                <h4 class="text-sm font-black uppercase tracking-widest text-[#006738]">Order Summary</h4>
                                <span class="bg-slate-100 text-slate-400 text-[10px] font-black px-2 py-0.5 rounded" x-text="selectedOrder?.Order_Stat"></span>
                            </div>
                            
                            <div class="space-y-4">
                                <template x-if="selectedOrder && selectedOrder.Order_Items">
                                    <div class="space-y-3">
                                        <template x-for="item in selectedOrder.Order_Items" :key="item.OItem_ID">
                                            <div class="flex justify-between items-center group">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-[10px] text-slate-400" x-text="item.OItem_Quantity"></div>
                                                    <span class="font-bold text-slate-700 text-sm" x-text="item.Menu_Name"></span>
                                                </div>
                                                <span class="font-black text-slate-400 text-sm" x-text="'₱' + (parseFloat(item.OItem_Unit_Price) * item.OItem_Quantity).toFixed(0)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                
                                <div class="pt-4 border-t-2 border-dashed border-slate-100 flex justify-between items-center">
                                    <span class="text-xs font-black uppercase text-slate-400 tracking-widest">Total Paid</span>
                                    <span class="text-2xl font-black text-[#ed1c24]" x-text="'₱' + (selectedOrder ? parseFloat(selectedOrder.Order_Total_Amount).toFixed(0) : '0')"></span>
                                </div>
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
        <?php endif; ?>

        <!-- Universal Profile Tab -->
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
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest" x-text="role"></p>
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
            </div>
        </div>

        <?php if ($role !== 'Customer'): ?>
        <!-- Administrative Modals -->
        <div x-show="showEditBranchModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showEditBranchModal = false">
                <div class="bg-[#006738] p-6 text-white flex justify-between items-center">
                    <h3 class="text-xl font-black font-poppins">Edit Branch</h3>
                    <button @click="showEditBranchModal = false" class="hover:rotate-90 transition-transform">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4" x-if="editingBranch">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Branch Name</label>
                        <input type="text" x-model="editingBranch.Brnch_Name" class="w-full p-3 rounded-xl border-slate-200 focus:ring-[#006738] focus:border-[#006738] font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">City</label>
                            <input type="text" x-model="editingBranch.Brnch_City" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Province</label>
                            <input type="text" x-model="editingBranch.Brnch_Province" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Delivery Radius (km)</label>
                        <input type="number" step="0.1" x-model="editingBranch.Brnch_Radius" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <button @click="submitEditBranch" class="w-full bg-[#006738] text-white py-4 rounded-2xl font-black shadow-lg shadow-green-900/10 hover:bg-[#004d2a] transition-all">
                        Update Branch
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Menu Modal -->
        <div x-show="showMenuModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showMenuModal = false">
                <div class="p-6 bg-[#006738] text-white flex justify-between items-center">
                    <h3 class="font-black text-xl font-poppins capitalize" x-text="role === 'System Admin' ? 'Add Global Menu Item' : 'Add Branch Menu Item'"></h3>
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
                    <div>
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Menu Image</label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="w-20 h-20 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden">
                                <template x-if="newMenu.image">
                                    <img :src="newMenu.image" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!newMenu.image">
                                    <i data-lucide="image" class="w-6 h-6 text-slate-300"></i>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="file" @change="let file = $event.target.files[0]; if (file) { compressAndResizeImage(file, 500, 0.8).then(base64 => { newMenu.image = base64; }); }" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-green-50 file:text-[#006738] hover:file:bg-green-100 cursor-pointer">
                                <p class="text-[10px] text-slate-400 mt-1 uppercase font-black">Upload picture of the menu item (auto-saved as compressed file)</p>
                            </div>
                        </div>
                    </div>
                    <button @click="submitMenu()" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform" x-text="role === 'System Admin' ? 'Save Global Product' : 'Save Branch Product'"></button>
                </div>
            </div>
        </div>

        <!-- Edit Menu Modal -->
        <div x-show="showEditMenuModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showEditMenuModal = false">
                <div class="bg-[#006738] p-6 text-white flex justify-between items-center">
                    <h3 class="text-xl font-black font-poppins" x-text="role === 'System Admin' ? 'Edit Global Item' : 'Edit Branch Item'"></h3>
                    <button @click="showEditMenuModal = false" class="hover:rotate-90 transition-transform">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4" x-if="editingMenu">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Product Name</label>
                        <input type="text" x-model="editingMenu.Menu_Name" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Description</label>
                        <textarea x-model="editingMenu.Menu_Description" class="w-full p-3 rounded-xl border-slate-200 h-20 outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Category</label>
                        <select x-model="editingMenu.Menu_Category" class="w-full p-3 rounded-xl border-slate-200">
                            <template x-for="cat in Array.from(new Set(categories.map(c => c.db)))">
                                <option :value="cat" x-text="cat"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Price (₱)</label>
                            <input type="number" x-model="editingMenu.Menu_Price" class="w-full p-3 rounded-xl border-slate-200 font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Serving Size</label>
                            <input type="text" x-model="editingMenu.Menu_Size" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Item Image</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center text-slate-300">
                                <template x-if="editingMenu.Menu_Image">
                                    <img :src="editingMenu.Menu_Image" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!editingMenu.Menu_Image">
                                    <i data-lucide="utensils" class="w-6 h-6"></i>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="file" @change="let file = $event.target.files[0]; if (file) { compressAndResizeImage(file, 500, 0.8).then(base64 => { editingMenu.Menu_Image = base64; }); }" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-green-50 file:text-[#006738] hover:file:bg-green-100 cursor-pointer">
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <template x-if="role === 'Branch Manager' && editingMenu">
                            <button @click="deleteBranchMenuItem(editingMenu.Menu_ID); showEditMenuModal = false;" class="px-5 bg-red-50 hover:bg-red-100 text-red-600 rounded-2xl font-black text-xs flex items-center justify-center gap-1.5 transition-all">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                <span>Delete Item</span>
                            </button>
                        </template>
                        <button @click="submitEditMenu" class="flex-1 bg-[#006738] text-white py-4 rounded-2xl font-black shadow-lg shadow-green-900/10 hover:bg-[#004d2a] transition-all" x-text="role === 'System Admin' ? 'Update Global Menu' : 'Update Branch Menu'">
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Manager Modal -->
        <div x-show="showEditManagerModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showEditManagerModal = false">
                <div class="bg-[#ffec00] p-6 text-black flex justify-between items-center">
                    <h3 class="text-xl font-black font-poppins">Edit Manager</h3>
                    <button @click="showEditManagerModal = false" class="hover:rotate-90 transition-transform">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4" x-if="editingManager">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">First Name</label>
                            <input type="text" x-model="editingManager.fname" class="w-full p-3 rounded-xl border-slate-200 font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Last Name</label>
                            <input type="text" x-model="editingManager.lname" class="w-full p-3 rounded-xl border-slate-200 font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Email Address</label>
                        <input type="email" x-model="editingManager.email" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Mobile Number</label>
                        <input type="text" x-model="editingManager.mobile" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Assign to Branch</label>
                        <select x-model="editingManager.Mgr_Brnch_ID" class="w-full p-3 rounded-xl border-slate-200 font-bold">
                            <option value="">Select Branch</option>
                            <template x-for="branch in branches">
                                <option :value="branch.Brnch_ID" x-text="branch.Brnch_Name"></option>
                            </template>
                        </select>
                    </div>
                    <button @click="submitEditManager" class="w-full bg-black text-white py-4 rounded-2xl font-black shadow-lg hover:bg-slate-800 transition-all">
                        Update Manager
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Staff Modal -->
        <div x-show="showEditStaffModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showEditStaffModal = false">
                <div class="bg-[#006738] p-6 text-white">
                    <h3 class="text-xl font-black font-poppins">Edit Staff Member</h3>
                </div>
                <div class="p-6 space-y-4" x-if="editingStaff">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" x-model="editingStaff.fname" placeholder="First Name" class="w-full p-3 rounded-xl border-slate-200">
                        <input type="text" x-model="editingStaff.lname" placeholder="Last Name" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <input type="email" x-model="editingStaff.email" placeholder="Email Address" class="w-full p-3 rounded-xl border-slate-200">
                    <input type="text" x-model="editingStaff.mobile" placeholder="Mobile" class="w-full p-3 rounded-xl border-slate-200">
                    <select x-model="editingStaff.role" class="w-full p-3 rounded-xl border-slate-200">
                        <option value="Kitchen Staff">Kitchen Staff</option>
                        <option value="Front Counter">Front Counter</option>
                        <option value="Supervisor">Supervisor</option>
                    </select>
                    <button @click="submitEditStaff" class="w-full bg-[#006738] text-white py-3 rounded-xl font-bold">Update Staff</button>
                </div>
            </div>
        </div>

        <!-- Edit Rider Modal -->
        <div x-show="showEditRiderModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4" x-cloak x-transition>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden" @click.away="showEditRiderModal = false">
                <div class="bg-black p-6 text-white text-center">
                    <h3 class="text-xl font-black font-poppins">Edit Rider Account</h3>
                </div>
                <div class="p-6 space-y-4" x-if="editingRider">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" x-model="editingRider.fname" placeholder="First Name" class="w-full p-3 rounded-xl border-slate-200">
                        <input type="text" x-model="editingRider.lname" placeholder="Last Name" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <input type="email" x-model="editingRider.email" placeholder="Email Address" class="w-full p-3 rounded-xl border-slate-200">
                    <input type="text" x-model="editingRider.mobile" placeholder="Mobile" class="w-full p-3 rounded-xl border-slate-200">
                    <button @click="submitEditRider" class="w-full bg-[#ffec00] text-black py-3 rounded-xl font-bold">Update Rider Details</button>
                </div>
            </div>
        </div>

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
                    <div class="flex flex-col gap-3">
                        <button @click="submitStaff(false)" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Create Account</button>
                        <button @click="submitStaff(true)" class="w-full bg-[#f1f5f1] text-[#006738] font-black py-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-sm">Save & Add Another</button>
                    </div>
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
                    <div class="flex flex-col gap-3">
                        <button @click="submitRider(false)" class="w-full bg-[#006738] text-white font-black py-4 rounded-2xl shadow-lg hover:scale-[1.02] transition-transform">Register Rider</button>
                        <button @click="submitRider(true)" class="w-full bg-[#f1f5f1] text-[#006738] font-black py-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-sm">Save & Add Another</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Universal Profile Modal -->
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

        <?php if ($role === 'Customer'): ?>
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
                <!-- Logo with fallback like index.php -->
                <div class="relative group cursor-pointer mx-auto mb-12 w-fit" @click="window.location.reload()">
                    <img src="logo.png" alt="Mang Inasal" class="h-24 sm:h-32 w-auto object-contain transition-transform group-hover:scale-105 duration-300 mix-blend-multiply" onerror="this.style.display='none'; document.getElementById('branch-picker-logo-fallback').style.display='block'">
                    <div id="branch-picker-logo-fallback" style="display: none;" class="bg-[#ffec00] p-4 border-[4px] border-black rounded-sm shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                        <div class="text-black font-poppins font-black leading-none tracking-tighter">
                            <span class="block text-sm uppercase italic text-black">Mang</span>
                            <span class="block text-4xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
                        </div>
                    </div>
                </div>
                
                <h2 class="text-4xl md:text-6xl font-black text-white font-poppins mb-4 tracking-tight">KUNG SAAN ANG SARAP!</h2>
                <p class="text-white/80 text-lg md:text-xl font-bold mb-12">Please select a branch to view our menu and start your order.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[50vh] overflow-y-auto no-scrollbar p-4">
                    <template x-for="(branch, index) in customerBranches" :key="branch.Brnch_ID">
                        <div @click="selectBranch(branch.Brnch_ID); activeTab = 'order_now'" 
                             class="bg-white/10 backdrop-blur-md border-2 border-white/20 p-8 rounded-[2.5rem] hover:bg-white hover:border-[#ffec00] transition-all duration-300 cursor-pointer group text-left relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/5 rounded-full group-hover:scale-150 transition-transform"></div>
                            <div class="w-12 h-12 bg-[#ffec00] text-black rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg relative z-10">
                                <i :data-lucide="['store', 'building', 'map-pin', 'chef-hat'][index % 4]" class="w-6 h-6"></i>
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
        <div x-show="message" x-transition x-cloak class="fixed bottom-8 right-8 z-[200] max-w-sm sm:max-w-md">
            <div :class="message?.success ? 'bg-green-600 border border-green-500' : 'bg-red-600 border border-red-500'" class="text-white px-6 py-4 rounded-2xl shadow-2xl flex flex-col gap-2 relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <i :data-lucide="message?.success ? 'check-circle' : 'alert-circle'" class="w-6 h-6 shrink-0"></i>
                    <span x-text="message?.text" class="font-bold flex-1"></span>
                    <button @click="message = null" class="ml-4 opacity-70 hover:opacity-100 shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                <!-- Interactive Firebase/Firestore Connection Status & Helpful Diagnostic Alert -->
                <template x-if="message?.firebase_status && !message.firebase_status.connected">
                    <div class="mt-2 text-xs bg-black/30 p-4 rounded-xl border border-white/15 space-y-1.5 leading-normal">
                        <div class="font-black text-[9px] uppercase tracking-wider text-yellow-300 flex items-center gap-1.5">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-yellow-300"></i> FIRESTORE SYNC WARNING
                        </div>
                        <p class="text-[11px] font-medium text-white/95" x-text="message.firebase_status.error"></p>
                        <div class="pt-1.5 border-t border-white/10 text-[10px] text-yellow-200/90 font-semibold" x-text="message.firebase_status.detailed_guide"></div>
                    </div>
                </template>
            </div>
        </div>

    </main>

    <!-- Footer moved outside main for proper bottom placement -->
    <footer :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'" class="transition-all duration-300 bg-[#006738] pt-12 pb-8 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-4">
                <!-- Footer Links -->
                <nav class="flex flex-wrap items-center gap-x-6 gap-y-3 font-poppins font-black uppercase tracking-widest text-[10px] sm:text-xs">
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">About Us</a>
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">FAQs</a>
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">Contact Us</a>
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">Terms and Conditions</a>
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">Corporate Information</a>
                    <a href="#" class="text-white hover:text-[#ffec00] transition-colors">Privacy Notice</a>
                </nav>
                <!-- Store Badges in Footer -->
                <div class="flex items-center shrink-0">
                    <img src="socials2.png" class="h-9 sm:h-10 cursor-pointer hover:scale-105 transition-transform" alt="Google Play and App Store">
                </div>
            </div>

            <div class="mt-4 mb-8">
                <p class="text-white text-[11px] font-medium opacity-90 font-poppins">DTI Trustmark Application submitted with Reference No. 250922-111194080, pending approval</p>
            </div>
            
            <!-- White Line separator -->
            <div class="h-px bg-white/20 w-full mb-8"></div>

            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-white text-[11px] sm:text-xs font-medium font-poppins">Copyright © 2025 - 2026. Mang Inasal Philippines, Inc. All rights reserved.</p>
                <!-- Social Icons matching screenshot style -->
                <div class="flex items-center">
                    <img src="socials.png" class="h-10 w-auto cursor-pointer" alt="Social Media Links">
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
