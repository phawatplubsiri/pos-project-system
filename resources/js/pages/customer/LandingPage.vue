<template>
  <div class="landing-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content text-center">
        <h1 class="page-title">รายการเมนู</h1>
        <p class="page-subtitle">เครื่องดื่ม • ของทานเล่น • บอร์ดเกม</p>
      </div>
    </div>

    <div class="container">
      <div v-if="loading" class="loading-full">
        <div class="spinner-container">
          <div class="spinner-modern"></div>
          <p>กำลังตรวจสอบความถูกต้อง...</p>
        </div>
      </div>

      <div v-else-if="!sessionValid" class="error-container">
        <div class="error-card">
          <div class="error-icon-wrapper">
            <AlertCircle :size="56" />
          </div>
          <h2 class="error-title">ขออภัย</h2>
          <p class="error-message">{{ errorMessage }}</p>
          <p class="error-instruction">หากคุณต้องการสั่งอาหารหรือเปิดโต๊ะใหม่<br>กรุณาติดต่อพนักงานที่เคาน์เตอร์</p>
        </div>
      </div>

      <div v-else>
        <!-- Category Filters -->
        <div class="category-filters">
          <button 
            v-for="cat in categories" 
            :key="cat.type" 
            @click="currentTab = cat.type"
            :class="['category-btn', { 'active': currentTab === cat.type }]"
          >
            <component :is="cat.icon" :size="16" class="category-icon-comp" />
            {{ cat.name }}
          </button>
        </div>

        <!-- Menu Items -->
        <div class="menu-items">
          <div v-for="product in filteredProducts" :key="product.id" 
            :class="['menu-card', { 'out-of-stock': product.stock_qty <= 0 }]">
            <!-- Product Image -->
            <div class="product-image">
              <img v-if="product.image_url" :src="product.image_url" alt="" />
              <div v-else class="placeholder-image">
                <Coffee v-if="product.category?.type === 'drink'" :size="32" />
                <Utensils v-else-if="product.category?.type === 'food'" :size="32" />
                <Gamepad2 v-else :size="32" />
              </div>
              <div v-if="product.stock_qty <= 0" class="sold-out-overlay">
                <span>หมด</span>
              </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
              <h3 class="product-name">{{ product.name }}</h3>
              <p class="product-description">{{ product.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
              <span class="category-badge">{{ getCategoryLabel(product.category?.type) }}</span>
            </div>

            <!-- Price Badge -->
            <div class="price-badge">
              ฿{{ product.price }}
            </div>

            <!-- Action Buttons -->
            <div class="product-actions">
              <template v-if="product.stock_qty <= 0">
                <span class="sold-out-text">สินค้าหมดชั่วคราว</span>
              </template>
              <template v-else-if="product.category?.type === 'retail'">
                <button class="btn-view-details" @click="showProductDetail(product)">
                  <Search :size="18" />
                </button>
              </template>
              <template v-else>
                <div v-if="getItemQty(product.id) > 0" class="quantity-controls">
                  <button @click="decreaseQty(product.id)" class="btn-qty">
                    <Minus :size="16" />
                  </button>
                  <span class="qty-display">{{ getItemQty(product.id) }}</span>
                  <button @click="addToCart(product)" class="btn-qty" :disabled="getItemQty(product.id) >= product.stock_qty">
                    <Plus :size="16" />
                  </button>
                </div>
                <button v-else class="btn-add" @click="addToCart(product)">
                  <Plus :size="20" />
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Detail Modal -->
    <div v-if="detailProduct" class="modal-overlay" @click.self="detailProduct = null">
      <div class="modal-content">
        <button class="btn-close" @click="detailProduct = null">
          <X :size="20" />
        </button>
        <img v-if="detailProduct.image_url" :src="detailProduct.image_url" class="modal-image" />
        <h3 class="modal-title">{{ detailProduct.name }}</h3>
        <div class="modal-price">ราคา: ฿{{ detailProduct.price }}</div>
        <p class="modal-description">{{ detailProduct.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
        <div class="alert-contact">
          <Info :size="18" class="inline-icon" /> กรุณาติดต่อพนักงานที่เคาท์เตอร์สำหรับสินค้านี้
        </div>
      </div>
    </div>

    <!-- Cart Bar -->
    <div v-if="cart.length > 0" class="cart-bar">
      <div class="cart-info">
        <div class="cart-items">
          <ShoppingCart :size="14" class="inline-icon" /> {{ totalItems }} รายการ
        </div>
        <div class="cart-total">รวม ฿{{ totalPrice }}</div>
      </div>
      <div class="cart-actions">
        <button class="btn-view-cart" @click="showCartModal = true">ดูตะกร้า</button>
        <button class="btn-confirm" @click="submitOrder" :disabled="submitting">
          <template v-if="submitting">
            <span class="spinner-mini"></span> กำลังส่ง...
          </template>
          <template v-else>
            <Check :size="18" class="inline-icon" /> ยืนยัน
          </template>
        </button>
      </div>
    </div>

    <!-- Cart Modal -->
    <div v-if="showCartModal" class="modal-overlay" @click.self="showCartModal = false">
      <div class="cart-modal">
        <h3 class="cart-modal-title">
          <ShoppingCart :size="22" class="inline-icon" /> รายการอาหารของคุณ
        </h3>
        <div class="cart-items-list">
          <div v-for="item in cart" :key="item.id" class="cart-item">
            <div class="cart-item-name">{{ item.name }}</div>
            <div class="cart-item-controls">
              <button @click="decreaseQty(item.id)" class="btn-qty-small">
                <Minus :size="14" />
              </button>
              <span class="qty-display-small">{{ item.qty }}</span>
              <button @click="addToCart(item)" class="btn-qty-small">
                <Plus :size="14" />
              </button>
              <div class="cart-item-price">฿{{ item.price * item.qty }}</div>
            </div>
          </div>
        </div>
        <div class="cart-summary">
          <div class="summary-label">ยอดรวมทั้งหมด:</div>
          <div class="summary-total">฿{{ totalPrice }}</div>
        </div>
        <div class="cart-modal-actions">
          <button class="btn-cancel" @click="showCartModal = false">ปิด</button>
          <button class="btn-submit" @click="submitOrder">
            <Check v-if="!submitting" :size="18" class="inline-icon" />
            {{ submitting ? 'กำลังส่ง...' : 'ยืนยันการสั่งอาหาร' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { 
  ArrowLeft, 
  Coffee, 
  Utensils, 
  Gamepad2, 
  Flame, 
  Search, 
  Plus, 
  Minus, 
  ShoppingCart, 
  Check, 
  X, 
  Info,
  AlertCircle
} from 'lucide-vue-next';

export default {
  components: {
    ArrowLeft,
    Coffee,
    Utensils,
    Gamepad2,
    Flame,
    Search,
    Plus,
    Minus,
    ShoppingCart,
    Check,
    X,
    Info,
    AlertCircle
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const alert = useAlert();
    const token = ref(route.query.token); 
    const products = ref([]);
    const loading = ref(true);
    const sessionValid = ref(false);
    const errorMessage = ref('');
    const tableInfo = ref(null);
    const currentTab = ref('all');
    
    const cart = ref([]);
    const showCartModal = ref(false);
    const submitting = ref(false);
    const detailProduct = ref(null);

    const categories = [
      { name: 'ทั้งหมด', type: 'all', icon: 'Flame' },
      { name: 'เครื่องดื่ม', type: 'drink', icon: 'Coffee' },
      { name: 'อาหาร & ของทานเล่น', type: 'food', icon: 'Utensils' },
      { name: 'บอร์ดเกม', type: 'retail', icon: 'Gamepad2' },
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
      // กรองเอาสินค้าที่เป็นประเภท service (เช่น ค่าชั่วโมง) ออกไป ไม่ให้ลูกค้ากดสั่งเอง
      const visibleProducts = products.value.filter(p => p.category?.type !== 'service');
      
      if (currentTab.value === 'all') {
        return visibleProducts;
      }
      return visibleProducts.filter(p => p.category?.type === currentTab.value);
    });

    const getCategoryLabel = (type) => {
      const labels = {
        'drink': 'เครื่องดื่ม',
        'food': 'อาหาร & ของทานเล่น',
        'retail': 'บอร์ดเกม'
      };
      return labels[type] || type;
    };

    const addToCart = (product) => {
      const item = cart.value.find(i => i.id === product.id);
      if (item) {
        if (item.qty < product.stock_qty) {
          item.qty++;
        } else {
          alert.error('ขออภัย', 'สินค้า ' + product.name + ' มีจำนวนจำกัด');
        }
      } else {
        if (product.stock_qty > 0) {
          cart.value.push({ ...product, qty: 1 });
        } else {
          alert.error('ขออภัย', 'สินค้า ' + product.name + ' หมดชั่วคราว');
        }
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
        alert.success('สั่งอาหารเรียบร้อย');
        cart.value = [];
        showCartModal.value = false;
      } catch (error) {
        alert.error('ผิดพลาด', error.response?.data?.message || 'โปรดลองใหม่');
      } finally {
        submitting.value = false;
      }
    };

    const showProductDetail = (product) => {
      detailProduct.value = product;
    };

    onMounted(validateSession);

    return { 
      token, categories, currentTab, filteredProducts, getCategoryLabel,
      loading, sessionValid, errorMessage, tableInfo,
      cart, addToCart, decreaseQty, getItemQty, totalItems, totalPrice,
      showCartModal, submitOrder, submitting,
      detailProduct, showProductDetail
    };
  }
};
</script>

<style scoped>
.inline-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  vertical-align: middle;
  margin-right: 6px;
}

/* Loading & Error States */
.loading-full {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  color: var(--lp-text-secondary);
  text-align: center;
}

.spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.spinner-modern {
  width: 44px;
  height: 44px;
  border: 4px solid #eee;
  border-top-color: var(--lp-action);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.spinner-mini {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  margin-right: 6px;
  vertical-align: middle;
}

/* Error State Modernized */
.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 70vh;
  padding: 24px;
}

.error-card {
  background: rgb(255, 255, 255);
  padding: 32px 32px;
  border-radius: 32px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.06);
  text-align: center;
  max-width: 400px;
  width: 100%;
  border: 1px solid var(--lp-border);
}

.error-icon-wrapper {
  color: #ff5252;
  margin: 24px 0;
  display: flex;
  justify-content: center;
}

.error-title {
  font-size: 28px;
  font-weight: 800;
  color: #000000;
  margin: 0 0 12px 0;
}

.error-message {
  font-size: 16px;
  color: #000000;
  font-weight: 500;
  margin-bottom: 20px;
  line-height: 1.5;
}

.error-instruction {
  font-size: 14px;
  font-weight: bold;
  color: #444444;
  margin-bottom: 32px;
  line-height: 1.6;
}

.error-state {
  text-align: center;
  padding: 60px 20px;
}

.error-icon {
  color: #ff5252;
  margin-bottom: 20px;
}

/* Landing Page Container */
.landing-page {
  --lp-action: #298468;
  --lp-action-hover: #1e634e;
  --lp-primary: #256b56;
  --lp-highlight: #d19a4d;
  --lp-highlight-light: #fdf5e6;
  --lp-text-primary: #333333;
  --lp-text-secondary: #5a5a5a;
  --lp-bg: #eceee9;
  --lp-card: #f9faf8;
  --lp-border: #d8dbd5;

  min-height: 100vh;
  background: var(--lp-bg);
  padding-bottom: 100px;
}

/* Header */
.page-header {
  background: var(--lp-primary);
  padding: 24px 20px;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: white;
  margin: 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
  margin: 4px 0 0 0;
  font-weight: 500;
}

/* Container */
.container {
  max-width: 610px;
  margin: 0 auto;
  padding: 16px;
}

/* Category Filters */
.category-filters {
  display: flex;
  gap: 10px;
  margin-bottom: 24px;
  overflow-x: auto;
  padding: 4px 2px 10px 2px;
  scrollbar-width: none;
}

.category-filters::-webkit-scrollbar {
  display: none;
}

.category-btn {
  padding: 10px 18px;
  border-radius: 25px;
  border: 1px solid var(--lp-border);
  background: white;
  color: var(--lp-text-secondary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}

.category-btn.active {
  background: var(--lp-action);
  border-color: var(--lp-action);
  color: white;
  box-shadow: 0 4px 12px rgba(47, 157, 126, 0.25);
}

/* Menu Items */
.menu-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.menu-card {
  background: var(--lp-card);
  border: 1px solid var(--lp-border);
  border-radius: 20px;
  padding: 16px;
  display: grid;
  grid-template-columns: 80px 1fr;
  gap: 16px;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.menu-card.out-of-stock {
  opacity: 0.7;
  border-color: #ddd;
  background: #fdfdfd;
}

.menu-card.out-of-stock:hover {
  transform: none;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border-color: #ddd;
}

.menu-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  border-color: var(--lp-action);
}

/* Product Image */
.product-image {
  width: 80px;
  height: 80px;
  border-radius: 16px;
  overflow: hidden;
  background: #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.sold-out-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 800;
  font-size: 14px;
}

.sold-out-text {
  color: #ff5252;
  font-size: 13px;
  font-weight: 700;
}

.btn-qty:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-image {
  color: var(--lp-action);
}

/* Product Info */
.product-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.product-name {
  font-size: 17px;
  font-weight: 700;
  color: var(--lp-text-primary);
  margin: 0 0 4px 0;
  line-height: 1.3;
}

.product-description {
  font-size: 13px;
  color: var(--lp-text-secondary);
  margin: 0 0 8px 0;
  line-height: 1.4;
}

.category-badge {
  display: inline-block;
  padding: 2px 10px;
  background: #f3f3f3;
  border-radius: 6px;
  font-size: 11px;
  color: var(--lp-text-secondary);
  font-weight: 600;
  width: fit-content;
}

/* Price Badge */
.price-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  font-size: 16px;
  font-weight: 800;
  color: var(--lp-action);
}

/* Product Actions */
.product-actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 8px;
  min-height: 40px;
}

.btn-add {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: var(--lp-action);
  border: none;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-add:active {
  transform: scale(0.9);
}

.btn-view-details {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: white;
  border: 2px solid var(--lp-action);
  color: var(--lp-action);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-view-details:hover {
  background: var(--lp-action);
  color: white;
  transform: scale(1.05);
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f5f5f5;
  border-radius: 12px;
  padding: 4px;
}

.btn-qty {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: white;
  border: none;
  color: var(--lp-action);
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.qty-display {
  font-weight: 700;
  min-width: 20px;
  text-align: center;
  color: #000;
}

.qty-display-small {
  min-width: 20px;
  text-align: center;
  font-weight: 600;
  color: #000;
}

/* Cart Bar */
.cart-bar {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 32px);
  max-width: 500px;
  background: white;
  border-radius: 20px;
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  border: 1px solid var(--lp-border);
  z-index: 100;
  color: var(--lp-text-primary);
}

.cart-info {
  display: flex;
  flex-direction: column;
}

.cart-items {
  font-size: 12px;
  opacity: 0.85;
  color: var(--lp-text-secondary);
}

.cart-total {
  font-size: 18px;
  font-weight: 700;
  color: var(--lp-text-primary);
}

.cart-actions {
  display: flex;
  gap: 10px;
}

.btn-view-cart {
  background: transparent;
  border: 1.5px solid var(--lp-action);
  color: var(--lp-action);
  padding: 8px 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-view-cart:hover {
  background: rgba(47, 157, 126, 0.05);
}

.btn-confirm {
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  border: none;
  color: white;
  padding: 8px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(47, 157, 126, 0.25);
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 24px;
  padding: 24px;
  max-width: 420px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
}

.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f0f0f0;
  border: none;
  color: var(--lp-text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #e0e0e0;
}

.modal-image {
  width: 100%;
  height: 240px;
  object-fit: cover;
  border-radius: 16px;
  margin-bottom: 20px;
}

.modal-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--lp-text-primary);
  margin: 0 0 12px 0;
  line-height: 1.3;
}

.modal-price {
  font-size: 18px;
  font-weight: 700;
  color: var(--lp-action);
  margin: 0 0 16px 0;
}

.modal-description {
  font-size: 14px;
  color: var(--lp-text-secondary);
  margin: 0 0 16px 0;
  line-height: 1.6;
}

.alert-contact {
  background: var(--lp-highlight-light);
  border: 2px solid var(--lp-highlight);
  border-radius: 12px;
  padding: 14px;
  color: var(--lp-text-primary);
  font-weight: 600;
  text-align: center;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Cart Modal Specific */
.cart-modal {
  background: white;
  border-radius: 24px 24px 0 0;
  padding: 24px 20px;
  max-width: 540px;
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
}

.cart-modal-title {
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #000;
}

.cart-items-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
  flex: 1;
  overflow-y: auto;
}

.cart-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 14px;
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.cart-item:hover {
  background: #efefef;
  border-color: var(--lp-action);
}

.cart-item-name {
  font-weight: 600;
  font-size: 15px;
  color: #000;
  flex: 1;
}

.cart-item-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cart-item-price {
  font-weight: 700;
  color: #000;
  min-width: 60px;
  text-align: right;
}

.btn-qty-small {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: none;
  background: white;
  color: var(--lp-action);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-summary {
  margin-top: auto;
  padding: 16px 14px;
  background: #f5f5f5;
  border: 2px solid #ddd;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.summary-total {
  font-size: 24px;
  font-weight: 800;
  color: #000;
}

.summary-label {
  font-weight: 700;
  color: #000;
}

.cart-modal-actions {
  display: flex;
  gap: 12px;
  padding-top: 8px;
}

.btn-cancel {
  flex: 1;
  background: transparent;
  border: 1.5px solid var(--lp-action);
  padding: 12px 14px;
  border-radius: 12px;
  font-weight: 700;
  color: var(--lp-action);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.btn-cancel:hover {
  background: rgba(47, 157, 126, 0.05);
}

.btn-submit {
  flex: 2;
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  color: white;
  border: none;
  padding: 12px 14px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(47, 157, 126, 0.25);
  transition: all 0.2s ease;
  font-size: 14px;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(47, 157, 126, 0.35);
}

/* Responsive Fixes */
@media (max-width: 480px) {
  .menu-card {
    grid-template-columns: 70px 1fr;
    gap: 12px;
    padding: 12px;
    border-radius: 16px;
  }

  .product-image {
    width: 70px;
    height: 70px;
    border-radius: 12px;
  }

  .product-name {
    font-size: 15px;
    padding-right: 40px;
  }

  .product-description {
    font-size: 12px;
    -webkit-line-clamp: 2;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .price-badge {
    top: 12px;
    right: 12px;
    font-size: 14px;
  }
}

@media (max-width: 380px) {
  .menu-card {
    grid-template-columns: 60px 1fr;
  }

  .product-image {
    width: 60px;
    height: 60px;
  }

  .product-name {
    font-size: 14px;
  }

  .price-badge {
    position: relative;
    top: auto;
    right: auto;
    margin-top: 4px;
    display: block;
    font-size: 15px;
  }
  
  .product-actions {
    margin-top: 8px;
  }
}
</style>