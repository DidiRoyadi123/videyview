<script setup>
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess } = useToast();

const props = defineProps({
    videos: Object,
    filters: Object,
    stats: Object,
});

const currentStatusFilter = ref(props.filters.status || 'all');
const uploadProgress = ref({});
let pollInterval = null;

const startPolling = () => {
    const poll = async () => {
        const activeIds = props.videos.data
            .filter(v => v.hosting_status && Object.values(v.hosting_status).some(s => s === 'uploading' || s === 'remote_processing'))
            .map(v => v.id);
            
        if (activeIds.length > 0) {
            try {
                const response = await axios.get(route('admin.videos.progress'), { params: { ids: activeIds } });
                if (response.data.uploadProgress) uploadProgress.value = { ...uploadProgress.value, ...response.data.uploadProgress };
                if (response.data.mirroring) {
                    Object.entries(response.data.mirroring).forEach(([id, status]) => {
                        const video = props.videos.data.find(v => v.id == id);
                        if (video) video.hosting_status = status;
                    });
                }
            } catch (e) { console.error(e); }
        }
        pollInterval = setTimeout(poll, activeIds.length > 0 ? 5000 : 15000);
    };
    poll();
};

onMounted(() => startPolling());
onUnmounted(() => { if (pollInterval) clearTimeout(pollInterval); });

watch(currentStatusFilter, (newStatus) => {
    router.get(route('admin.videos.mirrors'), { status: newStatus }, { preserveState: true, replace: true });
});

const copyToClipboard = async (text) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        toastSuccess('Tautan disalin ke papan klip!');
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const getStatusColorClass = (status) => {
    if (!status) return 'bg-gray-50 text-gray-400 border-gray-100';
    if (status.includes('success')) return 'bg-green-50 text-green-600 border-green-100';
    if (status.includes('failed')) return 'bg-red-50 text-red-500 border-red-100';
    if (status.includes('uploading') || status.includes('pending')) return 'bg-amber-50 text-amber-600 border-amber-100 animate-pulse';
    return 'bg-[#8C57FF]/5 text-[#8C57FF] border-[#8C57FF]/10';
};
</script>

<template>
    <Head title="Pantauan Mirror - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Pantauan Mirror</h2>
                    <p class="text-sm text-gray-500 mt-1">Pantau integritas dan ketersediaan tautan pada host eksternal.</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="materio-card p-5 cursor-pointer hover:border-green-400/50 transition-all bg-gradient-to-br from-green-500/5 to-transparent" @click="currentStatusFilter = 'success'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Sukses Sync</span>
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    </div>
                    <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ stats.total_success }}</div>
                </div>
                <div class="materio-card p-5 cursor-pointer hover:border-amber-400/50 transition-all bg-gradient-to-br from-amber-500/5 to-transparent" @click="currentStatusFilter = 'pending'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Pending / Uploading</span>
                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></div>
                    </div>
                    <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ stats.total_pending }}</div>
                </div>
                <div class="materio-card p-5 cursor-pointer hover:border-red-400/50 transition-all bg-gradient-to-br from-red-500/5 to-transparent" @click="currentStatusFilter = 'failed'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Gagal Sinkronisasi</span>
                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                    </div>
                    <div class="text-3xl font-bold text-[#3A3541] tracking-tight">{{ stats.total_failed }}</div>
                </div>
            </div>

            <!-- Host Distribution -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-for="(count, host) in stats.host_counts" :key="host" class="materio-card px-4 py-3 flex justify-between items-center bg-gray-50/50">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ host }}</span>
                    <span class="text-[#3A3541] font-bold">{{ count }}</span>
                </div>
            </div>

            <!-- Link Table Card -->
            <div class="materio-card overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h3 class="font-bold text-[#3A3541]">Mirror Database Links</h3>
                    <select v-model="currentStatusFilter" class="bg-white border-gray-200 text-[#3A3541] text-sm rounded-xl px-4 py-2 focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all">
                        <option value="all">Semua Status</option>
                        <option value="success">Sukses</option>
                        <option value="pending">Pending/Uploading</option>
                        <option value="failed">Gagal</option>
                    </select>
                </div>

                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-bold uppercase text-gray-400 tracking-wider border-b border-gray-100 bg-gray-50/30">
                                <th class="py-4 px-6">Video (Slug)</th>
                                <th class="py-4 px-4 text-center">Videy</th>
                                <th class="py-4 px-4 text-center">Streamtape</th>
                                <th class="py-4 px-4 text-center">Doodstream</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="video in videos.data" :key="video.id" class="group hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="text-sm font-bold text-[#3A3541] group-hover:text-[#8C57FF] transition truncate max-w-xs">{{ video.title }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono tracking-tight">{{ video.slug }}</div>
                                    </div>
                                </td>
                                
                                <template v-for="host in ['videy', 'streamtape', 'doodstream']" :key="host">
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex flex-col items-center gap-2" v-if="video.hosting_status && video.hosting_status[host]">
                                            <!-- Status Badge -->
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[9px] uppercase font-bold tracking-widest border flex items-center gap-1.5"
                                                :class="getStatusColorClass(video.hosting_status[host])"
                                            >
                                                {{ video.hosting_status[host] }}
                                                <span v-if="(video.hosting_status[host] === 'uploading' || video.hosting_status[host] === 'remote_processing') && uploadProgress[video.id]" class="animate-pulse opacity-80">
                                                    {{ uploadProgress[video.id] }}%
                                                </span>
                                            </span>
                                            
                                            <!-- Progress Bar -->
                                            <div v-if="(video.hosting_status[host] === 'uploading' || video.hosting_status[host] === 'remote_processing') && uploadProgress[video.id]" class="w-full h-1 bg-gray-100 rounded-full overflow-hidden mt-1">
                                                <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: uploadProgress[video.id] + '%' }"></div>
                                            </div>

                                            <!-- Link Action -->
                                            <button 
                                                v-if="video.mirror_links && video.mirror_links[host]"
                                                @click="copyToClipboard(video.mirror_links[host])"
                                                class="flex items-center gap-1.5 text-[9px] text-[#8C57FF] hover:bg-[#8C57FF]/10 px-2.5 py-1 rounded-lg border border-[#8C57FF]/10 transition-all font-mono"
                                                title="Salin Tautan"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 00-2-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                <span class="truncate max-w-[100px] uppercase">{{ video.mirror_links[host].split('/').pop() }}</span>
                                            </button>
                                        </div>
                                        <div v-else class="text-gray-200 text-sm">-</div>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="videos.data.length === 0">
                                <td colspan="4" class="py-12 text-center">
                                    <div class="text-3xl mb-3">🔍</div>
                                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Tidak ada data mirror yang ditemukan.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-5 border-t border-gray-50 bg-gray-50/30 flex justify-center" v-if="videos.last_page > 1">
                    <div class="flex gap-1.5 flex-wrap justify-center">
                        <Link v-for="(link, idx) in videos.links" :key="idx" 
                            :href="link.url || '#'"
                            :class="[
                                'px-3.5 py-2 rounded-lg text-[10px] font-bold transition-all uppercase tracking-widest border',
                                link.active ? 'bg-[#8C57FF] text-white border-[#8C57FF] shadow-sm' : 'bg-white text-gray-500 border-gray-100 hover:text-[#8C57FF] hover:border-[#8C57FF]/30',
                                !link.url ? 'opacity-30 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
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
