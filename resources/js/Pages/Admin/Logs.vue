<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToast } from '@/Composables/useToast';
import { ref } from 'vue';

const props = defineProps({
    logs: Array,
    crudLogs: {
        type: Array,
        default: () => []
    }
});

const { success: toastSuccess, error: toastError } = useToast();

const activeTab = ref('diagnostic');

const clearLogs = () => {
    if (confirm('Apakah Anda yakin ingin menghapus semua log sistem? Tindakan ini tidak dapat dibatalkan.')) {
        router.post(route('admin.logs.clear'), {}, {
            onSuccess: () => toastSuccess('Log Sistem berhasil dihapus.')
        });
    }
};

const undoAction = (id) => {
    if (confirm('Yakin ingin membatalkan (Undo) aksi ini?')) {
        router.post(route('admin.crud-activity.undo', id), {}, {
            preserveScroll: true
        });
    }
};

const redoAction = (id) => {
    if (confirm('Yakin ingin memulihkan (Redo) aksi ini?')) {
        router.post(route('admin.crud-activity.redo', id), {}, {
            preserveScroll: true
        });
    }
};

const getLogLevelClass = (log) => {
    if (log.includes('.ERROR:')) return 'text-red-400 bg-red-500/10 border-red-500/20';
    if (log.includes('.WARNING:')) return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    if (log.includes('.INFO:')) return 'text-blue-400 bg-blue-500/10 border-blue-500/20';
    return 'text-slate-400 bg-slate-500/5 border-white/5';
};

const formatEvent = (event) => {
    const map = {
        'created': 'Dibuat',
        'updated': 'Diperbarui',
        'deleted': 'Dihapus'
    };
    return map[event] || event;
};

const getEventClass = (event) => {
    if (event === 'deleted') return 'bg-red-500/10 text-red-400 border-red-500/20';
    if (event === 'created') return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    if (event === 'updated') return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
    return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head title="Admin - Log Sistem (Diagnostic & CRUD)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Log & <span class="text-indigo-500">Aktivitas</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Pemantauan Integritas Arsitektur & Perilaku Konteks</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Tab Headers -->
                <div class="flex gap-2 p-1.5 bg-slate-900/50 rounded-2xl w-max border border-white/5 shadow-inner">
                    <button 
                        @click="activeTab = 'diagnostic'"
                        class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all"
                        :class="activeTab === 'diagnostic' ? 'bg-indigo-500 text-white shadow-[0_0_20px_rgba(99,102,241,0.3)] shadow-indigo-500/20 scale-100' : 'text-slate-500 hover:text-white hover:bg-white/5 scale-95'"
                    >
                        Diagnostik Sistem
                    </button>
                    <button 
                        @click="activeTab = 'crud'"
                        class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all flex items-center gap-2"
                        :class="activeTab === 'crud' ? 'bg-indigo-500 text-white shadow-[0_0_20px_rgba(99,102,241,0.3)] shadow-indigo-500/20 scale-100' : 'text-slate-500 hover:text-white hover:bg-white/5 scale-95'"
                    >
                        Riwayat CRUD
                        <span v-if="crudLogs.length" class="px-1.5 py-0.5 rounded border text-[9px] leading-none" :class="activeTab === 'crud' ? 'bg-white/20 border-white/30 text-white' : 'bg-slate-800 border-slate-700 text-slate-400'">{{ crudLogs.length }}</span>
                    </button>
                </div>

                <!-- Tab 1: Diagnostic Logs -->
                <div v-show="activeTab === 'diagnostic'" class="glass-dark p-1 rounded-2xl border border-white/10 overflow-hidden shadow-xl animate-in fade-in duration-300 relative">
                    <div class="absolute top-4 right-4 z-10">
                        <PrimaryButton @click="clearLogs" class="bg-red-500 hover:bg-red-600 !px-4 !py-2 !text-[10px] !rounded-xl shadow-lg shadow-red-500/20 active:scale-95 transition-all font-bold tracking-widest uppercase">
                            Bersihkan Log Sistem
                        </PrimaryButton>
                    </div>

                    <div class="bg-slate-900/50 p-4 sm:p-5 rounded-2xl pt-16 sm:pt-5">
                        <div v-if="logs.length > 0" class="space-y-2 font-mono text-xs sm:text-sm">
                            <div v-for="(log, index) in logs" :key="index" 
                                 class="p-3 rounded-xl border transition-all hover:bg-white/[0.07] group"
                                 :class="getLogLevelClass(log)"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="text-slate-700 shrink-0 select-none group-hover:text-indigo-400 transition-colors font-bold text-xs tabular-nums w-8 text-right">{{ logs.length - index }}</span>
                                    <span class="break-all leading-relaxed whitespace-pre-wrap font-medium">{{ log }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-16 text-center">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">📭</div>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tight">Cakrawala Bersih</h3>
                            <p class="text-slate-500 mt-2 text-sm font-semibold uppercase tracking-widest">Tidak ada log sistem terdeteksi</p>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: CRUD Activity Logs (Timeline Mode) -->
                <div v-show="activeTab === 'crud'" class="space-y-8 animate-in slide-in-from-bottom-8 duration-700">
                    <div class="px-5 py-4 flex items-center justify-between">
                         <div>
                            <h3 class="font-black text-white uppercase tracking-[0.2em] text-sm italic">Opsi Lini Masa Aktivitas</h3>
                            <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-1">Audit Jejak Digital & Integritas Konfigurasi</p>
                         </div>
                         <div class="text-[10px] font-black text-indigo-400 uppercase tracking-widest px-4 py-2 bg-indigo-500/5 rounded-2xl border border-indigo-500/10 shadow-lg">Live Audit Active</div>
                    </div>

                    <div class="relative pl-12 sm:pl-20 pb-12">
                        <!-- Vertical Line -->
                        <div class="absolute left-[23px] sm:left-[39px] top-0 bottom-0 w-px bg-gradient-to-b from-indigo-500/50 via-slate-800 to-transparent"></div>

                        <div v-if="crudLogs.length > 0" class="space-y-12">
                            <div v-for="log in crudLogs" :key="log.id" class="relative group">
                                <!-- Dot Anchor -->
                                <div :class="[
                                    'absolute -left-[54px] sm:-left-[86px] w-12 h-12 rounded-2xl border-4 border-slate-950 flex items-center justify-center text-xs shadow-2xl transition-all duration-500 group-hover:scale-125 z-10',
                                    log.event === 'deleted' ? 'bg-red-500 text-white shadow-red-500/20' : 
                                    (log.event === 'created' ? 'bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-blue-500 text-white shadow-blue-500/20')
                                ]">
                                    <span v-if="log.event === 'deleted'">🗑</span>
                                    <span v-else-if="log.event === 'created'">➕</span>
                                    <span v-else>📝</span>
                                </div>

                                <!-- Content Card -->
                                <div class="glass-dark p-6 sm:p-8 rounded-[32px] border border-white/5 shadow-royale group-hover:border-indigo-500/30 transition-all duration-500 relative overflow-hidden">
                                     <div v-if="log.undone_at" class="absolute inset-0 bg-yellow-500/5 backdrop-blur-[2px] z-0"></div>
                                     
                                     <div class="relative z-10">
                                         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                             <div class="flex items-center gap-4">
                                                 <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-sm shadow-inner group-hover:bg-indigo-500/10 transition-colors">👤</div>
                                                 <div>
                                                     <div class="text-[rgb(var(--text-main))] font-black uppercase tracking-tight text-sm">{{ log.user?.name || 'Sistem Otonom' }}</div>
                                                     <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
                                                 </div>
                                             </div>
                                             
                                             <div class="flex gap-2">
                                                 <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] border shadow-lg" :class="getEventClass(log.event)">
                                                     {{ formatEvent(log.event) }}
                                                 </span>
                                                 <span v-if="log.undone_at" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 shadow-lg">🏛 UNDONE</span>
                                             </div>
                                         </div>

                                         <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                                             <div class="space-y-4">
                                                 <div class="flex flex-col gap-1.5">
                                                     <span class="text-[9px] text-slate-500 font-black uppercase tracking-[0.3em]">Target Arsitektur</span>
                                                     <div class="font-mono text-xs text-indigo-300 break-all bg-indigo-500/5 px-4 py-2.5 rounded-2xl border border-indigo-500/10 w-fit">{{ log.subject_type }}</div>
                                                 </div>
                                                 <div class="flex items-center gap-2">
                                                     <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest bg-white/5 px-3 py-1 rounded-lg">ID : {{ log.subject_id }}</span>
                                                 </div>
                                             </div>

                                             <div class="flex justify-end gap-3">
                                                 <button 
                                                     v-if="!log.undone_at"
                                                     @click="undoAction(log.id)"
                                                     class="h-12 px-8 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white text-[10px] font-black uppercase tracking-[0.2em] border border-red-500/20 rounded-2xl transition-all active:scale-95 shadow-lg hover:shadow-red-500/30"
                                                 >
                                                     BATALKAN (UNDO)
                                                 </button>
                                                 
                                                 <button 
                                                     v-if="log.undone_at"
                                                     @click="redoAction(log.id)"
                                                     class="h-12 px-8 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white text-[10px] font-black uppercase tracking-[0.2em] border border-emerald-500/20 rounded-2xl transition-all active:scale-95 shadow-lg hover:shadow-emerald-500/30"
                                                 >
                                                     PULIHKAN (REDO)
                                                 </button>
                                             </div>
                                         </div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-24 text-center glass-dark rounded-[40px] border border-white/5">
                            <div class="text-6xl mb-6 grayscale opacity-30">⏳</div>
                            <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter">Lini Masa Hampa</h3>
                            <p class="text-slate-500 mt-2 text-sm font-semibold uppercase tracking-[0.3em]">Menunggu Jejak Digital Baru...</p>
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
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
