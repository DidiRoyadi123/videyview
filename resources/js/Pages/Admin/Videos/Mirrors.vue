<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    videos: Object,
    filters: Object,
    stats: Object,
});

const currentStatusFilter = ref(props.filters.status || 'all');

watch(currentStatusFilter, (newStatus) => {
    router.get(route('admin.videos.mirrors'), { status: newStatus }, { preserveState: true, replace: true });
});

const copyToClipboard = async (text) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        // Dispatch custom event for global toast if available
        document.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Tautan disalin!' } }));
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const getStatusColor = (status) => {
    if (!status) return 'text-slate-500 bg-slate-500/10 border-slate-500/20';
    if (status.includes('success')) return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
    if (status.includes('failed')) return 'text-red-400 bg-red-500/10 border-red-500/20';
    if (status.includes('uploading') || status.includes('pending')) return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    return 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20';
};
</script>

<template>
    <Head title="Pantauan Mirror" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Pantauan <span class="text-indigo-500">Mirror</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Status Database Host Eksternal</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Stats Row -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="glass-dark border border-emerald-500/20 rounded-2xl p-4 flex flex-col cursor-pointer hover:bg-emerald-500/5 transition-all" @click="currentStatusFilter = 'success'">
                        <span class="text-emerald-500 font-black text-2xl">{{ stats.total_success }}</span>
                        <span class="text-xs text-slate-400 uppercase font-bold tracking-widest">Sukses Sync</span>
                    </div>
                    <div class="glass-dark border border-amber-500/20 rounded-2xl p-4 flex flex-col cursor-pointer hover:bg-amber-500/5 transition-all" @click="currentStatusFilter = 'pending'">
                        <span class="text-amber-500 font-black text-2xl">{{ stats.total_pending }}</span>
                        <span class="text-xs text-slate-400 uppercase font-bold tracking-widest">Pending/Uploading</span>
                    </div>
                    <div class="glass-dark border border-red-500/20 rounded-2xl p-4 flex flex-col cursor-pointer hover:bg-red-500/5 transition-all" @click="currentStatusFilter = 'failed'">
                        <span class="text-red-500 font-black text-2xl">{{ stats.total_failed }}</span>
                        <span class="text-xs text-slate-400 uppercase font-bold tracking-widest">Gagal</span>
                    </div>
                </div>

                <!-- Specific Host Counts -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="glass-dark border border-indigo-500/20 rounded-xl p-3 flex justify-between items-center bg-indigo-500/5">
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Local</span>
                        <span class="text-white font-black text-lg">{{ stats.host_counts.local }}</span>
                    </div>
                    <div class="glass-dark border border-indigo-500/20 rounded-xl p-3 flex justify-between items-center bg-indigo-500/5">
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Videy</span>
                        <span class="text-white font-black text-lg">{{ stats.host_counts.videy }}</span>
                    </div>
                    <div class="glass-dark border border-indigo-500/20 rounded-xl p-3 flex justify-between items-center bg-indigo-500/5">
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Streamtape</span>
                        <span class="text-white font-black text-lg">{{ stats.host_counts.streamtape }}</span>
                    </div>
                    <div class="glass-dark border border-indigo-500/20 rounded-xl p-3 flex justify-between items-center bg-indigo-500/5">
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Doodstream</span>
                        <span class="text-white font-black text-lg">{{ stats.host_counts.doodstream }}</span>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="glass-dark border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                    <div class="p-4 border-b border-white/10 flex flex-wrap gap-4 justify-between items-center bg-black/20">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Mirror Database Links</h3>
                        <select v-model="currentStatusFilter" class="bg-black/40 border border-white/10 rounded-lg text-sm text-slate-300 px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="all">Semua Status</option>
                            <option value="success">Sukses</option>
                            <option value="pending">Pending/Uploading</option>
                            <option value="failed">Gagal</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-400 uppercase tracking-widest bg-black/40 border-b border-white/10">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">Video (Slug)</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-center">Videy</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-center">Streamtape</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-center">Doodstream</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="video in videos.data" :key="video.id" class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-4 py-3 align-top min-w-[200px]">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-white font-bold truncate max-w-xs">{{ video.title }}</span>
                                            <span class="text-slate-500 text-xs font-mono">{{ video.slug }}</span>
                                        </div>
                                    </td>
                                    
                                    <template v-for="host in ['videy', 'streamtape', 'doodstream']" :key="host">
                                        <td class="px-4 py-3 align-top">
                                            <div class="flex flex-col items-center gap-2" v-if="video.hosting_status && video.hosting_status[host]">
                                                <!-- Status Badge -->
                                                <span 
                                                    class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-widest border"
                                                    :class="getStatusColor(video.hosting_status[host])"
                                                >
                                                    {{ video.hosting_status[host] }}
                                                </span>
                                                
                                                <!-- Link/Copy Button -->
                                                <button 
                                                    v-if="video.mirror_links && video.mirror_links[host]"
                                                    @click="copyToClipboard(video.mirror_links[host])"
                                                    class="flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-mono bg-indigo-500/5 hover:bg-indigo-500/20 px-2 py-1 rounded transition-colors"
                                                    title="Copy Link"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    <span class="truncate max-w-[80px]">{{ video.mirror_links[host].replace('https://', '') }}</span>
                                                </button>
                                                <span v-else-if="video.hosting_status[host] === 'success'" class="text-[10px] text-slate-500 italic">No Link Saved</span>
                                            </div>
                                            <div v-else class="flex justify-center">
                                                <span class="text-slate-600 text-xs">-</span>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="videos.data.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">Tidak ada data mirror yang sesuai kriteria.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-4 border-t border-white/10 bg-black/20 flex flex-wrap items-center justify-between gap-4" v-if="videos.last_page > 1">
                        <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">
                            Halaman {{ videos.current_page }} dari {{ videos.last_page }}
                        </span>
                        <div class="flex gap-2">
                            <Link 
                                v-for="(link, idx) in videos.links" 
                                :key="idx"
                                :href="link.url || '#'"
                                class="px-3 py-1 rounded-lg text-sm font-medium transition-colors"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white',
                                    !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                                ]"
                                v-html="link.label"
                            />
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
</style>
