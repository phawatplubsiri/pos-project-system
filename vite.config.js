import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'

// export default defineConfig({
//     plugins: [
//         tailwindcss(),
//         laravel({
//             // บอก Vite ว่าไฟล์หลักของเราอยู่ที่ไหน
//             input: ['resources/css/app.css', 'resources/js/app.js'],
//             refresh: true,
//         }),
//         vue({
//             template: {
//                 transformAssetUrls: {
//                     // ตั้งค่าให้ Vue ดึงรูปภาพได้ถูกต้อง
//                     base: null,
//                     includeAbsolute: false,
//                 },
//             },
//         }),
//     ],
//     resolve: {
//         alias: {
//             // ช่วยให้เรียกใช้ Vue ได้สะดวกขึ้น
//             vue: 'vue/dist/vue.esm-bundler.js',
//             '@': '/resources/js',
//         },
//     },
// });

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
        },
    },
    build: {
        outDir: 'dist', // ให้ Vite build ไฟล์ไปไว้ที่โฟลเดอร์ dist สำหรับ Vercel
        rollupOptions: {
            input: 'index.html', // บังคับให้ Vite ใช้ index.html เป็นจุดเริ่มต้น
        },
    },
});