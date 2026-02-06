import { createApp } from 'vue';

// Import global app styles (includes theme.css)
import '../css/app.css';

// 1. นำเข้า App ตัวแม่ และ Router
import App from './App.vue';
import router from './router';

// 2. สร้าง Vue App
const app = createApp(App);

// 3. ใช้งาน Router
app.use(router);

// 4. แสดงผลที่ div id="app"
app.mount('#app');