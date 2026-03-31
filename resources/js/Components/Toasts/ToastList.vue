<script setup>
import { useToast } from '@/Composables/useToast';
import ToastMessage from './ToastMessage.vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div 
        aria-live="assertive" 
        class="fixed bottom-0 right-0 z-[100] flex flex-col p-6 space-y-4 items-end pointer-events-none"
    >
        <ToastMessage 
            v-for="toast in toasts" 
            :key="toast.id" 
            :toast="toast" 
            @remove="removeToast"
            class="pointer-events-auto"
        />
    </div>
</template>
