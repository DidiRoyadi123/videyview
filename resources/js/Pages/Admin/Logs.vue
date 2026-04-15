<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    logs: Array
});

const { success: toastSuccess } = useToast();

const clearLogs = () => {
    if (confirm('Apakah Anda yakin ingin menghapus semua log sistem? Tindakan ini tidak dapat dibatalkan.')) {
        router.post(route('admin.logs.clear'), {}, {
            onSuccess: () => toastSuccess('Log berhasil dihapus.')
        });
    }
};

const getLogLevelClass = (log) => {
    if (log.includes('.ERROR:')) return 'text-red-400 bg-red-500/10 border-red-500/20';
    if (log.includes('.WARNING:')) return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
    if (log.includes('.INFO:')) return 'text-blue-400 bg-blue-500/10 border-blue-500/20';
    return 'text-slate-400 bg-slate-500/5 border-white/5';
};
</script>

<template>
    <Head title="Admin - Log Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Diagnostik <span class="text-indigo-500">Sistem</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Pemantauan Integritas Arsitektur</p>
                </div>
                <div class="flex items-center gap-3">
                    <PrimaryButton @click="clearLogs" class="bg-red-500 hover:bg-red-600 !px-4 !py-2 !text-xs !rounded-xl shadow-lg shadow-red-500/20 active:scale-95 transition-all font-bold tracking-widest uppercase">
                        Bersihkan Log
                    </PrimaryButton>
                    <div class="bg-indigo-500/10 px-3 py-1.5 rounded-xl border border-indigo-500/20">
                        <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">Live Feed</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="glass-dark p-1 rounded-2xl border border-white/10 overflow-hidden shadow-xl">
                    <div class="bg-slate-900/50 p-4 sm:p-5 rounded-2xl">
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
                            <div class="mt-4 px-4 py-2 bg-emerald-500/10 text-emerald-400 rounded-full border border-emerald-500/20 text-[10px] font-bold uppercase tracking-widest inline-block">Segalanya berjalan sesuai protokol</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 flex justify-center">
                    <div class="px-4 py-2 bg-white/5 rounded-full border border-white/5">
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Menampilkan 100 entri terbaru • Pemeriksaan Kesehatan Otonom Aktif</p>
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
}
</style>
