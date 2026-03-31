<script setup>
import { ref, onMounted } from 'vue';

const isAdblockActive = ref(false);
const showModal = ref(false);

const checkAdblock = () => {
    // Check if the bait script was loaded
    if (window.isAdEnabled !== true) {
        isAdblockActive.value = true;
        
        // Show modal after a short delay for better UX
        setTimeout(() => {
            showModal.value = true;
        }, 1500);
    }
};

onMounted(() => {
    // We try to load the script in MainLayout, then check here
    // Or we can dynamically create it here
    const script = document.createElement('script');
    script.src = '/js/ads-check.js';
    script.onload = () => {
        checkAdblock();
    };
    script.onerror = () => {
        isAdblockActive.value = true;
        showModal.value = true;
    };
    document.head.appendChild(script);
});

const closeGuard = () => {
    showModal.value = false;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-400 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-xl" @click="closeGuard"></div>
                
                <!-- Modal Content -->
                <div class="relative max-w-lg w-full glass-dark border border-indigo-500/20 rounded-[40px] p-10 shadow-[0_32px_100px_rgba(79,70,229,0.2)] text-center overflow-hidden">
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px]"></div>
                    
                    <div class="mb-8 relative">
                        <div class="w-24 h-24 mx-auto rounded-3xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-5xl shadow-inner">
                            🛡️
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center text-xs border-4 border-slate-950 font-black">X</div>
                    </div>
                    
                    <h2 class="text-3xl font-black text-white italic tracking-tighter mb-4 uppercase">
                        Ad-Blocker <span class="text-indigo-400">Detected</span>
                    </h2>
                    
                    <p class="text-slate-400 font-medium mb-10 leading-relaxed text-lg">
                        We noticed you're using an ad-blocker. To keep <b>VideyView</b> free and maintain our cinematic quality, please consider disabling it.
                    </p>
                    
                    <div class="space-y-4">
                        <button @click="closeGuard" class="btn-premium w-full h-14 text-sm font-black uppercase tracking-widest">
                            I've Disabled It
                        </button>
                        <button @click="closeGuard" class="text-slate-500 hover:text-white transition text-[10px] font-black uppercase tracking-widest">
                            Continue for now (limited features)
                        </button>
                    </div>
                    
                    <div class="mt-10 pt-8 border-t border-white/5">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                            Or Upgrade to <span class="text-indigo-400">Premium</span> for an Ad-Free Experience
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.9);
    backdrop-filter: blur(24px);
}
.btn-premium {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.4);
    border-radius: 20px;
    color: white;
}
.btn-premium:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
}
</style>
