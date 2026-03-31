<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    adCode: {
        type: String,
        default: ''
    },
    type: {
        type: String, // banner, popunder, social_bar
        default: 'banner'
    },
    isIsolated: {
        type: Boolean,
        default: false
    }
});

const adContainer = ref(null);
const injectedElements = ref([]);

const cleanUp = () => {
    injectedElements.value.forEach(el => {
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
    });
    injectedElements.value = [];
    if (adContainer.value) {
        adContainer.value.innerHTML = '';
    }
};

const injectIsolated = () => {
    if (!adContainer.value) return;

    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = 'none';
    iframe.style.overflow = 'hidden';
    iframe.scrolling = 'no';
    
    // Set initial height low, resize will expand
    iframe.style.minHeight = '60px'; 

    adContainer.value.appendChild(iframe);
    injectedElements.value.push(iframe);

    const doc = iframe.contentWindow.document;
    doc.open();
    
    // Process the adCode to proxy scripts
    let processedCode = props.adCode;
    const scriptRegex = /<script\b[^>]*src=["']([^"']+)["'][^>]*>([\s\S]*?)<\/script>/gi;
    
    processedCode = processedCode.replace(scriptRegex, (match, src, content) => {
        if (src.includes('adsterra') || src.includes('exdynsrv.com') || src.includes('recollectsideway.com')) {
            const fullUrl = src.startsWith('//') ? 'https:' + src : src;
            const encodedUrl = btoa(fullUrl);
            const proxiedSrc = `/assets/js/system-v4.js?u=${encodedUrl}&v=${Date.now()}`;
            return `<script src="${proxiedSrc}">${content}<\/script>`;
        }
        return match;
    });

    doc.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; overflow: hidden; display: flex; justify-content: center; background: transparent; }
                #container-wrapper { width: 100%; display: flex; justify-content: center; }
            </style>
        </head>
        <body>
            <div id="container-wrapper">
                ${processedCode}
            </div>
            <script>
                // Auto-resize iframe based on content
                window.onload = function() {
                    const resize = () => {
                        const height = document.body.scrollHeight;
                        if (height > 0) window.frameElement.style.height = height + "px";
                    };
                    resize();
                    setInterval(resize, 2000); // Periodic resize check for dynamic ads
                };
            <\/script>
        </body>
        </html>
    `);
    doc.close();
};

const injectStandard = () => {
    if (!props.adCode || !adContainer.value) return;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = props.adCode.trim();

    const scripts = Array.from(tempDiv.getElementsByTagName('script'));
    const otherElements = Array.from(tempDiv.childNodes).filter(node => node.nodeName !== 'SCRIPT');

    // Determine target
    let target = adContainer.value;
    if (props.type === 'popunder') target = document.head;
    if (props.type === 'social_bar') target = document.body;

    if (!target) return;

    // Handle non-script parts
    otherElements.forEach(node => {
        const cloned = node.cloneNode(true);
        target.appendChild(cloned);
        injectedElements.value.push(cloned);
    });

    // Handle scripts
    scripts.forEach(script => {
        const newScript = document.createElement('script');
        
        // Copy attributes
        Array.from(script.attributes).forEach(attr => {
            let value = attr.value;
            // Proxy Adsterra scripts
            if (attr.name === 'src' && (value.includes('adsterra') || value.includes('exdynsrv.com') || value.includes('recollectsideway.com'))) {
                const fullUrl = value.startsWith('//') ? 'https:' + value : value;
                const encodedUrl = btoa(fullUrl);
                value = `/assets/js/system-v4.js?u=${encodedUrl}&v=${Date.now()}`;
            }
            newScript.setAttribute(attr.name, value);
        });

        // Copy content
        if (script.innerHTML) {
            newScript.innerHTML = script.innerHTML;
        }

        target.appendChild(newScript);
        injectedElements.value.push(newScript);
    });
};

const handleInjection = () => {
    // SECURITY: Never inject ads for Admin or Premium users
    const page = usePage();
    const user = page.props.auth.user;
    
    if (user?.is_admin || user?.has_active_subscription) {
        cleanUp();
        return;
    }

    cleanUp();
    if (props.isIsolated && props.type === 'banner') {
        injectIsolated();
    } else {
        injectStandard();
    }
};

onMounted(() => {
    handleInjection();
});

onUnmounted(() => {
    cleanUp();
});

watch(() => props.adCode, () => {
    handleInjection();
});

// Watch for auth changes (like login/logout)
watch(() => page.props.auth.user, () => {
    handleInjection();
});
</script>

<template>
    <div :class="['ad-handler-wrapper', props.type]" ref="adContainer">
        <!-- Ad will be injected here -->
    </div>
</template>

<style scoped>
.ad-handler-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 20px;
    width: 100%;
}

.banner { width: 100%; }
.social_bar { position: fixed; bottom: 0; left: 0; z-index: 9999; }
</style>
