<template>
  <div class="landing-page">
    <!-- Header -->
    <div class="page-header">
      <div class="text-center">
        <h1 class="text-2xl font-extrabold text-white m-0 mb-1.5 tracking-tight">รายการเมนู</h1>
        <p class="text-sm text-white/80 m-0 font-medium">เครื่องดื่ม • ของทานเล่น • บอร์ดเกม</p>
      </div>
    </div>

    <div class="max-w-[540px] mx-auto px-4 pb-[120px]">
      <div v-if="loading" class="flex flex-col justify-center items-center min-h-[60vh] text-[var(--lp-text-secondary)] text-center">
        <div class="flex flex-col items-center justify-center">
          <div class="spinner-modern"></div>
          <p>กำลังตรวจสอบความถูกต้อง...</p>
        </div>
      </div>

      <div v-else-if="!sessionValid" class="flex justify-center items-center min-h-[60vh]">
        <div class="error-card">
          <div class="w-20 h-20 bg-[#FFEBEE] rounded-full flex items-center justify-center mx-auto mb-5 text-[#E53935]">
            <AlertCircle :size="56" />
          </div>
          <h2 class="text-xl font-extrabold text-[var(--lp-text-primary)] m-0 mb-3 text-center">ขออภัย</h2>
          <p class="text-[15px] text-[var(--lp-text-secondary)] m-0 mb-4 text-center leading-relaxed">{{ errorMessage }}</p>
          <p class="text-sm text-[var(--lp-text-secondary)] m-0 text-center leading-relaxed">หากคุณต้องการสั่งอาหารหรือเปิดโต๊ะใหม่<br>กรุณาติดต่อพนักงานที่เคาน์เตอร์</p>
        </div>
      </div>

      <div v-else>
        <!-- Category Filters -->
        <div class="flex gap-2 overflow-x-auto py-5 px-1 scrollbar-none">
          <button 
            v-for="cat in categories" 
            :key="cat.type" 
            @click="currentTab = cat.type"
            :class="['category-btn', { 'active': currentTab === cat.type }]"
          >
            <component :is="cat.icon" :size="16" class="inline-icon" />
            {{ cat.name }}
          </button>
        </div>

        <!-- Menu Items -->
        <div class="flex flex-col gap-3.5 mt-2">
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
            <div class="flex-1 min-w-0 flex flex-col gap-1">
              <h3 class="text-base font-bold text-[var(--lp-text-primary)] m-0 leading-snug pr-16">{{ product.name }}</h3>
              <p class="text-xs text-[var(--lp-text-secondary)] m-0 leading-relaxed line-clamp-3">{{ product.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
              <span class="category-badge">{{ getCategoryLabel(product.category?.type) }}</span>
            </div>

            <!-- Price Badge -->
            <div class="price-badge">
              ฿{{ product.price }}
            </div>

            <!-- Action Buttons -->
            <div class="product-actions">
              <template v-if="product.stock_qty <= 0">
                <span class="text-xs text-[#999] font-semibold">สินค้าหมดชั่วคราว</span>
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
                  <span class="min-w-[28px] text-center text-[15px] font-bold text-[var(--lp-action)]">{{ getItemQty(product.id) }}</span>
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
      <div class="bg-white rounded-3xl p-6 max-w-[420px] w-full relative shadow-[0_20px_50px_rgba(0,0,0,0.2)]">
        <button class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full bg-[#f0f0f0] border-none text-[var(--lp-text-primary)] cursor-pointer transition-all duration-200 hover:bg-[#e0e0e0]" @click="detailProduct = null">
          <X :size="20" />
        </button>
        <img v-if="detailProduct.image_url" :src="detailProduct.image_url" class="w-full h-60 object-cover rounded-2xl mb-5" />
        <h3 class="text-[22px] font-extrabold text-[var(--lp-text-primary)] m-0 mb-3 leading-snug">{{ detailProduct.name }}</h3>
        <div class="text-lg font-bold text-[var(--lp-action)] m-0 mb-4">ราคา: ฿{{ detailProduct.price }}</div>
        <p class="text-sm text-[var(--lp-text-secondary)] m-0 mb-4 leading-relaxed">{{ detailProduct.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
        <div class="bg-[var(--lp-highlight-light)] border-2 border-[var(--lp-highlight)] rounded-xl p-3.5 text-[var(--lp-text-primary)] font-semibold text-center text-sm flex items-center justify-center">
          <Info :size="18" class="inline-icon" /> กรุณาติดต่อพนักงานที่เคาท์เตอร์สำหรับสินค้านี้
        </div>
      </div>
    </div>

    <!-- Cart Bar -->
    <div v-if="cart.length > 0" class="cart-bar">
      <div class="flex items-center gap-4 flex-1">
        <div class="text-sm opacity-85 text-[var(--lp-text-secondary)]">
          <ShoppingCart :size="14" class="inline-icon" /> {{ totalItems }} รายการ
        </div>
        <div class="text-lg font-bold text-[var(--lp-text-primary)]">รวม ฿{{ totalPrice }}</div>
      </div>
      <div class="flex gap-2.5">
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
        <h3 class="text-xl font-extrabold m-0 mb-5 flex items-center gap-2.5 text-black">
          <ShoppingCart :size="22" class="inline-icon" /> รายการอาหารของคุณ
        </h3>
        <div class="flex flex-col gap-2.5 mb-5 flex-1 overflow-y-auto">
          <div v-for="item in cart" :key="item.id" class="flex justify-between items-center p-3 px-3.5 bg-[#f5f5f5] border border-[#ddd] rounded-xl transition-all duration-200 hover:bg-[#efefef] hover:border-[var(--lp-action)]">
            <div class="font-semibold text-[15px] text-black flex-1">{{ item.name }}</div>
            <div class="flex items-center gap-2.5">
              <button @click="decreaseQty(item.id)" class="btn-qty-small">
                <Minus :size="14" />
              </button>
              <span class="min-w-[20px] text-center font-bold text-sm">{{ item.qty }}</span>
              <button @click="addToCart(item)" class="btn-qty-small">
                <Plus :size="14" />
              </button>
              <div class="font-bold text-black min-w-[60px] text-right">฿{{ item.price * item.qty }}</div>
            </div>
          </div>
        </div>
        <div class="mt-auto p-4 px-3.5 bg-[#f5f5f5] border-2 border-[#ddd] rounded-xl flex justify-between items-center mb-5">
          <div class="font-bold text-black">ยอดรวมทั้งหมด:</div>
          <div class="text-2xl font-extrabold text-black">฿{{ totalPrice }}</div>
        </div>
        <div class="flex gap-3 pt-2">
          <button class="btn-cancel" @click="showCartModal = false">ปิด</button>
          <button class="btn-submit" @click="submitOrder">
            <Check v-if="!submitting" :size="18" class="inline-icon" />
            {{ submitting ? 'กำลังส่ง...' : 'ยืนยันการสั่งอาหาร' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Floating Call Staff Button -->
    <button 
      v-if="sessionValid"
      @click="callStaff" 
      :disabled="callingStaff" 
      class="fixed bottom-6 right-6 z-[110] w-14 h-14 bg-[var(--lp-highlight)] text-white border-none rounded-full shadow-[0_4px_15px_rgba(0,0,0,0.3)] flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 disabled:opacity-50 disabled:grayscale"
    >
      <BellRing :size="28" :class="{ 'animate-bounce': callingStaff }" />
    </button>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { 
  ArrowLeft, Coffee, Utensils, Gamepad2, Flame, Search, 
  Plus, Minus, ShoppingCart, Check, X, Info, AlertCircle, BellRing
} from 'lucide-vue-next';

export default {
  components: {
    ArrowLeft, Coffee, Utensils, Gamepad2, Flame, Search,
    Plus, Minus, ShoppingCart, Check, X, Info, AlertCircle, BellRing
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
    const callingStaff = ref(false);

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
      } finally { loading.value = false; }
    };

    const fetchMenu = async () => {
      try {
        const response = await axios.get('/api/products');
        products.value = response.data;
      } catch (error) { console.error("โหลดเมนูไม่ได้", error); }
    };

    const filteredProducts = computed(() => {
      const visibleProducts = products.value.filter(p => p.category?.type !== 'service');
      if (currentTab.value === 'all') return visibleProducts;
      return visibleProducts.filter(p => p.category?.type === currentTab.value);
    });

    const getCategoryLabel = (type) => {
      const labels = { 'drink': 'เครื่องดื่ม', 'food': 'อาหาร & ของทานเล่น', 'retail': 'บอร์ดเกม' };
      return labels[type] || type;
    };

    const addToCart = (product) => {
      const item = cart.value.find(i => i.id === product.id);
      if (item) {
        if (item.qty < product.stock_qty) item.qty++;
        else alert.error('ขออภัย', 'สินค้า ' + product.name + ' มีจำนวนจำกัด');
      } else {
        if (product.stock_qty > 0) cart.value.push({ ...product, qty: 1 });
        else alert.error('ขออภัย', 'สินค้า ' + product.name + ' หมดชั่วคราว');
      }
    };

    const decreaseQty = (productId) => {
      const index = cart.value.findIndex(i => i.id === productId);
      if (index > -1) {
        if (cart.value[index].qty > 1) cart.value[index].qty--;
        else cart.value.splice(index, 1);
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
      } finally { submitting.value = false; }
    };

    const showProductDetail = (product) => { detailProduct.value = product; };

    const callStaff = async () => {
      if (!token.value || callingStaff.value) return;
      
      const proceed = await alert.confirm('เรียกพนักงาน?', 'คุณต้องการเรียกพนักงานมาที่โต๊ะใช่หรือไม่?');
      if (!proceed) return;

      callingStaff.value = true;
      try {
        const response = await axios.post('/api/guest/call-staff', { token: token.value });
        alert.success(response.data.message);
      } catch (error) {
        alert.error('ผิดพลาด', error.response?.data?.message || 'ไม่สามารถเรียกพนักงานได้');
      } finally {
        callingStaff.value = false;
      }
    };

    onMounted(validateSession);

    return { 
      token, categories, currentTab, filteredProducts, getCategoryLabel,
      loading, sessionValid, errorMessage, tableInfo,
      cart, addToCart, decreaseQty, getItemQty, totalItems, totalPrice,
      showCartModal, submitOrder, submitting,
      detailProduct, showProductDetail,
      callingStaff, callStaff
    };
  }
};
</script>

<style scoped>
/* Landing Page Custom Variables */
.landing-page {
  --lp-bg: #FFFFFF;
  --lp-action: #2f9d7e;
  --lp-text-primary: #1A1A1A;
  --lp-text-secondary: #777;
  --lp-highlight: #F5A623;
  --lp-highlight-light: #FFF8E1;
  min-height: 100vh;
  background: var(--lp-bg);
}

.inline-icon {
  display: inline-flex; align-items: center; justify-content: center;
  vertical-align: middle; margin-right: 6px;
}

/* Header */
.page-header {
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  padding: 28px 20px;
  border-radius: 0 0 28px 28px;
  margin-bottom: 4px;
}

/* Error Card */
.error-card {
  background: white;
  border-radius: 24px;
  padding: 40px 28px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  max-width: 380px;
  width: 100%;
}

/* Category Button */
.category-btn {
  padding: 10px 18px; border-radius: 28px;
  border: 1.5px solid #e0e0e0;
  background: white; color: var(--lp-text-primary);
  font-weight: 600; font-size: 14px;
  cursor: pointer; white-space: nowrap;
  transition: all 0.2s ease;
  display: flex; align-items: center; gap: 6px;
}
.category-btn.active {
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  color: white; border-color: var(--lp-action);
  box-shadow: 0 4px 12px rgba(47,157,126,0.25);
}

/* Menu Card */
.menu-card {
  display: grid;
  grid-template-columns: 80px 1fr;
  gap: 14px; padding: 14px;
  background: white; border: 1px solid #eee;
  border-radius: 18px; position: relative;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.menu-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
.menu-card.out-of-stock { opacity: 0.55; filter: grayscale(0.3); }

/* Product Image */
.product-image {
  width: 80px; height: 80px;
  border-radius: 14px; overflow: hidden;
  background: #f5f5f5; position: relative;
}
.product-image img { width: 100%; height: 100%; object-fit: cover; }
.placeholder-image {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  color: #ccc; background: linear-gradient(135deg, #f8f8f8 0%, #eee 100%);
}
.sold-out-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,0.55); display: flex;
  align-items: center; justify-content: center;
  border-radius: 14px;
}
.sold-out-overlay span {
  color: white; font-weight: 700; font-size: 13px;
  background: rgba(220,53,69,0.85); padding: 3px 10px; border-radius: 6px;
}

/* Category Badge */
.category-badge {
  display: inline-block; width: fit-content;
  padding: 2px 8px; border-radius: 6px;
  font-size: 11px; font-weight: 600;
  background: var(--lp-highlight-light); color: var(--lp-highlight);
  margin-top: 2px;
}

/* Price Badge */
.price-badge {
  position: absolute; top: 14px; right: 14px;
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  color: white; padding: 4px 12px; border-radius: 20px;
  font-weight: 700; font-size: 15px;
  box-shadow: 0 2px 6px rgba(47,157,126,0.2);
}

/* Product Actions */
.product-actions {
  grid-column: 1 / -1;
  display: flex; justify-content: flex-end; align-items: center;
  margin-top: -4px;
}
.btn-add {
  width: 40px; height: 40px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  color: white; cursor: pointer; display: flex;
  align-items: center; justify-content: center;
  box-shadow: 0 3px 8px rgba(47,157,126,0.25);
  transition: all 0.2s ease;
}
.btn-add:hover { transform: scale(1.1); }
.btn-view-details {
  width: 40px; height: 40px; border-radius: 14px; border: none;
  background: var(--lp-highlight-light); color: var(--lp-highlight);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s ease;
}
.btn-view-details:hover { background: var(--lp-highlight); color: white; }
.quantity-controls {
  display: flex; align-items: center; gap: 6px;
  background: #f0faf5; padding: 4px; border-radius: 14px;
  border: 1.5px solid var(--lp-action);
}
.btn-qty {
  width: 32px; height: 32px; border-radius: 10px; border: none;
  background: white; color: var(--lp-action); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.2s ease;
}
.btn-qty:hover { background: var(--lp-action); color: white; }

/* Cart Bar */
.cart-bar {
  position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
  width: 100%; max-width: 540px;
  background: white; border-top: 1px solid #eee;
  padding: 14px 20px; display: flex;
  justify-content: space-between; align-items: center;
  box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
  z-index: 100; border-radius: 20px 20px 0 0;
}
.btn-view-cart {
  background: transparent; border: 1.5px solid var(--lp-action); color: var(--lp-action);
  padding: 8px 14px; border-radius: 12px; font-size: 14px; font-weight: 600;
  cursor: pointer; transition: all 0.2s ease;
}
.btn-view-cart:hover { background: rgba(47,157,126,0.05); }
.btn-confirm {
  background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  border: none; color: white; padding: 8px 18px; border-radius: 12px;
  font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;
}
.btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(47,157,126,0.25); }

/* Modals */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  display: flex; justify-content: center; align-items: center;
  z-index: 1000; padding: 20px;
}
.cart-modal {
  background: white; border-radius: 24px 24px 0 0;
  padding: 24px 20px; max-width: 540px; width: 100%;
  max-height: 85vh; overflow-y: auto;
  position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
  display: flex; flex-direction: column;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
}

/* Cart Modal Buttons */
.btn-qty-small {
  width: 28px; height: 28px; border-radius: 8px; border: none;
  background: white; color: var(--lp-action);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.btn-cancel {
  flex: 1; background: transparent; border: 1.5px solid var(--lp-action);
  padding: 12px 14px; border-radius: 12px; font-weight: 700;
  color: var(--lp-action); cursor: pointer; transition: all 0.2s ease; font-size: 14px;
}
.btn-cancel:hover { background: rgba(47,157,126,0.05); }
.btn-submit {
  flex: 2; background: linear-gradient(135deg, var(--lp-action) 0%, #23856a 100%);
  color: white; border: none; padding: 12px 14px; border-radius: 12px;
  font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(47,157,126,0.25);
  transition: all 0.2s ease; font-size: 14px;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(47,157,126,0.35); }

/* Spinner */
.spinner-modern {
  width: 44px; height: 44px; border: 4px solid #eee;
  border-top-color: var(--lp-action); border-radius: 50%;
  animation: spin 1s linear infinite; margin-bottom: 16px;
}
.spinner-mini {
  display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite;
  vertical-align: middle; margin-right: 6px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 480px) {
  .menu-card { grid-template-columns: 70px 1fr; gap: 12px; padding: 12px; border-radius: 16px; }
  .product-image { width: 70px; height: 70px; border-radius: 12px; }
  .price-badge { top: 12px; right: 12px; font-size: 14px; }
}
@media (max-width: 380px) {
  .menu-card { grid-template-columns: 60px 1fr; }
  .product-image { width: 60px; height: 60px; }
  .price-badge { position: relative; top: auto; right: auto; margin-top: 4px; display: block; font-size: 15px; }
  .product-actions { margin-top: 8px; }
}
</style>