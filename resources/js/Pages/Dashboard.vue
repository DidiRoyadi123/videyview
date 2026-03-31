<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter">Admin Dashboard</h2>
                <div class="bg-indigo-500/10 px-4 py-1 rounded-full border border-indigo-500/20">
                    <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">System Overview</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                        <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>
                        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4">Total Videos</h3>
                        <div class="text-4xl font-black text-white italic tracking-tighter">{{ stats.total_videos }}</div>
                        <div class="mt-4 text-xs font-bold text-indigo-400">Cinematic Library</div>
                    </div>

                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                         <div class="absolute -top-12 -right-12 w-32 h-32 bg-violet-500/5 rounded-full blur-3xl group-hover:bg-violet-500/10 transition-colors"></div>
                        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4">Community</h3>
                        <div class="text-4xl font-black text-white italic tracking-tighter">{{ stats.total_users }}</div>
                        <div class="mt-4 text-xs font-bold text-violet-400">Registered Members</div>
                    </div>

                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                         <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>
                        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4">Premium Subs</h3>
                        <div class="text-4xl font-black text-white italic tracking-tighter">{{ stats.total_premium_users }}</div>
                        <div class="mt-4 text-xs font-bold text-amber-400">Gold Tier Accounts</div>
                    </div>

                    <div class="glass-dark p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
                         <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors"></div>
                        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-4">Total Views</h3>
                        <div class="text-4xl font-black text-white italic tracking-tighter">{{ stats.total_views.toLocaleString() }}</div>
                        <div class="mt-4 text-xs font-bold text-emerald-400">Total Playback</div>
                    </div>
                </div>

                <!-- Quick Actions / Info -->
                <div class="glass-dark p-10 rounded-[2.5rem] border border-white/5 bg-gradient-to-br from-indigo-500/5 to-transparent text-center">
                    <h3 class="text-2xl font-black text-white mb-4 italic uppercase tracking-tighter">System Status: Operational</h3>
                    <p class="text-slate-400 max-w-2xl mx-auto mb-8 font-medium">VideyView is currently processing requests and streaming content worldwide. Use the navigation above to manage users and media.</p>
                    <div class="flex justify-center gap-4">
                        <Link :href="route('admin.videos.index')" class="btn-premium !px-8">Manage Media</Link>
                        <Link :href="route('admin.users.index')" class="bg-white/5 hover:bg-white/10 text-white font-black px-8 py-3 rounded-xl border border-white/10 transition uppercase tracking-widest text-[10px]">Manage Users</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
