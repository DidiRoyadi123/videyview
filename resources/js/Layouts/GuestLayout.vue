<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 relative overflow-hidden px-4">
        <!-- Background Decorations -->
        <div class="absolute -top-40 -left-40 w-80 h-80 bg-indigo-600/10 rounded-full blur-[100px]"></div>
        <div class="absolute -bottom-40 -right-40 w-80 h-80 bg-violet-600/10 rounded-full blur-[100px]"></div>

        <div class="z-10 text-center mb-8">
            <Link href="/" class="group inline-flex flex-col items-center">
                <ApplicationLogo class="w-16 h-16 fill-current text-indigo-500 transition-transform group-hover:scale-110 duration-500" />
                <h1 class="mt-4 text-3xl font-black tracking-tighter text-white">VIDEY<span class="text-indigo-500">VIEW</span></h1>
            </Link>
        </div>

        <div class="w-full sm:max-w-md z-10">
            <div class="glass-dark p-8 rounded-[2rem] border border-white/5 shadow-2xl relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition-colors"></div>
                <slot />
            </div>
            
            <div class="mt-8 text-center">
                <Link href="/" class="text-slate-500 hover:text-indigo-400 text-sm font-bold transition flex items-center justify-center gap-2">
                    <span>←</span> Back to Discover
                </Link>
            </div>
        </div>
    </div>
</template>
