<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
    worker_self_healing: props.settings.worker_self_healing || '0',
    ad_ab_testing_enabled: props.settings.ad_ab_testing_enabled || '0',
});

const showStreamtapeKey = ref(false);
const showDoodstreamKey = ref(false);

const submit = () => {
    const encodedData = {};
    Object.keys(form.data()).forEach(key => {
        if (key.startsWith('ad_') && key !== 'ad_smartlink') {
            try {
                encodedData[key] = btoa(unescape(encodeURIComponent(form[key])));
            } catch (e) {
                encodedData[key] = form[key];
            }
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
    <Head title="System Configuration - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Konfigurasi Sistem</h2>
                    <p class="text-sm text-gray-500 mt-1">Manajemen parameter inti, hosting, dan monetisasi.</p>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- UI Template Engine -->
                <div class="materio-card p-6 relative overflow-hidden group">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#8C57FF]/5 rounded-full blur-[60px]"></div>
                    <h3 class="text-lg font-bold text-[#3A3541] mb-1 flex items-center gap-3">
                        <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                        Interface Engine
                    </h3>
                    <p class="text-[11px] text-gray-400 uppercase tracking-widest font-bold mb-6">Visual Presentation Controls</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <button 
                            type="button" 
                            @click="form.ui_template = 'classic'"
                            :class="form.ui_template === 'classic' ? 'border-[#8C57FF] bg-[#8C57FF]/5 ring-1 ring-[#8C57FF]/20' : 'border-gray-100 bg-gray-50/50 hover:bg-gray-50'"
                            class="p-6 rounded-2xl border transition-all duration-300 text-left relative overflow-hidden group/card"
                        >
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl shadow-sm transition-transform group-hover/card:scale-110" :class="form.ui_template === 'classic' ? 'bg-[#8C57FF] text-white' : 'bg-white text-gray-400'">🎨</div>
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-[#3A3541] uppercase tracking-wider">Classic Royale</div>
                                    <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Desktop-First Aesthetic</div>
                                </div>
                                <div v-if="form.ui_template === 'classic'" class="w-2.5 h-2.5 bg-[#8C57FF] rounded-full animate-pulse shadow-sm shadow-[#8C57FF]/50"></div>
                            </div>
                        </button>

                        <button 
                            type="button" 
                            @click="form.ui_template = 'spartankobs'"
                            :class="form.ui_template === 'spartankobs' ? 'border-[#03C3EC] bg-[#03C3EC]/5 ring-1 ring-[#03C3EC]/20' : 'border-gray-100 bg-gray-50/50 hover:bg-gray-50'"
                            class="p-6 rounded-2xl border transition-all duration-300 text-left relative overflow-hidden group/card"
                        >
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl shadow-sm transition-transform group-hover/card:scale-110" :class="form.ui_template === 'spartankobs' ? 'bg-[#03C3EC] text-white' : 'bg-white text-gray-400'">📱</div>
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-[#3A3541] uppercase tracking-wider">Mobile Spark</div>
                                    <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Mobile-First Optimized</div>
                                </div>
                                <div v-if="form.ui_template === 'spartankobs'" class="w-2.5 h-2.5 bg-[#03C3EC] rounded-full animate-pulse shadow-sm shadow-[#03C3EC]/50"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Host Integration -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Streamtape -->
                    <div class="materio-card p-6 relative overflow-hidden">
                        <h3 class="text-[11px] font-bold text-gray-400 mb-6 flex items-center gap-2 uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            Hosting: Streamtape
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel value="API Login" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider !mb-2" />
                                <input type="text" v-model="form.streamtape_login" class="w-full bg-white border-gray-200 text-[#3A3541] font-mono text-xs rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all px-4 py-2.5" placeholder="Login ID" />
                            </div>
                            <div>
                                <InputLabel value="API Key" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider !mb-2" />
                                <div class="relative">
                                    <input :type="showStreamtapeKey ? 'text' : 'password'" v-model="form.streamtape_key" class="w-full bg-white border-gray-200 text-[#3A3541] font-mono text-xs rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all px-4 py-2.5 pr-12" placeholder="Key" />
                                    <button type="button" @click="showStreamtapeKey = !showStreamtapeKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-green-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doodstream -->
                    <div class="materio-card p-6 relative overflow-hidden">
                        <h3 class="text-[11px] font-bold text-gray-400 mb-6 flex items-center gap-2 uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                            Hosting: Doodstream
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel value="API Key" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider !mb-2" />
                                <div class="relative">
                                    <input :type="showDoodstreamKey ? 'text' : 'password'" v-model="form.doodstream_key" class="w-full bg-white border-gray-200 text-[#3A3541] font-mono text-xs rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all px-4 py-2.5 pr-12" placeholder="API Key" />
                                    <button type="button" @click="showDoodstreamKey = !showDoodstreamKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="opacity-50">
                                <InputLabel value="Login Username" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider !mb-2" />
                                <input type="text" v-model="form.doodstream_login" class="w-full bg-gray-100 border-gray-200 text-gray-400 font-mono text-xs rounded-xl px-4 py-2.5 cursor-not-allowed" disabled placeholder="Optional" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Worker Engine Control -->
                <div class="materio-card p-6 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-green-500/5 rounded-full blur-[60px]"></div>
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-lg font-bold text-[#3A3541] mb-1 flex items-center gap-3">
                                <span class="w-1 h-6 bg-green-500 rounded-full"></span>
                                Robot Mandor Engine
                            </h3>
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Self-Healing & Auto-Recovery Logistics</p>
                        </div>
                        <button 
                            type="button"
                            @click="form.worker_self_healing = form.worker_self_healing === '1' ? '0' : '1'"
                            class="flex items-center gap-3 px-6 py-2.5 rounded-2xl transition-all duration-300 border"
                            :class="form.worker_self_healing === '1' ? 'bg-green-500/5 text-green-600 border-green-500/20 shadow-sm' : 'bg-gray-50 text-gray-400 border-gray-100'"
                        >
                            <span class="text-xs font-black uppercase tracking-widest">Auto-Healing: {{ form.worker_self_healing === '1' ? 'ON' : 'OFF' }}</span>
                            <div class="w-10 h-5 bg-gray-200 rounded-full relative transition-colors duration-300" :class="form.worker_self_healing === '1' ? 'bg-green-500/40' : ''">
                                <div :class="form.worker_self_healing === '1' ? 'translate-x-5 bg-green-600' : 'translate-x-0 bg-gray-400'" class="w-5 h-5 rounded-full transition-all duration-300 shadow-md"></div>
                            </div>
                        </button>
                    </div>
                    <div v-if="form.worker_self_healing === '1'" class="p-4 bg-green-50 rounded-2xl border border-green-100 flex items-start gap-4">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-xl shadow-sm">🤖</div>
                        <div>
                            <div class="text-xs font-bold text-green-800 uppercase tracking-tight">Active Pulse Monitoring</div>
                            <p class="text-[10px] text-green-600 mt-1 font-medium leading-relaxed">Sistem akan secara otomatis mendeteksi jika worker `app:video-worker` berhenti dan melakukan restart paksa setiap menit.</p>
                            <div v-if="settings.last_worker_restart" class="text-[9px] text-green-500 mt-2 font-bold uppercase tracking-widest">Last Restart Event: {{ settings.last_worker_restart }}</div>
                        </div>
                    </div>
                </div>

                <!-- Watermark -->
                <div class="materio-card p-6">
                    <h3 class="text-[11px] font-bold text-gray-400 mb-6 flex items-center gap-2 uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                        Security: Dynamic Watermark
                    </h3>
                    <div>
                        <InputLabel value="Watermark Text" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider !mb-2" />
                        <input type="text" v-model="form.watermark_text" class="w-full bg-white border-gray-200 text-[#3A3541] font-bold text-xs rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all px-4 py-2.5 uppercase" placeholder="e.g. VIDEYVIEW OFFICIAL" />
                    </div>
                </div>

                <!-- Monetization -->
                <div class="materio-card p-6">
                    <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-50 flex-wrap gap-4">
                        <h3 class="text-[11px] font-bold text-gray-400 flex items-center gap-2 uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 bg-[#8C57FF] rounded-full"></span>
                            Monetization Engine
                        </h3>
                        <div class="flex items-center gap-4">
                            <!-- A/B Testing Toggle -->
                            <button 
                                type="button"
                                @click="form.ad_ab_testing_enabled = form.ad_ab_testing_enabled === '1' ? '0' : '1'"
                                class="flex items-center gap-3 px-4 py-1.5 rounded-xl transition-all duration-300 border"
                                :class="form.ad_ab_testing_enabled === '1' ? 'bg-amber-500/5 text-amber-600 border-amber-500/20' : 'bg-gray-50 text-gray-400 border-gray-100'"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider">A/B Testing: {{ form.ad_ab_testing_enabled === '1' ? 'Active' : 'Static' }}</span>
                                <div class="w-8 h-4 bg-gray-200 rounded-full relative transition-colors duration-300" :class="form.ad_ab_testing_enabled === '1' ? 'bg-amber-500/40' : ''">
                                    <div :class="form.ad_ab_testing_enabled === '1' ? 'translate-x-4 bg-amber-600' : 'translate-x-0 bg-gray-400'" class="w-4 h-4 rounded-full transition-all duration-300 shadow-sm"></div>
                                </div>
                            </button>

                            <button 
                                type="button"
                                @click="form.anti_adblock_enabled = form.anti_adblock_enabled === '1' ? '0' : '1'"
                                class="flex items-center gap-3 px-4 py-1.5 rounded-xl transition-all duration-300 border"
                                :class="form.anti_adblock_enabled === '1' ? 'bg-[#8C57FF]/5 text-[#8C57FF] border-[#8C57FF]/20' : 'bg-gray-50 text-gray-400 border-gray-100'"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider">Anti-Adblock: {{ form.anti_adblock_enabled === '1' ? 'Enabled' : 'Disabled' }}</span>
                                <div class="w-8 h-4 bg-gray-200 rounded-full relative transition-colors duration-300" :class="form.anti_adblock_enabled === '1' ? 'bg-[#8C57FF]/40' : ''">
                                    <div :class="form.anti_adblock_enabled === '1' ? 'translate-x-4 bg-[#8C57FF]' : 'translate-x-0 bg-gray-400'" class="w-4 h-4 rounded-full transition-all duration-300 shadow-sm"></div>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-2">
                            <InputLabel value="Social Bar Script" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_social_bar" class="w-full h-32 bg-white border-gray-200 text-[#3A3541] font-mono text-[10px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-4" placeholder="Paste script..."></textarea>
                        </div>
                        <div class="space-y-2">
                            <InputLabel value="Popunder Script" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_popunder" class="w-full h-32 bg-white border-gray-200 text-[#3A3541] font-mono text-[10px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-4" placeholder="Paste script..."></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <InputLabel value="Banner 728x90" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_banner_728x90" class="w-full h-24 bg-white border-gray-200 text-[#3A3541] font-mono text-[9px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-3"></textarea>
                        </div>
                        <div class="space-y-2">
                            <InputLabel value="Banner 300x250" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_banner_300x250" class="w-full h-24 bg-white border-gray-200 text-[#3A3541] font-mono text-[9px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-3"></textarea>
                        </div>
                        <div class="space-y-2">
                            <InputLabel value="Banner 468x60" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_banner_468x60" class="w-full h-24 bg-white border-gray-200 text-[#3A3541] font-mono text-[9px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-3"></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div class="space-y-2">
                            <InputLabel value="Native Banner" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_native_banner" class="w-full h-24 bg-white border-gray-200 text-[#3A3541] font-mono text-[9px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-3"></textarea>
                        </div>
                        <div class="space-y-2">
                            <InputLabel value="Smartlink URL" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                            <textarea v-model="form.ad_smartlink" class="w-full h-24 bg-white border-gray-200 text-[#3A3541] font-mono text-[9px] rounded-xl focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all p-3" placeholder="https://..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button class="px-10 py-3 bg-[#8C57FF] text-white font-bold rounded-xl hover:bg-[#7B47E6] transition shadow-lg shadow-[#8C57FF]/30 uppercase text-sm tracking-widest disabled:opacity-50" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Commit Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </MaterioLayout>
</template>
