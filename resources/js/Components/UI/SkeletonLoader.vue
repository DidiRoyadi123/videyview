<script setup>
defineProps({
    type: {
        type: String,
        default: 'card' // card, list, video, text
    },
    count: {
        type: Number,
        default: 1
    }
});
</script>

<template>
    <div class="space-y-4">
        <div v-for="i in count" :key="i" :class="[
            'relative overflow-hidden bg-[rgb(var(--bg-input))] border border-[rgb(var(--border-main))] shadow-inner',
            type === 'card' ? 'aspect-video rounded-[32px]' : '',
            type === 'list' ? 'h-24 rounded-3xl flex gap-4 p-3' : '',
            type === 'text' ? 'h-4 rounded-full' : '',
            type === 'video' ? 'aspect-video rounded-[40px]' : ''
        ]">
            <!-- Shimmer Effect -->
            <div class="shimmer-sweep absolute inset-0 bg-gradient-to-r from-transparent via-indigo-500/5 to-transparent -translate-x-full"></div>
            
            <template v-if="type === 'list'">
                <div class="w-24 h-full rounded-xl bg-white/5 shrink-0"></div>
                <div class="flex-grow space-y-3 pt-2">
                    <div class="h-3 w-2/3 bg-white/5 rounded-full"></div>
                    <div class="h-2 w-1/3 bg-white/5 rounded-full"></div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.shimmer-sweep {
    animation: shimmer 2s infinite cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>
