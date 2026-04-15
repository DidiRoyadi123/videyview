<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-6 py-2.5 bg-white/5 border border-white/10 text-slate-300 text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition duration-300 ease-in-out hover:bg-white/10 hover:text-white focus:outline-none focus:ring-4 focus:ring-white/5 active:scale-95 shadow-lg italic"
    >
        <slot />
    </button>
</template>
