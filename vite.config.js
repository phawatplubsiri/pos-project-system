import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'
import path from 'path';

export default defineConfig({
    plugins: [
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/node_modules/**', '**/vendor/**'],
        },
        hmr: {
            host: 'localhost',
        },
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: 'index.html',
        },
    },
    // เพิ่ม base เพื่อให้ Vite เข้าถึงไฟล์ได้ถูกต้องบน Vercel
    base: '/'
});
