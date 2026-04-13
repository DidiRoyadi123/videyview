<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import DynamicWatermark from '@/Components/Video/DynamicWatermark.vue';
import { ref, computed, inject, onUnmounted, watch } from 'vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';
import axios from 'axios';

const props = defineProps({
    video: Object,
    is_allowed: Boolean,
    recommended: Array,
    has_local: Boolean,
    has_videy: Boolean,
    available_mirrors: Array,
    premium_count: Number,
    // Social States
    user_like_status: String, // 'like', 'dislike', or null
    is_watchlisted: Boolean,
    watermark_text: String,
});

const isPremiumUser = computed(() => {
    return usePage().props.auth.user?.active_subscription || usePage().props.auth.user?.is_admin;
});

const activeSource = ref(() => {
    const isAdmin = usePage().props.auth.user?.is_admin;
    const isPremium = usePage().props.auth.user?.active_subscription;
    
    if (!isAdmin && !isPremium && props.available_mirrors.length > 0) {
        return props.available_mirrors[0];
    }
    
    return props.has_local ? 'local' : (props.has_videy ? 'videy' : props.available_mirrors[0]);
});

// Social Actions
const toggleLike = (type) => {
    if (!usePage().props.auth.user) {
        window.location.href = route('login');
        return;
    }
    useForm({ type }).post(route('videos.like', props.video.id), {
        preserveScroll: true
    });
};

const toggleWatchlist = () => {
    if (!usePage().props.auth.user) {
        window.location.href = route('login');
        return;
    }
    useForm({}).post(route('videos.watchlist', props.video.id), {
        preserveScroll: true
    });
};
const streamingUrl = ref(null);
const isLoadingStream = ref(false);
const hasSwitched = ref(false);

const page = usePage();

const allSources = computed(() => {
    const isAdmin = page.props.auth.user?.is_admin;
    
    // Exact mapping requested by Mandor
    const hostNames = {
        'local': isAdmin ? 'Local (Storage)' : 'Server 1',
        'streamtape': isAdmin ? 'Streamtape' : 'Server 2',
        'doodstream': isAdmin ? 'Doodstream' : 'Server 3',
        'videy': isAdmin ? 'Videy Backup' : 'Server 4'
    };
    
    const sources = [];
    
    // Check and push in specific order defined by Mandor
    if (props.has_local) {
        sources.push({ id: 'local', name: hostNames['local'], icon: 'server' });
    }
    
    if (props.available_mirrors.includes('streamtape')) {
        sources.push({ id: 'streamtape', name: hostNames['streamtape'], icon: 'mirror' });
    }

    if (props.available_mirrors.includes('doodstream')) {
        sources.push({ id: 'doodstream', name: hostNames['doodstream'], icon: 'mirror' });
    }

    if (props.has_videy) {
        sources.push({ id: 'videy', name: hostNames['videy'], icon: 'cloud' });
    }
    
    return sources;
});

const fetchStream = async (hostId) => {
    if (!props.is_allowed) return;
    
    isLoadingStream.value = true;
    try {
        const response = await axios.post(route('videos.stream', props.video.id), { host: hostId });
        streamingUrl.value = response.data.url;
    } catch (e) {
        console.error("Failed to fetch secure stream:", e);
    } finally {
        isLoadingStream.value = false;
    }
};

// Initial fetch
if (props.is_allowed) {
    fetchStream(activeSource.value);
}

// Watch for source changes
watch(activeSource, (newHost) => {
    streamingUrl.value = null;
    fetchStream(newHost);
});

const isVideoSource = computed(() => {
    if (!streamingUrl.value || typeof streamingUrl.value !== 'string') return false;
    return streamingUrl.value.includes('.mp4') || streamingUrl.value.includes('/video-proxy');
});

let waitingTimeout = null;

const handleFallback = () => {
    if (activeSource.value === 'local' && props.has_videy && !hasSwitched.value) {
        console.log("Local stream issue. Switching to Server 2...");
        activeSource.value = 'videy';
        hasSwitched.value = true;
    }
};

const handleVideoWaiting = () => {
    if (activeSource.value === 'local' && props.cdn_url && !hasSwitched.value) {
        waitingTimeout = setTimeout(handleFallback, 5000); // 5 seconds threshold
    }
};

const handleVideoPlaying = () => {
    if (waitingTimeout) {
        clearTimeout(waitingTimeout);
    }
};

onUnmounted(() => {
    if (waitingTimeout) clearTimeout(waitingTimeout);
});

const authUser = computed(() => page.props.auth.user);
const isNavigating = inject('isNavigating', false);

const commentForm = useForm({
    content: '',
});

const submitComment = () => {
    commentForm.post(route('comments.store', props.video.id), {
        onSuccess: () => {
            commentForm.reset();
        },
        preserveScroll: true,
    });
};

const deleteComment = (id) => {
    if (confirm('Are you sure you want to delete this comment?')) {
        commentForm.delete(route('comments.destroy', id), {
            preserveScroll: true,
        });
    }
};

const canDelete = (comment) => {
    if (!authUser.value) return false;
    return authUser.value.id === comment.user_id || authUser.value.is_admin;
};

const shareVideo = async () => {
    const shareData = {
        title: props.video.title,
        text: 'Check out this video on VideyView!',
        url: window.location.href
    };

    try {
        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            await navigator.clipboard.writeText(window.location.href);
            // We'll use a simple alert or toast if we had a toast system globally, 
            // but for now let's rely on the native share or just copy.
            alert('Link copied to clipboard!');
        }
    } catch (err) {
        console.error('Error sharing:', err);
    }
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head :title="video.title" />

    <MainLayout>
        <div class="relative min-h-screen flex flex-col pt-8 md:pt-16 pb-24">
            <!-- Background Glow -->
            <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[120%] h-[120%] bg-indigo-600/5 rounded-full blur-[150px]"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <!-- Royale Skeleton Context -->
                <div v-if="isNavigating" class="flex flex-col lg:flex-row gap-12 animate-pulse">
                    <!-- Left Column Skeleton -->
                    <div class="flex-grow space-y-12">
                        <div>
                            <div class="mb-8 flex items-center justify-between">
                                <div class="w-48 h-6 bg-[rgb(var(--bg-input))] rounded-2xl"></div>
                                <div class="w-24 h-8 bg-[rgb(var(--bg-input))] rounded-full"></div>
                            </div>
                            <div class="aspect-video rounded-[40px] bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] shadow-royale relative overflow-hidden"></div>
                        </div>
                        <div class="h-[600px] w-full bg-[rgb(var(--bg-input))] rounded-[40px] border border-[rgb(var(--border-main))] shadow-royale"></div>
                    </div>
                    
                    <!-- Right Column Skeleton -->
                    <div class="hidden lg:block w-96 flex-shrink-0">
                        <div class="h-[800px] w-full bg-[rgb(var(--bg-input))] rounded-[40px] border border-[rgb(var(--border-main))] shadow-royale"></div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div v-else class="flex flex-col lg:flex-row gap-12">
                    
                    <!-- Left Column -->
                    <div class="flex-grow space-y-12">
                        
                        <div>
                            <div class="mb-8 flex flex-col gap-6">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <div class="flex items-center gap-4">
                                        <Link :href="route('home')" class="group flex items-center gap-3 text-[rgb(var(--text-muted))] hover:text-indigo-500 transition font-black text-[10px] uppercase tracking-[0.2em] bg-[rgb(var(--bg-input))] px-5 py-2.5 rounded-2xl border border-[rgb(var(--border-main))] shadow-inner">
                                            <span class="group-hover:-translate-x-1 transition-transform">←</span> Back
                                        </Link>
                                        
                                        <!-- Social Royale -->
                                        <div class="flex items-center gap-2 p-1.5 bg-[rgb(var(--bg-input))] rounded-[24px] border border-[rgb(var(--border-main))] shadow-inner">
                                            <button @click="toggleLike('like')" 
                                                :class="user_like_status === 'like' ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-500/30' : 'text-[rgb(var(--text-muted))] hover:text-indigo-500 hover:bg-white/10'"
                                                class="flex items-center gap-2 px-5 py-2 rounded-[18px] transition-all active:scale-90"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" :class="user_like_status === 'like' ? 'fill-current' : 'fill-none'" class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10h4.708c.93 0 1.74.616 1.97 1.517l.732 2.86a3.605 3.605 0 01-3.605 4.623H14v1.5a2.5 2.5 0 01-5 0V11a2.5 2.5 0 012.5-2.5h1.5V10zM4 11h3v7H4v-7z" /></svg>
                                                <span class="text-[10px] font-black uppercase tracking-widest">{{ video.likes_count ?? '' }}</span>
                                            </button>
                                            
                                            <button @click="toggleWatchlist" 
                                                :class="is_watchlisted ? 'text-amber-500 bg-amber-500/10' : 'text-[rgb(var(--text-muted))] hover:text-indigo-500 hover:bg-white/10'"
                                                class="flex items-center gap-2 px-4 py-2 rounded-[18px] transition-all active:scale-90"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" :class="is_watchlisted ? 'fill-current' : 'fill-none'" class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                                            </button>
                                        </div>

                                        <button @click="shareVideo" class="flex items-center gap-3 px-6 py-2.5 bg-[rgb(var(--bg-input))] hover:bg-indigo-500/5 text-[rgb(var(--text-muted))] hover:text-indigo-500 transition-all rounded-[20px] border border-[rgb(var(--border-main))] text-[10px] font-black uppercase tracking-widest shadow-inner active:scale-95">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                                            Share
                                        </button>
                                    </div>
                                    
                                    <div class="flex items-center gap-4">
                                        <!-- Royale Source Selector -->
                                        <div v-if="allSources.length > 1" class="p-1 bg-[rgb(var(--bg-input))] rounded-[20px] border border-[rgb(var(--border-main))] shadow-inner flex gap-1">
                                            <button 
                                                v-for="source in allSources"
                                                :key="source.id"
                                                @click="activeSource = source.id"
                                                :class="activeSource === source.id ? 'bg-indigo-600 text-white shadow-lg' : 'text-[rgb(var(--text-muted))] hover:text-indigo-400'"
                                                class="px-4 py-2 rounded-[16px] text-[9px] font-black uppercase tracking-widest transition-all"
                                            >
                                                {{ source.name }}
                                            </button>
                                        </div>

                                        <div v-if="video.is_premium" class="flex gap-2">
                                             <span class="bg-amber-500/10 text-amber-600 text-[9px] px-4 py-2 rounded-full border border-amber-500/20 font-black uppercase tracking-[0.2em] shadow-lg shadow-amber-500/5">
                                                ELITE ACCESS
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
>

                                    <div v-if="video.is_premium" class="flex gap-2">
                                         <span class="bg-indigo-500/10 text-indigo-400 text-[10px] px-4 py-2 rounded-full border border-indigo-500/20 font-black uppercase tracking-widest shadow-lg backdrop-blur">
                                            EKSKLUSIF PREMIUM
                                        </span>
                                    </div>
                                </div>
                            </div>

                             <!-- Desktop Banner -->
                            <div v-if="!$page.props.auth.user?.is_admin" class="hidden lg:flex mb-6 w-full flex-col gap-2 overflow-hidden rounded-2xl">
                                <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.banner_728x90" :adCode="$page.props.ads.banner_728x90" type="banner" :is-isolated="true" />
                                <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.native_banner" :adCode="$page.props.ads.native_banner" type="banner" :is-isolated="true" />
                            </div>

                            <!-- Royale Video Player Wrapper -->
                            <div :class="[
                                is_allowed ? 'aspect-video' : 'aspect-auto min-h-[480px] sm:aspect-video',
                                'rounded-[40px] overflow-hidden shadow-royale relative group bg-black border border-[rgb(var(--border-main))] transition-all duration-700',
                                video.is_premium && is_allowed ? 'ring-2 ring-indigo-500/20' : ''
                            ]"
                            oncontextmenu="return false;">
                                <template v-if="is_allowed">
                                    <DynamicWatermark v-if="!isPremiumUser" :text="watermark_text" />
                                    <!-- Royale Loading State -->
                                    <div v-if="isLoadingStream && !streamingUrl" class="absolute inset-0 flex flex-col items-center justify-center bg-[rgb(var(--bg-main))] z-20">
                                        <div class="w-16 h-16 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-6"></div>
                                        <span class="text-[10px] font-black text-[rgb(var(--text-muted))] uppercase tracking-[0.4em] animate-pulse">Establishing Secure Stream...</span>
                                    </div>

                                    <video 
                                        v-if="isVideoSource"
                                        :src="streamingUrl" 
                                        class="w-full h-full object-contain" 
                                        controls
                                        autoplay
                                        @error="handleFallback"
                                        @waiting="handleVideoWaiting"
                                        @playing="handleVideoPlaying"
                                        oncontextmenu="return false;"
                                    ></video>
                                    <iframe 
                                        v-else-if="streamingUrl"
                                        :src="streamingUrl" 
                                        class="w-full h-full" 
                                        frameborder="0" 
                                        allow="autoplay; fullscreen" 
                                        allowfullscreen
                                        oncontextmenu="return false;"
                                    ></iframe>
                                    <div v-else-if="!isLoadingStream" class="w-full h-full flex flex-col items-center justify-center text-[rgb(var(--text-muted))] bg-black">
                                        <div class="w-16 h-16 rounded-full glass flex items-center justify-center text-2xl mb-4">🎬</div>
                                        <p class="text-[10px] font-black uppercase tracking-widest">Wait for Stream...</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <!-- Royale Lock Screen -->
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-[rgb(var(--bg-main))] text-center p-8 sm:p-16 relative overflow-hidden">
                                        <div class="absolute inset-0 opacity-10 pointer-events-none bg-gradient-to-br from-indigo-500/20 via-transparent to-amber-500/20"></div>
                                        
                                        <div class="mb-10 relative">
                                            <div class="w-24 h-24 rounded-full glass border-amber-500/30 flex items-center justify-center text-5xl shadow-royale">🔒</div>
                                            <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-[10px] animate-bounce shadow-xl shadow-amber-500/30">👑</div>
                                        </div>
                                        
                                        <h2 class="text-4xl md:text-6xl font-black text-[rgb(var(--text-main))] mb-4 tracking-tighter italic uppercase">KONTEN <span class="text-indigo-600">PREMIUM.</span></h2>
                                        <p class="text-[rgb(var(--text-muted))] max-w-lg mx-auto mb-12 text-sm md:text-lg font-medium leading-relaxed">
                                            Tayangan kualitas primer ini dikhususkan bagi anggota kami. Berlanggananlah sekarang dan saksikan <b class="text-indigo-600">{{ Number(premium_count).toLocaleString('id-ID') }} video eksklusif</b> lainnya tanpa batas!
                                        </p>
                                        
                                        <div class="flex flex-col sm:flex-row gap-4 w-full max-w-md mx-auto">
                                            <a href="https://t.me/Mandorbuah" target="_blank" class="btn-premium flex-1 py-4 text-xs shadow-[0_20px_50px_rgba(79,70,229,0.4)]">
                                                ⭐ BERLANGGANAN SEKARANG
                                            </a>
                                            <Link :href="route('login')" class="flex-1 py-4 rounded-full bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] text-[rgb(var(--text-main))] font-black text-xs uppercase tracking-widest hover:brightness-110 transition shadow-inner">
                                                TELAH MEMILIKI AKUN?
                                            </Link>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Download Video Ad Placement (Mobile-optimized position) -->
                            <div v-if="!$page.props.auth.user?.has_active_subscription && !$page.props.auth.user?.is_admin && $page.props.ads.smartlink" class="w-full mt-8 animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-500">
                                <a :href="$page.props.ads.smartlink" target="_blank" class="group relative w-full h-16 bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/20 rounded-[24px] flex items-center justify-center gap-4 transition-all duration-500 overflow-hidden shadow-2xl shadow-emerald-500/5">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-emerald-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                    <span class="text-xl group-hover:scale-125 transition-transform duration-500">📥</span>
                                    <span class="text-emerald-400 font-black text-sm uppercase tracking-[0.2em] italic group-hover:text-emerald-300 transition-colors">Unduh Video</span>
                                    <span class="text-xl group-hover:scale-125 transition-transform duration-500">📥</span>
                                </a>
                            </div>
                        </div>

                        <!-- Mobile-only Video Info -->
                        <div class="lg:hidden">
                            <div class="glass-dark p-8 rounded-[40px] border border-slate-800/50 mb-12">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="px-5 py-4 bg-white/5 rounded-3xl border border-white/5 flex flex-col gap-1 shadow-inner">
                                        <span class="text-[9px] uppercase tracking-widest text-slate-500 font-black">Audience</span>
                                        <span class="text-indigo-400 font-black tracking-tighter text-lg">{{ video.views.toLocaleString() }}</span>
                                    </div>
                                    <div class="px-5 py-4 bg-white/5 rounded-3xl border border-white/5 flex flex-col gap-1 shadow-inner">
                                        <span class="text-[9px] uppercase tracking-widest text-slate-500 font-black">Access Tier</span>
                                        <span :class="['font-black tracking-tighter text-lg uppercase', video.is_premium ? 'text-yellow-500' : 'text-green-500']">
                                            {{ video.is_premium ? 'Elite' : 'Open' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile-only Recommendations -->
                        <div class="lg:hidden glass-dark p-8 rounded-[40px] border border-slate-800/50 relative overflow-hidden mb-12">
                            <h2 class="text-2xl font-black text-white italic tracking-tighter mb-6 uppercase">Up Next</h2>
                            <div class="space-y-4">
                                <Link v-for="rec in recommended" :key="rec.id" :href="route('videos.show', rec.slug)" class="group flex items-center gap-4 bg-white/5 p-3 rounded-2xl border border-white/5 hover:border-indigo-500/30 transition shadow-inner">
                                    <div class="w-32 aspect-video rounded-xl bg-slate-900 border border-white/10 flex-shrink-0 flex items-center justify-center text-xl overflow-hidden relative shadow-lg">
                                        <img v-if="rec.thumbnail_url" :src="rec.thumbnail_url" class="w-full h-full object-cover transition-all duration-500" :class="rec.is_premium && !authUser?.active_subscription && !authUser?.is_admin ? 'blur-[6px] brightness-75' : 'opacity-100'" loading="lazy" />
                                        <span v-else class="grayscale opacity-50 text-sm">🎬</span>
                                        <div v-if="rec.is_premium" class="absolute top-1.5 left-1.5 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 shadow-[0_4px_15px_rgba(0,0,0,0.4)] text-white text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-widest flex items-center gap-1 border border-amber-500/50 z-10 origin-top-left scale-[0.75]">
                                            <svg class="w-2.5 h-2.5 fill-amber-300 drop-shadow-sm" viewBox="0 0 24 24">
                                                <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                            </svg>
                                            Premium
                                        </div>
                                        <div v-if="rec.is_free_to_all" class="absolute top-1.5 left-1.5 bg-blue-500/90 backdrop-blur shadow-lg text-white text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-widest z-10 origin-top-left scale-[0.75]">
                                            Free
                                        </div>
                                    </div>
                                    <div class="flex-grow overflow-hidden">
                                        <h4 class="text-sm font-bold text-white line-clamp-2 leading-tight">{{ rec.title }}</h4>
                                        <p class="text-xs text-slate-500 font-medium mt-2">{{ Number(rec.views).toLocaleString() }} views</p>
                                    </div>
                                </Link>
                            </div>
                        </div>


                        <!-- Royale Community Section -->
                        <div class="glass shadow-royale p-8 rounded-[40px] border border-[rgb(var(--border-main))] relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none"></div>
                            
                            <div class="flex items-center justify-between mb-12">
                                <div>
                                    <h2 class="text-3xl font-black text-[rgb(var(--text-main))] italic tracking-tighter mb-1 uppercase">Komunitas</h2>
                                    <p class="text-[rgb(var(--text-muted))] text-[10px] font-black uppercase tracking-[0.3em]">{{ video.comments.length }} Pemikiran dibagikan</p>
                                </div>
                            </div>

                            <!-- Comment Input -->
                            <div v-if="authUser" class="mb-16">
                                <form @submit.prevent="submitComment" class="space-y-4">
                                    <div class="group relative">
                                        <textarea 
                                            v-model="commentForm.content"
                                            placeholder="BAGIKAN PENGALAMAN ANDA..."
                                            class="w-full bg-[rgb(var(--bg-input))] border-none rounded-3xl p-8 text-[rgb(var(--text-main))] placeholder:text-[rgb(var(--text-muted))]/40 focus:ring-2 focus:ring-indigo-500/20 transition min-h-[140px] resize-none shadow-inner text-sm font-medium leading-relaxed"
                                        ></textarea>
                                        <div v-if="commentForm.errors.content" class="text-red-500 text-[10px] mt-4 font-black uppercase tracking-widest bg-red-500/10 px-4 py-1.5 rounded-full w-fit border border-red-500/20">
                                            ⚠ {{ commentForm.errors.content }}
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button 
                                            type="submit" 
                                            :disabled="commentForm.processing"
                                            class="btn-premium !px-12 !py-4 shadow-xl"
                                        >
                                            KIRIM KOMENTAR
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div v-else class="mb-16 p-10 rounded-[40px] bg-indigo-500/5 border border-indigo-500/10 text-center relative overflow-hidden group shadow-inner">
                                <div class="mb-6 flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-full glass border-indigo-500/20 flex items-center justify-center text-3xl mb-4 shadow-xl">💬</div>
                                    <p class="text-[rgb(var(--text-main))] text-sm font-black uppercase tracking-[0.3em] italic flex items-center justify-center gap-3">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                        Diskusi Terkunci
                                    </p>
                                </div>
                                <p class="text-[rgb(var(--text-muted))] text-sm mb-10 font-medium max-w-sm mx-auto">Bergabunglah dengan komunitas elit kami secara gratis untuk berbagi pemikiran dan berinteraksi dengan orang lain.</p>
                                <div class="flex flex-wrap justify-center gap-4">
                                    <Link :href="route('register')" class="btn-premium !px-8 !py-3 shadow-lg shadow-indigo-500/20">DAFTAR SECARA GRATIS</Link>
                                    <Link :href="route('login')" class="px-8 py-3 bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] text-[rgb(var(--text-main))] font-black uppercase tracking-widest text-[10px] rounded-2xl hover:brightness-110 transition shadow-inner">MASUK</Link>
                                </div>
                            </div>

                            <!-- Comment List -->
                            <div class="space-y-12">
                                <div v-for="comment in video.comments" :key="comment.id" class="group relative flex gap-8 p-6 rounded-[32px] hover:bg-white/40 transition-all duration-500 border border-transparent hover:border-[rgb(var(--border-main))] hover:shadow-xl hover:shadow-indigo-500/5">
                                    <div class="flex-shrink-0">
                                        <div :class="[
                                            'w-16 h-16 rounded-[24px] flex items-center justify-center font-black text-2xl shadow-xl relative transition-transform group-hover:scale-110',
                                            comment.user.active_subscription ? 'bg-gradient-to-br from-indigo-500 to-indigo-700 text-white ring-4 ring-indigo-500/10 shadow-indigo-500/30' : 'bg-[rgb(var(--bg-input))] text-[rgb(var(--text-muted))]'
                                        ]">
                                            {{ comment.user.name.charAt(0).toUpperCase() }}
                                            <div v-if="comment.user.active_subscription" class="absolute -top-1.5 -right-1.5 w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center text-[11px] border-4 border-[rgb(var(--bg-main))] shadow-lg">👑</div>
                                        </div>
                                    </div>
                                    <div class="flex-grow pt-1">
                                        <div class="flex items-center gap-4 mb-3 flex-wrap">
                                            <span class="text-sm font-black text-[rgb(var(--text-main))] tracking-tight uppercase">{{ comment.user.name }}</span>
                                            <span v-if="comment.user.active_subscription" class="bg-indigo-500 text-white text-[9px] px-3 py-1 rounded-full font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">PREMIUM</span>
                                            <span class="text-[10px] text-[rgb(var(--text-muted))] font-bold uppercase tracking-widest ml-auto">{{ formatDate(comment.created_at) }}</span>
                                        </div>
                                        <p class="text-[rgb(var(--text-muted))] text-sm leading-relaxed font-medium">
                                            {{ comment.content }}
                                        </p>
                                        <div v-if="canDelete(comment)" class="mt-6 opacity-0 group-hover:opacity-100 transition-all">
                                            <button @click="deleteComment(comment.id)" class="text-[9px] text-red-500/60 hover:text-red-500 font-black uppercase tracking-[0.2em] transition-colors bg-red-500/5 px-4 py-1.5 rounded-full border border-red-500/10">Hapus Kiriman</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="video.comments.length === 0" class="text-center py-24 opacity-40">
                                    <div class="text-6xl mb-6">💬</div>
                                    <p class="text-[rgb(var(--text-muted))] text-sm font-black uppercase tracking-[0.3em] italic">Vault silence is golden...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sidebar -->
                    <div class="hidden lg:block w-96 flex-shrink-0">
                        <div class="sticky top-28 space-y-12">
                            <div class="glass shadow-royale p-6 rounded-[40px] border border-[rgb(var(--border-main))] relative overflow-hidden group">
                                <div class="mb-4 w-full overflow-hidden rounded-[24px] shadow-inner">
                                    <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.banner_300x250" :adCode="$page.props.ads.banner_300x250" type="banner" :is-isolated="true" />
                                </div>
                            </div>

                            <div>
                                <h3 class="flex items-center gap-4 text-[10px] font-black uppercase tracking-[0.4em] text-[rgb(var(--text-muted))] mb-8">
                                    <span class="w-8 h-px bg-indigo-500/30"></span>
                                    SELANJUTNYA
                                </h3>
                                <div class="space-y-6">
                                    <Link v-for="rec in recommended" :key="rec.id" :href="route('videos.show', rec.slug)" class="group flex items-center gap-5 p-3 -mx-3 rounded-[24px] hover:bg-[rgb(var(--bg-input))] transition-all duration-500 border border-transparent hover:border-[rgb(var(--border-main))] hover:shadow-royale">
                                        <div class="w-28 aspect-video rounded-2xl bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] flex-shrink-0 flex items-center justify-center text-xl overflow-hidden relative shadow-lg group-hover:scale-105 transition-transform duration-500">
                                            <img v-if="rec.thumbnail_url" :src="rec.thumbnail_url" class="w-full h-full object-cover transition-all duration-700" :class="rec.is_premium && !authUser?.active_subscription && !authUser?.is_admin ? 'blur-md brightness-75' : 'opacity-100'" loading="lazy" />
                                            <span v-else class="grayscale opacity-20 text-sm">🎬</span>
                                            
                                            <div v-if="rec.is_premium" class="absolute top-2 left-2 bg-gradient-to-r from-amber-500 to-amber-700 shadow-xl text-white text-[8px] px-2 py-0.5 rounded-full font-black uppercase tracking-widest border border-amber-400/30 z-10">
                                                ★
                                            </div>
                                        </div>
                                        <div class="flex-grow overflow-hidden">
                                            <h4 class="text-xs font-black text-[rgb(var(--text-main))] line-clamp-2 uppercase tracking-tight group-hover:text-indigo-600 transition-colors leading-relaxed">{{ rec.title }}</h4>
                                            <p class="text-[9px] text-[rgb(var(--text-muted))] font-black uppercase tracking-widest mt-2">{{ Number(rec.views).toFixed(1) }}k views</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    </MainLayout>
</template>

<style scoped>
.animate-shimmer {
    background-size: 200% 200%;
    animation: shimmer 5s ease infinite;
}

@keyframes shimmer {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.tracking-tighter {
    letter-spacing: -0.04em;
}

/* Premium Player Skin */
.premium-player::-webkit-media-controls-panel {
    background-color: rgb(var(--glass-bg)) !important;
    backdrop-filter: blur(10px);
}

.premium-player::-webkit-media-controls-play-button,
.premium-player::-webkit-media-controls-mute-button,
.premium-player::-webkit-media-controls-timeline,
.premium-player::-webkit-media-controls-current-time-display,
.premium-player::-webkit-media-controls-time-remaining-display,
.premium-player::-webkit-media-controls-volume-slider,
.premium-player::-webkit-media-controls-fullscreen-button {
    filter: sepia(100%) saturate(300%) hue-rotate(200deg) brightness(100%);
    transition: all 0.3s ease;
}

.premium-player::-webkit-media-controls-play-button:hover {
    transform: scale(1.1);
    filter: sepia(100%) saturate(500%) hue-rotate(200deg) brightness(120%);
}
</style>
