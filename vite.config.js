import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            // บอก Vite ว่าไฟล์หลักของเราอยู่ที่ไหน
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    // ตั้งค่าให้ Vue ดึงรูปภาพได้ถูกต้อง
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            // ช่วยให้เรียกใช้ Vue ได้สะดวกขึ้น
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
        },
    },
});