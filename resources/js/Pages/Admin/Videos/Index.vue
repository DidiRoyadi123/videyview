<script setup>
import { useForm, router, usePage, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/Composables/useToast';
import axios from 'axios';

const { success: toastSuccess, error: toastError } = useToast();

const props = defineProps({
    videos: Object,
    total_local: Number,
    total_download_pending: Number,
    total_pending: Number,
    total_mirrored: Number,
    total_mirror_pending: Number,
    total_active_downloads: Number,
    total_active_mirrors: Number,
    host_stats: Object,
    recent_activity: Array,
    proxy_enabled: Boolean,
    categories: Array,
});

const proxyActive = ref(props.proxy_enabled);

const toggleProxy = () => {
    router.post(route('admin.settings.update'), {
        key: 'proxy_enabled',
        value: proxyActive.value ? '1' : '0'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toastSuccess('Proxy setting updated.');
        }
    });
};

const downloadProgress = ref({});
const uploadProgress = ref({});
const activeDownloads = ref(props.total_active_downloads || 0);
const activeMirrors = ref(props.total_active_mirrors || 0);
const localReady = ref(props.total_local || 0);
const mirroredTotal = ref(props.total_mirrored || 0);
const downloadPendingTotal = ref(props.total_download_pending || 0);
const hostStats = ref(props.host_stats || { streamtape: 0, doodstream: 0 });
const recentActivityList = ref(props.recent_activity || []);

const averageDownloadProgress = ref(0);
const averageUploadProgress = ref(0);

const uploadType = ref('url');

const form = useForm({
    title: '',
    url: '',
    video_file: null,
    is_premium: true,
    skip_download: false,
    category_id: '',
});

const bulkForm = useForm({
    ids: [],
    action: '',
    file: null,
});

const deleteForm = useForm({
    ids: [],
});

const editForm = useForm({
    id: null,
    title: '',
    category_id: '',
    is_premium: true,
});

const showEditModal = ref(false);

const openEditModal = (video) => {
    editForm.id = video.id;
    editForm.title = video.title;
    editForm.category_id = video.category_id || '';
    editForm.is_premium = !!video.is_premium;
    showEditModal.value = true;
};

const updateVideo = () => {
    editForm.patch(route('admin.videos.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
            toastSuccess('Video updated successfully.');
        },
    });
};

const selectedIds = ref([]);

const showPreviewModal = ref(false);
const showMirrorConfirmModal = ref(false);
const mirrorLoading = ref(false);
const selectedVideo = ref(null);
const mirrorTargetHost = ref(null);

const openPreview = (video) => {
    selectedVideo.value = video;
    showPreviewModal.value = true;
};

const getPreviewUrl = (video) => {
    if (!video) return '';
    return video.local_url || video.proxy_url || '';
};

const isLocal = (video) => video && video.download_status === 'completed';

const isAllSelected = computed(() => {
    return props.videos.data.length > 0 && selectedIds.value.length === props.videos.data.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = props.videos.data.map(v => v.id);
    } else {
        selectedIds.value = [];
    }
};

const submit = () => {
    form.post(route('admin.videos.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            uploadType.value = 'url';
        },
    });
};

const aiLoading = ref(false);
const aiSuggest = async (sourceForm) => {
    const input = sourceForm.title || sourceForm.url || '';
    if (input.length < 3) {
        toastError('Input too short for AI suggestion.');
        return;
    }

    aiLoading.value = true;
    try {
        const response = await axios.post(route('admin.videos.suggest-metadata'), { input });
        if (response.data.success) {
            const data = response.data.data;
            sourceForm.title = data.title;
            
            // Map category name to ID
            if (data.category) {
                const category = props.categories.find(c => 
                    c.name.toLowerCase() === data.category.toLowerCase()
                );
                if (category) sourceForm.category_id = category.id;
            }
            
            toastSuccess('AI suggested metadata applied!');
        }
    } catch (err) {
        toastError(err.response?.data?.error || 'AI Suggestion failed.');
    } finally {
        aiLoading.value = false;
    }
};


const submitBulk = () => {
    bulkForm.post(route('admin.videos.bulk'), {
        onSuccess: () => bulkForm.reset(),
    });
};

const deleteVideo = (id) => {
    if (confirm('Are you sure you want to delete this video?')) {
        router.delete(route('admin.videos.destroy', id), {
            onSuccess: () => {
                selectedIds.value = selectedIds.value.filter(sid => sid !== id);
            },
        });
    }
};

const downloadVideo = (id) => {
    router.post(route('admin.videos.download', id), {}, {
        preserveScroll: true
    });
};

const downloadAllMissing = () => {
    router.post(route('admin.videos.bulk-download-all'), {}, {
        onSuccess: () => toastSuccess('Download queue started.'),
        preserveScroll: true
    });
};

const mirrorAllMissing = () => {
    mirrorTargetHost.value = null; // null means all hosts
    showMirrorConfirmModal.value = true;
};

const confirmMirrorBulk = () => {
    showMirrorConfirmModal.value = false;
    mirrorLoading.value = true;
    router.post(route('admin.videos.mirror-bulk'), { host: 'streamtape' }, {
        onSuccess: () => {
            toastSuccess('Bulk mirroring queue started.');
            mirrorLoading.value = false;
            router.reload({ only: ['videos', 'total_mirrored', 'host_stats', 'recent_activity'] });
        },
        onError: () => {
            mirrorLoading.value = false;
        },
        preserveScroll: true
    });
};

const deleteSelected = () => {
    if (selectedIds.value.length === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selectedIds.value.length} videos?`)) {
        router.delete(route('admin.videos.bulk-destroy'), {
            data: { ids: selectedIds.value },
            onSuccess: () => {
                selectedIds.value = [];
            },
            onError: (errors) => {
                if (errors.ids) toastError(errors.ids);
            }
        });
    }
};

const mirrorSelected = () => {
    if (selectedIds.value.length === 0) return;
    
    if (confirm(`Mirror ${selectedIds.value.length} selected videos to all hosts?`)) {
        router.post(route('admin.videos.mirror-selected'), { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: (page) => {
                toastSuccess(page.props.flash?.success || 'Mirroring jobs dispatched.');
                selectedIds.value = [];
            },
            onError: (errors) => {
                if (errors.ids) toastError(errors.ids);
            }
        });
    }
};

const downloadSelected = () => {
    if (selectedIds.value.length === 0) return;
    
    if (confirm(`Queue ${selectedIds.value.length} videos for background downloading?`)) {
        router.post(route('admin.videos.bulk-download'), { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: (page) => {
                selectedIds.value = [];
                toastSuccess(page.props.flash?.success || `${selectedIds.value.length} downloads queued.`);
            },
            onError: (errors) => {
                if (errors.ids) toastError(errors.ids);
            }
        });
    }
};

const syncStorage = (id) => {
    router.post(route('admin.videos.sync-storage', id), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) toastSuccess(page.props.flash.success);
            else if (page.props.flash?.info) toastSuccess(page.props.flash.info);
        }
    });
};

const syncAllStorage = () => {
    if (confirm('Scan storage for ALL missing videos? This will update database if files are found.')) {
        router.post(route('admin.videos.sync-all-storage'), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                toastSuccess(page.props.flash?.success || 'Storage sync completed.');
            }
        });
    }
};

const downloadSyncManifest = () => {
    window.location.href = route('admin.videos.export-sync');
};

const runHealthCheck = () => {
    if (confirm('Run health check for all videos? This will dispatch background jobs to verify file availability.')) {
        router.post(route('admin.videos.check-health'), {}, {
            preserveScroll: true,
            onSuccess: () => toastSuccess('Health check jobs dispatched.')
        });
    }
};

const distributeVideo = (id, host = null) => {
    router.post(route('admin.videos.distribute', id), { host }, {
        preserveScroll: true,
        onSuccess: () => {
            toastSuccess('Mirroring job dispatched.');
        }
    });
};

const copyMaskedLink = (video) => {
    const maskedUrl = `${window.location.origin}/v/${video.slug}.mp4`;
    navigator.clipboard.writeText(maskedUrl).then(() => {
        toastSuccess('Masked link copied to clipboard!');
    }).catch(err => {
        toastError('Failed to copy link.');
        console.error(err);
    });
};

const exportSocialLinks = (all = false) => {
    const ids = all ? [] : selectedIds.value;
    
    toastSuccess(all ? 'Generating ALL video links...' : 'Generating selected video links...');
    
    axios.post(route('admin.videos.export-social'), { ids, export_all: all }, {
        responseType: 'blob'
    }).then((response) => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        // Extract filename from content-disposition if possible, else default
        const contentDisposition = response.headers['content-disposition'];
        let fileName = `Social_Links_Export_${new Date().toISOString().slice(0,10)}.csv`;
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="(.+)"/);
            if (fileNameMatch && fileNameMatch.length === 2) fileName = fileNameMatch[1];
        }
        
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
        toastSuccess('Export completed!');
    }).catch((err) => {
        toastError('Failed to generate export.');
        console.error('Export error:', err);
    });
};

const getMirrorStatusClass = (status) => {
    if (!status) return 'bg-slate-700';
    if (status === 'success') return 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
    if (status.startsWith('failed')) return 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]';
    if (status === 'uploading') return 'bg-blue-500 animate-pulse shadow-[0_0_8px_rgba(59,130,246,0.4)]';
    if (status === 'skipped') return 'bg-amber-500/50 border-amber-500/30';
    return 'bg-slate-700';
};

const getMirrorStatusLabel = (status) => {
    if (!status) return 'Pending';
    // Handle "failed: reason" format
    if (status.startsWith('failed:')) {
        return 'Failed: ' + status.replace('failed:', '').trim();
    }
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const visibleVideos = ref(new Set());
let observer = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                visibleVideos.value.add(entry.target.dataset.id);
            }
        });
    }, { rootMargin: '200px' });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
    if (pollInterval) clearInterval(pollInterval);
});

const observeVideo = (el, id) => {
    if (el && observer) {
        el.dataset.id = id;
        observer.observe(el);
    }
};

let pollInterval = null;

const startPollingProgress = () => {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(async () => {
        const activeIds = props.videos.data
            .filter(v => 
                v.download_status === 'downloading' || 
                (v.hosting_status && Object.values(v.hosting_status).some(s => s === 'uploading' || s === 'pending'))
            )
            .map(v => v.id);
            
        try {
            const response = await axios.get(route('admin.videos.progress'), {
                params: { ids: activeIds }
            });
            
            if (response.data.progress) {
                downloadProgress.value = { ...downloadProgress.value, ...response.data.progress };
            }
            if (response.data.uploadProgress) {
                uploadProgress.value = { ...uploadProgress.value, ...response.data.uploadProgress };
            }

            if (response.data.mirroring) {
                Object.entries(response.data.mirroring).forEach(([id, status]) => {
                    const video = props.videos.data.find(v => v.id == id);
                    if (video) video.hosting_status = status;
                });
            }

            if (response.data.global_stats) {
                activeDownloads.value = response.data.global_stats.total_active_downloads;
                activeMirrors.value = response.data.global_stats.total_active_mirrors;
                localReady.value = response.data.global_stats.total_local;
                mirroredTotal.value = response.data.global_stats.total_mirrored;
                downloadPendingTotal.value = response.data.global_stats.total_download_pending || 0;
                
                if (response.data.global_stats.global_download_avg !== undefined) {
                    averageDownloadProgress.value = response.data.global_stats.global_download_avg;
                }
                if (response.data.global_stats.global_upload_avg !== undefined) {
                    averageUploadProgress.value = response.data.global_stats.global_upload_avg;
                }
                if (response.data.global_stats.host_stats) {
                    hostStats.value = response.data.global_stats.host_stats;
                }
            }

            if (response.data.recent_activity) {
                recentActivityList.value = response.data.recent_activity;
            }
            
            const finishedDownloadIds = Object.entries(response.data.progress || {})
                .filter(([id, p]) => p >= 100)
                .map(([id]) => id);
                
            if (finishedDownloadIds.length > 0) {
                toastSuccess(`${finishedDownloadIds.length} video(s) completed downloading!`);
                router.reload({ only: ['videos'] });
            }
        } catch (error) {
            console.error("Failed to fetch progress", error);
        }
    }, 3000);
};

onMounted(() => startPollingProgress());
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval); });
</script>

<template>
    <Head title="Admin - Manajemen Video" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-8 bg-indigo-600 rounded-full"></div>
                    <h2 class="text-2xl sm:text-3xl font-black text-[rgb(var(--text-main))] italic uppercase tracking-tight">Brankas <span class="text-indigo-600">Video</span></h2>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button 
                        @click="downloadSyncManifest"
                        class="px-3 py-2 bg-indigo-500/5 hover:bg-indigo-600/10 text-indigo-600 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-indigo-500/20 transition-all flex items-center gap-1.5 group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 group-hover:translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Ekspor Manifes
                    </button>
                    <Link 
                        :href="route('admin.videos.bulk-upload')"
                        class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-1.5 group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Bulk Pro
                    </Link>
                    <button 
                        @click="runHealthCheck"
                        class="px-3 py-2 bg-emerald-500/5 hover:bg-emerald-600/10 text-emerald-600 text-[10px] font-bold uppercase tracking-widest rounded-xl border border-emerald-500/20 transition-all flex items-center gap-1.5 group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 group-hover:rotate-180 transition-transform duration-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04c0 4.833 1.807 9.242 4.798 12.584a11.11 11.11 0 0011.64 0c2.991-3.342 4.798-7.751 4.798-12.584z" />
                        </svg>
                        Audit Kesehatan
                    </button>
                    <div class="bg-indigo-500/10 px-3 py-1.5 rounded-full border border-indigo-500/20">
                        <span class="text-[10px] font-bold uppercase text-indigo-600 tracking-widest">{{ videos.total }} Terdaftar</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Add Video & Bulk Upload Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Manual Upload -->
                    <div class="lg:col-span-2 glass shadow-royale p-5 rounded-2xl border border-[rgb(var(--border-main))]">
                        <div class="flex items-center gap-4 mb-5 overflow-x-auto no-scrollbar">
                            <h3 class="text-base font-black text-[rgb(var(--text-main))] flex items-center gap-3 uppercase tracking-tight shrink-0">
                                <span class="w-1 h-5 bg-indigo-600 rounded-full"></span>
                                Masukan
                            </h3>
                            <div class="flex bg-[rgb(var(--bg-input))] p-1 rounded-xl border border-[rgb(var(--border-main))] shadow-inner shrink-0">
                                <button 
                                    @click="uploadType = 'url'"
                                    :class="uploadType === 'url' ? 'bg-indigo-600 text-white shadow' : 'text-[rgb(var(--text-muted))] hover:text-indigo-500'"
                                    class="px-4 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all"
                                >
                                    URL Jarak Jauh
                                </button>
                                <button 
                                    @click="uploadType = 'file'"
                                    :class="uploadType === 'file' ? 'bg-indigo-600 text-white shadow' : 'text-[rgb(var(--text-muted))] hover:text-indigo-500'"
                                    class="px-4 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all"
                                >
                                    File Langsung
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="title" value="Identitas Video" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                                        <button 
                                            type="button"
                                            @click="aiSuggest(form)"
                                            :disabled="aiLoading"
                                            class="text-[9px] font-bold text-indigo-400 uppercase hover:text-indigo-300 transition-colors flex items-center gap-1"
                                        >
                                            <span v-if="aiLoading" class="animate-spin">🌀</span>
                                            <span v-else>✨</span>
                                            AI Suggest
                                        </button>
                                    </div>
                                    <TextInput id="title" type="text" class="mt-1 block w-full !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-sm !shadow-inner focus:ring-2 focus:ring-indigo-500/20" v-model="form.title" required placeholder="Enter title..." />
                                    <InputError class="mt-1 text-xs" :message="form.errors.title" />
                                </div>
                                <div v-if="uploadType === 'url'" class="space-y-1.5">
                                    <InputLabel for="url" value="Tautan Sumber" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                                    <TextInput id="url" type="text" class="mt-1 block w-full !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-sm !shadow-inner focus:ring-2 focus:ring-indigo-500/20" v-model="form.url" required placeholder="Paste URL..." />
                                    <InputError class="mt-1 text-xs" :message="form.errors.url" />
                                </div>
                                <div v-else class="space-y-1.5">
                                    <InputLabel for="video_file" value="File Video" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                                    <div class="bg-[rgb(var(--bg-input))] rounded-xl p-2 shadow-inner">
                                        <input 
                                            id="video_file" 
                                            type="file" 
                                            accept="video/*"
                                            @input="form.video_file = $event.target.files[0]"
                                            class="block w-full text-[10px] text-[rgb(var(--text-muted))] file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:bg-indigo-600/10 file:text-indigo-600 hover:file:bg-indigo-600/20 transition-all cursor-pointer"
                                            :required="uploadType === 'file'"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-xs" :message="form.errors.video_file" />
                                </div>
                                <div class="space-y-1.5">
                                    <InputLabel for="category_id" value="Kategori" class="!text-[rgb(var(--text-muted))] !text-[10px] !font-bold !uppercase !tracking-widest" />
                                    <select 
                                        id="category_id" 
                                        v-model="form.category_id" 
                                        class="mt-1 block w-full !bg-[rgb(var(--bg-input))] !border-none !rounded-xl !py-2.5 !px-4 !text-sm !shadow-inner focus:ring-2 focus:ring-indigo-500/20 text-[rgb(var(--text-main))] appearance-none cursor-pointer"
                                    >
                                        <option value="">Tanpa Kategori</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-1 text-xs" :message="form.errors.category_id" />
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="flex items-center group cursor-pointer">
                                        <Checkbox name="is_premium" v-model:checked="form.is_premium" class="!bg-[rgb(var(--bg-input))] !border-none !text-indigo-600 rounded-md w-4 h-4 shadow-inner" />
                                        <span class="ms-2 text-xs font-bold uppercase tracking-widest text-[rgb(var(--text-muted))] group-hover:text-indigo-600 transition">Premium</span>
                                    </label>
                                    
                                    <label v-if="uploadType === 'url'" class="flex items-center group cursor-pointer">
                                        <Checkbox name="skip_download" v-model:checked="form.skip_download" class="!bg-[rgb(var(--bg-input))] !border-none !text-indigo-600 rounded-md w-4 h-4 shadow-inner" />
                                        <span class="ms-2 text-xs font-bold uppercase tracking-widest text-[rgb(var(--text-muted))] group-hover:text-amber-600 transition">Skip Lock</span>
                                    </label>
                                </div>
                                <PrimaryButton class="btn-premium !px-6 !py-2.5 shadow-lg shadow-indigo-500/20" :disabled="form.processing">
                                    {{ form.processing ? 'Sedang Diproses...' : 'Tambahkan Video' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Bulk Upload -->
                    <div class="glass shadow-royale p-5 rounded-2xl border border-[rgb(var(--border-main))] bg-gradient-to-br from-indigo-500/5 to-transparent relative group overflow-hidden">
                         <div class="absolute inset-0 bg-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                         <h3 class="text-base font-black text-[rgb(var(--text-main))] mb-4 flex items-center gap-3 uppercase tracking-tight relative z-10">
                            <span class="w-1 h-5 bg-violet-600 rounded-full"></span>
                            Logistik Massal
                        </h3>
                        <form @submit.prevent="submitBulk" class="space-y-4 relative z-10">
                            <div class="relative group/file">
                                <input id="file" type="file" @input="bulkForm.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                                <div class="border-2 border-dashed border-[rgb(var(--border-main))] rounded-xl p-6 text-center group-hover/file:border-indigo-500/30 transition-all bg-[rgb(var(--bg-input))] shadow-inner">
                                    <div class="text-2xl mb-2 group-hover/file:scale-110 transition-transform duration-300">📜</div>
                                    <div class="text-[10px] font-bold text-[rgb(var(--text-muted))] uppercase tracking-widest">
                                        {{ bulkForm.file ? bulkForm.file.name : 'Upload JSON / CSV' }}
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="bulkForm.errors.file" />
                            </div>
                            <button class="w-full bg-[rgb(var(--text-main))] text-[rgb(var(--bg-main))] font-bold py-2.5 rounded-xl hover:brightness-110 transition uppercase tracking-widest text-[10px] shadow-lg" :disabled="bulkForm.processing">
                                {{ bulkForm.processing ? 'Memproses...' : 'Kirim Logistik' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                     <div class="glass shadow-royale p-4 rounded-2xl border border-[rgb(var(--border-main))] bg-gradient-to-br from-indigo-500/5 to-transparent">
                         <div class="text-[10px] font-bold text-[rgb(var(--text-muted))] uppercase tracking-widest mb-1">Perpustakaan Asal</div>
                         <div class="flex items-baseline gap-2">
                             <div class="text-3xl font-black text-[rgb(var(--text-main))] italic tracking-tight">{{ total_local || 0 }}</div>
                             <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Vault Items</div>
                         </div>
                     </div>
                     <div class="glass shadow-royale p-4 rounded-2xl border border-[rgb(var(--border-main))] bg-gradient-to-br from-emerald-500/5 to-transparent">
                         <div class="text-[10px] font-bold text-[rgb(var(--text-muted))] uppercase tracking-widest mb-1">Efisiensi Sinkronisasi</div>
                         <div class="flex items-baseline justify-between mb-2">
                             <div class="text-3xl font-black text-[rgb(var(--text-main))] italic tracking-tight">{{ total_mirrored || 0 }}</div>
                             <div class="text-xs font-bold text-emerald-600 uppercase tracking-widest">{{ Math.round((total_mirrored / total_local) * 100) || 0 }}%</div>
                         </div>
                         <div class="w-full h-1.5 bg-[rgb(var(--bg-input))] rounded-full overflow-hidden">
                             <div class="h-full bg-emerald-500 transition-all duration-1000 rounded-full" :style="{ width: ((total_mirrored / total_local) * 100 || 0) + '%' }"></div>
                         </div>
                     </div>
                     <div class="glass shadow-royale p-4 rounded-2xl border border-[rgb(var(--border-main))] bg-gradient-to-br from-amber-500/5 to-transparent">
                         <div class="text-[10px] font-bold text-[rgb(var(--text-muted))] uppercase tracking-widest mb-1">Antrean Sinkronisasi</div>
                         <div class="flex items-baseline gap-2">
                             <div class="text-3xl font-black text-[rgb(var(--text-main))] italic tracking-tight">{{ total_pending || 0 }}</div>
                             <div class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Menunggu</div>
                         </div>
                     </div>
                </div>

                <!-- Mirroring Live Monitor (Informative Dashboard) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    <!-- Automated Pipeline Dashboard -->
                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 space-y-8 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-emerald-500/5 opacity-50"></div>
                        
                        <div class="flex items-center justify-between border-b border-white/5 pb-4 relative z-10">
                            <h3 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                Saluran Distribusi
                            </h3>
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest italic">Multi-Host Mirroring Pipeline</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 relative z-10">
                            <!-- Stage 1: Videy CDN -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1">
                                <div :class="{'animate-pulse bg-indigo-500/20 border-indigo-500/40': activeDownloads > 0}" class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-xl shadow-inner shadow-white/5 transition-all duration-500">
                                    ☁️
                                </div>
                                <div class="text-[10px] font-black text-white uppercase tracking-widest">Videy CDN</div>
                                <div class="flex flex-col gap-0.5">
                                    <div class="text-[9px] font-bold text-slate-500 uppercase">{{ total_download_pending }} Pending</div>
                                    <div v-if="activeDownloads > 0" class="text-[8px] font-black text-indigo-400 uppercase animate-pulse">{{ activeDownloads }} Downloading ({{ averageDownloadProgress }}%)</div>
                                </div>
                            </div>

                            <div class="hidden sm:block text-slate-700 animate-pulse">→</div>

                            <!-- Stage 2: Local Storage -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xl shadow-lg shadow-indigo-500/10">💾</div>
                                <div class="text-[10px] font-black text-white uppercase tracking-widest">Penyimpanan Lokal</div>
                                <div class="text-[9px] font-bold text-indigo-400 uppercase">{{ localReady }} Siap</div>
                            </div>

                            <div class="hidden sm:block text-slate-700 animate-pulse">→</div>

                            <!-- Stage 3: Mirrors -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1 relative group/mirrors">
                                <div :class="{'animate-pulse bg-emerald-500/20 border-emerald-500/40': activeMirrors > 0}" class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-xl shadow-lg shadow-emerald-500/10 transition-all duration-500">
                                    📼
                                </div>
                                <div class="text-[10px] font-black text-white uppercase tracking-widest">Mirror Aktif</div>
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[8px] font-bold text-emerald-400 uppercase">TAP: {{ hostStats.streamtape }}</span>
                                            <span class="text-[8px] font-bold text-blue-400 uppercase">DDS: {{ hostStats.doodstream }}</span>
                                        </div>
                                    </div>
                                    <div v-if="activeMirrors > 0" class="text-[8px] font-black text-yellow-400 uppercase animate-pulse">{{ activeMirrors }} Syncing ({{ averageUploadProgress }}%)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Progress Bar -->
                        <div class="space-y-2 relative z-10">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <span>Total Keberhasilan Distribusi</span>
                                <span class="text-white">{{ Math.round((mirroredTotal / (localReady + downloadPendingTotal || 1)) * 100) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                <div 
                                    class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(79,70,229,0.4)]" 
                                    :style="{ width: ((mirroredTotal / (localReady + downloadPendingTotal || 1)) * 100 || 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 space-y-6">
                        <div class="flex items-center justify-between border-b border-white/5 pb-4">
                            <h3 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                Aktivitas Sinkronisasi Terkini
                            </h3>
                        </div>
                        <div class="space-y-3">
                            <div v-for="video in recentActivityList" :key="video.id" 
                                 class="flex items-center gap-4 p-3 bg-white/2 rounded-2xl border border-white/5 hover:bg-white/5 transition cursor-pointer"
                                 @click="openPreview(video)"
                            >
                                <div class="w-12 aspect-video rounded-lg bg-slate-800 flex-shrink-0 overflow-hidden">
                                     <img v-if="video.thumbnail_url" :src="video.thumbnail_url" class="w-full h-full object-cover" />
                                     <div v-else class="w-full h-full flex items-center justify-center text-[10px]">🎬</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10px] font-black text-white truncate uppercase tracking-tighter">{{ video.title }}</div>
                                    <div class="flex gap-1.5 mt-1">
                                        <div v-for="(status, host) in video.hosting_status" :key="host">
                                            <div v-if="status === 'success'" class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.5)]" :title="host"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[8px] font-black text-slate-600 uppercase">{{ new Date(video.updated_at).toLocaleTimeString() }}</div>
                            </div>
                            <div v-if="recent_activity.length === 0" class="text-center py-6 text-[10px] font-black text-slate-600 uppercase tracking-widest">
                                Menunggu sinkronisasi...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video List -->
                <div class="glass-dark rounded-2xl border border-white/5 overflow-hidden shadow-xl">
                    <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between flex-wrap gap-3">
                         <h3 class="font-black text-white uppercase tracking-widest text-sm flex items-center gap-3">
                             Perpustakaan Aktif
                             
                             <!-- Proxy Toggle -->
                             <div class="flex items-center bg-white/5 px-3 py-1.5 rounded-xl border border-white/10 gap-2">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Proksi</span>
                                <button 
                                    @click="proxyActive = !proxyActive; toggleProxy()"
                                    :class="proxyActive ? 'bg-indigo-600' : 'bg-slate-700'"
                                    class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span 
                                        :class="proxyActive ? 'translate-x-4' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    ></span>
                                </button>
                                <span :class="proxyActive ? 'text-indigo-400' : 'text-slate-500'" class="text-[10px] font-bold uppercase">
                                    {{ proxyActive ? 'ON' : 'OFF' }}
                                </span>
                             </div>
                         </h3>
                         
                         <!-- Bulk Actions Toolbar -->
                         <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center gap-2 animate-in fade-in slide-in-from-right-4">
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">{{ selectedIds.length }} Terpilih</span>
                            <div class="flex items-center gap-1.5">
                                <button 
                                    @click="exportSocialLinks"
                                    class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-500 text-[10px] font-bold border border-emerald-500/20 rounded-lg transition uppercase tracking-widest flex items-center gap-1.5"
                                >
                                    📥 <span class="hidden sm:inline">Ekspor</span>
                                </button>
                                <button 
                                    @click="deleteSelected"
                                    class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-[10px] font-bold border border-red-500/20 rounded-lg transition uppercase tracking-widest flex items-center gap-1.5"
                                >
                                    🗑️ <span class="hidden sm:inline">Hapus</span>
                                </button>
                            </div>
                         </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase text-slate-500 tracking-widest border-b border-white/5 bg-white/5">
                                    <th class="py-3 px-4 w-10">
                                        <Checkbox :checked="isAllSelected" @change="toggleSelectAll" class="!bg-white/5 !border-white/10 !text-indigo-600 rounded-lg" />
                                    </th>
                                    <th class="py-3 px-4">Detail Media</th>
                                    <th class="py-3 px-4 hidden md:table-cell">Kesehatan</th>
                                    <th class="py-3 px-4 hidden lg:table-cell">Kategori</th>
                                    <th class="py-3 px-4 hidden sm:table-cell">Tingkatan</th>
                                    <th class="py-3 px-4">Sinkronisasi</th>
                                    <th class="py-3 px-4 hidden lg:table-cell">Penyimpanan</th>
                                    <th class="py-3 px-4 hidden md:table-cell text-center">Statistik</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="video in videos.data" :key="video.id" class="group hover:bg-white/5 transition duration-200" :class="{'bg-indigo-500/5': selectedIds.includes(video.id)}">
                                    <td class="py-3 px-4">
                                        <Checkbox v-model:checked="selectedIds" :value="video.id" class="!bg-white/5 !border-white/10 !text-indigo-600 rounded-lg" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div :ref="el => observeVideo(el, video.id)" class="w-20 aspect-video flex-shrink-0 rounded-xl bg-slate-900 border flex items-center justify-center overflow-hidden relative transition cursor-pointer group/thumb"
                                                 @click="openPreview(video)"
                                                 :class="video.download_status === 'completed' ? 'border-green-500/50 shadow-[0_0_15px_rgba(34,197,94,0.2)]' : 'border-white/10 group-hover:border-indigo-500/30'">
                                                <img 
                                                    v-if="video.thumbnail_url" 
                                                    :src="video.thumbnail_url" 
                                                    class="w-full h-full object-cover group-hover/thumb:scale-105 transition-transform duration-500" 
                                                    loading="lazy" 
                                                />
                                                <div v-else class="w-full h-full bg-slate-800 flex items-center justify-center">
                                                    <span class="text-sm opacity-50">🎬</span>
                                                </div>
                                                <div class="absolute inset-0 bg-indigo-600/20 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center">
                                                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                     </svg>
                                                </div>
                                                <div v-if="video.download_status === 'completed'" class="absolute top-1 left-1 text-[8px] bg-green-500 text-green-950 px-1.5 py-0.5 rounded font-black z-10 uppercase">LOC</div>
                                            </div>
                                            <div class="cursor-pointer min-w-0">
                                                 <div class="text-xs font-bold text-white group-hover:text-indigo-400 transition tracking-tight leading-tight truncate max-w-[200px]" @click="openPreview(video)">{{ video.title }}</div>
                                                 <div class="text-[10px] text-slate-500 font-mono truncate opacity-60 max-w-[180px] mt-0.5">{{ video.url }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden md:table-cell">
                                        <div v-if="video.health_report" class="flex flex-col gap-1.5">
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.local?.status === 'healthy' ? 'bg-emerald-500' : (video.health_report.local?.status === 'na' ? 'bg-slate-700' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">LOC</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.videy?.status === 'healthy' ? 'bg-emerald-500' : (video.health_report.videy?.status === 'na' ? 'bg-slate-700' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">VDY</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.streamtape?.status === 'healthy' ? 'bg-emerald-500' : (video.health_report.streamtape?.status === 'na' ? 'bg-slate-700' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">TAP</span>
                                             </div>
                                        </div>
                                        <div v-else class="text-[10px] font-bold text-slate-700 uppercase italic">-</div>
                                    </td>
                                    <td class="py-3 px-4 hidden lg:table-cell">
                                         <span v-if="video.category" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-500/5 border border-indigo-500/10 rounded-full text-[10px] font-bold text-white uppercase tracking-tight">
                                             <span class="text-sm">{{ video.category.icon || '📁' }}</span>
                                             {{ video.category.name }}
                                         </span>
                                         <span v-else class="text-[10px] font-bold text-slate-700 uppercase italic">-</span>
                                     </td>
                                    <td class="py-3 px-4 hidden sm:table-cell">
                                         <span v-if="video.is_free_to_all" class="bg-green-500/10 text-green-400 text-[10px] px-2.5 py-1 rounded-full border border-green-500/20 font-bold uppercase tracking-wider">Free</span>
                                         <span v-else-if="video.is_premium" class="bg-indigo-500/10 text-indigo-400 text-[10px] px-2.5 py-1 rounded-full border border-indigo-500/20 font-bold uppercase tracking-wider">Premium</span>
                                         <span v-else class="bg-slate-800 text-slate-400 text-[10px] px-2.5 py-1 rounded-full border border-white/5 font-bold uppercase tracking-wider">Standard</span>
                                     </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                             <div class="group/host relative">
                                                 <div 
                                                     :class="getMirrorStatusClass(video.hosting_status?.['streamtape'])"
                                                     class="w-3.5 h-3.5 rounded-full border border-white/20 transition-all cursor-help"
                                                     @click="distributeVideo(video.id, 'streamtape')"
                                                 ></div>
                                                 <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 bg-black text-[10px] font-bold uppercase text-white rounded-lg opacity-0 group-hover/host:opacity-100 transition whitespace-nowrap z-20 pointer-events-none shadow-lg">
                                                     Tape: {{ getMirrorStatusLabel(video.hosting_status?.['streamtape']) }}
                                                 </div>
                                             </div>
                                             <div>
                                                 <span v-if="video.hosting_status?.['streamtape'] === 'success'" class="text-[10px] font-bold text-emerald-400 uppercase">OK</span>
                                                 <span v-else-if="video.hosting_status?.['streamtape'] === 'uploading'" class="text-[10px] font-bold text-blue-400 uppercase animate-pulse">Sync...</span>
                                                 <span v-else-if="video.hosting_status?.['streamtape']?.startsWith('failed')" class="text-[10px] font-bold text-red-400 uppercase">Gagal</span>
                                                 <span v-else class="text-[10px] font-bold text-slate-600 uppercase">-</span>
                                             </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden lg:table-cell">
                                         <div class="flex flex-col gap-1">
                                             <span v-if="video.download_status === 'completed'" class="text-[10px] font-bold uppercase text-emerald-400 bg-green-500/10 px-2.5 py-1 rounded-full border border-green-500/20 w-fit flex items-center gap-1">
                                                 <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div> Local
                                             </span>
                                             <div v-else-if="video.download_status === 'downloading'" class="flex flex-col gap-1 w-full max-w-[120px]">
                                                 <span class="text-[10px] font-bold uppercase text-blue-400 flex items-center justify-between">
                                                     <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span> DL</span>
                                                     <span>{{ downloadProgress[video.id] || 0 }}%</span>
                                                 </span>
                                                 <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                                                     <div class="h-full bg-blue-500 transition-all duration-300 rounded-full" :style="{ width: (downloadProgress[video.id] || 0) + '%' }"></div>
                                                 </div>
                                             </div>
                                             <span v-else-if="video.download_status === 'failed'" class="text-[10px] font-bold uppercase text-red-400 bg-red-500/10 px-2.5 py-1 rounded-full border border-red-500/20 w-fit">Gagal</span>
                                             <span v-else class="text-[10px] font-bold uppercase text-slate-500 w-fit">Cloud</span>
                                         </div>
                                     </td>
                                     <td class="py-3 px-4 hidden md:table-cell text-center">
                                         <div class="inline-flex flex-col items-center px-3 py-1.5 bg-white/5 rounded-xl border border-white/10">
                                             <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Views</span>
                                             <span class="text-white font-black text-sm tracking-tight">{{ video.views.toLocaleString() }}</span>
                                         </div>
                                     </td>
                                     <td class="py-3 px-4 text-right">
                                         <div class="flex justify-end gap-1.5">
                                             <button @click="openEditModal(video)" class="p-2 text-indigo-400 hover:bg-indigo-500/20 hover:text-white rounded-lg transition-all bg-white/5 border border-white/5" title="Edit Video">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.242 19.172l3.414-3.414a2 2 0 000-2.828l-3.414-3.414a2 2 0 00-2.828 0L10 12.586l.707.707L13.414 10l2.828 2.828L13.414 16.242l.707.707 2.121-2.121z" />
                                                 </svg>
                                             </button>
                                             <button @click="copyMaskedLink(video)" class="p-2 text-emerald-400 hover:bg-emerald-500/20 hover:text-white rounded-lg transition-all bg-white/5 border border-white/5" title="Copy Link">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.142a2 2 0 012.828 2.828l-3.536 3.536a2 2 0 01-2.828 0M9.172 14.858a2 2 0 01-2.828-2.828l3.536-3.536a2 2 0 012.828 0M11 11l.01.01" />
                                                 </svg>
                                             </button>
                                             <button @click="openPreview(video)" class="p-2 text-indigo-400 hover:bg-indigo-500/20 hover:text-white rounded-lg transition-all bg-white/5 border border-white/5" title="Preview">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                 </svg>
                                             </button>
                                             <button @click="deleteVideo(video.id)" class="p-2 text-red-500 hover:bg-red-500/20 hover:text-white rounded-lg transition-all bg-white/5 border border-white/5" title="Hapus">
                                                 <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                 </svg>
                                             </button>
                                         </div>
                                     </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-5 py-3 border-t border-white/5 bg-slate-900/80 flex justify-center">
                         <div class="flex gap-1.5 flex-wrap justify-center">
                            <Link v-for="link in videos.links" :key="link.label" 
                                :href="link.url || '#'"
                                :class="[
                                    'px-6 py-3 rounded-2xl text-sm font-black transition-all uppercase tracking-[0.2em] shadow-inner',
                                    link.active ? 'bg-indigo-600 text-white shadow-[0_0_20px_rgba(79,70,229,0.4)]' : 'text-slate-500 hover:text-white hover:bg-white/10',
                                    !link.url ? 'opacity-20 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <Teleport to="body">
            <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/95 backdrop-blur-md animate-in fade-in duration-300">
                <div class="relative w-full max-w-4xl glass-dark rounded-[2.5rem] border border-white/10 overflow-hidden shadow-2xl animate-in zoom-in-95 duration-300">
                    <!-- Header -->
                    <div class="p-6 border-b border-white/5 flex items-center justify-between bg-white/5">
                        <div class="flex items-center gap-4">
                            <h3 class="text-lg font-black text-white italic truncate max-w-md uppercase tracking-tighter">{{ selectedVideo?.title }}</h3>
                            <span :class="isLocal(selectedVideo) ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.2)]' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'" class="text-[10px] px-4 py-1.5 rounded-full border font-black uppercase tracking-widest">
                                Sumber: {{ isLocal(selectedVideo) ? 'PENYIMPANAN LOKAL' : 'PROKSI CDN' }}
                            </span>
                        </div>
                        <button @click="showPreviewModal = false" class="p-3 hover:bg-white/10 rounded-full text-slate-400 hover:text-white transition-all active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <!-- Player Area -->
                    <div class="aspect-video bg-black/50 flex items-center justify-center relative group/video">
                        <video 
                            v-if="showPreviewModal"
                            :src="getPreviewUrl(selectedVideo)" 
                            controls 
                            autoplay
                            class="w-full h-full"
                        ></video>
                    </div>

                    <!-- Debug Info Area -->
                    <div class="p-8 bg-black/40 border-t border-white/5 space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest min-w-[100px] mt-2">Aliran Aktif:</span>
                            <div class="flex-1 bg-white/5 p-3 rounded-2xl border border-white/5 font-mono text-[10px] text-indigo-300 break-all select-all leading-relaxed">
                                {{ getPreviewUrl(selectedVideo) }}
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest min-w-[100px] mt-1">URL Asal:</span>
                            <div class="flex-1 text-[10px] text-slate-500 truncate italic">
                                {{ selectedVideo?.url }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Mirror Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showMirrorConfirmModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in">
                <div class="glass-dark w-full max-w-lg rounded-[2.5rem] border border-white/10 p-10 space-y-8 animate-in zoom-in-95">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-indigo-500/10 rounded-3xl flex items-center justify-center border border-indigo-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white uppercase tracking-tighter italic">Konfirmasi Sinkronisasi</h3>
                            <p class="text-xs text-slate-400 font-medium">Mulai sinkronisasi latar belakang untuk semua video lokal?</p>
                        </div>
                    </div>

                    <!-- Streamtape only mode -->
                    <div class="flex items-center gap-4 p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10">
                        <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        </div>
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-widest">Streamtape</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">All videos will be mirrored to Streamtape</div>
                        </div>
                        <div class="ml-auto w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button @click="showMirrorConfirmModal = false" class="flex-1 py-4 bg-white/5 hover:bg-white/10 rounded-2xl text-[10px] font-black uppercase text-slate-400 border border-white/10 transition">Batal</button>
                        <button @click="confirmMirrorBulk" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-500 rounded-2xl text-[10px] font-black uppercase text-white shadow-xl shadow-indigo-600/20 transition">Mulai Sinkronisasi</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Video Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md animate-in fade-in">
                <div class="glass-dark w-full max-w-lg rounded-[3rem] border border-white/10 p-10 space-y-8 animate-in zoom-in-95 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-white/5 pb-6">
                        <h3 class="text-xl font-black text-white uppercase tracking-tighter italic flex items-center gap-4">
                            <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                            Sunting Video
                        </h3>
                        <button @click="showEditModal = false" class="text-slate-500 hover:text-white transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="updateVideo" class="space-y-6">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between px-2">
                                <InputLabel for="edit_title" value="Judul Video" class="!text-slate-500 !text-[9px] !font-black !uppercase !tracking-widest" />
                                <button 
                                    type="button"
                                    @click="aiSuggest(editForm)"
                                    :disabled="aiLoading"
                                    class="text-[9px] font-bold text-indigo-400 uppercase hover:text-indigo-300 transition-colors flex items-center gap-1"
                                >
                                    <span v-if="aiLoading" class="animate-spin">🌀</span>
                                    <span v-else>✨</span>
                                    AI Suggest
                                </button>
                            </div>
                            <TextInput id="edit_title" type="text" v-model="editForm.title" class="w-full !bg-white/5 !border-none !rounded-2xl !py-4 !px-6 !text-xs !text-white !shadow-inner focus:ring-2 focus:ring-indigo-500/20" required />
                            <InputError :message="editForm.errors.title" />
                        </div>

                        <div class="space-y-2">
                            <InputLabel for="edit_category" value="Kategori" class="!text-slate-500 !text-[9px] !font-black !uppercase !tracking-widest ml-2" />
                            <select 
                                id="edit_category" 
                                v-model="editForm.category_id" 
                                class="w-full !bg-white/5 !border-none !rounded-2xl !py-4 !px-6 !text-xs !text-white !shadow-inner focus:ring-2 focus:ring-indigo-500/20 appearance-none cursor-pointer"
                            >
                                <option value="" class="bg-slate-900">Tanpa Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id" class="bg-slate-900">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.category_id" />
                        </div>

                        <div class="flex items-center gap-8 py-2">
                            <label class="flex items-center group cursor-pointer">
                                <Checkbox name="is_premium" v-model:checked="editForm.is_premium" class="!bg-white/5 !border-none !text-indigo-600 rounded-lg w-6 h-6 shadow-inner" />
                                <span class="ms-3 text-xs font-black uppercase tracking-widest text-slate-500 group-hover:text-indigo-400 transition">Premium</span>
                            </label>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" @click="showEditModal = false" class="flex-1 py-4 bg-white/5 hover:bg-white/10 rounded-2xl text-[10px] font-black uppercase text-slate-400 border border-white/10 transition">Batal</button>
                            <button type="submit" :disabled="editForm.processing" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-500 rounded-2xl text-[10px] font-black uppercase text-white shadow-xl shadow-indigo-600/20 transition uppercase tracking-widest">
                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.animate-pulse-subtle {
    animation: pulse-subtle 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(0.98); }
}

.btn-premium {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    color: white;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-radius: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
}

.btn-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
    filter: brightness(1.1);
}
</style>
