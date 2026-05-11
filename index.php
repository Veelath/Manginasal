<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mang Inasal - Login</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js for simple state management -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] { display: none !important; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-[#fcfbf7] font-outfit min-h-screen flex items-center justify-center p-4 sm:p-8" x-data="authApp()" x-cloak>

    <div id="main-container" class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px] transition-all duration-500">
        
        <!-- Left Side: Brand Panel -->
        <div id="brand-panel" class="w-full md:w-1/2 bg-[#006738] relative p-12 flex flex-col justify-between overflow-hidden">
            <!-- Background Decoration -->
            <div class="absolute top-[-10%] right-[-10%] w-[120%] h-[120%] opacity-10 pointer-events-none text-[#ffec00]">
                <i data-lucide="chef-hat" class="w-full h-full rotate-12"></i>
            </div>

            <!-- Logo -->
            <div class="relative z-10">
                <div class="relative group flex items-start">
                    <!-- If they have logo.png in the same folder -->
                    <img src="logo.png" alt="Mang Inasal Logo" class="h-24 w-auto object-contain transition-transform group-hover:scale-105 duration-300 mix-blend-multiply" onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block'">
                    
                    <div id="logo-fallback" style="display: none;" class="inline-block bg-[#ffec00] p-4 border-[4px] border-black rounded-sm shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-transform group-hover:scale-105">
                        <div class="text-black font-poppins font-black leading-none tracking-tighter">
                            <span class="block text-sm uppercase italic text-black">Mang</span>
                            <span class="block text-4xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tagline -->
            <div class="relative z-10 space-y-6">
                <div class="transition-all duration-700 delay-200">
                    <h2 class="text-white text-3xl md:text-4xl font-bold leading-tight font-poppins">
                        The 2-in-1 Big Size, <br />
                        <span class="text-[#ffec00]">Deep in Grilled-Goodness!</span>
                    </h2>
                    <p class="text-green-50/80 mt-4 text-lg">Authentic Filipino charcoal-grilled chicken that satisfies every craving.</p>
                </div>
            </div>

            <div class="relative z-10 mt-8">
                <p class="text-xs text-white/40 uppercase tracking-widest font-semibold">© 2026 Mang Inasal</p>
            </div>
        </div>

        <!-- Right Side: Form Panel -->
        <div id="form-panel" class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
            
            <div class="space-y-6">
                <!-- Header -->
                <div class="space-y-1 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-slate-800 font-poppins" x-text="isResetMode ? 'Reset Password' : (isLogin ? 'Welcome Back!' : 'Create Account')"></h3>
                    <p class="text-slate-500 text-sm" x-text="isResetMode ? 'Enter your email to receive a temporary password.' : (isLogin ? 'Fresh grilled meals are waiting for you.' : 'Join the Mang Inasal family today!')"></p>
                </div>

                <!-- Messages -->
                <template x-if="message">
                    <div :class="message.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'" 
                         class="flex items-center gap-3 p-4 rounded-xl border text-sm font-medium animate-in fade-in slide-in-from-top-2 duration-300">
                        <i :data-lucide="message.type === 'success' ? 'check-circle-2' : 'alert-circle'" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-text="message.text"></span>
                    </div>
                </template>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4" x-show="!isLogin && !isResetMode">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">First Name</label>
                            <div class="relative group">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                                <input type="text" :required="!isLogin && !isResetMode" x-model="fname" 
                                       placeholder="Juan"
                                       class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Last Name</label>
                            <div class="relative group">
                                <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                                <input type="text" :required="!isLogin && !isResetMode" x-model="lname" 
                                       placeholder="Dela Cruz"
                                       class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2" x-show="!isLogin && !isResetMode">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Mobile Number</label>
                        <div class="relative group">
                            <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                            <input type="tel" :required="!isLogin && !isResetMode" x-model="mobile" 
                                   placeholder="09123456789" maxlength="11"
                                   class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                            <input type="email" required x-model="email" 
                                   placeholder="kaingInasal@example.com"
                                   class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-2" x-show="!isResetMode">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Password</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738] transition-colors"></i>
                            <input type="password" :required="!isResetMode" x-model="password"
                                   placeholder="••••••••"
                                   class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 transition-all outline-none">
                        </div>
                        <div x-show="isLogin" class="flex justify-end mt-1">
                            <button type="button" @click="toggleReset" class="text-xs font-bold text-[#006738] hover:underline uppercase tracking-wider">Forgot?</button>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-[#006738] hover:bg-[#004d29] text-white font-black py-4 rounded-2xl shadow-xl shadow-green-900/10 flex items-center justify-center gap-2 group transition-all transform hover:-translate-y-1 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="loading">
                            <div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </template>
                        <template x-if="!loading">
                            <div class="flex items-center gap-2">
                                <span class="uppercase tracking-widest" x-text="isResetMode ? 'Send Reset Link' : (isLogin ? 'Login to Order' : 'Sign Up Now')"></span>
                                <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </template>
                    </button>
                </form>

                <!-- Switch -->
                <div class="pt-2 text-center">
                    <p class="text-sm font-medium text-slate-500">
                        <span x-text="isResetMode ? 'Remembered your password?' : (isLogin ? 'Don\'t have an account?' : 'Already have an account?')"></span>
                        <button @click="isResetMode ? toggleReset() : toggleAuth()" class="text-[#006738] font-black hover:underline uppercase tracking-tight">
                            <span x-text="isResetMode ? 'Go Back' : (isLogin ? 'Sign Up' : 'Log In')"></span>
                        </button>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        function authApp() {
            return {
                isLogin: true,
                isResetMode: false,
                fname: '',
                lname: '',
                mobile: '',
                email: '',
                password: '',
                loading: false,
                message: null,
                toggleAuth() {
                    this.isLogin = !this.isLogin;
                    this.isResetMode = false;
                    this.message = null;
                    this.fname = '';
                    this.lname = '';
                    this.mobile = '';
                    this.email = '';
                    this.password = '';
                    this.$nextTick(() => lucide.createIcons());
                },
                toggleReset() {
                    this.isResetMode = !this.isResetMode;
                    this.message = null;
                    this.$nextTick(() => lucide.createIcons());
                },
                async handleSubmit() {
                    this.loading = true;
                    this.message = null;
                    
                    const action = this.isResetMode ? 'reset_password' : (this.isLogin ? 'login' : 'signup');
                    
                    try {
                        const response = await fetch('auth.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: this.email,
                                password: this.password,
                                fname: this.fname,
                                lname: this.lname,
                                mobile: this.mobile,
                                action: action
                            })
                        });
                        
                        const data = await response.json();
                        this.message = {
                            type: data.success ? 'success' : 'error',
                            text: data.message
                        };

                        if (data.success) {
                            if (action === 'login') {
                                setTimeout(() => window.location.href = 'dashboard.php', 1000);
                            } else if (action === 'signup' || action === 'reset_password') {
                                setTimeout(() => {
                                    this.isLogin = true;
                                    this.isResetMode = false;
                                    this.message = null;
                                    if(action === 'signup') {
                                        this.email = '';
                                        this.password = '';
                                        this.fname = '';
                                        this.lname = '';
                                        this.mobile = '';
                                    }
                                    this.$nextTick(() => lucide.createIcons());
                                }, 3000);
                            }
                        }
                    } catch (err) {
                        this.message = {
                            type: 'error',
                            text: 'An error occurred. Please try again.'
                        };
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                }
            }
        }
        
        // Initial icons
        lucide.createIcons();
    </script>
</body>
</html>
