<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: 'VIDEYVIEW PROTECT'
    }
});

const position = ref({ x: 50, y: 50 });
const opacity = ref(0.15);
const scale = ref(1);
const rotate = ref(0);
const transitionDuration = ref(5000);

let interval = null;

const moveWatermark = () => {
    // Random positions (inner safe area)
    position.value = {
        x: Math.floor(Math.random() * 70) + 15,
        y: Math.floor(Math.random() * 70) + 15
    };
    
    // Subtle rotation & scale evolution
    rotate.value = (Math.random() * 10) - 5; // -5 to 5 deg
    scale.value = (Math.random() * 0.4) + 0.9; // 0.9 to 1.3 (slightly larger)
    opacity.value = (Math.random() * 0.25) + 0.2; // 0.2 to 0.45 (clearer)
    
    // Randomize next transition speed for organic movement
    transitionDuration.value = Math.floor(Math.random() * 3000) + 3000; // 3s to 6s
};

onMounted(() => {
    moveWatermark();
    // Evolve position Every 8-12 seconds
    interval = setInterval(moveWatermark, 10000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <div 
        class="absolute pointer-events-none select-none z-[45] transition-all ease-in-out"
        :style="{
            left: position.x + '%',
            top: position.y + '%',
            opacity: opacity,
            transform: `translate(-50%, -50%) scale(${scale}) rotate(${rotate}deg)`,
            transitionDuration: transitionDuration + 'ms'
        }"
    >
        <div class="flex flex-col items-center">
            <span class="text-[11px] sm:text-[15px] font-black text-white/70 uppercase tracking-[0.4em] drop-shadow-[0_2px_8px_rgba(0,0,0,1)] whitespace-nowrap">
                {{ text }}
            </span>
            <span class="text-[9px] font-bold text-white/40 uppercase tracking-widest mt-1 drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)]">
                Authorized Stream
            </span>
        </div>
    </div>
</template>
