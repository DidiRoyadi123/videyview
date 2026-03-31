<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    toast: Object,
});

const emit = defineEmits(['remove']);

onMounted(() => {
    // Auto-remove handled by composable, but could add progress bar here
});
</script>

<template>
    <div 
        class="group relative flex items-center p-4 min-w-[300px] max-w-md glass-dark border border-white/10 rounded-2xl shadow-2xl transition-all duration-300 animate-in slide-in-from-right-full"
        :class="{
            'border-emerald-500/30 bg-emerald-500/5': toast.type === 'success',
            'border-red-500/30 bg-red-500/5': toast.type === 'error',
            'border-amber-500/30 bg-amber-500/5': toast.type === 'warning',
            'border-indigo-500/30 bg-indigo-500/5': toast.type === 'info',
        }"
    >
        <!-- Icon -->
        <div class="mr-4 flex-shrink-0">
            <div v-if="toast.type === 'success'" class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-500 text-lg">✓</div>
            <div v-if="toast.type === 'error'" class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 text-lg">✕</div>
            <div v-if="toast.type === 'warning'" class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-500 text-lg">!</div>
            <div v-if="toast.type === 'info'" class="w-8 h-8 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-500 text-lg">i</div>
        </div>

        <!-- Content -->
        <div class="flex-1">
            <p class="text-[10px] font-black uppercase tracking-widest mb-0.5 opacity-50">
                {{ toast.type }}
            </p>
            <p class="text-sm font-bold text-white leading-tight">
                {{ toast.message }}
            </p>
        </div>

        <!-- Close Button -->
        <button 
            @click="$emit('remove', toast.id)"
            class="ml-4 p-1 rounded-lg hover:bg-white/5 text-slate-500 hover:text-white transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Progress Bar (Optional) -->
        <div class="absolute bottom-0 left-4 right-4 h-0.5 bg-white/5 rounded-full overflow-hidden">
            <div 
                class="h-full transition-all duration-[5000ms] ease-linear"
                :class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-red-500': toast.type === 'error',
                    'bg-amber-500': toast.type === 'warning',
                    'bg-indigo-500': toast.type === 'info',
                }"
                style="width: 0%; animation: toast-timeout 5s linear forwards;"
            ></div>
        </div>
    </div>
</template>

<style scoped>
@keyframes toast-timeout {
    from { width: 100%; }
    to { width: 0%; }
}
</style>
