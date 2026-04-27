<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';
import { Doughnut, Bar } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, ArcElement, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    stats: Object,
    worker_active: Boolean,
});

// Chart.js Options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#94a3b8',
                font: { size: 10, weight: 'bold' },
                usePointStyle: true,
                padding: 20
            }
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleColor: '#fff',
            bodyColor: '#cbd5e1',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6,
            cornerRadius: 12
        }
    },
    scales: {
        y: {
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#94a3b8', font: { size: 10 } }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10 } }
        }
    }
};

const donutOptions = {
    ...chartOptions,
    scales: null
};

// Mirror Distribution Data
const mirrorChartData = {
    labels: ['Berhasil', 'Menunggu', 'Gagal'],
    datasets: [{
        data: [props.stats.mirrorStats.success, props.stats.mirrorStats.pending, props.stats.mirrorStats.failed],
        backgroundColor: [
            'rgba(16, 185, 129, 0.8)', // Success
            'rgba(59, 130, 246, 0.8)', // Pending
            'rgba(239, 68, 68, 0.8)',  // Failed
        ],
        hoverBackgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
        borderWidth: 0,
        borderRadius: 10,
        spacing: 5
    }]
};

// Health Library Data
const healthChartData = {
    labels: ['Sehat (ALIVE)', 'Bermasalah', 'Unknown'],
    datasets: [{
        label: 'Integritas Library',
        data: [props.stats.healthSummary.healthy, props.stats.healthSummary.down, props.stats.healthSummary.unknown || 0],
        backgroundColor: [
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)', // Amber
            'rgba(148, 163, 184, 0.5)'  // Slate
        ],
        borderRadius: 12,
        maxBarThickness: 40
    }]
};

const getStatusColor = (status) => {
    if (!status) return 'text-slate-500';
    const s = JSON.stringify(status);
    if (s.includes('success')) return 'text-emerald-400';
    if (s.includes('uploading') || s.includes('pending')) return 'text-amber-400';
    return 'text-red-400';
};
</script>

<template>
    <Head title="Dasbor Admin Premium" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Dasbor <span class="text-indigo-500">Admin</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Komando & Intelijen Terpadu</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20 flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">Sistem Aktif</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Top Tier Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                    <!-- Total Videos -->
                    <div class="glass-card group p-4 sm:p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform duration-300 border border-indigo-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest text-right">Perpustakaan</div>
                        </div>
                        <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Total Konten</h3>
                        <div class="text-3xl sm:text-4xl font-black text-white italic tracking-tighter leading-none">{{ stats.total_videos.toLocaleString() }}</div>
                        <div class="mt-3 flex items-center gap-2">
                            <div class="h-1.5 flex-1 bg-white/5 rounded-full overflow-hidden border border-white/5">
                                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: (stats.total_local / stats.total_videos * 100) + '%' }"></div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">{{ Math.round(stats.total_local / stats.total_videos * 100) }}%</span>
                        </div>
                    </div>

                    <!-- Community -->
                    <div class="glass-card group p-4 sm:p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-violet-500/10 rounded-xl flex items-center justify-center text-violet-500 group-hover:scale-110 transition-transform duration-300 border border-violet-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1z" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-violet-400 uppercase tracking-widest text-right">Pengguna</div>
                        </div>
                        <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Terdaftar</h3>
                        <div class="text-3xl sm:text-4xl font-black text-white italic tracking-tighter leading-none">{{ stats.total_users.toLocaleString() }}</div>
                        <div class="mt-3 text-[10px] font-bold text-violet-400 flex items-center gap-1.5 bg-violet-500/5 px-2.5 py-1 rounded-lg border border-violet-500/10 w-fit">
                            👤 {{ stats.total_premium_users }} Premium
                        </div>
                    </div>

                    <!-- Mirror Mastery -->
                    <div class="glass-card group p-4 sm:p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform duration-300 border border-amber-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest text-right">Sinkronisasi</div>
                        </div>
                        <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Berhasil Sinkron</h3>
                        <div class="text-3xl sm:text-4xl font-black text-white italic tracking-tighter leading-none">{{ stats.total_mirror_success.toLocaleString() }}</div>
                        <div class="mt-3 text-[10px] font-bold text-amber-400 uppercase tracking-widest bg-amber-500/5 px-2.5 py-1 rounded-lg border border-amber-500/10">
                            {{ stats.host_stats.streamtape }} Tape · {{ stats.host_stats.doodstream }} Dood
                        </div>
                    </div>

                    <!-- Total Views -->
                    <div class="glass-card group p-4 sm:p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500 group-hover:scale-110 transition-transform duration-300 border border-emerald-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest text-right">Traffic</div>
                        </div>
                        <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Total Pemutaran</h3>
                        <div class="text-3xl sm:text-4xl font-black text-white italic tracking-tighter leading-none">{{ stats.total_views.toLocaleString() }}</div>
                        <div class="mt-3 text-[10px] font-bold text-emerald-400 uppercase tracking-widest bg-emerald-500/5 px-2.5 py-1 rounded-lg border border-emerald-500/10">Resolusi Tinggi</div>
                    </div>
                </div>

                <!-- NEW Intelijen & Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                    <!-- Mirror Distribution -->
                    <div class="glass-card p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black text-white uppercase tracking-widest italic">Distribusi Sinkronisasi</h3>
                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Live Analysis</div>
                        </div>
                        <div class="h-64 relative">
                            <Doughnut :data="mirrorChartData" :options="donutOptions" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-xl font-black text-white italic">{{ stats.mirrorStats.success }}</span>
                                <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">Mirror OK</span>
                            </div>
                        </div>
                    </div>

                    <!-- Health Analysis -->
                    <div class="glass-card p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black text-white uppercase tracking-widest italic">Integritas Library</h3>
                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Automated Audit</div>
                        </div>
                        <div class="h-64">
                            <Bar :data="healthChartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Recent Activity Table -->
                    <div class="lg:col-span-2 glass-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                            <h3 class="text-sm font-black uppercase tracking-widest text-white">Logistik Terkini</h3>
                            <Link :href="route('admin.videos.index')" class="text-indigo-400 text-[10px] font-bold uppercase hover:text-white transition-colors tracking-widest">Detail Brankas</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-indigo-500/5 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-4 py-3">Judul / ID</th>
                                        <th class="px-4 py-3 hidden sm:table-cell">Host Status</th>
                                        <th class="px-4 py-3 text-right">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="video in stats.recent_videos" :key="video.id" class="hover:bg-white/[0.05] transition-colors group">
                                        <td class="px-4 py-3">
                                            <div class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors truncate max-w-[180px]">{{ video.title }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5 tracking-wider">{{ video.slug }}</div>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            <div class="flex gap-1.5 flex-wrap">
                                                <span v-for="(status, host) in video.hosting_status" :key="host" 
                                                      class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full border"
                                                      :class="status === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-amber-500/10 border-amber-500/20 text-amber-400'">
                                                    {{ host }}
                                                </span>
                                                <span v-if="!video.hosting_status" class="text-[10px] text-slate-600 italic">Belum sinkron</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-[10px] text-slate-500 text-right group-hover:text-slate-300 transition-colors whitespace-nowrap">
                                            {{ new Date(video.created_at).toLocaleTimeString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top 10 Content -->
                    <div class="glass-card flex flex-col p-6 h-full">
                         <h3 class="text-xs font-black text-white uppercase tracking-widest italic mb-4">Top 10 Konten</h3>
                         <div class="space-y-3 overflow-y-auto max-h-[350px] no-scrollbar flex-1 pr-2">
                            <div v-for="(video, index) in stats.topVideos" :key="video.id" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/5 group hover:bg-white/10 transition-colors">
                                <div class="w-6 h-6 rounded bg-indigo-600/20 flex items-center justify-center font-black text-indigo-400 italic text-[10px]">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[10px] font-black text-white uppercase truncate">{{ video.title }}</h4>
                                    <p class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ video.views.toLocaleString() }} Views</p>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>

                <!-- Action Center -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <!-- Backup -->
                    <div class="glass-card p-5 border-emerald-500/10 hover:border-emerald-500/40">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-black text-white uppercase tracking-tight italic">Benteng Database</h4>
                                <p class="text-slate-400 text-[10px] mt-1 font-bold uppercase tracking-widest">Amankan Struktur .SQL</p>
                            </div>
                            <Link :href="route('admin.project.backup')" method="post" as="button" class="px-5 py-2 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                Backup
                            </Link>
                        </div>
                    </div>

                    <!-- PWA / Worker -->
                    <div class="glass-card p-5 border-blue-500/10 hover:border-blue-500/40">
                         <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-black text-white uppercase tracking-tight italic">Status Robot Mandor</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="props.worker_active ? 'bg-emerald-500' : 'bg-red-500'" class="w-1.5 h-1.5 rounded-full"></span>
                                    <span class="text-slate-400 text-[9px] font-black uppercase tracking-widest">{{ props.worker_active ? 'OTONOM AKTIF' : 'OFFLINE' }}</span>
                                </div>
                            </div>
                            <div class="bg-blue-500/10 px-3 py-1.5 rounded-lg border border-blue-500/20 text-blue-400 text-[9px] font-black uppercase tracking-widest">
                                Guard v7
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-card {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.glass-card:hover {
    background: rgba(30, 41, 59, 0.6);
    border-color: rgba(99, 102, 241, 0.2);
    box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
