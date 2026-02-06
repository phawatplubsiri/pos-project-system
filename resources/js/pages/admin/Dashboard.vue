<template>
  <div class="container">
    <h1>👨‍💼 ผู้ดูแลระบบ (Admin)</h1>
    <p>กรุณาเลือกรายการที่ต้องการทำ</p>

    <div class="menu-grid">
      <div class="card" @click="$router.push('/pos')">
        <div class="icon">🛒</div>
        <h3>ระบบขายหน้าร้าน (POS)</h3>
        <p>เปิดโต๊ะ สั่งอาหาร เช็คบิล</p>
      </div>

      <div class="card" @click="$router.push('/admin/staff')">
        <div class="icon">👥</div>
        <h3>จัดการพนักงาน</h3>
        <p>เพิ่ม ลบ แก้ไข รายชื่อ Staff</p>
      </div>

      <div class="card" @click="$router.push('/admin/products')">
        <div class="icon">📦</div>
        <h3>จัดการสินค้า & ราคา</h3>
        <p>เมนูอาหาร สินค้า และค่าบริการ</p>
      </div>

      <div class="card" @click="$router.push('/admin/reports')">
        <div class="icon">📊</div>
        <h3>รายงานรายได้</h3>
        <p>สรุปยอดรายวัน & Export CSV</p>
      </div>
    </div>
    
    <button class="logout-btn" @click="logout">ออกจากระบบ</button>
  </div>
</template>

<script>
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const router = useRouter();

    const logout = async () => {
        try {
            await axios.post('/api/logout', {}, {
                headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
            });
        } catch (e) {}
        
        localStorage.clear();
        router.push('/');
    };

    return { logout };
  }
};
</script>

<style scoped>
.container { padding: 40px; text-align: center; font-family: 'Sarabun', sans-serif; }
.menu-grid { display: flex; justify-content: center; gap: 30px; margin-top: 30px; }
.card { 
    border: 1px solid #ddd; padding: 30px; width: 250px; cursor: pointer; 
    border-radius: 10px; transition: 0.2s; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.card:hover { transform: translateY(-5px); border-color: #007bff; }
.icon { font-size: 3rem; margin-bottom: 10px; }
h3 { margin: 10px 0; color: #333; }
.logout-btn { margin-top: 50px; padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; }
</style>