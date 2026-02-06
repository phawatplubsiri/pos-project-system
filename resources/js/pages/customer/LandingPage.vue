<template>
  <div class="container my-3" style="max-width:540px;">
    <header class="d-flex flex-column align-items-center mb-3">
      <div class="w-100 text-center py-3 rounded" style="background:var(--color-accent); color:var(--color-text-white);">
        <div class="h5 mb-0">☕ Board Game Cafe</div>
        <div v-if="sessionValid && tableInfo" class="badge bg-warning text-dark mt-2">โต๊ะ {{ tableInfo.number }}: พร้อมสั่งอาหาร</div>
      </div>
    </header>

    <main>
      <div v-if="loading" class="text-center py-4 text-muted">กำลังตรวจสอบความถูกต้อง...</div>

      <div v-else-if="!sessionValid" class="text-center py-4">
        <div class="display-6">🚫</div>
        <h5>ขออภัย</h5>
        <p>{{ errorMessage }}</p>
        <p class="text-muted">หากต้องการสั่งอาหารเพิ่มเติม กรุณาติดต่อพนักงาน</p>
      </div>

      <div v-else>
        <h5 class="mb-3">📜 เมนูของเรา</h5>

        <div class="d-flex gap-2 mb-3 overflow-auto">
          <button v-for="cat in categories" :key="cat.type" @click="currentTab = cat.type"
            :class="['btn btn-sm', currentTab === cat.type ? 'btn-dark' : 'btn-outline-secondary']">
            {{ cat.name }}
          </button>
        </div>

        <div class="list-group">
          <div v-for="product in filteredProducts" :key="product.id" class="list-group-item d-flex align-items-center justify-content-between">
            <div class="d-flex gap-2 align-items-center" style="min-width:0;">
              <img v-if="product.image_url" :src="product.image_url" class="rounded me-2" style="width:64px; height:64px; object-fit:cover;" />
              <div class="flex-grow-1">
                <div class="fw-semibold text-truncate">{{ product.name }}</div>
                <div class="text-muted small">{{ product.description || 'ดูรายละเอียดเพิ่มเติม' }}</div>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold text-success">{{ product.price }} ฿</div>
              <div class="mt-2">
                <template v-if="product.category?.type === 'retail'">
                  <button class="btn btn-outline-secondary btn-sm" @click="showProductDetail(product)">🔍</button>
                </template>
                <template v-else>
                  <div v-if="getItemQty(product.id) > 0" class="d-flex gap-1 align-items-center justify-content-end">
                    <button @click="decreaseQty(product.id)" class="btn btn-sm btn-light">-</button>
                    <span class="px-2">{{ getItemQty(product.id) }}</span>
                    <button @click="addToCart(product)" class="btn btn-sm btn-light">+</button>
                  </div>
                  <button v-else class="btn btn-dark btn-sm" @click="addToCart(product)">+</button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Product Detail Modal -->
    <div v-if="detailProduct" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card" style="width:90%; max-width:420px;">
        <div class="card-body">
          <button class="btn-close float-end" @click="detailProduct=null"></button>
          <img v-if="detailProduct.image_url" :src="detailProduct.image_url" class="img-fluid rounded mb-3" />
          <h5>{{ detailProduct.name }}</h5>
          <div class="text-success mb-2">ราคา: {{ detailProduct.price }} ฿</div>
          <p class="text-muted">{{ detailProduct.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
          <div v-if="detailProduct.category?.type === 'retail'" class="alert alert-warning">ติดต่อพนักงานสำหรับสินค้านี้</div>
          <div v-else>
            <button class="btn btn-success w-100" @click="addToCart(detailProduct); detailProduct=null">🛒 เพิ่มลงตะกร้า</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Bar -->
    <div v-if="cart.length > 0" class="fixed-bottom mb-3 d-flex justify-content-center">
      <div class="card w-100" style="max-width:540px;">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="small">{{ totalItems }} รายการ</div>
            <div class="fw-bold">รวม {{ totalPrice }} ฿</div>
          </div>
          <div>
            <button class="btn btn-outline-secondary me-2" @click="showCartModal=true">ดูตะกร้า</button>
            <button class="btn btn-success" @click="submitOrder">✅ ยืนยัน</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Modal -->
    <div v-if="showCartModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-end" style="background: rgba(0,0,0,0.45); z-index:1050;">
      <div class="card w-100" style="max-width:540px; border-radius:12px 12px 0 0;">
        <div class="card-body">
          <h5>🛒 รายการอาหารของคุณ</h5>
          <div class="list-group mb-3">
            <div v-for="item in cart" :key="item.id" class="list-group-item d-flex justify-content-between align-items-center">
              <div class="fw-semibold">{{ item.name }}</div>
              <div class="d-flex align-items-center gap-2">
                <button @click="decreaseQty(item.id)" class="btn btn-sm btn-light">-</button>
                <span>{{ item.qty }}</span>
                <button @click="addToCart(item)" class="btn btn-sm btn-light">+</button>
                <div class="fw-bold ms-3">{{ item.price * item.qty }} ฿</div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold">ยอดรวมทั้งหมด:</div>
            <div class="fw-bold">{{ totalPrice }} ฿</div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary flex-fill" @click="showCartModal=false">ปิด</button>
            <button class="btn btn-success flex-fill" @click="submitOrder">{{ submitting ? 'กำลังส่ง...' : '✅ ยืนยันการสั่งอาหาร' }}</button>
          </div>
        </div>
      </div>
    </div>

    <footer class="text-center text-muted mt-4">เพลิดเพลินกับเกมและอาหารในร้านเรานะครับ</footer>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const route = useRoute();
    const token = ref(route.query.token); 
    const products = ref([]);
    const loading = ref(true);
    const sessionValid = ref(false);
    const errorMessage = ref('');
    const tableInfo = ref(null);
    const currentTab = ref('drink');
    
    // Cart logic
    const cart = ref([]);
    const showCartModal = ref(false);
    const submitting = ref(false);
    const detailProduct = ref(null);

    const categories = [
      { name: '🥤 เครื่องดื่ม', type: 'drink' },
      { name: '🍟 อาหาร', type: 'food' },
      { name: '📦 บอร์ดเกม', type: 'retail' },
    ];

    const validateSession = async () => {
      if (!token.value) {
        errorMessage.value = 'ไม่พบ Token กรุณาสแกน QR Code ใหม่อีกครั้ง';
        loading.value = false;
        return;
      }

      try {
        const response = await axios.get(`/api/sessions/validate/${token.value}`);
        if (response.data.valid) {
          sessionValid.value = true;
          tableInfo.value = response.data.table;
          await fetchMenu();
        }
      } catch (error) {
        sessionValid.value = false;
        errorMessage.value = 'ลิงก์นี้หมดอายุแล้ว หรือไม่ถูกต้อง';
        console.error("Session validation failed", error);
      } finally {
        loading.value = false;
      }
    };

    const fetchMenu = async () => {
      try {
        const response = await axios.get('/api/products');
        products.value = response.data;
      } catch (error) {
        console.error("โหลดเมนูไม่ได้", error);
      }
    };

    const filteredProducts = computed(() => {
      return products.value.filter(p => p.category?.type === currentTab.value);
    });

    const addToCart = (product) => {
      const item = cart.value.find(i => i.id === product.id);
      if (item) {
        item.qty++;
      } else {
        cart.value.push({ ...product, qty: 1 });
      }
    };

    const decreaseQty = (productId) => {
      const index = cart.value.findIndex(i => i.id === productId);
      if (index > -1) {
        if (cart.value[index].qty > 1) {
          cart.value[index].qty--;
        } else {
          cart.value.splice(index, 1);
        }
      }
    };

    const getItemQty = (productId) => {
      const item = cart.value.find(i => i.id === productId);
      return item ? item.qty : 0;
    };

    const totalItems = computed(() => cart.value.reduce((sum, i) => sum + i.qty, 0));
    const totalPrice = computed(() => cart.value.reduce((sum, i) => sum + (i.price * i.qty), 0));

    const submitOrder = async () => {
      if (cart.value.length === 0) return;
      submitting.value = true;
      try {
        await axios.post('/api/guest/orders', {
          token: token.value,
          items: cart.value.map(i => ({ id: i.id, qty: i.qty }))
        });
        alert('✅ สั่งอาหารเรียบร้อย กรุณารอพนักงานยืนยันครับ');
        cart.value = [];
        showCartModal.value = false;
      } catch (error) {
        alert('❌ ผิดพลาด: ' + (error.response?.data?.message || 'โปรดลองใหม่'));
      } finally {
        submitting.value = false;
      }
    };

    const showProductDetail = (product) => {
      detailProduct.value = product;
    };

    onMounted(validateSession);

    return { 
      token, categories, currentTab, filteredProducts, 
      loading, sessionValid, errorMessage, tableInfo,
      cart, addToCart, decreaseQty, getItemQty, totalItems, totalPrice,
      showCartModal, submitOrder, submitting,
      detailProduct, showProductDetail
    };
  }
};
</script>

<style scoped>
/* Compact mobile-friendly overrides using theme variables */
.card-theme { background: var(--color-bg-card); border: 1px solid var(--color-border-light); }
.text-brown { color: var(--color-text-primary) !important; }
.menu-card, .list-group-item { border-radius: 10px; }
.badge.bg-warning { background: var(--color-warning) !important; color: var(--color-text-white); }
.btn-dark { background: var(--color-accent); color: var(--color-text-white); border: none; }

/* Cart bar spacing */
.fixed-bottom .card { box-shadow: 0 6px 20px rgba(0,0,0,0.12); }

@media (max-width: 480px) {
  .container { padding-left:10px; padding-right:10px; }
}

/* Small helpers */
.menu-card img { width:64px; height:64px; object-fit:cover; }
</style>