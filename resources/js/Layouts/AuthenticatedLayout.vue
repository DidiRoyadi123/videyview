<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useAutoLogout } from '@/Composables/useAutoLogout';
import { useToast } from '@/Composables/useToast';
import ToastList from '@/Components/Toasts/ToastList.vue';
import AdDefenseShield from '@/Components/Security/AdDefenseShield.vue';
import { watch } from 'vue';

const { success: toastSuccess, error: toastError, info: toastInfo } = useToast();
const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toastSuccess(flash.success);
    if (flash?.error) toastError(flash.error);
    if (flash?.message) toastInfo(flash.message);
}, { deep: true, immediate: true });

const showingNavigationDropdown = ref(false);
const showingSidebar = ref(false);

// Theme Engine v3.0
const activeTheme = ref(localStorage.getItem('admin_aura') || 'indigo');
const themes = {
    indigo: { primary: '#6366f1', glow: 'rgba(99, 102, 241, 0.15)' },
    emerald: { primary: '#10b981', glow: 'rgba(16, 185, 129, 0.15)' },
    amber: { primary: '#f59e0b', glow: 'rgba(245, 158, 11, 0.15)' },
    rose: { primary: '#f43f5e', glow: 'rgba(244, 63, 94, 0.15)' }
};

const applyTheme = (themeName) => {
    activeTheme.value = themeName;
    localStorage.setItem('admin_aura', themeName);
};

onMounted(() => {
    if (!localStorage.getItem('admin_aura')) {
        localStorage.setItem('admin_aura', 'indigo');
    }
});

useAutoLogout(30); // 30 minutes idle timeout
</script>

<template>
    <div 
        class="min-h-screen transition-colors duration-500"
        :style="{
            backgroundColor: 'rgb(var(--bg-main))',
            color: 'rgb(var(--text-main))',
            '--brand-color': themes[activeTheme].primary,
            '--brand-glow': themes[activeTheme].glow
        }"
    >
        <nav class="glass border-b border-[rgb(var(--border-main))] sticky top-0 z-50 transition-colors duration-500">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-8 w-auto fill-current brand-text-color" />
                            </Link>
                        </div>

                        <!-- Desktop Hamburger Toggle -->
                        <div class="flex items-center ms-4">
                            <button 
                                @click="showingSidebar = !showingSidebar" 
                                class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-[var(--brand-color)] border border-white/10 hover:border-[var(--brand-color)]/40 transition-all group"
                            >
                                <svg class="h-5 w-5 group-hover:scale-110 transition-transform" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <NavLink :href="route('home')" class="mr-6 !border-none !text-[10px] !font-black !uppercase !tracking-widest !text-slate-500 hover:!text-[var(--brand-color)]">
                            Situs Publik
                        </NavLink>
                        <!-- Theme Toggle Quick Menu -->
                        <div class="flex items-center gap-2 mr-6 px-3 py-1 bg-white/5 rounded-full border border-white/5">
                            <button 
                                v-for="(theme, name) in themes" 
                                :key="name"
                                @click="applyTheme(name)"
                                class="w-4 h-4 rounded-full transition-all duration-300 hover:scale-125"
                                :class="{'ring-2 ring-white scale-110 shadow-lg shadow-white/10': activeTheme === name}"
                                :style="{ backgroundColor: theme.primary }"
                                :title="'Aura ' + name"
                            ></button>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="btn-aura flex items-center gap-2 !py-2 !px-4 !text-xs">
                                            {{ $page.props.auth.user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <div class="glass-dark border border-white/5 rounded-xl overflow-hidden mt-2 p-1">
                                        <DropdownLink :href="route('profile.edit')" class="!text-slate-300 hover:!bg-white/5 rounded-lg"> Profil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="!text-red-400 hover:!bg-red-500/10 rounded-lg"> Keluar </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Mobile Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingSidebar = !showingSidebar" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 border border-white/10">
                            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar Drawer Overlay -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showingSidebar" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" @click="showingSidebar = false"></div>
        </Transition>

        <!-- Sidebar Drawer Panel -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div v-if="showingSidebar" class="fixed inset-y-0 left-0 z-[60] w-72 glass-dark border-r border-white/10 shadow-2xl flex flex-col overflow-y-auto">
                    <!-- Logo & Close -->
                    <div class="px-5 py-4 flex items-center justify-between border-b border-white/10">
                        <ApplicationLogo class="h-7 w-auto fill-current brand-text-color" />
                        <button @click="showingSidebar = false" class="p-1.5 text-slate-400 hover:text-white hover:bg-white/10 rounded-lg transition-all">
                            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 px-3 py-3 space-y-0.5">
                        <div class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase text-slate-500 tracking-widest">Navigasi Utama</div>
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                            <div class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span class="font-semibold">Dasbor</span>
                            </div>
                        </ResponsiveNavLink>

                        <template v-if="$page.props.auth.user.is_admin">
                            <div class="px-3 pt-4 pb-1 text-[10px] font-bold uppercase text-slate-500 tracking-widest">Logistik Video</div>
                            <ResponsiveNavLink :href="route('admin.videos.index')" :active="route().current('admin.videos.index')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span class="font-semibold">Vault (Gudang)</span>
                                </div>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.categories.index')" :active="route().current('admin.categories.*')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <span class="font-semibold">Kategori</span>
                                </div>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.videos.bulk-sync')" :active="route().current('admin.videos.bulk-sync')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span class="font-semibold">Sinkronisasi</span>
                                </div>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.videos.extractor')" :active="route().current('admin.videos.extractor')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    <span class="font-semibold">Ekstraktor</span>
                                </div>
                            </ResponsiveNavLink>

                            <div class="px-3 pt-4 pb-1 text-[10px] font-bold uppercase text-slate-500 tracking-widest">Manajemen Sistem</div>
                            <ResponsiveNavLink :href="route('admin.users.index')" :active="route().current('admin.users.*')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span class="font-semibold">Pengguna</span>
                                </div>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.settings.index')" :active="route().current('admin.settings.*')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="font-semibold">Pengaturan</span>
                                </div>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.logs.index')" :active="route().current('admin.logs.*')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="font-semibold">Log Sistem</span>
                                </div>
                            </ResponsiveNavLink>
                        </template>

                        <div class="px-3 pt-4 pb-1 text-[10px] font-bold uppercase text-slate-500 tracking-widest">Akun & Keamanan</div>
                        <ResponsiveNavLink :href="route('profile.edit')" :active="route().current('profile.edit')" @click="showingSidebar = false" class="!py-2.5 !px-3 !rounded-lg !text-sm">
                            <div class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="font-semibold">Profil Saya</span>
                            </div>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="!text-red-400 !py-2.5 !px-3 !rounded-lg !text-sm">
                            <div class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span class="font-semibold">Keluar Sistem</span>
                            </div>
                        </ResponsiveNavLink>
                    </div>

                    <!-- Sidebar Footer -->
                    <div class="px-4 py-4 border-t border-white/10 bg-black/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center font-bold text-sm text-indigo-400">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-bold text-white truncate">{{ $page.props.auth.user.name }}</span>
                                <span class="text-xs text-slate-500 font-medium">{{ $page.props.auth.user.is_admin ? 'Super Admin' : 'Member' }}</span>
                            </div>
                        </div>
                        <NavLink :href="route('home')" class="!text-xs !font-semibold !text-[var(--brand-color)] flex items-center gap-2 !border-none hover:opacity-80 transition-opacity">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Situs Publik
                        </NavLink>
                    </div>
            </div>
        </Transition>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <slot name="header" />
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <AdDefenseShield />

        <ToastList />
    </div>
</template>

<style>
/* Global Aura Overrides */
:root {
    --brand-color: #6366f1;
    --brand-glow: rgba(99, 102, 241, 0.15);
}

.brand-bg-color { background-color: var(--brand-color) !important; }
.brand-text-color { color: var(--brand-color) !important; }
.brand-border-color { border-color: var(--brand-color) !important; }

.nav-link-active {
    color: var(--brand-color) !important;
    border-color: var(--brand-color) !important;
}

.btn-aura {
    @apply bg-white/5 text-slate-200 border border-white/10 rounded-xl transition-all duration-300;
    box-shadow: 0 4px 12px var(--brand-glow);
}

.btn-aura:hover {
    @apply bg-white/10 border-[var(--brand-color)];
    box-shadow: 0 4px 20px var(--brand-glow);
}

/* Override premium buttons to use aura */
.btn-premium {
    background: linear-gradient(135deg, var(--brand-color) 0%, #1e293b 100%) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
}
</style>
