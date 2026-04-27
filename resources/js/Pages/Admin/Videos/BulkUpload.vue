<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/Composables/useToast';
import axios from 'axios';

const props = defineProps({
    categories: Array
});

const { success: toastSuccess, error: toastError } = useToast();

const dragActive = ref(false);
const uploadQueue = ref([]);
const globalSettings = ref({
    category_id: '',
    is_premium: true,
    auto_mirror: true
});

const handleDrop = (e) => {
    dragActive.value = false;
    handleFiles(e.dataTransfer.files);
};

const handleFileSelect = (e) => {
    handleFiles(e.target.files);
};

const handleFiles = (files) => {
    const fileList = Array.from(files);
    fileList.forEach(file => {
        // Simple type check
        if (!file.type.startsWith('video/') && !file.name.match(/\.(mp4|mkv|avi|mov|wmv)$/i)) {
            toastError(`${file.name} mungkin bukan file video.`);
            return;
        }
        
        // Avoid duplicate files in the session queue
        if (uploadQueue.value.some(f => f.name === file.name && f.size_raw === file.size)) {
            return;
        }

        uploadQueue.value.push({
            file,
            id: Math.random().toString(36).substr(2, 9),
            name: file.name,
            size_raw: file.size,
            size: (file.size / (1024 * 1024)).toFixed(2) + ' MB',
            progress: 0,
            status: 'pending', // pending, uploading, success, error
            error: null
        });
    });
    processQueue();
};

const processQueue = async () => {
    const uploadingCount = uploadQueue.value.filter(f => f.status === 'uploading').length;
    if (uploadingCount >= 2) return; // limit concurrency to 2 to avoid browser/server crash

    const next = uploadQueue.value.find(f => f.status === 'pending');
    if (!next) return;

    next.status = 'uploading';
    
    const formData = new FormData();
    formData.append('video_file', next.file);
    formData.append('category_id', globalSettings.value.category_id);
    formData.append('is_premium', globalSettings.value.is_premium ? 1 : 0);
    formData.append('auto_mirror', globalSettings.value.auto_mirror ? 1 : 0);

    try {
        const response = await axios.post(route('admin.videos.upload-ajax'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                next.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            }
        });

        if (response.data.success) {
            next.status = 'success';
            next.progress = 100;
        } else {
            next.status = 'error';
            next.error = response.data.message;
        }
    } catch (err) {
        next.status = 'error';
        next.error = err.response?.data?.message || 'Network error / File too large';
        console.error(err);
    } finally {
        processQueue(); // Try to start next one
    }
};

const clearQueue = () => {
    if (uploadQueue.value.some(f => f.status === 'uploading')) {
        if (!confirm('Proses unggah sedang berjalan. Yakin ingin membersihkan antrean?')) return;
    }
    uploadQueue.value = [];
};

const clearCompleted = () => {
    uploadQueue.value = uploadQueue.value.filter(f => f.status !== 'success' && f.status !== 'error');
};

const stats = computed(() => {
    return {
        total: uploadQueue.value.length,
        success: uploadQueue.value.filter(f => f.status === 'success').length,
        error: uploadQueue.value.filter(f => f.status === 'error').length,
        uploading: uploadQueue.value.filter(f => f.status === 'uploading').length,
        pending: uploadQueue.value.filter(f => f.status === 'pending').length,
    };
});

</script>

<template>
    <Head title="Admin - Bulk Pro Uploader" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.videos.index')" class="p-2 hover:bg-white/5 rounded-full transition text-[rgb(var(--text-muted))]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div class="w-1 h-8 bg-indigo-600 rounded-full"></div>
                    <h2 class="text-2xl font-black text-[rgb(var(--text-main))] italic uppercase tracking-tight">Bulk Pro <span class="text-indigo-600">Uploader</span></h2>
                </div>
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-500/10 px-3 py-1.5 rounded-full border border-indigo-500/20">
                        <span class="text-[10px] font-bold uppercase text-indigo-600 tracking-widest">v6.8 Logistics</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Global Settings Card -->
                <div class="glass shadow-royale p-6 rounded-3xl border border-[rgb(var(--border-main))] relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 text-indigo-500/5 select-none pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <h3 class="text-xs font-black text-[rgb(var(--text-muted))] uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.5)]"></span>
                        Konfigurasi Logistik Massal
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold uppercase text-[rgb(var(--text-muted))] tracking-widest pl-1">Kategori (Semua File)</label>
                            <select 
                                v-model="globalSettings.category_id"
                                class="w-full bg-[rgb(var(--bg-input))] border-none rounded-2xl py-3 px-4 text-sm text-[rgb(var(--text-main))] focus:ring-2 focus:ring-indigo-500/20 appearance-none cursor-pointer shadow-inner"
                            >
                                <option value="">Tanpa Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-8 md:justify-center">
                            <label class="flex items-center group cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="globalSettings.is_premium" class="sr-only" />
                                    <div :class="globalSettings.is_premium ? 'bg-indigo-600' : 'bg-slate-700'" class="block w-10 h-6 rounded-full transition-colors"></div>
                                    <div :class="globalSettings.is_premium ? 'translate-x-5' : 'translate-x-1'" class="absolute left-0 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                                </div>
                                <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-[rgb(var(--text-muted))] group-hover:text-indigo-400 transition">Premium</span>
                            </label>

                            <label class="flex items-center group cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="globalSettings.auto_mirror" class="sr-only" />
                                    <div :class="globalSettings.auto_mirror ? 'bg-emerald-600' : 'bg-slate-700'" class="block w-10 h-6 rounded-full transition-colors"></div>
                                    <div :class="globalSettings.auto_mirror ? 'translate-x-5' : 'translate-x-1'" class="absolute left-0 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                                </div>
                                <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-[rgb(var(--text-muted))] group-hover:text-emerald-400 transition">Auto-Mirror</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                             <button 
                                @click="clearCompleted"
                                v-if="stats.success > 0 || stats.error > 0"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-[9px] font-black uppercase tracking-widest rounded-xl transition-all"
                             >
                                Bersihkan Berhasil
                             </button>
                             <button 
                                @click="clearQueue"
                                v-if="stats.total > 0"
                                class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all"
                             >
                                Hapus Antrean
                             </button>
                        </div>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div 
                    @dragover.prevent="dragActive = true"
                    @dragleave.prevent="dragActive = false"
                    @drop.prevent="handleDrop"
                    :class="[
                        dragActive ? 'border-indigo-500 bg-indigo-500/5 scale-[0.99]' : 'border-[rgb(var(--border-main))] bg-transparent',
                        'relative border-4 border-dashed rounded-[3rem] p-12 transition-all duration-300 min-h-[300px] flex flex-col items-center justify-center group'
                    ]"
                >
                    <input 
                        type="file" 
                        multiple 
                        accept="video/*" 
                        @change="handleFileSelect"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                    />
                    
                    <div class="text-center space-y-4">
                        <div :class="dragActive ? 'scale-125 rotate-12 text-indigo-500' : 'text-slate-600'" class="text-6xl transition-all duration-500 group-hover:scale-110">
                            🚀
                        </div>
                        <div>
                            <h4 class="text-xl font-black text-[rgb(var(--text-main))] uppercase tracking-tight">Tarik & Lepas File Di sini</h4>
                            <p class="text-xs text-[rgb(var(--text-muted))] font-bold uppercase tracking-widest mt-2">Atau klik untuk memilih dari penyimpanan lokal</p>
                        </div>
                        <div class="flex gap-2 justify-center pt-4">
                            <span class="px-2 py-1 bg-white/5 rounded-md text-[8px] font-black text-slate-500 uppercase">MP4</span>
                            <span class="px-2 py-1 bg-white/5 rounded-md text-[8px] font-black text-slate-500 uppercase">MKV</span>
                            <span class="px-2 py-1 bg-white/5 rounded-md text-[8px] font-black text-slate-500 uppercase">AVI</span>
                            <span class="px-2 py-1 bg-white/5 rounded-md text-[8px] font-black text-slate-500 uppercase">MAX 1GB/FILE</span>
                        </div>
                    </div>
                </div>

                <!-- Queue Manager -->
                <div v-if="uploadQueue.length > 0" class="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex items-center justify-between px-2">
                         <h3 class="text-xs font-black text-[rgb(var(--text-muted))] uppercase tracking-widest flex items-center gap-2">
                            Antrean Aktif ({{ stats.total }})
                            <span v-if="stats.uploading > 0" class="flex h-2 w-2 rounded-full bg-indigo-500 animate-ping"></span>
                        </h3>
                        <div class="flex gap-4">
                            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">{{ stats.success }} Berhasil</div>
                            <div class="text-[10px] font-bold text-red-500 uppercase tracking-widest">{{ stats.error }} Gagal</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="item in uploadQueue" :key="item.id" class="glass p-4 rounded-2xl border border-[rgb(var(--border-main))] group transition hover:border-indigo-500/30">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-[rgb(var(--bg-input))] flex items-center justify-center text-xl shrink-0">
                                    <span v-if="item.status === 'pending'">🕒</span>
                                    <span v-else-if="item.status === 'uploading'">🚀</span>
                                    <span v-else-if="item.status === 'success'">✅</span>
                                    <span v-else-if="item.status === 'error'">❌</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <h5 class="text-[11px] font-black text-white truncate uppercase tracking-tighter">{{ item.name }}</h5>
                                        <span class="text-[9px] font-bold text-slate-500 shrink-0">{{ item.size }}</span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden mt-2 relative">
                                        <div 
                                            :class="[
                                                item.status === 'error' ? 'bg-red-500' :
                                                item.status === 'success' ? 'bg-emerald-500' :
                                                'bg-indigo-500'
                                            ]"
                                            class="h-full transition-all duration-300 rounded-full" 
                                            :style="{ width: item.progress + '%' }"
                                        ></div>
                                    </div>

                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-[8px] font-black uppercase tracking-widest" :class="item.status === 'error' ? 'text-red-500' : 'text-slate-500'">
                                            {{ item.status === 'uploading' ? `Mengunggah ${item.progress}%` : item.status.toUpperCase() }}
                                        </span>
                                        <span v-if="item.error" class="text-[8px] text-red-400 font-bold truncate max-w-[200px]">{{ item.error }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20">
                     <div class="text-8xl opacity-10 grayscale mb-6">🛸</div>
                     <p class="text-xs font-black text-slate-600 uppercase tracking-[0.3em]">Antrean Kosong. Siap Menerima Logistik.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass {
    background: rgba(30, 41, 59, 0.5);
    backdrop-filter: blur(16px);
}
.shadow-royale {
    box-shadow: 0 15px 35px -12px rgba(0, 0, 0, 0.4);
}
</style>
