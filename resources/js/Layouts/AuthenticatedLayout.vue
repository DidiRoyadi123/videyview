<script setup>
import { ref } from 'vue';
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

useAutoLogout(30); // 30 minutes idle timeout
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-200">
        <nav class="glass-dark border-b border-white/5 sticky top-0 z-50">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-8 w-auto fill-current text-indigo-500" />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" :active="route().current('admin.videos.index')">
                                Videos
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.extractor')" :active="route().current('admin.videos.extractor')">
                                Extractor
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.users.index')" :active="route().current('admin.users.*')">
                                Users
                            </NavLink>
                            <NavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.settings.index')" :active="route().current('admin.settings.*')">
                                Ads
                            </NavLink>
                            <NavLink :href="route('home')">
                                Public Site
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="btn-premium flex items-center gap-2 !py-2 !px-4 !text-xs">
                                            {{ $page.props.auth.user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <div class="glass-dark border border-white/5 rounded-xl overflow-hidden mt-2 p-1">
                                        <DropdownLink :href="route('profile.edit')" class="!text-slate-300 hover:!bg-white/5 rounded-lg"> Profile </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="!text-red-400 hover:!bg-red-500/10 rounded-lg"> Log Out </DropdownLink>
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
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')"> Dashboard </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.index')" :active="route().current('admin.videos.index')"> Manage Videos </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.videos.extractor')" :active="route().current('admin.videos.extractor')"> Link Extractor </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.users.index')" :active="route().current('admin.users.*')"> Manage Users </ResponsiveNavLink>
                    <ResponsiveNavLink v-if="$page.props.auth.user.is_admin" :href="route('admin.settings.index')" :active="route().current('admin.settings.*')"> Ad Settings </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('home')"> Public Site </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-white/5 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-white">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-slate-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Log Out </ResponsiveNavLink>
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
/* Global Nav Overrides for Dark Mode */
.nav-link-active {
    @apply text-indigo-400 border-indigo-500 !important;
}
</style>
