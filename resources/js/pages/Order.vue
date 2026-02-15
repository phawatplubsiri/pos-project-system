<template>
  <div class="order-page">
    <!-- Header -->
    <div class="order-header">
      <div class="header-container">
        <div class="header-left">
          <button @click="$router.go(-1)" class="btn-back">←</button>
          <div class="table-info">
            <h1 class="table-title">🛒 โต๊ะ {{ tableName || tableId }}</h1>
            <div class="table-timer">⏱️ {{ formatDuration(sessionStartTime) }}</div>
          </div>
        </div>
        
        <div class="header-actions">
          <button @click="showQrModal = true" class="btn-qr">📱 QR Code</button>
          <button @click="handleCheckout" class="btn-checkout">💰 เช็คบิล / ปิดโต๊ะ</button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="order-content">
      <div class="content-grid">
        <!-- Products Section -->
        <div class="products-section">
          <!-- Category Filters -->
          <div class="category-filters">
            <button
              v-for="cat in categories"
              :key="cat.type"
              @click="currentTab = cat.type"
              class="category-btn"
              :class="{ active: currentTab === cat.type }"
            >
              {{ cat.name }}
            </button>
          </div>

          <!-- Product Grid -->
          <div class="product-grid">
            <div
              v-for="product in filteredProducts"
              :key="product.id"
              class="product-card"
              @click="addToCart(product)"
            >
              <div class="product-name">{{ product.name }}</div>
              <div class="product-price">{{ formatPrice(product.price) }} ฿</div>
            </div>
          </div>
        </div>

        <!-- Cart & Orders Section -->
        <div class="cart-section">
          <!-- Awaiting Confirmation Orders -->
          <div v-if="awaitingConfirmOrders.length > 0" class="awaiting-orders">
            <div class="section-header alert-header">
              🔔 ออเดอร์จากลูกค้า (รอยืนยัน)
            </div>
            <div class="order-list">
              <div
                v-for="order in awaitingConfirmOrders"
                :key="order.id"
                class="order-item awaiting-item"
              >
                <div class="order-info">
                  <div class="order-product">{{ order.product?.name }}</div>
                  <div class="order-qty">จำนวน: {{ order.quantity }}</div>
                </div>
                <div class="order-actions">
                  <button @click="confirmCancelOrder(order.id)" class="btn-reject-small">❌</button>
                  <button @click="updateOrderStatus(order.id, 'pending')" class="btn-confirm-small">✅</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Staff Cart -->
          <div class="cart-card">
            <h3 class="cart-title">🛒 รายการที่พนักงานเลือก</h3>
            
            <div v-if="cart.length === 0" class="empty-cart">
              ยังไม่มีรายการใหม่
            </div>

            <div v-else class="cart-items">
              <div v-for="(item, index) in cart" :key="index" class="cart-item">
                <div class="item-info">
                  <div class="item-name">{{ item.name }}</div>
                  <div class="item-detail">{{ item.price }} × {{ item.qty }}</div>
                </div>
                <div class="item-controls">
                  <button @click="decreaseQty(index)" class="btn-qty">-</button>
                  <span class="qty-display">{{ item.qty }}</span>
                  <button @click="increaseQty(index)" class="btn-qty">+</button>
                  <button @click="removeFromCart(index)" class="btn-remove">×</button>
                </div>
              </div>
            </div>

            <div class="cart-total">
              <span>รวมรายการใหม่:</span>
              <span class="total-amount">{{ formatPrice(totalPrice) }} ฿</span>
            </div>

            <button
              class="btn-submit-order"
              :disabled="cart.length === 0"
              @click="submitOrder"
            >
              ✅ ยืนยันรายการพนักงาน
            </button>
          </div>

          <!-- Order History -->
          <div class="history-card">
            <h3 class="history-title">📝 ประวัติการสั่ง</h3>
            <div class="history-list">
              <div
                v-for="order in activeAndPastOrders"
                :key="order.id"
                class="history-item"
                :class="{ cancelled: order.status === 'cancelled' }"
              >
                <div class="history-info">
                  <div class="history-product">{{ order.product?.name }}</div>
                  <div class="history-qty">จำนวน: {{ order.quantity }}</div>
                  <div class="history-status">
                    <span
                      class="status-badge"
                      :class="{
                        'status-pending': order.status === 'pending',
                        'status-completed': order.status === 'completed',
                        'status-cancelled': order.status === 'cancelled'
                      }"
                    >
                      {{ order.status === 'pending' ? '⏳ กำลังทำ' : (order.status === 'completed' ? '✅ เสร็จแล้ว' : '❌ ยกเลิกแล้ว') }}
                    </span>
                  </div>
                </div>
                <div v-if="order.status === 'pending'" class="history-actions">
                  <button @click="updateOrderStatus(order.id, 'completed')" class="btn-complete">เสร็จ</button>
                  <button @click="confirmCancelOrder(order.id)" class="btn-cancel">ยกเลิก</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="modal-overlay" @click.self="showQrModal = false">
      <div class="modal-card modal-small">
        <div class="modal-header">
          <h3>📱 สแกนเพื่อสั่งอาหาร</h3>
        </div>
        <div class="modal-body text-center">
          <p class="qr-table-name">โต๊ะ: <strong>{{ tableName || tableId }}</strong></p>
          <div class="qr-code-wrapper">
            <QrcodeVue :value="qrUrl" :size="220" level="H" />
          </div>
          <p class="qr-instruction">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
        </div>
        <div class="modal-footer">
          <button @click="showQrModal = false" class="btn-primary full-width">ปิด</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router'; // ✅ เพิ่ม useRouter
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';
import { useAlert } from '../composables/useAlert';

export default {
  components: {
    QrcodeVue
  },
  setup() {
    const route = useRoute();
    const router = useRouter(); // ✅ เรียกใช้ router
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
      { name: '🥤 เครื่องดื่ม', type: 'drink' },
      { name: '🍟 อาหาร', type: 'food' },
      { name: '📦 สินค้า', type: 'retail' },
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
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
    };

    const qrUrl = computed(() => {
        if (!guestToken.value) return '';
        const baseUrl = window.location.origin;
        return `${baseUrl}/customer/menu?token=${guestToken.value}`;
    });

    const fetchProducts = async () => {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/products', {
            headers: { Authorization: `Bearer ${token}` }
        });
        products.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    const filteredProducts = computed(() => {
      return (products.value || []).filter(p => p.category && p.category.type === currentTab.value);
    });

    const addToCart = (product) => {
      const existingItem = cart.value.find(item => item.id === product.id);
      if (existingItem) {
        existingItem.qty++; 
      } else {
        cart.value.push({ ...product, qty: 1 });
      }
    };

    const decreaseQty = (index) => {
        if (cart.value[index].qty > 1) {
            cart.value[index].qty--;
        } else {
            removeFromCart(index); 
        }
    };

    const increaseQty = (index) => {
        cart.value[index].qty++;
    };

    const removeFromCart = (index) => {
        cart.value.splice(index, 1);
    };

    const totalPrice = computed(() => {
        return cart.value.reduce((sum, item) => sum + (item.price * item.qty), 0);
    });

    const awaitingConfirmOrders = computed(() => {
        return orderHistory.value.filter(o => o.status.toLowerCase() === 'confirming');
    });

    const activeAndPastOrders = computed(() => {
        return orderHistory.value.filter(o => o.status.toLowerCase() !== 'confirming');
    });

    const fetchOrderHistory = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get(`/api/tables/${tableId}/orders`, {
                headers: { Authorization: `Bearer ${token}` }
            });
            
            const newOrders = response.data;
            const currentConfirmingCount = awaitingConfirmOrders.value.length;
            const newConfirmingCount = newOrders.filter(o => o.status.toLowerCase() === 'confirming').length;

            // ถ้ามีออเดอร์ใหม่ที่รอยืนยันเพิ่มขึ้น ให้โชว์ Alert
            if (newConfirmingCount > currentConfirmingCount) {
                success('🔔 มีออเดอร์ใหม่จากลูกค้า!');
            }

            orderHistory.value = newOrders;
        } catch (err) {
            console.error('Fetch history error:', err);
        }
    };

    const updateOrderStatus = async (orderId, newStatus) => {
        try {
            const token = localStorage.getItem('token');
            await axios.put(`/api/orders/${orderId}/status`, {
                status: newStatus
            }, {
                headers: { Authorization: `Bearer ${token}` }
            });

            if (newStatus === 'pending') {
                success('✅ ยืนยันรายการแล้ว');
            } else if (newStatus === 'completed') {
                success('✅ รายการเสร็จสมบูรณ์');
            } else {
                warning('❌ ยกเลิกรายการแล้ว');
            }

            fetchOrderHistory(); // โหลดใหม่หลังอัปเดต
        } catch (err) {
            error('ไม่สามารถอัปเดตสถานะได้', err.response?.data?.message || err.message);
        }
    };

    const confirmCancelOrder = async (orderId) => {
        const isConfirmed = await confirm('⚠️ ยืนยันการยกเลิกรายการนี้?', '(สต็อกจะถูกคืน และยอดเงินจะถูกหักออกจากบิล)');
        if (isConfirmed) {
            updateOrderStatus(orderId, 'cancelled');
        }
    };

    const submitOrder = async () => {
        if (cart.value.length === 0) return;
        
        const isConfirmed = await confirm('ยืนยันการสั่ง?', `สั่งอาหาร รวม ${totalPrice.value} บาท?`);
        if(!isConfirmed) return;

        try {
            const token = localStorage.getItem('token');
            showAlertLoading('กำลังส่งออเดอร์...');
            await axios.post('/api/orders', {
                table_id: tableId, 
                items: cart.value.map(item => ({
                    id: item.id,
                    qty: item.qty
                }))
            }, {
                headers: { Authorization: `Bearer ${token}` }
            });

            success('✅ สั่งอาหารสำเร็จ!');
            cart.value = []; 
            fetchOrderHistory(); // <--- เพิ่มให้โหลดประวัติใหม่ทันที
            
        } catch (err) {
            error('❌ สั่งอาหารไม่สำเร็จ', err.response?.data?.message || 'โปรดลองใหม่');
        }
    };

    // ✅ ฟังก์ชันเช็คบิล / ปิดโต๊ะ (เพิ่มใหม่)
    const handleCheckout = async () => {
      // ตรวจสอบออเดอร์ตกค้าง (ที่ยังไม่เสร็จหรือยังไม่ถูกยกเลิก)
      const outstandingOrders = orderHistory.value.filter(o => 
        o.status.toLowerCase() === 'confirming' || o.status.toLowerCase() === 'pending'
      );

      if (outstandingOrders.length > 0) {
        warning(
          'มีออเดอร์ตกค้าง!', 
          `กรุณาจัดการออเดอร์ที่ยังค้างอยู่ (${outstandingOrders.length} รายการ) ให้เรียบร้อยก่อนเช็คบิล`
        );
        return;
      }

      try {
        const token = localStorage.getItem('token');
        
        // 1. ดึงข้อมูลบิลมาดู (Preview)
        const response = await axios.get(`/api/tables/${tableId}/bill`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        const data = response.data;
        
        // 2. เตรียมข้อความสรุป
        const timeLabel = data.is_day_pass ? '🎟️ ประเภท: 1 Day Pass (เหมาวัน)' : `⏱️ เวลาที่เล่น: ${data.duration_minutes} นาที`;
        const htmlContent = `
            <div style="text-align: left; font-family: 'Sarabun', sans-serif;">
                <p><strong>👥 ลูกค้า:</strong> ${data.pax} คน</p>
                <p><strong>${timeLabel}</strong></p>
                <hr>
                <div style="display: flex; justify-content: space-between;">
                    <span>💵 ค่าบริการ:</span>
                    <strong>${formatPrice(data.costs.time)} ฿</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>🍔 ค่าอาหาร/น้ำ:</span>
                    <strong>${formatPrice(data.costs.food)} ฿</strong>
                </div>
                <hr>
                <div style="display: flex; justify-content: space-between; font-size: 1.2em; color: #4B3621;">
                    <span>💰 ยอดสุทธิ:</span>
                    <strong>${formatPrice(data.costs.total)} ฿</strong>
                </div>
            </div>
        `;

        // 3. ถามยืนยัน
        const result = await import('sweetalert2').then(Swal => Swal.default.fire({
            title: `🧾 สรุปยอดเงิน (โต๊ะ ${tableName.value || tableId})`,
            html: htmlContent,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#4CAF8E',
            cancelButtonColor: '#E5533D',
            confirmButtonText: '💰 เช็คบิล & ปิดโต๊ะ',
            cancelButtonText: 'กลับไปแก้ไข',
            background: '#F6F5F2'
        }));

        if (result.isConfirmed) {
            showAlertLoading('กำลังปิดโต๊ะ...');
            // 4. ยิง API ปิดโต๊ะ
            await axios.post(`/api/tables/${tableId}/checkout`, {}, {
                headers: { Authorization: `Bearer ${token}` }
            });

            success('✅ ปิดโต๊ะเรียบร้อย ขอบคุณครับ!');
            router.push('/pos'); // เด้งกลับหน้าผังร้าน
        }

      } catch (err) {
        error('เกิดข้อผิดพลาด', err.response?.data?.message || err.message);
      }
    };
    const checkTableStatus = async () => {
      try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/api/tables/${tableId}`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        const tableData = response.data;
        const status = (tableData.status || '').toLowerCase(); 
        tableName.value = tableData.name;

        if (status !== 'busy' && status !== 'occupied') {
            error('โต๊ะนี้ยังไม่ได้เปิด', `สถานะปัจจุบัน: ${status}`);
            router.push('/pos'); 
        } else {
            // เก็บ Token ไว้ใช้ทำ QR
            if (tableData.sessions && tableData.sessions.length > 0) {
                guestToken.value = tableData.sessions[0].guest_token;
                sessionStartTime.value = tableData.sessions[0].start_time;
            }
        }

      } catch (err) {
        console.error(err);
        router.push('/pos'); 
      }
    };

    onMounted(() => {
      checkTableStatus();
      fetchProducts();
      fetchOrderHistory(); // <--- โหลดประวัติเมื่อเปิดหน้า
      timerInterval = setInterval(() => {
          currentTime.value = new Date();
      }, 1000);
      pollingInterval = setInterval(fetchOrderHistory, 5000); // เช็คออเดอร์ใหม่ทุก 5 วินาที
    });

    onUnmounted(() => {
      if (timerInterval) clearInterval(timerInterval);
      if (pollingInterval) clearInterval(pollingInterval);
    });

    return { 
      tableId, tableName, products, cart, categories, currentTab, filteredProducts, totalPrice,
      addToCart, decreaseQty, increaseQty, removeFromCart, submitOrder,
      handleCheckout, guestToken, showQrModal, qrUrl, formatDuration, sessionStartTime,
      orderHistory, updateOrderStatus, confirmCancelOrder,
      awaitingConfirmOrders, activeAndPastOrders, formatPrice
    };

    
  }
};
</script>

<style scoped>
/* ========== Global Styles ========== */
.order-page {
  min-height: 100vh;
  background: var(--color-bg-primary);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* ========== Header ========== */
.order-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  box-shadow: var(--shadow-md);
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

.btn-back {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-normal);
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.25);
}

.table-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.table-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-highlight-light);
  margin: 0;
}

.table-timer {
  font-family: 'Courier New', monospace;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn-qr,
.btn-checkout {
  padding: 10px 18px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-normal);
  border: none;
}

.btn-qr {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-qr:hover {
  background: rgba(255, 255, 255, 0.25);
}

.btn-checkout {
  background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%);
  color: white;
}

.btn-checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
}

/* ========== Main Content ========== */
.order-content {
  max-width: 1600px;
  margin: 0 auto;
  padding: 32px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 32px;
}

/* ========== Products Section ========== */
.products-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.category-filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  overflow-x: auto;
  padding-bottom: 8px;
}

.category-btn {
  padding: 10px 20px;
  border-radius: 20px;
  border: 2px solid var(--color-secondary-dark);
  background: white;
  color: var(--color-accent);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-normal);
  white-space: nowrap;
}

.category-btn:hover {
  border-color: var(--color-primary);
  background: var(--color-bg-primary);
}

.category-btn.active {
  background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%);
  color: white;
  border-color: var(--color-action);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
}

.product-card {
  background: var(--color-table-row);
  border: 2px solid var(--color-table-border);
  border-radius: var(--radius-lg);
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: center;
  min-height: 100px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-action);
}

.product-name {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: 15px;
}

.product-price {
  font-weight: 700;
  color: var(--color-text-price);
  font-size: 16px;
}

/* ========== Cart Section ========== */
.cart-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.awaiting-orders,
.cart-card,
.history-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 20px;
  box-shadow: var(--shadow-sm);
}

.section-header {
  font-weight: 700;
  font-size: 14px;
  margin-bottom: 12px;
  padding: 12px;
  border-radius: var(--radius-md);
}

.alert-header {
  background: #FFF3CD;
  color: #856404;
  border: 1px solid #FFE69C;
}

.order-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-radius: var(--radius-md);
  gap: 12px;
}

.awaiting-item {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-secondary-dark);
}

.order-info {
  flex: 1;
}

.order-product {
  font-weight: 600;
  color: var(--color-accent);
  margin-bottom: 4px;
}

.order-qty {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.order-actions {
  display: flex;
  gap: 6px;
}

.btn-reject-small,
.btn-confirm-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  font-size: 14px;
  transition: var(--transition-normal);
}

.btn-reject-small {
  background: #FFE4E1;
}

.btn-reject-small:hover {
  background: var(--color-danger);
}

.btn-confirm-small {
  background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success) 100%);
}

.btn-confirm-small:hover {
  transform: scale(1.1);
}

/* Cart Card */
.cart-title,
.history-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-accent);
  margin: 0 0 16px 0;
}

.empty-cart {
  text-align: center;
  color: var(--color-text-light);
  padding: 20px;
  font-size: 14px;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.cart-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-secondary-dark);
  border-radius: var(--radius-md);
  gap: 12px;
}

.item-info {
  flex: 1;
}

.item-name {
  font-weight: 600;
  color: var(--color-accent);
  margin-bottom: 4px;
}

.item-detail {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.item-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-qty {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1px solid var(--color-secondary-dark);
  background: white;
  color: var(--color-accent);
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition-normal);
}

.btn-qty:hover {
  background: var(--color-bg-primary);
  border-color: var(--color-primary);
}

.qty-display {
  min-width: 24px;
  text-align: center;
  font-weight: 600;
  color: var(--color-accent);
}

.btn-remove {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: #FFE4E1;
  color: var(--color-danger);
  font-weight: 700;
  font-size: 18px;
  cursor: pointer;
  transition: var(--transition-normal);
  margin-left: 4px;
}

.btn-remove:hover {
  background: var(--color-danger);
  color: white;
}

.cart-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 2px solid var(--color-secondary-dark);
  margin-bottom: 16px;
  font-weight: 700;
  color: var(--color-accent);
}

.total-amount {
  font-size: 20px;
  color: var(--color-text-price);
}

.btn-submit-order {
  width: 100%;
  padding: 14px;
  border-radius: 24px;
  border: none;
  background: linear-gradient(135deg, var(--color-action) 0%, var(--color-action-hover) 100%);
  color: white;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: var(--transition-normal);
}

.btn-submit-order:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
}

.btn-submit-order:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* History Card */
.history-list {
  max-height: 300px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-secondary-dark);
  border-radius: var(--radius-md);
  gap: 12px;
}

.history-item.cancelled {
  opacity: 0.6;
  background: var(--color-bg-secondary);
}

.history-info {
  flex: 1;
}

.history-product {
  font-weight: 600;
  color: var(--color-accent);
  margin-bottom: 4px;
}

.history-qty {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
}

.history-status {
  margin-top: 4px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending {
  background: #FFF3CD;
  color: #856404;
}

.status-completed {
  background: #D4EDDA;
  color: #155724;
}

.status-cancelled {
  background: #F8D7DA;
  color: #721C24;
}

.history-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.btn-complete,
.btn-cancel {
  padding: 6px 12px;
  border-radius: 12px;
  border: none;
  font-weight: 600;
  font-size: 12px;
  cursor: pointer;
  transition: var(--transition-normal);
  white-space: nowrap;
}

.btn-complete {
  background: linear-gradient(135deg, var(--color-success-light) 0%, var(--color-success) 100%);
  color: white;
}

.btn-complete:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(102, 187, 106, 0.3);
}

.btn-cancel {
  background: #FFE4E1;
  color: var(--color-danger);
}

.btn-cancel:hover {
  background: var(--color-danger);
  color: white;
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
  border-radius: var(--radius-lg);
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

.text-center {
  text-align: center;
}

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
  border-radius: var(--radius-lg);
  padding: 20px;
  display: inline-block;
  margin-bottom: 16px;
}

.qr-instruction {
  color: var(--color-text-light);
  font-size: 13px;
  margin: 0;
}

.btn-primary {
  padding: 12px 24px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-normal);
  border: none;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
}

.full-width {
  width: 100%;
}

/* ========== Responsive ========== */
@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .cart-section {
    order: -1;
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
}

@media (max-width: 768px) {
  .header-container {
    padding: 16px 20px;
  }

  .table-title {
    font-size: 20px;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .order-content {
    padding: 24px 16px;
  }

  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .history-list {
    max-height: 200px;
  }
}

@media (max-width: 480px) {
  .btn-back,
  .btn-qr,
  .btn-checkout {
    font-size: 12px;
    padding: 8px 12px;
  }

  .table-title {
    font-size: 18px;
  }

  .category-btn {
    font-size: 13px;
    padding: 8px 16px;
  }
}
</style>