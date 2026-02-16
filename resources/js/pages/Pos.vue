<template>
  <div class="pos-page">
    <!-- Header -->
    <div class="pos-header">
      <div class="header-container">
        <div class="header-left">
          <h1 class="cafe-title">
            <Coffee :size="32" class="title-icon" />
            Board Game Cafe
          </h1>
        </div>
        
        <div class="header-right">
          <button v-if="pendingOrders.length > 0" @click="showPendingModal = true" class="btn-notification">
            <Bell :size="18" />
            <span class="notification-count">{{ pendingOrders.length }}</span> ออเดอร์ใหม่
          </button>
          <div class="staff-info">
            <span class="staff-label">พนักงาน:</span>
            <strong class="staff-name">{{ user.name }}</strong>
          </div>
          <button v-if="user.role === 'admin'" @click="$router.push('/admin/dashboard')" class="btn-header">
            <LayoutDashboard :size="16" />
            Dashboard
          </button>
          <button @click="logout" class="btn-header-logout">
            <LogOut :size="16" />
            ออกจากระบบ
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
              <Users :size="18" class="seats-icon" />
              <span>{{ table.seat_count }} ที่นั่ง</span>
            </div>

            <!-- Status Badge -->
            <div class="table-status">
              <span v-if="table.status?.toLowerCase() === 'available'" class="status-badge status-available">
                <Check :size="14" /> ว่าง
              </span>
              <span v-else class="status-badge status-busy">
                <Circle :size="10" fill="currentColor" /> ไม่ว่าง
              </span>
            </div>

            <!-- Timer for Busy Tables -->
            <div v-if="table.status?.toLowerCase() === 'busy' && table.active_session" class="table-timer">
              <div class="timer-label">เวลาใช้งาน</div>
              <div class="timer-value">
                <Clock :size="16" class="timer-icon" />
                {{ formatDuration(table.active_session.start_time) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Orders Modal -->
    <div v-if="showPendingModal" class="modal-overlay" @click.self="showPendingModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>
            <Bell :size="20" class="modal-header-icon" />
            ยืนยันรายการอาหารจากลูกค้า
          </h3>
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
                <button @click="goToTable(order.session?.table_id)" class="btn-confirm">ไปยังโต๊ะ</button>
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
      <div class="modal-card modal-compact">
        <div class="modal-header text-center">
          <div class="modal-title-wrapper">
            <div class="modal-icon-bg">
              <LayoutGrid :size="24" />
            </div>
            <h3 class="modal-title-text">เปิดโต๊ะ {{ targetTable?.name }}</h3>
          </div>
        </div>
        
        <div class="modal-body">
          <div class="pax-section">
            <label class="pax-label">จำนวนลูกค้า</label>
            <div class="pax-control-modern">
              <button @click="pax > 1 ? pax-- : null" class="btn-pax-modern" type="button">
                <Minus :size="20" />
              </button>
              <div class="pax-display">
                <span class="pax-number">{{ pax }}</span>
                <span class="pax-unit">คน</span>
              </div>
              <button @click="pax++" class="btn-pax-modern" type="button">
                <Plus :size="20" />
              </button>
            </div>
          </div>
          
          <div class="options-section">
            <label class="option-card" :class="{ active: isDayPass }">
              <input type="checkbox" v-model="isDayPass" class="hidden-checkbox">
              <Ticket :size="20" />
              <span class="option-text">เหมาวัน (Day Pass)</span>
              <div class="custom-check">
                <Check v-if="isDayPass" :size="14" />
              </div>
            </label>
          </div>

          <transition name="fade">
            <div v-if="pax > targetTable?.seat_count" class="warning-modern">
              <AlertTriangle :size="18" />
              <span>ลูกค้าเกินจำนวนที่นั่ง ({{ targetTable?.seat_count }})</span>
            </div>
          </transition>
        </div>

        <div class="modal-footer-grid">
          <button @click="closeModal" class="btn-modal-secondary">
            ยกเลิก
          </button>
          <button @click="confirmOpenTable" class="btn-modal-primary">
            ยืนยันเปิดโต๊ะ
          </button>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="closeQrModal">
      <div class="modal-card modal-small">
        <div class="modal-header">
          <h3>
            <QrCode :size="20" class="modal-header-icon" />
            สแกนเพื่อสั่งอาหาร
          </h3>
        </div>
        <div class="modal-body text-center">
          <p class="qr-table-name">โต๊ะ: <strong>{{ targetTable?.name }}</strong></p>
          <div class="qr-code-wrapper">
            <QrcodeVue :value="qrUrl" :size="220" level="H" />
          </div>
          <p class="qr-instruction">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
        </div>
        
        <!-- ใช้ Teleport ย้ายใบเสร็จไปที่ body เพื่อไม่ให้ซ้อนกับ UI หลักตอนพิมพ์ -->
        <teleport to="body">
          <div v-if="showQrModal" id="receipt-print-area" class="print-only">
            <div class="receipt-header">
              <h2 class="receipt-shop-name">☕ Board Game Cafe</h2>
              <div class="receipt-divider">--------------------------------</div>
              <h1 class="receipt-table-num">โต๊ะ: {{ targetTable?.name }}</h1>
              <div class="receipt-divider">--------------------------------</div>
            </div>
            <div class="receipt-body">
              <div class="receipt-qr-wrapper">
                <QrcodeVue :value="qrUrl" :size="180" level="H" />
              </div>
              <p class="receipt-instruction">สแกนเพื่อสั่งอาหารและดูรายการ</p>
            </div>
            <div class="receipt-footer">
              <div class="receipt-divider">--------------------------------</div>
              <p>{{ new Date().toLocaleString('th-TH') }}</p>
              <p>ขอให้สนุกกับการเล่นเกมนะคะ!</p>
            </div>
          </div>
        </teleport>

        <div class="modal-footer">
          <button @click="printQr" class="btn-secondary">
            พิมพ์
          </button>
          <button @click="closeQrModal" class="btn-primary">ปิด</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import QrcodeVue from 'qrcode.vue';
import { useAlert } from '../composables/useAlert';
import { 
  Coffee, 
  Bell, 
  LayoutDashboard, 
  LogOut, 
  Users, 
  Check, 
  Circle, 
  Clock, 
  QrCode,
  Printer,
  Ticket,
  AlertTriangle,
  Plus,
  Minus,
  X,
  LayoutGrid
} from 'lucide-vue-next';

export default {
  components: {
    QrcodeVue,
    Coffee,
    Bell,
    LayoutDashboard,
    LogOut,
    Users,
    Check,
    Circle,
    Clock,
    QrCode,
    Printer,
    Ticket,
    AlertTriangle,
    Plus,
    Minus,
    X,
    LayoutGrid
  },
  setup() {
    const router = useRouter();
    const { success, error, warning, confirm, loading: showAlertLoading } = useAlert();
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
        
        // ลบส่วนที่เรียก success('🔔 มีออเดอร์ใหม่จากลูกค้า!') ออกตามคำขอ
        pendingOrders.value = response.data;
      } catch (error) {
        console.error("Fetch pending error", error);
      }
    };

    const confirmOrder = async (orderId, status) => {
      try {
        const token = localStorage.getItem('token');
        await axios.put(`/api/orders/${orderId}/status`, { status }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        
        if (status === 'pending') {
            success('✅ ยืนยันออเดอร์แล้ว');
        } else {
            warning('❌ ปฏิเสธออเดอร์แล้ว');
        }

        fetchPendingOrders();
        fetchTables();
      } catch (err) {
        error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message);
      }
    };

    const getTablePendingCount = (tableId) => {
      return pendingOrders.value.filter(o => o.session?.table_id === tableId).length;
    };

    const goToTable = (tableId) => {
      if (!tableId) return;
      showPendingModal.value = false;
      router.push(`/order/${tableId}`);
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
            const proceed = await confirm(
                '⚠️ ยืนยันการเปิดโต๊ะ?',
                `จำนวนลูกค้า (${pax.value} คน) เกินที่นั่งของโต๊ะนี้ (${targetTable.value.seat_count} ที่นั่ง)\nคุณยังต้องการที่จะเปิดโต๊ะนี้ใช่หรือไม่?`
            );
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
        } catch (err) {
            error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message);
        }
    };

    const closeQrModal = () => {
        showQrModal.value = false;
        targetTable.value = null;
    };

    const printQr = () => {
      window.print();
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
        showQrModal, qrUrl, closeQrModal, printQr,
        formatDuration, 
        pendingOrders, showPendingModal, confirmOrder, getTablePendingCount, formatTime, goToTable
    };
  }
};
</script>

<style scoped>

/* ========== Print Styles ========== */

@media screen {

  .print-only { display: none; }

}



@media print {



  @page {



    size: 80mm auto;



    margin: 0;



  }







  html, body {



    width: 80mm !important;



    margin: 0 !important;



    padding: 0 !important;



    overflow: visible !important;



  }







    /* ซ่อนโครงสร้างหลักของหน้าเว็บทั้งหมด และ SweetAlert */







    .pos-page, .modal-overlay, .swal2-container, #app > div:not(#receipt-print-area) {







      display: none !important;







    }



  



  #receipt-print-area {



    display: block !important;



    width: 80mm !important;



    margin: 0 auto !important;



    padding: 10mm 5mm !important;



    background: white !important;



    color: black !important;



    font-family: 'Courier New', Courier, monospace;



    text-align: center;



    position: static !important;



    visibility: visible !important;



  }







  #receipt-print-area * {



    visibility: visible !important;



  }



  .receipt-header { margin-bottom: 5mm; }

  .receipt-shop-name { font-size: 18pt; margin: 0; }

  .receipt-table-num { font-size: 24pt; margin: 5mm 0; font-weight: bold; }

  .receipt-divider { font-size: 10pt; margin: 2mm 0; }

  .receipt-qr-wrapper { margin: 5mm auto; display: flex; justify-content: center; }

  .receipt-instruction { font-size: 12pt; margin-top: 3mm; }

    .receipt-footer { margin-top: 5mm; font-size: 9pt; }

  

    /* บังคับขนาดกระดาษที่นี่ */

    @page {

      margin: 0;

      size: 80mm auto; /* กำหนดความกว้าง 80mm ความสูงยืดหยุ่นตามเนื้อหา */

    }

  }

  

  /* ========== Global Styles ========== */

.pos-page {

  min-height: 100vh;

  background: var(--color-bg-primary);

  color: #000000;

  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

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

}



.cafe-title {

  font-size: 28px;

  font-weight: 700;

  color: var(--color-highlight-light);

  margin: 0;

  display: flex;

  align-items: center;

  gap: 12px;

}



.title-icon { color: var(--color-highlight); }



.header-right {

  display: flex;

  align-items: center;

  gap: 12px;

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

  animation: pulse 2s infinite;

  display: flex;

  align-items: center;

  gap: 8px;

}



.notification-count {

  background: white;

  color: var(--color-danger);

  padding: 2px 8px;

  border-radius: 10px;

  font-weight: 700;

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

  display: flex;

  align-items: center;

  gap: 8px;

  transition: all 0.2s;

}



.btn-header:hover { background: rgba(255, 255, 255, 0.25); }



.btn-header-logout {

  background: var(--color-danger);

  color: white;

  border: none;

  padding: 8px 16px;

  border-radius: 8px;

  font-weight: 600;

  font-size: 13px;

  cursor: pointer;

  display: flex;

  align-items: center;

  gap: 8px;

  transition: all 0.2s;

}



.btn-header-logout:hover { background: #d32f2f; }



.staff-info {

  display: flex;

  align-items: center;

  gap: 6px;

  color: rgba(255, 255, 255, 0.9);

  font-size: 14px;

}



.staff-name { color: var(--color-highlight); font-weight: bold; }



/* ========== Main Content ========== */

.pos-content {

  max-width: 1600px;

  margin: 0 auto;

  padding: 32px;

}



.content-header { margin-bottom: 32px; }



.section-title {

  font-size: 32px;

  font-weight: 700;

  color: #000000;

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



.section-subtitle { font-size: 16px; color: #000000; margin: 0; }



/* ========== Loading State ========== */

.loading-state { text-align: center; padding: 80px 20px; color: #000000; }

.spinner {

  width: 50px; height: 50px;

  border: 4px solid var(--color-secondary-dark);

  border-top-color: var(--color-primary);

  border-radius: 50%;

  animation: spin 1s linear infinite;

  margin: 0 auto 16px;

}

@keyframes spin { to { transform: rotate(360deg); } }



/* ========== Table Grid ========== */

.table-grid {

  display: grid;

  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));

  gap: 24px;

}



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



.table-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); }

.table-available { border: 3px solid var(--color-success-light); }

.table-available:hover { border-color: var(--color-success); }

.table-busy { border: 3px solid var(--color-danger-light); }

.table-busy:hover { border-color: var(--color-danger); }



.pending-badge {

  position: absolute; top: 12px; right: 12px;

  background: var(--color-danger); color: white;

  width: 28px; height: 28px; border-radius: 50%;

  display: flex; align-items: center; justify-content: center;

  font-weight: 700; font-size: 13px;

  box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);

  animation: bounce 1s infinite;

}

@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }



.table-number { font-size: 28px; font-weight: 700; color: #000000; }

.table-seats { display: flex; align-items: center; gap: 6px; color: #000000; font-size: 15px; }

.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; }

.status-available { background: var(--color-success); color: white; }

.status-busy { background: var(--color-danger); color: white; }



.table-timer { margin-top: 8px; padding-top: 12px; border-top: 1px solid var(--color-secondary-dark); }

.timer-label { font-size: 11px; color: #000000; text-transform: uppercase; margin-bottom: 4px; }

.timer-value { font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700; color: var(--color-danger); display: flex; align-items: center; gap: 6px; }



/* ========== Modals ========== */

.modal-overlay {

  position: fixed; top: 0; left: 0; right: 0; bottom: 0;

  background: rgba(0, 0, 0, 0.5);

  display: flex; align-items: center; justify-content: center;

  z-index: 1000; backdrop-filter: blur(4px);

}



.modal-card {

  background: white; border-radius: 24px;

  box-shadow: var(--shadow-lg); width: 95%;

  max-width: 500px; max-height: 90vh; overflow: hidden;

}



.modal-compact { max-width: 380px; }



.modal-header { padding: 24px; border-bottom: 1px solid var(--color-secondary-dark); }

.modal-header h3 { margin: 0; font-size: 20px; font-weight: 800; color: #000000; display: flex; align-items: center; gap: 10px; }

.modal-title-wrapper { display: flex; flex-direction: column; align-items: center; gap: 12px; }

.modal-icon-bg {

  width: 56px; height: 56px; background: var(--color-bg-primary);

  color: var(--color-primary); border-radius: 16px;

  display: flex; align-items: center; justify-content: center;

}



.modal-body { padding: 24px; overflow-y: auto; }



/* Open Table Specific */

.pax-section { text-align: center; margin-bottom: 24px; }

.pax-label { display: block; font-size: 14px; font-weight: 600; color: #666; margin-bottom: 12px; }

.pax-control-modern {

  display: flex; align-items: center; justify-content: center;

  background: var(--color-bg-primary); padding: 8px; border-radius: 20px;

  border: 2px solid var(--color-secondary-dark); max-width: 240px; margin: 0 auto;

}

.btn-pax-modern {

  width: 48px; height: 48px; border-radius: 16px; border: none;

  background: white; color: var(--color-primary); cursor: pointer;

  display: flex; align-items: center; justify-content: center;

  box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;

}

.pax-display { flex: 1; display: flex; flex-direction: column; align-items: center; }

.pax-number { font-size: 32px; font-weight: 800; color: #000000; }

.pax-unit { font-size: 12px; color: #666; }



.option-card {

  display: flex; align-items: center; gap: 12px; padding: 16px;

  background: white; border: 2px solid var(--color-secondary-dark);

  border-radius: 16px; cursor: pointer; transition: all 0.2s; margin-bottom: 16px;

}

.option-card.active { border-color: var(--color-primary); background: rgba(107, 79, 63, 0.05); }

.option-text { flex: 1; font-weight: 600; color: #000000; }

.custom-check { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--color-secondary-dark); display: flex; align-items: center; justify-content: center; }

.option-card.active .custom-check { background: var(--color-primary); color: white; border-color: var(--color-primary); }

.hidden-checkbox { display: none; }



.warning-modern {

  display: flex; align-items: center; justify-content: center; gap: 8px;

  padding: 12px; background: #FFF3CD; color: #856404;

  border-radius: 12px; font-size: 13px; font-weight: 600; border: 1px solid #FFE69C;

}



.modal-footer { padding: 16px 24px; border-top: 1px solid var(--color-secondary-dark); display: flex; gap: 12px; justify-content: flex-end; }

.modal-footer-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; padding: 24px; background: var(--color-bg-primary); }



.btn-primary, .btn-modal-primary { background: var(--color-primary); color: white; }

.btn-secondary, .btn-modal-secondary { background: white; color: #000000; border: 1px solid var(--color-secondary-dark); }



.btn-primary, .btn-secondary, .btn-modal-primary, .btn-modal-secondary, .btn-close-modal {

  padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 14px;

  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; transition: all 0.2s;

}

.btn-primary:hover, .btn-modal-primary:hover { background: var(--color-primary-dark); transform: translateY(-2px); }

.btn-secondary:hover, .btn-modal-secondary:hover { background: #f5f5f5; }



/* QR Code */

.qr-table-name { font-size: 16px; color: #000000; margin-bottom: 16px; }

.qr-table-name strong { font-size: 20px; }

.qr-code-wrapper { background: white; border: 2px solid var(--color-secondary-dark); border-radius: 16px; padding: 20px; display: inline-block; margin-bottom: 16px; }

.qr-instruction { color: #000000; font-size: 14px; }



/* Pending Orders List */

.pending-orders-list { display: flex; flex-direction: column; gap: 12px; }

.pending-order-item { background: var(--color-bg-primary); border: 1px solid var(--color-secondary-dark); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }

.order-table { font-weight: 700; color: #000000; }

.order-detail { color: #000000; font-size: 14px; }

.order-time { color: #666; font-size: 12px; }

.btn-confirm { background: var(--color-action); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }



/* ========== Responsive ========== */

@media (max-width: 768px) {

  .header-container { padding: 16px 20px; }

  .cafe-title { font-size: 22px; }

  .pos-content { padding: 24px 16px; }

  .table-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }

}

</style>