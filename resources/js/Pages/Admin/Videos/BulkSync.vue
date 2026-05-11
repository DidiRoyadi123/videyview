<script setup>
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess } = useToast();

const form = useForm({
    content: '',
});

const isProcessing = ref(false);
const progressText = ref('');

const submit = async () => {
    isProcessing.value = true;
    progressText.value = 'Mempersiapkan...';
    
    const lines = form.content.split('\n');
    const totalLines = lines.length;
    
    let chunks = [];
    let currentChunk = [];
    
    // Safely chunk every 100 lines, ensuring we don't break markers
    for(let i=0; i<lines.length; i++) {
        currentChunk.push(lines[i]);
        if (currentChunk.length >= 100 && !lines[i].includes('<!--')) {
            chunks.push(currentChunk.join('\n'));
            currentChunk = [];
        }
    }
    if (currentChunk.length > 0) chunks.push(currentChunk.join('\n'));
    
    let processedLines = 0;
    let totalMatched = 0;
    
    for(let chunk of chunks) {
        try {
            const resp = await window.axios.post(route('admin.videos.bulk-sync.chunk'), { content: chunk });
            totalMatched += resp.data.matched;
            processedLines += chunk.split('\n').length;
            
            // Cap at totalLines just in case
            if (processedLines > totalLines) processedLines = totalLines;
            progressText.value = `${processedLines}/${totalLines} baris`;
        } catch (e) {
            console.error('Error in chunk sync:', e);
            break;
        }
    }
    
    isProcessing.value = false;
    progressText.value = '';
    form.reset('content');
    
    toastSuccess(`Berhasil menyinkronkan ${totalMatched} tautan secara massal.`);
};
</script>

<template>
    <Head title="Bulk Link Sync - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Bulk Link Sync</h2>
                    <p class="text-sm text-gray-500 mt-1">Jembatan sinkronisasi manual untuk host eksternal.</p>
                </div>
                <div class="bg-[#8C57FF]/5 px-4 py-2 rounded-xl border border-[#8C57FF]/10">
                    <span class="text-xs font-bold uppercase text-[#8C57FF] tracking-widest leading-none">Manual Sync Bridge</span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="materio-card overflow-hidden">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-[#3A3541] mb-2 flex items-center gap-3">
                            <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                            Streamtape & Doodstream Smart Sync
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <p class="text-gray-500 text-sm leading-relaxed">
                                Tempelkan data log dari host video di bawah ini. Sistem akan otomatis mencocokkan tautan berdasarkan slug pada nama file (Streamtape) atau penanda khusus 
                                <code class="bg-[#8C57FF]/10 px-2 py-0.5 rounded-lg text-[#8C57FF] font-bold border border-[#8C57FF]/10 text-xs mx-1">&lt;!-- video-ID --&gt;</code> 
                                untuk Doodstream.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="group relative">
                            <textarea
                                v-model="form.content"
                                rows="14"
                                class="w-full bg-white border-gray-200 rounded-2xl text-[#3A3541] text-sm font-mono placeholder-gray-400 focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all duration-300 p-5 outline-none resize-none"
                                placeholder="<!-- Contoh Streamtape -->&#10;https://streamtape.to/e/rBrBYWBqRoibZBR/video-video-slug.mp4&#10;&#10;<!-- Contoh Doodstream -->&#10;<!-- video-slug -->&#10;https://doodstream.com/e/obrsyaxnb6zl"
                                required
                            ></textarea>
                        </div>

                        <div v-if="form.errors.content" class="text-red-500 text-xs font-bold bg-red-50 px-4 py-2 rounded-lg border border-red-100">
                            {{ form.errors.content }}
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div class="hidden sm:block text-[11px] text-gray-400 font-bold uppercase tracking-widest">
                                Format: Log-style data dengan komentar HTML dan URL.
                            </div>
                            <button
                                type="submit"
                                class="w-full sm:w-auto flex items-center justify-center gap-3 py-3 px-8 bg-[#8C57FF] hover:bg-[#7B47E6] text-white font-bold rounded-xl transition shadow-lg shadow-[#8C57FF]/25 text-xs uppercase tracking-widest disabled:opacity-50"
                                :disabled="form.processing || isProcessing"
                            >
                                <svg v-if="isProcessing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>{{ isProcessing ? (progressText ? 'Sinkronisasi... ' + progressText : 'Memproses...') : 'Mulai Sinkronisasi Massal' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Stats Info -->
                <div class="bg-gray-50 border-t border-gray-100 p-5 flex items-start gap-4">
                    <div class="w-10 h-10 flex-shrink-0 bg-[#8C57FF]/10 rounded-xl flex items-center justify-center text-[#8C57FF] border border-[#8C57FF]/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[#3A3541] font-bold text-sm mb-1 uppercase tracking-widest">Smart Matching Engine v5.0</h4>
                        <p class="text-gray-500 text-[11px] leading-relaxed font-medium">
                            Sistem menggunakan algoritma pencocokan agresif yang mendukung sinkronisasi massal hingga ribuan baris. Status mirroring akan diupdate secara presisi selama pola identifier (slug) ditemukan dalam data yang Anda tempelkan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
