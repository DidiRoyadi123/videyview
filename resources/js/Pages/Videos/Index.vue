<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { ref, onMounted, onUnmounted, inject, watch, computed } from 'vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';

const isSpark = inject('isSpark', computed(() => false));

const props = defineProps({
    videos: Object,
    trendingVideos: Array,
    currentFilter: String,
    currentSort: String,
    currentCategory: String,
    currentTag: String,
});

const page = usePage();
const isNavigating = inject('isNavigating', false);
const allVideos = ref([...props.videos.data]);
const loadingMore = ref(false);
const activeSlide = ref(0);
let slideInterval = null;

const popularTags = computed(() => page.props.popular_tags || []);

const nextSlide = () => {
    if (props.trendingVideos && props.trendingVideos.length > 0) {
        activeSlide.value = (activeSlide.value + 1) % props.trendingVideos.length;
    }
};

const prevSlide = () => {
    if (props.trendingVideos && props.trendingVideos.length > 0) {
        activeSlide.value = (activeSlide.value - 1 + props.trendingVideos.length) % props.trendingVideos.length;
    }
};

// Update allVideos when props change
watch(() => props.videos.data, (newData) => {
    if (props.videos.current_page === 1) {
        allVideos.value = [...newData];
    } else {
        const existingIds = new Set(allVideos.value.map(v => v.id));
        const uniqueNew = newData.filter(v => !existingIds.has(v.id));
        allVideos.value = [...allVideos.value, ...uniqueNew];
    }
}, { deep: true });

const loadMore = () => {
    if (props.videos.next_page_url && !loadingMore.value && !isNavigating.value) {
        router.get(props.videos.next_page_url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['videos'],
            onStart: () => { loadingMore.value = true },
            onFinish: () => { loadingMore.value = false },
        });
    }
};

onMounted(() => {
    const sentinel = document.getElementById('infinite-scroll-sentinel');
    if (sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                loadMore();
            }
        }, { rootMargin: '400px' });
        observer.observe(sentinel);
    }

    if (props.trendingVideos && props.trendingVideos.length > 1) {
        slideInterval = setInterval(nextSlide, 6000);
    }
});

onUnmounted(() => {
    if (slideInterval) clearInterval(slideInterval);
});

const searchQuery = ref(usePage().props.zigzag?.query?.search || '');

const handleSearch = () => {
    router.get(route('home'), { 
        search: searchQuery.value,
        filter: props.currentFilter,
        sort: props.currentSort
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};

const formatViews = (views) => {
    return views.toString();
};

const activeCategory = computed(() => {
    if (!props.currentCategory) return null;
    return page.props.categories?.find(c => c.slug === props.currentCategory);
});

const activeTag = computed(() => {
    if (!props.currentTag) return null;
    return page.props.popular_tags?.find(t => t.slug === props.currentTag);
});
</script>

<template>
    <Head title="Browse Premium Videos" />

    <MainLayout>
        <template #header>
            <!-- Trending Hero Carousel -->
            <div v-if="trendingVideos && trendingVideos.length > 0" :class="['relative group mt-1 overflow-hidden transition-all duration-500 shadow-2xl', isSpark ? 'rounded-2xl sm:rounded-[32px]' : 'rounded-2xl sm:rounded-[40px]']" :style="{ backgroundColor: 'rgb(var(--bg-surface))', border: '1px solid rgb(var(--border-main))', height: '' }" style="height: clamp(180px, 40vw, 500px)">
                <!-- Slides Container -->
                <div class="absolute inset-0 transition-transform duration-700 ease-in-out flex" :style="{ transform: `translateX(-${activeSlide * 100}%)` }">
                    <div v-for="video in trendingVideos" :key="video.id" class="relative min-w-full h-full overflow-hidden">
                        <!-- Backdrop Thumbnail -->
                        <img 
                            :src="video.thumbnail_url" 
                            class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 scale-105" 
                            :class="video.is_premium && !$page.props.auth.user?.active_subscription && !$page.props.auth.user?.is_admin ? 'blur-[8px] brightness-[0.4] scale-110' : 'brightness-50'"
                            alt=""
                        />

                        <!-- Gradient Overlays -->
                        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t" :style="{ backgroundImage: `linear-gradient(to top, rgb(var(--bg-main)), rgb(var(--bg-main) / 0.8), transparent)` }"></div>
                        <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r hidden md:block" :style="{ backgroundImage: `linear-gradient(to right, rgb(var(--bg-main) / 0.8), transparent)` }"></div>

                        <!-- Content Area -->
                        <div class="absolute inset-0 flex flex-col justify-end p-5 sm:p-12 md:p-24">
                            <div class="max-w-4xl animate-in">
                                <!-- Status Tags -->
                                <div class="flex items-center gap-2 mb-3 sm:mb-6 flex-wrap">
                                    <div v-if="video.is_premium" class="bg-gradient-to-r from-amber-500 to-amber-700 text-white text-[8px] sm:text-[10px] px-2.5 sm:px-4 py-1 rounded-full font-black uppercase tracking-widest flex items-center gap-1 border border-amber-400/30 shadow-xl">
                                        👑 PREMIUM
                                    </div>
                                    <div v-if="video.is_free_to_all" class="bg-indigo-600 text-white text-[8px] sm:text-[10px] px-2.5 sm:px-4 py-1 rounded-full font-black uppercase tracking-widest border border-indigo-400/30">
                                        FREE
                                    </div>
                                    <div class="flex items-center gap-1.5 text-indigo-500 text-[8px] sm:text-[10px] font-black uppercase tracking-widest bg-white/10 backdrop-blur px-2.5 sm:px-4 py-1 rounded-full border border-white/10">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                        TRENDING
                                    </div>
                                </div>

                                <h2 class="text-xl sm:text-4xl md:text-7xl font-black leading-[0.95] mb-3 sm:mb-8 line-clamp-2 italic tracking-tighter drop-shadow-2xl" :style="{ color: 'rgb(var(--text-main))' }">
                                    {{ video.title }}
                                </h2>

                                <div class="flex items-center gap-4">
                                    <Link :href="route('videos.show', video.slug)" class="btn-premium text-center !text-[9px] sm:!text-[10px] !px-5 sm:!px-8 !py-2.5 sm:!py-3">
                                        ▶ Tonton Sekarang
                                    </Link>
                                    <div class="hidden sm:flex items-center gap-2 text-[rgb(var(--text-muted))] text-[10px] font-bold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        {{ formatViews(video.views) }} Views
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls (Desktop Only) -->
                <div class="absolute inset-y-0 left-0 items-center px-4 hidden sm:flex opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="prevSlide" class="w-10 h-10 rounded-full glass flex items-center justify-center text-white hover:bg-indigo-600 transition-all active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                </div>
                <div class="absolute inset-y-0 right-0 items-center px-4 hidden sm:flex opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="nextSlide" class="w-10 h-10 rounded-full glass flex items-center justify-center text-white hover:bg-indigo-600 transition-all active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>

                <!-- Pagination Dots -->
                <div class="absolute top-4 right-4 sm:bottom-8 sm:top-auto flex gap-1.5">
                    <button 
                        v-for="(_, i) in trendingVideos" 
                        :key="i"
                        @click="activeSlide = i"
                        class="transition-all duration-500 rounded-full"
                        :class="activeSlide === i ? 'w-5 h-1.5 bg-indigo-500' : 'w-1.5 h-1.5 bg-white/20'"
                    ></button>
                </div>
            </div>

            <!-- Legacy Hero Placeholder (if no trending videos) -->
            <div v-else class="relative py-8 sm:py-12 overflow-hidden rounded-2xl sm:rounded-[40px] border border-[rgb(var(--border-main))] p-6 sm:p-8" :style="{ backgroundColor: 'rgb(var(--bg-surface))' }">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px]"></div>
                <h1 class="text-3xl sm:text-5xl md:text-7xl font-black tracking-tightest leading-tight mb-4 animate-in fade-in slide-in-from-bottom-4 duration-1000 break-words italic" :style="{ color: 'rgb(var(--text-main))' }">
                    Binge-worthy <br class="hidden sm:block" />
                    <span class="text-gradient">Experiences.</span>
                </h1>
                <p class="text-[rgb(var(--text-muted))] text-base sm:text-lg md:text-xl max-w-2xl font-medium leading-relaxed">
                    Access high-quality content curated for enthusiasts. Join our premium membership for unlimited access.
                </p>
                <div class="mt-6 sm:mt-8">
                    <Link :href="route('register')" class="btn-premium px-8 inline-block">Upgrade to Premium</Link>
                </div>
            </div>
        </template>

        <div class="px-3 sm:px-6 lg:px-8 max-w-7xl mx-auto -mt-4 sm:-mt-10 relative z-30">
            
            <!-- Horizontal Tag Scroller (Both templates) -->
            <div v-if="popularTags.length" class="mb-3 sm:mb-6 -mx-3 sm:mx-0">
                <div class="flex gap-2 overflow-x-auto no-scrollbar px-3 sm:px-0 py-1">
                    <Link 
                        v-for="tag in popularTags" 
                        :key="tag.slug"
                        :href="route('home', { tag: tag.slug })"
                        class="tag-chip"
                        :class="{ 'active bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-600/20': currentTag === tag.slug }"
                    >
                        #{{ tag.name }}
                    </Link>
                </div>
            </div>

            <!-- Category/Tag Filter Indicator -->
            <div v-if="activeCategory || activeTag" class="mb-4 flex items-center justify-between p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl animate-in">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600/10 rounded-xl flex items-center justify-center text-xl">
                        {{ activeCategory ? (activeCategory.icon || '📁') : '#' }}
                    </div>
                    <div>
                        <div class="text-[9px] font-black uppercase text-indigo-500 tracking-widest">Menampilkan</div>
                        <div class="text-xs font-black text-[rgb(var(--text-main))] uppercase tracking-tight">
                            {{ activeCategory ? activeCategory.name : activeTag.name }}
                        </div>
                    </div>
                </div>
                <Link :href="route('home')" class="p-2.5 bg-white/5 hover:bg-red-500/10 hover:text-red-500 rounded-xl transition-all text-[rgb(var(--text-muted))]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </div>

            <!-- Search & Filter Bar -->
            <div class="mb-4 sm:mb-8 lg:mb-12 glass shadow-royale p-2 sm:p-3 rounded-2xl sm:rounded-[32px] flex flex-col lg:flex-row items-center gap-2 sm:gap-4">
                <!-- Search Box (Hidden on mobile — bottom nav handles search) -->
                <div class="relative group flex-1 w-full hidden sm:block">
                    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none transition-transform group-focus-within:scale-110">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="search" 
                        @keyup.enter="handleSearch"
                        placeholder="CARI VIDEO..." 
                        class="w-full h-11 sm:h-14 pl-14 pr-6 rounded-xl sm:rounded-2xl bg-[rgb(var(--bg-input))] border-none focus:ring-2 focus:ring-indigo-500/10 text-xs font-black tracking-widest uppercase transition-all placeholder:text-[rgb(var(--text-muted))]/40"
                    />
                </div>

                <!-- Sort Control -->
                <div class="hidden sm:flex items-center gap-3 w-full lg:w-auto px-2">
                    <div class="hidden sm:block text-[9px] font-black uppercase text-[rgb(var(--text-muted))] tracking-[0.3em] whitespace-nowrap">Urutkan:</div>
                    <div class="relative w-full lg:w-48">
                        <select 
                            @change="(e) => router.get(route('home'), { ...route().params, sort: e.target.value })"
                            class="w-full bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] text-[rgb(var(--text-main))] text-[10px] font-black uppercase tracking-widest px-5 py-3.5 rounded-2xl focus:ring-2 focus:ring-indigo-500/20 appearance-none shadow-inner cursor-pointer"
                        >
                            <option value="latest" :selected="currentSort === 'latest'">Terbaru</option>
                            <option value="popular" :selected="currentSort === 'popular'">Terpopuler</option>
                            <option value="oldest" :selected="currentSort === 'oldest'">Terlama</option>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-indigo-500">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Filters (Horizontal Scrollable Pills) -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar w-full lg:w-auto px-1">
                    <button 
                        v-for="cat in [{id:'all',label:'Semua'},{id:'trending',label:'Populer'},{id:'free',label:'Gratis'},{id:'premium',label:'Premium'}]" 
                        :key="cat.id"
                        @click="router.visit(route('home', { filter: cat.id }))"
                        class="filter-pill"
                        :class="{ active: ($page.props.currentFilter === cat.id) || (cat.id === 'all' && !$page.props.currentFilter) }"
                    >
                        {{ cat.label }}
                    </button>
                </div>
            </div>

            <!-- Video Grid (Mobile-first: compact for both templates) -->
            <div :class="['grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4', isSpark ? 'gap-3 sm:gap-4 lg:gap-6' : 'gap-3 sm:gap-5 lg:gap-8']">
                <template v-for="(video, index) in allVideos" :key="video.id">
                    <!-- Integrated Ad Slot -->
                    <template v-if="index === 4 && $page.props.ads && !$page.props.auth.user?.is_admin">
                        <div class="col-span-full mb-2 sm:mb-6">
                            <div class="glass p-3 sm:p-6 rounded-2xl sm:rounded-[32px] relative overflow-hidden group shadow-royale">
                                <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/5 rounded-full blur-[100px] group-hover:bg-indigo-500/10 transition-all duration-700"></div>
                                <div class="flex items-center gap-2 mb-3 justify-center">
                                    <span class="w-6 h-[1.5px] bg-indigo-500/20"></span>
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase text-[rgb(var(--text-muted))] tracking-widest">Konten Pilihan</div>
                                    <span class="w-6 h-[1.5px] bg-indigo-500/20"></span>
                                </div>
                                <AdHandler :adCode="$page.props.ads.native_banner" type="banner" />
                            </div>
                        </div>
                    </template>

                    <!-- Video Card (Mobile-First for Both Templates) -->
                    <div class="group">
                        <Link :href="route('videos.show', video.slug)" class="block">
                            <!-- Thumbnail Container -->
                            <div :class="['aspect-video relative overflow-hidden', isSpark ? 'card-spark' : 'card-spark sm:rounded-3xl lg:rounded-[32px]']">
                                <img 
                                    v-if="video.thumbnail_url" 
                                    :src="video.thumbnail_url" 
                                    class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105" 
                                    :class="video.is_premium && !$page.props.auth.user?.active_subscription && !$page.props.auth.user?.is_admin ? 'blur-md brightness-75' : ''"
                                    loading="lazy"
                                    alt=""
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-[rgb(var(--bg-input))]">
                                    <div class="text-2xl opacity-30">🎬</div>
                                </div>

                                <!-- Premium Badge -->
                                <div v-if="video.is_premium" class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 z-10">
                                    <span class="bg-gradient-to-r from-amber-500 to-amber-700 text-white text-[7px] sm:text-[8px] px-1.5 sm:px-2 py-0.5 rounded font-black uppercase tracking-wider border border-amber-400/30 flex items-center gap-0.5">
                                        👑 <span class="hidden sm:inline">VIP</span>
                                    </span>
                                </div>

                                <!-- Free Badge -->
                                <div v-else-if="video.is_free_to_all" class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 z-10">
                                    <span class="bg-indigo-600 text-white text-[7px] sm:text-[8px] px-1.5 sm:px-2 py-0.5 rounded font-black uppercase tracking-wider">
                                        FREE
                                    </span>
                                </div>

                                <!-- Views Badge -->
                                <div class="badge-duration">
                                    <span class="flex items-center gap-0.5">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        {{ formatViews(video.views) }}
                                    </span>
                                </div>

                                <!-- Classic Desktop: Overlay Title (hidden on mobile, visible from sm) -->
                                <div v-if="!isSpark" class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-3 sm:p-5 hidden sm:block">
                                    <h3 class="text-xs sm:text-sm font-black leading-tight line-clamp-2 text-white drop-shadow-lg">
                                        {{ video.title }}
                                    </h3>
                                </div>
                            </div>

                            <!-- Title Below Thumbnail (Mobile: always, Desktop: only Spark) -->
                            <div :class="[isSpark ? 'block' : 'block sm:hidden', 'px-1 py-1.5 sm:py-2']">
                                <h3 class="text-[10px] sm:text-xs font-bold leading-tight line-clamp-2 transition-colors group-hover:text-indigo-500" :style="{ color: 'rgb(var(--text-main))' }">
                                    {{ video.title }}
                                </h3>
                            </div>

                            <!-- CTA Button -->
                            <div class="px-1 pb-1">
                                <span class="flex items-center justify-center gap-1 w-full py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-white bg-gradient-to-r from-indigo-500 to-violet-600 shadow-md shadow-indigo-500/20 group-hover:shadow-indigo-500/40 transition-all active:scale-95">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    Tonton
                                </span>
                            </div>
                        </Link>
                    </div>
                </template>
            </div>

            <!-- Loader / Sentinel -->
            <div id="infinite-scroll-sentinel" class="mt-12 sm:mt-24 h-16 sm:h-24 flex items-center justify-center">
                <div v-if="loadingMore" class="flex gap-2">
                    <span class="w-2 h-2 sm:w-3 sm:h-3 bg-indigo-500 rounded-full animate-bounce"></span>
                    <span class="w-2 h-2 sm:w-3 sm:h-3 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                    <span class="w-2 h-2 sm:w-3 sm:h-3 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slide-in-from-bottom {
    from { transform: translateY(20px); }
    to { transform: translateY(0); }
}
.animate-in {
    animation: fade-in 0.8s ease-out, slide-in-from-bottom 0.8s ease-out;
}
</style>
