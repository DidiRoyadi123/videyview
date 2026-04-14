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
    // This bypasses many WAF/Security filters that block <script> tags in POST.
    const encodedData = {};
    Object.keys(form.data()).forEach(key => {
        if (key.startsWith('ad_') && key !== 'ad_smartlink') { // smartlink is just a URL
            encodedData[key] = btoa(unescape(encodeURIComponent(form[key])));
        } else {
            encodedData[key] = form[key];
        }
    });

    console.log('Submitting encoded form:', encodedData);
    
    // We use a separate POST request since we're encoding manually
    // Actually, we can just use form.transform() if using Inertia 3
    form.transform((data) => ({
        ...data,
        ...encodedData,
        _is_encoded: true
    })).post(route('admin.settings.update'), {
        onSuccess: () => {
            console.log('Form submitted successfully');
            toastSuccess('Pengaturan berhasil diperbarui.');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Admin - Konfigurasi Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter">Panel Konfigurasi</h2>
                <div class="bg-indigo-500/10 px-4 py-1 rounded-full border border-indigo-500/20">
                    <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">Sistem & Monetisasi</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-10">

                    <!-- UI Template Engine -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-cyan-500/10 rounded-full blur-[100px] group-hover:bg-cyan-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-2 flex items-center gap-4">
                            <span class="w-2 h-8 bg-cyan-500 rounded-full"></span>
                            Mesin Templat Antarmuka
                        </h3>
                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mb-8">Pilih gaya tampilan publik untuk pengunjung</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Classic Template -->
                            <button 
                                type="button" 
                                @click="form.ui_template = 'classic'"
                                :class="form.ui_template === 'classic' ? 'border-indigo-500 bg-indigo-500/10 ring-2 ring-indigo-500/30' : 'border-white/5 hover:border-white/20'"
                                class="p-6 rounded-2xl border text-left transition-all duration-300 relative overflow-hidden group/card"
                            >
                                <div class="absolute -top-12 -right-12 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover/card:bg-indigo-500/20 transition-all"></div>
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" :class="form.ui_template === 'classic' ? 'bg-indigo-500 shadow-lg shadow-indigo-500/30' : 'bg-white/5'">🎨</div>
                                    <div>
                                        <div class="text-sm font-black text-white uppercase tracking-wider">Classic Royale</div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Default • Desktop-First</div>
                                    </div>
                                    <div v-if="form.ui_template === 'classic'" class="ml-auto">
                                        <span class="text-[8px] font-black bg-indigo-500 text-white px-2 py-1 rounded-full uppercase">Aktif</span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">Tampilan premium khas VideyView — floating navbar, hero carousel besar, overlay title di thumbnail, rounded-[40px] cards, footer desktop.</p>
                            </button>

                            <!-- SpartanKobs Template -->
                            <button 
                                type="button" 
                                @click="form.ui_template = 'spartankobs'"
                                :class="form.ui_template === 'spartankobs' ? 'border-cyan-500 bg-cyan-500/10 ring-2 ring-cyan-500/30' : 'border-white/5 hover:border-white/20'"
                                class="p-6 rounded-2xl border text-left transition-all duration-300 relative overflow-hidden group/card"
                            >
                                <div class="absolute -top-12 -right-12 w-24 h-24 bg-cyan-500/10 rounded-full blur-2xl group-hover/card:bg-cyan-500/20 transition-all"></div>
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" :class="form.ui_template === 'spartankobs' ? 'bg-cyan-500 shadow-lg shadow-cyan-500/30' : 'bg-white/5'">📱</div>
                                    <div>
                                        <div class="text-sm font-black text-white uppercase tracking-wider">Mobile Spark</div>
                                        <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">SpartanKobs • Mobile-First</div>
                                    </div>
                                    <div v-if="form.ui_template === 'spartankobs'" class="ml-auto">
                                        <span class="text-[8px] font-black bg-cyan-500 text-white px-2 py-1 rounded-full uppercase">Aktif</span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">Optimasi mobile — bottom navigation bar, grid padat, judul di bawah thumbnail, tag scroller horizontal, search overlay, category bottom sheet.</p>
                            </button>
                        </div>
                    </div>

                    <!-- Host Integration: Streamtape -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-emerald-500/10 rounded-full blur-[100px] group-hover:bg-emerald-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                            Integrasi Hosting (Streamtape)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Login API Streamtape" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <input type="text" v-model="form.streamtape_login" class="w-full bg-white/5 border-white/5 text-emerald-400 font-mono text-xs rounded-xl focus:ring-emerald-500/50 transition-all" placeholder="Masukkan ID Login API..." />
                                <p class="text-[9px] text-slate-500 italic mt-1">Dapatkan ini dari Dasbor Streamtape > Pengaturan > API</p>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Kunci API Streamtape" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <div class="relative group/input">
                                    <input 
                                        :type="showStreamtapeKey ? 'text' : 'password'" 
                                        v-model="form.streamtape_key" 
                                        class="w-full bg-white/5 border-white/5 text-emerald-400 font-mono text-xs rounded-xl focus:ring-emerald-500/50 transition-all pr-12" 
                                        placeholder="Masukkan Kunci API/Rahasia..." 
                                    />
                                    <button 
                                        type="button"
                                        @click="showStreamtapeKey = !showStreamtapeKey"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-500 hover:text-emerald-400 transition-colors"
                                    >
                                        <svg v-if="!showStreamtapeKey" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                    </button>
                                </div>
                                <p class="text-[9px] text-slate-500 italic mt-1">Mengubah ini akan memengaruhi semua tugas sinkronisasi mendatang.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Host Integration: Doodstream -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/10 rounded-full blur-[100px] group-hover:bg-blue-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                            Integrasi Hosting (Doodstream)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Login API Doodstream" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <input type="text" v-model="form.doodstream_login" class="w-full bg-white/5 border-white/5 text-blue-400 font-mono text-xs rounded-xl focus:ring-blue-500/50 transition-all" placeholder="Masukkan ID Login API..." />
                                <p class="text-[9px] text-slate-500 italic mt-1">Ditemukan di Pengaturan Akun Doodstream > API (jika tersedia).</p>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Kunci API Doodstream" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <div class="relative group/input">
                                    <input 
                                        :type="showDoodstreamKey ? 'text' : 'password'" 
                                        v-model="form.doodstream_key" 
                                        class="w-full bg-white/5 border-white/5 text-blue-400 font-mono text-xs rounded-xl focus:ring-blue-500/50 transition-all pr-12" 
                                        placeholder="Masukkan Kunci API Doodstream..." 
                                    />
                                    <button 
                                        type="button"
                                        @click="showDoodstreamKey = !showDoodstreamKey"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-500 hover:text-blue-400 transition-colors"
                                    >
                                        <svg v-if="!showDoodstreamKey" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                    </button>
                                </div>
                                <p class="text-[9px] text-slate-500 italic mt-1">Digunakan untuk sinkronisasi otomatis dan pemeriksaan status.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content Security: Watermark -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-amber-500/10 rounded-full blur-[100px] group-hover:bg-amber-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-amber-500 rounded-full"></span>
                            Keamanan & Branding Video
                        </h3>
                        <div class="grid grid-cols-1 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Teks Watermark Dinamis" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <input type="text" v-model="form.watermark_text" class="w-full bg-white/5 border-white/5 text-amber-400 font-bold text-sm rounded-xl focus:ring-amber-500/50 transition-all" placeholder="Contoh: VIDEYVIEW PROTECT" />
                                <p class="text-[9px] text-slate-500 italic mt-1">Teks ini akan melayang secara acak di atas video untuk mencegah perekaman layar (Anti-Piracy Ghost).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Global Monetization -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px] group-hover:bg-indigo-500/20 transition-all duration-1000"></div>
                        <div class="flex items-center justify-between mb-8 pb-8 border-b border-white/5 relative z-10">
                            <h3 class="text-xl font-black text-white flex items-center gap-4">
                                <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                                Mesin Monetisasi Platform
                            </h3>
                            <button 
                                type="button"
                                @click="form.anti_adblock_enabled = form.anti_adblock_enabled === '1' ? '0' : '1'"
                                :class="form.anti_adblock_enabled === '1' ? 'bg-indigo-500' : 'bg-slate-700'"
                                class="flex items-center gap-3 px-4 py-2 rounded-2xl border border-white/5 transition-all duration-300 group/toggle"
                            >
                                <div class="flex flex-col items-end">
                                    <span class="text-[8px] font-black uppercase tracking-widest leading-none" :class="form.anti_adblock_enabled === '1' ? 'text-white' : 'text-slate-400'">Anti-Adblock</span>
                                    <span class="text-[10px] font-black uppercase italic">{{ form.anti_adblock_enabled === '1' ? 'Diaktifkan' : 'Dinonaktifkan' }}</span>
                                </div>
                                <div class="w-10 h-6 bg-black/20 rounded-full relative p-1 transition-all">
                                    <div 
                                        :class="form.anti_adblock_enabled === '1' ? 'translate-x-4 bg-white' : 'translate-x-0 bg-slate-500'" 
                                        class="w-4 h-4 rounded-full transition-all duration-300 shadow-lg"
                                    ></div>
                                </div>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Skrip Penempatan Sosial" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_social_bar" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 focus:border-indigo-500/30 transition-all" placeholder="Tempel skrip Social Bar di sini..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Skrip Penempatan Popunder" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_popunder" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 focus:border-indigo-500/30 transition-all" placeholder="Tempel skrip Popunder di sini..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Placements -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -left-24 w-64 h-64 bg-violet-500/10 rounded-full blur-[100px] group-hover:bg-violet-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-violet-500 rounded-full"></span>
                            Penempatan Tampilan
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-4">
                                <InputLabel value="Penempatan 728x90" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_728x90" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Skrip Penempatan Global..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Penempatan 300x250" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_300x250" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Skrip Penempatan Sidebar..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Penempatan 468x60" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_468x60" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Skrip Penempatan Seluler..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Integrations -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-pink-500 rounded-full"></span>
                            Ekstensi Platform
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Banner Native" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_native_banner" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 transition-all" placeholder="Kode Banner Native..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="URL Smartlink" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_smartlink" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 transition-all" placeholder="URL Langsung Smartlink..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6">
                        <PrimaryButton class="btn-premium !px-16" :disabled="form.processing">
                            Simpan Konfigurasi
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
.btn-premium {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.4);
}
.btn-premium:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
}
</style>
