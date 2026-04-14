<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import BottomNavBar from '@/Components/BottomNavBar.vue';
import { ref, computed, onMounted, onUnmounted, provide, watch } from 'vue';
import { useAutoLogout } from '@/Composables/useAutoLogout';
import { useToast } from '@/Composables/useToast';
import ToastList from '@/Components/Toasts/ToastList.vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';
import AntiAdblockGuard from '@/Components/Ads/AntiAdblockGuard.vue';
import AntiInspectGuard from '@/Components/Security/AntiInspectGuard.vue';

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
const theme = ref(localStorage.getItem('theme') || 'dark');

provide('isNavigating', isNavigating);

// Template Engine: detect active UI template from admin settings
const isSpark = computed(() => page.props.ui_template === 'spartankobs');
provide('isSpark', isSpark);

router.on('start', () => isNavigating.value = true);
router.on('finish', () => isNavigating.value = false);

useAutoLogout(30); // 30 minutes idle timeout

const toggleTheme = () => {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', theme.value);
    document.documentElement.setAttribute('data-theme', theme.value);
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    document.documentElement.setAttribute('data-theme', theme.value);
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="min-h-screen transition-colors duration-500 overflow-x-hidden" :style="{ backgroundColor: 'rgb(var(--bg-main))', color: 'rgb(var(--text-main))' }">
        <!-- Floating Modern Navbar -->
        <div class="fixed top-0 left-0 right-0 z-50 px-2 md:px-4 pt-2 md:pt-4 transition-all duration-300">
            <nav :class="[
                'max-w-7xl mx-auto rounded-3xl transition-all duration-700 overflow-hidden',
                isScrolled ? 'glass shadow-royale py-2' : 'bg-transparent py-4'
            ]">
                <div class="px-6 flex justify-between items-center h-14">
                    <!-- Brand Section -->
                    <div class="flex items-center gap-4 shrink-0">
                        <Link :href="route('home')" class="group flex items-center gap-2">
                            <ApplicationLogo class="h-9 w-auto fill-current text-indigo-500 transition-transform group-hover:scale-110" />
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-2 flex-1 px-12">
                        <Link :href="route('home')" class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all hover:bg-indigo-500/5" :class="route().current('home') ? 'text-indigo-500 bg-indigo-500/5 shadow-inner' : 'text-[rgb(var(--text-muted))] hover:text-indigo-400'">
                            JELAJAHI
                        </Link>
                    </div>

                    <!-- Actions -->
                    <div class="hidden md:flex items-center gap-4">
                        <!-- Theme Toggle (Royale Style) -->
                        <button 
                            @click="toggleTheme" 
                            class="p-3 rounded-2xl transition-all duration-500 hover:scale-110 active:scale-90 flex items-center justify-center bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] shadow-inner"
                            :title="theme === 'dark' ? 'Aktifkan Mode Terang' : 'Aktifkan Mode Gelap'"
                        >
                            <svg v-if="theme === 'dark'" class="w-5 h-5 text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                            <svg v-else class="w-5 h-5 text-indigo-600 drop-shadow-[0_0_8px_rgba(79,70,229,0.3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                        </button>

                        <template v-if="$page.props.auth.user">
                            <div class="flex items-center gap-3">
                                <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" class="text-[9px] font-black uppercase tracking-[0.3em] text-[rgb(var(--text-muted))] hover:text-indigo-400 transition hidden lg:block">ADMIN</Link>
                                <div class="h-4 w-px bg-[rgb(var(--border-main))] mx-1 hidden lg:block"></div>
                                <Link :href="route('dashboard')" class="btn-premium flex items-center gap-3 !px-5 !py-2.5">
                                    <span class="text-[10px] font-black uppercase tracking-widest">{{ $page.props.auth.user.name }}</span>
                                    <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-[10px] shadow-inner">👤</div>
                                </Link>
                                <div class="h-4 w-px bg-[rgb(var(--border-main))] mx-1 hidden lg:block"></div>
                                <Link :href="route('logout')" method="post" as="button" class="text-[10px] font-black uppercase tracking-widest text-rose-500/60 hover:text-rose-500 transition hidden lg:block">
                                    KELUAR
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-[10px] font-black uppercase tracking-widest text-[rgb(var(--text-muted))] hover:text-indigo-400 transition px-5 py-2.5 rounded-2xl border border-transparent hover:border-[rgb(var(--border-main))]">SIGN IN</Link>
                            <Link :href="route('register')" class="btn-premium">GET STARTED</Link>
                        </template>
                    </div>

                    <!-- Hamburger (Mobile Only) -->
                    <div class="md:hidden flex-shrink-0 ml-4">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-3 rounded-2xl bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] shadow-inner transition-all active:scale-90">
                            <div class="w-6 h-5 flex flex-col justify-between items-end relative overflow-hidden">
                                <span :class="['h-0.5 bg-indigo-500 transition-all duration-500 rounded-full', showingNavigationDropdown ? 'w-6 absolute top-2 rotate-45' : 'w-6']"></span>
                                <span :class="['h-0.5 bg-[rgb(var(--text-main))] transition-all duration-300 rounded-full', showingNavigationDropdown ? 'opacity-0 translate-x-12' : 'w-4']"></span>
                                <span :class="['h-0.5 bg-indigo-500 transition-all duration-500 rounded-full', showingNavigationDropdown ? 'w-6 absolute top-2 -rotate-45' : 'w-5']"></span>
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
                 class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-md md:hidden"
                 @click="showingNavigationDropdown = false"
            ></div>
        </Transition>

        <!-- Mobile Drawer Content -->
        <Transition
            enter-active-class="transition duration-700 cubic-bezier(0.4, 0, 0.2, 1)"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-500 cubic-bezier(0.4, 0, 0.2, 1)"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showingNavigationDropdown" 
                 class="fixed top-0 right-0 bottom-0 w-[85%] max-w-md z-[70] glass border-l border-[rgb(var(--border-main))] shadow-royale md:hidden overflow-y-auto"
            >
                <div class="p-10 space-y-12">
                    <!-- Brand -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo class="h-9 w-auto fill-current text-indigo-500" />
                            <span class="text-xl font-black italic tracking-tighter uppercase" :style="{ color: 'rgb(var(--text-main))' }">VIDEYVIEW</span>
                        </div>
                        <button @click="showingNavigationDropdown = false" class="p-2.5 rounded-2xl bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] text-[rgb(var(--text-muted))] hover:text-indigo-400 transition active:scale-90">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- User Profile Quick View -->
                    <div v-if="page.props.auth.user" class="p-8 rounded-[40px] bg-indigo-500/5 border border-indigo-500/10 shadow-inner">
                        <div class="flex items-center gap-5 mb-6">
                            <div class="w-14 h-14 rounded-[20px] bg-indigo-600 flex items-center justify-center text-xl shadow-2xl relative">
                                👤
                                <div v-if="page.props.auth.user.has_active_subscription" class="absolute -top-1 -right-1 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center text-[10px] border-2 border-[rgb(var(--bg-surface))]">👑</div>
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="text-lg font-black text-[rgb(var(--text-main))] truncate tracking-tight uppercase">{{ page.props.auth.user.name }}</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="page.props.auth.user.has_active_subscription" class="text-[8px] font-black bg-gradient-to-r from-amber-400 to-amber-600 text-amber-950 px-2 py-0.5 rounded-full uppercase">GOLD MEMBER</span>
                                    <span class="text-[9px] text-[rgb(var(--text-muted))] font-black uppercase tracking-widest">{{ page.props.auth.user.is_admin ? 'STAFF' : 'MEMBER' }}</span>
                                </div>
                            </div>
                        </div>
                        <Link :href="route('dashboard')" class="btn-premium w-full flex items-center justify-center gap-2" @click="showingNavigationDropdown = false">
                             MY DASHBOARD <span>→</span>
                        </Link>
                    </div>
                                    <!-- Navigation Links -->
                    <div class="flex flex-col gap-10">
                        <!-- Top Navigation -->
                        <div>
                            <div class="text-[10px] font-black text-[rgb(var(--text-muted))] uppercase tracking-widest mb-4 ps-2">Main Menu</div>
                            <Link :href="route('home')" @click="showingNavigationDropdown = false"
                                  class="group flex items-center justify-between text-2xl font-black italic tracking-tighter transition-all hover:translate-x-2" 
                                  :class="route().current('home') && !page.props.currentFilter ? 'text-indigo-400' : 'text-[rgb(var(--text-main))]'">
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
                            <div class="text-[10px] font-black text-[rgb(var(--text-muted))] uppercase tracking-widest mb-4 ps-2">Explore Categories</div>
                            <div class="flex flex-col gap-5 ps-2">
                                <Link v-for="cat in ['all', 'trending', 'free', 'premium']" :key="cat" 
                                    :href="route('home', { filter: cat })" 
                                    @click="showingNavigationDropdown = false"
                                    class="flex items-center justify-between text-lg font-black uppercase tracking-widest transition-all hover:text-indigo-400"
                                    :class="(page.props.currentFilter === cat) || (cat === 'all' && !page.props.currentFilter) ? 'text-indigo-400 scale-105' : 'text-[rgb(var(--text-muted))]'"
                                >
                                    <span>{{ cat }}</span>
                                    <span v-if="cat === 'premium'" class="text-[9px] bg-amber-600 text-white px-2 py-0.5 rounded-full shadow-lg shadow-amber-600/20">👑</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Account Section -->
                        <div class="h-px bg-[rgb(var(--border-main))] w-full"></div>
                        
                        <template v-if="page.props.auth.user">
                            <Link v-if="page.props.auth.user.is_admin" :href="route('admin.videos.index')" @click="showingNavigationDropdown = false"
                                  class="flex items-center gap-4 text-lg font-bold text-[rgb(var(--text-muted))] hover:text-indigo-400 transition group">
                                <span class="w-10 h-10 rounded-2xl bg-indigo-500/5 flex items-center justify-center text-sm group-hover:bg-indigo-500/20 transition-colors">🛠</span>
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
                                  class="flex items-center gap-4 text-xl font-black text-[rgb(var(--text-muted))] hover:text-indigo-400 transition uppercase tracking-tighter group">
                                <span class="w-12 h-12 rounded-[20px] bg-indigo-500/5 flex items-center justify-center text-sm group-hover:bg-indigo-500/10 transition-colors">🔑</span>
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
                         <div class="h-px bg-[rgb(var(--border-main))] w-12"></div>
                         <div class="flex items-center gap-2 opacity-20 filter grayscale">
                            <ApplicationLogo class="h-5 w-auto fill-current" />
                            <span class="text-[9px] font-black uppercase tracking-[0.3em] font-muted">VideyView 5.0</span>
                         </div>
                    </div>
                </div>
            </div>
        </Transition>

        <main class="pt-24 pb-12 safe-bottom transition-all duration-500">
            <div v-if="$slots.header" class="max-w-7xl mx-auto px-3 sm:px-6 mb-6 lg:mb-12">
                <slot name="header" />
            </div>
            <slot />
        </main>
        
        <footer class="mt-auto py-8 lg:py-12 border-t border-[rgb(var(--border-main))] bg-[rgb(var(--bg-main))] transition-colors duration-500 hidden lg:block">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2 opacity-50">
                    <ApplicationLogo class="h-6 w-auto fill-current text-[rgb(var(--text-main))]" />
                    <span class="font-black tracking-tighter text-sm uppercase text-[rgb(var(--text-main))]">VideyView</span>
                </div>
                <p class="text-[rgb(var(--text-muted))] text-sm">&copy; 2026 Crafted with ❤️ for VideyView.</p>
                <div class="flex gap-6 text-[rgb(var(--text-muted))] text-sm font-bold">
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

        <!-- Anti-Adblock Guard (Public Pages Only) -->
        <AntiAdblockGuard v-if="$page.props.anti_adblock_enabled && !$page.props.auth.user?.is_admin" />
        
        <!-- Anti-Inspect Pro Security (Guest/Non-Admin Only) -->
        <AntiInspectGuard v-if="!$page.props.auth.user?.is_admin" />

        <!-- Mobile Bottom Navigation (Both Templates) -->
        <BottomNavBar />
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
