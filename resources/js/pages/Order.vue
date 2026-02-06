<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-3">
        <button @click="$router.go(-1)" class="btn btn-light btn-sm">⬅ กลับ</button>
        <div>
          <h4 class="mb-0 text-brown">🛒 โต๊ะ {{ tableName || tableId }}</h4>
          <small class="text-muted">⏱️ {{ formatDuration(sessionStartTime) }}</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button @click="showQrModal = true" class="btn btn-outline-secondary btn-sm">📱 QR Code</button>
        <button @click="handleCheckout" class="btn btn-warning btn-sm">💰 เช็คบิล / ปิดโต๊ะ</button>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="mb-3">
          <div class="nav nav-pills flex-row gap-2 overflow-auto">
            <button v-for="cat in categories" :key="cat.type" @click="currentTab = cat.type"
              :class="['nav-link', { active: currentTab === cat.type } ]">
              {{ cat.name }}
            </button>
          </div>
        </div>

        <div class="row g-2">
          <div v-for="product in filteredProducts" :key="product.id" class="col-6 col-md-4">
            <div class="card card-theme h-100" @click="addToCart(product)" style="cursor:pointer;">
              <div class="card-body p-2 text-center">
                <div class="fw-semibold product-name">{{ product.name }}</div>
                <div class="text-success product-price">{{ product.price }} ฿</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card card-theme p-3">
          <div v-if="awaitingConfirmOrders.length > 0" class="mb-3">
            <div class="alert alert-warning mb-2">🔔 ออเดอร์จากลูกค้า (รอยืนยัน)</div>
            <div class="list-group mb-2">
              <div v-for="order in awaitingConfirmOrders" :key="order.id" class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">{{ order.product?.name }}</div>
                  <small class="text-muted">จำนวน: {{ order.quantity }}</small>
                </div>
                <div class="d-flex gap-1">
                  <button @click="confirmCancelOrder(order.id)" class="btn btn-sm btn-outline-danger">❌</button>
                  <button @click="updateOrderStatus(order.id, 'pending')" class="btn btn-sm btn-success">✅</button>
                </div>
              </div>
            </div>
          </div>

          <h6 class="mb-2">🛒 รายการที่พนักงานเลือก</h6>
          <div v-if="cart.length === 0" class="text-muted mb-3">ยังไม่มีรายการใหม่</div>

          <ul class="list-group mb-3">
            <li v-for="(item, index) in cart" :key="index" class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">{{ item.name }}</div>
                <small class="text-muted">{{ item.price }} x {{ item.qty }}</small>
              </div>
              <div class="d-flex align-items-center gap-2">
                <button @click="decreaseQty(index)" class="btn btn-sm btn-light">-</button>
                <span>{{ item.qty }}</span>
                <button @click="increaseQty(index)" class="btn btn-sm btn-light">+</button>
                <button @click="removeFromCart(index)" class="btn btn-sm btn-outline-danger ms-2">x</button>
              </div>
            </li>
          </ul>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>รวมรายการใหม่:</strong>
            <div class="text-success fs-5">{{ totalPrice }} ฿</div>
          </div>
          <button class="btn btn-success w-100" :disabled="cart.length===0" @click="submitOrder">✅ ยืนยันรายการพนักงาน</button>

          <hr class="my-3">

          <h6>📝 ประวัติการสั่ง</h6>
          <div class="history-list" style="max-height:220px; overflow:auto;">
            <div v-for="order in activeAndPastOrders" :key="order.id" class="d-flex justify-content-between align-items-start mb-2 p-2 rounded" :class="order.status === 'cancelled' ? 'bg-light text-muted' : 'bg-white'">
              <div>
                <div class="fw-semibold">{{ order.product?.name }}</div>
                <div class="small">จำนวน: {{ order.quantity }}</div>
                <div class="small mt-1">
                  <span class="badge bg-secondary">
                    {{ order.status === 'pending' ? '⏳ กำลังทำ' : (order.status === 'completed' ? '✅ เสร็จแล้ว' : '❌ ยกเลิกแล้ว') }}
                  </span>
                </div>
              </div>
              <div class="d-flex flex-column gap-1">
                <button v-if="order.status === 'pending'" @click="updateOrderStatus(order.id, 'completed')" class="btn btn-sm btn-success">เสร็จ</button>
                <button v-if="order.status === 'pending'" @click="confirmCancelOrder(order.id)" class="btn btn-sm btn-outline-danger">ยกเลิก</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <div v-if="showQrModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card" style="width:360px; max-width:95%;">
        <div class="card-body text-center">
          <h5 class="card-title">📱 สแกนเพื่อสั่งอาหาร</h5>
          <p class="mb-2">โต๊ะ: {{ tableName || tableId }}</p>
          <div class="qr-wrapper d-flex justify-content-center my-2">
            <QrcodeVue :value="qrUrl" :size="200" level="H" />
          </div>
          <p class="text-muted small">ให้ลูกค้าสแกนเพื่อดูเมนูและสั่งอาหาร</p>
          <button @click="showQrModal = false" class="btn btn-secondary w-100">ปิด</button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toastMsg" class="toast show position-fixed top-0 start-50 translate-middle-x mt-3" role="status" aria-live="polite">
      <div class="toast-body bg-dark text-white rounded-pill px-4 py-2">{{ toastMsg }}</div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router'; // ✅ เพิ่ม useRouter
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';

export default {
  components: {
    QrcodeVue
  },
  setup() {
    const route = useRoute();
    const router = useRouter(); // ✅ เรียกใช้ router
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
    const toastMsg = ref('');
    let timerInterval = null;
    let pollingInterval = null;

    const categories = [
      { name: '🥤 เครื่องดื่ม', type: 'drink' },
      { name: '🍟 อาหาร', type: 'food' },
      { name: '📦 สินค้า', type: 'retail' },
    ];

    const showToast = (msg) => {
      toastMsg.value = msg;
      setTimeout(() => { toastMsg.value = ''; }, 3000);
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
                showToast('🔔 มีออเดอร์ใหม่จากลูกค้าโต๊ะนี้!');
            }

            orderHistory.value = newOrders;
        } catch (error) {
            console.error('Fetch history error:', error);
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
            showToast(newStatus === 'pending' ? '✅ ยืนยันรายการแล้ว' : '❌ ยกเลิกรายการแล้ว');
            fetchOrderHistory(); // โหลดใหม่หลังอัปเดต
        } catch (error) {
            alert('ไม่สามารถอัปเดตสถานะได้');
        }
    };

    const confirmCancelOrder = (orderId) => {
        if (confirm('⚠️ ยืนยันการยกเลิกรายการนี้? \n(สต็อกจะถูกคืน และยอดเงินจะถูกหักออกจากบิล)')) {
            updateOrderStatus(orderId, 'cancelled');
        }
    };

    const submitOrder = async () => {
        if (cart.value.length === 0) return;
        
        if(!confirm(`ยืนยันสั่งอาหาร รวม ${totalPrice.value} บาท?`)) return;

        try {
            const token = localStorage.getItem('token');
            await axios.post('/api/orders', {
                table_id: tableId, 
                items: cart.value.map(item => ({
                    id: item.id,
                    qty: item.qty
                }))
            }, {
                headers: { Authorization: `Bearer ${token}` }
            });

            alert('✅ สั่งอาหารสำเร็จ!');
            cart.value = []; 
            fetchOrderHistory(); // <--- เพิ่มให้โหลดประวัติใหม่ทันที
            
        } catch (error) {
            console.error(error);
            alert('❌ สั่งอาหารไม่สำเร็จ: ' + (error.response?.data?.message || 'โปรดลองใหม่'));
        }
    };

    // ✅ ฟังก์ชันเช็คบิล / ปิดโต๊ะ (เพิ่มใหม่)
    const handleCheckout = async () => {
      try {
        const token = localStorage.getItem('token');
        
        // 1. ดึงข้อมูลบิลมาดู (Preview)
        const response = await axios.get(`/api/tables/${tableId}/bill`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        const data = response.data;
        
        // 2. เตรียมข้อความสรุป
        const timeLabel = data.is_day_pass ? '🎟️ ประเภท: 1 Day Pass (เหมาวัน)' : `⏱️ เวลาที่เล่น: ${data.duration_minutes} นาที`;
        const msg = `
        🧾 สรุปยอดเงิน (โต๊ะ ${tableName.value || tableId})
        --------------------------------
        👥 ลูกค้า: ${data.pax} คน
        ${timeLabel}
        
        💵 ค่าบริการ: ${data.costs.time} บาท
        🍔 ค่าอาหาร/น้ำ: ${data.costs.food} บาท
        --------------------------------
        💰 ยอดสุทธิ: ${data.costs.total} บาท
        
        ยืนยันการ "ปิดโต๊ะ" เลยหรือไม่?
        `;

        // 3. ถามยืนยัน
        if (confirm(msg)) {
            // 4. ยิง API ปิดโต๊ะ
            await axios.post(`/api/tables/${tableId}/checkout`, {}, {
                headers: { Authorization: `Bearer ${token}` }
            });

            alert('✅ ปิดโต๊ะเรียบร้อย ขอบคุณครับ!');
            router.push('/pos'); // เด้งกลับหน้าผังร้าน
        }

      } catch (error) {
        console.error(error);
        alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
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
            alert(`❌ โต๊ะนี้ยังไม่ได้เปิด (สถานะ: ${status})`);
            router.push('/pos'); 
        } else {
            // เก็บ Token ไว้ใช้ทำ QR
            if (tableData.sessions && tableData.sessions.length > 0) {
                guestToken.value = tableData.sessions[0].guest_token;
                sessionStartTime.value = tableData.sessions[0].start_time;
            }
        }

      } catch (error) {
        console.error(error);
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
      awaitingConfirmOrders, activeAndPastOrders, toastMsg // <--- เพิ่ม toastMsg
    };

    
  }
};
</script>

<style scoped>
/* Compact Bootstrap-friendly overrides */
.card-theme { background: var(--color-bg-card); border: 1px solid var(--color-border-light); }
.text-brown { color: var(--color-text-primary) !important; }
.product-name { font-size: 0.95rem; }
.product-price { font-weight: 700; }
.qr-wrapper { margin: 12px 0; }

/* Timer look */
.order-timer { font-family: 'Courier New', monospace; font-weight: 700; color: var(--color-accent-dark); }

/* Small responsive tweaks */
@media (max-width: 768px) {
  .history-list { max-height: 180px; }
}
</style>