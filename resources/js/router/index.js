import { createRouter, createWebHistory } from 'vue-router';

// Import หน้าต่างๆ
import Login from '../pages/staff/Login.vue';
import Pos from '../pages/Pos.vue';
import Order from '../pages/Order.vue';

// สร้าง 2 หน้าใหม่สำหรับ Admin (เดี๋ยวเราไปสร้างไฟล์กัน)
import AdminDashboard from '../pages/admin/Dashboard.vue';
import StaffManage from '../pages/admin/StaffManage.vue';
import ProductManage from '../pages/admin/ProductManage.vue';
import Report from '../pages/admin/Report.vue'; // <--- Import หน้า Report
import LandingPage from '../pages/customer/LandingPage.vue';
import ThemeExample from '../pages/ThemeExample.vue';

const routes = [
    // 1. หน้าแรก เป็น Login เลย
    { 
        path: '/', 
        name: 'login',
        component: Login 
    },
    { 
        path: '/theme-example', 
        name: 'theme-example',
        component: ThemeExample 
    },
    {                                                                                                                               
        path: '/pos',                                                                                                               
        name: 'pos',                                                                                                                
        component: Pos                                                                                                              
    },                                                                                                                              
    {                                                                                                                               
        path: '/order/:id',                                                                                                         
        name: 'order',                                                                                                              
        component: Order                                                                                                            
    },                                                                                                                              
    // 3. หน้าสำหรับ Admin                                                                                                                                                                                                            
    {                                                                                                                               
        path: '/admin/dashboard',                                                                                                   
        name: 'admin-dashboard',                                                                                                    
        component: AdminDashboard                                                                                                   
    },                                                                                                                              
    {                                                                                                                               
        path: '/admin/staff',                                                                                                       
        name: 'staff-manage',                                                                                                       
        component: StaffManage                                                                                                      
    }, 
    // ... (routes อื่นๆ คงเดิม)
    {
        path: '/admin/products',
        name: 'product-manage',
        component: ProductManage
    },
    // เพิ่ม Route ใหม่ตรงนี้
    {
        path: '/admin/reports',
        name: 'admin-reports',
        component: Report
    },
    {
        path: '/customer/menu',
        name: 'customer-menu',
        component: LandingPage
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// (Optional) เพิ่ม Guard ป้องกันคนไม่ได้ Login เข้ามา
router.beforeEach((to, from, next) => {
    const publicPages = ['/'];
    const authRequired = !publicPages.includes(to.path);
    const loggedIn = localStorage.getItem('token');

    if (authRequired && !loggedIn) {
        next('/');
    } else {
        next();
    }
});

export default router;