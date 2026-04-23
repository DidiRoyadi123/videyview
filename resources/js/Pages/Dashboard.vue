<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    worker_active: Boolean,
});

const getStatusColor = (status) => {
    if (!status) return 'text-slate-500';
    const s = JSON.stringify(status);
    if (s.includes('success')) return 'text-emerald-400';
    if (s.includes('uploading') || s.includes('pending')) return 'text-amber-400';
    return 'text-red-400';
};

const getMirrorCount = (status) => {
    if (!status) return 0;
    return Object.values(status).filter(val => val === 'success').length;
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
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Kontrol Logistik VideyView</p>
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

                <!-- Project Control Center -->
                <div class="mt-6">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="h-px flex-1 bg-white/5"></div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 px-3 text-center">Pusat Kontrol Proyek</h3>
                        <div class="h-px flex-1 bg-white/5"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Backup System -->
                        <div class="glass-card p-5 relative overflow-hidden group border-emerald-500/10 hover:border-emerald-500/40">
                            <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-500 opacity-5 blur-3xl group-hover:opacity-15 transition-opacity duration-700"></div>
                            <div class="relative z-10">
                                <span class="text-2xl mb-3 block">🛡️</span>
                                <h4 class="text-base font-black text-white italic uppercase tracking-tight">Penguncian Benteng</h4>
                                <p class="text-slate-400 text-xs mt-2 leading-relaxed max-w-sm">Mengamankan seluruh struktur database VideyView ke .sql secara instan.</p>
                                
                                <div class="mt-5 pt-4 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pemicu Manual Aktif</div>
                                    <Link 
                                        :href="route('admin.project.backup')" 
                                        method="post" 
                                        as="button" 
                                        class="w-full sm:w-auto px-5 py-2 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-300 active:scale-95"
                                    >
                                        Bangun Benteng
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Autonomy Status -->
                        <div class="glass-card p-5 relative overflow-hidden group border-blue-500/10 hover:border-blue-500/40">
                             <div class="absolute -right-6 -top-6 w-32 h-32 bg-blue-500 opacity-5 blur-3xl group-hover:opacity-15 transition-opacity duration-700"></div>
                             <div class="relative z-10">
                                 <span class="text-2xl mb-3 block">🤖</span>
                                 <h4 class="text-base font-black text-white italic uppercase tracking-tight">Robot Mandor Otonom</h4>
                                 <p class="text-slate-400 text-xs mt-2 leading-relaxed max-w-sm">Sistem cerdas yang mengelola background worker, download, dan distribusi video secara otonom.</p>
                                 
                                 <div class="mt-5 pt-4 border-t border-white/5 flex items-center gap-6">
                                     <div class="flex items-center gap-2">
                                         <span :class="props.worker_active ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                                         <span :class="props.worker_active ? 'text-emerald-400' : 'text-red-400'" class="text-[10px] font-bold uppercase tracking-widest">
                                             Worker {{ props.worker_active ? 'Active' : 'Offline' }}
                                         </span>
                                     </div>
                                     <div v-if="props.worker_active" class="flex items-center gap-2">
                                         <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                         <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Guard Active</span>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-6">
                    <!-- Recent Activity Table -->
                    <div class="lg:col-span-2 glass-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                            <h3 class="text-sm font-black uppercase tracking-widest text-white">Aktivitas Logistik Terkini</h3>
                            <Link :href="route('admin.videos.index')" class="text-indigo-400 text-[10px] font-bold uppercase hover:text-white transition-colors tracking-widest">Lihat Semua</Link>
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
                                            {{ new Date(video.created_at).toLocaleString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Command Panel -->
                    <div class="glass-card p-6 bg-gradient-to-br from-indigo-600/10 to-transparent">
                        <h3 class="text-sm font-black uppercase tracking-widest text-indigo-400 italic mb-6">Navigasi Cepat</h3>
                        <div class="space-y-4">
                            <Link :href="route('admin.analytics.index')" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition group">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-emerald-500/20 rounded-xl flex items-center justify-center text-emerald-400 group-hover:rotate-12 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-white">Intelijen & Analitik</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link :href="route('admin.videos.bulk-sync')" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition group">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400 group-hover:rotate-12 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-white">Sinkronisasi Massal Manual</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link :href="route('admin.videos.extractor')" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition group">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-500/20 rounded-xl flex items-center justify-center text-purple-400 group-hover:rotate-12 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-white">Ekstraktor Tautan Massal</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link :href="route('admin.settings.index')" class="flex items-center justify-between p-4 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition group">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-500/20 rounded-xl flex items-center justify-center text-slate-400 group-hover:rotate-12 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-white">Pengaturan Platform</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                        
                        <div class="mt-8 p-4 bg-indigo-500 rounded-2xl shadow-xl shadow-indigo-500/20 relative overflow-hidden group cursor-pointer">
                            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="relative z-10">
                                <div class="text-[10px] font-black uppercase text-indigo-100 tracking-widest mb-1 italic">Jangkauan Total Tontonan</div>
                                <div class="text-2xl font-black text-white tracking-tighter italic">KOMANDO UTAMA</div>
                                <p class="text-indigo-200 text-[10px] mt-2 leading-tight">Hub Premium VideyView berjalan pada kapasitas penuh.</p>
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
    border-radius: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
    background: rgba(30, 41, 59, 0.6);
    border-color: rgba(99, 102, 241, 0.2);
    box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}

.btn-premium {
    @apply bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl border border-indigo-400/20 shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 uppercase tracking-widest text-[10px] py-4;
}
</style>
