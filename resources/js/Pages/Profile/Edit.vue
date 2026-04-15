<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Admin - Profil Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Profil <span class="text-indigo-500">Privat</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Identitas & Keamanan Akun</p>
                </div>
                <div class="bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20">
                    <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">Aura Guard Active</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Information Card -->
                <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden group shadow-xl">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-[60px] group-hover:bg-indigo-500/10 transition-all duration-700"></div>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl relative z-10"
                    />
                </div>

                <!-- Update Password Card -->
                <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden group shadow-xl">
                    <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-purple-500/5 rounded-full blur-[60px] group-hover:bg-purple-500/10 transition-all duration-700"></div>
                    <UpdatePasswordForm class="max-w-2xl relative z-10" />
                </div>

                <!-- Danger Zone -->
                <div class="bg-red-500/5 p-5 sm:p-6 rounded-2xl border border-red-500/10 relative overflow-hidden group shadow-xl">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-red-500/10 rounded-full blur-[60px]"></div>
                    <DeleteUserForm class="max-w-2xl relative z-10" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(20px);
}
</style>
