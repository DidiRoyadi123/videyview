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
import { watch } from 'vue';

const { success: toastSuccess, error: toastError, info: toastInfo } = useToast();
const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toastSuccess(flash.success);
    if (flash?.error) toastError(flash.error);
    if (flash?.message) toastInfo(flash.message);
}, { deep: true, immediate: true });

const showingNavigationDropdown = ref(false);

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

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dasbor
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" :active="route().current('admin.videos.index')">
                                Video
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.bulk-sync')" :active="route().current('admin.videos.bulk-sync')">
                                Sinkronisasi Massal
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.extractor')" :active="route().current('admin.videos.extractor')">
                                Ekstraktor
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.users.index')" :active="route().current('admin.users.*')">
                                Pengguna
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.settings.index')" :active="route().current('admin.settings.*')">
                                Konfigurasi
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.logs.index')" :active="route().current('admin.logs.*')">
                                Log Sistem
                            </NavLink>
                            <NavLink :href="route('home')">
                                Situs Publik
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
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

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden glass-dark border-t border-white/5">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')"> Dasbor </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" :active="route().current('admin.videos.index')"> Kelola Video </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.bulk-sync')" :active="route().current('admin.videos.bulk-sync')"> Sinkronisasi Massal Manual </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.extractor')" :active="route().current('admin.videos.extractor')"> Ekstraktor Tautan </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.users.index')" :active="route().current('admin.users.*')"> Kelola Pengguna </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.settings.index')" :active="route().current('admin.settings.*')"> Konfigurasi Sistem </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.logs.index')" :active="route().current('admin.logs.*')"> Log Sistem </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('home')"> Situs Publik </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-white/5 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-white">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-slate-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profil </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Keluar </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <slot name="header" />
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

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
