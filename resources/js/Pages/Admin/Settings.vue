<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    settings: Object,
});

const { success: toastSuccess } = useToast();

const form = useForm({
    ad_banner_728x90: props.settings.ad_banner_728x90 || '',
    ad_social_bar: props.settings.ad_social_bar || '',
    ad_banner_468x60: props.settings.ad_banner_468x60 || '',
    ad_native_banner: props.settings.ad_native_banner || '',
    ad_banner_300x250: props.settings.ad_banner_300x250 || '',
    ad_smartlink: props.settings.ad_smartlink || '',
    ad_popunder: props.settings.ad_popunder || '',
    anti_adblock_enabled: props.settings.anti_adblock_enabled || '1',
    streamtape_login: props.settings.streamtape_login || '',
    streamtape_key: props.settings.streamtape_key || '',
    doodstream_login: props.settings.doodstream_login || '',
    doodstream_key: props.settings.doodstream_key || '',
    watermark_text: props.settings.watermark_text || '',
    ui_template: props.settings.ui_template || 'classic',
});

const showStreamtapeKey = ref(false);
const showDoodstreamKey = ref(false);

const submit = () => {
    // We create a copy of the data and encode script values.
    const encodedData = {};
    Object.keys(form.data()).forEach(key => {
        if (key.startsWith('ad_') && key !== 'ad_smartlink') {
            encodedData[key] = btoa(unescape(encodeURIComponent(form[key])));
        } else {
            encodedData[key] = form[key];
        }
    });

    form.transform((data) => ({
        ...data,
        ...encodedData,
        _is_encoded: true
    })).post(route('admin.settings.update'), {
        onSuccess: () => {
            toastSuccess('Pengaturan berhasil diperbarui.');
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Admin - Konfigurasi Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Konfigurasi <span class="text-indigo-500">Sistem</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Manajemen Inti & Monetisasi</p>
                </div>
                <div class="bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20">
                    <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">Hub Kendali Utama</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- UI Template Engine -->
                    <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden group">
                        <div class="absolute -top-12 -right-12 w-48 h-48 bg-cyan-500/5 rounded-full blur-[60px]"></div>
                        <h3 class="text-base font-black text-white mb-1 flex items-center gap-3 uppercase tracking-tight">
                            <span class="w-1 h-5 bg-cyan-500 rounded-full"></span>
                            Mesin Templat Antarmuka
                        </h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-6">Pilih gaya tampilan untuk pengunjung</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Classic Template -->
                            <button 
                                type="button" 
                                @click="form.ui_template = 'classic'"
                                :class="form.ui_template === 'classic' ? 'border-indigo-500 bg-indigo-500/10 ring-1 ring-indigo-500/30' : 'border-white/5 bg-white/[0.02]'"
                                class="p-5 rounded-2xl border transition-all duration-300 relative overflow-hidden group/card text-left"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" :class="form.ui_template === 'classic' ? 'bg-indigo-500 text-white' : 'bg-white/5 text-slate-400'">🎨</div>
                                    <div class="flex-1">
                                        <div class="text-sm font-black text-white uppercase tracking-wider">Classic Royale</div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Desktop-First</div>
                                    </div>
                                    <div v-if="form.ui_template === 'classic'">
                                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(99,102,241,0.6)]"></div>
                                    </div>
                                </div>
                            </button>

                            <!-- SpartanKobs Template -->
                            <button 
                                type="button" 
                                @click="form.ui_template = 'spartankobs'"
                                :class="form.ui_template === 'spartankobs' ? 'border-cyan-500 bg-cyan-500/10 ring-1 ring-cyan-500/30' : 'border-white/5 bg-white/[0.02]'"
                                class="p-5 rounded-2xl border transition-all duration-300 relative overflow-hidden group/card text-left"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl" :class="form.ui_template === 'spartankobs' ? 'bg-cyan-500 text-white' : 'bg-white/5 text-slate-400'">📱</div>
                                    <div class="flex-1">
                                        <div class="text-sm font-black text-white uppercase tracking-wider">Mobile Spark</div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Mobile-First</div>
                                    </div>
                                    <div v-if="form.ui_template === 'spartankobs'">
                                        <div class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(6,182,212,0.6)]"></div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Host Integration -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Streamtape -->
                        <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden">
                            <h3 class="text-xs font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                                <span class="w-1 h-4 bg-emerald-500 rounded-full"></span>
                                Hosting: Streamtape
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="API Login" class="!text-[10px] !font-bold !uppercase !tracking-widest !mb-1.5" />
                                    <input type="text" v-model="form.streamtape_login" class="w-full bg-white/5 border-none text-emerald-400 font-mono text-xs rounded-xl focus:ring-1 focus:ring-emerald-500/50 transition-all px-3 py-2" placeholder="API Login ID" />
                                </div>
                                <div>
                                    <InputLabel value="API Key" class="!text-[10px] !font-bold !uppercase !tracking-widest !mb-1.5" />
                                    <div class="relative">
                                        <input :type="showStreamtapeKey ? 'text' : 'password'" v-model="form.streamtape_key" class="w-full bg-white/5 border-none text-emerald-400 font-mono text-xs rounded-xl focus:ring-1 focus:ring-emerald-500/50 transition-all px-3 py-2 pr-10" placeholder="API Key" />
                                        <button type="button" @click="showStreamtapeKey = !showStreamtapeKey" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:text-emerald-400 p-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Doodstream -->
                        <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden">
                            <h3 class="text-xs font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                                <span class="w-1 h-4 bg-blue-500 rounded-full"></span>
                                Hosting: Doodstream
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="API Key" class="!text-[10px] !font-bold !uppercase !tracking-widest !mb-1.5" />
                                    <div class="relative">
                                        <input :type="showDoodstreamKey ? 'text' : 'password'" v-model="form.doodstream_key" class="w-full bg-white/5 border-none text-blue-400 font-mono text-xs rounded-xl focus:ring-1 focus:ring-blue-500/50 transition-all px-3 py-2 pr-10" placeholder="Doodstream API Key" />
                                        <button type="button" @click="showDoodstreamKey = !showDoodstreamKey" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 p-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="opacity-50">
                                    <InputLabel value="Login Username" class="!text-[10px] !font-bold !uppercase !tracking-widest !mb-1.5" />
                                    <input type="text" v-model="form.doodstream_login" class="w-full bg-white/5 border-none text-blue-400/50 font-mono text-xs rounded-xl px-3 py-2 cursor-not-allowed" disabled placeholder="Optional" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Watermark -->
                    <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden">
                        <h3 class="text-xs font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                            <span class="w-1 h-4 bg-amber-500 rounded-full"></span>
                            Keamanan: Watermark Dinamis
                        </h3>
                        <div>
                            <InputLabel value="Watermark Text" class="!text-[10px] !font-bold !uppercase !tracking-widest !mb-1.5" />
                            <input type="text" v-model="form.watermark_text" class="w-full bg-white/5 border-none text-amber-400 font-bold text-xs rounded-xl focus:ring-1 focus:ring-amber-500/50 transition-all px-3 py-2" placeholder="e.g. VIDEYVIEW OFFICIAL" />
                        </div>
                    </div>

                    <!-- Monetization -->
                    <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8 pb-4 border-b border-white/5">
                            <h3 class="text-xs font-black text-white flex items-center gap-2 uppercase tracking-widest">
                                <span class="w-1 h-4 bg-indigo-500 rounded-full"></span>
                                Mesin Monetisasi
                            </h3>
                            <button 
                                type="button"
                                @click="form.anti_adblock_enabled = form.anti_adblock_enabled === '1' ? '0' : '1'"
                                :class="form.anti_adblock_enabled === '1' ? 'bg-indigo-500/20 text-indigo-400 ring-1 ring-indigo-500/30' : 'bg-white/5 text-slate-500'"
                                class="flex items-center gap-3 px-3 py-1.5 rounded-xl transition-all duration-300"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-widest">Anti-Adblock: {{ form.anti_adblock_enabled === '1' ? 'Aktif' : 'Off' }}</span>
                                <div class="w-6 h-3 bg-black/30 rounded-full relative p-0.5">
                                    <div :class="form.anti_adblock_enabled === '1' ? 'translate-x-3 bg-indigo-500' : 'translate-x-0 bg-slate-600'" class="w-2 h-2 rounded-full transition-all duration-300"></div>
                                </div>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="space-y-2">
                                <InputLabel value="Social Bar Script" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_social_bar" class="w-full h-32 bg-white/5 border-none text-indigo-300 font-mono text-[10px] rounded-xl focus:ring-1 focus:ring-indigo-500/50 transition-all p-3" placeholder="Paste script..."></textarea>
                            </div>
                            <div class="space-y-2">
                                <InputLabel value="Popunder Script" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_popunder" class="w-full h-32 bg-white/5 border-none text-indigo-300 font-mono text-[10px] rounded-xl focus:ring-1 focus:ring-indigo-500/50 transition-all p-3" placeholder="Paste script..."></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <InputLabel value="Banner 728x90" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_728x90" class="w-full h-20 bg-white/5 border-none text-indigo-300 font-mono text-[9px] rounded-xl p-2.5"></textarea>
                            </div>
                            <div class="space-y-1.5">
                                <InputLabel value="Banner 300x250" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_300x250" class="w-full h-20 bg-white/5 border-none text-indigo-300 font-mono text-[9px] rounded-xl p-2.5"></textarea>
                            </div>
                            <div class="space-y-1.5">
                                <InputLabel value="Banner 468x60" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_468x60" class="w-full h-20 bg-white/5 border-none text-indigo-300 font-mono text-[9px] rounded-xl p-2.5"></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="space-y-1.5">
                                <InputLabel value="Native Banner" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_native_banner" class="w-full h-20 bg-white/5 border-none text-indigo-300 font-mono text-[9px] rounded-xl p-2.5"></textarea>
                            </div>
                            <div class="space-y-1.5">
                                <InputLabel value="Smartlink URL" class="!text-[10px] !font-bold !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_smartlink" class="w-full h-20 bg-white/5 border-none text-indigo-300 font-mono text-[9px] rounded-xl p-2.5" placeholder="https://..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 pb-12">
                        <PrimaryButton class="!px-10 !py-3 !rounded-xl !text-sm !font-bold !tracking-widest shadow-lg shadow-indigo-600/30" :disabled="form.processing">
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
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
