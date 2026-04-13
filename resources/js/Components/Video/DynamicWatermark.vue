<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: 'VIDEYVIEW PROTECT'
    }
});

const position = ref({ x: 50, y: 50 });
const opacity = ref(0.2);
let interval = null;

const moveWatermark = () => {
    // Generate random positions between 10% and 90%
    position.value = {
        x: Math.floor(Math.random() * 80) + 10,
        y: Math.floor(Math.random() * 80) + 10
    };
};

onMounted(() => {
    moveWatermark();
    // Move every 8 seconds to be subtle but annoying for pirates
    interval = setInterval(moveWatermark, 8000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <div 
        class="absolute pointer-events-none select-none z-[45] transition-all duration-[3000ms] ease-in-out"
        :style="{
            left: position.x + '%',
            top: position.y + '%',
            opacity: opacity
        }"
    >
        <div class="flex flex-col items-center">
            <span class="text-[10px] sm:text-[14px] font-black text-white/40 uppercase tracking-[0.4em] drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] whitespace-nowrap">
                {{ text }}
            </span>
            <span class="text-[8px] font-bold text-white/20 uppercase tracking-widest mt-1">
                Authorized Stream
            </span>
        </div>
    </div>
</template>
