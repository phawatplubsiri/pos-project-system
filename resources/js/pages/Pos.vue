<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] text-black font-[-apple-system,BlinkMacSystemFont,'Segoe_UI',Roboto,sans-serif]">
    <!-- Header -->
    <div class="bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-primary-dark)] shadow-[0_4px_12px_rgba(0,0,0,0.15)] sticky top-0 z-[100]">
      <div class="max-w-[1600px] mx-auto py-5 px-8 flex justify-between items-center gap-6">
        <div class="flex items-center gap-3">
          <h1 class="text-[28px] font-bold text-[var(--color-highlight-light)] m-0 flex items-center gap-3">
            <Coffee :size="32" class="text-[var(--color-highlight)]" />
            Board Game Cafe
          </h1>
        </div>
        
        <div class="flex items-center gap-3">
          <button v-if="regularPendingOrders.length > 0" @click="showPendingModal = true" class="btn-notification">
            <Bell :size="18" />
            <span class="bg-white text-[var(--color-danger)] px-2 py-0.5 rounded-[10px] font-bold">{{ regularPendingOrders.length }}</span> ออเดอร์ใหม่
          </button>
          <div class="flex items-center gap-1.5 text-white/90 text-sm">
            <span>พนักงาน:</span>
            <strong class="text-[var(--color-highlight)] font-bold">{{ user.name }}</strong>
          </div>
          <button v-if="user.role === 'admin'" @click="$router.push('/admin/dashboard')" class="bg-white/15 text-white border border-white/30 py-2 px-4 rounded-lg font-semibold text-[13px] cursor-pointer flex items-center gap-2 transition-all duration-200 hover:bg-white/25">
            <LayoutDashboard :size="16" />
            Dashboard
          </button>
          <button @click="logout" class="bg-[var(--color-danger)] text-white border-none py-2 px-4 rounded-lg font-semibold text-[13px] cursor-pointer flex items-center gap-2 transition-all duration-200 hover:bg-[#d32f2f]">
            <LogOut :size="16" />
            ออกจากระบบ
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-[1600px] mx-auto p-8">
      <!-- Staff Call Alerts -->
      <div v-if="staffCalls.length > 0" class="mb-6 flex flex-wrap gap-3">
        <div v-for="call in staffCalls" :key="call.id" class="flex items-center gap-3 bg-[#FFF3CD] border-2 border-[#FFE69C] p-3 px-5 rounded-2xl shadow-sm animate-pulse">
          <BellRing class="text-[#856404]" :size="20" />
          <span class="font-bold text-[#856404]">โต๊ะ {{ call.session?.table?.name }} เรียกพนักงาน!</span>
          <button @click="dismissCall(call.id)" class="ml-auto bg-transparent border-none text-[#856404] cursor-pointer p-1 rounded-full hover:bg-[#856404]/10 transition-colors flex items-center justify-center">
            <X :size="20" />
          </button>
        </div>
      </div>

      <div class="mb-8">
        <h2 class="section-title">ผังร้าน</h2>
        <p class="text-base text-black m-0">เลือกโต๊ะเพื่อเปิดบิลหรือจัดการออเดอร์</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-20 text-black">
        <div class="spinner"></div>
        <p>กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Table Grid -->
      <div v-else class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-6">
        <div v-for="table in tables" :key="table.id">
          <div
            class="table-card"
            :class="{
              'table-available': table.is_available,
              'table-busy': !table.is_available,
              'table-disabled': isTableDisabled(table)
            }"
            @click="!isTableDisabled(table) && handleTableClick(table)"
          >
            <!-- Disabled Overlay Info -->
            <div v-if="isTableDisabled(table)" class="absolute top-3 right-3 bg-black/50 text-white py-1 px-2 rounded text-[11px] flex items-center gap-1 z-[2]">
              <Lock :size="14" />
            </div>

            <!-- Pending Badge -->
            <div v-if="getTablePendingCount(table.id) > 0" class="pending-badge">
              {{ getTablePendingCount(table.id) }}
            </div>

            <!-- Table Info -->
            <div class="text-[28px] font-bold text-black">{{ table.name }}</div>
            
            <div class="flex items-center gap-1.5 text-black text-[15px]">
              <Users :size="18" />
              <span>{{ table.seat_count }} ที่นั่ง</span>
            </div>

            <!-- Status Badge -->
            <div>
              <span v-if="table.is_available" class="status-badge bg-[var(--color-success)] text-white">
                <Check :size="14" /> ว่าง
              </span>
              <span v-else class="status-badge bg-[var(--color-danger)] text-white">
                <Circle :size="10" fill="currentColor" /> ไม่ว่าง
              </span>
            </div>

            <!-- Timer for Busy Tables -->
            <div v-if="!table.is_available && table.active_session" class="mt-2 pt-3 border-t border-[var(--color-secondary-dark)]">
              <div class="flex justify-between items-center mb-1">
                <div class="text-[11px] text-black uppercase">เวลาใช้งาน</div>
              </div>
              <div class="font-[Courier_New,monospace] text-base font-bold text-[var(--color-danger)] flex items-center gap-1.5 mb-2">
                <Clock :size="16" />
                {{ formatDuration(table.active_session.start_time) }}
              </div>
              <!-- Staff Tag (Admin Only) -->
              <div v-if="user.role === 'admin'" class="flex items-center gap-1 text-[var(--color-primary)] text-[11px] font-bold">
                <User :size="12" />
                <span>พนักงาน: {{ table.active_session.staff_name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Orders Modal -->
    <div v-if="showPendingModal" class="modal-overlay" @click.self="showPendingModal = false">
      <div class="modal-card">
        <div class="p-6 border-b border-[var(--color-secondary-dark)]">
          <h3 class="m-0 text-xl font-extrabold text-black flex items-center gap-2.5">
            <Bell :size="20" class="text-[var(--color-primary)]" />
            ยืนยันรายการอาหารจากลูกค้า
          </h3>
        </div>
        <div class="p-6 overflow-y-auto">
          <div class="flex flex-col gap-3">
            <div v-for="order in pendingOrders" :key="order.id" class="bg-[var(--color-bg-primary)] border border-[var(--color-secondary-dark)] rounded-xl p-4 flex justify-between items-center gap-4">
              <div>
                <div class="font-bold text-black">โต๊ะ {{ order.session?.table?.name }}</div>
                <div class="text-black text-sm">{{ order.product?.name }} × {{ order.quantity }}</div>
                <div class="text-[#666] text-xs">{{ formatTime(order.created_at) }}</div>
              </div>
              <div>
                <button @click="goToTable(order.session?.table_id)" class="bg-[var(--color-action)] text-white border-none py-2 px-4 rounded-lg font-semibold cursor-pointer hover:scale-110 transition-transform">ไปยังโต๊ะ</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPendingModal = false" class="modal-btn bg-white text-black border border-[var(--color-secondary-dark)] hover:bg-[#f5f5f5]">ปิด</button>
        </div>
      </div>
    </div>

    <!-- Open Table Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card max-w-[380px]">
        <div class="p-6 border-b border-[var(--color-secondary-dark)] text-center">
          <div class="flex flex-col items-center gap-3">
            <div class="w-14 h-14 bg-[var(--color-bg-primary)] text-[var(--color-primary)] rounded-2xl flex items-center justify-center">
              <LayoutGrid :size="24" />
            </div>
            <h3 class="m-0 text-xl font-extrabold text-black">เปิดโต๊ะ {{ targetTable?.name }}</h3>
          </div>
        </div>
        
        <div class="p-6">
          <div class="text-center mb-6">
            <label class="block text-sm font-semibold text-[#666] mb-3">จำนวนลูกค้า</label>
            <div class="pax-control">
              <button @click="pax > 1 ? pax-- : null" class="btn-pax" type="button">
                <Minus :size="20" />
              </button>
              <div class="flex-1 flex flex-col items-center">
                <span class="text-[32px] font-extrabold text-black">{{ pax }}</span>
                <span class="text-xs text-[#666]">คน</span>
              </div>
              <button @click="pax++" class="btn-pax" type="button">
                <Plus :size="20" />
              </button>
            </div>
          </div>
          
          <div class="text-center mb-6">
            <label class="block text-sm font-semibold text-[#666] mb-3">จำนวน Daypass</label>
            <div class="pax-control">
              <button @click="dayPassCount > 0 ? dayPassCount-- : null" class="btn-pax" type="button">
                <Minus :size="20" />
              </button>
              <div class="flex-1 flex flex-col items-center">
                <span class="text-[32px] font-extrabold text-black">{{ dayPassCount }}</span>
                <span class="text-xs text-[#666]">คน</span>
              </div>
              <button @click="dayPassCount < pax ? dayPassCount++ : null" class="btn-pax" type="button">
                <Plus :size="20" />
              </button>
            </div>
          </div>

          <transition name="fade">
            <div v-if="pax > targetTable?.seat_count" class="flex items-center justify-center gap-2 p-3 bg-[#FFF3CD] text-[#856404] rounded-xl text-[13px] font-semibold border border-[#FFE69C]">
              <AlertTriangle :size="18" />
              <span>ลูกค้าเกินจำนวนที่นั่ง ({{ targetTable?.seat_count }})</span>
            </div>
          </transition>
        </div>

        <div class="grid grid-cols-[1fr_1.5fr] gap-3 p-6 bg-[var(--color-bg-primary)]">
          <button @click="closeModal" class="modal-btn bg-white text-black border border-[var(--color-secondary-dark)] hover:bg-[#f5f5f5]">
            ยกเลิก
          </button>
          <button @click="confirmOpenTable" class="modal-btn bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-dark)] hover:-translate-y-0.5">
            ยืนยันเปิดโต๊ะ
          </button>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="closeQrModal">
      <div class="modal-card max-w-[400px]">
        <div class="p-6 border-b border-[var(--color-secondary-dark)]">
          <h3 class="m-0 text-xl font-extrabold text-black flex items-center gap-2.5">
            <QrCode :size="20" class="text-[var(--color-primary)]" />
            สแกนเพื่อสั่งอาหาร
          </h3>
        </div>
        <div class="p-6 text-center">
          <p class="text-base text-black mb-4">โต๊ะ: <strong class="text-xl">{{ targetTable?.name }}</strong></p>
          <div class="bg-white border-2 border-[var(--color-secondary-dark)] rounded-2xl p-5 inline-block mb-4">
            <QrcodeVue :value="qrUrl" :size="220" level="H" />
          </div>
          <p class="text-black text-sm">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
        </div>
        
        <!-- Print Receipt (Teleport) -->
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
            </div>
          </div>
        </teleport>

        <div class="modal-footer">
          <button @click="closeQrModal" class="modal-btn bg-[var(--color-divider)] text-black hover:bg-[#C1C5CB]">ปิด</button>
          <button @click="printQr" class="modal-btn bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-dark)]">พิมพ์</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import QrcodeVue from 'qrcode.vue';
import { useAlert } from '../composables/useAlert';
import { 
  Coffee, Bell, LayoutDashboard, LogOut, Users, User, UserCheck,
  Check, Circle, Clock, QrCode, Printer, Ticket, AlertTriangle,
  Plus, Minus, X, LayoutGrid, Lock, BellRing
} from 'lucide-vue-next';

export default {
  components: {
    QrcodeVue, Coffee, Bell, LayoutDashboard, LogOut, Users, User,
    Check, Circle, Clock, QrCode, Printer, Ticket, AlertTriangle,
    Plus, Minus, X, LayoutGrid, Lock, BellRing
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
    const showModal = ref(false);
    const targetTable = ref(null);
    const pax = ref(1);
    const dayPassCount = ref(0);
    const showQrModal = ref(false);
    const qrUrl = ref(''); 

    const isTableDisabled = (table) => {
      if (table.is_available) return false;
      if (user.value.role === 'admin') return false;
      return table.active_session && table.active_session.user_id !== user.value.id;
    };

    const fetchPendingOrders = async () => {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/orders/pending-confirmations', {
          headers: { Authorization: `Bearer ${token}` }
        });
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
        if (status === 'pending') success('✅ ยืนยันออเดอร์แล้ว');
        else warning('❌ ปฏิเสธออเดอร์แล้ว');
        fetchPendingOrders();
        fetchTables();
      } catch (err) {
        error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message);
      }
    };

    const dismissCall = async (orderId) => {
      try {
        const token = localStorage.getItem('token');
        await axios.put(`/api/orders/${orderId}/status`, { status: 'completed' }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchPendingOrders();
        fetchTables();
      } catch (err) {
        console.error("Dismiss call error", err);
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
        tables.value = response.data.filter(t => t.is_active);
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
      if (!table.is_available) { router.push(`/order/${table.id}`); return; }
      targetTable.value = table;
      pax.value = 1;
      dayPassCount.value = 0;
      showModal.value = true;
    };

    const closeModal = () => { showModal.value = false; pax.value = 1; dayPassCount.value = 0; targetTable.value = null; };

    const confirmOpenTable = async () => {
        if (pax.value > targetTable.value.seat_count) {
            const proceed = await confirm('⚠️ ยืนยันการเปิดโต๊ะ?', `จำนวนลูกค้า (${pax.value} คน) เกินที่นั่งของโต๊ะนี้ (${targetTable.value.seat_count} ที่นั่ง)\nคุณยังต้องการที่จะเปิดโต๊ะนี้ใช่หรือไม่?`);
            if (!proceed) return;
        }
        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(`/api/tables/${targetTable.value.id}/open`, {
                pax: pax.value, day_pass_count: dayPassCount.value
            }, { headers: { Authorization: `Bearer ${token}` } });
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

    const closeQrModal = () => { showQrModal.value = false; targetTable.value = null; };
    const printQr = () => { window.print(); };
    const logout = () => {
        showAlertLoading('กำลังออกจากระบบ...');
        setTimeout(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('role');
            router.push('/staff/login');
            closeAlert();
        }, 800);
    };

    const staffCalls = computed(() => {
      return pendingOrders.value.filter(o => o.product?.name === 'เรียกพนักงาน');
    });

    const regularPendingOrders = computed(() => {
      return pendingOrders.value.filter(o => o.product?.name !== 'เรียกพนักงาน');
    });

    onMounted(() => {
      fetchTables();
      fetchPendingOrders();
      timerInterval = setInterval(() => { currentTime.value = new Date(); }, 1000);
      pollingInterval = setInterval(() => { fetchPendingOrders(); fetchTables(); }, 5000);
    });

    onUnmounted(() => {
      if (timerInterval) clearInterval(timerInterval);
      if (pollingInterval) clearInterval(pollingInterval);
    });

    return { 
        user, tables, loading, logout, 
        handleTableClick, confirmOpenTable,
        showModal, targetTable, pax, dayPassCount, closeModal,
        showQrModal, qrUrl, closeQrModal, printQr,
        formatDuration, 
        pendingOrders, staffCalls, regularPendingOrders,
        showPendingModal, confirmOrder, dismissCall, getTablePendingCount, formatTime, goToTable,
        isTableDisabled
    };
  }
};
</script>

<style scoped>
/* Print Styles */
@media screen { .print-only { display: none; } }

@media print {
  @page { size: 80mm auto; margin: 0; }
  html, body { width: 80mm !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; }
  .min-h-screen, .modal-overlay, .swal2-container, #app > div:not(#receipt-print-area) { display: none !important; }
  #receipt-print-area {
    display: block !important; width: 80mm !important; margin: 0 auto !important;
    padding: 10mm 5mm !important; background: white !important; color: black !important;
    font-family: 'Courier New', Courier, monospace; text-align: center; position: static !important; visibility: visible !important;
  }
  #receipt-print-area * { visibility: visible !important; }
  .receipt-header { margin-bottom: 5mm; }
  .receipt-shop-name { font-size: 18pt; margin: 0; }
  .receipt-table-num { font-size: 24pt; margin: 5mm 0; font-weight: bold; }
  .receipt-divider { font-size: 10pt; margin: 2mm 0; }
  .receipt-qr-wrapper { margin: 5mm auto; display: flex; justify-content: center; }
  .receipt-instruction { font-size: 12pt; margin-top: 3mm; }
  .receipt-footer { margin-top: 5mm; font-size: 9pt; }
}

/* Section Title with underline */
.section-title {
  font-size: 32px; font-weight: 700; color: #000000; margin: 0 0 8px 0;
  position: relative; display: inline-block;
}
.section-title::after {
  content: ''; position: absolute; bottom: -4px; left: 0;
  width: 60%; height: 4px;
  background: linear-gradient(90deg, var(--color-primary) 0%, transparent 100%);
  border-radius: 2px;
}

/* Table Card */
.table-card {
  background: var(--color-table-row); border-radius: 16px; padding: 24px;
  cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-sm); position: relative; min-height: 180px;
  display: flex; flex-direction: column; gap: 12px;
}
.table-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); }
.table-available { border: 3px solid var(--color-success-light); }
.table-available:hover { border-color: var(--color-success); }
.table-busy { border: 3px solid var(--color-danger-light); }
.table-busy:hover { border-color: var(--color-danger); }

/* Disabled state */
.table-disabled { background: #e0e0e0 !important; border-color: #bdbdbd !important; cursor: not-allowed !important; opacity: 0.8; filter: grayscale(0.8); }
.table-disabled:hover { transform: none !important; box-shadow: var(--shadow-sm) !important; }
.table-disabled .table-number, .table-disabled .table-seats { color: #757575 !important; }

/* Pending Badge */
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

/* Status Badge */
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; }

/* Notification Button */
.btn-notification {
  background: linear-gradient(135deg, var(--color-danger) 0%, var(--color-primary-dark) 100%);
  color: white; border: none; padding: 10px 18px; border-radius: 20px;
  font-weight: 600; font-size: 14px; cursor: pointer; animation: pulse 2s infinite;
  display: flex; align-items: center; gap: 8px;
}
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }

/* Spinner */
.spinner {
  width: 50px; height: 50px; border: 4px solid var(--color-secondary-dark);
  border-top-color: var(--color-primary); border-radius: 50%;
  animation: spin 1s linear infinite; margin: 0 auto 16px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Modals */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.5); display: flex; align-items: center;
  justify-content: center; z-index: 1000; backdrop-filter: blur(4px);
}
.modal-card {
  background: white; border-radius: 24px; box-shadow: var(--shadow-lg);
  width: 95%; max-width: 500px; max-height: 90vh; overflow: hidden;
}
.modal-footer { padding: 16px 24px; border-top: 1px solid var(--color-secondary-dark); display: flex; gap: 12px; justify-content: flex-end; }
.modal-btn {
  padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 14px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; border: none; transition: all 0.2s;
}

/* Pax Control */
.pax-control {
  display: flex; align-items: center; justify-content: center;
  background: var(--color-bg-primary); padding: 8px; border-radius: 20px;
  border: 2px solid var(--color-secondary-dark); max-width: 240px; margin: 0 auto;
}
.btn-pax {
  width: 48px; height: 48px; border-radius: 16px; border: none;
  background: white; color: var(--color-primary); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;
}

/* Responsive */
@media (max-width: 768px) {
  .grid { grid-template-columns: repeat(2, 1fr) !important; gap: 16px; }
}
</style>