<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
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
    mirrorStats: Object,
    hostStats: Object,
    healthSummary: Object,
    topVideos: Array,
    totalVideos: Number,
});

// Chart.js Options for Transparency & Dark Theme
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
    scales: null // Donut doesn't need scales
};

// 1. Mirror Stats Data (Doughnut)
const mirrorChartData = {
    labels: ['Success', 'Pending', 'Failed'],
    datasets: [{
        data: [props.mirrorStats.success, props.mirrorStats.pending, props.mirrorStats.failed],
        backgroundColor: [
            'rgba(16, 185, 129, 0.8)', // Success (Emerald)
            'rgba(59, 130, 246, 0.8)', // Pending (Blue)
            'rgba(239, 68, 68, 0.8)',  // Failed (Red)
        ],
        hoverBackgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
        borderWidth: 0,
        borderRadius: 10,
        spacing: 5
    }]
};

// 2. Host Stats Data (Bar)
const hostChartData = {
    labels: ['Streamtape', 'Doodstream'],
    datasets: [{
        label: 'Success Mirrors',
        data: [props.hostStats.streamtape, props.hostStats.doodstream],
        backgroundColor: 'rgba(99, 102, 241, 0.8)', // Indigo
        hoverBackgroundColor: '#6366f1',
        borderRadius: 12,
        maxBarThickness: 40
    }]
};

// 3. Health Summary (Bar)
const healthChartData = {
    labels: ['Healthy (ALIVE)', 'Issues (HEAD/404)', 'Unknown'],
    datasets: [{
        label: 'Video Library Health',
        data: [props.healthSummary.healthy, props.healthSummary.down, props.healthSummary.unknown],
        backgroundColor: [
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)', // Amber
            'rgba(148, 163, 184, 0.5)'  // Slate
        ],
        borderRadius: 12,
        maxBarThickness: 40
    }]
};

</script>

<template>
    <Head title="Admin - Analitik Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-1 h-8 bg-indigo-600 rounded-full"></div>
                <h2 class="text-2xl font-black text-white italic uppercase tracking-tight">KONTROL <span class="text-indigo-600">INTELIJEN</span></h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Quick Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="glass shadow-royale p-6 rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-500/5 to-transparent">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Koleksi</div>
                        <div class="text-4xl font-black text-white italic">{{ totalVideos.toLocaleString() }}</div>
                        <div class="mt-2 text-[10px] text-indigo-400 font-bold uppercase">Video Terdaftar</div>
                    </div>
                    <div class="glass shadow-royale p-6 rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-500/5 to-transparent">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Mirror Berhasil</div>
                        <div class="text-4xl font-black text-emerald-400 italic">{{ mirrorStats.success.toLocaleString() }}</div>
                        <div class="mt-2 text-[10px] text-emerald-500 font-bold uppercase">{{ Math.round((mirrorStats.success / (totalVideos * 2 || 1)) * 100) }}% Efisiensi</div>
                    </div>
                    <div class="glass shadow-royale p-6 rounded-3xl border border-white/10 bg-gradient-to-br from-red-500/5 to-transparent">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Kesehatan Kritis</div>
                        <div class="text-4xl font-black text-red-400 italic">{{ healthSummary.down }}</div>
                        <div class="mt-2 text-[10px] text-red-500 font-bold uppercase">Butuh Atensi</div>
                    </div>
                    <div class="glass shadow-royale p-6 rounded-3xl border border-white/10 bg-gradient-to-br from-blue-500/5 to-transparent">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Distribusi Aktif</div>
                        <div class="text-4xl font-black text-blue-400 italic">{{ (hostStats.streamtape + hostStats.doodstream).toLocaleString() }}</div>
                        <div class="mt-2 text-[10px] text-blue-500 font-bold uppercase">Total Mirror Hidup</div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Mirror Distribution -->
                    <div class="glass-dark p-8 rounded-[2.5rem] border border-white/10 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest italic">Status Sinkronisasi Mirror</h3>
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                            </div>
                        </div>
                        <div class="h-72 relative">
                            <Doughnut :data="mirrorChartData" :options="donutOptions" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-black text-white italic">{{ mirrorStats.success }}</span>
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">LIVE</span>
                            </div>
                        </div>
                    </div>

                    <!-- Host Performance -->
                    <div class="glass-dark p-8 rounded-[2.5rem] border border-white/10 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest italic">Performa Host (Mirror OK)</h3>
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                        </div>
                        <div class="h-72">
                            <Bar :data="hostChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Health Summary -->
                    <div class="glass-dark p-8 rounded-[2.5rem] border border-white/10 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest italic">Integritas Library (Audit)</h3>
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20 text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04c0 4.833 1.807 9.242 4.798 12.584a11.11 11.11 0 0011.64 0c2.991-3.342 4.798-7.751 4.798-12.584z" /></svg>
                            </div>
                        </div>
                        <div class="h-72">
                            <Bar :data="healthChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Top Content Table -->
                    <div class="glass-dark p-8 rounded-[2.5rem] border border-white/10 shadow-2xl space-y-6 overflow-hidden">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest italic">Top 10 Konten Terpanas</h3>
                            <div class="w-8 h-8 rounded-xl bg-orange-500/10 flex items-center justify-center border border-orange-500/20 text-orange-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.5-7 3 10 1 15 1 15z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.577 23.045a8.503 8.503 0 01-5.154-1.925 8.5 8.5 0 01-2.764-1.925l.013.014c2 0 3.5-3 5.5-3s3.5 3 5.5 3c0 .1-1 .2-1.1.2z" /></svg>
                            </div>
                        </div>
                        <div class="space-y-4 overflow-y-auto max-h-[300px] no-scrollbar pr-2">
                            <div v-for="(video, index) in topVideos" :key="video.id" class="flex items-center gap-4 p-4 bg-white/5 rounded-2xl border border-white/5 hover:bg-white/10 transition-all group">
                                <div class="w-8 h-8 rounded-lg bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center font-black text-indigo-400 italic text-xs group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[11px] font-black text-white uppercase tracking-tight truncate">{{ video.title }}</h4>
                                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">{{ video.slug }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-indigo-400 italic">{{ video.views.toLocaleString() }}</div>
                                    <div class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">PENAYANGAN</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
