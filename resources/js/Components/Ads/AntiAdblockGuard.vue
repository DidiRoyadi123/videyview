<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isRestrictedRoute = computed(() => {
    try {
        const routeName = window.route ? window.route().current() : null;
        return routeName && (
            routeName.startsWith('admin.') || 
            routeName === 'dashboard' || 
            routeName.startsWith('login') || 
            routeName.startsWith('register') || 
            routeName.startsWith('password.')
        );
    } catch (e) {
        return false;
    }
});

const isAdblockActive = ref(false);
const showModal = ref(false);
const isVerifying = ref(false);
const modalWrapperRef = ref(null);
const dynamicModalId = ref('anti-adblock-' + Math.random().toString(36).substr(2, 9));

// Base modal styles that must always be applied
const secureModalStyles = `
    position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important;
    height: 100vh !important; background: rgba(2, 6, 23, 0.9) !important;
    z-index: 999999 !important; display: flex !important; align-items: center !important;
    justify-content: center !important; backdrop-filter: blur(16px) !important;
    clip-path: none !important; visibility: visible !important; opacity: 1 !important;
`;

let checkIntervalId = null;

const createBaits = () => {
    // 1. Generic Bait
    let baitGeneric = document.getElementById('ad-detection-bait');
    if (!baitGeneric) {
        baitGeneric = document.createElement("div");
        baitGeneric.id = 'ad-detection-bait';
        baitGeneric.className = 'ad-banner adsbygoogle ad-unit advertisement adbox sponsored-ad';
        baitGeneric.style = "display: block !important; visibility: visible !important; opacity: 1 !important; height: 1px !important; width: 1px !important; position: absolute !important; left: -10000px !important; top: -10000px !important; pointer-events: none !important;";
        document.body.appendChild(baitGeneric);
    }
    
    // 2. AdSense Bait
    let baitAdsense = document.getElementById('ad-detection-bait-ins');
    if (!baitAdsense) {
        baitAdsense = document.createElement("ins");
        baitAdsense.id = 'ad-detection-bait-ins';
        baitAdsense.className = 'adsbygoogle';
        baitAdsense.style = "display: block !important; visibility: visible !important; opacity: 1 !important; height: 1px !important; width: 1px !important; position: absolute !important; left: -10000px !important; top: -10000px !important; pointer-events: none !important;";
        document.body.appendChild(baitAdsense);
    }
};

const refreshBaits = () => {
    if (isAdblockActive.value) return;
    ['ad-detection-bait', 'ad-detection-bait-ins'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.isConnected) {
            document.body.appendChild(el); // Move to end of DOM triggers CSS recalc
        } else {
            createBaits();
        }
    });
};

const isBlocked = (element) => {
    if (!element) return true;
    if (element.offsetHeight === 0 || element.offsetWidth === 0) return true;
    const style = getComputedStyle(element);
    return style.display === "none" || style.visibility === "hidden" || style.opacity === "0";
};

const showWarning = () => {
    if (!isAdblockActive.value) {
        isAdblockActive.value = true;
        showModal.value = true;
    }
};

const checkState = () => {
    let detected = false;
    
    // Check A: JS Variable Logic
    // If window.isAdEnabled is TRUE, it means the script loaded and ran. 
    // This is a very strong signal that Adblock is OFF or Whitelisted.
    const jsPass = window.isAdEnabled === true;

    // Check B: DOM Baits
    const baitGeneric = document.getElementById('ad-detection-bait');
    const baitAdsense = document.getElementById('ad-detection-bait-ins');
    
    // We only trigger detection if the JS is blocked OR (if JS passes) BOTH baits are hidden.
    // This provides leeway for browsers with persistent CSS rules for 'adsbygoogle' classes.
    if (!jsPass) {
        detected = true;
    } else {
        if (baitGeneric && baitAdsense && isBlocked(baitGeneric) && isBlocked(baitAdsense)) {
            detected = true;
        }
    }

    if (detected) {
        if (!isAdblockActive.value) {
            isAdblockActive.value = true;
            showModal.value = true;
        } else {
            // Aggressive Persistence Mechanism
            dynamicModalId.value = 'anti-adblock-' + Math.random().toString(36).substr(2, 9);
            if (modalWrapperRef.value) {
                modalWrapperRef.value.style.cssText = secureModalStyles;
            } else {
                showModal.value = true;
            }
        }
    } else {
        // Auto-recover if adblock was disabled on the fly
        if (isAdblockActive.value) {
            isAdblockActive.value = false;
            showModal.value = false;
        }
    }
};

const startIframeMonitor = () => {
    const RULES = [
        /height\s*:\s*1px\s*!important/i,
        /width\s*:\s*1px\s*!important/i,
        /max-height\s*:\s*1px\s*!important/i,
        /max-width\s*:\s*1px\s*!important/i
    ];

    const check = () => {
        if (isAdblockActive.value) return;
        const iframes = document.querySelectorAll('ins iframe');
        for (const iframe of iframes) {
            const style = iframe.getAttribute('style');
            if (!style) continue;

            let count = 0;
            for (const r of RULES) {
                if (r.test(style)) count++;
            }
            if (count >= 2) {
                showWarning();
                break;
            }
        }
    };

    const observer = new MutationObserver((mutations) => {
        let shouldCheck = false;
        for (const m of mutations) {
            if (m.type === 'childList') shouldCheck = true;
            else if (m.type === 'attributes' && (m.target.tagName === 'IFRAME' || m.target.tagName === 'INS')) shouldCheck = true;
        }
        if (shouldCheck) check();
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });

    return observer;
};

let iframeObserver = null;

const cleanUp = () => {
    ['ad-detection-bait', 'ad-detection-bait-ins', 'ads-verify-script'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
    });
};

onMounted(() => {
    if (page.props.auth.user?.is_admin || isRestrictedRoute.value) return;

    createBaits();
    checkState();
    
    checkIntervalId = setInterval(() => {
        checkState();
        if (!isAdblockActive.value) {
            refreshBaits();
        }
    }, 1000);

    iframeObserver = startIframeMonitor();

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            checkState();
        }
    });

    const script = document.createElement('script');
    script.src = '/js/ads-check.js?v=' + Date.now();
    script.onload = () => { setTimeout(checkState, 200); };
    script.onerror = () => { showWarning(); };
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (checkIntervalId) clearInterval(checkIntervalId);
    if (iframeObserver) iframeObserver.disconnect();
    cleanUp();
});

const verifyDisabled = () => {
    isVerifying.value = true;
    window.isAdEnabled = false;
    
    // Clear any previous attempts
    const oldScript = document.getElementById('ads-verify-script');
    if (oldScript) oldScript.remove();

    const script = document.createElement('script');
    script.id = 'ads-verify-script';
    script.src = '/js/ads-check.js?v=' + Date.now();
    
    script.onload = () => {
        setTimeout(() => {
            // Priority Check: If JS script loaded, script execution isn't blocked.
            if (window.isAdEnabled === true) {
                isAdblockActive.value = false;
                showModal.value = false;
                window.location.reload(true); // Force reload to clean up DOM/CSS remnants
            } else {
                alert("Adblock/Strict-Mode masih aktif! Silakan nonaktifkan agar website bisa diakses.");
                isVerifying.value = false;
            }
        }, 300);
    };
    
    script.onerror = () => {
        setTimeout(() => {
            alert("Akses masih terblokir! Periksa ekstensi Adblock atau mode 'Strict' pada peramban Anda.");
            isVerifying.value = false;
        }, 300);
    };
    
    document.head.appendChild(script);
};
</script>

<template>
    <div v-if="showModal">
        <div ref="modalWrapperRef" :id="dynamicModalId" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(2, 6, 23, 0.9); z-index: 999999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(16px); padding: 1rem;">
            <div class="bg-slate-900 border border-slate-800 rounded-[2rem] p-8 max-w-lg w-full text-center shadow-[0_0_100px_rgba(239,68,68,0.2)] relative overflow-hidden" style="display: block !important; visibility: visible !important; opacity: 1 !important; transform: scale(1)!important;">
                
                <!-- Background decoration -->
                <div class="absolute inset-0 bg-gradient-to-br from-red-500/10 via-transparent to-rose-500/5 pointer-events-none" style="display:block!important"></div>
                
                <div class="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-6 relative shadow-inner">
                    <div class="w-16 h-16 bg-red-500/20 border-2 border-red-500/50 rounded-full flex items-center justify-center animate-ping absolute inset-0 m-auto" style="display:block!important; animation-play-state: running!important;"></div>
                    <span class="text-4xl relative z-10" style="display:block!important">🛡️</span>
                </div>
                
                <h2 class="text-2xl font-black text-rose-500 mb-4 tracking-tight uppercase" style="display:block!important">AdBlock Terdeteksi!</h2>
                
                <p class="text-slate-300 text-sm leading-relaxed mb-8 font-medium" style="display:block!important">
                    Tampaknya Anda menggunakan pemerangkap iklan <span class="text-white font-bold">(AdBlock)</span>. Aplikasi ini bertahan dari sokongan iklan, mohon berikan pengecualian agar kami dapat tetap beroperasi untuk Anda.
                </p>
                
                <div class="flex flex-col gap-3" style="display:flex!important">
                    <button 
                        @click="verifyDisabled" 
                        :disabled="isVerifying"
                        class="w-full py-4 text-sm font-black text-white uppercase tracking-widest rounded-xl bg-gradient-to-r from-red-500 to-rose-600 hover:opacity-90 transition-all shadow-lg hover:shadow-red-500/50 disabled:opacity-50 disabled:cursor-not-allowed"
                        style="display:flex!important; justify-content:center!important; align-items:center!important;"
                    >
                        <svg v-if="isVerifying" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display:inline-block!important;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ isVerifying ? 'MEMERIKSA SITEM...' : 'SAYA SUDAH NONAKTIFKAN' }}
                    </button>
                    <p class="text-slate-500 mt-2 text-[10px]" style="display:block!important;">
                        Jika Anda yakin ini error sistem, matikan VPN atau mode ketat peramban Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
