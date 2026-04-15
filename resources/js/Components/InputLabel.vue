<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1.5 italic">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
