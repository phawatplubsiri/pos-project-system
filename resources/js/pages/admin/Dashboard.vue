<template>
  <div class="dashboard-container">
    <!-- Admin Header -->
    <div class="admin-header">
      <div class="admin-icon">👷</div>
      <h1 class="admin-title">ผู้ดูแลระบบ (Admin)</h1>
      <p class="admin-subtitle">กรุณาเลือกรายการที่ต้องการทำงาน</p>
    </div>

    <!-- Feature Cards Grid -->
    <div class="cards-grid">
      <!-- POS Card -->
      <div class="feature-card" @click="$router.push('/pos')">
        <div class="card-icon cart-icon">🛒</div>
        <h3 class="card-title">ระบบขายหน้าร้าน (POS)</h3>
        <p class="card-description">เปิดโต๊ะ สั่งอาหาร เช็คบิล</p>
      </div>

      <!-- Staff Management Card -->
      <div class="feature-card" @click="$router.push('/admin/staff')">
        <div class="card-icon staff-icon">👥</div>
        <h3 class="card-title">จัดการพนักงาน</h3>
        <p class="card-description">เพิ่ม ลบ แก้ไข รายชื่อ Staff</p>
      </div>

      <!-- Inventory Card -->
      <div class="feature-card" @click="$router.push('/admin/products')">
        <div class="card-icon inventory-icon">📦</div>
        <h3 class="card-title">จัดการสินค้า & ราคา</h3>
        <p class="card-description">เมนูอาหาร สินค้า และค่าบริการ</p>
      </div>

      <!-- Reports Card -->
      <div class="feature-card" @click="$router.push('/admin/reports')">
        <div class="card-icon reports-icon">📊</div>
        <h3 class="card-title">รายงานรายได้</h3>
        <p class="card-description">สรุปยอดรายวัน & Export CSV</p>
      </div>
    </div>
    
    <!-- Logout Button -->
    <button class="logout-button" @click="logout">ออกจากระบบ</button>
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
/* Dashboard Container - Full Screen with Cream Background */
.dashboard-container {
  min-height: 100vh;
  background-color: #fff8e7; /* Cream background from theme */
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Admin Header */
.admin-header {
  text-align: center;
  margin-bottom: 60px;
}

.admin-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.admin-title {
  font-size: 36px;
  font-weight: 700;
  color: #8b4513; /* Brown from theme */
  margin: 0 0 12px 0;
}

.admin-subtitle {
  font-size: 18px;
  color: #a0522d; /* Light brown */
  margin: 0;
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 240px));
  gap: 24px;
  max-width: 1100px;
  width: 100%;
  justify-content: center;
  margin-bottom: 50px;
}

/* Feature Card */
.feature-card {
  background-color: #ffffff;
  border: 2px solid #deb887; /* Tan border */
  border-radius: 16px;
  padding: 40px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(139, 69, 19, 0.08);
  min-height: 240px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 20px rgba(139, 69, 19, 0.15);
  border-color: #ff8c42; /* Orange border on hover */
}

/* Card Icons with Different Colors */
.card-icon {
  font-size: 72px;
  margin-bottom: 20px;
  filter: grayscale(0);
}

.cart-icon {
  color: #8b4513; /* Brown */
}

.staff-icon {
  color: #6a5acd; /* Purple-ish */
}

.inventory-icon {
  color: #d4a574; /* Tan/Gold */
}

.reports-icon {
  color: #ff8c42; /* Orange */
}

/* Card Title */
.card-title {
  font-size: 18px;
  font-weight: 700;
  color: #654321; /* Dark brown */
  margin: 0 0 12px 0;
  line-height: 1.4;
}

/* Card Description */
.card-description {
  font-size: 14px;
  color: #a0522d; /* Light brown */
  margin: 0;
  line-height: 1.5;
}

/* Logout Button */
.logout-button {
  padding: 14px 40px;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  background-color: #8b4513; /* Brown from theme */
  border: none;
  border-radius: 24px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.2);
  margin-top: 20px;
}

.logout-button:hover {
  background-color: #a0522d;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(139, 69, 19, 0.3);
}

.logout-button:active {
  transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 40px 20px;
  }

  .admin-title {
    font-size: 28px;
  }

  .admin-icon {
    font-size: 48px;
  }

  .cards-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
  }

  .feature-card {
    padding: 30px 20px;
    min-height: 200px;
  }

  .card-icon {
    font-size: 56px;
  }

  .card-title {
    font-size: 16px;
  }

  .card-description {
    font-size: 13px;
  }
}

@media (max-width: 480px) {
  .cards-grid {
    grid-template-columns: 1fr;
    max-width: 300px;
  }
}
</style>
