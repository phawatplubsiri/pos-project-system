<template>
  <div class="pos-page">
    <!-- Header -->
    <div class="pos-header">
      <div class="header-container">
        <div class="header-left">
          <h1 class="cafe-title">☕ Board Game Cafe</h1>
        </div>
        
        <div class="header-right">
          <button v-if="pendingOrders.length > 0" @click="showPendingModal = true" class="btn-notification">
            🔔 <span class="notification-count">{{ pendingOrders.length }}</span> ออเดอร์ใหม่
          </button>
          <div class="staff-info">
            <span class="staff-label">พนักงาน:</span>
            <strong class="staff-name">{{ user.name }}</strong>
          </div>
          <button v-if="user.role === 'admin'" @click="$router.push('/admin/dashboard')" class="btn-header">
            📊 Dashboard
          </button>
          <button @click="logout" class="btn-header">
            🚪 ออกจากระบบ
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="pos-content">
      <div class="content-header">
        <h2 class="section-title">ผังร้าน</h2>
        <p class="section-subtitle">เลือกโต๊ะเพื่อเปิดบิลหรือจัดการออเดอร์</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Table Grid -->
      <div v-else class="table-grid">
        <div v-for="table in tables" :key="table.id" class="table-card-wrapper">
          <div
            class="table-card"
            :class="{
              'table-available': table.status?.toLowerCase() === 'available',
              'table-busy': table.status?.toLowerCase() === 'busy'
            }"
            @click="handleTableClick(table)"
          >
            <!-- Pending Badge -->
            <div v-if="getTablePendingCount(table.id) > 0" class="pending-badge">
              {{ getTablePendingCount(table.id) }}
            </div>

            <!-- Table Info -->
            <div class="table-number">{{ table.name }}</div>
            
            <div class="table-seats">
              <span class="seats-icon">👥</span>
              <span>{{ table.seat_count }} ที่นั่ง</span>
            </div>

            <!-- Status Badge -->
            <div class="table-status">
              <span v-if="table.status?.toLowerCase() === 'available'" class="status-badge status-available">
                ✓ ว่าง
              </span>
              <span v-else class="status-badge status-busy">
                ● ไม่ว่าง
              </span>
            </div>

            <!-- Timer for Busy Tables -->
            <div v-if="table.status?.toLowerCase() === 'busy' && table.active_session" class="table-timer">
              <div class="timer-label">เวลาใช้งาน</div>
              <div class="timer-value">⏱️ {{ formatDuration(table.active_session.start_time) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Orders Modal -->
    <div v-if="showPendingModal" class="modal-overlay" @click.self="showPendingModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>🔔 ยืนยันรายการอาหารจากลูกค้า</h3>
        </div>
        <div class="modal-body">
          <div class="pending-orders-list">
            <div v-for="order in pendingOrders" :key="order.id" class="pending-order-item">
              <div class="order-info">
                <div class="order-table">โต๊ะ {{ order.session?.table?.name }}</div>
                <div class="order-detail">{{ order.product?.name }} × {{ order.quantity }}</div>
                <div class="order-time">{{ formatTime(order.created_at) }}</div>
              </div>
              <div class="order-actions">
                <button @click="confirmOrder(order.id, 'cancelled')" class="btn-reject">ปฏิเสธ</button>
                <button @click="confirmOrder(order.id, 'pending')" class="btn-confirm">ยืนยัน</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPendingModal = false" class="btn-close-modal">ปิด</button>
        </div>
      </div>
    </div>

    <!-- Open Table Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card modal-small">
        <div class="modal-header">
          <h3>เปิดโต๊ะ {{ targetTable?.name }}</h3>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">จำนวนลูกค้า (คน)</label>
            <div class="pax-control">
              <button @click="pax > 1 ? pax-- : null" class="btn-pax" type="button">-</button>
              <input type="number" v-model.number="pax" min="1" class="form-input pax-input" />
              <button @click="pax++" class="btn-pax" type="button">+</button>
            </div>
          </div>
          
          <div class="form-check">
            <input type="checkbox" v-model="isDayPass" id="isDayPass" class="checkbox-input">
            <label for="isDayPass" class="checkbox-label">🎟️ 1 Day Pass (เหมาวัน)</label>
          </div>

          <div v-if="pax > targetTable?.seat_count" class="warning-message">
            ⚠️ ลูกค้า {{ pax }} คน เกินจำนวนที่นั่ง ({{ targetTable?.seat_count }})
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">ยกเลิก</button>
          <button @click="confirmOpenTable" class="btn-primary">✅ ยืนยัน</button>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="closeQrModal">
      <div class="modal-card modal-small">
        <div class="modal-header">
          <h3>📱 สแกนเพื่อสั่งอาหาร</h3>
        </div>
        <div class="modal-body text-center">
          <p class="qr-table-name">โต๊ะ: <strong>{{ targetTable?.name }}</strong></p>
          <div class="qr-code-wrapper">
            <QrcodeVue :value="qrUrl" :size="220" level="H" />
          </div>
          <p class="qr-instruction">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
        </div>
        <div class="modal-footer">
          <button @click="closeQrModal" class="btn-primary full-width">ปิด</button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toastMsg" class="toast-notification">
      {{ toastMsg }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import QrcodeVue from 'qrcode.vue';

export default {
  components: {
    QrcodeVue
  },
  setup() {
    const router = useRouter();
    const user = ref({});
    const currentTime = ref(new Date());
    let timerInterval = null;
    let pollingInterval = null;

    try {
        const storedUser = localStorage.getItem('user');
        if (storedUser) user.value = JSON.parse(storedUser);
    } catch (e) {}

    const tables = ref([]);
    const loading = ref(true);
    const pendingOrders = ref([]);
    const showPendingModal = ref(false);
    const toastMsg = ref('');

    // Modal 1: เปิดโต๊ะ
    const showModal = ref(false);
    const targetTable = ref(null);
    const pax = ref(1);
    const isDayPass = ref(false);

    // Modal 2: QR Code
    const showQrModal = ref(false);
    const qrUrl = ref(''); 

    const fetchPendingOrders = async () => {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/orders/pending-confirmations', {
          headers: { Authorization: `Bearer ${token}` }
        });
        
        if (response.data.length > pendingOrders.value.length) {
          showToast('🔔 มีออเดอร์ใหม่จากลูกค้า!');
        }
        pendingOrders.value = response.data;
      } catch (error) {
        console.error("Fetch pending error", error);
      }
    };

    const showToast = (msg) => {
      toastMsg.value = msg;
      setTimeout(() => { toastMsg.value = ''; }, 3000);
    };

    const confirmOrder = async (orderId, status) => {
      try {
        const token = localStorage.getItem('token');
        await axios.put(`/api/orders/${orderId}/status`, { status }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        showToast(status === 'pending' ? '✅ ยืนยันออเดอร์แล้ว' : '❌ ปฏิเสธออเดอร์แล้ว');
        fetchPendingOrders();
        fetchTables();
      } catch (error) {
        alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
      }
    };

    const getTablePendingCount = (tableId) => {
      return pendingOrders.value.filter(o => o.session?.table_id === tableId).length;
    };

    const formatTime = (timeStr) => {
      if (!timeStr) return '';
      return new Date(timeStr).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    };

    const fetchTables = async () => {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/tables', {
          headers: { Authorization: `Bearer ${token}` }
        });
        tables.value = response.data;
      } catch (error) {
        if (error.response && error.response.status === 401) logout();
      } finally {
        loading.value = false;
      }
    };

    const formatDuration = (startTime) => {
        if (!startTime) return '00:00:00';
        const start = new Date(startTime);
        const diff = Math.floor((currentTime.value - start) / 1000);
        
        if (diff < 0) return '00:00:00';

        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;

        return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
    };

    const handleTableClick = (table) => {
      if (table.status?.toLowerCase() === 'busy') {
        router.push(`/order/${table.id}`);
        return;
      }
      targetTable.value = table;
      pax.value = 1;
      isDayPass.value = false;
      showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
    };

    const confirmOpenTable = async () => {
        if (pax.value > targetTable.value.seat_count) {
            const proceed = confirm(`⚠️ จำนวนลูกค้า (${pax.value} คน) เกินที่นั่งของโต๊ะนี้ (${targetTable.value.seat_count} ที่นั่ง)\nคุณยังต้องการที่จะเปิดโต๊ะนี้ใช่หรือไม่?`);
            if (!proceed) return;
        }

        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(`/api/tables/${targetTable.value.id}/open`, {
                pax: pax.value,
                is_day_pass: isDayPass.value
            }, {
                headers: { Authorization: `Bearer ${token}` }
            });

            showModal.value = false;
            const sessionData = response.data.session; 
            const guestToken = sessionData.guest_token;
            const baseUrl = window.location.origin;
            qrUrl.value = `${baseUrl}/customer/menu?token=${guestToken}`;
            showQrModal.value = true;
            fetchTables(); 
        } catch (error) {
            alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
        }
    };

    const closeQrModal = () => {
        showQrModal.value = false;
        targetTable.value = null;
    };

    const logout = () => {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('role');
      router.push('/staff/login');
    };

    onMounted(() => {
      fetchTables();
      fetchPendingOrders();
      timerInterval = setInterval(() => {
          currentTime.value = new Date();
      }, 1000);
      pollingInterval = setInterval(() => {
        fetchPendingOrders();
        fetchTables();
      }, 5000);
    });

    onUnmounted(() => {
      if (timerInterval) clearInterval(timerInterval);
      if (pollingInterval) clearInterval(pollingInterval);
    });

    return { 
        user, tables, loading, logout, 
        handleTableClick, confirmOpenTable,
        showModal, targetTable, pax, isDayPass, closeModal,
        showQrModal, qrUrl, closeQrModal,
        formatDuration, 
        pendingOrders, showPendingModal, confirmOrder, getTablePendingCount, formatTime, toastMsg
    };
  }
};
</script>

<style scoped>
/* ========== Global Styles ========== */
.pos-page {
  min-height: 100vh;
  background: var(--color-bg-primary);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ========== Header ========== */
.pos-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.cafe-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-highlight-light);
  margin: 0;
  letter-spacing: -0.5px;
}



.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-notification {
  background: linear-gradient(135deg, var(--color-danger) 0%, var(--color-primary-dark) 100%);
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(229, 83, 61, 0.3);
  animation: pulse 2s infinite;
}

.btn-notification:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(229, 83, 61, 0.4);
}

.notification-count {
  background: white;
  color: var(--color-danger);
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 700;
  margin: 0 4px;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.btn-header {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-header:hover {
  background: rgba(255, 255, 255, 0.25);
}

.staff-info {
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
}

.staff-name {
  color: white;
}

.btn-logout {
  background: rgba(220, 53, 69, 0.2);
  color: white;
  border: 1px solid rgba(220, 53, 69, 0.4);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-logout:hover {
  background: #DC3545;
  border-color: #DC3545;
}

/* ========== Main Content ========== */
.pos-content {
  max-width: 1600px;
  margin: 0 auto;
  padding: 32px;
}

.content-header {
  margin-bottom: 32px;
}

.section-title {
  font-size: 32px;
  font-weight: 700;
  color: var(--color-accent);
  margin: 0 0 8px 0;
  position: relative;
  display: inline-block;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 60%;
  height: 4px;
  background: linear-gradient(90deg, var(--color-primary) 0%, transparent 100%);
  border-radius: 2px;
}

.section-subtitle {
  font-size: 16px;
  color: var(--color-accent-light);
  margin: 0;
}

/* ========== Loading State ========== */
.loading-state {
  text-align: center;
  padding: 80px 20px;
  color: var(--color-accent-light);
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--color-secondary-dark);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ========== Table Grid ========== */
.table-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 24px;
}

/* ========== Table Cards ========== */
.table-card {
  background: var(--color-table-row);
  border-radius: 16px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-sm);
  position: relative;
  min-height: 180px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.table-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-md);
}

.table-available {
  border: 3px solid var(--color-success-light);
}

.table-available:hover {
  border-color: var(--color-success);
  box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
}

.table-busy {
  border: 3px solid var(--color-danger-light);
}

.table-busy:hover {
  border-color: var(--color-danger);
  box-shadow: 0 8px 24px rgba(229, 83, 61, 0.3);
}

.pending-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background: var(--color-danger);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
  animation: bounce 1s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.table-number {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-accent);
  margin-bottom: 4px;
}

.table-seats {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--color-accent-light);
  font-size: 15px;
}

.seats-icon {
  font-size: 18px;
}

.table-status {
  margin-top: auto;
}

.status-badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 13px;
}

.status-available {
  background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success) 100%);
  color: white;
}

.status-busy {
  background: linear-gradient(135deg, var(--color-danger-light) 0%, var(--color-danger) 100%);
  color: white;
}

.table-timer {
  margin-top: 8px;
  padding-top: 12px;
  border-top: 1px solid var(--color-secondary-dark);
}

.timer-label {
  font-size: 11px;
  color: var(--color-accent-light);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.timer-value {
  font-family: 'Courier New', monospace;
  font-size: 16px;
  font-weight: 700;
  color: var(--color-danger);
}

/* ========== Modals ========== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-card {
  background: white;
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

.modal-small {
  max-width: 400px;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 24px 24px 16px;
  border-bottom: 1px solid var(--color-secondary-dark);
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: var(--color-accent);
}

.modal-body {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--color-secondary-dark);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Pending Orders List */
.pending-orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pending-order-item {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-secondary-dark);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.order-info {
  flex: 1;
}

.order-table {
  font-weight: 700;
  color: var(--color-accent);
  margin-bottom: 4px;
}

.order-detail {
  color: var(--color-text-secondary);
  font-size: 14px;
}

.order-time {
  color: var(--color-text-light);
  font-size: 12px;
  margin-top: 4px;
}

.order-actions {
  display: flex;
  gap: 8px;
}

.btn-reject,
.btn-confirm {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: var(--transition-normal);
  border: none;
}

.btn-reject {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.btn-reject:hover {
  background: var(--color-danger);
  color: white;
}

.btn-confirm {
  background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%);
  color: white;
}

.btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
}

/* Form Elements */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: var(--color-accent);
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid var(--color-secondary-dark);
  border-radius: 8px;
  font-size: 16px;
  transition: var(--transition-normal);
  text-align: center;
}

.pax-control {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-pax {
  width: 45px;
  height: 45px;
  border-radius: 8px;
  border: 2px solid var(--color-secondary-dark);
  background: white;
  color: var(--color-accent);
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-pax:hover {
  background: var(--color-secondary);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.pax-input {
  flex: 1;
  margin: 0;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
}

.form-check {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-label {
  cursor: pointer;
  user-select: none;
  font-size: 14px;
  color: var(--color-text-secondary);
}

.warning-message {
  background: #FFF3CD;
  border: 1px solid #FFE69C;
  color: #856404;
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  margin-top: 16px;
}

/* Modal Buttons */
.btn-primary,
.btn-secondary,
.btn-close-modal {
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-normal);
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
}

.btn-secondary {
  background: var(--color-secondary-dark);
  color: var(--color-accent);
}

.btn-secondary:hover {
  background: #D2C4A8;
}

.btn-close-modal {
  background: var(--color-accent);
  color: white;
}

.btn-close-modal:hover {
  background: var(--color-accent-light);
}

.full-width {
  width: 100%;
}

/* QR Code */
.qr-table-name {
  font-size: 16px;
  color: var(--color-text-secondary);
  margin-bottom: 16px;
}

.qr-table-name strong {
  color: var(--color-accent);
  font-size: 18px;
}

.qr-code-wrapper {
  background: white;
  border: 3px solid var(--color-secondary-dark);
  border-radius: 12px;
  padding: 20px;
  display: inline-block;
  margin-bottom: 16px;
}

.qr-instruction {
  color: var(--color-text-light);
  font-size: 13px;
  margin: 0;
}

.text-center {
  text-align: center;
}

/* ========== Toast Notification ========== */
.toast-notification {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-accent);
  color: white;
  padding: 16px 32px;
  border-radius: 24px;
  box-shadow: var(--shadow-lg);
  z-index: 2000;
  animation: slideDown 0.3s ease;
  font-weight: 600;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translate(-50%, -20px);
  }
  to {
    opacity: 1;
    transform: translate(-50%, 0);
  }
}

/* ========== Responsive ========== */
@media (max-width: 1024px) {
  .table-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .header-container {
    padding: 16px 20px;
  }

  .cafe-title {
    font-size: 22px;
  }

  .system-badge {
    display: none;
  }

  .header-right {
    width: 100%;
    justify-content: space-between;
  }

  .pos-content {
    padding: 24px 16px;
  }

  .section-title {
    font-size: 24px;
  }

  .table-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .table-card {
    padding: 16px;
    min-height: 160px;
  }

  .table-number {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .btn-notification,
  .btn-header,
  .btn-logout {
    font-size: 12px;
    padding: 6px 12px;
  }

  .staff-info {
    font-size: 12px;
  }

  .table-grid {
    grid-template-columns: 1fr;
  }
}
</style>