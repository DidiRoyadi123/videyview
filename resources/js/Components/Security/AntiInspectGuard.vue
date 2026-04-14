<script setup>
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    enabled: {
        type: Boolean,
        default: true
    }
});

const handleContextMenu = (e) => {
    if (!props.enabled) return;
    e.preventDefault();
    return false;
};

const handleKeyDown = (e) => {
    if (!props.enabled) return;

    // F12
    if (e.keyCode === 123) {
        e.preventDefault();
        return false;
    }

    // Ctrl + Shift + I/J/C
    if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
        e.preventDefault();
        return false;
    }

    // Ctrl + U (View Source)
    if (e.ctrlKey && e.keyCode === 85) {
        e.preventDefault();
        return false;
    }
};

let debuggerInterval = null;

const startDebuggerTrap = () => {
    if (!props.enabled) return;
    
    // The "Debugger Trap" technique:
    // Calling debugger inside a fast interval will cause the browser 
    // to pause execution if DevTools is open, effectively freezing the site for the inspector.
    debuggerInterval = setInterval(() => {
        (function() {
            return false;
        }['constructor']('debugger')['call']());
    }, 100);
};

onMounted(() => {
    if (props.enabled) {
        window.addEventListener('contextmenu', handleContextMenu);
        window.addEventListener('keydown', handleKeyDown);
        startDebuggerTrap();
        
        // Anti-drag for images/videos
        document.addEventListener('dragstart', (e) => e.preventDefault());
    }
});

onUnmounted(() => {
    window.removeEventListener('contextmenu', handleContextMenu);
    window.removeEventListener('keydown', handleKeyDown);
    if (debuggerInterval) clearInterval(debuggerInterval);
});
</script>

<template>
    <!-- Invisible Security Guard -->
    <div v-if="enabled" class="hidden pointer-events-none select-none" aria-hidden="true">
        <!-- Security Active -->
    </div>
</template>

<style scoped>
/* Aggressive content protection */
:global(body) {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

:global(video), :global(img) {
    -webkit-user-drag: none;
    user-drag: none;
}
</style>
