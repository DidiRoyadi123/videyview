<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 transition-colors duration-500" :style="{ backgroundColor: 'rgb(var(--bg-main))' }">
        <div class="mb-12 transition-transform duration-700 hover:scale-110">
            <Link href="/">
                <ApplicationLogo class="w-24 h-24 fill-current text-indigo-500 drop-shadow-[0_0_15px_rgba(99,102,241,0.3)]" />
            </Link>
        </div>

        <div class="w-full sm:max-w-md z-10">
            <div class="glass-dark p-8 rounded-[2rem] border border-[rgb(var(--border-main))] shadow-2xl relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition-colors"></div>
                <slot />
            </div>
            
            <div class="mt-8 text-center">
                <Link href="/" class="text-[rgb(var(--text-muted))] hover:text-indigo-400 text-sm font-bold transition flex items-center justify-center gap-2">
                    <span>←</span> Back to Discover
                </Link>
            </div>
        </div>
    </div>
</template>
