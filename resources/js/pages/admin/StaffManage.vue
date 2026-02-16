<template>
  <div class="staff-manage-container">
    <!-- Header Section -->
    <div class="page-header">
      <button class="back-button" @click="$router.push('/admin/dashboard')">
        <ArrowLeft :size="24" />
      </button>
      <div class="header-content">
        <h1 class="page-title">จัดการพนักงาน</h1>
        <p class="page-subtitle">Staff Management</p>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card">
      <!-- Section Header with Add Button -->
      <div class="section-header">
        <h2 class="section-title">รายชื่อพนักงาน</h2>
        <button class="add-staff-button" @click="openModal()">
          <Plus :size="18" /> เพิ่มพนักงานใหม่
        </button>
      </div>

      <!-- Staff Table -->
      <div class="table-container">
        <table id="staffTable" class="staff-table" style="width:100%">
          <thead>
            <tr>
              <th>ชื่อ-นามสกุล</th>
              <th>ตำแหน่ง</th>
              <th>อีเมล</th>
              <th>PIN</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.role }}</td>
              <td>{{ user.email }}</td>
              <td><span v-if="user.has_pin || user.pin" title="PIN is hidden for security">******</span><span v-else>-</span></td>
              <td class="action-cell">
                <button @click="openModal(user)" class="action-btn edit-btn" title="แก้ไข">
                  <Pencil :size="16" />
                </button>
                <button @click="deleteUser(user.id)" class="action-btn delete-btn" title="ลบ">
                  <Trash2 :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content-custom">
        <h3 class="modal-title">{{ isEdit ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่' }}</h3>
        
        <div class="form-group">
          <label>ชื่อ-นามสกุล</label>
          <input v-model="form.name" placeholder="กรอกชื่อ-นามสกุล" />
        </div>
        
        <div class="form-group">
          <label>Email</label>
          <input type="email" v-model="form.email" placeholder="กรอกอีเมล" />
        </div>
        
        <div class="form-group">
          <label>รหัสผ่าน</label>
          <input v-model="form.password" type="password" :placeholder="isEdit ? 'เว้นว่างถ้าไม่ต้องการเปลี่ยน' : 'กรอกรหัสผ่าน'" />
        </div>
        
        <div class="form-group">
          <label>ตำแหน่ง</label>
          <select v-model="form.role">
            <option value="STAFF">Staff (พนักงานทั่วไป)</option>
            <option value="ADMIN">Admin (ผู้ดูแลระบบ)</option>
          </select>
        </div>

        <div class="modal-actions">
          <button @click="showModal = false" class="cancel-btn">ยกเลิก</button>
          <button @click="saveUser" class="save-btn">บันทึก</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, nextTick, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { 
  ArrowLeft, 
  Plus, 
  Pencil, 
  Trash2 
} from 'lucide-vue-next';

export default {
  components: {
    ArrowLeft,
    Plus,
    Pencil,
    Trash2
  },
  setup() {
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
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
                },
                pageLength: 10,
                order: [[0, 'asc']]
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

    const saveUser = async () => {
        const token = localStorage.getItem('token');
        const headers = { Authorization: `Bearer ${token}` };

        try {
            if (isEdit.value) {
                await axios.put(`/api/users/${form.value.id}`, form.value, { headers });
                alert('อัปเดตข้อมูลสำเร็จ');
            } else {
                const res = await axios.post('/api/users', form.value, { headers });
                if (res.data.pin) {
                    alert('สร้างพนักงานสำเร็จ! PIN คือ: ' + res.data.pin);
                } else {
                    alert('สร้างพนักงานสำเร็จ');
                }
            }
            showModal.value = false;
            fetchUsers();
        } catch (error) {
            alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
        }
    };

    const deleteUser = async (id) => {
        if (!confirm('ยืนยันที่จะลบพนักงานคนนี้?')) return;
        const token = localStorage.getItem('token');
        await axios.delete(`/api/users/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        fetchUsers();
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

    return { users, showModal, isEdit, form, openModal, saveUser, deleteUser };
  }
};
</script>

<style scoped>
/* Staff Management Container */
.staff-manage-container {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: #000000;
}

/* Page Header - Brown Section */
.page-header {
  background-color: var(--color-primary);
  padding: 20px 40px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-height: 100px;
  box-sizing: border-box;
}

.back-button {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.2);
  border: none;
  color: #ffffff;
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
  color: var(--color-highlight-light);
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
}

/* Main ัContent Card */
.content-card {
  max-width: 1200px;
  margin: 40px auto;
  background-color: #ffffff;
  border: 1px solid #eee;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

/* Section Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.section-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-primary);
  margin: 0;
  padding-bottom: 8px;
  border-bottom: 3px solid var(--color-action);
  display: inline-block;
}

.add-staff-button {
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  color: #ffffff;
  background: var(--color-action);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.add-staff-button:hover {
  background-color: var(--color-action-hover);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.3);
}

/* Table Container */
.table-container {
  overflow-x: auto;
  color: #000000;
}

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

/* Action Cell */
.action-cell {
  display: flex;
  gap: 10px;
  align-items: center;
}

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

.edit-btn:hover { color: #1976D2; border-color: #1976D2; background-color: #E3F2FD; }
.delete-btn:hover { color: #D32F2F; border-color: #D32F2F; background-color: #FFEBEE; }

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
  padding: 35px;
  border-radius: 16px;
  width: 90%;
  max-width: 480px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  color: #000000;
}

.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-primary);
  margin: 0 0 25px 0;
}

/* Form Group */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #444444;
  margin-bottom: 8px;
}

.form-group input,
.form-group select {
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

.form-group input::placeholder {
  color: #999999;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--color-action);
  box-shadow: 0 0 0 3px rgba(76, 175, 142, 0.1);
}

/* Modal Actions */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 30px;
}

.modal-actions button {
  padding: 12px 28px;
  font-size: 15px;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-btn {
  background-color: #f5f5f5;
  color: #666666;
}

.cancel-btn:hover { background-color: #eeeeee; }

.save-btn {
  background: var(--color-action);
  color: #ffffff;
}

.save-btn:hover {
  background-color: var(--color-action-hover);
  transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header { padding: 20px 24px; }
  .page-title { font-size: 24px; }
  .content-card { margin: 24px 16px; padding: 24px 20px; }
  .section-header { flex-direction: column; align-items: flex-start; gap: 16px; }
  .add-staff-button { width: 100%; }
}
</style>