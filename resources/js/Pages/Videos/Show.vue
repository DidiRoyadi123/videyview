<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { ref, computed, inject, onUnmounted, watch } from 'vue';
import AdHandler from '@/Components/Ads/AdHandler.vue';

const props = defineProps({
    video: Object,
    is_allowed: Boolean,
    recommended: Array,
    has_local: Boolean,
    has_videy: Boolean,
    available_mirrors: Array,
});

const activeSource = ref(props.has_local ? 'local' : (props.has_videy ? 'videy' : props.available_mirrors[0]));
const streamingUrl = ref(null);
const isLoadingStream = ref(false);
const hasSwitched = ref(false);

const allSources = computed(() => {
    const sources = [];
    if (props.has_local) sources.push({ id: 'local', name: 'Server 1', icon: 'server' });
    if (props.has_videy) sources.push({ id: 'videy', name: 'Server 2', icon: 'cloud' });
    
    props.available_mirrors.forEach((host, index) => {
        sources.push({ 
            id: host, 
            name: 'Server ' + (sources.length + 1), 
            icon: 'mirror'
        });
    });
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

const page = usePage();
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
                <!-- Skeleton Context Matching Exact Layout -->
                <div v-if="isNavigating" class="flex flex-col lg:flex-row gap-12 animate-pulse">
                    <!-- Left Column Skeleton -->
                    <div class="flex-grow space-y-12">
                        <div>
                            <div class="mb-6 flex items-center justify-between">
                                <div class="w-32 h-4 bg-slate-800 rounded"></div>
                                <div class="w-24 h-6 bg-slate-800 rounded-full hidden sm:block"></div>
                            </div>
                            <div class="aspect-video rounded-[32px] bg-slate-900 border border-slate-800 relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div>
                            </div>
                        </div>
                        <div class="lg:hidden space-y-12">
                            <div class="h-64 w-full bg-slate-900 rounded-[40px] border border-slate-800 mb-12 relative overflow-hidden"><div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div></div>
                            <div class="h-[400px] w-full bg-slate-900 rounded-[40px] border border-slate-800 mb-12 relative overflow-hidden"><div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div></div>
                        </div>
                        <div class="h-[500px] w-full bg-slate-900 rounded-[40px] border border-slate-800 relative overflow-hidden"><div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div></div>
                    </div>
                    
                    <!-- Right Column Skeleton -->
                    <div class="hidden lg:block w-96 flex-shrink-0">
                        <div class="sticky top-24 space-y-8">
                            <div class="h-[800px] w-full bg-slate-900 rounded-[40px] border border-slate-800 relative overflow-hidden"><div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-800 to-slate-900"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid (Original Flow) -->
                <div v-else class="flex flex-col lg:flex-row gap-12">
                    
                    <!-- Left Column: Player & Comments -->
                    <div class="flex-grow space-y-12">
                        
                        <div>
                            <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                                <div class="w-full lg:hidden mb-4 flex flex-col gap-2">
                                    <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.banner_468x60" :adCode="$page.props.ads.banner_468x60" type="banner" :is-isolated="true" />
                                    <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.native_banner" :adCode="$page.props.ads.native_banner" type="banner" :is-isolated="true" />
                                </div>
                                <Link :href="route('home')" class="group flex items-center gap-2 text-slate-400 hover:text-white transition font-bold text-sm">
                                    <span class="group-hover:-translate-x-1 transition-transform">←</span> Back to Discover
                                </Link>
                                
                                <div class="flex items-center gap-4">
                                    <!-- Multi-Host Source Selector (Mobile Friendly Grid) -->
                                    <div v-if="allSources.length > 1" class="w-full sm:w-auto p-1.5 bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-white/10 shadow-2xl ring-1 ring-white/5 grid grid-cols-2 sm:flex sm:flex-wrap gap-1">
                                        <button 
                                            v-for="source in allSources"
                                            :key="source.id"
                                            @click="activeSource = source.id"
                                            :class="activeSource === source.id ? 'bg-indigo-600 text-white shadow-[0_0_20px_rgba(79,70,229,0.4)] border-white/20' : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'"
                                            class="flex items-center justify-center sm:justify-start gap-2 px-3 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all duration-300 border whitespace-nowrap"
                                        >
                                            <svg v-if="source.icon === 'server'" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                                            <svg v-else-if="source.icon === 'cloud'" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                            <span>{{ source.name }}</span>
                                        </button>
                                    </div>

                                    <div v-if="video.is_premium" class="flex gap-2">
                                         <span class="bg-indigo-500/10 text-indigo-400 text-[10px] px-4 py-2 rounded-full border border-indigo-500/20 font-black uppercase tracking-widest shadow-lg backdrop-blur">
                                            PREMIUM EXCLUSIVE
                                        </span>
                                    </div>
                                </div>
                            </div>

                             <!-- Desktop Banner -->
                            <div v-if="!$page.props.auth.user?.is_admin" class="hidden lg:flex mb-6 w-full flex-col gap-2 overflow-hidden rounded-2xl">
                                <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.banner_728x90" :adCode="$page.props.ads.banner_728x90" type="banner" :is-isolated="true" />
                                <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.native_banner" :adCode="$page.props.ads.native_banner" type="banner" :is-isolated="true" />
                            </div>

                            <!-- Video Player Wrapper -->
                            <div :class="[
                                is_allowed ? 'aspect-video' : 'aspect-auto min-h-[480px] sm:aspect-video',
                                'rounded-[32px] overflow-hidden shadow-[0_32px_100px_rgba(0,0,0,0.8)] relative group bg-slate-900 border border-white/5 transition-all duration-700',
                                video.is_premium && is_allowed ? 'ring-2 ring-indigo-500/30' : ''
                            ]"
                            oncontextmenu="return false;">
                                <template v-if="is_allowed">
                                    <!-- Loading Shimmer -->
                                    <div v-if="isLoadingStream && !streamingUrl" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 z-20">
                                        <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] animate-pulse">Securing Connection...</span>
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
                                        allow="autoplay; fullscreen; picture-in-picture" 
                                        allowfullscreen
                                        oncontextmenu="return false;"
                                    ></iframe>
                                    <!-- Fallback if still no URL after loading -->
                                    <div v-else-if="!isLoadingStream" class="w-full h-full flex flex-col items-center justify-center text-slate-600 bg-slate-950">
                                        <span class="text-4xl mb-4">📺</span>
                                        <p class="text-xs font-black uppercase tracking-widest text-slate-500">Wait for Stream...</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-slate-950 text-center p-6 sm:p-12 relative overflow-hidden">
                                        <div class="absolute inset-0 opacity-20 pointer-events-none animate-shimmer bg-gradient-to-br from-indigo-500/20 via-slate-900 to-violet-500/20"></div>
                                        
                                        <div class="mb-6 sm:mb-10 relative">
                                            <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-full glass border-indigo-500/50 flex items-center justify-center text-3xl sm:text-5xl shadow-2xl shadow-indigo-500/20">
                                                🔒
                                            </div>
                                            <div class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] animate-bounce shadow-xl">👑</div>
                                        </div>
                                        
                                        <h2 class="text-3xl sm:text-5xl font-black text-white mb-4 tracking-tighter italic uppercase">Members <span class="text-indigo-400">Only.</span></h2>
                                        <p class="text-slate-400 max-w-sm mx-auto mb-8 sm:mb-10 text-sm sm:text-lg font-medium leading-relaxed px-2">
                                            This premium experience is reserved for our supporters. Get instant access to this and thousands of other exclusives now.
                                        </p>
                                        
                                        <div class="flex flex-col xs:flex-row gap-3 sm:gap-4 w-full max-w-sm">
                                            <Link :href="route('login')" class="btn-premium flex-1 py-3 text-xs sm:text-sm">
                                                SIGN IN
                                            </Link>
                                            <a :href="authUser ? 'https://t.me/Mandorbuah' : 'https://t.me/buahmanis1'" target="_blank" class="flex-1 px-6 sm:px-8 py-3 rounded-full glass border-white/10 text-white font-black text-xs sm:text-sm uppercase tracking-widest hover:bg-white/5 transition flex items-center justify-center gap-2">
                                                {{ authUser ? 'CONTACT SUPPORT' : 'JOIN CHANNEL' }}
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Download Video Ad Placement (Mobile-optimized position) -->
                            <div v-if="!$page.props.auth.user?.has_active_subscription && !$page.props.auth.user?.is_admin && $page.props.ads.smartlink" class="w-full mt-8 animate-in fade-in slide-in-from-bottom-8 duration-1000 delay-500">
                                <a :href="$page.props.ads.smartlink" target="_blank" class="group relative w-full h-16 bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/20 rounded-[24px] flex items-center justify-center gap-4 transition-all duration-500 overflow-hidden shadow-2xl shadow-emerald-500/5">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-emerald-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                    <span class="text-xl group-hover:scale-125 transition-transform duration-500">📥</span>
                                    <span class="text-emerald-400 font-black text-sm uppercase tracking-[0.2em] italic group-hover:text-emerald-300 transition-colors">Download Video</span>
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


                        <!-- Community / Comments Section -->
                        <div class="glass-dark p-8 rounded-[40px] border border-slate-800/50 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none"></div>
                            
                            <div class="flex items-center justify-between mb-10">
                                <div>
                                    <h2 class="text-3xl font-black text-white italic tracking-tighter mb-1 uppercase">Community</h2>
                                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">{{ video.comments.length }} Thoughts shared</p>
                                </div>
                            </div>

                            <!-- Comment Input -->
                            <div v-if="authUser" class="mb-12">
                                <form @submit.prevent="submitComment" class="space-y-4">
                                    <div class="group relative">
                                        <textarea 
                                            v-model="commentForm.content"
                                            placeholder="Write a respectful comment..."
                                            class="w-full bg-slate-900/50 border border-slate-800 rounded-3xl p-6 text-slate-200 placeholder-slate-600 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition min-h-[120px] resize-none shadow-inner"
                                        ></textarea>
                                        <div v-if="commentForm.errors.content" class="text-red-500 text-[10px] mt-2 font-black uppercase tracking-widest bg-red-500/10 px-3 py-1 rounded-full w-fit">
                                            ⚠ {{ commentForm.errors.content }}
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button 
                                            type="submit" 
                                            :disabled="commentForm.processing"
                                            class="btn-premium px-10 py-3 text-xs"
                                        >
                                            POST COMMENT
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div v-else class="mb-12 p-8 rounded-[32px] bg-indigo-500/5 border border-indigo-500/10 text-center relative overflow-hidden group">
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/0 via-indigo-500/5 to-indigo-500/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                <div class="mb-4 flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-indigo-500/10 flex items-center justify-center text-xl mb-2">💬</div>
                                    <p class="text-slate-200 text-sm font-black uppercase tracking-widest italic flex items-center justify-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                        Discussion Locked
                                    </p>
                                </div>
                                <p class="text-slate-500 text-xs mb-6 font-medium">Join our community for free to share your thoughts and interact with others.</p>
                                <div class="flex justify-center gap-4">
                                    <Link :href="route('register')" class="px-6 py-2 bg-indigo-600 text-white font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-indigo-500 transition shadow-lg">Sign Up for Free</Link>
                                    <a href="https://t.me/buahmanis1" target="_blank" class="px-6 py-2 border border-white/10 text-slate-400 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-white/5 transition">Join Telegram</a>
                                </div>
                            </div>

                            <!-- Comment List -->
                            <div class="space-y-10">
                                <div v-for="comment in video.comments" :key="comment.id" class="group relative flex gap-6">
                                    <div class="flex-shrink-0">
                                        <div :class="[
                                            'w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl shadow-2xl relative transition-transform group-hover:scale-105',
                                            comment.user.active_subscription ? 'bg-gradient-to-br from-yellow-300 via-yellow-500 to-yellow-600 text-yellow-950 ring-2 ring-yellow-400/30' : 'bg-slate-800 text-slate-400'
                                        ]">
                                            {{ comment.user.name.charAt(0).toUpperCase() }}
                                            <div v-if="comment.user.active_subscription" class="absolute -top-1 -right-1 w-5 h-5 bg-yellow-400 rounded-full flex items-center justify-center text-[10px] border-2 border-slate-950">👑</div>
                                        </div>
                                    </div>
                                    <div class="flex-grow pt-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-sm font-black text-white tracking-tight uppercase">{{ comment.user.name }}</span>
                                            
                                            <span v-if="comment.user.active_subscription" class="flex items-center gap-1.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white text-[9px] px-3 py-1.5 rounded-full border border-amber-500/30 font-black uppercase tracking-widest shadow-lg shadow-amber-900/20">
                                                <svg class="w-3 h-3 fill-amber-300" viewBox="0 0 24 24">
                                                    <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                                </svg>
                                                PREMIUM MEMBER
                                            </span>
                                            
                                            <span class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">{{ formatDate(comment.created_at) }}</span>
                                        </div>
                                        <p class="text-slate-400 text-sm leading-relaxed font-medium">
                                            {{ comment.content }}
                                        </p>
                                        
                                        <div v-if="canDelete(comment)" class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="deleteComment(comment.id)" class="text-[9px] text-red-500/40 hover:text-red-500 font-black uppercase tracking-widest transition-colors">Remove Post</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="video.comments.length === 0" class="text-center py-20">
                                    <div class="text-6xl mb-6 opacity-10">💬</div>
                                    <p class="text-slate-600 text-sm font-bold uppercase tracking-widest italic leading-loose">Silence is golden,<br>but your thoughts are better.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sidebar Video Info -->
                    <div class="hidden lg:block w-96 flex-shrink-0">
                        <div class="sticky top-24 space-y-8">
                            <div class="glass-dark p-8 rounded-[40px] border border-slate-800/50 relative overflow-hidden group">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>
                                
                                <div class="mb-4 w-full overflow-hidden rounded-2xl">
                                    <AdHandler v-if="!$page.props.auth.user?.has_active_subscription && $page.props.ads.banner_300x250" :adCode="$page.props.ads.banner_300x250" type="banner" :is-isolated="true" />
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-10">
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
                                <div class="space-y-8">
                                    <div class="flex flex-col gap-4">
                                        <div v-if="authUser" class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl mb-2">
                                            <p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Member Priority
                                            </p>
                                            <p class="text-[10px] text-slate-400 mb-3 leading-relaxed">Logged in as member? Contact Mandor directly for any requests.</p>
                                            <a href="https://t.me/Mandorbuah" target="_blank" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                                                Contact @Mandorbuah
                                            </a>
                                        </div>
                                        <a v-else href="https://t.me/buahmanis1" target="_blank" class="w-full h-14 bg-indigo-500 text-white py-4 rounded-[20px] font-black text-xs tracking-widest uppercase hover:bg-indigo-600 transition shadow-2xl flex items-center justify-center gap-2">
                                            Join Our Telegram
                                        </a>
                                    </div>
                                </div>

                                    <div class="pt-0">
                                         <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-4 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span>
                                            Up Next
                                        </h3>
                                        <div class="space-y-4">
                                            <Link v-for="rec in recommended" :key="rec.id" :href="route('videos.show', rec.slug)" class="group flex items-center gap-4 p-2 -mx-2 rounded-2xl hover:bg-white/5 transition">
                                                <div class="w-24 aspect-video rounded-xl bg-slate-900 border border-white/10 flex-shrink-0 flex items-center justify-center text-xl overflow-hidden relative group-hover:border-indigo-500/50 transition shadow-lg shrink-0">
                                                    <img v-if="rec.thumbnail_url" :src="rec.thumbnail_url" class="w-full h-full object-cover transition-all duration-500" :class="rec.is_premium && !authUser?.active_subscription && !authUser?.is_admin ? 'blur-[6px] brightness-75' : 'opacity-100'" loading="lazy" />
                                                    <span v-else class="grayscale opacity-50 text-xs">🎬</span>
                                                    <div v-if="rec.is_premium" class="absolute top-1.5 left-1.5 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 shadow-[0_4px_15px_rgba(0,0,0,0.4)] text-white text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-widest flex items-center gap-1 border border-amber-500/50 z-10 origin-top-left scale-[0.65]">
                                                        <svg class="w-2.5 h-2.5 fill-amber-300" viewBox="0 0 24 24">
                                                            <path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.6 18.6 20 18 20H6C5.4 20 5 19.6 5 19V18H19V19Z" />
                                                        </svg>
                                                        Premium
                                                    </div>
                                                    <div v-if="rec.is_free_to_all" class="absolute top-1.5 left-1.5 bg-blue-500/90 backdrop-blur shadow-lg text-white text-[9px] px-1.5 py-0.5 rounded-full font-black uppercase tracking-widest z-10 origin-top-left scale-[0.65]">
                                                        Free
                                                    </div>
                                                </div>
                                                <div class="flex-grow overflow-hidden">
                                                    <h4 class="text-xs font-bold text-white line-clamp-2 group-hover:text-indigo-400 transition pr-2">{{ rec.title }}</h4>
                                                    <p class="text-[10px] text-slate-500 font-medium mt-1">{{ Number(rec.views).toLocaleString() }} views</p>
                                                </div>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
</style>
