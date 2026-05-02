import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        if (id.includes('chart.js') || id.includes('vue-chartjs')) {
                            return 'vendor-charts';
                        }
                        if (id.includes('axios') || id.includes('lodash')) {
                            return 'vendor-utils';
                        }
                        if (id.includes('@inertiajs') || id.includes('vue')) {
                            return 'vendor-core';
                        }
                        return 'vendor';
                    }
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
});
