import { onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useAutoLogout(timeoutMinutes = 30) {
    let timeoutId = null;
    const timeoutMs = timeoutMinutes * 60 * 1000;
    const page = usePage();

    const isVideoPlaying = () => {
        const videos = document.querySelectorAll('video');
        for (let i = 0; i < videos.length; i++) {
            if (!videos[i].paused && !videos[i].ended) {
                return true;
            }
        }
        return false;
    };

    const checkAndLogout = () => {
        // Double check auth status
        if (!page.props.auth.user) return;

        if (isVideoPlaying()) {
            // If a video is playing, defer the logout
            resetTimer(); 
        } else {
            router.post(route('logout'));
        }
    };

    let lastReset = 0;
    const resetTimer = () => {
        if (!page.props.auth.user) return;

        const now = Date.now();
        if (now - lastReset < 2000) return; // Throttle to once every 2 seconds
        lastReset = now;

        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(checkAndLogout, timeoutMs);
    };

    const events = ['mousemove', 'keydown', 'mousedown', 'scroll', 'touchstart'];

    onMounted(() => {
        if (page.props.auth.user) {
            events.forEach(event => document.addEventListener(event, resetTimer, { passive: true }));
            resetTimer(); // Start timer immediately
        }
    });

    onUnmounted(() => {
        if (timeoutId) clearTimeout(timeoutId);
        events.forEach(event => document.removeEventListener(event, resetTimer));
    });
}
