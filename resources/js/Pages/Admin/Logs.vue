<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
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
    if (log.includes('.ERROR:')) return 'text-red-500 bg-red-50 border-red-100';
    if (log.includes('.WARNING:')) return 'text-amber-600 bg-amber-50 border-amber-100';
    if (log.includes('.INFO:')) return 'text-blue-500 bg-blue-50 border-blue-100';
    return 'text-gray-500 bg-gray-50 border-gray-100';
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
    if (event === 'deleted') return 'bg-red-50 text-red-500 border-red-100';
    if (event === 'created') return 'bg-green-50 text-green-600 border-green-100';
    if (event === 'updated') return 'bg-blue-50 text-blue-500 border-blue-100';
    return 'bg-gray-50 text-gray-500 border-gray-100';
};
</script>

<template>
    <Head title="System Logs & Activity - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Log & Aktivitas</h2>
                    <p class="text-sm text-gray-500 mt-1">Audit jejak digital dan diagnostik sistem.</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Headers -->
            <div class="flex gap-2 p-1 bg-gray-100/50 rounded-xl w-max border border-gray-200 shadow-inner">
                <button 
                    @click="activeTab = 'diagnostic'"
                    class="px-6 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all"
                    :class="activeTab === 'diagnostic' ? 'bg-white text-[#8C57FF] shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                >
                    Diagnostic Logs
                </button>
                <button 
                    @click="activeTab = 'crud'"
                    class="px-6 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2"
                    :class="activeTab === 'crud' ? 'bg-white text-[#8C57FF] shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                >
                    CRUD History
                    <span v-if="crudLogs.length" class="px-1.5 py-0.5 rounded-full text-[9px] bg-gray-100 text-gray-500">{{ crudLogs.length }}</span>
                </button>
            </div>

            <!-- Diagnostic Logs Tab -->
            <div v-show="activeTab === 'diagnostic'" class="materio-card overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="font-bold text-[#3A3541]">System Diagnostic</h3>
                    <button @click="clearLogs" class="text-red-500 text-[10px] font-bold uppercase tracking-widest bg-red-50 px-4 py-1.5 rounded-lg border border-red-100 hover:bg-red-500 hover:text-white transition-all">
                        Clear System Logs
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="logs.length > 0" class="space-y-3 font-mono">
                        <div v-for="(log, index) in logs" :key="index" 
                             class="p-4 rounded-xl border text-[11px] leading-relaxed transition-all hover:shadow-sm"
                             :class="getLogLevelClass(log)"
                        >
                            <div class="flex items-start gap-4">
                                <span class="text-gray-300 shrink-0 font-bold w-10 text-right">{{ logs.length - index }}</span>
                                <span class="break-all whitespace-pre-wrap">{{ log }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-20 text-center">
                        <div class="text-4xl mb-4">✨</div>
                        <h3 class="text-lg font-bold text-[#3A3541]">All Clear</h3>
                        <p class="text-sm text-gray-400 mt-1">No system logs detected at the moment.</p>
                    </div>
                </div>
            </div>

            <!-- CRUD History Tab -->
            <div v-show="activeTab === 'crud'" class="space-y-6">
                <div class="px-4 flex items-center justify-between">
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Digital Audit Timeline</h3>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold text-green-600 uppercase">Live Audit Active</span>
                    </div>
                </div>

                <div class="relative pl-12 md:pl-24 pb-12">
                    <!-- Timeline Line -->
                    <div class="absolute left-[31px] md:left-[43px] top-0 bottom-0 w-1 bg-gray-100 rounded-full"></div>

                    <div v-if="crudLogs.length > 0" class="space-y-12">
                        <div v-for="log in crudLogs" :key="log.id" class="relative group">
                            <!-- Timeline Icon -->
                            <div :class="[
                                'absolute -left-[54px] md:-left-[78px] w-12 h-12 rounded-2xl border-4 border-white bg-white flex items-center justify-center text-xl shadow-md transition-transform group-hover:scale-110 z-10',
                                log.event === 'deleted' ? 'text-red-500' : 
                                (log.event === 'created' ? 'text-green-500' : 'text-blue-500')
                            ]">
                                <span v-if="log.event === 'deleted'"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>
                                <span v-else-if="log.event === 'created'"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></span>
                                <span v-else><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.242 3.758a2.121 2.121 0 013.03 3.03L9 17 4 17 4 12l10.242-10.242z"/></svg></span>
                            </div>

                            <!-- Activity Card -->
                            <div class="materio-card p-6 md:p-8 relative overflow-hidden group-hover:border-[#8C57FF]/30 transition-all duration-300">
                                 <div v-if="log.undone_at" class="absolute inset-0 bg-amber-50/50 backdrop-blur-[1px] z-10 flex items-center justify-center pointer-events-none">
                                     <span class="text-3xl font-black text-amber-500/20 rotate-12 uppercase tracking-[0.5em]">Undone</span>
                                 </div>
                                 
                                 <div class="relative z-0">
                                     <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                                         <div class="flex items-center gap-4">
                                             <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-lg shadow-inner group-hover:bg-[#8C57FF]/10 transition-colors">👤</div>
                                             <div>
                                                 <div class="text-sm font-bold text-[#3A3541] uppercase tracking-tight">{{ log.user?.name || 'System Auto' }}</div>
                                                 <div class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
                                             </div>
                                         </div>
                                         
                                         <div class="flex gap-2">
                                             <span class="px-4 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border shadow-sm" :class="getEventClass(log.event)">
                                                 {{ formatEvent(log.event) }}
                                             </span>
                                             <span v-if="log.undone_at" class="px-4 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500 text-white shadow-sm shadow-amber-500/20">REVERSED</span>
                                         </div>
                                     </div>

                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                                         <div class="space-y-4">
                                             <div class="flex flex-col gap-1.5">
                                                 <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Architectural Target</span>
                                                 <div class="font-mono text-[11px] text-[#8C57FF] bg-[#8C57FF]/5 px-4 py-2 rounded-lg border border-[#8C57FF]/10 w-fit">{{ log.subject_type }}</div>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider bg-gray-100 px-3 py-1 rounded-lg">Instance ID : {{ log.subject_id }}</span>
                                             </div>
                                         </div>

                                         <div class="flex justify-end gap-3 relative z-20">
                                             <button 
                                                 v-if="!log.undone_at"
                                                 @click="undoAction(log.id)"
                                                 class="px-6 py-2 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white text-[10px] font-bold uppercase tracking-widest border border-red-100 rounded-lg transition-all active:scale-95 shadow-sm"
                                             >
                                                 Undo Action
                                             </button>
                                             
                                             <button 
                                                 v-if="log.undone_at"
                                                 @click="redoAction(log.id)"
                                                 class="px-6 py-2 bg-green-50 text-green-600 hover:bg-green-600 hover:text-white text-[10px] font-bold uppercase tracking-widest border border-green-100 rounded-lg transition-all active:scale-95 shadow-sm"
                                             >
                                                 Redo Action
                                             </button>
                                         </div>
                                     </div>
                                 </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-24 text-center materio-card border-dashed">
                        <div class="text-5xl mb-6 opacity-30">⏳</div>
                        <h3 class="text-xl font-bold text-[#3A3541] italic">Timeline Empty</h3>
                        <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest">Waiting for digital footprints...</p>
                    </div>
                </div>
            </div>

        </div>
    </MaterioLayout>
</template>
