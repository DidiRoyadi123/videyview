<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { ref, onMounted, onUnmounted, inject, watch } from 'vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';

const props = defineProps({
    videos: Object,
    trendingVideos: Array,
    currentFilter: String,
});

const isNavigating = inject('isNavigating', false);
const allVideos = ref([...props.videos.data]);
const loadingMore = ref(false);
const activeSlide = ref(0);
let slideInterval = null;

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
        filter: props.currentFilter 
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};
</script>

<template>
    <Head title="Browse Premium Videos" />

    <MainLayout>
        <template #header>
            <!-- Trending Hero Carousel -->
            <div v-if="trendingVideos && trendingVideos.length > 0" class="relative group mt-4 overflow-hidden rounded-[32px] transition-all duration-500 shadow-2xl h-[300px] sm:h-[400px] md:h-[500px]" :style="{ backgroundColor: 'rgb(var(--bg-surface))', border: '1px solid rgb(var(--border-main))' }">
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
                        <div class="absolute inset-0 flex flex-col justify-end p-8 sm:p-16 md:p-24">
                            <div class="max-w-4xl animate-in">
                                <!-- Status Tags -->
                                <div class="flex items-center gap-3 mb-6 flex-wrap">
                                    <div v-if="video.is_premium" class="bg-gradient-to-r from-amber-500 to-amber-700 text-white text-[10px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest flex items-center gap-2 border border-amber-400/30 shadow-xl shadow-amber-500/20">
                                        <svg class="w-3 h-3 fill-amber-300" viewBox="0 0 24 24">
                                            <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                        </svg>
                                        PREMIUM
                                    </div>
                                    <div v-if="video.is_free_to_all" class="bg-indigo-600 text-white text-[10px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest border border-indigo-400/30 shadow-xl shadow-indigo-600/20">
                                        FREE
                                    </div>
                                    <div class="flex items-center gap-2 text-indigo-500 text-[10px] font-black uppercase tracking-[0.3em] bg-white/10 backdrop-blur px-4 py-1.5 rounded-full border border-white/10">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                        TRENDING
                                    </div>
                                </div>

                                <h2 class="text-4xl sm:text-6xl md:text-8xl font-black leading-[0.9] mb-4 sm:mb-8 line-clamp-2 italic tracking-tighter drop-shadow-2xl" :style="{ color: 'rgb(var(--text-main))' }">
                                    {{ video.title }}
                                </h2>

                                <p class="text-[rgb(var(--text-muted))] text-sm md:text-xl mb-10 line-clamp-2 max-w-2xl font-medium leading-relaxed hidden sm:block">
                                    Experience the future of media. High-fidelity streams, exclusive content, and a community of connoisseurs.
                                </p>

                                        {{ (video.views / 1000).toFixed(1) }}k Views
                                    </div>
                                </div>
                            </div>
                        </div>

                <!-- Navigation Controls (Reduced for Mobile) -->
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
                <div class="absolute top-6 right-6 flex gap-1.5 sm:bottom-8 sm:top-auto">
                    <button 
                        v-for="(_, i) in trendingVideos" 
                        :key="i"
                        @click="activeSlide = i"
                        class="transition-all duration-500 rounded-full"
                        :class="activeSlide === i ? 'w-6 h-1.5 bg-indigo-500' : 'w-1.5 h-1.5 bg-white/20'"
                    ></button>
                </div>
            </div>

            <!-- Legacy Hero Placeholder (if no trending videos) -->
            <div v-else class="relative py-12 overflow-hidden rounded-[40px] border border-[rgb(var(--border-main))] p-8" :style="{ backgroundColor: 'rgb(var(--bg-surface))' }">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px]"></div>
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-black tracking-tightest leading-tight mb-4 animate-in fade-in slide-in-from-bottom-4 duration-1000 break-words italic" :style="{ color: 'rgb(var(--text-main))' }">
                    Binge-worthy <br class="hidden sm:block" />
                    <span class="text-gradient">Experiences.</span>
                </h1>
                <p class="text-[rgb(var(--text-muted))] text-lg md:text-xl max-w-2xl font-medium leading-relaxed">
                    Access high-quality content curated for enthusiasts. Join our premium membership for unlimited access.
                </p>
                <div class="mt-8">
                    <Link :href="route('register')" class="btn-premium px-8 inline-block">Upgrade to Premium</Link>
                </div>
            </div>
        </template>

        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto -mt-10 relative z-30">
            <!-- Royale Search & Nav -->
            <div class="mb-16 glass shadow-royale p-3 rounded-[32px] flex flex-col lg:flex-row items-center gap-6">
                <!-- Search Box 2.0 -->
                <div class="relative group flex-1 w-full">
                    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none transition-transform group-focus-within:scale-110">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="search" 
                        placeholder="SEARCH THE VAULT..." 
                        class="w-full h-14 pl-14 pr-6 rounded-2xl bg-[rgb(var(--bg-input))] border-none focus:ring-2 focus:ring-indigo-500/10 text-xs font-black tracking-widest uppercase transition-all placeholder:text-[rgb(var(--text-muted))]/40"
                    />
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 no-scrollbar w-full lg:w-auto">
                    <button 
                        v-for="cat in ['all', 'trending', 'free', 'premium']" 
                        :key="cat"
                        @click="router.visit(route('home', { filter: cat }))"
                        class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap"
                        :class="($page.props.currentFilter === cat) || (cat === 'all' && !$page.props.currentFilter) 
                            ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/30' 
                            : 'bg-indigo-500/5 text-[rgb(var(--text-muted))] hover:bg-indigo-500/10 hover:text-indigo-500'"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- Video Grid Royale -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 sm:gap-10">
                <template v-for="(video, index) in allVideos" :key="video.id">
                    <!-- Integrated Ad Slot -->
                    <template v-if="index === 4 && $page.props.ads && !$page.props.auth.user?.is_admin">
                        <div class="col-span-full mb-8">
                            <div class="glass p-6 sm:p-10 rounded-[40px] relative overflow-hidden group shadow-royale">
                                <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/5 rounded-full blur-[100px] group-hover:bg-indigo-500/10 transition-all duration-700"></div>
                                <div class="flex items-center gap-3 mb-6 justify-center">
                                    <span class="w-8 h-[2px] bg-indigo-500/20"></span>
                                    <div class="text-[10px] font-black uppercase text-[rgb(var(--text-muted))] tracking-[0.3em]">Featured Experience</div>
                                    <span class="w-8 h-[2px] bg-indigo-500/20"></span>
                                </div>
                                <AdHandler :adCode="$page.props.ads.native_banner" type="banner" />
                            </div>
                        </div>
                    </template>

                    <div class="group">
                        <Link :href="route('videos.show', video.slug)" class="block">
                            <div class="card-modern aspect-video relative overflow-hidden">
                                <img 
                                    v-if="video.thumbnail_url" 
                                    :src="video.thumbnail_url" 
                                    class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110" 
                                    :class="video.is_premium && !$page.props.auth.user?.active_subscription && !$page.props.auth.user?.is_admin ? 'blur-md brightness-75' : 'opacity-100'"
                                    alt=""
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2 bg-[rgb(var(--bg-input))]">
                                    <div class="text-2xl">🎬</div>
                                </div>

                                <!-- Premium Badge -->
                                <div class="absolute top-4 left-4 z-10">
                                    <span v-if="video.is_premium" class="bg-gradient-to-r from-amber-500 to-amber-700 shadow-xl text-white text-[9px] px-3 py-1 rounded-full font-black uppercase tracking-widest border border-amber-400/30">
                                        PREMIUM
                                    </span>
                                </div>

                                <!-- Info Overlay -->
                                <div class="absolute bottom-0 inset-x-0 p-5 bg-gradient-to-t from-black/80 via-black/40 to-transparent">
                                    <h3 class="text-white text-sm font-black truncate group-hover:text-indigo-400 transition-colors uppercase tracking-tight">{{ video.title }}</h3>
                                    <p class="text-[rgb(var(--text-muted))] text-[9px] font-black uppercase tracking-widest mt-1">{{ video.views }} Views</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </template>
            </div>

            <!-- Loader / Sentinel -->
            <div id="infinite-scroll-sentinel" class="mt-24 h-24 flex items-center justify-center">
                <div v-if="loadingMore" class="flex gap-2">
                    <span class="w-3 h-3 bg-indigo-500 rounded-full animate-bounce"></span>
                    <span class="w-3 h-3 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                    <span class="w-3 h-3 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
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
