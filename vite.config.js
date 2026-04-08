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
    server: {
        host: '0.0.0.0', // ให้ Docker เข้าถึงได้
        port: 5173,      // พอร์ตภายใน Container
        strictPort: true, // บังคับใช้พอร์ตนี้
        hmr: {
            host: 'localhost', // พอร์ตที่ Browser จะใช้เชื่อมต่อ
            clientPort: 5173,  // บังคับให้ Client ใช้พอร์ต 5173 สำหรับ WebSocket
        },
        watch: {
            usePolling: true, // แก้ปัญหา Hot Reload ไม่ทำงานใน Docker/Windows
        },
    },
});