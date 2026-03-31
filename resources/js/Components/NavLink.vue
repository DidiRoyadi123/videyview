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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-black leading-5 text-white focus:outline-none focus:border-indigo-400 transition duration-150 ease-in-out uppercase tracking-widest'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold leading-5 text-slate-400 hover:text-white hover:border-slate-700 focus:outline-none focus:text-white focus:border-slate-700 transition duration-150 ease-in-out uppercase tracking-widest',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
