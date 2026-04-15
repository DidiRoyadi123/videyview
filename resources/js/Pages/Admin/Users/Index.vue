<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess } = useToast();

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search);

watch(search, (value) => {
    router.get(route('admin.users.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    duration_days: 30,
});

const submit = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => form.reset(),
    });
};

const grantPremium = (user, days) => {
    router.patch(route('admin.users.grant-premium', user.id), {
        days: days || user._add_days || 30,
    }, {
        onSuccess: () => {
            toastSuccess(`Status Premium diberikan kepada ${user.name}!`);
            user._add_days = 0;
        },
    });
};

const updateUser = (user) => {
    router.patch(route('admin.users.update', user.id), {
        password: user._new_password,
    }, {
        onSuccess: () => {
            toastSuccess('Kata sandi pengguna diperbarui!');
            user._new_password = '';
        },
    });
};

const deleteUser = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
        useForm({}).delete(route('admin.users.destroy', id), {
            onFinish: () => { toastSuccess('Pengguna dihapus!'); }
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Admin - Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white italic uppercase tracking-tight">
                        Pengguna <span class="text-indigo-500">Premium</span>
                    </h2>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mt-1">Manajemen Hak Akses & Keanggotaan</p>
                </div>
                <div class="bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20">
                    <span class="text-xs font-bold uppercase text-indigo-400 tracking-widest">{{ users.total }} Anggota</span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Add User Form -->
                <div class="glass-dark p-5 sm:p-6 rounded-2xl border border-white/10 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-[60px] pointer-events-none"></div>
                    <h3 class="text-base font-black text-white mb-5 flex items-center gap-3 uppercase tracking-tight">
                        <span class="w-1 h-5 bg-indigo-500 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
                        Daftarkan Anggota Premium
                    </h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div class="space-y-1.5">
                            <InputLabel for="name" value="Nama Lengkap" class="!text-slate-500 !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="name" type="text" class="block w-full !bg-white/5 !border !border-white/10 !text-white !text-sm !px-4 !py-2.5 focus:!ring-indigo-500/30 rounded-xl shadow-inner transition-all" v-model="form.name" required placeholder="e.g. John Doe" />
                            <InputError class="mt-1 text-xs" :message="form.errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <InputLabel for="email" value="Alamat Email" class="!text-slate-500 !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="email" type="email" class="block w-full !bg-white/5 !border !border-white/10 !text-white !text-sm !px-4 !py-2.5 focus:!ring-indigo-500/30 rounded-xl shadow-inner transition-all" v-model="form.email" required placeholder="email@example.com" />
                            <InputError class="mt-1 text-xs" :message="form.errors.email" />
                        </div>
                        <div class="space-y-1.5">
                            <InputLabel for="password" value="Kata Sandi" class="!text-slate-500 !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="password" type="password" class="block w-full !bg-white/5 !border !border-white/10 !text-white !text-sm !px-4 !py-2.5 focus:!ring-indigo-500/30 rounded-xl shadow-inner transition-all" v-model="form.password" required placeholder="••••••••" />
                            <InputError class="mt-1 text-xs" :message="form.errors.password" />
                        </div>
                        <div class="space-y-1.5">
                            <InputLabel for="duration_days" value="Hari (Akses)" class="!text-slate-500 !text-[10px] !font-bold !uppercase !tracking-widest" />
                            <TextInput id="duration_days" type="number" class="block w-full !bg-white/5 !border !border-white/10 !text-white !text-sm !px-4 !py-2.5 focus:!ring-indigo-500/30 rounded-xl shadow-inner transition-all text-center" v-model="form.duration_days" required />
                            <InputError class="mt-1 text-xs" :message="form.errors.duration_days" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                            <PrimaryButton :disabled="form.processing" class="btn-premium !px-6 !py-2.5 !text-xs !rounded-xl shadow-lg shadow-indigo-600/20 active:scale-95 transition-all">Buat Akun Premium</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- User List -->
                <div class="glass-dark rounded-2xl border border-white/10 overflow-hidden shadow-xl">
                    <div class="px-5 py-4 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/[0.02]">
                        <h3 class="font-black text-white uppercase tracking-widest text-sm">Registri Pelanggan</h3>
                        <div class="relative w-full sm:w-72">
                            <TextInput 
                                type="text" 
                                v-model="search" 
                                placeholder="Cari nama atau email..." 
                                class="w-full !bg-white/5 !border !border-white/10 !text-white !text-sm !rounded-xl !ps-9 !py-2 placeholder:!text-slate-600 focus:!border-indigo-500/50 transition-all shadow-inner"
                            />
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-600 text-sm">🔍</span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase text-slate-500 tracking-widest border-b border-white/10 bg-white/[0.02]">
                                    <th class="py-3 px-4">Anggota</th>
                                    <th class="py-3 px-4 hidden sm:table-cell">Kontak</th>
                                    <th class="py-3 px-4 hidden md:table-cell">Akses</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Manajemen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="user in users.data" :key="user.id" class="group hover:bg-white/[0.04] transition-all duration-200">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 flex-shrink-0 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 text-sm font-black">{{ user.name.charAt(0) }}</div>
                                            <div>
                                                <div class="text-sm font-bold text-white group-hover:text-indigo-400 transition-colors">{{ user.name }}</div>
                                                <div class="text-[10px] text-slate-500 font-medium truncate max-w-[160px]">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden sm:table-cell">
                                        <div class="inline-flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-lg border border-white/5">
                                            <span class="text-xs font-medium text-slate-300">{{ user.whatsapp_telegram || 'N/A' }}</span>
                                            <button v-if="user.whatsapp_telegram" @click="navigator.clipboard.writeText(user.whatsapp_telegram); toastSuccess('Kontak disalin!')" class="text-slate-500 hover:text-indigo-400 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 hidden md:table-cell">
                                        <div class="text-sm font-bold text-white">
                                            {{ user.active_subscription ? formatDate(user.active_subscription.expires_at) : 'Tidak Aktif' }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-medium mt-0.5">Tanggal Berakhir</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span v-if="user.active_subscription" class="bg-indigo-500/20 text-indigo-400 text-[10px] px-3 py-1 rounded-full border border-indigo-500/30 font-bold uppercase tracking-wide flex items-center gap-1.5 w-fit">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                            Premium
                                        </span>
                                        <span v-else class="bg-slate-800/50 text-slate-600 text-[10px] px-3 py-1 rounded-full border border-white/5 font-bold uppercase tracking-wide w-fit">
                                            Gratis
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex justify-end gap-2 items-center">
                                            <div class="flex flex-col gap-2 bg-white/5 rounded-xl border border-white/10 p-3 text-left shadow-lg backdrop-blur-xl min-w-[200px]">
                                                <div>
                                                    <label class="text-[10px] font-bold uppercase text-slate-500 tracking-widest block mb-1">Sandi Baru</label>
                                                    <div class="flex gap-1.5">
                                                        <input 
                                                            type="password" 
                                                            v-model="user._new_password" 
                                                            placeholder="Kata sandi baru"
                                                            class="w-full h-8 text-xs !bg-black/40 !border !border-white/10 !text-white focus:!ring-indigo-500/30 rounded-lg placeholder:text-slate-700 px-3" 
                                                        />
                                                        <button 
                                                            @click="updateUser(user)" 
                                                            class="h-8 px-3 bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-bold uppercase rounded-lg transition-all active:scale-95"
                                                        >
                                                            Set
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="text-[10px] font-bold uppercase text-slate-500 tracking-widest block mb-1">Hibah Akses</label>
                                                    <div class="flex gap-1.5">
                                                        <input 
                                                            type="number" 
                                                            v-model="user._add_days" 
                                                            placeholder="30"
                                                            class="w-16 h-8 text-xs font-bold !bg-black/40 !border !border-white/10 !text-white focus:!ring-emerald-500/30 rounded-lg text-center shadow-inner" 
                                                        />
                                                        <button 
                                                            @click="grantPremium(user)" 
                                                            class="flex-1 h-8 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all active:scale-95"
                                                        >
                                                            Berikan
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click="deleteUser(user.id)" class="p-2 text-slate-500 hover:text-white hover:bg-red-500 rounded-xl border border-white/5 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="px-5 py-4 border-t border-white/10 bg-black/20 flex justify-center">
                        <div class="flex gap-1.5 flex-wrap justify-center">
                            <Link v-for="link in users.links" :key="link.label" 
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all uppercase tracking-widest',
                                    link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-500 hover:text-white hover:bg-white/10',
                                    !link.url ? 'opacity-30 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
