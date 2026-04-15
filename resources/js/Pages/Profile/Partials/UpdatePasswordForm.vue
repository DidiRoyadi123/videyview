<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2 italic">
                <span class="w-1 h-3 bg-indigo-500 rounded-full"></span>
                Perbarui Kata Sandi
            </h2>

            <p class="mt-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed mt-2">
                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk tetap aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-5">
            <div class="space-y-1.5">
                <InputLabel for="current_password" value="Kata Sandi Saat Ini" class="!text-[10px] !font-bold !text-slate-500 !uppercase !tracking-widest" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full !bg-black/20 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl focus:!ring-indigo-500/20"
                    autocomplete="current-password"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2 text-xs"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <InputLabel for="password" value="Kata Sandi Baru" class="!text-[10px] !font-bold !text-slate-500 !uppercase !tracking-widest" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full !bg-black/20 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl focus:!ring-indigo-500/20"
                        autocomplete="new-password"
                    />

                    <InputError :message="form.errors.password" class="mt-2 text-xs" />
                </div>

                <div class="space-y-1.5">
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Sandi"
                        class="!text-[10px] !font-bold !text-slate-500 !uppercase !tracking-widest"
                    />

                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full !bg-black/20 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl focus:!ring-indigo-500/20"
                        autocomplete="new-password"
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2 text-xs"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing" class="!py-2.5 !px-6 !text-[10px] !rounded-xl !uppercase !tracking-widest">Ganti Kata Sandi</PrimaryButton>

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
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
