<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2 italic">
                <span class="w-1 h-3 bg-indigo-500 rounded-full"></span>
                Informasi Profil
            </h2>

            <p class="mt-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed mt-2">
                Perbarui informasi profil akun dan alamat email Anda.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-5"
        >
            <div class="space-y-1.5">
                <InputLabel for="name" value="Nama Lengkap" class="!text-[10px] !font-bold !text-slate-500 !uppercase !tracking-widest" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full !bg-black/20 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl focus:!ring-indigo-500/20"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2 text-xs" :message="form.errors.name" />
            </div>

            <div class="space-y-1.5">
                <InputLabel for="email" value="Alamat Email" class="!text-[10px] !font-bold !text-slate-500 !uppercase !tracking-widest" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !bg-black/20 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl focus:!ring-indigo-500/20"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2 text-xs" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-xs text-white">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-xs text-indigo-400 underline hover:text-indigo-300 font-bold uppercase tracking-widest"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-bold text-emerald-400 px-3 py-2 bg-emerald-500/10 rounded-lg border border-emerald-500/20 uppercase tracking-widest"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing" class="!py-2.5 !px-6 !text-[10px] !rounded-xl !uppercase !tracking-widest">Simpan Perubahan</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest bg-emerald-500/10 px-3 py-1 rounded-lg border border-emerald-500/10"
                    >
                        Berhasil Disimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
