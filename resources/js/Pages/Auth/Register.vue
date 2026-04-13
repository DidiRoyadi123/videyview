<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    whatsapp_telegram: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Buat Akun" />

        <div class="mb-8 overflow-hidden">
            <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter">Bergabung dengan VideyView</h2>
            <p class="text-slate-500 text-sm font-medium mt-1">Mulai perjalanan Anda dengan streaming premium hari ini.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="John Doe"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="whatsapp_telegram" value="WhatsApp atau Telegram" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />

                <TextInput
                    id="whatsapp_telegram"
                    type="text"
                    class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl"
                    v-model="form.whatsapp_telegram"
                    required
                    placeholder="@username or +62..."
                />

                <InputError class="mt-2" :message="form.errors.whatsapp_telegram" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="name@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Kata Sandi" class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Konfirmasi Kata Sandi"
                    class="!text-slate-400 !text-[10px] !font-black !uppercase !tracking-widest mb-1"
                />

                <PasswordInput
                    id="password_confirmation"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-4">
                <PrimaryButton
                    class="w-full btn-premium !py-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    BUAT AKUN
                </PrimaryButton>
            </div>

            <div class="text-center pt-2">
                <p class="text-xs font-bold text-slate-600">
                    Sudah memiliki akun? 
                    <Link :href="route('login')" class="text-indigo-400 hover:text-indigo-300 transition uppercase tracking-widest ms-1">Masuk</Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
