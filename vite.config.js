import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

export default defineConfig({
    plugins: [
        tailwindcss(),
        vue(),
        symfonyPlugin(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'assets'),
        },
    },
    build: {
        rolldownOptions: {
            input: {
                app: './assets/app.js',
                sidebar: './assets/sidebar.js',
                flash: './assets/flash.js',
                theme: './assets/theme.js',
                guest: './assets/guest/index.js',
                register: './assets/auth/register/index.js',
                // Admin pages
                'admin/dashboard': './assets/admin/dashboard/index.js',
                'admin/posts': './assets/admin/posts/index.js',
                'admin/editor': './assets/admin/editor/index.js',
                'admin/media': './assets/admin/media/index.js',
                'admin/menus': './assets/admin/menus/index.js',
                'admin/post-types': './assets/admin/post-types/index.js',
                'admin/profile': './assets/admin/profile/index.js',
                'admin/administration': './assets/admin/administration/index.js',
                'admin/tags': './assets/admin/tags/index.js',
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
