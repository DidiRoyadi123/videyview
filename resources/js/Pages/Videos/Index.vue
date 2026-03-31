<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
</script>

<template>
    <Head title="Browse Premium Videos" />

    <MainLayout>
        <template #header>
            <!-- Trending Hero Carousel -->
            <div v-if="trendingVideos && trendingVideos.length > 0" class="relative group mt-4 overflow-hidden rounded-[32px] bg-slate-900 border border-white/5 shadow-2xl h-[300px] sm:h-[400px] md:h-[500px]">
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
                        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent sm:via-slate-950/60"></div>
                        <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-slate-950/80 to-transparent hidden md:block"></div>

                        <!-- Content Area -->
                        <div class="absolute inset-0 flex flex-col justify-end p-5 sm:p-12 md:p-16">
                            <div class="max-w-3xl animate-in">
                                <!-- Status Tags -->
                                <div class="flex items-center gap-2 mb-3 flex-wrap">
                                    <div v-if="video.is_premium" class="bg-gradient-to-r from-amber-600 to-amber-800 text-white text-[9px] px-2.5 py-1 rounded-full font-black uppercase tracking-widest flex items-center gap-1.5 border border-amber-500/30">
                                        <svg class="w-2.5 h-2.5 fill-amber-300" viewBox="0 0 24 24">
                                            <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                        </svg>
                                        Premium
                                    </div>
                                    <div v-if="video.is_free_to_all" class="bg-blue-600/80 backdrop-blur text-white text-[9px] px-2.5 py-1 rounded-full font-black uppercase tracking-widest border border-blue-400/30">
                                        Free
                                    </div>
                                    <div class="flex items-center gap-1.5 text-indigo-400 text-[9px] font-black uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                        Trending
                                    </div>
                                </div>

                                <h2 class="text-2xl sm:text-5xl md:text-6xl font-black text-white leading-tight mb-2 sm:mb-4 line-clamp-1 sm:line-clamp-2 italic tracking-tighter">
                                    {{ video.title }}
                                </h2>

                                <!-- Hidden on Mobile to save space -->
                                <p class="text-slate-400 text-sm md:text-lg mb-6 line-clamp-2 max-w-xl font-medium leading-relaxed hidden sm:block">
                                    Watch the latest most viewed content. Experiences curated specifically for our community.
                                </p>

                                <div class="flex items-center gap-3">
                                    <Link :href="route('videos.show', video.slug)" class="group/btn flex items-center gap-2 sm:gap-3 bg-white text-slate-950 px-5 sm:px-8 py-3 sm:py-4 rounded-xl sm:rounded-2xl font-black uppercase tracking-widest text-[10px] sm:text-xs hover:bg-indigo-500 hover:text-white transition-all duration-500 shadow-2xl active:scale-95">
                                        <span>Watch Now</span>
                                        <svg class="w-3.5 h-3.5 transition-transform group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </Link>
                                    
                                    <Link v-if="video.is_premium && !$page.props.auth.user?.active_subscription" :href="route('register')" class="hidden xs:flex items-center gap-2 px-5 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-colors">
                                        Unlock
                                    </Link>

                                    <div class="ms-auto flex items-center gap-2 text-slate-500 font-black text-[9px] uppercase tracking-widest sm:block hidden">
                                        {{ (video.views / 1000).toFixed(1) }}k Views
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls (Reduced for Mobile) -->
                <div class="absolute inset-y-0 left-0 items-center px-4 hidden sm:flex opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="prevSlide" class="w-10 h-10 rounded-full glass flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                </div>
                <div class="absolute inset-y-0 right-0 items-center px-4 hidden sm:flex opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="nextSlide" class="w-10 h-10 rounded-full glass flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>

                <!-- Pagination Dots (Repositioned to top-right on mobile to avoid button overlap) -->
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
            <div v-else class="relative py-12 overflow-hidden rounded-[40px] bg-slate-900/50 border border-white/5 p-8">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-[120px]"></div>
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-black tracking-tightest leading-tight mb-4 animate-in fade-in slide-in-from-bottom-4 duration-1000 break-words text-white italic">
                    Binge-worthy <br class="hidden sm:block" />
                    <span class="text-gradient">Experiences.</span>
                </h1>
                <p class="text-slate-400 text-lg md:text-xl max-w-2xl font-medium leading-relaxed">
                    Access high-quality content curated for enthusiasts. Join our premium membership for unlimited access.
                </p>
                <div class="mt-8">
                    <Link :href="route('register')" class="btn-premium px-8 inline-block">Upgrade to Premium</Link>
                </div>
            </div>
        </template>

        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Filter Navigation Menu -->
            <div class="mb-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <span class="w-12 h-1.5 bg-indigo-500 rounded-full"></span>
                    <h2 class="text-3xl font-black uppercase tracking-tighter text-white italic">
                        {{ currentFilter === 'trending' ? 'Trending Now' : (currentFilter === 'free' ? 'Free Content' : (currentFilter === 'premium' ? 'Premium Vault' : 'Discover All')) }}
                    </h2>
                </div>
                
                <!-- Filter Navigation Menu (Hidden on mobile, moved to sidebar) -->
                <div class="overflow-x-auto pb-4 -mb-4 scrollbar-hide w-full md:w-auto hidden md:block">
                    <div class="flex items-center gap-2 p-1.5 glass-dark rounded-[24px] border border-white/5 w-fit min-w-full md:min-w-0">
                        <Link 
                            v-for="f in ['all', 'trending', 'free', 'premium']" 
                            :key="f"
                            :href="route('home', { filter: f })"
                            :class="[
                                'px-6 py-2.5 rounded-[18px] text-[10px] font-black uppercase tracking-widest transition-all duration-300 flex-shrink-0',
                                currentFilter === f ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 scale-105' : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'
                            ]"
                        >
                            {{ f }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Skeleton Loader -->
            <div v-if="isNavigating" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <div v-for="i in 12" :key="i" class="w-full aspect-video rounded-[24px] bg-slate-900 border border-white/5 animate-pulse overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div>
                </div>
            </div>

            <!-- Actual Content -->
            <div v-else>
                <!-- Mid-Page Native Banner (High Visibility) -->
                <div v-if="!$page.props.auth.user?.has_active_subscription && !$page.props.auth.user?.is_admin && $page.props.ads?.native_banner" 
                     class="mb-12"
                >
                    <div class="glass-dark p-6 sm:p-10 rounded-[40px] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px] group-hover:bg-indigo-500/20 transition-all duration-700"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-violet-500/10 rounded-full blur-[100px] group-hover:bg-violet-500/20 transition-all duration-700"></div>
                        
                        <div class="flex items-center gap-3 mb-6 justify-center">
                            <span class="w-8 h-[2px] bg-indigo-500/50"></span>
                            <div class="text-[10px] sm:text-xs font-black uppercase text-slate-500 tracking-[0.3em]">Exclusive Picks For You</div>
                            <span class="w-8 h-[2px] bg-indigo-500/50"></span>
                        </div>
                        
                        <AdHandler :adCode="$page.props.ads.native_banner" type="banner" />
                    </div>
                </div>

                <!-- Video Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-10 sm:gap-8">
                <template v-for="(video, index) in allVideos" :key="video.id">
                    <div class="group">
                        <Link :href="route('videos.show', video.slug)" class="block">
                            <div class="relative card-modern aspect-video overflow-hidden transition-all duration-500"
                                 :class="video.download_status === 'completed' ? 'ring-2 ring-green-500 shadow-[0_0_20px_rgba(34,197,94,0.15)]' : 'ring-1 ring-white/5'">
                                <!-- Shimmer Effect on hover -->
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 animate-shimmer pointer-events-none"></div>
                                
                                    <!-- Thumbnail / Placeholder -->
                                    <img 
                                        v-if="video.thumbnail_url" 
                                        :src="video.thumbnail_url" 
                                        class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110" 
                                        :class="video.is_premium && !$page.props.auth.user?.active_subscription && !$page.props.auth.user?.is_admin ? 'blur-[6px] brightness-75' : 'opacity-100'"
                                        @error="video.thumbnail_url = null"
                                        loading="lazy"
                                        alt=""
                                    />
                                    <div v-else class="w-full h-full bg-slate-800 flex flex-col items-center justify-center gap-2 group-hover:bg-slate-700 transition-colors">
                                        <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-2xl shadow-xl">🎬</div>
                                        <span v-if="$page.props.auth.user?.is_admin" class="text-[9px] font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-300 transition-colors">Sync Pending</span>
                                    </div>

                                    <!-- Local Badge (Admin Only) -->
                                    <div v-if="video.download_status === 'completed' && $page.props.auth.user?.is_admin" class="absolute top-4 right-4 bg-emerald-500/90 backdrop-blur shadow-lg text-white text-[8px] px-2 py-0.5 rounded font-black uppercase tracking-widest z-20">
                                        Local
                                    </div>

                                <!-- Tags Overlay -->
                                <div class="absolute top-4 left-4 flex flex-wrap gap-2 pr-4 z-10">
                                    <span v-if="video.is_premium" class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 shadow-[0_4px_15px_rgba(0,0,0,0.4)] text-white text-[9px] px-3 py-1.5 rounded-full font-black uppercase tracking-widest flex items-center gap-2 border border-amber-500/50">
                                        <svg class="w-3 h-3 fill-amber-300 drop-shadow-sm" viewBox="0 0 24 24">
                                            <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                        </svg>
                                        Premium
                                    </span>
                                    <span v-if="video.is_free_to_all" class="bg-blue-500/90 backdrop-blur shadow-lg text-white text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-widest">
                                        Free
                                    </span>
                                </div>

                                <!-- Play Button Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                    <div class="w-16 h-16 rounded-full glass flex items-center justify-center transform scale-75 group-hover:scale-100 transition-transform duration-500 text-white">
                                        <svg class="w-8 h-8 fill-current ms-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>

                                <!-- Bottom Info Overlay -->
                                <div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent">
                                    <h3 class="text-white font-bold truncate group-hover:text-indigo-400 transition-colors">{{ video.title }}</h3>
                                    <div class="flex items-center gap-2 mt-1 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        <span>{{ video.views }} views</span>
                                        <span>•</span>
                                        <span>Added recently</span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Native Banner Insertion (Every 16 videos for less intrusiveness but better reach) -->
                    <div v-if="(index + 1) % 16 === 0 && !$page.props.auth.user?.has_active_subscription && $page.props.ads?.native_banner" 
                         class="col-span-full"
                    >
                         <div class="relative overflow-hidden w-full">
                            <AdHandler :adCode="$page.props.ads.native_banner" type="banner" :is-isolated="true" />
                        </div>
                    </div>
                </template>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div id="infinite-scroll-sentinel" class="mt-16 h-24 flex flex-col items-center justify-center gap-4">
                <div v-if="loadingMore" class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                    <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                    <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce"></div>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-2">Loading more experiences...</span>
                </div>
                <div v-else-if="!videos.next_page_url && allVideos.length > 0" class="text-[10px] font-black text-slate-600 uppercase tracking-widest bg-white/5 px-4 py-2 rounded-full border border-white/5">
                    You've reached the end of the vault
                </div>
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
