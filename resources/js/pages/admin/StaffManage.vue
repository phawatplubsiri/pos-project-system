<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] font-[Sarabun,-apple-system,BlinkMacSystemFont,'Segoe_UI',sans-serif] text-black">
    <AdminHeader title="จัดการพนักงาน" subtitle="Staff Management" backRoute="/admin/dashboard" />

    <div class="max-w-[1200px] mx-auto my-10 bg-white border border-[#eee] rounded-2xl p-8 shadow-[0_4px_20px_rgba(0,0,0,0.05)]">
      <div class="flex justify-between items-center mb-7.5">
        <h2 class="section-title">รายชื่อพนักงาน</h2>
        <button class="py-3 px-6 text-[15px] font-semibold text-white bg-[var(--color-action)] border-none rounded-lg cursor-pointer transition-all duration-300 flex items-center gap-2 hover:bg-[var(--color-action-hover)] hover:-translate-y-0.5" @click="openModal()">
          <Plus :size="18" /> เพิ่มพนักงานใหม่
        </button>
      </div>

      <div class="table-responsive-wrapper text-black relative min-h-[200px]">
        <div v-if="isTableLoading" class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center backdrop-blur-[1px]">
           <div class="flex flex-col items-center gap-2">
             <div class="w-8 h-8 border-4 border-[var(--color-action)] border-t-transparent rounded-full animate-spin"></div>
             <span class="text-xs font-bold text-[var(--color-action)]">กำลังโหลดตาราง...</span>
           </div>
        </div>

        <table id="staffTable" class="table-theme" style="width:100%">
          <thead>
            <tr>
              <th>ชื่อ</th>
              <th>ตำแหน่ง</th>
              <th>Email</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="isTableLoading && users.length === 0">
              <tr v-for="i in 5" :key="i" class="skeleton-row">
                <td><div></div></td>
                <td><div></div></td>
                <td><div></div></td>
                <td><div></div></td>
              </tr>
            </template>
            <tr v-else v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td class="lowercase">{{ user.role }}</td>
              <td>{{ user.email }}</td>
              <td class="flex gap-2.5 items-center">
                <button @click="regeneratePin(user.id)" class="action-btn hover:text-[#F57C00] hover:border-[#F57C00] hover:bg-[#FFF3E0]" title="รีเซ็ต PIN">
                  <Key :size="16" />
                </button>
                <button @click="openModal(user)" class="action-btn hover:text-[#1976D2] hover:border-[#1976D2] hover:bg-[#E3F2FD]" title="แก้ไข">
                  <Pencil :size="16" />
                </button>
                <button @click="deleteUser(user.id)" class="action-btn hover:text-[#D32F2F] hover:border-[#D32F2F] hover:bg-[#FFEBEE]" title="ลบ">
                  <Trash2 :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BaseModal v-model="showModal" :title="isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่'" size="lg">
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-sm font-semibold text-[#444] mb-2">ชื่อ-นามสกุล</label>
          <input v-model="form.name" placeholder="กรอกชื่อ-นามสกุล" class="form-field" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-[#444] mb-2">Email</label>
          <input type="email" v-model="form.email" placeholder="กรอกอีเมล" class="form-field" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-[#444] mb-2">รหัสผ่าน</label>
          <input v-model="form.password" type="password" :placeholder="isEdit ? 'เว้นว่างถ้าไม่ต้องการเปลี่ยน' : 'กรอกรหัสผ่าน'" class="form-field" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-[#444] mb-2">ตำแหน่ง</label>
          <select v-model="form.role" class="form-field">
            <option value="staff">Staff (พนักงานทั่วไป)</option>
            <option value="admin">Admin (ผู้ดูแลระบบ)</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button @click="showModal = false" class="py-3 px-7 text-[15px] font-semibold border-none rounded-lg cursor-pointer bg-[#f5f5f5] text-[#666] hover:bg-[#eee]">ยกเลิก</button>
        <button @click="saveUser" class="py-3 px-7 text-[15px] font-semibold border-none rounded-lg cursor-pointer bg-[var(--color-action)] text-white hover:bg-[var(--color-action-hover)]">บันทึก</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { useDataTable } from '../../composables/useDataTable';
import AdminHeader from '../../components/AdminHeader.vue';
import BaseModal from '../../components/BaseModal.vue';
import { Plus, Pencil, Trash2, Key } from 'lucide-vue-next';

const { success, error, confirm: swalConfirm, loading, close: swalClose, showPin } = useAlert();
const { refreshDataTable, isTableLoading } = useDataTable({ 
  tableId: '#staffTable',
  config: { order: [[0, 'asc']] }
});

const users = ref([]);
const showModal = ref(false);
const isEdit = ref(false);
const form = ref({ id: null, name: '', email: '', password: '', role: 'staff' });

const fetchUsers = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/users', { headers: { Authorization: `Bearer ${token}` } });
        users.value = res.data;
    } catch (err) {
        console.error('Fetch users error:', err);
    }
};

const openModal = (user = null) => {
    if (user) {
        isEdit.value = true;
        form.value = { ...user, password: '' };
    } else {
        isEdit.value = false;
        form.value = { name: '', email: '', password: '', role: 'staff' };
    }
    showModal.value = true;
};

const saveUser = async () => {
    const token = localStorage.getItem('token');
    const headers = { Authorization: `Bearer ${token}` };
    try {
        loading('กำลังบันทึกข้อมูล...');
        if (isEdit.value) {
            await axios.put(`/api/users/${form.value.id}`, form.value, { headers });
            success('อัปเดตข้อมูลสำเร็จ');
        } else {
            const res = await axios.post('/api/users', form.value, { headers });
            if (res.data.pin) await showPin('สร้างพนักงานสำเร็จ', res.data.pin);
            else success('สร้างพนักงานสำเร็จ');
        }
        showModal.value = false;
        fetchUsers();
    } catch (err) {
        swalClose();
        error('เกิดข้อผิดพลาด', (err.response?.data?.message || err.message));
    }
};

const deleteUser = async (id) => {
    if (!await swalConfirm('ยืนยันการลบ', 'ยืนยันที่จะลบพนักงานคนนี้?')) return;
    try {
        const token = localStorage.getItem('token');
        loading('กำลังลบ...');
        await axios.delete(`/api/users/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        success('ลบพนักงานเรียบร้อย');
        fetchUsers();
    } catch (err) {
        swalClose();
        error('ล้มเหลว', (err.response?.data?.message || err.message));
    }
};

const regeneratePin = async (id) => {
    if (!await swalConfirm('ยืนยันการรีเซ็ต PIN', 'ต้องการรีเซ็ต PIN ใหม่ใช่หรือไม่?')) return;
    try {
        const token = localStorage.getItem('token');
        loading('กำลังสร้าง PIN ใหม่...');
        const res = await axios.post(`/api/users/${id}/regenerate-pin`, {}, { headers: { Authorization: `Bearer ${token}` } });
        await showPin('รีเซ็ต PIN สำเร็จ', res.data.pin);
    } catch (err) {
        swalClose();
        error('เกิดข้อผิดพลาด', (err.response?.data?.message || err.message));
    }
};

onMounted(fetchUsers);
watch(users, () => refreshDataTable());
</script>

<style scoped>
:deep(.dataTables_wrapper) { color: #000000 !important; }
:deep(.dataTables_filter input) { border: 1px solid #ddd !important; border-radius: 6px !important; padding: 6px 12px !important; color: #000000 !important; margin-bottom: 10px !important; }
:deep(.dataTables_info), :deep(.dataTables_paginate) { color: #000000 !important; margin-top: 20px !important; }
@media (max-width: 768px) { .flex.justify-between { flex-direction: column; align-items: flex-start; gap: 16px; } }
</style>
