<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
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
    <Head title="Bulk Pro Uploader - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.videos.index')" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-[#3A3541]">Bulk Pro Uploader</h2>
                        <p class="text-sm text-gray-500 mt-1">Sistem unggah massal berbasis logistik performa tinggi.</p>
                    </div>
                </div>
                <div class="bg-[#8C57FF]/5 px-4 py-2 rounded-xl border border-[#8C57FF]/10">
                    <span class="text-xs font-bold uppercase text-[#8C57FF] tracking-widest leading-none">Logistics Engine v6.8</span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Global Settings Card -->
            <div class="materio-card p-6 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 p-8 text-[#8C57FF]/5 select-none pointer-events-none transform rotate-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-40 w-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#8C57FF]"></span>
                    Konfigurasi Batch Logistik
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                    <div class="space-y-2">
                        <label class="text-[11px] font-bold uppercase text-gray-500 tracking-widest pl-1">Kategori (Global)</label>
                        <select 
                            v-model="globalSettings.category_id"
                            class="w-full bg-white border-gray-200 rounded-2xl py-3.5 px-4 text-sm text-[#3A3541] focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all appearance-none cursor-pointer"
                        >
                            <option value="">Tanpa Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-8 md:justify-center">
                        <label class="flex items-center group cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" v-model="globalSettings.is_premium" class="sr-only" />
                                <div :class="globalSettings.is_premium ? 'bg-[#8C57FF]' : 'bg-gray-200'" class="block w-11 h-6 rounded-full transition-colors"></div>
                                <div :class="globalSettings.is_premium ? 'translate-x-6' : 'translate-x-1'" class="absolute left-0 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                            </div>
                            <span class="ms-3 text-[11px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-[#8C57FF] transition">Premium</span>
                        </label>

                        <label class="flex items-center group cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" v-model="globalSettings.auto_mirror" class="sr-only" />
                                <div :class="globalSettings.auto_mirror ? 'bg-green-500' : 'bg-gray-200'" class="block w-11 h-6 rounded-full transition-colors"></div>
                                <div :class="globalSettings.auto_mirror ? 'translate-x-6' : 'translate-x-1'" class="absolute left-0 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                            </div>
                            <span class="ms-3 text-[11px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-green-600 transition">Mirroring</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                         <button 
                            @click="clearCompleted"
                            v-if="stats.success > 0 || stats.error > 0"
                            class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-[#3A3541] text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all"
                         >
                            Bersihkan Selesai
                         </button>
                         <button 
                            @click="clearQueue"
                            v-if="stats.total > 0"
                            class="px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-500 text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all"
                         >
                            Hapus Semua
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
                    dragActive ? 'border-[#8C57FF] bg-[#8C57FF]/5 scale-[0.99]' : 'border-gray-200 bg-white',
                    'relative border-4 border-dashed rounded-[32px] p-16 transition-all duration-300 min-h-[320px] flex flex-col items-center justify-center group shadow-sm'
                ]"
            >
                <input 
                    type="file" 
                    multiple 
                    accept="video/*" 
                    @change="handleFileSelect"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                />
                
                <div class="text-center space-y-6">
                    <div :class="dragActive ? 'scale-125 rotate-6 text-[#8C57FF]' : 'text-gray-300'" class="text-7xl transition-all duration-500 group-hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-[#3A3541]">Tarik & Lepas Video Di sini</h4>
                        <p class="text-sm text-gray-400 font-medium mt-2 uppercase tracking-widest">Atau klik untuk menelusuri file</p>
                    </div>
                    <div class="flex gap-3 justify-center pt-2">
                        <span class="px-3 py-1 bg-gray-50 rounded-full text-[10px] font-bold text-gray-500 uppercase border border-gray-100">MP4</span>
                        <span class="px-3 py-1 bg-gray-50 rounded-full text-[10px] font-bold text-gray-500 uppercase border border-gray-100">MKV</span>
                        <span class="px-3 py-1 bg-gray-50 rounded-full text-[10px] font-bold text-gray-500 uppercase border border-gray-100">AVI</span>
                        <span class="px-3 py-1 bg-[#8C57FF]/5 rounded-full text-[10px] font-bold text-[#8C57FF] uppercase border border-[#8C57FF]/10">Max 1GB / File</span>
                    </div>
                </div>
            </div>

            <!-- Queue Manager -->
            <div v-if="uploadQueue.length > 0" class="space-y-4">
                <div class="flex items-center justify-between px-2">
                     <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-3">
                        Antrean Logistik Aktif ({{ stats.total }})
                        <span v-if="stats.uploading > 0" class="flex h-2 w-2 rounded-full bg-[#8C57FF] animate-pulse"></span>
                    </h3>
                    <div class="flex gap-6">
                        <div class="text-[11px] font-bold text-green-600 uppercase tracking-widest flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            {{ stats.success }} Berhasil
                        </div>
                        <div class="text-[11px] font-bold text-red-500 uppercase tracking-widest flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            {{ stats.error }} Gagal
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div v-for="item in uploadQueue" :key="item.id" class="materio-card p-4 transition-all duration-300 hover:border-[#8C57FF]/30 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-2xl shrink-0">
                                <span v-if="item.status === 'pending'">🕒</span>
                                <span v-else-if="item.status === 'uploading'">🚀</span>
                                <span v-else-if="item.status === 'success'">✅</span>
                                <span v-else-if="item.status === 'error'">❌</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <h5 class="text-xs font-bold text-[#3A3541] truncate">{{ item.name }}</h5>
                                    <span class="text-[10px] font-bold text-gray-400 shrink-0">{{ item.size }}</span>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                                    <div 
                                        :class="[
                                            item.status === 'error' ? 'bg-red-500' :
                                            item.status === 'success' ? 'bg-green-500' :
                                            'bg-[#8C57FF]'
                                        ]"
                                        class="h-full transition-all duration-300 rounded-full" 
                                        :style="{ width: item.progress + '%' }"
                                    ></div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-bold uppercase tracking-widest" :class="item.status === 'error' ? 'text-red-500' : 'text-gray-400'">
                                        {{ item.status === 'uploading' ? `Mengunggah... ${item.progress}%` : item.status.toUpperCase() }}
                                    </span>
                                    <span v-if="item.error" class="text-[10px] text-red-500 font-bold truncate max-w-[180px] italic">{{ item.error }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-24 bg-gray-50/50 rounded-[32px] border border-dashed border-gray-200">
                 <div class="text-7xl opacity-20 grayscale mb-6 transform hover:scale-110 transition-transform duration-500 cursor-default">🛸</div>
                 <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.4em]">Antrean Logistik Kosong</p>
            </div>
        </div>
    </MaterioLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
