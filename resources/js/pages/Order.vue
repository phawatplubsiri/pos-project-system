<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] text-black font-[-apple-system,BlinkMacSystemFont,'Segoe_UI',Roboto,sans-serif]">
    <!-- Header -->
    <div class="order-header">
      <div class="max-w-[1600px] mx-auto py-5 px-8 flex justify-between items-center gap-6 flex-wrap">
        <div class="flex items-center gap-4">
          <button @click="$router.go(-1)" class="btn-back">
            <ArrowLeft :size="18" />
          </button>
          <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold text-[var(--color-highlight-light)] m-0">โต๊ะ {{ tableName || tableId }}</h1>
            <div class="font-[Courier_New,monospace] text-sm text-white/90 font-semibold flex items-center gap-1.5">
              <Clock :size="14" />
              {{ formatDuration(sessionStartTime) }}
            </div>
          </div>
        </div>
        
        <div class="flex gap-3">
          <button @click="showQrModal = true" class="btn-qr">
            <QrCode :size="18" />
            QR Code
          </button>
          <button @click="handleCheckout" class="btn-checkout">
            <Banknote :size="18" />
            เช็คบิล / ปิดโต๊ะ
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-[1600px] mx-auto p-8">
      <div class="content-grid">
        <!-- Products Section -->
        <div class="flex flex-col gap-6">
          <!-- Category Filters -->
          <div class="flex gap-3 flex-wrap overflow-x-auto pb-2">
            <button
              v-for="cat in categories"
              :key="cat.type"
              @click="currentTab = cat.type"
              class="category-btn"
              :class="{ active: currentTab === cat.type }"
            >
              <component :is="cat.icon" :size="16" />
              {{ cat.name }}
            </button>
          </div>

          <!-- Product Grid -->
          <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-4">
            <div
              v-for="product in filteredProducts"
              :key="product.id"
              class="product-card"
              @click="addToCart(product)"
            >
              <div class="font-semibold text-black text-[15px]">{{ product.name }}</div>
              <div class="font-bold text-[var(--color-text-price)] text-base">{{ formatPrice(product.price) }} ฿</div>
            </div>
          </div>
        </div>

        <!-- Cart & Orders Section -->
        <div class="flex flex-col gap-5">
          <!-- Awaiting Confirmation Orders -->
          <div v-if="awaitingConfirmOrders.length > 0" class="bg-white rounded-[var(--radius-lg)] p-5 shadow-[var(--shadow-sm)]">
            <div class="font-bold text-sm mb-3 p-3 rounded-[var(--radius-md)] flex items-center gap-2 bg-[#FFF3CD] text-[#856404] border border-[#FFE69C]">
              <Bell :size="18" />
              ออเดอร์จากลูกค้า (รอยืนยัน)
            </div>
            <div class="flex flex-col gap-2">
              <div
                v-for="order in awaitingConfirmOrders"
                :key="order.id"
                class="flex justify-between items-center p-3 rounded-[var(--radius-md)] bg-[var(--color-bg-primary)] border border-[var(--color-secondary-dark)] gap-3"
              >
                <div class="flex-1">
                  <div class="font-semibold text-black mb-1">{{ order.product?.name }}</div>
                  <div class="text-[13px] text-black">จำนวน: {{ order.quantity }}</div>
                </div>
                <div class="flex gap-1.5">
                  <button @click="confirmCancelOrder(order.id)" class="btn-reject-small">
                    <X :size="16" />
                  </button>
                  <button @click="updateOrderStatus(order.id, 'pending')" class="btn-confirm-small">
                    <Check :size="16" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Staff Cart -->
          <div class="bg-white rounded-[var(--radius-lg)] p-5 shadow-[var(--shadow-sm)]">
            <h3 class="text-lg font-bold text-black m-0 mb-4 flex items-center gap-2.5">
              <ShoppingCart :size="20" />
              รายการที่พนักงานเลือก
            </h3>
            
            <div v-if="cart.length === 0" class="text-center text-black py-5 text-sm">
              ยังไม่มีรายการใหม่
            </div>

            <div v-else class="flex flex-col gap-3 mb-4">
              <div v-for="(item, index) in cart" :key="index" class="flex justify-between items-center p-3 bg-[var(--color-bg-primary)] border border-[var(--color-secondary-dark)] rounded-[var(--radius-md)] gap-3">
                <div class="flex-1">
                  <div class="font-semibold text-black mb-1">{{ item.name }}</div>
                  <div class="text-[13px] text-black">{{ item.price }} × {{ item.qty }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="decreaseQty(index)" class="btn-qty">
                    <Minus :size="14" />
                  </button>
                  <span class="min-w-[24px] text-center font-semibold text-black">{{ item.qty }}</span>
                  <button @click="increaseQty(index)" class="btn-qty">
                    <Plus :size="14" />
                  </button>
                  <button @click="removeFromCart(index)" class="btn-remove">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-between items-center py-4 border-t-2 border-[var(--color-secondary-dark)] mb-4 font-bold text-black">
              <span>รวมรายการใหม่:</span>
              <span class="text-xl text-[var(--color-text-price)]">{{ formatPrice(totalPrice) }} ฿</span>
            </div>

            <button
              class="btn-submit-order"
              :disabled="cart.length === 0"
              @click="submitOrder"
            >
              <Check :size="18" />
              ยืนยันรายการพนักงาน
            </button>
          </div>

          <!-- Order History -->
          <div class="bg-white rounded-[var(--radius-lg)] p-5 shadow-[var(--shadow-sm)]">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-bold text-black m-0 flex items-center gap-2.5">
                <ClipboardList :size="20" />
                ประวัติการสั่ง
              </h3>
              <button 
                v-if="hasPendingOrders"
                @click="handleCompleteAll" 
                class="btn-complete"
              >
                <Check :size="14" /> เสร็จสิ้นทั้งหมด
              </button>
            </div>
            <div class="max-h-[300px] overflow-y-auto flex flex-col gap-3">
              <div
                v-for="order in activeAndPastOrders"
                :key="order.id"
                class="flex justify-between items-start p-3 bg-[var(--color-bg-primary)] border border-[var(--color-secondary-dark)] rounded-[var(--radius-md)] gap-3"
                :class="{ 'opacity-60 bg-[var(--color-bg-secondary)]': order.status === 'cancelled' }"
              >
                <div class="flex-1">
                  <div class="font-semibold text-black mb-1">{{ order.product?.name }}</div>
                  <div class="text-[13px] text-black mb-1.5">จำนวน: {{ order.quantity }}</div>
                  <div class="mt-1">
                    <span
                      class="status-badge"
                      :class="{
                        'status-pending': order.status === 'pending',
                        'status-completed': order.status === 'completed',
                        'status-cancelled': order.status === 'cancelled'
                      }"
                    >
                      <Clock v-if="order.status === 'pending'" :size="12" />
                      <Check v-if="order.status === 'completed'" :size="12" />
                      <X v-if="order.status === 'cancelled'" :size="12" />
                      {{ order.status === 'pending' ? 'กำลังทำ' : (order.status === 'completed' ? 'เสร็จแล้ว' : 'ยกเลิกแล้ว') }}
                    </span>
                  </div>
                </div>
                <div v-if="order.status === 'pending'" class="flex flex-col gap-1.5">
                  <button @click="updateOrderStatus(order.id, 'completed')" class="btn-complete">
                    <Check :size="12" /> เสร็จ
                  </button>
                  <button @click="confirmCancelOrder(order.id)" class="btn-cancel">
                    <RotateCcw :size="12" /> ยกเลิก
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="showQrModal = false">
      <div class="modal-card max-w-[400px]">
        <div class="p-6 pb-4 border-b border-[var(--color-secondary-dark)]">
          <h3 class="m-0 text-xl font-bold text-black flex items-center gap-2.5">
            <QrCode :size="20" class="text-[var(--color-primary)]" />
            สแกนเพื่อสั่งอาหาร
          </h3>
        </div>
        <div class="p-6 text-center">
          <p class="text-base text-black mb-4">โต๊ะ: <strong class="text-lg">{{ tableName || tableId }}</strong></p>
          <div class="bg-white border-3 border-[var(--color-secondary-dark)] rounded-[var(--radius-lg)] p-5 inline-block mb-4">
            <QrcodeVue :value="qrUrl" :size="220" level="H" />
          </div>
          <p class="text-black text-[13px] m-0">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
        </div>

        <!-- Print Receipt (Teleport) -->
        <teleport to="body">
          <div v-if="showQrModal" id="receipt-print-area" class="print-only">
            <div class="receipt-header">
              <h2 class="receipt-shop-name">☕ Board Game Cafe</h2>
              <div class="receipt-divider">--------------------------------</div>
              <h1 class="receipt-table-num">โต๊ะ: {{ tableName || tableId }}</h1>
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

        <div class="p-4 border-t border-[var(--color-secondary-dark)] flex gap-3 justify-end">
          <button @click="showQrModal = false" class="btn-secondary">ปิด</button>
          <button @click="printQr" class="btn-primary">พิมพ์</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';
import { useAlert } from '../composables/useAlert';
import { 
  ArrowLeft, ShoppingCart, Clock, QrCode, Banknote, 
  Coffee, Utensils, Package, Bell, Trash2, 
  Plus, Minus, Check, X, ClipboardList, RotateCcw, Printer
} from 'lucide-vue-next';

export default {
  components: {
    QrcodeVue, ArrowLeft, ShoppingCart, Clock, QrCode, Banknote,
    Coffee, Utensils, Package, Bell, Trash2,
    Plus, Minus, Check, X, ClipboardList, RotateCcw, Printer
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const { success, error, warning, confirm, loading: showAlertLoading, close: closeAlert } = useAlert();
    const tableId = route.params.id;
    const tableName = ref('');
    const products = ref([]);
    const cart = ref([]); 
    const orderHistory = ref([]);
    const currentTab = ref('drink');
    const guestToken = ref('');
    const showQrModal = ref(false);
    const currentTime = ref(new Date());
    const sessionStartTime = ref(null);
    let timerInterval = null;
    let pollingInterval = null;

    const categories = [
      { name: 'เครื่องดื่ม', type: 'drink', icon: 'Coffee' },
      { name: 'อาหาร', type: 'food', icon: 'Utensils' },
      { name: 'สินค้า', type: 'retail', icon: 'Package' },
    ];

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

    const formatPrice = (value) => {
        return new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }).format(value || 0);
    };

    const qrUrl = computed(() => {
        if (!guestToken.value) return '';
        return `${window.location.origin}/customer/menu?token=${guestToken.value}`;
    });

    const fetchProducts = async () => {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/products', { headers: { Authorization: `Bearer ${token}` } });
        products.value = response.data;
      } catch (error) { console.error(error); }
    };

    const filteredProducts = computed(() => {
      return (products.value || []).filter(p => p.category && p.category.type === currentTab.value);
    });

    const addToCart = (product) => {
      const existingItem = cart.value.find(item => item.id === product.id);
      if (existingItem) { existingItem.qty++; } 
      else { cart.value.push({ ...product, qty: 1 }); }
    };

    const decreaseQty = (index) => { if (cart.value[index].qty > 1) cart.value[index].qty--; else removeFromCart(index); };
    const increaseQty = (index) => { cart.value[index].qty++; };
    const removeFromCart = (index) => { cart.value.splice(index, 1); };
    const totalPrice = computed(() => cart.value.reduce((sum, item) => sum + (item.price * item.qty), 0));
    const awaitingConfirmOrders = computed(() => orderHistory.value.filter(o => o.status.toLowerCase() === 'confirming'));
    const activeAndPastOrders = computed(() => orderHistory.value.filter(o => o.status.toLowerCase() !== 'confirming'));
    const hasPendingOrders = computed(() => orderHistory.value.some(o => o.status.toLowerCase() === 'pending'));

    const handleCompleteAll = async () => {
        const isConfirmed = await confirm('ยืนยันทำรายการเสร็จสิ้นทั้งหมด?', 'รายการที่กำลังทำอยู่ทั้งหมดจะถูกเปลี่ยนเป็นเสร็จสิ้น');
        if (!isConfirmed) return;
        try {
            const token = localStorage.getItem('token');
            await axios.post(`/api/tables/${tableId}/orders/complete-all`, {}, { headers: { Authorization: `Bearer ${token}` } });
            success('รายการทั้งหมดเสร็จสมบูรณ์');
            fetchOrderHistory();
        } catch (err) { error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message); }
    };

    const fetchOrderHistory = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get(`/api/tables/${tableId}/orders`, { headers: { Authorization: `Bearer ${token}` } });
            const newOrders = response.data;
            const currentConfirmingCount = awaitingConfirmOrders.value.length;
            const newConfirmingCount = newOrders.filter(o => o.status.toLowerCase() === 'confirming').length;
            if (newConfirmingCount > currentConfirmingCount) success('🔔 มีออเดอร์ใหม่จากลูกค้า!');
            orderHistory.value = newOrders;
        } catch (err) { console.error('Fetch history error:', err); }
    };

    const updateOrderStatus = async (orderId, newStatus) => {
        try {
            const token = localStorage.getItem('token');
            await axios.put(`/api/orders/${orderId}/status`, { status: newStatus }, { headers: { Authorization: `Bearer ${token}` } });
            if (newStatus === 'pending') success('ยืนยันรายการแล้ว');
            else if (newStatus === 'completed') success('รายการเสร็จสมบูรณ์');
            else warning('ยกเลิกรายการแล้ว');
            fetchOrderHistory();
        } catch (err) { error('ไม่สามารถอัปเดตสถานะได้', err.response?.data?.message || err.message); }
    };

    const confirmCancelOrder = async (orderId) => {
        const isConfirmed = await confirm('⚠️ ยืนยันการยกเลิกรายการนี้?', '(สต็อกจะถูกคืน และยอดเงินจะถูกหักออกจากบิล)');
        if (isConfirmed) updateOrderStatus(orderId, 'cancelled');
    };

    const submitOrder = async () => {
        if (cart.value.length === 0) return;
        const isConfirmed = await confirm('ยืนยันการสั่ง?', `สั่งอาหาร รวม ${totalPrice.value} บาท?`);
        if(!isConfirmed) return;
        try {
            const token = localStorage.getItem('token');
            showAlertLoading('กำลังส่งออเดอร์...');
            await axios.post('/api/orders', {
                table_id: tableId, items: cart.value.map(item => ({ id: item.id, qty: item.qty }))
            }, { headers: { Authorization: `Bearer ${token}` } });
            success('✅ สั่งอาหารสำเร็จ!');
            cart.value = []; 
            fetchOrderHistory();
        } catch (err) { error('❌ สั่งอาหารไม่สำเร็จ', err.response?.data?.message || 'โปรดลองใหม่'); }
    };

    const handleCheckout = async () => {
      const outstandingOrders = orderHistory.value.filter(o => 
        o.status.toLowerCase() === 'confirming' || o.status.toLowerCase() === 'pending'
      );
      if (outstandingOrders.length > 0) {
        warning('มีออเดอร์ตกค้าง!', `กรุณาจัดการออเดอร์ที่ยังค้างอยู่ (${outstandingOrders.length} รายการ) ให้เรียบร้อยก่อนเช็คบิล`);
        return;
      }
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/tables/${tableId}/bill`, { headers: { Authorization: `Bearer ${token}` } });
        const data = response.data;
        let serviceBreakdown = '';
        if (data.day_pass_count > 0) {
          serviceBreakdown += `<div style="display: flex; justify-content: space-between;"><span>🎟️ Day Pass (${data.day_pass_count} คน):</span><strong>${formatPrice(data.costs.day_pass)} ฿</strong></div>`;
        }
        if (data.regular_count > 0) {
          serviceBreakdown += `<div style="display: flex; justify-content: space-between;"><span>⏱️ ค่าชั่วโมง (${data.regular_count} คน):</span><strong>${formatPrice(data.costs.regular_time)} ฿</strong></div>`;
        }
        const htmlContent = `
            <div style="text-align: left; font-family: 'Sarabun', sans-serif;">
                <p><strong>👥 รวมลูกค้า:</strong> ${data.pax} คน</p><hr>
                ${serviceBreakdown}
                <div style="display: flex; justify-content: space-between;"><span>ค่าบริการรวม:</span><strong>${formatPrice(data.costs.time)} ฿</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>🍔 ค่าอาหาร/น้ำ:</span><strong>${formatPrice(data.costs.food)} ฿</strong></div><hr>
                <div style="display: flex; justify-content: space-between; font-size: 1.2em; color: #4B3621;"><span>💰 ยอดสุทธิ:</span><strong>${formatPrice(data.costs.total)} ฿</strong></div>
            </div>`;
        const isConfirmed = await confirm(`สรุปยอดเงิน (โต๊ะ ${tableName.value || tableId})`, htmlContent, 'เช็คบิล & ปิดโต๊ะ');
        if (isConfirmed) {
          showAlertLoading('กำลังปิดโต๊ะ...');
          await axios.post(`/api/tables/${tableId}/checkout`, {}, { headers: { Authorization: `Bearer ${token}` } });
          success('ปิดโต๊ะเรียบร้อย');
          router.push('/pos');
        }
      } catch (err) { error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message); }
    };

    const checkTableStatus = async () => {
      try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/api/tables/${tableId}`, { headers: { Authorization: `Bearer ${token}` } });
          const tableData = response.data;
          tableName.value = tableData.name;
          if (tableData.is_available) { error('โต๊ะนี้ยังไม่ได้เปิด', `สถานะปัจจุบัน: ว่าง`); router.push('/pos'); }
          else {
              const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
              const activeSession = tableData.sessions ? tableData.sessions[0] : null;
              if (currentUser.role !== 'admin' && activeSession && activeSession.user_id !== currentUser.id) {
                  warning('ไม่มีสิทธิ์เข้าถึง', 'โต๊ะนี้ถูกเปิดโดยพนักงานท่านอื่น'); router.push('/pos'); return;
              }
              if (tableData.sessions && tableData.sessions.length > 0) {
                  guestToken.value = tableData.sessions[0].guest_token;
                  sessionStartTime.value = tableData.sessions[0].start_time;
              }
          }
      } catch (err) { console.error(err); router.push('/pos'); }
    };

    const printQr = () => { window.print(); };

    onMounted(() => {
      checkTableStatus(); fetchProducts(); fetchOrderHistory();
      timerInterval = setInterval(() => { currentTime.value = new Date(); }, 1000);
      pollingInterval = setInterval(fetchOrderHistory, 5000);
    });

    onUnmounted(() => {
      if (timerInterval) clearInterval(timerInterval);
      if (pollingInterval) clearInterval(pollingInterval);
    });

    return { 
      tableId, tableName, products, cart, categories, currentTab, filteredProducts, totalPrice,
      addToCart, decreaseQty, increaseQty, removeFromCart, submitOrder,
      handleCheckout, guestToken, showQrModal, qrUrl, formatDuration, sessionStartTime,
      orderHistory, updateOrderStatus, confirmCancelOrder, handleCompleteAll,
      awaitingConfirmOrders, activeAndPastOrders, hasPendingOrders, formatPrice, printQr
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

/* Header */
.order-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  box-shadow: var(--shadow-md); position: sticky; top: 0; z-index: 100;
}

/* Content Grid */
.content-grid { display: grid; grid-template-columns: 1fr 400px; gap: 32px; }

/* Header Buttons */
.btn-back, .btn-qr, .btn-checkout {
  padding: 10px 18px; border-radius: 20px; font-weight: 600; font-size: 14px;
  cursor: pointer; transition: var(--transition-normal); border: none;
  display: flex; align-items: center; gap: 8px;
}
.btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); }
.btn-back:hover { background: rgba(255,255,255,0.25); }
.btn-qr { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); }
.btn-qr:hover { background: rgba(255,255,255,0.25); }
.btn-checkout { background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%); color: white; }
.btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(76,175,142,0.3); }

/* Category Button */
.category-btn {
  padding: 10px 20px; border-radius: 20px; border: 2px solid var(--color-secondary-dark);
  background: white; color: #000; font-weight: 600; font-size: 14px; cursor: pointer;
  transition: var(--transition-normal); white-space: nowrap; display: flex; align-items: center; gap: 8px;
}
.category-btn:hover { border-color: var(--color-primary); background: var(--color-bg-primary); }
.category-btn.active { background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%); color: white; border-color: var(--color-action); }

/* Product Card */
.product-card {
  background: var(--color-table-row); border: 2px solid var(--color-table-border);
  border-radius: var(--radius-lg); padding: 20px; cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1); text-align: center;
  min-height: 100px; display: flex; flex-direction: column; justify-content: center; gap: 8px;
}
.product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--color-action); }

/* Action Buttons */
.btn-reject-small, .btn-confirm-small {
  width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer;
  font-size: 14px; transition: var(--transition-normal);
  display: flex; align-items: center; justify-content: center;
}
.btn-reject-small { background: #FFE4E1; color: var(--color-danger); }
.btn-reject-small:hover { background: var(--color-danger); color: white; }
.btn-confirm-small { background: var(--color-success); color: white; }
.btn-confirm-small:hover { background: rgb(173,222,184); color: var(--color-success); }

/* Qty and Remove Buttons */
.btn-qty {
  width: 28px; height: 28px; border-radius: 50%; border: 1px solid var(--color-secondary-dark);
  background: white; color: #000; font-weight: 700; cursor: pointer;
  transition: var(--transition-normal); display: flex; align-items: center; justify-content: center;
}
.btn-qty:hover { background: var(--color-bg-primary); border-color: var(--color-primary); }
.btn-remove {
  width: 28px; height: 28px; border-radius: 50%; border: none; background: #FFE4E1;
  color: var(--color-danger); font-weight: 700; cursor: pointer; margin-left: 4px;
  display: flex; align-items: center; justify-content: center; transition: var(--transition-normal);
}
.btn-remove:hover { background: var(--color-danger); color: white; }

/* Submit Order */
.btn-submit-order {
  width: 100%; padding: 14px; border-radius: 24px; border: none;
  background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%);
  color: white; font-weight: 700; font-size: 15px; cursor: pointer;
  transition: var(--transition-normal); display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-order:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(76,175,142,0.3); }
.btn-submit-order:disabled { opacity: 0.5; cursor: not-allowed; }

/* Status Badges */
.status-badge { display: flex; align-items: center; gap: 4px; width: fit-content; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.status-pending { background: #FFF3CD; color: #856404; }
.status-completed { background: #D4EDDA; color: #155724; }
.status-cancelled { background: #F8D7DA; color: #721C24; }

/* History Action Buttons */
.btn-complete, .btn-cancel {
  padding: 6px 12px; border-radius: 12px; border: none; font-weight: 600; font-size: 12px;
  cursor: pointer; transition: var(--transition-normal); white-space: nowrap;
  display: flex; align-items: center; gap: 4px;
}
.btn-complete { background: var(--color-success); color: white; }
.btn-complete:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(102,187,106,0.3); background: var(--color-success-light); color: var(--color-success); }
.btn-cancel { background: #FFE4E1; color: var(--color-danger); }
.btn-cancel:hover { background: var(--color-danger); color: white; }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 20px; animation: fadeIn 0.2s ease;
}
.modal-card {
  background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
  max-width: 500px; width: 100%; max-height: 90vh; overflow: hidden; animation: slideUp 0.3s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Modal Buttons */
.btn-primary, .btn-secondary {
  padding: 12px 24px; border-radius: 20px; font-weight: 600; font-size: 14px;
  cursor: pointer; transition: var(--transition-normal); border: none;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-primary { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,140,66,0.3); }
.btn-secondary { background: var(--color-divider); color: #000; }
.btn-secondary:hover { background: #C1C5CB; transform: translateY(-2px); }

/* Responsive */
@media (max-width: 1024px) {
  .content-grid { grid-template-columns: 1fr; }
  .content-grid > div:last-child { order: -1; }
}
@media (max-width: 768px) {
  .content-grid .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .btn-back, .btn-qr, .btn-checkout { font-size: 12px; padding: 8px 12px; }
  .category-btn { font-size: 13px; padding: 8px 16px; }
}
</style>