<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] py-15 px-10 flex flex-col items-center font-[Sarabun,-apple-system,BlinkMacSystemFont,'Segoe_UI',sans-serif]">
    <!-- Admin Header -->
    <div class="text-center mb-7.5">
      <div class="flex flex-col items-center text-[64px] text-[var(--color-primary)]">
        <UserCog :size="64" />
      </div>
      <h1 class="text-4xl font-bold text-[var(--color-primary)] m-0 mb-3">ผู้ดูแลระบบ (Admin)</h1>
      <p class="text-lg text-[var(--color-text-secondary)] m-0">กรุณาเลือกรายการที่ต้องการทำงาน</p>
    </div>

    <!-- Feature Cards Grid -->
    <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,240px))] gap-6 max-w-[1100px] w-full justify-center mb-12">
      <!-- POS Card -->
      <div class="feature-card" @click="$router.push('/pos')">
        <div class="mb-5 text-[var(--color-primary)]">
          <ShoppingCart :size="72" />
        </div>
        <h3 class="text-lg font-bold text-[var(--color-text-primary)] m-0 mb-3 leading-snug">ระบบขายหน้าร้าน (POS)</h3>
        <p class="text-sm text-[var(--color-text-secondary)] m-0 leading-relaxed">เปิดโต๊ะ สั่งอาหาร เช็คบิล</p>
      </div>

      <!-- Staff Management Card -->
      <div class="feature-card" @click="$router.push('/admin/staff')">
        <div class="mb-5 text-[var(--color-action)]">
          <Users :size="72" />
        </div>
        <h3 class="text-lg font-bold text-[var(--color-text-primary)] m-0 mb-3 leading-snug">จัดการพนักงาน</h3>
        <p class="text-sm text-[var(--color-text-secondary)] m-0 leading-relaxed">เพิ่ม ลบ แก้ไข รายชื่อ Staff</p>
      </div>

      <!-- Inventory Card -->
      <div class="feature-card" @click="$router.push('/admin/products')">
        <div class="mb-5 text-[var(--color-highlight)]">
          <Package :size="72" />
        </div>
        <h3 class="text-lg font-bold text-[var(--color-text-primary)] m-0 mb-3 leading-snug">จัดการสินค้า ค่าบริการ</h3>
        <p class="text-sm text-[var(--color-text-secondary)] m-0 leading-relaxed">เมนูอาหาร สินค้า และค่าบริการ</p>
      </div>

      <!-- Reports Card -->
      <div class="feature-card" @click="$router.push('/admin/reports')">
        <div class="mb-5 text-[var(--color-warning)]">
          <BarChart3 :size="72" />
        </div>
        <h3 class="text-lg font-bold text-[var(--color-text-primary)] m-0 mb-3 leading-snug">รายงานรายได้</h3>
        <p class="text-sm text-[var(--color-text-secondary)] m-0 leading-relaxed">สรุปยอดรายวัน & Export CSV</p>
      </div>
    </div>
    
    <!-- Logout Button -->
    <button class="py-3.5 px-10 text-base font-semibold text-white bg-[var(--color-primary)] border-none rounded-3xl cursor-pointer transition-all duration-300 shadow-[var(--shadow-md)] flex items-center justify-center gap-2.5 hover:bg-[var(--color-primary-dark)] hover:-translate-y-0.5 hover:shadow-[var(--shadow-lg)] active:translate-y-0" @click="logout">
      <LogOut :size="18" />
      ออกจากระบบ
    </button>
  </div>
</template>

<script>
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { 
  UserCog, 
  ShoppingCart, 
  Users, 
  Package, 
  BarChart3, 
  LogOut 
} from 'lucide-vue-next';

export default {
  components: {
    UserCog,
    ShoppingCart,
    Users,
    Package,
    BarChart3,
    LogOut
  },
  setup() {
    const router = useRouter();
    const { loading: showAlertLoading, close: closeAlert } = useAlert();

    const logout = async () => {
        showAlertLoading('กำลังออกจากระบบ...');
        try {
            await axios.post('/api/logout', {}, {
                headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
            });
        } catch (e) {}
        
        localStorage.clear();
        setTimeout(() => {
            router.push('/');
            closeAlert();
        }, 800);
    };

    return { logout };
  }
};
</script>

<style scoped>
.feature-card {
  background-color: var(--color-table-row);
  border: 2px solid var(--color-table-border);
  border-radius: 16px;
  padding: 40px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
  min-height: 240px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-action);
}

@media (max-width: 768px) {
  .feature-card {
    padding: 30px 20px;
    min-height: 200px;
  }
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
    max-width: 300px;
  }
}
</style>
