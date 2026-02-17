<template>
  <div class="landing-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content text-center">
        <h1 class="page-title">Our Menu</h1>
        <p class="page-subtitle">Coffee • Snacks • Games</p>
      </div>
    </div>

    <div class="container">
      <div v-if="loading" class="loading-full">
        <div class="spinner-container">
          <div class="spinner-modern"></div>
          <p>กำลังตรวจสอบความถูกต้อง...</p>
        </div>
      </div>

      <div v-else-if="!sessionValid" class="error-state">
        <div class="error-icon">
          <AlertCircle :size="48" />
        </div>
        <h5>ขออภัย</h5>
        <p>{{ errorMessage }}</p>
        <p class="text-muted">หากต้องการสั่งอาหารเพิ่มเติม กรุณาติดต่อพนักงาน</p>
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
          <div v-for="product in filteredProducts" :key="product.id" class="menu-card">
            <!-- Product Image -->
            <div class="product-image">
              <img v-if="product.image_url" :src="product.image_url" alt="" />
              <div v-else class="placeholder-image">
                <Coffee v-if="product.category?.type === 'drink'" :size="32" />
                <Utensils v-else-if="product.category?.type === 'food'" :size="32" />
                <Gamepad2 v-else :size="32" />
              </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
              <h3 class="product-name">{{ product.name }}</h3>
              <p class="product-description">{{ product.description || 'Rich, bold Italian espresso shot' }}</p>
              <span class="category-badge">{{ getCategoryLabel(product.category?.type) }}</span>
            </div>

            <!-- Price Badge -->
            <div class="price-badge">
              ฿{{ product.price }}
            </div>

            <!-- Action Buttons -->
            <div class="product-actions">
              <template v-if="product.category?.type === 'retail'">
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
                  <button @click="addToCart(product)" class="btn-qty">
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
        <button class="btn-confirm" @click="submitOrder">
          <Check :size="18" class="inline-icon" /> ยืนยัน
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

    <footer class="page-footer">เพลิดเพลินกับเกมและอาหารในร้านเรานะครับ</footer>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
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
      { name: 'All', type: 'all', icon: 'Flame' },
      { name: 'Coffee', type: 'drink', icon: 'Coffee' },
      { name: 'Snacks', type: 'food', icon: 'Utensils' },
      { name: 'Games', type: 'retail', icon: 'Gamepad2' },
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
        'drink': 'Coffee',
        'food': 'Snacks',
        'retail': 'Games'
      };
      return labels[type] || type;
    };

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
      token, categories, currentTab, filteredProducts, getCategoryLabel,
      loading, sessionValid, errorMessage, tableInfo,
      cart, addToCart, decreaseQty, getItemQty, totalItems, totalPrice,
      showCartModal, submitOrder, submitting,
      detailProduct, showProductDetail
    };
  }
};
</script>

<style>
.inline-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  vertical-align: middle;
  margin-right: 6px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-divider);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 8px;
  vertical-align: middle;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Landing Page Container */
.landing-page {
  --lp-action: #2F9D7E;
  --lp-action-hover: #23856a;
  --lp-primary: #2F9D7E;
  --lp-highlight: #FFB74D;
  --lp-highlight-light: #FFF8E1;
  --lp-text-primary: #2b2b2b;
  --lp-text-secondary: #6b6b6b;
  --lp-bg: #F7F9F8;
  --lp-card: #FFFFFF;
  --lp-border: #E6E6E6;

  min-height: 100vh;
  background: var(--lp-bg);
  padding-bottom: 100px;
}

/* Header */
.page-header {
  background: var(--color-primary);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--color-primary-dark);
}

.back-button {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.25);
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-highlight-light);
  margin: 0;
  line-height: 1.2;
}

.page-subtitle {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
  margin: 2px 0 0 0;
}

/* Container */
.container {
  max-width: 540px;
  margin: 0 auto;
  padding: 16px;
}

/* Error State */
.error-state {
  text-align: center;
  padding: 40px 20px;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-state h5 {
  color: var(--color-primary);
  margin-bottom: 12px;
}

.error-state p {
  color: var(--color-text-secondary);
  margin-bottom: 8px;
}

/* Category Filters */
.category-filters {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.category-btn {
  padding: 10px 16px;
  border-radius: 20px;
  border: 2px solid var(--color-table-border);
  background: white;
  color: var(--color-text-secondary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 6px;
}

.category-btn.active {
  background: linear-gradient(135deg, var(--lp-action) 0%, var(--lp-action-hover) 100%);
  border-color: var(--lp-action);
  color: white;
}

.category-btn:hover:not(.active) {
  border-color: var(--lp-action);
  background: var(--lp-bg);
}

.category-icon {
  font-size: 16px;
}

.category-icon-comp {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Menu Items */
.menu-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.menu-card {
  background: var(--lp-card);
  border: 2px solid var(--lp-border);
  border-radius: 16px;
  padding: 16px;
  display: grid;
  grid-template-columns: 60px 1fr auto;
  grid-template-rows: auto auto;
  gap: 12px;
  position: relative;
  transition: all 0.3s ease;
}

.menu-card:hover {
  border-color: var(--lp-action);
  box-shadow: 0 6px 18px rgba(47, 157, 126, 0.12);
}

/* Product Image */
.product-image {
  grid-row: 1 / 3;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--color-bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-image {
  font-size: 28px;
  color: var(--lp-action);
  opacity: 1;
}

.placeholder-image svg {
  width: 36px;
  height: 36px;
  color: var(--lp-action);
  opacity: 1;
  display: block;
}

/* Product Info */
.product-info {
  grid-column: 2;
  grid-row: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.product-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--lp-text-primary);
  margin: 0;
  line-height: 1.3;
}

.product-description {
  font-size: 13px;
  color: var(--lp-text-secondary);
  margin: 0;
  line-height: 1.4;
}

.category-badge {
  display: inline-block;
  padding: 4px 10px;
  background: var(--lp-card);
  border: 1px solid var(--lp-border);
  border-radius: 12px;
  font-size: 11px;
  color: var(--lp-text-secondary);
  font-weight: 600;
  width: fit-content;
  margin-top: 4px;
}

/* Price Badge */
.price-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background: linear-gradient(135deg, var(--lp-highlight) 0%, #FF8C42 100%);
  color: white;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 700;
}

/* Product Actions */
.product-actions {
  grid-column: 2 / 4;
  grid-row: 2;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 8px;
}

.btn-add {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--lp-action) 0%, var(--lp-action-hover) 100%);
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-add:hover {
  background: linear-gradient(135deg, var(--lp-action-hover) 0%, #23856a 100%);
  transform: scale(1.03);
}

.btn-view-details {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--lp-card);
  border: 2px solid var(--lp-action);
  color: var(--lp-action);
  font-size: 16px;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-view-details:hover {
  background: var(--lp-action);
  color: white;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--color-bg-card);
  border-radius: 20px;
  padding: 4px 8px;
}

.btn-qty {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: white;
  border: 1px solid var(--lp-action);
  color: var(--lp-action);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-qty:hover {
  background: var(--lp-action);
  color: white;
}

.qty-display {
  min-width: 24px;
  text-align: center;
  font-weight: 700;
  color: var(--lp-text-primary);
}

/* Cart Bar */
.cart-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-top: 2px solid var(--color-table-border);
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.cart-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.cart-items {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.cart-total {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text-price);
}

.cart-actions {
  display: flex;
  gap: 8px;
}

.btn-view-cart {
  padding: 10px 16px;
  background: white;
  border: 2px solid var(--lp-action);
  color: var(--lp-action);
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-view-cart:hover {
  background: var(--lp-bg);
}

.btn-confirm {
  padding: 8px 14px;
  background: linear-gradient(135deg, var(--lp-action) 0%, var(--lp-action-hover) 100%);
  border: none;
  color: white;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.18s ease;
}

.btn-confirm:hover {
  background: linear-gradient(135deg, var(--lp-action-hover) 0%, #23856a 100%);
}

/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

/* Product Detail Modal */
.modal-content {
  background: white;
  border-radius: 16px;
  padding: 24px;
  max-width: 420px;
  width: 100%;
  position: relative;
  border: 2px solid var(--color-table-border);
}

.btn-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--lp-card);
  border: none;
  color: var(--lp-text-primary);
  cursor: pointer;
  line-height: 1;
}

.btn-close:hover {
  background: var(--lp-border);
}

.modal-image {
  width: 100%;
  border-radius: 12px;
  margin-bottom: 16px;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 8px 0;
}

.modal-price {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text-price);
  margin-bottom: 12px;
}

.modal-description {
  color: var(--color-text-secondary);
  margin-bottom: 16px;
  line-height: 1.6;
}

.alert-contact {
  background: var(--color-highlight-light);
  border: 1px solid var(--color-highlight);
  border-radius: 8px;
  padding: 12px;
  color: var(--color-text-primary);
  font-weight: 600;
  text-align: center;
}

/* Cart Modal */
.cart-modal {
  background: white;
  border-radius: 16px 16px 0 0;
  padding: 24px;
  max-width: 540px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  border: 2px solid var(--color-table-border);
  border-bottom: none;
}

.cart-modal-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 16px 0;
}

.cart-items-list {
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
  background: var(--color-table-row-alt);
  border-radius: 8px;
}

.cart-item-name {
  font-weight: 600;
  color: var(--color-text-primary);
}

.cart-item-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-qty-small {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: white;
  border: 1px solid var(--lp-action);
  color: var(--lp-action);
  font-size: 14px;
  cursor: pointer;
}

.qty-display-small {
  min-width: 20px;
  text-align: center;
  font-weight: 600;
  color: var(--lp-text-primary);
}

.cart-item-price {
  font-weight: 700;
  color: var(--color-text-price);
  min-width: 60px;
  text-align: right;
}

.cart-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 2px solid var(--color-table-border);
  margin-bottom: 16px;
}

.summary-label {
  font-weight: 700;
  color: var(--color-text-primary);
}

.summary-total {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text-price);
}

.cart-modal-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel {
  flex: 1;
  padding: 12px;
  background: var(--color-bg-card);
  border: none;
  color: var(--color-text-secondary);
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit {
  flex: 2;
  padding: 10px;
  background: linear-gradient(135deg, var(--lp-action) 0%, var(--lp-action-hover) 100%);
  border: none;
  color: white;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

/* Footer */
.page-footer {
  text-align: center;
  color: var(--color-text-secondary);
  padding: 20px;
  font-size: 14px;
}

/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

/* Product Detail Modal */
.modal-content {
  background: white;
  border-radius: 16px;
  padding: 24px;
  max-width: 420px;
  width: 100%;
  position: relative;
}

.btn-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--lp-card);
  border: none;
  color: var(--lp-text-primary);
  cursor: pointer;
  line-height: 1;
}

.btn-close:hover {
  background: var(--lp-border);
}

.modal-image {
  width: 100%;
  border-radius: 12px;
  margin-bottom: 16px;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--lp-text-primary);
  margin: 0 0 8px 0;
}

.modal-price {
  font-size: 18px;
  font-weight: 700;
  color: var(--lp-highlight);
  margin-bottom: 12px;
}

.modal-description {
  color: var(--lp-text-secondary);
  margin-bottom: 16px;
  line-height: 1.6;
}

.alert-contact {
  background: var(--lp-highlight-light);
  border: 1px solid var(--lp-highlight);
  border-radius: 8px;
  padding: 12px;
  color: var(--lp-text-primary);
  font-weight: 600;
  text-align: center;
}

/* Cart Modal */
.cart-modal {
  background: var(--lp-card);
  border-radius: 16px 16px 0 0;
  padding: 24px;
  max-width: 540px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
}

.cart-modal-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--lp-text-primary);
  margin: 0 0 16px 0;
}

.cart-items-list {
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
  background: var(--lp-card);
  border: 1px solid var(--lp-border);
  border-radius: 8px;
}

.cart-item-name {
  font-weight: 600;
  color: var(--lp-text-primary);
}

.cart-item-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-qty-small {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: white;
  border: 1px solid var(--lp-action);
  color: var(--lp-action);
  font-size: 14px;
  cursor: pointer;
}

.qty-display-small {
  min-width: 20px;
  text-align: center;
  font-weight: 600;
  color: var(--lp-text-primary);
}

.cart-item-price {
  font-weight: 700;
  color: var(--lp-text-primary);
  min-width: 60px;
  text-align: right;
}

.cart-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 2px solid var(--lp-border);
  margin-bottom: 16px;
}

.summary-label {
  font-weight: 700;
  color: var(--lp-text-primary);
}

.summary-total {
  font-size: 20px;
  font-weight: 700;
  color: var(--lp-highlight);
}

.cart-modal-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel {
  flex: 1;
  padding: 12px;
  background: var(--lp-card);
  border: none;
  color: var(--lp-text-primary);
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit {
  flex: 2;
  padding: 12px;
  background: linear-gradient(135deg, #90C695 0%, #66BB6A 100%);
  border: none;
  color: white;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* Footer */
.page-footer {
  text-align: center;
  color: #A0522D;
  padding: 20px;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 480px) {
  .container {
    padding: 12px;
  }

  .menu-card {
    padding: 12px;
  }

  .product-name {
    font-size: 15px;
  }

  .product-description {
    font-size: 12px;
  }

  .cart-actions {
    flex-direction: column;
    width: 100%;
  }

  .btn-view-cart,
  .btn-confirm {
    width: 100%;
  }
}
</style>