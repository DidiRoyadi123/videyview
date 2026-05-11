<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ToastList from '@/Components/Toasts/ToastList.vue';

const page = usePage();
const showingSidebar = ref(true);
const showingMobileMenu = ref(false);
const searchQuery = ref(page.props.filters?.search || '');

const handleSearch = () => {
    router.get(route('admin.videos.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true
    });
};

const toggleSidebar = () => {
    showingSidebar.value = !showingSidebar.value;
};

const toggleMobileMenu = () => {
    showingMobileMenu.value = !showingMobileMenu.value;
};

// Menu structure inspired by Materio
const menuGroups = [
    {
        title: 'Dashboards',
        items: [
            { name: 'Analytics', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        ]
    },
    {
        title: 'Logistics',
        items: [
            { name: 'Video Vault', route: 'admin.videos.index', icon: 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
            { name: 'Categories', route: 'admin.categories.index', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
            { name: 'Bulk Sync', route: 'admin.videos.bulk-sync', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
            { name: 'Bulk Editor', route: 'admin.videos.bulk-edit', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
            { name: 'Mirror Watch', route: 'admin.videos.mirrors', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
            { name: 'Extractor', route: 'admin.videos.extractor', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
            { name: 'Bulk Pro', route: 'admin.videos.bulk-upload', icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12' },
        ]
    },
    {
        title: 'System',
        items: [
            { name: 'Users', route: 'admin.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { name: 'Settings', route: 'admin.settings.index', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
            { name: 'System Logs', route: 'admin.logs.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1.01.293.707V19a2 2 0 01-2 2z' },
        ]
    }
];

onMounted(() => {
    document.documentElement.setAttribute('data-theme', 'materio');
});
</script>

<template>
    <div class="min-h-screen bg-[rgb(var(--materio-bg))] text-[rgb(var(--materio-text))] font-inter flex overflow-x-hidden">
        
        <!-- Desktop Sidebar -->
        <aside 
            :class="[
                'fixed inset-y-0 left-0 z-50 bg-white shadow-xl transition-all duration-300 ease-in-out border-r border-gray-100 hidden lg:flex flex-col',
                showingSidebar ? 'w-64' : 'w-20 overflow-hidden'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center px-6 border-b border-gray-50 flex-shrink-0">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[#8C57FF] rounded-lg flex items-center justify-center shadow-lg shadow-[#8C57FF]/30">
                        <ApplicationLogo class="w-5 h-5 fill-white" />
                    </div>
                    <span v-if="showingSidebar" class="font-bold text-xl tracking-tight text-[#3A3541]">VideyView</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-8 no-scrollbar">
                <div v-for="group in menuGroups" :key="group.title">
                    <h4 v-if="showingSidebar" class="px-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">{{ group.title }}</h4>
                    <div class="space-y-1">
                        <Link 
                            v-for="item in group.items" 
                            :key="item.name"
                            :href="route(item.route)"
                            :class="[
                                'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200 group relative',
                                route().current(item.route) 
                                    ? 'bg-gradient-to-r from-[#8C57FF] to-[#A478FF] text-white shadow-md shadow-[#8C57FF]/20' 
                                    : 'text-[#3A3541] hover:bg-gray-50'
                            ]"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span v-if="showingSidebar" class="font-medium text-[15px]">{{ item.name }}</span>
                            <div v-if="!showingSidebar && route().current(item.route)" class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-[#8C57FF] rounded-l-full"></div>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-50">
                <div :class="['flex items-center gap-3 bg-gray-50 p-3 rounded-xl', !showingSidebar && 'justify-center p-2']">
                    <div class="w-9 h-9 rounded-full bg-[#8C57FF]/10 text-[#8C57FF] flex items-center justify-center font-bold">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div v-if="showingSidebar" class="flex flex-col min-w-0">
                        <span class="text-sm font-bold text-[#3A3541] truncate">{{ $page.props.auth.user.name }}</span>
                        <span class="text-[11px] text-gray-500 font-medium">Super Admin</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Drawer Overlay -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showingMobileMenu" class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm lg:hidden" @click="showingMobileMenu = false"></div>
        </Transition>

        <!-- Mobile Drawer Sidebar -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside v-if="showingMobileMenu" class="fixed inset-y-0 left-0 z-[70] w-72 bg-white shadow-2xl flex flex-col lg:hidden">
                <div class="h-16 flex items-center justify-between px-6 border-b border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#8C57FF] rounded-lg flex items-center justify-center shadow-lg shadow-[#8C57FF]/30">
                            <ApplicationLogo class="w-5 h-5 fill-white" />
                        </div>
                        <span class="font-bold text-xl tracking-tight text-[#3A3541]">VideyView</span>
                    </div>
                    <button @click="showingMobileMenu = false" class="p-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-8 no-scrollbar">
                    <div v-for="group in menuGroups" :key="group.title">
                        <h4 class="px-4 text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">{{ group.title }}</h4>
                        <div class="space-y-1">
                            <Link 
                                v-for="item in group.items" 
                                :key="item.name"
                                :href="route(item.route)"
                                @click="showingMobileMenu = false"
                                :class="[
                                    'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                                    route().current(item.route) 
                                        ? 'bg-gradient-to-r from-[#8C57FF] to-[#A478FF] text-white shadow-md shadow-[#8C57FF]/20' 
                                        : 'text-[#3A3541] hover:bg-gray-50'
                                ]"
                            >
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                <span class="font-medium text-[15px]">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>
            </aside>
        </Transition>

        <!-- Main Content Area -->
        <div 
            :class="[
                'flex-1 flex flex-col transition-all duration-300 min-w-0',
                showingSidebar ? 'lg:ml-64' : 'lg:ml-20'
            ]"
        >
            <!-- Navbar -->
            <header class="h-16 bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-40 flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-4">
                    <!-- Desktop Toggle -->
                    <button @click="toggleSidebar" class="p-2 hover:bg-gray-50 rounded-lg text-gray-500 hidden lg:block transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Mobile Toggle -->
                    <button @click="toggleMobileMenu" class="p-2 hover:bg-gray-50 rounded-lg text-gray-500 lg:hidden transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <!-- Search Bar (Hidden on small mobile) -->
                    <div class="hidden md:flex items-center bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200 w-48 lg:w-64 group focus-within:border-[#8C57FF]/50 transition-all">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            placeholder="Cari konten..." 
                            class="bg-transparent border-none focus:ring-0 text-sm placeholder-gray-400 w-full ml-2"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-2">
                    <button class="p-2 hover:bg-gray-50 rounded-lg text-gray-500 relative">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    
                    <div class="h-8 w-px bg-gray-100 mx-1 sm:mx-2"></div>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-lg transition-colors">
                                <div class="w-8 h-8 rounded-full bg-[#8C57FF] text-white flex items-center justify-center font-bold text-xs">
                                    {{ $page.props.auth.user.name.charAt(0) }}
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-50">
                                    <p class="text-sm font-bold text-[#3A3541]">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-[11px] text-gray-500">Administrator</p>
                                </div>
                                <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-500">Keluar</DropdownLink>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>

            <!-- Footer -->
            <footer class="p-6 mt-auto border-t border-gray-100 text-center sm:text-left text-sm text-gray-500 flex flex-col sm:flex-row justify-between gap-4 bg-white/50">
                <div class="text-[11px] sm:text-sm">© 2026 VideyView · <span class="text-[#8C57FF] font-bold">Quantum Efficiency</span></div>
                <div class="flex items-center justify-center sm:justify-end gap-6 font-medium text-[11px] sm:text-sm">
                    <Link :href="route('home')" class="hover:text-[#8C57FF] transition-colors">Situs Publik</Link>
                    <a href="#" class="hover:text-[#8C57FF] transition-colors">Dokumentasi</a>
                </div>
            </footer>
        </div>

        <ToastList />
    </div>
</template>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.materio-card {
    @apply bg-white rounded-xl border border-gray-100 transition-all duration-300;
    box-shadow: 0 4px 10px rgba(58, 53, 65, 0.05);
}
.materio-card:hover {
    box-shadow: 0 8px 20px rgba(58, 53, 65, 0.1);
}
</style>
