<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-sm font-black text-red-400 uppercase tracking-widest flex items-center gap-2 italic">
                <span class="w-1 h-3 bg-red-500 rounded-full"></span>
                Hapus Akun
            </h2>

            <p class="mt-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion" class="!py-2.5 !px-6 !text-[10px] !rounded-xl !uppercase !tracking-widest">Hapus Akun Saya</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-slate-900 text-white rounded-3xl border border-white/10 shadow-2xl">
                <h2 class="text-lg font-black italic uppercase tracking-tight text-white">
                    Apakah Anda yakin ingin menghapus akun?
                </h2>

                <p class="mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
                    Tindakan ini permanen. Silakan masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Password"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full !bg-black/40 !border-white/10 !text-white !text-sm !px-4 !py-3 !rounded-xl"
                        placeholder="Kata Sandi Anda"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2 text-xs" />
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal" class="!bg-white/5 !text-slate-400 !border-white/10 !px-4 !py-2 !text-[10px] !rounded-lg uppercase font-bold tracking-widest">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3 !bg-red-600 hover:!bg-red-500 !text-white !px-6 !py-2 !text-[10px] !rounded-lg uppercase font-bold tracking-widest"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Hapus Permanen
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
