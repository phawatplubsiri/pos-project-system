<template>
  <div class="product-manage-container">
    <!-- Header Section -->
    <div class="page-header">
      <button class="back-button" @click="$router.push('/admin/dashboard')">
        <ChevronLeft :size="28" />
      </button>
      <div class="header-content">
        <h1 class="page-title">จัดการสินค้า & ค่าบริการ</h1>
        <p class="page-subtitle">Product & Service Management</p>
      </div>
    </div>

    <div class="main-content-wrapper">
      <!-- 1. ตั้งค่าค่าบริการ Section -->
      <section class="manage-section">
        <div class="section-header-main">
          <Timer :size="24" class="text-primary" />
          <h2 class="section-title-main">ตั้งค่าค่าบริการ</h2>
        </div>
        <div class="section-card">
          <div v-if="globalLoading" class="text-center py-4">กำลังโหลด...</div>
          <div v-else>
            <div class="settings-grid">
              <div class="setting-card">
                <label class="setting-label">ราคาต่อคน ต่อชั่วโมง (บาท):</label>
                <input type="number" v-model="hourlyRate" class="setting-input">
              </div>
              <div class="setting-card">
                <label class="setting-label">ราคาเหมาวัน Day Pass (บาท):</label>
                <input type="number" v-model="dayPassRate" class="setting-input">
              </div>
            </div>
            <button @click="saveRate" class="save-btn" :disabled="savingRate">
              <Save :size="18" />
              {{ savingRate ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
            </button>
          </div>
        </div>
      </section>

      <!-- 2. จัดการโต๊ะ Section -->
      <section class="manage-section">
        <div class="section-header-main">
          <LayoutGrid :size="24" class="text-primary" />
          <h2 class="section-title-main">จัดการโต๊ะ</h2>
          <button class="add-btn-small" @click="openTableModal">
            <Plus :size="16" /> เพิ่มโต๊ะ
          </button>
        </div>
        <div class="section-card">
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ชื่อโต๊ะ</th>
                  <th>จำนวนที่นั่ง</th>
                  <th>สถานะ</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="table in tables" :key="table.id">
                  <td class="font-bold">โต๊ะ {{ table.name }}</td>
                  <td>{{ table.seat_count }} ที่นั่ง</td>
                  <td>
                    <span :class="['status-badge', table.status === 'available' ? 'available' : 'busy']">
                      {{ table.status === 'available' ? 'ว่าง' : 'ไม่ว่าง' }}
                    </span>
                  </td>
                  <td class="action-cell">
                    <button @click="editTable(table)" class="action-btn edit-btn">
                      <Pencil :size="14" />
                    </button>
                    <button @click="deleteTable(table.id)" class="action-btn delete-btn" :disabled="table.status !== 'available'">
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
      <section class="manage-section">
        <div class="section-header-main">
          <Package :size="24" class="text-primary" />
          <h2 class="section-title-main">รายการสินค้า</h2>
          <button class="add-btn-small" @click="openAddModal">
            <Plus :size="16" /> เพิ่มสินค้า
          </button>
        </div>
        
        <div class="section-card">
          <!-- Category Filter -->
          <div class="filter-bar">
            <button 
              v-for="cat in [{id: 'all', name: 'ทั้งหมด'}, ...categories]" 
              :key="cat.id"
              :class="['filter-btn', { active: selectedCategory === cat.id }]"
              @click="changeCategory(cat.id)"
            >
              {{ cat.name }}
            </button>
          </div>

          <div class="table-container" v-if="renderTable">
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
                    <img v-if="product.image_url" :src="product.image_url" class="product-img-mini">
                    <div v-else class="no-img-mini">
                      <ImageOff :size="18" />
                    </div>
                  </td>
                  <td class="text-secondary">{{ product.category?.name }}</td>
                  <td class="font-bold">{{ product.name }}</td>
                  <td class="text-accent">{{ product.price }} ฿</td>
                  <td>{{ product.stock_qty }}</td>
                  <td>
                    <span :class="['status-badge', product.is_active ? 'available' : 'inactive']">
                      {{ product.is_active ? 'เปิด' : 'ปิด' }}
                    </span>
                  </td>
                  <td class="action-cell">
                    <button @click="editProduct(product)" class="action-btn edit-btn">
                      <Pencil :size="14" />
                    </button>
                    <button @click="deleteProduct(product.id)" class="action-btn delete-btn">
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

    <!-- Modals -->
    <!-- Modal เพิ่ม/แก้ไขสินค้า -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content-custom shadow-xl">
        <h3 class="modal-title">{{ editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}</h3>
        
        <div class="form-group">
          <label>รูปภาพสินค้า:</label>
          <div class="image-upload-wrapper">
            <div v-if="imagePreview || form.image_url" class="preview-container-large">
              <img :src="imagePreview || form.image_url" class="image-preview-large">
              <button @click="clearImage" class="clear-img-btn-large">×</button>
            </div>
            <div v-else class="upload-placeholder" @click="$refs.fileInput.click()">
              <ImageOff :size="48" />
              <span>คลิกเพื่อเลือกรูปภาพ</span>
            </div>
            <input type="file" @change="onFileChange" accept="image/*" ref="fileInput" class="file-input-hidden">
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
          <label class="checkbox-label-inline">
            <input type="checkbox" v-model="form.is_active"> 
            <span>เปิดใช้งานสินค้านี้</span>
          </label>
        </div>

        <div class="modal-actions">
          <button @click="showModal = false" class="cancel-btn">ยกเลิก</button>
          <button @click="saveProduct" class="save-btn">บันทึก</button>
        </div>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขโต๊ะ -->
    <div v-if="showTableModal" class="modal-overlay" @click.self="showTableModal = false">
      <div class="modal-content-custom shadow-xl">
        <h3 class="modal-title">{{ editingTableId ? 'แก้ไขโต๊ะ' : 'เพิ่มโต๊ะใหม่' }}</h3>
        
        <div class="form-group">
          <label>ชื่อโต๊ะ:</label>
          <input type="text" v-model="tableForm.name" placeholder="เช่น T1, A10">
        </div>

        <div class="form-group">
          <label>จำนวนที่นั่ง:</label>
          <input type="number" v-model="tableForm.seat_count" min="1">
        </div>

        <div class="modal-actions">
          <button @click="showTableModal = false" class="cancel-btn">ยกเลิก</button>
          <button @click="saveTable" class="save-btn">บันทึก</button>
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
  ChevronLeft, Timer, LayoutGrid, Package, Save, Plus, Pencil, Trash2, ImageOff
} from 'lucide-vue-next';

export default {
  name: 'ProductManage',
  components: {
    ChevronLeft, Timer, LayoutGrid, Package, Save, Plus, Pencil, Trash2, ImageOff
  },
  setup() {
    const { success, error, confirm } = useAlert();
    const hourlyRate = ref(0);
    const dayPassRate = ref(0);
    const savingRate = ref(false);
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
    const tableForm = ref({ name: '', seat_count: 4 });

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
          order: [[2, 'asc']], // Order by product name (index 2)
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
      tableForm.value = { name: '', seat_count: 4 };
      showTableModal.value = true;
    };

    const editTable = (table) => {
      editingTableId.value = table.id;
      tableForm.value = { name: table.name, seat_count: table.seat_count };
      showTableModal.value = true;
    };

    const saveTable = async () => {
      try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        if (editingTableId.value) await axios.put(`/api/tables/${editingTableId.value}`, tableForm.value, config);
        else await axios.post('/api/tables', tableForm.value, config);
        showTableModal.value = false;
        fetchData();
        success('บันทึกโต๊ะสำเร็จ');
      } catch (err) { error('ผิดพลาด', err.message); }
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
      selectedFile.value = null; imagePreview.value = null; form.value.image_url = null;
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
      try {
        const token = localStorage.getItem('token');
        const formData = new FormData();
        Object.keys(form.value).forEach(key => {
          if (form.value[key] !== null) formData.append(key, form.value[key]);
        });
        if (selectedFile.value) formData.append('image', selectedFile.value);
        if (editingId.value) formData.append('_method', 'PUT');

        const url = editingId.value ? `/api/products/${editingId.value}` : '/api/products';
        await axios.post(url, formData, { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'multipart/form-data' } });
        showModal.value = false;
        fetchData();
        success('บันทึกสำเร็จ');
      } catch (err) { error('ผิดพลาด', err.message); }
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

    onMounted(fetchData);
    onBeforeUnmount(() => { if (dataTable) dataTable.destroy(); });

    return {
      hourlyRate, dayPassRate, savingRate, saveRate,
      products, categories, selectedCategory, filteredProducts, changeCategory,
      showModal, editingId, form, openAddModal, editProduct, saveProduct, deleteProduct,
      onFileChange, imagePreview, clearImage, fileInput,
      tables, showTableModal, editingTableId, tableForm, openTableModal, editTable, saveTable, deleteTable,
      globalLoading, renderTable
    };
  }
};
</script>

<style scoped>
.product-manage-container {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  padding-bottom: 50px;
  font-family: 'Sarabun', sans-serif;
  color: #000000; /* บังคับตัวอักษรสีดำเป็นค่าเริ่มต้น */
}

/* บังคับสีตัวอักษรสำหรับ Input และ Form ต่างๆ */
input, select, textarea {
  color: #000000 !important;
  background-color: #ffffff !important;
}

.page-header {
  background-color: var(--color-primary);
  padding: 20px 40px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.back-button {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: #ffffff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-highlight-light);
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.main-content-wrapper {
  max-width: 1200px;
  margin: 30px auto;
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.manage-section {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.section-header-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title-main {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-primary);
  margin: 0;
  flex: 1;
}

.section-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  border: 1px solid #eee;
  color: #000000;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.setting-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #333333; }
.setting-input { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; color: #000000; }

.save-btn {
  padding: 10px 25px;
  background: var(--color-action);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.add-btn-small {
  padding: 8px 16px;
  background: var(--color-action);
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
}

.table-container { overflow-x: auto; color: #000000; }
.data-table { width: 100%; border-collapse: collapse; color: #000000; }
.data-table th { text-align: left; padding: 12px; border-bottom: 2px solid #eee; font-size: 14px; color: #000000; font-weight: 700; }
.data-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; color: #000000; }

/* แก้ไขสีของ DataTable Components (Search, Info, Pagination) */
:deep(.dataTables_wrapper) {
  color: #000000 !important;
}
:deep(.dataTables_filter input), :deep(.dataTables_length select) {
  color: #000000 !important;
  background-color: #ffffff !important;
  border: 1px solid #ddd !important;
  padding: 5px !important;
  border-radius: 4px !important;
}
:deep(.dataTables_info), :deep(.dataTables_paginate) {
  color: #000000 !important;
  margin-top: 15px !important;
}

.status-badge { padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }
.status-badge.available { background: #E8F5E9; color: #2E7D32; }
.status-badge.busy { background: #FFEBEE; color: #C62828; }
.status-badge.inactive { background: #F5F5F5; color: #757575; }

.action-cell { display: flex; gap: 8px; }
.action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #ddd; background-color: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.action-btn:hover { transform: scale(1.1); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

.edit-btn:hover { color: #1976D2; border-color: #1976D2; background-color: #E3F2FD; }
.delete-btn:hover { color: #D32F2F; border-color: #D32F2F; background-color: #FFEBEE; }

.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn { padding: 6px 15px; border-radius: 15px; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 13px; }
.filter-btn.active { background: var(--color-action); color: white; border-color: var(--color-action); }

.product-img-mini { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; }
.no-img-mini { width: 40px; height: 40px; background: #eee; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #999; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content-custom { background: white; padding: 30px; border-radius: 16px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.modal-title { margin-top: 0; margin-bottom: 20px; font-size: 20px; font-weight: 700; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

/* New Image Upload Styles */
.image-upload-wrapper {
  border: 2px dashed #ddd;
  border-radius: 12px;
  padding: 10px;
  background: #fafafa;
  text-align: center;
}

.preview-container-large {
  position: relative;
  display: inline-block;
  width: 100%;
}

.image-preview-large {
  width: 100%;
  max-height: 250px;
  object-fit: contain;
  border-radius: 8px;
  background: #fff;
}

.clear-img-btn-large {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #ff4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.upload-placeholder {
  padding: 40px 20px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #666;
}

.upload-placeholder:hover {
  background: #f0f0f0;
}

.file-input-hidden {
  display: none;
}

/* Inline Checkbox Styles */
.checkbox-label-inline {
  display: flex !important;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
  margin-top: 10px;
}

.checkbox-label-inline input[type="checkbox"] {
  width: 20px !important;
  height: 20px !important;
  margin: 0 !important;
  cursor: pointer;
}

.font-bold { font-weight: 700; }
.text-primary { color: var(--color-primary); }
.text-accent { color: var(--color-action); font-weight: 700; }
.text-secondary { color: #666; }
</style>