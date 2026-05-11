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
        class="rounded-lg bg-white border-gray-300 text-[#8C57FF] shadow-sm focus:ring-[#8C57FF]/20 focus:ring-offset-0 focus:border-[#8C57FF]/50 transition-all duration-300 w-5 h-5"
    />
</template>
