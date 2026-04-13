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
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter">Diagnostik Sistem</h2>
                <div class="flex items-center gap-4">
                    <PrimaryButton @click="clearLogs" class="bg-red-500 hover:bg-red-600 !px-8">
                        Bersihkan Log
                    </PrimaryButton>
                    <div class="bg-indigo-500/10 px-4 py-1 rounded-full border border-indigo-500/20">
                        <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">Umpan Langsung</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="glass-dark p-1 rounded-[2.5rem] border border-white/5 overflow-hidden">
                    <div class="bg-slate-900/50 p-8 rounded-[2.3rem]">
                        <div v-if="logs.length > 0" class="space-y-4 font-mono text-xs">
                            <div v-for="(log, index) in logs" :key="index" 
                                 class="p-4 rounded-xl border transition-all hover:bg-white/5 group"
                                 :class="getLogLevelClass(log)"
                            >
                                <div class="flex items-start gap-4">
                                    <span class="text-slate-600 shrink-0 select-none group-hover:text-slate-400 transition-colors">{{ logs.length - index }}</span>
                                    <span class="break-all leading-relaxed whitespace-pre-wrap">{{ log }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-20 text-center">
                            <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">📭</div>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-widest">Cakrawala Bersih</h3>
                            <p class="text-slate-500 mt-2">Tidak ada log sistem yang terdeteksi. Segalanya berjalan lancar.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-center">
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.3em]">Menampilkan 100 entri terakhir • Pemeriksaan Kesehatan Otonom Aktif</p>
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
