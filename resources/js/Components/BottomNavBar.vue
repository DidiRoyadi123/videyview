<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const showSearch = ref(false);
const showCategories = ref(false);
const searchQuery = ref('');

const isActive = (routeCheck) => {
    const current = route().current();
    if (routeCheck === 'home') return current === 'home' && !page.props.currentFilter;
    if (routeCheck === 'saved') return current === 'home' && page.props.currentFilter === 'saved';
    if (routeCheck === 'profile') return current === 'dashboard' || current === 'profile.edit';
    return current === routeCheck;
};

const popularTags = computed(() => page.props.popular_tags || []);

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('home'), { search: searchQuery.value });
        showSearch.value = false;
        searchQuery.value = '';
    }
};

const closeSearch = () => {
    showSearch.value = false;
    searchQuery.value = '';
};

const navigateTag = (slug) => {
    router.get(route('home'), { tag: slug });
    showCategories.value = false;
};

const navigateFilter = (filter) => {
    router.get(route('home'), { filter });
    showCategories.value = false;
};
</script>

<template>
    <!-- Bottom Navigation Bar (Mobile Only) -->
    <nav class="bottom-nav lg:hidden" id="bottom-nav">
        <div class="flex items-center justify-around px-2 pt-1">
            <!-- Beranda -->
            <Link :href="route('home')" class="bottom-nav-item" :class="{ active: isActive('home') }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" :class="isActive('home') ? 'fill-current' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Beranda</span>
            </Link>

            <!-- Cari -->
            <button @click="showSearch = !showSearch" class="bottom-nav-item" :class="{ active: showSearch }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Cari</span>
            </button>

            <!-- Kategori -->
            <button @click="showCategories = !showCategories" class="bottom-nav-item" :class="{ active: showCategories }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Kategori</span>
            </button>

            <!-- Simpan -->
            <Link :href="route('home', { filter: 'saved' })" class="bottom-nav-item" :class="{ active: isActive('saved') }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" :class="isActive('saved') ? 'fill-current' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Simpan</span>
            </Link>

            <!-- Profil -->
            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="bottom-nav-item" :class="{ active: isActive('profile') }">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" :class="isActive('profile') ? 'fill-current' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Profil</span>
            </Link>
            <Link v-else :href="route('login')" class="bottom-nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <span class="text-[9px] font-bold tracking-wide">Masuk</span>
            </Link>
        </div>
    </nav>

    <!-- Search Overlay -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
    >
        <div v-if="showSearch" class="search-overlay lg:hidden">
            <div class="flex items-center gap-3 p-4 border-b" :style="{ borderColor: 'rgb(var(--border-main))' }">
                <button @click="closeSearch" class="p-2 rounded-xl" :style="{ color: 'rgb(var(--text-muted))' }">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div class="flex-1 relative">
                    <input 
                        v-model="searchQuery"
                        type="search"
                        @keyup.enter="handleSearch"
                        placeholder="Cari video..."
                        autofocus
                        class="w-full h-12 pl-4 pr-12 rounded-2xl border-none focus:ring-2 focus:ring-indigo-500/20 text-sm font-medium"
                        :style="{ backgroundColor: 'rgb(var(--bg-input))', color: 'rgb(var(--text-main))' }"
                    />
                    <button @click="handleSearch" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-500 rounded-xl hover:bg-indigo-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Quick Tags in Search -->
            <div v-if="popularTags.length" class="p-4">
                <h3 class="text-[10px] font-bold uppercase tracking-widest mb-3" :style="{ color: 'rgb(var(--text-muted))' }">Tag Populer</h3>
                <div class="flex flex-wrap gap-2">
                    <button 
                        v-for="tag in popularTags" 
                        :key="tag.slug" 
                        @click="() => { router.get(route('home'), { tag: tag.slug }); showSearch = false; }"
                        class="tag-chip"
                    >
                        #{{ tag.name }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Category Bottom Sheet -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showCategories" class="fixed inset-0 z-[85] bg-black/40 backdrop-blur-sm lg:hidden" @click="showCategories = false"></div>
    </Transition>

    <Transition
        enter-active-class="transition duration-400 ease-out"
        enter-from-class="translate-y-full"
        enter-to-class="translate-y-0"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="translate-y-0"
        leave-to-class="translate-y-full"
    >
        <div v-if="showCategories" class="fixed bottom-0 left-0 right-0 z-[86] lg:hidden rounded-t-3xl overflow-hidden max-h-[70vh] overflow-y-auto" :style="{ backgroundColor: 'rgb(var(--bg-surface))', borderTop: '1px solid rgb(var(--border-main))' }">
            <!-- Handle Bar -->
            <div class="flex justify-center pt-3 pb-2">
                <div class="w-10 h-1 rounded-full" :style="{ backgroundColor: 'rgb(var(--border-main))' }"></div>
            </div>

            <div class="px-5 pb-6">
                <!-- Filter Section -->
                <h3 class="text-[10px] font-bold uppercase tracking-widest mb-3" :style="{ color: 'rgb(var(--text-muted))' }">Filter</h3>
                <div class="flex flex-wrap gap-2 mb-5">
                    <button 
                        v-for="f in [{id:'all',label:'Semua'},{id:'trending',label:'Populer'},{id:'free',label:'Gratis'},{id:'premium',label:'Premium 👑'}]" 
                        :key="f.id" 
                        @click="navigateFilter(f.id)"
                        class="filter-pill"
                        :class="{ active: ($page.props.currentFilter === f.id) || (f.id === 'all' && !$page.props.currentFilter) }"
                    >
                        {{ f.label }}
                    </button>
                </div>

                <!-- Tags Section -->
                <template v-if="popularTags.length">
                    <h3 class="text-[10px] font-bold uppercase tracking-widest mb-3" :style="{ color: 'rgb(var(--text-muted))' }">Tag Populer</h3>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            v-for="tag in popularTags" 
                            :key="tag.slug" 
                            @click="navigateTag(tag.slug)"
                            class="tag-chip"
                        >
                            #{{ tag.name }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Transition>
</template>
