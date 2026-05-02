<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onBeforeUnmount } from 'vue';

const form = useForm({
    content: '',
});

const isProcessing = ref(false);
const progressText = ref('');
let progressInterval = null;

const pollProgress = async () => {
    // Legacy cache polling endpoint fallback
};

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
    
    // Dispatch global toast success
    document.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: `Berhasil menyinkronkan ${totalMatched} tautan secara massal.` } }));
};
</script>

<template>
    <Head title="Bulk Link Sync" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Bulk <span class="text-indigo-500">Link Sync</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Jembatan Sinkronisasi Manual</p>
                </div>
                <div class="bg-indigo-500/10 px-3 py-1.5 rounded-xl border border-indigo-500/20">
                    <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">Manual Bridge</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="glass-dark border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                    <div class="p-5 sm:p-6">
                        <div class="mb-5">
                            <h3 class="text-base font-black text-white mb-2 uppercase tracking-tight">Streamtape & Doodstream Smart Sync</h3>
                            <p class="text-slate-400 text-sm leading-relaxed font-medium">
                                Tempelkan data dari host video di bawah ini. Untuk **Streamtape**, sistem akan otomatis mencocokkan melalui slug di nama file. Untuk **Doodstream**, pastikan ada penanda
                                <code class="bg-indigo-500/20 px-2 py-0.5 rounded-lg text-indigo-300 font-bold border border-indigo-500/30 text-xs">&lt;!-- video-ID --&gt;</code> 
                                di atas linknya.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="group relative">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur-lg opacity-10 group-focus-within:opacity-30 transition duration-700"></div>
                                <textarea
                                    v-model="form.content"
                                    rows="14"
                                    class="relative w-full bg-black/60 border border-white/10 rounded-2xl text-indigo-300 text-sm font-mono placeholder-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500/50 transition-all duration-300 p-4 shadow-inner outline-none"
                                    placeholder="<!-- Contoh Streamtape -->&#10;https://streamtape.to/e/rBrBYWBqRoibZBR/video-video-slug.mp4&#10;&#10;<!-- Contoh Doodstream -->&#10;<!-- video-slug -->&#10;https://doodstream.com/e/obrsyaxnb6zl"
                                    required
                                ></textarea>
                            </div>


                            <div v-if="form.errors.content" class="text-red-400 text-xs mt-1">
                                {{ form.errors.content }}
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <div class="hidden sm:block text-xs text-slate-500 font-medium uppercase tracking-widest">
                                    Format: Log-style data with HTML comments and URLs.
                                </div>
                                <button
                                    type="submit"
                                    class="w-full sm:w-auto btn-premium flex items-center justify-center gap-3 !py-2.5 !px-6 !rounded-xl !text-sm !font-bold uppercase tracking-widest shadow-lg shadow-indigo-600/30 active:scale-95 transition-all"
                                    :disabled="form.processing || isProcessing"
                                >
                                    <svg v-if="isProcessing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>{{ isProcessing ? (progressText ? 'Memproses... ' + progressText : 'Memproses...') : 'Mulai Sinkronisasi' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Footer Help -->
                    <div class="bg-indigo-500/10 border-t border-white/10 p-4 flex items-start gap-4">
                        <div class="w-9 h-9 flex-shrink-0 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400 border border-indigo-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm mb-1 uppercase tracking-widest">Analis Mesin Pintar v5.0</h4>
                            <p class="text-slate-500 text-xs leading-relaxed font-medium">
                                Sistem kami menggunakan algoritma V5 Case-Insensitive yang agresif. Tidak peduli ID di Doodstream menggunakan huruf kecil/besar atau ada teks sampah lainnya, selama pola komentar cocok dengan database VideyView, status mirroring akan sukses diupdate secara presisi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(16px);
}

.btn-premium {
    @apply bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 transform hover:-translate-y-0.5 transition-all duration-300 active:scale-95 disabled:opacity-50 disabled:pointer-events-none;
}
</style>
