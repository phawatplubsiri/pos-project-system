<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)] font-[Sarabun,-apple-system,BlinkMacSystemFont,'Segoe_UI',sans-serif] text-black">
    <!-- Header Section -->
    <div class="bg-[var(--color-primary)] py-5 px-10 flex items-center gap-5 shadow-[0_2px_8px_rgba(0,0,0,0.1)] min-h-[100px] box-border">
      <button class="w-10 h-10 rounded-full bg-white/20 border-none text-white cursor-pointer flex items-center justify-center transition-all duration-300 hover:bg-white/30 hover:scale-105" @click="$router.push('/admin/dashboard')">
        <ArrowLeft :size="24" />
      </button>
      <div class="flex-1">
        <h1 class="text-[28px] font-bold text-[var(--color-highlight-light)] m-0 mb-1">จัดการพนักงาน</h1>
        <p class="text-sm text-white/90 m-0">Staff Management</p>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="max-w-[1200px] mx-auto my-10 bg-white border border-[#eee] rounded-2xl p-8 shadow-[0_4px_20px_rgba(0,0,0,0.05)]">
      <!-- Section Header -->
      <div class="flex justify-between items-center mb-7.5">
        <h2 class="text-[22px] font-bold text-[var(--color-primary)] m-0 pb-2 border-b-3 border-[var(--color-action)] inline-block">รายชื่อพนักงาน</h2>
        <button class="py-3 px-6 text-[15px] font-semibold text-white bg-[var(--color-action)] border-none rounded-lg cursor-pointer transition-all duration-300 flex items-center gap-2 hover:bg-[var(--color-action-hover)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(76,175,142,0.3)]" @click="openModal()">
          <Plus :size="18" /> เพิ่มพนักงานใหม่
        </button>
      </div>

      <!-- Staff Table -->
      <div class="overflow-x-auto text-black">
        <table id="staffTable" class="staff-table" style="width:100%">
          <thead>
            <tr>
              <th>ชื่อ-นามสกุล</th>
              <th>ตำแหน่ง</th>
              <th>อีเมล</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td style="text-transform: lowercase;">{{ user.role }}</td>
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

    <!-- Modal for Add/Edit -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex justify-center items-center z-[1000] backdrop-blur-[4px]" @click.self="showModal = false">
      <div class="bg-white p-7 rounded-2xl w-2/5 max-w-[1000px] shadow-[0_10px_40px_rgba(0,0,0,0.12)] text-black relative">
        <button class="absolute top-[18px] right-5 w-9 h-9 rounded-full border-none bg-transparent text-2xl leading-none cursor-pointer text-[var(--color-text-secondary)]" @click="showModal = false">&times;</button>
        <h3 class="text-[22px] font-bold text-[var(--color-primary)] m-0 mb-6">{{ isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่' }}</h3>
        
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

        <div class="flex justify-end gap-3 mt-7.5">
          <button @click="showModal = false" class="py-3 px-7 text-[15px] font-semibold border-none rounded-lg cursor-pointer transition-all duration-300 bg-[#f5f5f5] text-[#666] hover:bg-[#eee]">ยกเลิก</button>
          <button @click="saveUser" class="py-3 px-7 text-[15px] font-semibold border-none rounded-lg cursor-pointer transition-all duration-300 bg-[var(--color-action)] text-white hover:bg-[var(--color-action-hover)] hover:-translate-y-0.5">บันทึก</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, nextTick, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { useAlert } from '../../composables/useAlert';
import { 
  ArrowLeft, 
  Plus, 
  Pencil, 
  Trash2,
  Key
} from 'lucide-vue-next';

export default {
  components: {
    ArrowLeft,
    Plus,
    Pencil,
    Trash2,
    Key
  },
  setup() {
    const { success, error, confirm: swalConfirm, loading, close: swalClose, showPin, Toast } = useAlert();
    const users = ref([]);
    const showModal = ref(false);
    const isEdit = ref(false);
    const form = ref({ id: null, name: '', email: '', password: '', role: 'staff' });
    let dataTable = null;

    const initDataTable = () => {
        if ($.fn.DataTable.isDataTable('#staffTable')) {
            $('#staffTable').DataTable().destroy();
        }
        nextTick(() => {
            dataTable = $('#staffTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
                },
                pageLength: 10,
                order: [[0, 'asc']],
                columnDefs: [
                  { responsivePriority: 1, targets: 0 },
                  { responsivePriority: 2, targets: 2 },
                  { responsivePriority: 5, targets: 3 }
                ]
            });
        });
    };

    const fetchUsers = async () => {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/users', { headers: { Authorization: `Bearer ${token}` } });
        users.value = res.data;
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

    const regeneratePin = async (id) => {
      const ok = await swalConfirm('ยืนยันการรีเซ็ต PIN', 'คุณต้องการรีเซ็ต PIN ใหม่สำหรับพนักงานคนนี้ใช่หรือไม่?');
      if (!ok) return;

      const token = localStorage.getItem('token');
      try {
        loading('กำลังสร้าง PIN ใหม่...');
        const res = await axios.post(`/api/users/${id}/regenerate-pin`, {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        swalClose();
        await showPin('รีเซ็ต PIN สำเร็จ', res.data.pin);
      } catch (err) {
        swalClose();
        error('เกิดข้อผิดพลาด', (err.response?.data?.message || err.message));
      }
    };

    const saveUser = async () => {
        const token = localStorage.getItem('token');
        const headers = { Authorization: `Bearer ${token}` };

          try {
            loading('กำลังบันทึกข้อมูล...');
            if (isEdit.value) {
              await axios.put(`/api/users/${form.value.id}`, form.value, { headers });
              swalClose();
              success('อัปเดตข้อมูลสำเร็จ');
            } else {
              const res = await axios.post('/api/users', form.value, { headers });
              swalClose();
              if (res.data.pin) {
                await showPin('สร้างพนักงานสำเร็จ', res.data.pin);
              } else {
                success('สร้างพนักงานสำเร็จ');
              }
            }
            showModal.value = false;
            fetchUsers();
          } catch (err) {
            swalClose();
            error('เกิดข้อผิดพลาด', (err.response?.data?.message || err.message));
          }
    };

    const deleteUser = async (id) => {
      const ok = await swalConfirm('ยืนยันการลบ', 'ยืนยันที่จะลบพนักงานคนนี้?');
      if (!ok) return;
      const token = localStorage.getItem('token');
      try {
        loading('กำลังลบ...');
        await axios.delete(`/api/users/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        swalClose();
        success('ลบพนักงานเรียบร้อย');
        fetchUsers();
      } catch (err) {
        swalClose();
        error('ล้มเหลว', (err.response?.data?.message || err.message));
      }
    };

    onMounted(fetchUsers);

    onBeforeUnmount(() => {
        if (dataTable) {
            dataTable.destroy();
        }
    });

    watch(users, () => {
        initDataTable();
    });

    return { users, showModal, isEdit, form, openModal, saveUser, deleteUser, regeneratePin };
  }
};
</script>

<style scoped>
/* Staff Table */
.staff-table {
  width: 100% !important;
  border-collapse: collapse;
}

.staff-table thead th {
  padding: 16px 12px;
  text-align: left;
  font-size: 14px;
  font-weight: 700;
  color: #333333;
  border-bottom: 2px solid #f0f0f0;
  background-color: #fafafa;
}

.staff-table tbody tr {
  border-bottom: 1px solid #f5f5f5;
  transition: background-color 0.2s ease;
}

.staff-table tbody tr:hover {
  background-color: #fffaf5;
}

.staff-table tbody td {
  padding: 16px 12px;
  font-size: 15px;
  color: #000000;
}

/* Action Button */
.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #ddd;
  background-color: #ffffff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.action-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Form Field */
.form-field {
  width: 100%;
  padding: 12px 16px;
  font-size: 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #ffffff;
  color: #000000 !important;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-field::placeholder {
  color: #999999;
}

.form-field:focus {
  outline: none;
  border-color: var(--color-action);
  box-shadow: 0 0 0 3px rgba(76, 175, 142, 0.1);
}

/* DataTable Overrides */
:deep(.dataTables_wrapper) { color: #000000 !important; }
:deep(.dataTables_filter input) {
  border: 1px solid #ddd !important;
  border-radius: 6px !important;
  padding: 6px 12px !important;
  color: #000000 !important;
  margin-bottom: 10px !important;
}
:deep(.dataTables_length select) {
  border: 1px solid #ddd !important;
  border-radius: 6px !important;
  padding: 4px !important;
  color: #000000 !important;
}
:deep(.dataTables_info), :deep(.dataTables_paginate) {
  color: #000000 !important;
  margin-top: 20px !important;
}

@media (max-width: 768px) {
  .flex.justify-between { flex-direction: column; align-items: flex-start; gap: 16px; }
}
</style>