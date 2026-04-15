<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded-lg bg-black/40 border-white/10 text-indigo-500 shadow-sm focus:ring-indigo-500/20 focus:ring-offset-0 focus:border-indigo-500/50 transition-all duration-300 w-5 h-5"
    />
</template>
