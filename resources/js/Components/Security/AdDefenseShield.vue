<script setup>
import { onMounted, onUnmounted } from 'vue';

/**
 * AdDefenseShield
 * Aggressively scans and removes any ad-related elements from the DOM.
 * Used in Admin and Auth layouts to ensure zero ad leakage.
 */

const adLeakers = [
    'script[src*="adsterra"]',
    'script[src*="exdynsrv"]',
    'script[src*="recollectsideway"]',
    'script[src*="system-v4.js"]',
    'iframe[src*="exdynsrv"]',
    'iframe[id*="aswgv"]',
    'div[id*="container-"]',
    'ins.adsbygoogle',
    '.ad-handler-wrapper'
];

const purge = () => {
    adLeakers.forEach(selector => {
        try {
            document.querySelectorAll(selector).forEach(el => {
                console.warn('[AdDefense] Purged leaking ad element:', selector);
                el.remove();
            });
        } catch (e) {}
    });
    
    // Also clear interval/timeout leaks if possible (Adsterra scripts often use them)
    // We can't easily clear all intervals without tracking, but we can clear common ones if we find them
};

let observer = null;

onMounted(() => {
    purge(); // Immediate purge on mount
    
    // Level 2: MutationObserver for real-time protection without polling
    observer = new MutationObserver((mutations) => {
        let shouldPurge = false;
        for (const m of mutations) {
            if (m.addedNodes.length > 0) {
                shouldPurge = true;
                break;
            }
        }
        if (shouldPurge) purge();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <!-- Invisible Guard -->
    <div class="ad-defense-shield hidden" aria-hidden="true"></div>
</template>
