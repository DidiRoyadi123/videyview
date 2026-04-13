<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    content: '',
});

const isProcessing = ref(false);

const submit = () => {
    isProcessing.value = true;
    form.post(route('admin.videos.bulk-sync.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('content');
            isProcessing.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
};
</script>

<template>
    <Head title="Bulk Link Sync" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white tracking-tight">
                    Bulk <span class="text-indigo-500">Link Sync</span>
                </h2>
                <div class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 rounded-full">
                    <span class="text-xs font-medium text-indigo-400 uppercase tracking-widest">Manual Bridge</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="glass-dark border border-white/5 rounded-3xl overflow-hidden shadow-2xl">
                    <div class="p-8">
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-white mb-2 text-center sm:text-left">Doodstream Manual Mirroring</h3>
                            <p class="text-slate-400 text-sm leading-relaxed">
                                Tempelkan data kado dari Doodstream di bawah ini. Sistem akan secara otomatis mengekstrak ID Videy dari komentar 
                                <code class="bg-white/5 px-1.5 py-0.5 rounded text-indigo-300">&lt;!-- video-ID --&gt;</code> 
                                dan menjodohkannya dengan link di bawahnya ke dalam database.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="group relative">
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-10 group-focus-within:opacity-30 transition duration-500"></div>
                                <textarea
                                    v-model="form.content"
                                    rows="15"
                                    class="relative w-full bg-slate-900/50 border border-white/10 rounded-2xl text-slate-300 text-sm font-mono placeholder-slate-600 focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-300"
                                    placeholder="<!-- video-1utm0any1-9BzlL -->&#10;https://playmogo.com/e/grjt0x291jzc&#10;&#10;<!-- video-1mqb45el1-NuCA1 -->&#10;https://playmogo.com/e/obrsyaxnb6zl"
                                    required
                                ></textarea>
                            </div>

                            <div v-if="form.errors.content" class="text-red-400 text-xs mt-1 px-2">
                                {{ form.errors.content }}
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <div class="hidden sm:block text-xs text-slate-500 italic">
                                    Format: Log-style with HTML comments and URLs. Duplicates are ignored.
                                </div>
                                <button
                                    type="submit"
                                    class="w-full sm:w-auto btn-premium flex items-center justify-center gap-3 !py-4 !px-10"
                                    :disabled="form.processing || isProcessing"
                                >
                                    <svg v-if="isProcessing" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>{{ isProcessing ? 'Syncing Records...' : 'Start Mass Sync' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Footer Help -->
                    <div class="bg-indigo-500/5 border-t border-white/5 p-6 flex items-start gap-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1 text-v 8h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-indigo-300 font-medium text-sm mb-1">Smart Engine Analysis</h4>
                            <p class="text-slate-500 text-xs">
                                Sistem kami menggunakan algoritma V4 Case-Insensitive. Tidak peduli ID di Doodstream menggunakan huruf kecil/besar, selama pola ID-nya cocok dengan database VideyView, status mirroring akan sukses diupdate.
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
    @apply bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-lg shadow-indigo-500/20 transform hover:-translate-y-0.5 transition-all duration-300 active:scale-95 disabled:opacity-50 disabled:pointer-events-none;
}
</style>
