<template>
  <div class="container">
    <header class="header">
      <button @click="$router.push('/admin/dashboard')" class="back-btn">⬅ กลับไปแดชบอร์ด</button>
      <h1>📦 จัดการสินค้า & ค่าบริการ</h1>
    </header>

    <div class="settings-section">
      <h3>⏱️ ตั้งค่าค่าบริการ</h3>
      <div class="settings-grid">
        <div class="setting-card">
          <label>ราคาต่อคน ต่อชั่วโมง (บาท):</label>
          <input type="number" v-model="hourlyRate" class="rate-input">
        </div>
        <div class="setting-card">
          <label>ราคาเหมาวัน Day Pass (บาท):</label>
          <input type="number" v-model="dayPassRate" class="rate-input">
        </div>
      </div>
      <button @click="saveRate" class="save-btn" :disabled="savingRate">
        {{ savingRate ? 'กำลังบันทึก...' : '💾 บันทึกการตั้งค่า' }}
      </button>
    </div>

    <hr>

    <div class="table-section">
      <div class="section-header">
        <h3>🪑 จัดการโต๊ะ</h3>
        <button @click="openTableModal" class="add-btn">+ เพิ่มโต๊ะใหม่</button>
      </div>

      <div class="table-responsive mb-4">
        <table class="display product-table" style="width:100%">
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
                <span :class="['status-badge', table.status === 'available' ? 'active' : 'inactive']">
                  {{ table.status === 'available' ? 'ว่าง' : 'ไม่ว่าง' }}
                </span>
              </td>
              <td>
                <button @click="editTable(table)" class="edit-btn">แก้ไข</button>
                <button @click="deleteTable(table.id)" class="del-btn" :disabled="table.status !== 'available'">ลบ</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <hr>

    <div class="product-section">
      <div class="section-header">
        <h3>🍔 รายการสินค้า (อาหาร, น้ำ, สินค้า)</h3>
        <button @click="openAddModal" class="add-btn">+ เพิ่มสินค้าใหม่</button>
      </div>

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

      <div class="table-responsive">
        <table id="productTable" class="display product-table" style="width:100%">
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
                <span :class="['status-badge', product.is_active ? 'active' : 'inactive']">
                  {{ product.is_active ? 'เปิดใช้งาน' : 'ปิด' }}
                </span>
              </td>
              <td>
                <button @click="editProduct(product)" class="edit-btn">แก้ไข</button>
                <button @click="deleteProduct(product.id)" class="del-btn">ลบ</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขสินค้า -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}</h3>
        
        <div class="form-group">
          <label>รูปภาพสินค้า:</label>
          <div v-if="imagePreview || form.image_url" class="preview-container">
            <img :src="imagePreview || form.image_url" class="image-preview">
            <button @click="clearImage" class="clear-img-btn">เอาออก</button>
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

        <div class="form-group checkbox">
          <label>
            <input type="checkbox" v-model="form.is_active"> เปิดใช้งาน
          </label>
        </div>

        <div class="modal-actions">
          <button @click="showModal = false" class="cancel-btn">ยกเลิก</button>
          <button @click="saveProduct" class="confirm-btn">✅ บันทึก</button>
        </div>
      </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขโต๊ะ -->
    <div v-if="showTableModal" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ editingTableId ? 'แก้ไขโต๊ะ' : 'เพิ่มโต๊ะใหม่' }}</h3>
        
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
          <button @click="saveTable" class="confirm-btn">✅ บันทึก</button>
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

    // Re-init DataTable when filtered products change
    watch(filteredProducts, () => {
        initDataTable();
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
.container { padding: 30px; font-family: 'Sarabun', sans-serif; }
.header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
.back-btn { padding: 8px 15px; cursor: pointer; border: none; background: #eee; border-radius: 5px; }

.settings-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 15px 0; }
.setting-card { display: flex; flex-direction: column; gap: 8px; }
.rate-input { padding: 10px; width: 100%; font-size: 1.1rem; border-radius: 5px; border: 1px solid #ccc; }
.save-btn { background: #007bff; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.add-btn { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }

.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn { padding: 5px 15px; border: 1px solid #ddd; background: white; border-radius: 20px; cursor: pointer; }
.filter-btn.active { background: #007bff; color: white; border-color: #007bff; }

.table-responsive { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
.mb-4 { margin-bottom: 1.5rem; }
.product-table { width: 100% !important; border-collapse: collapse; }
.product-table th { background: #f8f9fa; }

.status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; }
.status-badge.active { background: #e8f5e9; color: #2e7d32; }
.status-badge.inactive { background: #ffebee; color: #c62828; }

.edit-btn { background: #ffc107; color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; margin-right: 5px; }
.del-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }

/* Image Styles */
.product-img-mini {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 4px;
}
.no-img-mini {
  font-size: 0.7rem;
  color: #999;
}
.preview-container {
  position: relative;
  display: inline-block;
  margin-bottom: 10px;
}
.image-preview {
  max-width: 150px;
  max-height: 150px;
  border-radius: 8px;
  border: 1px solid #ddd;
}
.clear-img-btn {
  position: absolute;
  top: -10px;
  right: -10px;
  background: red;
  color: white;
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  cursor: pointer;
  font-weight: bold;
}

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 30px; border-radius: 10px; width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
.form-group label { font-weight: bold; }
.form-group input, .form-group select, .form-group textarea { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
.checkbox { flex-direction: row; align-items: center; gap: 10px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.cancel-btn { padding: 10px 20px; border: none; background: #ccc; border-radius: 5px; cursor: pointer; }
.confirm-btn { padding: 10px 20px; border: none; background: #28a745; color: white; border-radius: 5px; cursor: pointer; }
</style>
