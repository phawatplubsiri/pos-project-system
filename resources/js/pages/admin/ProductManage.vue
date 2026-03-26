<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] pb-12 font-[Sarabun,sans-serif] text-black">
    <!-- Header Section -->
    <div class="bg-[var(--color-primary)] py-5 px-10 flex items-center gap-5 shadow-[0_2px_8px_rgba(0,0,0,0.1)] min-h-[100px] box-border">
      <button class="w-10 h-10 rounded-full bg-white/20 border-none text-white cursor-pointer flex items-center justify-center" @click="$router.push('/admin/dashboard')">
        <ArrowLeft :size="24" />
      </button>
      <div>
        <h1 class="text-[28px] font-bold text-[var(--color-highlight-light)] m-0 mb-1">จัดการสินค้า & ค่าบริการ</h1>
        <p class="text-sm text-white/80 m-0">Product & Service Management</p>
      </div>
    </div>

    <div class="max-w-[1200px] mx-auto mt-7.5 px-5 flex flex-col gap-10">
      <!-- 1. ตั้งค่าค่าบริการ Section -->
      <section class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <Timer :size="24" class="text-[var(--color-primary)]" />
          <h2 class="text-xl font-bold text-[var(--color-primary)] m-0 flex-1">ตั้งค่าค่าบริการ</h2>
        </div>
        <div class="section-card">
          <div v-if="globalLoading" class="text-center py-4">กำลังโหลด...</div>
          <div v-else>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-5 mb-5">
              <div>
                <label class="block text-sm font-semibold mb-2 text-[#333]">ราคาต่อคน ต่อชั่วโมง (บาท):</label>
                <input type="number" v-model="hourlyRate" class="setting-input">
              </div>
              <div>
                <label class="block text-sm font-semibold mb-2 text-[#333]">ราคาเหมาวัน Day Pass (บาท):</label>
                <input type="number" v-model="dayPassRate" class="setting-input">
              </div>
            </div>
            <button @click="saveRate" class="py-2.5 px-6 bg-[var(--color-action)] text-white border-none rounded-lg font-semibold cursor-pointer flex items-center gap-2" :disabled="savingRate">
              <Save :size="18" />
              {{ savingRate ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
            </button>
          </div>
        </div>
      </section>

      <!-- 2. จัดการโต๊ะ Section -->
      <section class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <LayoutGrid :size="24" class="text-[var(--color-primary)]" />
          <h2 class="text-xl font-bold text-[var(--color-primary)] m-0 flex-1">จัดการโต๊ะ</h2>
          <button class="py-2 px-4 bg-[var(--color-action)] text-white border-none rounded-md text-sm font-semibold cursor-pointer flex items-center gap-1" @click="openTableModal">
            <Plus :size="16" /> เพิ่มโต๊ะ
          </button>
        </div>
        <div class="section-card">
          <div class="overflow-x-auto text-black">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ชื่อโต๊ะ</th>
                  <th>จำนวนที่นั่ง</th>
                  <th>สถานะลูกค้า</th>
                  <th>สถานะการใช้งาน</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="table in tables" :key="table.id">
                  <td class="font-bold">โต๊ะ {{ table.name }}</td>
                  <td>{{ table.seat_count }} ที่นั่ง</td>
                  <td>
                    <span :class="['status-badge', table.is_available ? 'available' : 'busy']">
                      {{ table.is_available ? 'ว่าง' : 'ไม่ว่าง' }}
                    </span>
                  </td>
                  <td>
                    <span :class="['status-badge', table.is_active ? 'available' : 'inactive']">
                      {{ table.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                    </span>
                  </td>
                  <td class="flex gap-2">
                    <button 
                      @click="editTable(table)" 
                      class="action-btn hover:text-[#1976D2] hover:border-[#1976D2] hover:bg-[#E3F2FD]" 
                      :disabled="!table.is_available"
                      :title="table.is_available ? 'แก้ไข' : 'ไม่สามารถแก้ไขโต๊ะที่มีลูกค้าอยู่'"
                    >
                      <Pencil :size="14" />
                    </button>
                    <button 
                      @click="deleteTable(table.id)" 
                      class="action-btn hover:text-[#D32F2F] hover:border-[#D32F2F] hover:bg-[#FFEBEE]" 
                      :disabled="!table.is_available" 
                      :title="table.is_available ? 'ลบ' : 'ไม่สามารถลบโต๊ะที่มีลูกค้าอยู่'"
                    >
                      <Trash2 :size="14" />
                    </button>
                  </td>
                </tr>
                <tr v-if="tables.length === 0 && !globalLoading">
                  <td colspan="4" class="text-center py-4">ไม่พบข้อมูลโต๊ะ</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 3. รายการสินค้า Section -->
      <section class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <Package :size="24" class="text-[var(--color-primary)]" />
          <h2 class="text-xl font-bold text-[var(--color-primary)] m-0 flex-1">รายการสินค้า</h2>
          <button class="py-2 px-4 bg-[var(--color-action)] text-white border-none rounded-md text-sm font-semibold cursor-pointer flex items-center gap-1" @click="openAddModal">
            <Plus :size="16" /> เพิ่มสินค้า
          </button>
        </div>
        
        <div class="section-card">
          <!-- Category Filter -->
          <div class="flex gap-2.5 mb-5 flex-wrap">
            <button 
              v-for="cat in [{id: 'all', name: 'ทั้งหมด'}, ...categories]" 
              :key="cat.id"
              :class="['filter-btn', { active: selectedCategory === cat.id }]"
              @click="changeCategory(cat.id)"
            >
              {{ cat.name }}
            </button>
          </div>

          <div class="overflow-x-auto text-black" v-if="renderTable">
            <table id="productTable" class="data-table display" style="width:100%">
              <thead>
                <tr>
                  <th>รูป</th>
                  <th>หมวดหมู่</th>
                  <th>ชื่อสินค้า</th>
                  <th>ราคา</th>
                  <th>สต็อก</th>
                  <th>สถานะ</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in filteredProducts" :key="product.id">
                  <td>
                    <img v-if="product.image_url" :src="product.image_url" class="w-10 h-10 rounded-md object-cover">
                    <div v-else class="w-10 h-10 bg-[#eee] rounded-md flex items-center justify-center text-[#999]">
                      <ImageOff :size="18" />
                    </div>
                  </td>
                  <td class="text-[#666]">{{ product.category?.name }}</td>
                  <td class="font-bold">{{ product.name }}</td>
                  <td class="text-[var(--color-action)] font-bold">{{ product.price }} ฿</td>
                  <td>{{ product.stock_qty }}</td>
                  <td>
                    <span :class="['status-badge', product.is_active ? 'available' : 'inactive']">
                      {{ product.is_active ? 'เปิดให้บริการ' : 'ปิดให้บริการ' }}
                    </span>
                  </td>
                  <td class="flex gap-2">
                    <button @click="editProduct(product)" class="action-btn hover:text-[#1976D2] hover:border-[#1976D2] hover:bg-[#E3F2FD]">
                      <Pencil :size="14" />
                    </button>
                    <button @click="deleteProduct(product.id)" class="action-btn hover:text-[#D32F2F] hover:border-[#D32F2F] hover:bg-[#FFEBEE]">
                      <Trash2 :size="14" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <!-- Global Processing Overlay -->
    <div v-if="isSaving" class="fixed inset-0 bg-white/80 flex items-center justify-center z-[9999] backdrop-blur-[4px] cursor-wait">
      <div class="text-center bg-white py-7.5 px-12 rounded-2xl shadow-2xl">
        <div class="spinner"></div>
        <p class="font-bold text-[var(--color-primary)] text-lg m-0">กำลังบันทึกข้อมูล...</p>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขสินค้า -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]" @click.self="showModal = false">
      <div class="bg-white p-7.5 rounded-2xl w-[90%] max-w-[500px] max-h-[90vh] overflow-y-auto shadow-xl">
        <h3 class="mt-0 mb-5 text-xl font-bold">{{ editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}</h3>
        
        <div class="form-group">
          <label>รูปภาพสินค้า:</label>
          <div class="image-upload-wrapper">
            <div v-if="imagePreview || form.image_url" class="relative inline-block w-full">
              <img :src="imagePreview || form.image_url" class="w-full max-h-[250px] object-contain rounded-lg bg-white">
              <button @click="clearImage" class="absolute top-2.5 right-2.5 bg-[#ff4444] text-white border-none rounded-full w-7.5 h-7.5 cursor-pointer flex items-center justify-center font-bold shadow-[0_2px_4px_rgba(0,0,0,0.2)]">×</button>
            </div>
            <div v-else class="py-10 px-5 cursor-pointer flex flex-col items-center gap-2.5 text-[#666] hover:bg-[#f0f0f0]" @click="$refs.fileInput.click()">
              <ImageOff :size="48" />
              <span>คลิกเพื่อเลือกรูปภาพ</span>
            </div>
            <input type="file" @change="onFileChange" accept="image/*" ref="fileInput" class="hidden">
          </div>
        </div>

        <div class="form-group">
          <label>หมวดหมู่:</label>
          <select v-model="form.category_id">
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>ชื่อสินค้า:</label>
          <input type="text" v-model="form.name">
        </div>

        <div class="form-group">
          <label>ราคา (บาท):</label>
          <input type="number" v-model="form.price">
        </div>

        <div class="form-group">
          <label>จำนวนสต็อก:</label>
          <input type="number" v-model="form.stock_qty">
        </div>

        <div class="form-group">
          <label>รายละเอียด:</label>
          <textarea v-model="form.description"></textarea>
        </div>

        <div class="form-group">
          <label class="flex! items-center gap-2.5 cursor-pointer select-none mt-2.5">
            <input type="checkbox" v-model="form.is_active" class="w-5! h-5! m-0! cursor-pointer"> 
            <span>เปิดใช้งานสินค้านี้</span>
          </label>
        </div>

        <div class="flex justify-end gap-2.5 mt-5">
          <button @click="showModal = false" class="py-2.5 px-5 bg-[#eee] border-none rounded-lg cursor-pointer font-semibold" :disabled="savingProduct">ยกเลิก</button>
          <button @click="saveProduct" class="py-2.5 px-6 bg-[var(--color-action)] text-white border-none rounded-lg font-semibold cursor-pointer flex items-center gap-2" :disabled="savingProduct">
            {{ savingProduct ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขโต๊ะ -->
    <div v-if="showTableModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]" @click.self="showTableModal = false">
      <div class="bg-white p-7.5 rounded-2xl w-[90%] max-w-[500px] max-h-[90vh] overflow-y-auto shadow-xl">
        <h3 class="mt-0 mb-5 text-xl font-bold">{{ editingTableId ? 'แก้ไขโต๊ะ' : 'เพิ่มโต๊ะใหม่' }}</h3>
        
        <div class="form-group">
          <label>ชื่อโต๊ะ:</label>
          <input type="text" v-model="tableForm.name" placeholder="เช่น T1, A10">
        </div>

        <div class="form-group">
          <label>จำนวนที่นั่ง:</label>
          <input type="number" v-model="tableForm.seat_count" min="1">
        </div>

        <div class="form-group">
          <label class="flex! items-center gap-2.5 cursor-pointer select-none mt-2.5">
            <input type="checkbox" v-model="tableForm.is_active" class="w-5! h-5! m-0! cursor-pointer"> 
            <span>เปิดใช้งานโต๊ะนี้</span>
          </label>
        </div>

        <div class="flex justify-end gap-2.5 mt-5">
          <button @click="showTableModal = false" class="py-2.5 px-5 bg-[#eee] border-none rounded-lg cursor-pointer font-semibold" :disabled="savingTable">ยกเลิก</button>
          <button @click="saveTable" class="py-2.5 px-6 bg-[var(--color-action)] text-white border-none rounded-lg font-semibold cursor-pointer flex items-center gap-2" :disabled="savingTable">
            {{ savingTable ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, computed, nextTick, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { 
  ArrowLeft, Timer, LayoutGrid, Package, Save, Plus, Pencil, Trash2, ImageOff
} from 'lucide-vue-next';

export default {
  name: 'ProductManage',
  components: {
    ArrowLeft, Timer, LayoutGrid, Package, Save, Plus, Pencil, Trash2, ImageOff
  },
  setup() {
    const { success, error, confirm } = useAlert();
    const hourlyRate = ref(0);
    const dayPassRate = ref(0);
    const savingRate = ref(false);
    const savingProduct = ref(false);
    const savingTable = ref(false);
    const globalLoading = ref(true);

    const products = ref([]);
    const categories = ref([]);
    const tables = ref([]);
    const selectedCategory = ref('all');
    const renderTable = ref(true);

    const showModal = ref(false);
    const editingId = ref(null);
    const showTableModal = ref(false);
    const editingTableId = ref(null);
    const tableForm = ref({ name: '', seat_count: 4, is_active: true });

    const imagePreview = ref(null);
    const selectedFile = ref(null);
    const fileInput = ref(null);
    let dataTable = null;

    const form = ref({
      name: '', category_id: '', price: 0, stock_qty: 0, description: '', is_active: true, image_url: null
    });

    const initDataTable = () => {
      nextTick(() => {
        if (typeof $ === 'undefined' || !$.fn.DataTable) return;
        const tableId = '#productTable';
        if ($(tableId).length === 0) return;
        
        if ($.fn.DataTable.isDataTable(tableId)) {
          $(tableId).DataTable().destroy();
        }
        
        dataTable = $(tableId).DataTable({
          language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json' },
          pageLength: 10,
          order: [[2, 'asc']],
          responsive: true
        });
      });
    };

    const fetchData = async () => {
      globalLoading.value = true;
      try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const results = await Promise.allSettled([
          axios.get('/api/settings/hourly_rate', config),
          axios.get('/api/settings/day_pass_rate', config),
          axios.get('/api/categories', config),
          axios.get('/api/products?all=1', config),
          axios.get('/api/tables', config)
        ]);

        if (results[0].status === 'fulfilled') hourlyRate.value = results[0].value.data.value;
        if (results[1].status === 'fulfilled') dayPassRate.value = results[1].value.data.value;
        if (results[2].status === 'fulfilled') categories.value = results[2].value.data.filter(c => c.type !== 'service');
        if (results[3].status === 'fulfilled') products.value = results[3].value.data.filter(p => p.category?.type !== 'service');
        if (results[4].status === 'fulfilled') tables.value = results[4].value.data;

        await nextTick();
        initDataTable();
      } catch (err) {
        console.error(err);
      } finally {
        globalLoading.value = false;
      }
    };

    const saveRate = async () => {
      savingRate.value = true;
      try {
        const token = localStorage.getItem('token');
        await axios.post('/api/settings', {
          settings: { hourly_rate: hourlyRate.value, day_pass_rate: dayPassRate.value }
        }, { headers: { Authorization: `Bearer ${token}` } });
        success('บันทึกสำเร็จ');
      } catch (err) {
        error('ผิดพลาด', err.message);
      } finally {
        savingRate.value = false;
      }
    };

    const openTableModal = () => {
      editingTableId.value = null;
      tableForm.value = { name: '', seat_count: 4, is_active: true };
      showTableModal.value = true;
    };

    const editTable = (table) => {
      editingTableId.value = table.id;
      tableForm.value = { name: table.name, seat_count: table.seat_count, is_active: !!table.is_active };
      showTableModal.value = true;
    };

    const saveTable = async () => {
      savingTable.value = true;
      try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        if (editingTableId.value) await axios.put(`/api/tables/${editingTableId.value}`, tableForm.value, config);
        else await axios.post('/api/tables', tableForm.value, config);
        showTableModal.value = false;
        fetchData();
        success('บันทึกโต๊ะสำเร็จ');
      } catch (err) { error('ผิดพลาด', err.message); }
      finally { savingTable.value = false; }
    };

    const deleteTable = async (id) => {
      if (!await confirm('ยืนยันการลบ?')) return;
      try {
        const token = localStorage.getItem('token');
        await axios.delete(`/api/tables/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        fetchData();
        success('ลบสำเร็จ');
      } catch (err) { error('ลบไม่สำเร็จ', err.message); }
    };

    const filteredProducts = computed(() => {
      if (selectedCategory.value === 'all') return products.value;
      return products.value.filter(p => String(p.category_id) === String(selectedCategory.value));
    });

    const changeCategory = async (catId) => {
      selectedCategory.value = catId;
      renderTable.value = false;
      await nextTick();
      renderTable.value = true;
      await nextTick();
      initDataTable();
    };

    const onFileChange = (e) => {
      const file = e.target.files[0];
      if (!file) return;
      selectedFile.value = file;
      imagePreview.value = URL.createObjectURL(file);
    };

    const clearImage = () => {
      selectedFile.value = null; 
      imagePreview.value = null; 
      form.value.image_url = null;
      form.value.image_path = null;
    };

    const openAddModal = () => {
      editingId.value = null; imagePreview.value = null; selectedFile.value = null;
      form.value = { name: '', category_id: categories.value[0]?.id || '', price: 0, stock_qty: 0, description: '', is_active: true, image_url: null };
      showModal.value = true;
    };

    const editProduct = (product) => {
      editingId.value = product.id; imagePreview.value = null; selectedFile.value = null;
      form.value = { ...product };
      showModal.value = true;
    };

    const saveProduct = async () => {
      savingProduct.value = true;
      try {
        const token = localStorage.getItem('token');
        const formData = new FormData();
        
        Object.keys(form.value).forEach(key => {
          if (key === 'image_url') return;
          const value = form.value[key];
          if (key === 'image_path' && (value === null || value === '')) {
            formData.append(key, '');
          } else if (value !== null) {
            formData.append(key, value);
          }
        });

        if (selectedFile.value) formData.append('image', selectedFile.value);
        if (editingId.value) formData.append('_method', 'PUT');

        const url = editingId.value ? `/api/products/${editingId.value}` : '/api/products';
        await axios.post(url, formData, { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'multipart/form-data' } });
        showModal.value = false;
        fetchData();
        success('บันทึกสำเร็จ');
      } catch (err) { error('ผิดพลาด', err.message); }
      finally { savingProduct.value = false; }
    };

    const deleteProduct = async (id) => {
      if (!await confirm('ลบสินค้านี้?')) return;
      try {
        const token = localStorage.getItem('token');
        await axios.delete(`/api/products/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        fetchData();
        success('ลบสำเร็จ');
      } catch (err) { error('ผิดพลาด', err.message); }
    };

    const isSaving = computed(() => savingRate.value || savingProduct.value || savingTable.value);

    onMounted(fetchData);
    onBeforeUnmount(() => { if (dataTable) dataTable.destroy(); });

    return {
      hourlyRate, dayPassRate, savingRate, saveRate,
      products, categories, selectedCategory, filteredProducts, changeCategory,
      showModal, editingId, form, openAddModal, editProduct, saveProduct, deleteProduct,
      onFileChange, imagePreview, clearImage, fileInput,
      tables, showTableModal, editingTableId, tableForm, openTableModal, editTable, saveTable, deleteTable,
      globalLoading, renderTable, savingProduct, savingTable, isSaving
    };
  }
};
</script>

<style scoped>
/* Force input/select/textarea colors */
input, select, textarea {
  color: #000000 !important;
  background-color: #ffffff !important;
}

/* Section Card */
.section-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  border: 1px solid #eee;
  color: #000000;
}

.setting-input { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; color: #000000; }

/* Data Table */
.data-table { width: 100%; border-collapse: collapse; color: #000000; }
.data-table th { text-align: left; padding: 12px; border-bottom: 2px solid #eee; font-size: 14px; color: #000000; font-weight: 700; }
.data-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; color: #000000; }

/* DataTable Overrides */
:deep(.dataTables_wrapper) { color: #000000 !important; }
:deep(.dataTables_filter input), :deep(.dataTables_length select) {
  color: #000000 !important; background-color: #ffffff !important;
  border: 1px solid #ddd !important; padding: 5px !important; border-radius: 4px !important;
}
:deep(.dataTables_info), :deep(.dataTables_paginate) { color: #000000 !important; margin-top: 15px !important; }

/* Status Badge */
.status-badge { padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }
.status-badge.available { background: #E8F5E9; color: #2E7D32; }
.status-badge.busy { background: #FFEBEE; color: #C62828; }
.status-badge.inactive { background: #F5F5F5; color: #757575; }

/* Action Button */
.action-btn {
  width: 32px; height: 32px; border-radius: 6px; border: 1px solid #ddd;
  background-color: #ffffff; cursor: pointer; display: flex; align-items: center;
  justify-content: center; transition: all 0.2s ease;
}
.action-btn:hover:not(:disabled) { transform: scale(1.1); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.action-btn:disabled { opacity: 0.4; cursor: not-allowed; background-color: #f5f5f5; border-color: #eee; }

/* Filter Button */
.filter-btn { padding: 6px 15px; border-radius: 15px; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 13px; }
.filter-btn.active { background: var(--color-action); color: white; border-color: var(--color-action); }

/* Form Group */
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }

/* Image Upload */
.image-upload-wrapper {
  border: 2px dashed #ddd; border-radius: 12px; padding: 10px; background: #fafafa; text-align: center;
}

/* Spinner */
.spinner {
  width: 50px; height: 50px; border: 4px solid #f3f3f3;
  border-top: 4px solid var(--color-primary); border-radius: 50%;
  animation: spin 1s linear infinite; margin: 0 auto 15px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>