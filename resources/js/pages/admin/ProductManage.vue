<template>
  <div class="product-manage-container">
    <!-- Header Section -->
    <div class="page-header">
      <button class="back-button" @click="$router.push('/admin/dashboard')">
        ←
      </button>
      <div class="header-content">
        <h1 class="page-title">จัดการสินค้า & ค่าบริการ</h1>
        <p class="page-subtitle">Product & Service Management</p>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card">
      <!-- Tab Navigation -->
      <div class="tab-navigation">
        <button 
          :class="['tab-btn', { active: activeTab === 'service' }]"
          @click="activeTab = 'service'"
        >
          ⏱️ ตั้งค่าบริการ
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'tables' }]"
          @click="activeTab = 'tables'"
        >
          🪑 จัดการโต๊ะ
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'products' }]"
          @click="activeTab = 'products'"
        >
          🍔 รายการสินค้า
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Tab 1: Service Settings -->
        <div v-if="activeTab === 'service'" class="tab-panel">
          <h2 class="section-title">ตั้งค่าค่าบริการ</h2>
          
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
            {{ savingRate ? 'กำลังบันทึก...' : '💾 บันทึกการตั้งค่า' }}
          </button>
        </div>

        <!-- Tab 2: Table Management -->
        <div v-if="activeTab === 'tables'" class="tab-panel">
          <div class="panel-header">
            <h2 class="section-title">จัดการโต๊ะ</h2>
            <button class="add-btn" @click="openTableModal">+ เพิ่มโต๊ะใหม่</button>
          </div>

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
                  <td>{{ table.name }}</td>
                  <td>{{ table.seat_count }} ที่นั่ง</td>
                  <td>
                    <span :class="['status-badge', table.status === 'available' ? 'available' : 'busy']">
                      {{ table.status === 'available' ? 'ว่าง' : 'ไม่ว่าง' }}
                    </span>
                  </td>
                  <td class="action-cell">
                    <button @click="editTable(table)" class="action-btn edit-btn" title="แก้ไข">✏️</button>
                    <button @click="deleteTable(table.id)" class="action-btn delete-btn" title="ลบ" :disabled="table.status !== 'available'">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 3: Product List -->
        <div v-if="activeTab === 'products'" class="tab-panel">
          <div class="panel-header">
            <h2 class="section-title">รายการสินค้า</h2>
            <button class="add-btn" @click="openAddModal">+ เพิ่มสินค้าใหม่</button>
          </div>

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

          <div class="table-container">
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
                    <div v-else class="no-img-mini">ไม่มีรูป</div>
                  </td>
                  <td>{{ product.category?.name }}</td>
                  <td>{{ product.name }}</td>
                  <td>{{ product.price }} ฿</td>
                  <td>{{ product.stock_qty }}</td>
                  <td>
                    <span :class="['status-badge', product.is_active ? 'available' : 'inactive']">
                      {{ product.is_active ? 'เปิดใช้งาน' : 'ปิด' }}
                    </span>
                  </td>
                  <td class="action-cell">
                    <button @click="editProduct(product)" class="action-btn edit-btn" title="แก้ไข">✏️</button>
                    <button @click="deleteProduct(product.id)" class="action-btn delete-btn" title="ลบ">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขสินค้า -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content-custom">
        <h3 class="modal-title">{{ editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}</h3>
        
        <div class="form-group">
          <label>รูปภาพสินค้า:</label>
          <div v-if="imagePreview || form.image_url" class="preview-container">
            <img :src="imagePreview || form.image_url" class="image-preview">
            <button @click="clearImage" class="clear-img-btn">×</button>
          </div>
          <input type="file" @change="onFileChange" accept="image/*" ref="fileInput">
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

        <div class="form-group checkbox-group">
          <label>
            <input type="checkbox" v-model="form.is_active"> เปิดใช้งาน
          </label>
        </div>

        <div class="modal-actions">
          <button @click="showModal = false" class="cancel-btn">ยกเลิก</button>
          <button @click="saveProduct" class="save-btn">✅ บันทึก</button>
        </div>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขโต๊ะ -->
    <div v-if="showTableModal" class="modal-overlay" @click.self="showTableModal = false">
      <div class="modal-content-custom">
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
          <button @click="saveTable" class="save-btn">✅ บันทึก</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, computed, nextTick, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';

export default {
  setup() {
    const activeTab = ref('service'); // Tab state
    const hourlyRate = ref(40);
    const dayPassRate = ref(199);
    const savingRate = ref(false);
    
    const products = ref([]);
    const categories = ref([]);
    const tables = ref([]);
    const selectedCategory = ref('all');

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
      name: '',
      category_id: '',
      price: 0,
      stock_qty: 0,
      description: '',
      is_active: true,
      image_url: null
    });

    const initDataTable = () => {
      // ทำลาย DataTable เก่าถ้ามีอยู่ก่อน
      if ($.fn.DataTable.isDataTable('#productTable')) {
        $('#productTable').DataTable().destroy();
      }
      
      // ใช้ nextTick เพื่อให้แน่ใจว่า Vue render ข้อมูลใหม่ลงใน DOM เรียบร้อยแล้ว
      nextTick(() => {
        dataTable = $('#productTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
            },
            pageLength: 10,
            order: [[1, 'asc']],
            // รีเซ็ตการค้นหาและสถานะต่างๆ ของตาราง
            stateSave: false 
        });
      });
    };

    const fetchData = async () => {
      try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        
        // ดึงราคาต่อชั่วโมง
        const rateRes = await axios.get('/api/settings/hourly_rate', config);
        if (rateRes.data) hourlyRate.value = rateRes.data.value;

        // ดึงราคาเหมาวัน
        const dayRateRes = await axios.get('/api/settings/day_pass_rate', config);
        if (dayRateRes.data) dayPassRate.value = dayRateRes.data.value;

        // ดึงหมวดหมู่ (กรองเอา 'service' ออก)
        const catRes = await axios.get('/api/categories', config);
        categories.value = catRes.data.filter(c => c.type !== 'service');

        // ดึงสินค้า
        const prodRes = await axios.get('/api/products?all=1', config);
        // กรองเอาสินค้าที่อยู่ในหมวด 'service' ออกด้วย
        products.value = prodRes.data.filter(p => p.category?.type !== 'service');

        // ดึงข้อมูลโต๊ะ
        const tableRes = await axios.get('/api/tables', config);
        tables.value = tableRes.data;

      } catch (error) {
        console.error('Fetch error:', error);
      }
    };

    const saveRate = async () => {
      savingRate.value = true;
      try {
        const token = localStorage.getItem('token');
        await axios.post('/api/settings', {
          settings: { 
              hourly_rate: hourlyRate.value,
              day_pass_rate: dayPassRate.value 
          }
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        alert('✅ บันทึกการตั้งค่าสำเร็จ');
      } catch (error) {
        alert('❌ บันทึกไม่สำเร็จ');
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
      if (!tableForm.value.name) return alert('กรุณาระบุชื่อโต๊ะ');
      try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        if (editingTableId.value) {
          await axios.put(`/api/tables/${editingTableId.value}`, tableForm.value, config);
        } else {
          await axios.post('/api/tables', tableForm.value, config);
        }
        showTableModal.value = false;
        fetchData();
        alert('✅ บันทึกโต๊ะสำเร็จ');
      } catch (error) {
        alert('❌ ผิดพลาด: ' + (error.response?.data?.message || 'โปรดลองใหม่'));
      }
    };

    const deleteTable = async (id) => {
      if (!confirm('ยืนยันลบโต๊ะนี้?')) return;
      try {
        const token = localStorage.getItem('token');
        await axios.delete(`/api/tables/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchData();
      } catch (error) {
        alert('ลบไม่สำเร็จ: ' + (error.response?.data?.message || 'โต๊ะอาจจะไม่ว่าง'));
      }
    };

    const filteredProducts = computed(() => {
      if (selectedCategory.value === 'all') return products.value;
      return products.value.filter(p => p.category_id === selectedCategory.value);
    });

    const changeCategory = (catId) => {
        // ทำลายตารางก่อนเปลี่ยนข้อมูล เพื่อไม่ให้ jQuery ค้างสถานะเดิม
        if ($.fn.DataTable.isDataTable('#productTable')) {
            $('#productTable').DataTable().destroy();
        }
        selectedCategory.value = catId;
        // การเปลี่ยน selectedCategory จะไป trigger watch(filteredProducts) และสั่ง initDataTable() เอง
    };

    // Re-init DataTable when filtered products change (only if products tab is active)
    watch(filteredProducts, () => {
        if (activeTab.value === 'products') {
            initDataTable();
        }
    });

    // Initialize DataTable when switching to products tab
    watch(activeTab, (newTab) => {
        if (newTab === 'products') {
            nextTick(() => {
                initDataTable();
            });
        }
    });

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
      if (fileInput.value) fileInput.value.value = '';
    };

    const openAddModal = () => {
      editingId.value = null;
      imagePreview.value = null;
      selectedFile.value = null;
      form.value = {
        name: '',
        category_id: categories.value[0]?.id || '',
        price: 0,
        stock_qty: 0,
        description: '',
        is_active: true,
        image_url: null
      };
      showModal.value = true;
    };

    const editProduct = (product) => {
      editingId.value = product.id;
      imagePreview.value = null;
      selectedFile.value = null;
      form.value = { ...product };
      showModal.value = true;
    };

    const saveProduct = async () => {
      try {
        const token = localStorage.getItem('token');
        
        // ใช้ FormData เพราะมีการส่งไฟล์
        const formData = new FormData();
        formData.append('name', form.value.name);
        formData.append('category_id', form.value.category_id);
        formData.append('price', form.value.price);
        formData.append('stock_qty', form.value.stock_qty);
        formData.append('description', form.value.description || '');
        formData.append('is_active', form.value.is_active ? 1 : 0);
        
        if (selectedFile.value) {
          formData.append('image', selectedFile.value);
        }

        const config = { 
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          } 
        };
        
        if (editingId.value) {
          // Laravel Workaround: ใช้ POST และส่ง _method: PUT สำหรับ FormData
          formData.append('_method', 'PUT');
          await axios.post(`/api/products/${editingId.value}`, formData, config);
        } else {
          await axios.post('/api/products', formData, config);
        }

        showModal.value = false;
        fetchData();
        alert('✅ บันทึกสินค้าสำเร็จ');
      } catch (error) {
        console.error(error);
        alert('❌ ผิดพลาด: ' + (error.response?.data?.message || 'โปรดลองใหม่'));
      }
    };

    const deleteProduct = async (id) => {
      if (!confirm('ยืนยันลบสินค้านี้?')) return;
      try {
        const token = localStorage.getItem('token');
        await axios.delete(`/api/products/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchData();
      } catch (error) {
        alert('ลบไม่สำเร็จ');
      }
    };

    onMounted(fetchData);

    onBeforeUnmount(() => {
        if (dataTable) {
            dataTable.destroy();
        }
    });

    return {
      activeTab,
      hourlyRate, dayPassRate, savingRate, saveRate,
      products, categories, selectedCategory, filteredProducts, changeCategory,
      showModal, editingId, form, openAddModal, editProduct, saveProduct, deleteProduct,
      onFileChange, imagePreview, clearImage, fileInput,
      tables, showTableModal, editingTableId, tableForm, openTableModal, editTable, saveTable, deleteTable
    };
  }
};
</script>

<style scoped>
/* Product Management Container */
.product-manage-container {
  min-height: 100vh;
  background-color: #fff8e7; /* Cream background */
  font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Page Header - Brown Section */
.page-header {
  background-color: #8b4513; /* Brown */
  padding: 24px 40px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.back-button {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.2);
  border: none;
  color: #ffffff;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.back-button:hover {
  background-color: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #ff8c42; /* Orange */
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

/* Main Content Card */
.content-card {
  max-width: 1400px;
  margin: 40px auto;
  background-color: #ffffff;
  border: 2px solid #deb887;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.08);
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  background-color: #f5e6d3;
  border-bottom: 2px solid #deb887;
}

.tab-btn {
  flex: 1;
  padding: 16px 24px;
  font-size: 16px;
  font-weight: 600;
  color: #8b4513;
  background-color: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.tab-btn:hover {
  background-color: #ffe4b5;
}

.tab-btn.active {
  background-color: #ffffff;
  color: #ff8c42;
  border-bottom: 3px solid #ff8c42;
}

/* Tab Content */
.tab-content {
  padding: 32px;
}

.tab-panel {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Section Title */
.section-title {
  font-size: 22px;
  font-weight: 700;
  color: #8b4513;
  margin: 0 0 24px 0;
  padding-bottom: 8px;
  border-bottom: 3px solid #ff8c42;
  display: inline-block;
}

/* Panel Header */
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

/* Settings Grid */
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.setting-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.setting-label {
  font-size: 15px;
  font-weight: 600;
  color: #654321;
}

.setting-input {
  padding: 12px 16px;
  font-size: 16px;
  border: 1px solid #deb887;
  border-radius: 8px;
  background-color: #ffffff;
  color: #654321;
  transition: all 0.3s ease;
}

.setting-input:focus {
  outline: none;
  border-color: #ff8c42;
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
}

/* Buttons */
.save-btn {
  padding: 14px 32px;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
}

.save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #ff7a29, #e67e22);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.add-btn {
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
}

.add-btn:hover {
  background: linear-gradient(135deg, #ff7a29, #e67e22);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
}

/* Filter Bar */
.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 8px 20px;
  font-size: 14px;
  font-weight: 600;
  color: #8b4513;
  background-color: #ffffff;
  border: 2px solid #deb887;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-btn:hover {
  background-color: #ffe4b5;
  border-color: #ff8c42;
}

.filter-btn.active {
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  color: #ffffff;
  border-color: #ff8c42;
}

/* Table Container */
.table-container {
  overflow-x: auto;
  background-color: #fffef9;
  border-radius: 8px;
  padding: 16px;
}

/* Data Table */
.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.data-table thead th {
  padding: 14px 12px;
  text-align: left;
  font-size: 14px;
  font-weight: 700;
  color: #8b4513;
  border-bottom: 2px solid #f5e6d3;
  background-color: transparent;
}

.data-table tbody tr {
  border-bottom: 1px solid #f5e6d3;
  transition: background-color 0.2s ease;
}

.data-table tbody tr:hover {
  background-color: #fff8e7;
}

.data-table tbody td {
  padding: 14px 12px;
  font-size: 15px;
  color: #654321;
}

/* Product Image */
.product-img-mini {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #deb887;
}

.no-img-mini {
  font-size: 12px;
  color: #c19a6b;
  font-style: italic;
}

/* Status Badges */
.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  display: inline-block;
}

.status-badge.available {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.status-badge.busy {
  background-color: #ffebee;
  color: #c62828;
}

.status-badge.inactive {
  background-color: #f5f5f5;
  color: #757575;
}

/* Action Cell */
.action-cell {
  display: flex;
  gap: 8px;
  align-items: center;
}

.action-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #deb887;
  background-color: #fff8e7;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.action-btn:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(139, 69, 19, 0.15);
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.edit-btn:hover:not(:disabled) {
  background-color: #ffe4b5;
  border-color: #ff8c42;
}

.delete-btn:hover:not(:disabled) {
  background-color: #ffe4e1;
  border-color: #c85a54;
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
  backdrop-filter: blur(4px);
}

/* Modal */
.modal-content-custom {
  background: #ffffff;
  padding: 32px;
  border-radius: 16px;
  width: 90%;
  max-width: 520px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  border: 2px solid #deb887;
}

.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #8b4513;
  margin: 0 0 24px 0;
}

/* Form Group */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #654321;
  margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  font-size: 15px;
  border: 1px solid #deb887;
  border-radius: 8px;
  background-color: #ffffff;
  color: #654321;
  transition: all 0.3s ease;
  box-sizing: border-box;
  font-family: 'Sarabun', sans-serif;
}

.form-group textarea {
  min-height: 80px;
  resize: vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #ff8c42;
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-group label {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
  margin: 0;
}

/* Image Preview */
.preview-container {
  position: relative;
  display: inline-block;
  margin-bottom: 12px;
}

.image-preview {
  max-width: 200px;
  max-height: 200px;
  border-radius: 8px;
  border: 2px solid #deb887;
}

.clear-img-btn {
  position: absolute;
  top: -10px;
  right: -10px;
  width: 28px;
  height: 28px;
  background-color: #c85a54;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.clear-img-btn:hover {
  background-color: #b44a3e;
  transform: scale(1.1);
}

/* Modal Actions */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 28px;
}

.modal-actions button {
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-btn {
  background-color: #f5e6d3;
  color: #8b4513;
}

.cancel-btn:hover {
  background-color: #ffe4b5;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header {
    padding: 20px 24px;
  }

  .page-title {
    font-size: 24px;
  }

  .content-card {
    margin: 24px 16px;
  }

  .tab-content {
    padding: 24px 20px;
  }

  .tab-btn {
    padding: 12px 16px;
    font-size: 14px;
  }

  .panel-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .add-btn {
    width: 100%;
  }

  .settings-grid {
    grid-template-columns: 1fr;
  }

  .filter-bar {
    gap: 8px;
  }

  .filter-btn {
    font-size: 13px;
    padding: 6px 16px;
  }

  .data-table thead th,
  .data-table tbody td {
    padding: 10px 8px;
    font-size: 14px;
  }

  .modal-content-custom {
    padding: 24px;
    margin: 16px;
  }
}
</style>
