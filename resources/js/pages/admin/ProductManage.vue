<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] pb-12 font-[Sarabun,sans-serif] text-black">
    <AdminHeader title="จัดการสินค้า & ค่าบริการ" subtitle="Product & Service Management" backRoute="/admin/dashboard" />

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
                <input type="number" v-model="hourlyRate" class="form-field">
              </div>
              <div>
                <label class="block text-sm font-semibold mb-2 text-[#333]">ราคาเหมาวัน Day Pass (บาท):</label>
                <input type="number" v-model="dayPassRate" class="form-field">
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
          <div class="table-responsive-wrapper text-black relative min-h-[150px]">
            <table class="table-theme">
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
                  <td><span :class="['status-badge', table.is_available ? 'available' : 'busy']">{{ table.is_available ? 'ว่าง' : 'ไม่ว่าง' }}</span></td>
                  <td><span :class="['status-badge', table.is_active ? 'available' : 'inactive']">{{ table.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span></td>
                  <td class="flex gap-2">
                    <button @click="editTable(table)" class="action-btn hover:text-[#1976D2] hover:border-[#1976D2] hover:bg-[#E3F2FD]" :disabled="!table.is_available"><Pencil :size="14" /></button>
                    <button @click="deleteTable(table.id)" class="action-btn hover:text-[#D32F2F] hover:border-[#D32F2F] hover:bg-[#FFEBEE]" :disabled="!table.is_available"><Trash2 :size="14" /></button>
                  </td>
                </tr>
                <tr v-if="tables.length === 0 && !globalLoading"><td colspan="5" class="text-center py-4">ไม่พบข้อมูลโต๊ะ</td></tr>
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
          <div class="flex gap-2.5 mb-5 flex-wrap">
            <button v-for="cat in [{id: 'all', name: 'ทั้งหมด'}, ...categories]" :key="cat.id" :class="['filter-btn', { active: selectedCategory === cat.id }]" @click="changeCategory(cat.id)">{{ cat.name }}</button>
          </div>

          <div class="table-responsive-wrapper text-black relative min-h-[300px]">
            <table id="productTable" class="table-theme display" style="width:100%">
              <thead>
                <tr><th>รูป</th><th>หมวดหมู่</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก</th><th>สถานะ</th><th>จัดการ</th></tr>
              </thead>
              <tbody>
                <template v-if="isTableLoading && filteredProducts.length === 0">
                  <tr v-for="i in 5" :key="i" class="skeleton-row">
                    <td><div></div></td>
                    <td><div></div></td>
                    <td><div></div></td>
                    <td><div></div></td>
                    <td><div></div></td>
                    <td><div></div></td>
                    <td><div></div></td>
                  </tr>
                </template>
                <tr v-else v-for="product in filteredProducts" :key="product.id">
                  <td>
                    <img v-if="product.image_url" :src="product.image_url" class="w-10 h-10 rounded-md object-cover">
                    <div v-else class="w-10 h-10 bg-[#eee] rounded-md flex items-center justify-center text-[#999]"><ImageOff :size="18" /></div>
                  </td>
                  <td class="text-[#666]">{{ product.category?.name }}</td>
                  <td class="font-bold">{{ product.name }}</td>
                  <td class="text-[var(--color-action)] font-bold">{{ product.price }} ฿</td>
                  <td>{{ product.stock_qty }}</td>
                  <td><span :class="['status-badge', product.is_active ? 'available' : 'inactive']">{{ product.is_active ? 'เปิดให้บริการ' : 'ปิดให้บริการ' }}</span></td>
                  <td class="flex gap-2">
                    <button @click="editProduct(product)" class="action-btn hover:text-[#1976D2] hover:border-[#1976D2] hover:bg-[#E3F2FD]"><Pencil :size="14" /></button>
                    <button @click="deleteProduct(product.id)" class="action-btn hover:text-[#D32F2F] hover:border-[#D32F2F] hover:bg-[#FFEBEE]"><Trash2 :size="14" /></button>
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
        <div class="w-12 h-12 border-4 border-[var(--color-action)] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <p class="font-bold text-[var(--color-primary)] text-lg m-0">กำลังบันทึกข้อมูล...</p>
      </div>
    </div>

    <BaseModal v-model="showModal" :title="editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'" size="lg">
      <div class="form-group">
        <label class="block text-sm font-semibold mb-2">รูปภาพสินค้า:</label>
        <div class="image-upload-wrapper">
          <div v-if="imagePreview || form.image_url" class="relative inline-block w-full">
            <img :src="imagePreview || form.image_url" class="w-full max-h-[250px] object-contain rounded-lg bg-white">
            <button @click="clearImage" class="absolute top-2.5 right-2.5 bg-[#ff4444] text-white border-none rounded-full w-7.5 h-7.5 cursor-pointer flex items-center justify-center font-bold">×</button>
          </div>
          <div v-else class="py-10 px-5 cursor-pointer flex flex-col items-center gap-2.5 text-[#666] hover:bg-[#f0f0f0]" @click="fileInput.click()">
            <ImageOff :size="48" />
            <span>คลิกเพื่อเลือกรูปภาพ</span>
          </div>
          <input type="file" @change="onFileChange" accept="image/*" ref="fileInput" class="hidden">
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div><label class="block text-sm font-semibold mb-2">หมวดหมู่:</label><select v-model="form.category_id" class="form-field"><option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option></select></div>
        <div><label class="block text-sm font-semibold mb-2">ชื่อสินค้า:</label><input type="text" v-model="form.name" class="form-field"></div>
        <div><label class="block text-sm font-semibold mb-2">ราคา (บาท):</label><input type="number" v-model="form.price" class="form-field"></div>
        <div><label class="block text-sm font-semibold mb-2">จำนวนสต็อก:</label><input type="number" v-model="form.stock_qty" class="form-field"></div>
      </div>
      <div class="mt-4"><label class="block text-sm font-semibold mb-2">รายละเอียด:</label><textarea v-model="form.description" class="form-field min-h-[80px]"></textarea></div>
      <div class="mt-4"><label class="flex items-center gap-2.5 cursor-pointer select-none"><input type="checkbox" v-model="form.is_active" class="w-5 h-5 cursor-pointer"><span class="text-sm font-semibold">เปิดใช้งานสินค้านี้</span></label></div>
      <template #footer>
        <button @click="showModal = false" class="py-2.5 px-5 bg-[#eee] border-none rounded-lg cursor-pointer font-semibold">ยกเลิก</button>
        <button @click="saveProduct" class="py-2.5 px-6 bg-[var(--color-action)] text-white border-none rounded-lg font-semibold">บันทึก</button>
      </template>
    </BaseModal>

    <BaseModal v-model="showTableModal" :title="editingTableId ? 'แก้ไขโต๊ะ' : 'เพิ่มโต๊ะใหม่'">
      <div><label class="block text-sm font-semibold mb-2">ชื่อโต๊ะ:</label><input type="text" v-model="tableForm.name" placeholder="เช่น T1, A10" class="form-field"></div>
      <div class="mt-4"><label class="block text-sm font-semibold mb-2">จำนวนที่นั่ง:</label><input type="number" v-model="tableForm.seat_count" min="1" class="form-field"></div>
      <div class="mt-4"><label class="flex items-center gap-2.5 cursor-pointer select-none"><input type="checkbox" v-model="tableForm.is_active" class="w-5 h-5 cursor-pointer"><span class="text-sm font-semibold">เปิดใช้งานโต๊ะนี้</span></label></div>
      <template #footer>
        <button @click="showTableModal = false" class="py-2.5 px-5 bg-[#eee] border-none rounded-lg cursor-pointer font-semibold">ยกเลิก</button>
        <button @click="saveTable" class="py-2.5 px-6 bg-[var(--color-action)] text-white border-none rounded-lg font-semibold">บันทึก</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { useDataTable } from '../../composables/useDataTable';
import AdminHeader from '../../components/AdminHeader.vue';
import BaseModal from '../../components/BaseModal.vue';
import { Timer, LayoutGrid, Package, Save, Plus, Pencil, Trash2, ImageOff } from 'lucide-vue-next';

const { success, error, confirm } = useAlert();
const { refreshDataTable, isTableLoading } = useDataTable({ tableId: '#productTable', config: { order: [[2, 'asc']] } });

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
const showModal = ref(false);
const editingId = ref(null);
const showTableModal = ref(false);
const editingTableId = ref(null);
const tableForm = ref({ name: '', seat_count: 4, is_active: true });
const imagePreview = ref(null);
const selectedFile = ref(null);
const fileInput = ref(null);
const form = ref({ name: '', category_id: '', price: 0, stock_qty: 0, description: '', is_active: true, image_url: null });

const fetchData = async () => {
    globalLoading.value = true;
    try {
        const token = localStorage.getItem('token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const [hr, dp, cat, prod, tbl] = await Promise.all([
            axios.get('/api/settings/hourly_rate', config),
            axios.get('/api/settings/day_pass_rate', config),
            axios.get('/api/categories', config),
            axios.get('/api/products?all=1', config),
            axios.get('/api/tables', config)
        ]);
        hourlyRate.value = hr.data.value;
        dayPassRate.value = dp.data.value;
        categories.value = cat.data.filter(c => c.type !== 'service');
        products.value = prod.data.filter(p => p.category?.type !== 'service');
        tables.value = tbl.data;
        refreshDataTable();
    } catch (err) { console.error(err); } finally { globalLoading.value = false; }
};

const saveRate = async () => {
    savingRate.value = true;
    try {
        await axios.post('/api/settings', { settings: { hourly_rate: hourlyRate.value, day_pass_rate: dayPassRate.value } }, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        success('บันทึกสำเร็จ');
    } catch (err) { error('ผิดพลาด', err.message); } finally { savingRate.value = false; }
};

const openTableModal = () => { editingTableId.value = null; tableForm.value = { name: '', seat_count: 4, is_active: true }; showTableModal.value = true; };
const editTable = (t) => { editingTableId.value = t.id; tableForm.value = { name: t.name, seat_count: t.seat_count, is_active: !!t.is_active }; showTableModal.value = true; };
const saveTable = async () => {
    savingTable.value = true;
    try {
        const config = { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } };
        if (editingTableId.value) await axios.put(`/api/tables/${editingTableId.value}`, tableForm.value, config);
        else await axios.post('/api/tables', tableForm.value, config);
        showTableModal.value = false;
        fetchData();
        success('บันทึกโต๊ะสำเร็จ');
    } catch (err) { error('ผิดพลาด', err.message); } finally { savingTable.value = false; }
};

const deleteTable = async (id) => {
    if (!await confirm('ยืนยันการลบ?')) return;
    try { await axios.delete(`/api/tables/${id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } }); fetchData(); success('ลบสำเร็จ'); }
    catch (err) { error('ลบไม่สำเร็จ', err.message); }
};

const filteredProducts = computed(() => selectedCategory.value === 'all' ? products.value : products.value.filter(p => String(p.category_id) === String(selectedCategory.value)));
const changeCategory = (id) => { selectedCategory.value = id; refreshDataTable(); };
const onFileChange = (e) => { const f = e.target.files[0]; if (!f) return; selectedFile.value = f; imagePreview.value = URL.createObjectURL(f); };
const clearImage = () => { selectedFile.value = null; imagePreview.value = null; form.value.image_url = null; };
const openAddModal = () => { editingId.value = null; imagePreview.value = null; selectedFile.value = null; form.value = { name: '', category_id: categories.value[0]?.id || '', price: 0, stock_qty: 0, description: '', is_active: true, image_url: null }; showModal.value = true; };
const editProduct = (p) => { editingId.value = p.id; imagePreview.value = null; selectedFile.value = null; form.value = { ...p }; showModal.value = true; };

const saveProduct = async () => {
    savingProduct.value = true;
    try {
        const formData = new FormData();
        Object.keys(form.value).forEach(k => { if (k !== 'image_url' && form.value[k] !== null) formData.append(k, form.value[k]); });
        if (selectedFile.value) formData.append('image', selectedFile.value);
        if (editingId.value) formData.append('_method', 'PUT');
        const url = editingId.value ? `/api/products/${editingId.value}` : '/api/products';
        await axios.post(url, formData, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, 'Content-Type': 'multipart/form-data' } });
        showModal.value = false;
        fetchData();
        success('บันทึกสำเร็จ');
    } catch (err) { error('ผิดพลาด', err.message); } finally { savingProduct.value = false; }
};

const deleteProduct = async (id) => {
    if (!await confirm('ลบสินค้าน้านี้?')) return;
    try { await axios.delete(`/api/products/${id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } }); fetchData(); success('ลบสำเร็จ'); }
    catch (err) { error('ผิดพลาด', err.message); }
};

const isSaving = computed(() => savingRate.value || savingProduct.value || savingTable.value);
onMounted(fetchData);
</script>

<style scoped>
.section-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); border: 1px solid #eee; }
.status-badge { padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }
.status-badge.available { background: #E8F5E9; color: #2E7D32; }
.status-badge.busy { background: #FFEBEE; color: #C62828; }
.status-badge.inactive { background: #F5F5F5; color: #757575; }
.filter-btn { padding: 6px 15px; border-radius: 15px; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 13px; }
.filter-btn.active { background: var(--color-action); color: white; border-color: var(--color-action); }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
.image-upload-wrapper { border: 2px dashed #ddd; border-radius: 12px; padding: 10px; background: #fafafa; text-align: center; }
:deep(.dataTables_wrapper) { color: #000000 !important; }
:deep(.dataTables_filter input), :deep(.dataTables_length select) { color: #000000 !important; background-color: #ffffff !important; border: 1px solid #ddd !important; padding: 5px !important; border-radius: 4px !important; }
:deep(.dataTables_info), :deep(.dataTables_paginate) { color: #000000 !important; margin-top: 15px !important; }
</style>
