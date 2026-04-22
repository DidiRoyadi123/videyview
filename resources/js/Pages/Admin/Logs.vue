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

                <!-- Tab 2: CRUD Activity Logs (Undo/Redo) -->
                <div v-show="activeTab === 'crud'" class="glass-dark rounded-2xl border border-white/10 overflow-hidden shadow-xl animate-in fade-in duration-300">
                    <div class="px-5 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <h3 class="font-black text-white uppercase tracking-widest text-sm">Log Modifikasi Data</h3>
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 py-1 bg-white/5 rounded-full border border-white/5">50 Riwayat Terakhir</div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left bg-slate-900/30">
                            <thead class="text-[10px] font-black uppercase text-slate-500 tracking-widest border-b border-white/10 bg-white/[0.02]">
                                <tr>
                                    <th class="py-4 px-5">Waktu & User</th>
                                    <th class="py-4 px-5">Model</th>
                                    <th class="py-4 px-5">Aksi</th>
                                    <th class="py-4 px-5">Status</th>
                                    <th class="py-4 px-5 text-right w-40">Tindakan Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-sm font-medium">
                                <tr v-for="log in crudLogs" :key="log.id" class="group hover:bg-white/[0.04] transition-all duration-200">
                                    <td class="py-4 px-5">
                                        <div class="text-white font-bold">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
                                        <div class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">{{ log.user?.name || 'Sistem Otonom' }}</div>
                                    </td>
                                    
                                    <td class="py-4 px-5">
                                        <div class="font-mono text-xs text-indigo-300 break-all bg-indigo-500/10 px-2 py-1 rounded inline-block">{{ log.subject_type }}</div>
                                        <div class="text-[10px] text-slate-500 mt-1 font-bold tracking-widest uppercase">ID Kunci: <span class="text-white">{{ log.subject_id }}</span></div>
                                    </td>
                                    
                                    <td class="py-4 px-5">
                                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border" :class="getEventClass(log.event)">
                                            {{ formatEvent(log.event) }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-5">
                                        <div v-if="log.undone_at" class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-lg text-xs font-bold">
                                            <span>↩</span> Dibatalkan (Undo)
                                        </div>
                                        <div v-else class="text-xs text-slate-500 font-bold tracking-wide">Aktif</div>
                                    </td>
                                    
                                    <td class="py-4 px-5 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button 
                                                v-if="!log.undone_at"
                                                @click="undoAction(log.id)"
                                                class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-[10px] font-black uppercase tracking-widest border border-red-500/20 rounded-lg transition-all active:scale-95"
                                            >
                                                UNDO
                                            </button>
                                            
                                            <button 
                                                v-if="log.undone_at"
                                                @click="redoAction(log.id)"
                                                class="px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 rounded-lg transition-all active:scale-95"
                                            >
                                                REDO
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="!crudLogs.length">
                                    <td colspan="5" class="py-16 text-center">
                                        <div class="text-3xl mb-3">🕒</div>
                                        <h3 class="text-lg font-black text-white uppercase italic tracking-tight">Tidak Ada Riwayat</h3>
                                        <p class="text-slate-500 mt-1 text-[10px] font-semibold uppercase tracking-widest">Aktivitas CRUD akan muncul di sini</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
