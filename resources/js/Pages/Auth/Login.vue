<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Selamat Datang Kembali" />

        <div class="mb-8 overflow-hidden">
            <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter">Selamat Datang Kembali</h2>
            <p class="text-slate-500 text-sm font-medium mt-1">Masuk untuk melanjutkan pengalaman premium Anda.</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-bold text-green-500 bg-green-500/10 p-3 rounded-xl border border-green-500/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Alamat Email" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="name@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <InputLabel for="password" value="Kata Sandi" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest" />
                    <a
                        href="https://t.me/Mandorbuah"
                        target="_blank"
                        class="text-[10px] font-black uppercase tracking-widest text-indigo-400 hover:text-indigo-300 transition"
                    >
                        Lupa? Hubungi Admin
                    </a>
                </div>

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center group cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="!bg-white/5 !border-white/10 !text-indigo-600 rounded" />
                    <span class="ms-2 text-xs font-bold text-slate-500 group-hover:text-slate-400 transition">Tetap masuk</span>
                </label>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full btn-premium !py-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    MASUK
                </PrimaryButton>
            </div>

            <div class="text-center pt-4">
                <p class="text-xs font-bold text-slate-600">
                    Belum memiliki akun? 
                    <Link :href="route('register')" class="text-indigo-400 hover:text-indigo-300 transition uppercase tracking-widest ms-1">Daftar</Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
