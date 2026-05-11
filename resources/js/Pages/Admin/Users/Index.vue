<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import MaterioLayout from '@/Layouts/MaterioLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
        onSuccess: () => {
            form.reset();
            toastSuccess('Akun premium berhasil didaftarkan!');
        },
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
        router.delete(route('admin.users.destroy', id), {
            onSuccess: () => toastSuccess('Pengguna dihapus!'),
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Manajemen Pengguna - Materio Royale" />

    <MaterioLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#3A3541]">Manajemen Pengguna</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola hak akses premium dan database anggota.</p>
                </div>
                <div class="bg-[#8C57FF]/5 px-4 py-2 rounded-xl border border-[#8C57FF]/10 text-center">
                    <span class="text-xs font-bold uppercase text-[#8C57FF] tracking-widest">{{ users.total }} Anggota Terdaftar</span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Add User Card -->
            <div class="materio-card p-6 relative overflow-hidden">
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-[#8C57FF]/10 rounded-full blur-[60px] pointer-events-none"></div>
                <h3 class="text-lg font-bold text-[#3A3541] mb-6 flex items-center gap-3">
                    <span class="w-1 h-6 bg-[#8C57FF] rounded-full"></span>
                    Registrasi Premium Baru
                </h3>
                
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div class="space-y-1.5">
                        <InputLabel for="name" value="Nama Lengkap" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="name" type="text" v-model="form.name" required class="block w-full" placeholder="John Doe" />
                        <InputError :message="form.errors.name" class="mt-1 text-xs" />
                    </div>
                    <div class="space-y-1.5">
                        <InputLabel for="email" value="Alamat Email" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="email" type="email" v-model="form.email" required class="block w-full" placeholder="email@contoh.com" />
                        <InputError :message="form.errors.email" class="mt-1 text-xs" />
                    </div>
                    <div class="space-y-1.5">
                        <InputLabel for="password" value="Kata Sandi" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="password" type="password" v-model="form.password" required class="block w-full" placeholder="••••••••" />
                        <InputError :message="form.errors.password" class="mt-1 text-xs" />
                    </div>
                    <div class="space-y-1.5">
                        <InputLabel for="duration_days" value="Durasi Akses (Hari)" class="!text-gray-500 !text-[11px] !font-bold !uppercase !tracking-wider" />
                        <TextInput id="duration_days" type="number" v-model="form.duration_days" required class="block w-full text-center" />
                        <InputError :message="form.errors.duration_days" class="mt-1 text-xs" />
                    </div>
                    <div class="md:col-span-2 lg:col-span-4 flex justify-end pt-2">
                        <button class="px-8 py-2.5 bg-[#8C57FF] text-white font-bold rounded-lg hover:bg-[#7B47E6] transition shadow-md shadow-[#8C57FF]/20 uppercase text-xs tracking-widest disabled:opacity-50" :disabled="form.processing">
                            {{ form.processing ? 'Memproses...' : 'Daftarkan VIP' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users List Card -->
            <div class="materio-card overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h3 class="font-bold text-[#3A3541]">Database Anggota</h3>
                    <div class="relative w-full sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama atau email..." 
                            class="w-full bg-white border-gray-200 text-[#3A3541] text-sm rounded-xl ps-10 py-2.5 focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] transition-all"
                        />
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                    </div>
                </div>
                
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-bold uppercase text-gray-400 tracking-wider border-b border-gray-100 bg-gray-50/30">
                                <th class="py-4 px-6">Anggota</th>
                                <th class="py-4 px-4 hidden md:table-cell">Akses Berakhir</th>
                                <th class="py-4 px-4">Status</th>
                                <th class="py-4 px-6 text-right">Manajemen Akun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="group hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 shrink-0 rounded-full bg-[#8C57FF]/10 border border-[#8C57FF]/20 flex items-center justify-center text-[#8C57FF] text-sm font-bold">{{ user.name.charAt(0).toUpperCase() }}</div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-[#3A3541] truncate group-hover:text-[#8C57FF] transition-colors">{{ user.name }}</div>
                                            <div class="text-[11px] text-gray-400 truncate">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">
                                    <div class="text-xs font-bold text-[#3A3541]">
                                        {{ user.active_subscription ? formatDate(user.active_subscription.expires_at) : '-' }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5 uppercase tracking-wider">Expiry Date</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="user.active_subscription" class="inline-flex items-center gap-1.5 bg-green-50 text-green-600 px-3 py-1 rounded-full border border-green-100 text-[10px] font-bold uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        Premium
                                    </span>
                                    <span v-else class="inline-flex items-center bg-gray-50 text-gray-400 px-3 py-1 rounded-full border border-gray-100 text-[10px] font-bold uppercase tracking-wider">
                                        Reguler
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <div class="flex justify-end gap-3 items-center">
                                        <!-- User Management Controls -->
                                        <div class="flex flex-col gap-2 bg-gray-50/80 rounded-xl border border-gray-100 p-3 text-left min-w-[220px]">
                                            <div>
                                                <label class="text-[9px] font-bold uppercase text-gray-400 tracking-widest block mb-1">Set Password</label>
                                                <div class="flex gap-1.5">
                                                    <input 
                                                        type="password" 
                                                        v-model="user._new_password" 
                                                        placeholder="Sandi baru..."
                                                        class="w-full h-8 text-[11px] bg-white border-gray-200 text-[#3A3541] focus:ring-4 focus:ring-[#8C57FF]/10 focus:border-[#8C57FF] rounded-lg px-2.5 transition-all" 
                                                    />
                                                    <button 
                                                        @click="updateUser(user)" 
                                                        class="h-8 px-3 bg-[#3A3541] hover:bg-[#2A2531] text-white text-[9px] font-bold uppercase rounded-lg transition-all"
                                                    >
                                                        Save
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="pt-2 border-t border-gray-100">
                                                <label class="text-[9px] font-bold uppercase text-gray-400 tracking-widest block mb-1">Grant Premium</label>
                                                <div class="flex gap-1.5">
                                                    <input 
                                                        type="number" 
                                                        v-model="user._add_days" 
                                                        placeholder="30"
                                                        class="w-14 h-8 text-[11px] font-bold bg-white border-gray-200 text-[#3A3541] focus:ring-4 focus:ring-green-500/10 focus:border-green-500 rounded-lg text-center transition-all" 
                                                    />
                                                    <button 
                                                        @click="grantPremium(user)" 
                                                        class="flex-1 h-8 bg-green-500 hover:bg-green-600 text-white text-[9px] font-bold uppercase tracking-wider rounded-lg transition-all shadow-sm"
                                                    >
                                                        Add Days
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Action -->
                                        <button @click="deleteUser(user.id)" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all border border-transparent hover:border-red-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-5 border-t border-gray-50 bg-gray-50/30 flex justify-center">
                    <div class="flex gap-1.5 flex-wrap justify-center">
                        <Link v-for="link in users.links" :key="link.label" 
                            :href="link.url || '#'"
                            :class="[
                                'px-3.5 py-2 rounded-lg text-[10px] font-bold transition-all uppercase tracking-widest border',
                                link.active ? 'bg-[#8C57FF] text-white border-[#8C57FF] shadow-sm' : 'bg-white text-gray-500 border-gray-100 hover:text-[#8C57FF] hover:border-[#8C57FF]/30',
                                !link.url ? 'opacity-30 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </MaterioLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
