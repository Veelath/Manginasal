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
<body class="bg-[#fcfbf7] font-outfit min-h-screen flex flex-col" x-data="authApp()" x-cloak>

    <!-- Header -->
    <header class="bg-white sticky top-0 z-[100] shadow-sm">
        <!-- Top Header -->
        <div class="border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-12 sm:h-16 flex items-center justify-between">
                <div class="flex items-center gap-4 sm:gap-8">
                    <!-- Logo -->
                    <div class="relative group cursor-pointer" @click="window.location.reload()">
                        <img src="logo.png" alt="Mang Inasal" class="h-8 sm:h-12 w-auto object-contain transition-transform group-hover:scale-105 duration-300 mix-blend-multiply" onerror="this.style.display='none'; document.getElementById('header-logo-fallback').style.display='block'">
                        <div id="header-logo-fallback" style="display: none;" class="inline-block bg-[#ffec00] p-1.5 sm:p-2 border-[2px] sm:border-[3px] border-black rounded-sm shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform group-hover:scale-105">
                            <div class="text-black font-poppins font-black leading-none tracking-tighter">
                                <span class="block text-[6px] sm:text-[8px] uppercase italic text-black">Mang</span>
                                <span class="block text-sm sm:text-xl uppercase text-[#ed1c24] -mt-0.5">Inasal</span>
                            </div>
                        </div>
                    </div>

    
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    <button @click="showAuthForm = true; isLogin = true; isResetMode = false; $nextTick(() => lucide.createIcons())" class="bg-[#ffec00] text-black text-[10px] sm:text-xs font-black uppercase tracking-widest px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:shadow-lg transition-all active:scale-95">Sign Up / Log In</button>
                    <button @click="showAuthForm = true; isLogin = true; isResetMode = false; $nextTick(() => lucide.createIcons())" class="bg-[#006738] text-white text-[10px] sm:text-xs font-black uppercase tracking-widest px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-[#004d29] transition-all active:scale-95">Order Now</button>
                </div>
            </div>
        </div>
        <!-- Bottom Header (Navigation) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-10 sm:h-12 flex items-center">
            <nav class="flex items-center gap-6 sm:gap-10">
                <a href="#" class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-slate-800 hover:text-[#006738] transition-colors border-b-2 border-transparent hover:border-[#006738] h-full flex items-center">Menu</a>
                <a href="#" class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-slate-800 hover:text-[#006738] transition-colors border-b-2 border-transparent hover:border-[#006738] h-full flex items-center">Stores</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow flex flex-col relative overflow-hidden" :class="showAuthForm ? 'h-screen overflow-hidden' : ''">
        
        <!-- Auth Modal Backdrop -->
        <div x-show="showAuthForm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[200] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
             @click.self="showAuthForm = false">
            
            <div id="main-container" class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px] transition-all duration-500 relative">
                <button @click="showAuthForm = false" class="absolute top-4 right-4 z-[210] w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 hover:text-[#ed1c24] transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                
                <!-- Left Side: Brand Panel -->
                <div id="brand-panel" class="w-full md:w-1/2 bg-[#006738] relative p-12 flex flex-col justify-between overflow-hidden">
                    <div class="absolute top-[-10%] right-[-10%] w-[120%] h-[120%] opacity-10 pointer-events-none text-[#ffec00]">
                        <i data-lucide="chef-hat" class="w-full h-full rotate-12"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="relative group flex items-start">
                            <img src="logo.png" alt="Mang Inasal Logo" class="h-24 w-auto object-contain mix-blend-multiply" onerror="this.style.display='none'; document.getElementById('logo-modal-fallback').style.display='block'">
                            <div id="logo-modal-fallback" style="display: none;" class="inline-block bg-[#ffec00] p-4 border-[4px] border-black rounded-sm shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]">
                                <div class="text-black font-poppins font-black leading-none tracking-tighter">
                                    <span class="block text-sm uppercase italic text-black">Mang</span>
                                    <span class="block text-4xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="relative z-10 space-y-6">
                        <h2 class="text-white text-3xl md:text-4xl font-bold leading-tight font-poppins">
                            The 2-in-1 Big Size, <br />
                            <span class="text-[#ffec00]">Deep in Grilled-Goodness!</span>
                        </h2>
                        <p class="text-green-50/80 mt-4 text-lg">Authentic Filipino charcoal-grilled chicken that satisfies every craving.</p>
                    </div>
                    <div class="relative z-10 mt-8">
                        <p class="text-xs text-white/40 uppercase tracking-widest font-semibold">© 2026 Mang Inasal</p>
                    </div>
                </div>

                <!-- Right Side: Form Panel -->
                <div id="form-panel" class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
                    <div class="space-y-6">
                        <div class="space-y-1 text-center md:text-left">
                            <h3 class="text-2xl font-bold text-slate-800 font-poppins" x-text="isResetMode ? 'Reset Password' : (isLogin ? 'Welcome Back!' : 'Create Account')"></h3>
                            <p class="text-slate-500 text-sm" x-text="isResetMode ? 'Enter your email to receive a temporary password.' : (isLogin ? 'Fresh grilled meals are waiting for you.' : 'Join the Mang Inasal family today!')"></p>
                        </div>
                        <template x-if="message">
                            <div :class="message.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'" class="flex items-center gap-3 p-4 rounded-xl border text-sm font-medium">
                                <i :data-lucide="message.type === 'success' ? 'check-circle-2' : 'alert-circle'" class="w-5 h-5 flex-shrink-0"></i>
                                <span x-text="message.text"></span>
                            </div>
                        </template>
                        <form @submit.prevent="handleSubmit" class="space-y-5">
                            <div class="grid grid-cols-2 gap-4" x-show="!isLogin && !isResetMode">
                                <div class="space-y-2">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">First Name</label>
                                    <div class="relative group">
                                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738]"></i>
                                        <input type="text" :required="!isLogin && !isResetMode" x-model="fname" placeholder="Juan" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 outline-none">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Last Name</label>
                                    <div class="relative group">
                                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738]"></i>
                                        <input type="text" :required="!isLogin && !isResetMode" x-model="lname" placeholder="Dela Cruz" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 outline-none">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2" x-show="!isLogin && !isResetMode">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Mobile Number</label>
                                <div class="relative group">
                                    <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738]"></i>
                                    <input type="tel" :required="!isLogin && !isResetMode" x-model="mobile" placeholder="09123456789" maxlength="11" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 outline-none">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                                <div class="relative group">
                                    <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738]"></i>
                                    <input type="text" x-model="email" placeholder="kaingInasal@example.com" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 outline-none">
                                </div>
                            </div>
                            <div class="space-y-2" x-show="!isResetMode">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Password</label>
                                <div class="relative group">
                                    <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-[#006738]"></i>
                                    <input type="password" :required="!isResetMode" x-model="password" placeholder="••••••••" class="w-full bg-[#f1f5f1] border-2 border-transparent focus:border-[#006738] rounded-2xl py-4 pl-12 pr-4 text-slate-800 outline-none">
                                </div>
                                <div x-show="isLogin" class="flex justify-end mt-1">
                                    <button type="button" @click="toggleReset" class="text-xs font-bold text-[#006738] hover:underline uppercase tracking-wider">Forgot?</button>
                                </div>
                            </div>
                            <button type="submit" :disabled="loading" class="w-full bg-[#006738] hover:bg-[#004d29] text-white font-black py-4 rounded-2xl shadow-xl shadow-green-900/10 flex items-center justify-center gap-2 group transition-all transform hover:-translate-y-1 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="loading"><div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin"></div></template>
                                <template x-if="!loading"><div class="flex items-center gap-2"><span class="uppercase tracking-widest" x-text="isResetMode ? 'Send Reset Link' : (isLogin ? 'Login to Order' : 'Sign Up Now')"></span><i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i></div></template>
                            </button>
                        </form>
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
        </div>

        <!-- Landing Sections (Hidden when Auth Form is visible to improve focus, or kept behind backdrop) -->
        <div x-show="!showAuthForm">
            <!-- Hero Banner -->
            <section class="relative w-full h-[300px] sm:h-[450px] overflow-hidden group">
                <template x-for="(slide, index) in slides" :key="index">
                    <div x-show="currentSlide === index" 
                         x-transition:enter="transition ease-out duration-1000"
                         x-transition:enter-start="opacity-0 scale-105"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-1000"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute inset-0">
                        <img :src="slide.image" :alt="slide.title" class="w-full h-full object-cover">
                    </div>
                </template>

                <button @click="currentSlide = (currentSlide - 1 + slides.length) % slides.length" 
                        class="absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/40 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-[#006738] transition-all z-20 shadow-lg">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button @click="currentSlide = (currentSlide + 1) % slides.length" 
                        class="absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/40 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-[#006738] transition-all z-20 shadow-lg">
                    <i data-lucide="chevron-right"></i>
                </button>

                <!-- Carousel Indicators -->
                <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 z-10">
                    <template x-for="(slide, index) in slides" :key="'ind'+index">
                        <button @click="currentSlide = index" 
                                :class="currentSlide === index ? 'w-12 h-1 bg-white' : 'w-12 h-1 bg-white/30 hover:bg-white/50'"
                                class="rounded-full transition-all duration-300"></button>
                    </template>
                </div>
            </section>

            <!-- Featured Menu -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                <div class="flex items-center justify-between mb-12 sm:mb-16">
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-800 font-poppins tracking-tight">Featured Menu</h2>
                    <a href="#" class="text-[#006738] font-bold text-sm sm:text-lg flex items-center gap-2 hover:underline">View All <i data-lucide="chevron-right" class="w-5 h-5"></i></a>
                </div>
                
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-6 sm:gap-10">
                    <!-- Must Try -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="must-try.jpg" alt="Must Try" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Must Try</p>
                        </div>
                    </div>
                    <!-- Fiesta Group Meals -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="fiesta.jpg" alt="Fiesta Group Meals" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Fiesta Group Meals</p>
                        </div>
                    </div>
                    <!-- Chicken Inasal -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="chck.jpg" alt="Chicken Inasal" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Chicken Inasal</p>
                        </div>
                    </div>
                    <!-- Halo-Halo -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="hal.jpg" alt="Halo-Halo" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Halo-Halo</p>
                        </div>
                    </div>
                    <!-- Palabok -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="pal.jpg" alt="Palabok" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Palabok</p>
                        </div>
                    </div>
                    <!-- Grilled Pork -->
                    <div class="group cursor-pointer">
                        <div class="bg-white rounded-3xl p-4 sm:p-6 mb-4 transition-transform group-hover:scale-105 shadow-sm border border-slate-50">
                            <img src="pork.jpg" alt="Grilled Pork" class="w-full aspect-square object-cover rounded-2xl mb-4 shadow-md">
                            <p class="text-center font-bold text-slate-800 text-sm sm:text-base tracking-tight">Grilled Pork</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- App Promo Section -->
            <section class="bg-[#ffec00] py-16 sm:py-24 relative overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center gap-12 sm:gap-20">
                    <div class="flex-1 space-y-6 sm:space-y-10 z-10">
                        <h2 class="text-3xl sm:text-6xl font-black text-slate-800 font-poppins leading-[1.1] tracking-tight">Order Mang Inasal online for delivery or pick-up!</h2>
                        <p class="text-slate-700 text-lg sm:text-2xl font-bold leading-relaxed">Enjoy unli-sulit at unli-saya with the all-new Mang Inasal app. It makes ordering for delivery or pick-up super easy, plus you get access to exclusive promos! <br> <span class="font-black text-slate-800">Download the app now.</span></p>
                        <div class="flex flex-wrap items-center gap-4 pt-4 shrink-0">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/3c/Download_on_the_App_Store_Badge.svg/1200px-Download_on_the_App_Store_Badge.svg.png" class="h-10 sm:h-14 cursor-pointer hover:scale-105 transition-transform">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Google_Play_Store_badge_EN.svg/1200px-Google_Play_Store_badge_EN.svg.png" class="h-10 sm:h-14 cursor-pointer hover:scale-105 transition-transform">
                        </div>
                    </div>
                    <div class="flex-1 relative z-10 px-8 sm:px-0">
                        <div class="relative w-full max-w-[400px] mx-auto bg-slate-800 rounded-[3rem] p-4 shadow-2xl border-8 border-slate-700">
                             <div class="bg-white rounded-[2.5rem] overflow-hidden aspect-[9/19]">
                                <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="w-full h-full object-cover opacity-50 grayscale">
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-8 bg-[#006738]/80 text-center">
                                    <div class="inline-block bg-[#ffec00] p-4 border-[4px] border-black rounded-sm shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] mb-8 scale-75">
                                        <div class="text-black font-poppins font-black leading-none tracking-tighter">
                                            <span class="block text-sm uppercase italic text-black">Mang</span>
                                            <span class="block text-4xl uppercase text-[#ed1c24] -mt-1">Inasal</span>
                                        </div>
                                    </div>
                                    <p class="text-white text-xl font-black uppercase tracking-widest leading-tight italic">Get your ihaw-sarap faves in just a few taps</p>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
                <!-- Background decorative logo -->
                <i data-lucide="chef-hat" class="absolute right-[-10%] bottom-[-10%] w-[50%] h-[50%] text-black/5 rotate-12 -z-0"></i>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#006738] pt-12 pb-8">
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

    <!-- Floating Chat Avatar -->
    <div class="fixed bottom-6 right-6 z-[300] group cursor-pointer">
        <div class="relative">
            <div class="w-16 h-16 rounded-full overflow-hidden border-4 border-white shadow-2xl transition-transform group-hover:scale-110">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=InasalHelper" class="w-full h-full bg-[#ffec00]" alt="Assistance">
            </div>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
        </div>
    </div>

    <script>
        function authApp() {
            return {
                showAuthForm: false,
                isLogin: true,
                isResetMode: false,
                fname: '',
                lname: '',
                mobile: '',
                email: '',
                password: '',
                loading: false,
                message: null,
                // Slider state
                currentSlide: 0,
                slides: [
                    { 
                        title: 'Juicy & Lami na Grilled Chicken!', 
                        image: 'hero1.jpg'
                    },
                    { 
                        title: 'Experience Authentic Pinoy Flavors!', 
                        image: 'hero2.jpg'
                    },
                    { 
                        title: 'Share the Joy with Fiesta Meals!', 
                        image: 'hero3.jpg'
                    }
                ],
                init() {
                    lucide.createIcons();
                },
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

                    // Manual Validation
                    if (action === 'signup') {
                        if (!this.fname || !this.lname || !this.mobile || !this.email || !this.password) {
                            this.message = { type: 'error', text: 'Please fill out all information to proceed.' };
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                            return;
                        }

                        // Mobile Validation: 11 digits and only numbers
                        const mobileRegex = /^[0-9]{11}$/;
                        if (!mobileRegex.test(this.mobile)) {
                            this.message = { type: 'error', text: 'Mobile number must be exactly 11 digits and only contain numbers (e.g., 09123456789).' };
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                            return;
                        }

                        if (!this.email.includes('@')) {
                            this.message = { type: 'error', text: 'Please enter a valid email address with @ symbol for signup.' };
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                            return;
                        }
                    } else if (action === 'login') {
                        if (!this.email || !this.password) {
                            this.message = { type: 'error', text: 'Please enter both email and password.' };
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                            return;
                        }
                    }
                    
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
