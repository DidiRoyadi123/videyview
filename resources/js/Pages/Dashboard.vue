<script setup>
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
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

// Materio-Inspired Chart Options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#3A3541',
                font: { size: 12, weight: '500' },
                usePointStyle: true,
                padding: 20
            }
        },
        tooltip: {
            backgroundColor: '#3A3541',
            titleColor: '#fff',
            bodyColor: '#fff',
            padding: 12,
            cornerRadius: 8
        }
    },
    scales: {
        y: {
            grid: { color: '#E0E0E0', drawBorder: false },
            ticks: { color: '#898591', font: { size: 11 } }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#898591', font: { size: 11 } }
        }
    }
};

const donutOptions = {
    ...chartOptions,
    scales: null,
    cutout: '75%'
};

// Purple Theme Colors (Materio)
const primaryColor = '#8C57FF';
const secondaryColor = '#A478FF';

// Mirror Distribution Data
const mirrorChartData = {
    labels: ['Berhasil', 'Menunggu', 'Gagal'],
    datasets: [{
        data: [props.stats.mirrorStats.success, props.stats.mirrorStats.pending, props.stats.mirrorStats.failed],
        backgroundColor: [
            '#56CA00', // Success Green
            '#16B1FF', // Info Blue
            '#FF4C51', // Error Red
        ],
        borderWidth: 0,
        hoverOffset: 4
    }]
};

// Health Library Data
const healthChartData = {
    labels: ['Sehat (ALIVE)', 'Bermasalah', 'Unknown'],
    datasets: [{
        label: 'Integritas Library',
        data: [props.stats.healthSummary.healthy, props.stats.healthSummary.down, props.stats.healthSummary.unknown || 0],
        backgroundColor: [
            primaryColor,
            '#FFB400', // Warning Orange
            '#898591'  // Grey
        ],
        borderRadius: 6,
        maxBarThickness: 32
    }]
};
</script>

<template>
    <Head title="Admin Dashboard - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Ringkasan Statistik</h2>
                    <p class="text-sm text-gray-500 mt-1">Pantau performa dan integritas VideyView secara real-time.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div :class="props.worker_active ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100'" class="px-4 py-1.5 rounded-full border flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span :class="props.worker_active ? 'bg-green-400' : 'bg-red-400'" class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                            <span :class="props.worker_active ? 'bg-green-500' : 'bg-red-500'" class="relative inline-flex rounded-full h-2 w-2"></span>
                        </span>
                        <span class="text-xs font-bold uppercase tracking-widest">{{ props.worker_active ? 'Worker Aktif' : 'Worker Offline' }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Top Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Videos -->
                <div class="materio-card p-5 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-[#8C57FF]/10 rounded-xl flex items-center justify-center text-[#8C57FF]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-[11px] font-bold text-green-500 bg-green-50 px-2 py-0.5 rounded-full">+12%</span>
                    </div>
                    <p class="text-sm font-medium text-gray-500">Total Video</p>
                    <h3 class="text-2xl font-bold text-[#3A3541] mt-1">{{ stats.total_videos.toLocaleString() }}</h3>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="h-1.5 flex-1 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#8C57FF] rounded-full" :style="{ width: (stats.total_local / stats.total_videos * 100) + '%' }"></div>
                        </div>
                        <span class="text-[11px] font-bold text-gray-400">{{ Math.round(stats.total_local / stats.total_videos * 100) }}%</span>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="materio-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-[#16B1FF]/10 rounded-xl flex items-center justify-center text-[#16B1FF]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1z" /></svg>
                        </div>
                        <span class="text-[11px] font-bold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">New</span>
                    </div>
                    <p class="text-sm font-medium text-gray-500">Total Pengguna</p>
                    <h3 class="text-2xl font-bold text-[#3A3541] mt-1">{{ stats.total_users.toLocaleString() }}</h3>
                    <p class="mt-4 text-[11px] font-semibold text-gray-400">{{ stats.total_premium_users }} VIP Priority Members</p>
                </div>

                <!-- Mirror Success -->
                <div class="materio-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-[#56CA00]/10 rounded-xl flex items-center justify-center text-[#56CA00]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-500">Berhasil Sinkron</p>
                    <h3 class="text-2xl font-bold text-[#3A3541] mt-1">{{ stats.total_mirror_success.toLocaleString() }}</h3>
                    <div class="mt-4 flex gap-3 text-[11px] font-bold uppercase tracking-tight">
                        <span class="text-[#8C57FF]">Tape: {{ stats.host_stats.streamtape }}</span>
                        <span class="text-[#16B1FF]">Dood: {{ stats.host_stats.doodstream }}</span>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="materio-card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-[#FFB400]/10 rounded-xl flex items-center justify-center text-[#FFB400]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-500">Total Views</p>
                    <h3 class="text-2xl font-bold text-[#3A3541] mt-1">{{ stats.total_views.toLocaleString() }}</h3>
                    <p class="mt-4 text-[11px] font-semibold text-gray-400">Peningkatan Trafik Global</p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Mirror Distribution -->
                <div class="materio-card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-[#3A3541]">Distribusi Sinkronisasi</h3>
                        <button class="text-gray-400 hover:text-[#8C57FF]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                        </button>
                    </div>
                    <div class="h-64 relative">
                        <Doughnut :data="mirrorChartData" :options="donutOptions" />
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-2xl font-bold text-[#3A3541]">{{ stats.mirrorStats.success }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase">Success</span>
                        </div>
                    </div>
                </div>

                <!-- Health Analysis -->
                <div class="materio-card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-[#3A3541]">Analisis Kesehatan Library</h3>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#8C57FF]"></span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase">Automated Scan</span>
                        </div>
                    </div>
                    <div class="h-64">
                        <Bar :data="healthChartData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Tables Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Videos -->
                <div class="lg:col-span-2 materio-card overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                        <h3 class="font-bold text-[#3A3541]">Upload Terkini</h3>
                        <Link :href="route('admin.videos.index')" class="text-[#8C57FF] text-xs font-bold hover:underline">Lihat Semua</Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 text-left">
                                    <th class="px-6 py-3 text-[11px] font-bold text-gray-400 uppercase">Video</th>
                                    <th class="px-6 py-3 text-[11px] font-bold text-gray-400 uppercase">Mirror Status</th>
                                    <th class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="video in stats.recent_videos" :key="video.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-sm text-[#3A3541] truncate max-w-[200px]">{{ video.title }}</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">ID: {{ video.slug }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-1.5">
                                            <span v-for="(status, host) in video.hosting_status" :key="host" 
                                                  class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border"
                                                  :class="status === 'success' ? 'bg-green-50 border-green-100 text-green-600' : 'bg-amber-50 border-amber-100 text-amber-600'">
                                                {{ host }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-gray-500 font-medium">
                                        {{ new Date(video.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top 10 Content -->
                <div class="materio-card p-6">
                    <h3 class="font-bold text-[#3A3541] mb-6">Konten Terpopuler</h3>
                    <div class="space-y-4">
                        <div v-for="(video, index) in stats.topVideos" :key="video.id" class="flex items-center gap-4 group">
                            <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center font-bold text-[#3A3541] group-hover:bg-[#8C57FF] group-hover:text-white transition-colors">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-[#3A3541] truncate group-hover:text-[#8C57FF] transition-colors">{{ video.title }}</h4>
                                <p class="text-xs text-gray-400 mt-0.5">{{ video.views.toLocaleString() }} Views</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>
