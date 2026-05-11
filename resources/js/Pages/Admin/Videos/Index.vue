<script setup>
import { useForm, router, usePage, Head } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
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

const retryFailedDownloads = () => {
    if (confirm('Ulangi semua unduhan yang gagal?')) {
        router.post(route('admin.videos.retry-failed'), {}, {
            preserveScroll: true,
            onSuccess: () => toastSuccess('Antrean unduhan ulang dimulai.')
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
    if (status === 'uploading' || status === 'remote_processing') return 'bg-blue-500 animate-pulse shadow-[0_0_8px_rgba(59,130,246,0.4)]';
    if (status === 'skipped') return 'bg-amber-500/50 border-amber-500/30';
    return 'bg-slate-700';
};

const getMirrorStatusLabel = (status) => {
    if (!status) return 'Pending';
    if (status === 'remote_processing') return 'Remote Processing';
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
    if (pollInterval) clearTimeout(pollInterval);
});

const observeVideo = (el, id) => {
    if (el && observer) {
        el.dataset.id = id;
        observer.observe(el);
    }
};

let pollInterval = null;

const startPollingProgress = () => {
    if (pollInterval) clearTimeout(pollInterval);
    
    const poll = async () => {
        const activeIds = props.videos.data
            .filter(v => 
                v.download_status === 'downloading' || 
                (v.hosting_status && Object.values(v.hosting_status).some(s => s === 'uploading' || s === 'pending'))
            )
            .map(v => v.id);
            
        if (activeIds.length === 0 && !props.total_active_downloads && !props.total_active_mirrors) {
            pollInterval = setTimeout(poll, 30000); // Check less frequently if nothing active
            return;
        }

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
        } finally {
            // Schedule next poll only after current is finished
            pollInterval = setTimeout(poll, 10000);
        }
    };

    poll();
};

onMounted(() => startPollingProgress());
onUnmounted(() => { if (pollInterval) clearInterval(pollInterval); });
</script>

<template>
    <Head title="Admin - Manajemen Video" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Manajemen Video</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola, sinkronkan, dan pantau seluruh konten media Anda.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button 
                        @click="downloadSyncManifest"
                        class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-600 text-xs font-bold uppercase tracking-widest rounded-lg border border-gray-200 transition-all flex items-center gap-2 shadow-sm"
                    >
                        Ekspor Manifes
                    </button>
                    <Link 
                        :href="route('admin.videos.bulk-upload')"
                        class="px-4 py-2 bg-[#8C57FF] hover:bg-[#7B47E6] text-white text-xs font-bold uppercase tracking-widest rounded-lg shadow-md shadow-[#8C57FF]/20 transition-all flex items-center gap-2"
                    >
                        Bulk Pro
                    </Link>
                    <button 
                        @click="runHealthCheck"
                        class="px-4 py-2 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-bold uppercase tracking-widest rounded-lg border border-green-200 transition-all flex items-center gap-2"
                    >
                        Audit Kesehatan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Add Video & Bulk Upload Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Manual Upload -->
                    <div class="lg:col-span-2 materio-card p-6">
                        <div class="flex items-center gap-4 mb-6 overflow-x-auto no-scrollbar pb-1">
                            <h3 class="text-lg font-bold text-[#3A3541] flex items-center gap-3 tracking-tight shrink-0">
                                <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                                Tambah Video
                            </h3>
                            <div class="flex bg-gray-50 p-1 rounded-xl border border-gray-100 shrink-0">
                                <button 
                                    @click="uploadType = 'url'"
                                    :class="uploadType === 'url' ? 'bg-[#8C57FF] text-white shadow-sm' : 'text-gray-500 hover:text-[#8C57FF]'"
                                    class="px-4 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider transition-all"
                                >
                                    URL Jarak Jauh
                                </button>
                                <button 
                                    @click="uploadType = 'file'"
                                    :class="uploadType === 'file' ? 'bg-[#8C57FF] text-white shadow-sm' : 'text-gray-500 hover:text-[#8C57FF]'"
                                    class="px-4 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider transition-all"
                                >
                                    File Langsung
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="title" value="Judul Video" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                                        <button 
                                            type="button"
                                            @click="aiSuggest(form)"
                                            :disabled="aiLoading"
                                            class="text-[10px] font-bold text-[#8C57FF] uppercase hover:underline transition-colors flex items-center gap-1"
                                        >
                                            <span v-if="aiLoading" class="animate-spin">🌀</span>
                                            <span v-else>✨</span>
                                            AI Suggest
                                        </button>
                                    </div>
                                    <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" required placeholder="Contoh: Video Dokumenter Alam" />
                                    <InputError class="mt-1 text-xs" :message="form.errors.title" />
                                </div>
                                <div v-if="uploadType === 'url'" class="space-y-1.5">
                                    <InputLabel for="url" value="Tautan Videy.co" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                                    <TextInput id="url" type="text" class="mt-1 block w-full" v-model="form.url" required placeholder="https://videy.co/v?id=..." />
                                    <InputError class="mt-1 text-xs" :message="form.errors.url" />
                                </div>
                                <div v-else class="space-y-1.5">
                                    <InputLabel for="video_file" value="File Video" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                                    <div class="bg-gray-50 rounded-lg p-2 border border-gray-100">
                                        <input 
                                            id="video_file" 
                                            type="file" 
                                            accept="video/*"
                                            @input="form.video_file = $event.target.files[0]"
                                            class="block w-full text-[11px] text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:uppercase file:bg-[#8C57FF]/10 file:text-[#8C57FF] hover:file:bg-[#8C57FF]/20 transition-all cursor-pointer"
                                            :required="uploadType === 'file'"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-xs" :message="form.errors.video_file" />
                                </div>
                                <div class="space-y-1.5">
                                    <InputLabel for="category_id" value="Kategori" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                                    <select 
                                        id="category_id" 
                                        v-model="form.category_id" 
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-xl py-2.5 px-4 text-sm focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] text-[#3A3541] appearance-none cursor-pointer transition-all"
                                    >
                                        <option value="">Tanpa Kategori</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-1 text-xs" :message="form.errors.category_id" />
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pt-4 border-t border-gray-50">
                                <div class="flex flex-wrap items-center gap-6">
                                    <label class="flex items-center group cursor-pointer">
                                        <Checkbox name="is_premium" v-model:checked="form.is_premium" />
                                        <span class="ms-2 text-[11px] font-bold uppercase tracking-wider text-gray-500 group-hover:text-[#8C57FF] transition">Konten Premium</span>
                                    </label>
                                    
                                    <label v-if="uploadType === 'url'" class="flex items-center group cursor-pointer">
                                        <Checkbox name="skip_download" v-model:checked="form.skip_download" />
                                        <span class="ms-2 text-[11px] font-bold uppercase tracking-wider text-gray-500 group-hover:text-amber-600 transition">Lewati Unduhan</span>
                                    </label>
                                </div>
                                <button class="px-8 py-2.5 bg-[#8C57FF] text-white font-bold rounded-lg hover:bg-[#7B47E6] transition-all shadow-md shadow-[#8C57FF]/20 uppercase text-xs tracking-widest disabled:opacity-50" :disabled="form.processing">
                                    {{ form.processing ? 'Sedang Diproses...' : 'Tambahkan Ke Vault' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bulk Upload -->
                    <div class="materio-card p-6 bg-gradient-to-br from-[#8C57FF]/5 to-transparent relative group">
                         <h3 class="text-lg font-bold text-[#3A3541] mb-6 flex items-center gap-3 tracking-tight relative z-10">
                            <span class="w-1 h-6 bg-[#A478FF] rounded-full"></span>
                            Logistik Massal
                        </h3>
                        <form @submit.prevent="submitBulk" class="space-y-4 relative z-10">
                            <div class="relative group/file">
                                <input id="file" type="file" @input="bulkForm.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                                <div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center group-hover/file:border-[#8C57FF]/30 transition-all bg-gray-50">
                                    <div class="text-3xl mb-3 group-hover/file:scale-110 transition-transform duration-300">📦</div>
                                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                                        {{ bulkForm.file ? bulkForm.file.name : 'Drop JSON / CSV' }}
                                    </div>
                                </div>
                                <InputError class="mt-1" :message="bulkForm.errors.file" />
                            </div>
                            <button class="w-full bg-[#3A3541] text-white font-bold py-3 rounded-lg hover:bg-[#2A2531] transition shadow-md uppercase tracking-widest text-[11px]" :disabled="bulkForm.processing">
                                {{ bulkForm.processing ? 'Memproses...' : 'Kirim Logistik' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                     <div class="materio-card p-5 bg-gradient-to-br from-[#8C57FF]/5 to-transparent">
                         <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Vault Library</div>
                         <div class="flex items-baseline gap-2">
                             <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ total_local || 0 }}</div>
                             <div class="text-[11px] font-bold text-[#8C57FF] uppercase">Items</div>
                         </div>
                     </div>
                     <div class="materio-card p-5 bg-gradient-to-br from-green-500/5 to-transparent">
                         <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Sync Efficiency</div>
                         <div class="flex items-baseline justify-between mb-3">
                             <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ total_mirrored || 0 }}</div>
                             <div class="text-xs font-bold text-green-600">{{ Math.round((total_mirrored / total_local) * 100) || 0 }}%</div>
                         </div>
                         <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                             <div class="h-full bg-green-500 transition-all duration-1000 rounded-full" :style="{ width: ((total_mirrored / total_local) * 100 || 0) + '%' }"></div>
                         </div>
                     </div>
                     <div class="materio-card p-5 bg-gradient-to-br from-amber-500/5 to-transparent">
                         <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Queue Pending</div>
                         <div class="flex items-baseline gap-2">
                             <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ total_pending || 0 }}</div>
                             <div class="text-[11px] font-bold text-amber-600 uppercase">Awaiting</div>
                         </div>
                     </div>
                </div>

                <!-- Mirroring Live Monitor (Informative Dashboard) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    <!-- Automated Pipeline Dashboard -->
                    <div class="materio-card p-8 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-[#8C57FF]/5 via-transparent to-green-500/5 opacity-50"></div>
                        
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4 relative z-10">
                            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#8C57FF] animate-pulse"></span>
                                Distribution Pipeline
                            </h3>
                            <span class="text-[9px] font-bold text-[#8C57FF] uppercase tracking-widest italic">Multi-Host Sync Engine</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 relative z-10">
                            <!-- Stage 1: Videy CDN -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1">
                                <div :class="{'animate-pulse bg-[#8C57FF]/10 border-[#8C57FF]/30': activeDownloads > 0}" class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-2xl shadow-sm transition-all duration-500">
                                    ☁️
                                </div>
                                <div class="text-[11px] font-bold text-[#3A3541] uppercase">Videy CDN</div>
                                <div class="flex flex-col gap-0.5">
                                    <div class="text-[10px] text-gray-500">{{ total_download_pending }} Pending</div>
                                    <div v-if="activeDownloads > 0" class="text-[9px] font-bold text-[#8C57FF] animate-pulse">{{ activeDownloads }} DL ({{ averageDownloadProgress }}%)</div>
                                </div>
                            </div>

                            <div class="hidden sm:block text-gray-200 animate-pulse text-xl">→</div>

                            <!-- Stage 2: Local Storage -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1">
                                <div class="w-14 h-14 rounded-2xl bg-[#8C57FF]/10 border border-[#8C57FF]/20 flex items-center justify-center text-2xl shadow-md shadow-[#8C57FF]/10">💾</div>
                                <div class="text-[11px] font-bold text-[#3A3541] uppercase">Vault Local</div>
                                <div class="text-[10px] font-bold text-[#8C57FF]">{{ localReady }} Ready</div>
                            </div>

                            <div class="hidden sm:block text-gray-200 animate-pulse text-xl">→</div>

                            <!-- Stage 3: Mirrors -->
                            <div class="flex flex-col items-center text-center gap-2 flex-1 relative group/mirrors">
                                <div :class="{'animate-pulse bg-green-500/10 border-green-500/30': activeMirrors > 0}" class="w-14 h-14 rounded-2xl bg-green-50 border border-green-100 flex items-center justify-center text-2xl shadow-sm transition-all duration-500">
                                    📼
                                </div>
                                <div class="text-[11px] font-bold text-[#3A3541] uppercase">Mirrors Active</div>
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] font-bold text-green-600">TAP: {{ hostStats.streamtape }}</span>
                                            <span class="text-[9px] font-bold text-blue-500">DDS: {{ hostStats.doodstream }}</span>
                                        </div>
                                    </div>
                                    <div v-if="activeMirrors > 0" class="text-[9px] font-bold text-amber-500 animate-pulse">{{ activeMirrors }} Sync ({{ averageUploadProgress }}%)</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 relative z-10">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">
                                <span>Total Keberhasilan Distribusi</span>
                                <span class="text-white">{{ Math.round(Math.min((mirroredTotal / (localReady || 1)) * 100, 100)) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                <div 
                                    class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(79,70,229,0.4)]" 
                                    :style="{ width: Math.min((mirroredTotal / (localReady || 1)) * 100, 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="materio-card p-8">
                         <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                            <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Synchronization Activity
                            </h3>
                        </div>
                        <div class="space-y-4 max-h-[400px] overflow-y-auto no-scrollbar pr-2">
                            <div v-for="video in recentActivityList" :key="video.id" 
                                 class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-[#8C57FF]/30 transition-all cursor-pointer group/item"
                                 @click="openPreview(video)"
                            >
                                <div class="w-14 aspect-video rounded-lg bg-gray-200 flex-shrink-0 overflow-hidden shadow-sm relative">
                                     <img v-if="video.thumbnail_url" :src="video.thumbnail_url" class="w-full h-full object-cover transition-transform group-hover/item:scale-110" />
                                     <div v-else class="w-full h-full flex items-center justify-center text-xs">🎬</div>
                                     <div class="absolute inset-0 bg-black/0 group-hover/item:bg-black/20 transition-colors"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[11px] font-bold text-[#3A3541] truncate uppercase tracking-tight group-hover/item:text-[#8C57FF] transition-colors">{{ video.title }}</div>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <div v-for="(status, host) in video.hosting_status" :key="host" 
                                             class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg border text-[9px] font-bold uppercase tracking-wider"
                                             :class="{
                                                'bg-green-50 border-green-100 text-green-600': status === 'success',
                                                'bg-amber-50 border-amber-100 text-amber-600': status === 'pending' || status === 'uploading' || status === 'remote_processing',
                                                'bg-red-50 border-red-100 text-red-500': status && status.startsWith('failed'),
                                             }"
                                        >
                                            <div class="w-1.5 h-1.5 rounded-full"
                                                 :class="{
                                                    'bg-green-500': status === 'success',
                                                    'bg-amber-500 animate-pulse': status === 'pending' || status === 'uploading' || status === 'remote_processing',
                                                    'bg-red-500': status && status.startsWith('failed')
                                                 }"
                                            ></div>
                                            {{ host === 'streamtape' ? 'TAP' : (host === 'doodstream' ? 'DDS' : host.substring(0,3)) }}
                                            <span v-if="(status === 'uploading' || status === 'remote_processing') && uploadProgress[video.id]" class="ml-1 text-blue-600">
                                                {{ uploadProgress[video.id] }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase flex flex-col items-end gap-1">
                                    <span>{{ new Date(video.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                </div>
                            </div>
                            <div v-if="recent_activity.length === 0" class="text-center py-12 text-gray-300 text-[11px] font-bold uppercase tracking-widest">
                                No recent activity
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video List -->
                <div class="materio-card overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between flex-wrap gap-4">
                         <h3 class="font-bold text-[#3A3541] text-lg flex items-center gap-3">
                            <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                             Video Vault
                             
                             <!-- Proxy Toggle -->
                             <div class="flex items-center bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 gap-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Proxy</span>
                                <button 
                                    @click="proxyActive = !proxyActive; toggleProxy()"
                                    :class="proxyActive ? 'bg-[#8C57FF]' : 'bg-gray-300'"
                                    class="relative inline-flex h-4 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                >
                                    <span 
                                        :class="proxyActive ? 'translate-x-5' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition duration-200 ease-in-out"
                                    ></span>
                                </button>
                                <span :class="proxyActive ? 'text-[#8C57FF]' : 'text-gray-400'" class="text-[10px] font-bold">
                                    {{ proxyActive ? 'ON' : 'OFF' }}
                                </span>
                             </div>
                         </h3>
                         
                         <!-- Bulk Actions Toolbar -->
                         <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center gap-3">
                            <span class="text-[11px] font-bold text-[#8C57FF] uppercase tracking-wider">{{ selectedIds.length }} selected</span>
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="exportSocialLinks"
                                    class="px-4 py-1.5 bg-green-50 hover:bg-green-100 text-green-600 text-[11px] font-bold rounded-lg transition uppercase flex items-center gap-2 border border-green-100"
                                >
                                    Export
                                </button>
                                <button 
                                    @click="deleteSelected"
                                    class="px-4 py-1.5 bg-red-50 hover:bg-red-100 text-red-500 text-[11px] font-bold rounded-lg transition uppercase flex items-center gap-2 border border-red-100"
                                >
                                    Delete
                                </button>
                            </div>
                         </div>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold uppercase text-gray-400 tracking-wider border-b border-gray-100 bg-gray-50/30">
                                    <th class="py-4 px-6 w-12">
                                        <Checkbox :checked="isAllSelected" @change="toggleSelectAll" class="!bg-white !border-gray-300 !text-[#8C57FF] rounded" />
                                    </th>
                                    <th class="py-4 px-4">Detail Media</th>
                                    <th class="py-4 px-4 hidden md:table-cell">Health Status</th>
                                    <th class="py-4 px-4 hidden lg:table-cell">Category</th>
                                    <th class="py-4 px-4 hidden sm:table-cell">Tier</th>
                                    <th class="py-4 px-4">Mirrors</th>
                                    <th class="py-4 px-4 hidden lg:table-cell">Storage</th>
                                    <th class="py-4 px-4 hidden md:table-cell text-center">Stats</th>
                                    <th class="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="video in videos.data" :key="video.id" class="group hover:bg-gray-50 transition-colors" :class="{'bg-[#8C57FF]/5': selectedIds.includes(video.id)}">
                                    <td class="py-3 px-6">
                                        <Checkbox v-model:checked="selectedIds" :value="video.id" class="!bg-white !border-gray-300 !text-[#8C57FF] rounded" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div :ref="el => observeVideo(el, video.id)" class="w-20 aspect-video flex-shrink-0 rounded-lg bg-gray-100 border border-gray-100 flex items-center justify-center overflow-hidden relative cursor-pointer"
                                                 @click="openPreview(video)">
                                                <img 
                                                    v-if="video.thumbnail_url" 
                                                    :src="video.thumbnail_url" 
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                                    loading="lazy" 
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center">
                                                    <span class="text-sm">🎬</span>
                                                </div>
                                                <div v-if="video.download_status === 'completed'" class="absolute top-1 left-1 text-[8px] bg-green-500 text-white px-1.5 py-0.5 rounded-md font-bold z-10 uppercase">LOCAL</div>
                                            </div>
                                            <div class="min-w-0">
                                                 <div class="text-sm font-bold text-[#3A3541] group-hover:text-[#8C57FF] transition truncate max-w-[180px] sm:max-w-[240px]" @click="openPreview(video)">{{ video.title }}</div>
                                                 <div class="text-[11px] text-gray-400 truncate opacity-80 max-w-[180px] mt-0.5">{{ video.url }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden md:table-cell">
                                        <div v-if="video.health_report" class="flex flex-col gap-1">
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.local?.status === 'healthy' ? 'bg-green-500' : (video.health_report.local?.status === 'na' ? 'bg-gray-300' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold text-gray-400">LOC</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.videy?.status === 'healthy' ? 'bg-green-500' : (video.health_report.videy?.status === 'na' ? 'bg-gray-300' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold text-gray-400">VDY</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <div :class="video.health_report.streamtape?.status === 'healthy' ? 'bg-green-500' : (video.health_report.streamtape?.status === 'na' ? 'bg-gray-300' : 'bg-red-500')" class="w-2 h-2 rounded-full"></div>
                                                 <span class="text-[10px] font-bold text-gray-400">TAP</span>
                                             </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden lg:table-cell">
                                         <span v-if="video.category" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 border border-gray-100 rounded-lg text-[11px] font-bold text-[#3A3541]">
                                             <span class="text-sm">{{ video.category.icon || '📁' }}</span>
                                             {{ video.category.name }}
                                         </span>
                                         <span v-else class="text-[11px] text-gray-300 italic">-</span>
                                     </td>
                                    <td class="py-3 px-4 hidden sm:table-cell">
                                         <span v-if="video.is_premium" class="bg-[#8C57FF]/10 text-[#8C57FF] text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-[#8C57FF]/10">Premium</span>
                                         <span v-else class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Standard</span>
                                     </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col gap-1.5">
                                            <div v-for="(status, host) in (video.hosting_status || {streamtape: null, doodstream: null})" :key="host" class="flex items-center gap-2">
                                                <div 
                                                    :class="[getMirrorStatusClass(status), {'border-dashed': host === 'doodstream'}]"
                                                    class="w-2.5 h-2.5 rounded-full cursor-help transition-all"
                                                    @click="distributeVideo(video.id, host)"
                                                    :title="getMirrorStatusLabel(status)"
                                                ></div>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase">{{ host === 'streamtape' ? 'TAP' : (host === 'doodstream' ? 'DDS' : host.substring(0,3)) }}</span>
                                                <span v-if="(status === 'uploading' || status === 'remote_processing') && uploadProgress[video.id]" class="text-[9px] font-bold text-blue-500 animate-pulse">
                                                    {{ uploadProgress[video.id] }}%
                                                </span>
                                                <div v-if="(status === 'uploading' || status === 'remote_processing') && uploadProgress[video.id]" class="w-12 h-1 bg-gray-100 rounded-full overflow-hidden ml-1">
                                                    <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: uploadProgress[video.id] + '%' }"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden lg:table-cell">
                                         <div class="flex flex-col gap-1">
                                             <span v-if="video.download_status === 'completed'" class="text-[10px] font-bold text-green-600 bg-green-50 px-2.5 py-1 rounded-full border border-green-100 w-fit">
                                                 LOCAL
                                             </span>
                                             <div v-else-if="video.download_status === 'downloading'" class="flex flex-col gap-1 w-full max-w-[100px]">
                                                 <span class="text-[9px] font-bold text-blue-500 flex justify-between">
                                                     <span>DL</span>
                                                     <span>{{ downloadProgress[video.id] || 0 }}%</span>
                                                 </span>
                                                 <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                                                     <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: (downloadProgress[video.id] || 0) + '%' }"></div>
                                                 </div>
                                             </div>
                                             <span v-else class="text-[10px] font-bold text-gray-400 uppercase">Remote</span>
                                         </div>
                                     </td>
                                     <td class="py-3 px-4 hidden md:table-cell text-center">
                                         <div class="text-xs font-bold text-[#3A3541]">{{ video.views.toLocaleString() }}</div>
                                     </td>
                                     <td class="py-3 px-6 text-right">
                                         <div class="flex justify-end gap-2">
                                             <button @click="openEditModal(video)" class="p-2 text-gray-400 hover:text-[#8C57FF] hover:bg-[#8C57FF]/10 rounded-lg transition-all" title="Edit">
                                                 <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                             </button>
                                             <button @click="deleteVideo(video.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                                 <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                             </button>
                                         </div>
                                     </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30 flex justify-center">
                         <div class="flex gap-2 flex-wrap justify-center">
                            <Link v-for="link in videos.links" :key="link.label" 
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-[13px] font-bold transition-all shadow-sm',
                                    link.active ? 'bg-[#8C57FF] text-white' : 'bg-white text-gray-500 hover:text-[#8C57FF] hover:bg-gray-50 border border-gray-100',
                                    !link.url ? 'opacity-30 cursor-not-allowed' : ''
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
    </MaterioLayout>
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
