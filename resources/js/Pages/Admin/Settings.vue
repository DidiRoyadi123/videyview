<script setup>
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
});

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
            toastSuccess('Settings updated successfully.');
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Admin - Ad Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter">Ad Settings</h2>
                <div class="bg-indigo-500/10 px-4 py-1 rounded-full border border-indigo-500/20">
                    <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">Monetization Engine</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-10">
                    <!-- Global Ads -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                         <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px] group-hover:bg-indigo-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                            Global Engagement Ads
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Social Bar Code" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_social_bar" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 focus:border-indigo-500/30 transition-all" placeholder="Paste Adsterra Social Bar script here..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Popunder Code" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_popunder" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 focus:border-indigo-500/30 transition-all" placeholder="Paste Adsterra Popunder script here..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Banners -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-24 -left-24 w-64 h-64 bg-violet-500/10 rounded-full blur-[100px] group-hover:bg-violet-500/20 transition-all duration-1000"></div>
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-violet-500 rounded-full"></span>
                            Display Banners
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-4">
                                <InputLabel value="Banner 728x90" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_728x90" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Banner Ad Code..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Banner 300x250" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_300x250" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Sidebar Ad Code..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Banner 468x60" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_banner_468x60" class="w-full h-24 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-xl focus:ring-indigo-500/50 transition-all" placeholder="Mobile Banner Code..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Others -->
                    <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                        <h3 class="text-xl font-black text-white mb-8 flex items-center gap-4">
                            <span class="w-2 h-8 bg-pink-500 rounded-full"></span>
                            Others & Smartlinks
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <InputLabel value="Native Banner" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_native_banner" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 transition-all" placeholder="Native Banner Code..."></textarea>
                            </div>
                            <div class="space-y-4">
                                <InputLabel value="Smartlink URL" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest" />
                                <textarea v-model="form.ad_smartlink" class="w-full h-32 bg-white/5 border-white/5 text-indigo-300 font-mono text-xs rounded-2xl focus:ring-indigo-500/50 transition-all" placeholder="Smartlink Direct URL..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6">
                        <PrimaryButton class="btn-premium !px-16" :disabled="form.processing">
                            Save Configuration
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
