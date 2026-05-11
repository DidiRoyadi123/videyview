<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="bg-white border-gray-200 text-[#3A3541] text-sm px-4 py-2.5 rounded-xl placeholder:text-gray-400 focus:border-[#8C57FF] focus:ring-4 focus:ring-[#8C57FF]/10 transition-all duration-300"
        v-model="model"
        ref="input"
    />
</template>
