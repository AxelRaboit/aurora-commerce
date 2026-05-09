import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

// AURORA_CLIENT_DIR points to a client project's extension dir (e.g.
// aurora-client/assets/client). When set, aurora's Vite scans that dir for
// additional Vue components, controllers and CSS, exposed under the @client
// alias. Standalone aurora dev leaves the var unset; the alias then resolves
// to an empty placeholder dir so import.meta.glob('@client/...') returns {}.
const CLIENT_DIR = process.env.AURORA_CLIENT_DIR
    ? path.resolve(process.env.AURORA_CLIENT_DIR)
    : path.resolve(__dirname, 'assets/.client-fallback');

export default defineConfig({
    plugins: [
        tailwindcss(),
        vue(),
        symfonyPlugin({ stimulus: true }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'assets'),
            '@core': path.resolve(__dirname, 'assets/Core'),
            '@editorial': path.resolve(__dirname, 'assets/Module/Editorial'),
            '@crm': path.resolve(__dirname, 'assets/Module/Crm'),
            '@erp': path.resolve(__dirname, 'assets/Module/Erp'),
            '@ecommerce': path.resolve(__dirname, 'assets/Module/Ecommerce'),
            '@photo': path.resolve(__dirname, 'assets/Module/Photo'),
            '@billing': path.resolve(__dirname, 'assets/Module/Billing'),
            '@ged': path.resolve(__dirname, 'assets/Module/Ged'),
            '@hr': path.resolve(__dirname, 'assets/Module/Hr'),
            '@planning': path.resolve(__dirname, 'assets/Module/Planning'),
            '@project': path.resolve(__dirname, 'assets/Module/Project'),
            '@vault': path.resolve(__dirname, 'assets/Module/Vault'),
            '@shared': path.resolve(__dirname, 'assets/shared'),
            '@client': CLIENT_DIR,
        },
        // When client Vue files (in @client) import shared packages, Rolldown
        // walks up looking for node_modules and finds the client project's
        // (which also has these packages). Dedupe forces a single instance so
        // we don't get duplicate Vue / vue-i18n at runtime.
        dedupe: ['vue', 'vue-i18n', 'vue-sonner', 'lucide-vue-next', '@hotwired/stimulus', '@symfony/ux-vue'],
    },
    server: {
        fs: {
            allow: [path.resolve(__dirname), CLIENT_DIR],
        },
    },
    build: {
        rolldownOptions: {
            input: {
                app: './assets/app.js',
                flash: './assets/flash.js',
                theme: './assets/theme.js',
                guest: './assets/backend/guest/index.js',
            },
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('@editorjs') || id.includes('editorjs-drag-drop') || id.includes('editorjs-undo')) return 'vendor-editorjs';
                    if (id.includes('lucide-vue-next')) return 'vendor-icons';
                    if (id.includes('axios')) return 'vendor-utils';
                    if (id.includes('vue-i18n') || id.includes('vue-sonner') || id.includes('/vue/') || id.includes('/vue@')) return 'vendor-vue';
                },
            },
        },
    },
});
