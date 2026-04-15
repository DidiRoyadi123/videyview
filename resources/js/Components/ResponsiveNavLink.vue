<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-3 border-l-4 border-indigo-500 text-start text-sm font-black text-white bg-indigo-500/10 focus:outline-none transition duration-150 ease-in-out uppercase tracking-widest shadow-[inset_4px_0_10px_rgba(99,102,241,0.1)]'
        : 'block w-full ps-3 pe-4 py-3 border-l-4 border-transparent text-start text-sm font-bold text-slate-500 hover:text-white hover:bg-white/5 hover:border-slate-700 focus:outline-none transition duration-150 ease-in-out uppercase tracking-widest',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
