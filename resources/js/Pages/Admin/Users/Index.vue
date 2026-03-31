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
            toastSuccess(`Premium status granted to ${user.name}!`);
            user._add_days = 0;
        },
    });
};

const updateUser = (user) => {
    router.patch(route('admin.users.update', user.id), {
        password: user._new_password,
        // Keep existing update logic if needed, but grantPremium is preferred for status
    }, {
        onSuccess: () => {
            toastSuccess('User password updated!');
            user._new_password = '';
        },
    });
};

const deleteUser = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        useForm({}).delete(route('admin.users.destroy', id), {
            onFinish: () => { toastSuccess('User deleted!'); }
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
    <Head title="Admin - User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter">Premium Users</h2>
                <div class="bg-indigo-500/10 px-4 py-1 rounded-full border border-indigo-500/20">
                    <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">{{ users.total }} Verified Users</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                <!-- Add User Form -->
                <div class="glass-dark p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                     <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>
                    <h3 class="text-xl font-black text-white mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>
                        Onboard Premium Member
                    </h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
                        <div>
                            <InputLabel for="name" value="Full Name" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />
                            <TextInput id="name" type="text" class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl" v-model="form.name" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email Address" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />
                            <TextInput id="email" type="email" class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="password" value="Password" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />
                            <TextInput id="password" type="password" class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl" v-model="form.password" required />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="duration_days" value="Days (Access)" class="!text-slate-500 !text-[10px] !font-black !uppercase !tracking-widest mb-1" />
                            <TextInput id="duration_days" type="number" class="mt-1 block w-full !bg-white/5 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-xl" v-model="form.duration_days" required />
                            <InputError class="mt-2" :message="form.errors.duration_days" />
                        </div>
                        <div class="lg:col-span-4 flex justify-end">
                            <PrimaryButton :disabled="form.processing" class="btn-premium !px-12">CREATE PREMIUM ACCOUNT</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- User List -->
                <div class="glass-dark rounded-[2.5rem] border border-white/5 overflow-hidden shadow-2xl">
                    <div class="p-8 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <h3 class="font-black text-white uppercase tracking-widest text-sm">Customer Registry</h3>
                        <div class="relative w-full md:w-80 group">
                            <TextInput 
                                type="text" 
                                v-model="search" 
                                placeholder="Search member name or email..." 
                                class="w-full !bg-white/5 !border-white/10 !text-white !rounded-2xl !ps-10 placeholder:!text-slate-600 focus:!border-indigo-500/50"
                            />
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors group-hover:text-slate-400">🔍</div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-black uppercase text-slate-500 tracking-widest border-b border-white/5 bg-white/5">
                                    <th class="py-5 px-8">Member Profile</th>
                                    <th class="py-5 px-8">Contact Info</th>
                                    <th class="py-5 px-8">Expiry Date</th>
                                    <th class="py-5 px-8">Tier Status</th>
                                    <th class="py-5 px-8 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="user in users.data" :key="user.id" class="group hover:bg-white/5 transition duration-300">
                                    <td class="py-5 px-8">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-black">{{ user.name.charAt(0) }}</div>
                                            <div>
                                                <div class="text-white font-bold group-hover:text-indigo-400 transition">{{ user.name }}</div>
                                                <div class="text-[10px] text-slate-500 font-medium">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-5 px-8">
                                        <div class="inline-flex items-center gap-2 group/contact">
                                            <span class="text-xs font-bold text-slate-300">{{ user.whatsapp_telegram || 'Not provided' }}</span>
                                            <button v-if="user.whatsapp_telegram" @click="navigator.clipboard.writeText(user.whatsapp_telegram); toastSuccess('Contact copied!')" class="opacity-0 group-hover/contact:opacity-100 transition-opacity p-1 text-slate-500 hover:text-indigo-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-5 px-8">
                                        <div class="text-slate-300 font-bold tracking-tight text-sm">
                                            {{ user.active_subscription ? formatDate(user.active_subscription.expires_at) : 'Inactive' }}
                                        </div>
                                        <div class="text-[9px] text-slate-500 font-black uppercase tracking-widest mt-0.5">Automated billing</div>
                                    </td>
                                    <td class="py-5 px-8">
                                        <span v-if="user.active_subscription" class="bg-indigo-500/10 text-indigo-400 text-[10px] px-3 py-1 rounded-full border border-indigo-500/20 font-black uppercase tracking-widest flex items-center gap-1.5 w-fit">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                            Premium
                                        </span>
                                        <span v-else class="bg-slate-800 text-slate-500 text-[10px] px-3 py-1 rounded-full border border-white/5 font-black uppercase tracking-widest w-fit">
                                            Basic Free
                                        </span>
                                    </td>
                                    <td class="py-5 px-8 text-right">
                                        <div class="flex justify-end gap-3 items-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                            <div class="flex flex-col gap-2 bg-white/5 rounded-2xl border border-white/10 p-3 w-48 text-left">
                                                <div>
                                                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest block mb-1 ms-1">Reset Password</label>
                                                    <div class="flex gap-1">
                                                        <input 
                                                            type="password" 
                                                            v-model="user._new_password" 
                                                            placeholder="••••••••"
                                                            class="w-full h-8 text-xs !bg-slate-900/50 !border-white/5 !text-white focus:!ring-indigo-500/50 rounded-lg placeholder:text-slate-700" 
                                                        />
                                                        <button 
                                                            @click="updateUser(user)" 
                                                            class="h-8 px-3 bg-indigo-600 hover:bg-indigo-500 text-white text-[9px] font-black uppercase tracking-tight rounded-lg transition"
                                                        >
                                                            Set
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest block mb-1 ms-1">Grant Premium Days</label>
                                                    <div class="flex gap-1">
                                                        <input 
                                                            type="number" 
                                                            v-model="user._add_days" 
                                                            placeholder="30"
                                                            class="w-16 h-8 text-xs !bg-slate-900/50 !border-white/5 !text-white focus:!ring-emerald-500/50 rounded-lg text-center" 
                                                        />
                                                        <button 
                                                            @click="grantPremium(user)" 
                                                            class="flex-1 h-8 bg-emerald-600 hover:bg-emerald-500 text-white text-[9px] font-black uppercase tracking-tight rounded-lg transition"
                                                        >
                                                            Grant
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click="deleteUser(user.id)" class="p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="p-6 border-t border-white/5 bg-slate-900/50 flex justify-center">
                         <div class="flex gap-1">
                            <Link v-for="link in users.links" :key="link.label" 
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded-xl text-xs font-black transition-all uppercase tracking-widest',
                                    link.active ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white hover:bg-white/5',
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
