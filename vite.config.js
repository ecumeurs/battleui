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
    ], server: {
        host: '0.0.0.0', // Écoute sur tout le réseau du container
        port: 5173,      // Ensure this matches your docker-compose port
        strictPort: true,
        hmr: {
            host: 'localhost', // Force le navigateur à utiliser localhost pour le hot reload
        },
    },
});
