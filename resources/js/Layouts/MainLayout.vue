<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { ref, onMounted, onUnmounted, provide, watch } from 'vue';
import { useAutoLogout } from '@/Composables/useAutoLogout';
import { useToast } from '@/Composables/useToast';
import ToastList from '@/Components/Toasts/ToastList.vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';
import AntiAdblockGuard from '@/Components/Ads/AntiAdblockGuard.vue';

const { success: toastSuccess, error: toastError, info: toastInfo } = useToast();
const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toastSuccess(flash.success);
    if (flash?.error) toastError(flash.error);
    if (flash?.message) toastInfo(flash.message);
}, { deep: true, immediate: true });

const showingNavigationDropdown = ref(false);
const isScrolled = ref(false);
const isNavigating = ref(false);

provide('isNavigating', isNavigating);

router.on('start', () => isNavigating.value = true);
router.on('finish', () => isNavigating.value = false);

useAutoLogout(30); // 30 minutes idle timeout

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-200 overflow-x-hidden">
        <!-- Floating Modern Navbar -->
        <div class="fixed top-0 left-0 right-0 z-50 px-2 md:px-4 pt-2 md:pt-4 transition-all duration-300">
            <nav :class="[
                'max-w-7xl mx-auto rounded-2xl md:rounded-3xl transition-all duration-500',
                isScrolled ? 'glass-dark py-2' : 'bg-slate-950/80 backdrop-blur-xl md:bg-transparent py-2 md:py-4 border border-white/5 md:border-transparent'
            ]">
                <div class="px-4 md:px-6 flex justify-between items-center h-12 md:h-14">
                    <!-- Brand Section -->
                    <div class="flex items-center gap-4 shrink-0">
                        <Link :href="route('home')" class="group flex items-center gap-2">
                            <ApplicationLogo class="h-8 w-auto fill-current text-indigo-500 transition-transform group-hover:scale-110" />
                            <span class="font-black text-lg md:text-xl tracking-tighter text-white group-hover:text-indigo-400 transition-colors">VIDEY<span class="text-indigo-500">VIEW</span></span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation (Center/Right) -->
                    <div class="hidden md:flex items-center gap-1 flex-1 px-8">
                        <Link :href="route('home')" class="px-4 py-2 rounded-xl text-sm font-bold transition-all hover:bg-white/5" :class="route().current('home') ? 'text-indigo-400 bg-white/5' : 'text-slate-400'">
                            Discover
                        </Link>
                    </div>

                    <!-- Desktop Actions -->
                    <div class="hidden md:flex items-center gap-4">
                        <template v-if="$page.props.auth.user">
                            <div class="flex items-center gap-2">
                                <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" class="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-white transition">Admin</Link>
                                <div class="h-4 w-px bg-slate-800 mx-2"></div>
                                <Link :href="route('dashboard')" class="btn-premium flex items-center gap-2">
                                    <span class="text-xs font-black">{{ $page.props.auth.user.name }}</span>
                                    <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">👤</div>
                                </Link>
                                <div class="h-4 w-px bg-slate-800 mx-2"></div>
                                <Link :href="route('logout')" method="post" as="button" class="text-[10px] font-black uppercase tracking-widest text-red-500/60 hover:text-red-500 transition">
                                    Sign Out
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <a v-if="!$page.props.auth.user?.is_admin && $page.props.ads?.smartlink" :href="$page.props.ads.smartlink" target="_blank" class="text-xs font-black text-emerald-400 hover:text-emerald-300 transition uppercase tracking-widest px-4 border-r border-white/5 mr-2">🚀 Special Offer</a>
                            <Link :href="route('login')" class="text-sm font-bold text-slate-400 hover:text-white transition px-4 py-2">Sign In</Link>
                            <Link :href="route('register')" class="btn-premium">Get Started</Link>
                        </template>
                    </div>

                    <!-- Hamburger (Visible on Mobile Only) -->
                    <div class="md:hidden flex-shrink-0 ml-4">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="relative group p-2.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all active:scale-95 shadow-lg">
                            <div class="w-6 h-5 flex flex-col justify-between items-end relative overflow-hidden">
                                <span :class="['h-0.5 bg-white transition-all duration-300 rounded-full', showingNavigationDropdown ? 'w-6 absolute top-2 rotate-45' : 'w-6']"></span>
                                <span :class="['h-0.5 bg-indigo-500 transition-all duration-300 rounded-full', showingNavigationDropdown ? 'opacity-0 translate-x-12' : 'w-4']"></span>
                                <span :class="['h-0.5 bg-white transition-all duration-300 rounded-full', showingNavigationDropdown ? 'w-6 absolute top-2 -rotate-45' : 'w-5']"></span>
                            </div>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Premium Mobile Drawer Overlay -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-400 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showingNavigationDropdown" 
                 class="fixed inset-0 z-[60] bg-slate-950/60 backdrop-blur-md md:hidden"
                 @click="showingNavigationDropdown = false"
            ></div>
        </Transition>

        <!-- Mobile Drawer Content -->
        <Transition
            enter-active-class="transition duration-500 cubic-bezier(0.4, 0, 0.2, 1)"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-400 cubic-bezier(0.4, 0, 0.2, 1)"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showingNavigationDropdown" 
                 class="fixed top-0 right-0 bottom-0 w-[80%] max-w-sm z-[70] glass-dark border-l border-white/10 shadow-[-20px_0_100px_rgba(0,0,0,0.5)] md:hidden overflow-y-auto"
            >
                <div class="p-8 space-y-12">
                    <!-- Brand -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo class="h-8 w-auto fill-current text-indigo-500" />
                            <span class="font-black text-xl tracking-tighter text-white">VIDEY<span class="text-indigo-500">VIEW</span></span>
                        </div>
                        <button @click="showingNavigationDropdown = false" class="p-2 text-slate-400 hover:text-white transition">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- User Profile Quick View -->
                    <div v-if="page.props.auth.user" class="p-6 rounded-[32px] bg-gradient-to-br from-indigo-500/10 to-transparent border border-indigo-500/20 shadow-inner">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-xl shadow-xl">👤</div>
                            <div class="overflow-hidden">
                                <h3 class="font-black text-white truncate uppercase tracking-tight">{{ page.props.auth.user.name }}</h3>
                                <div class="flex items-center gap-2">
                                    <span v-if="page.props.auth.user.has_active_subscription" class="text-[8px] font-black bg-gradient-to-r from-amber-400 to-amber-600 text-amber-950 px-2 py-0.5 rounded-full uppercase tracking-tighter">Gold Member</span>
                                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">{{ page.props.auth.user.is_admin ? 'Staff' : 'Viewer' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="h-px bg-white/5 w-full mb-4"></div>
                        <Link :href="route('dashboard')" class="text-xs font-black text-indigo-400 hover:text-indigo-300 transition uppercase tracking-widest flex items-center gap-2" @click="showingNavigationDropdown = false">
                             My Profile <span>→</span>
                        </Link>
                    </div>
                                    <!-- Navigation Links -->
                    <div class="flex flex-col gap-10">
                        <!-- Top Navigation -->
                        <div>
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 ps-2">Main Menu</div>
                            <Link :href="route('home')" @click="showingNavigationDropdown = false"
                                  class="group flex items-center justify-between text-2xl font-black italic tracking-tighter transition-all hover:translate-x-2" 
                                  :class="route().current('home') && !page.props.currentFilter ? 'text-indigo-400' : 'text-white'">
                                DISCOVER ALL
                            </Link>

                            <a v-if="!$page.props.auth.user?.has_active_subscription && !$page.props.auth.user?.is_admin && $page.props.ads?.smartlink" 
                               :href="$page.props.ads.smartlink" target="_blank"
                               class="mt-4 flex items-center justify-between p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black uppercase tracking-widest text-xs shadow-lg shadow-emerald-500/5 transition-transform active:scale-95"
                            >
                                <span>🚀 Special Offer</span>
                                <span class="animate-pulse">🔥</span>
                            </a>
                        </div>

                        <!-- Categorization -->
                        <div>
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 ps-2">Explore Categories</div>
                            <div class="flex flex-col gap-5 ps-2">
                                <Link v-for="cat in ['all', 'trending', 'free', 'premium']" :key="cat" 
                                    :href="route('home', { filter: cat })" 
                                    @click="showingNavigationDropdown = false"
                                    class="flex items-center justify-between text-lg font-black uppercase tracking-widest transition-all hover:text-indigo-400"
                                    :class="(page.props.currentFilter === cat) || (cat === 'all' && !page.props.currentFilter) ? 'text-indigo-400 scale-105' : 'text-slate-400'"
                                >
                                    <span>{{ cat }}</span>
                                    <span v-if="cat === 'premium'" class="text-[9px] bg-amber-600 text-white px-2 py-0.5 rounded-full shadow-lg shadow-amber-600/20">👑</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Account Section -->
                        <div class="h-px bg-white/5 w-full"></div>
                        
                        <template v-if="page.props.auth.user">
                            <Link v-if="page.props.auth.user.is_admin" :href="route('admin.videos.index')" @click="showingNavigationDropdown = false"
                                  class="flex items-center gap-4 text-lg font-bold text-slate-400 hover:text-white transition group">
                                <span class="w-10 h-10 rounded-2xl bg-white/5 flex items-center justify-center text-sm group-hover:bg-indigo-500/20 transition-colors">🛠</span>
                                Admin Terminal
                            </Link>

                            <Link :href="route('logout')" method="post" as="button" 
                                  class="flex items-center gap-4 text-lg font-bold text-red-400/60 hover:text-red-400 transition text-left w-full group">
                                <span class="w-10 h-10 rounded-2xl bg-red-500/5 flex items-center justify-center text-sm group-hover:bg-red-500/20 transition-colors">🚪</span>
                                Sign Out
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" @click="showingNavigationDropdown = false"
                                  class="flex items-center gap-4 text-xl font-black text-slate-400 hover:text-white transition uppercase tracking-tighter group">
                                <span class="w-12 h-12 rounded-[20px] bg-white/5 flex items-center justify-center text-sm group-hover:bg-white/10 transition-colors">🔑</span>
                                Login
                            </Link>
                            <Link :href="route('register')" @click="showingNavigationDropdown = false"
                                  class="btn-premium w-full text-center mt-2 h-14 flex items-center justify-center text-sm font-black shadow-2xl shadow-indigo-500/10">
                                JOIN THE VAULT
                            </Link>
                        </template>
                    </div>

                    <!-- Footer / Version -->
                    <div class="pt-8 flex flex-col items-center gap-4">
                         <div class="h-px bg-white/5 w-12"></div>
                         <div class="flex items-center gap-2 opacity-20 filter grayscale">
                            <ApplicationLogo class="h-5 w-auto fill-current" />
                            <span class="text-[9px] font-black uppercase tracking-[0.3em]">VideyView 4.0</span>
                         </div>
                    </div>
                </div>
            </div>
        </Transition>

        <main class="pt-24 pb-12">
            <div v-if="$slots.header" class="max-w-7xl mx-auto px-6 mb-12">
                <slot name="header" />
            </div>
            <slot />
        </main>
        
        <footer class="mt-auto py-12 border-t border-slate-900 bg-slate-950">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2 opacity-50">
                    <ApplicationLogo class="h-6 w-auto fill-current" />
                    <span class="font-black tracking-tighter text-sm uppercase">VideyView</span>
                </div>
                <p class="text-slate-600 text-sm">&copy; 2026 Crafted with ❤️ for VideyView.</p>
                <div class="flex gap-6 text-slate-500 text-sm font-bold">
                    <a href="#" class="hover:text-indigo-400 transition">Telegram</a>
                    <a href="#" class="hover:text-indigo-400 transition">Twitter</a>
                    <a href="#" class="hover:text-indigo-400 transition">Privacy</a>
                </div>
            </div>
        </footer>

        <ToastList />

        <!-- Global Ads -->
        <template v-if="$page.props.ads && !$page.props.auth.user?.is_admin">
            <AdHandler 
                v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.popunder" 
                :adCode="$page.props.ads.popunder" 
                type="popunder" 
            />
            <AdHandler 
                v-if="$page.props.ads.social_bar" 
                :adCode="$page.props.ads.social_bar" 
                :is-isolated="true"
                type="social_bar" 
            />
        </template>

        <AntiAdblockGuard />
    </div>
</template>

<style scoped>
.nav-link-active {
    @apply text-indigo-400 bg-white/5;
}
.btn-premium {
    @apply px-5 py-2.5 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black text-xs uppercase tracking-widest shadow-lg shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95 hover:shadow-indigo-500/40;
}
.glass-dark {
    @apply bg-slate-950/80 backdrop-blur-xl border border-white/5;
}
</style>
