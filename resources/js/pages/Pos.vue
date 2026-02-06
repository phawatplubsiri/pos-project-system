<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 navbar-theme p-3 rounded">
      <div class="d-flex align-items-center gap-3">
        <h2 class="mb-0 text-brown">☕ <span class="accent-underline">Board Game</span> Cafe POS</h2>
        <small class="text-secondary">ระบบสั่งอาหาร & จัดการโต๊ะ</small>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button v-if="pendingOrders.length > 0" @click="showPendingModal = true" class="btn btn-danger btn-pill">
          🔔 มี {{ pendingOrders.length }} ออเดอร์ใหม่!
        </button>

        <div class="d-flex align-items-center gap-2">
          <button v-if="user.role === 'admin'" @click="$router.push('/admin/dashboard')" class="btn btn-outline-secondary btn-sm">📊 แดชบอร์ด</button>
          <span class="text-brown">พนักงาน: <strong>{{ user.name }}</strong></span>
          <button @click="logout" class="btn btn-outline-danger btn-sm">ออกจากระบบ</button>
        </div>
      </div>
    </div>

    <main>
      <h4 class="mb-3 text-brown">ผังร้าน — เลือกโต๊ะเพื่อเปิดบิล</h4>

      <div v-if="loading" class="text-muted">กำลังโหลดข้อมูล...</div>

      <div v-else class="row g-3">
        <div v-for="table in tables" :key="table.id" class="col-6 col-sm-4 col-md-3">
          <div
            class="card card-theme card-themed-hover text-center p-3"
            :class="{ 'border-brown': table.status?.toLowerCase() === 'busy' }"
            @click="handleTableClick(table)"
            style="cursor: pointer; min-height: 120px;"
          >
            <div class="h5 table-name text-brown">{{ table.name }}</div>
            <div class="text-secondary table-info">
              {{ table.seat_count }} ที่นั่ง
            </div>
            <div class="mt-2">
              <span v-if="table.status?.toLowerCase() === 'available'" class="badge bg-success">ว่าง</span>
              <span v-else class="badge bg-danger">ไม่ว่าง</span>
            </div>

            <div v-if="getTablePendingCount(table.id) > 0" class="position-absolute" style="top:8px; right:12px;">
              <span class="badge bg-danger">{{ getTablePendingCount(table.id) }}</span>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Pending Orders Modal (simple card modal) -->
    <div v-if="showPendingModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card" style="width:480px; max-width:95%;">
        <div class="card-body">
          <h5 class="card-title">🔔 ยืนยันรายการอาหารจากลูกค้า</h5>
          <div class="list-group mb-3">
            <div v-for="order in pendingOrders" :key="order.id" class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div><strong>โต๊ะ {{ order.session?.table?.name }}</strong> — {{ order.product?.name }} x {{ order.quantity }}</div>
                <small class="text-muted">เมื่อ {{ formatTime(order.created_at) }}</small>
              </div>
              <div class="d-flex gap-2">
                <button @click="confirmOrder(order.id, 'cancelled')" class="btn btn-sm btn-outline-danger">ปฏิเสธ</button>
                <button @click="confirmOrder(order.id, 'pending')" class="btn btn-sm btn-success">ยืนยัน</button>
              </div>
            </div>
          </div>
          <div class="text-end">
            <button @click="showPendingModal = false" class="btn btn-secondary">ปิด</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toastMsg" class="toast show position-fixed top-0 start-50 translate-middle-x mt-3" role="status" aria-live="polite">
      <div class="toast-body bg-dark text-white rounded-pill px-4 py-2">{{ toastMsg }}</div>
    </div>

    <!-- Open Table Modal -->
    <div v-if="showModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card" style="width:360px; max-width:95%;">
        <div class="card-body text-center">
          <h5 class="card-title">เปิดโต๊ะ {{ targetTable?.name }}</h5>
          <div class="mb-3">
            <label class="form-label">จำนวนลูกค้า (คน)</label>
            <input type="number" v-model="pax" min="1" class="form-control w-50 mx-auto text-center" />
          </div>
          <div class="mb-2 form-check">
            <input class="form-check-input" type="checkbox" v-model="isDayPass" id="isDayPass">
            <label class="form-check-label" for="isDayPass">🎟️ 1 Day Pass (เหมาวัน)</label>
          </div>

          <p v-if="pax > targetTable?.seat_count" class="text-warning">⚠️ ลูกค้า {{ pax }} คน เกินจำนวนที่นั่ง ({{ targetTable?.seat_count }})</p>

          <div class="d-flex gap-2 mt-3">
            <button @click="closeModal" class="btn btn-outline-secondary flex-fill">ยกเลิก</button>
            <button @click="confirmOpenTable" class="btn btn-success flex-fill">✅ ยืนยัน</button>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card" style="width:360px; max-width:95%;">
        <div class="card-body text-center">
          <h5 class="card-title">📱 สแกนเพื่อสั่งอาหาร</h5>
          <p class="mb-2">โต๊ะ: {{ targetTable?.name }}</p>
          <div class="qr-wrapper d-flex justify-content-center my-2">
            <QrcodeVue :value="qrUrl" :size="200" level="H" />
          </div>
          <p class="text-muted small">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
          <button @click="closeQrModal" class="btn btn-secondary w-100">ปิด</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import QrcodeVue from 'qrcode.vue'; // <--- 1. Import มาใช้

export default {
  components: {
    QrcodeVue // <--- 2. ลงทะเบียน Component
  },
  setup() {
    const router = useRouter();
    const user = ref({});
    const currentTime = ref(new Date());
    let timerInterval = null;

    try {
        const storedUser = localStorage.getItem('user');
        if (storedUser) user.value = JSON.parse(storedUser);
    } catch (e) {}

    const tables = ref([]);
    const loading = ref(true);
    const pendingOrders = ref([]);
    const showPendingModal = ref(false);
    const toastMsg = ref('');
    let pollingInterval = null;

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
        
        // ถ้ามีออเดอร์ใหม่ (จำนวนมากกว่าเดิม) ให้โชว์ Toast
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
      if (table.status === 'busy') {
        router.push(`/order/${table.id}`); // ถ้าไม่ว่าง ให้ไปหน้า Staff Order
        return;
      }
      targetTable.value = table;
      pax.value = 1;
      isDayPass.value = false;
      showModal.value = true; // เปิด Modal ถามจำนวนคน
    };

    const closeModal = () => {
        showModal.value = false;
        // targetTable.value = null; // อย่าเพิ่งเคลียร์ เผื่อใช้ต่อใน QR
    };

    // ✅ ฟังก์ชันสำคัญ: เปิดโต๊ะ -> ได้ Token -> สร้าง QR
    const confirmOpenTable = async () => {
        // เพิ่ม Alert เตือนถ้าคนเกินที่นั่ง
        if (pax.value > targetTable.value.seat_count) {
            const proceed = confirm(`⚠️ จำนวนลูกค้า (${pax.value} คน) เกินที่นั่งของโต๊ะนี้ (${targetTable.value.seat_count} ที่นั่ง)\nคุณยังต้องการที่จะเปิดโต๊ะนี้ใช่หรือไม่?`);
            if (!proceed) return;
        }

        try {
            const token = localStorage.getItem('token');
            // ยิง API เปิดโต๊ะ
            const response = await axios.post(`/api/tables/${targetTable.value.id}/open`, {
                pax: pax.value,
                is_day_pass: isDayPass.value
            }, {
                headers: { Authorization: `Bearer ${token}` }
            });

            // 1. ปิด Modal ถามจำนวนคน
            showModal.value = false;
            
            // 2. ดึง guest_token จาก API Response
            // (ต้องมั่นใจว่า TableController ส่ง session กลับมานะ)
            const sessionData = response.data.session; 
            const guestToken = sessionData.guest_token;

            // 3. สร้าง URL สำหรับลูกค้า
            // ⚠️ สำคัญ: ต้องเปลี่ยน localhost เป็น IP Address เครื่องเรา (เช่น 192.168.1.x) 
            // ไม่งั้นมือถือลูกค้าจะเปิดไม่ได้
            const baseUrl = window.location.origin; // หรือ Hardcode IP ไปเลย
            qrUrl.value = `${baseUrl}/customer/menu?token=${guestToken}`;

            // 4. เปิด Modal QR Code
            showQrModal.value = true;

            // 5. อัปเดตสถานะโต๊ะข้างหลัง
            fetchTables(); 

        } catch (error) {
            alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
        }
    };

    const closeQrModal = () => {
        showQrModal.value = false;
        targetTable.value = null; // เคลียร์ค่าทิ้ง
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
      pollingInterval = setInterval(fetchPendingOrders, 5000); // เช็คทุก 5 วินาที
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
/* Minimal overrides & helpers (Bootstrap handles layout) */
.qr-wrapper { margin: 12px 0; }

/* Ensure card-theme uses theme variables nicely in this page */
.card-theme { background: var(--color-bg-card); border: 1px solid var(--color-border-light); }

.text-brown { color: var(--color-text-primary) !important; }

.badge.bg-success { background-color: var(--color-success) !important; color: var(--color-text-white); }
.badge.bg-danger { background-color: var(--color-danger) !important; color: var(--color-text-white); }

.position-absolute { position: absolute; }

/* small responsive tweaks */
@media (max-width: 576px) {
  .card.card-theme { padding: 12px; }
  .accent-underline { border-bottom-width: 2px; }
}
</style>