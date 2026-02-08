<template>
  <div class="staff-manage-container">
    <!-- Header Section -->
    <div class="page-header">
      <button class="back-button" @click="$router.push('/admin/dashboard')">
        ←
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
          + เพิ่มพนักงาน
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
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.role }}</td>
              <td>{{ user.email }}</td>
              <td class="action-cell">
                <button @click="openModal(user)" class="action-btn edit-btn" title="แก้ไข">
                  ✏️
                </button>
                <button @click="deleteUser(user.id)" class="action-btn delete-btn" title="ลบ">
                  🗑️
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

export default {
  setup() {
    const users = ref([]);
    const showModal = ref(false);
    const isEdit = ref(false);
    // ✅ เปลี่ยนจาก username เป็น email ใน form เริ่มต้น
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

    // โหลดข้อมูลทั้งหมด
    const fetchUsers = async () => {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/users', { headers: { Authorization: `Bearer ${token}` } });
        users.value = res.data;
    };

    // เปิด Modal
    const openModal = (user = null) => {
        if (user) {
            isEdit.value = true;
            form.value = { ...user, password: '' }; // ไม่เอารหัสเดิมมาโชว์
        } else {
            isEdit.value = false;
            // ✅ รีเซ็ตค่า form เป็น email
            form.value = { name: '', email: '', password: '', role: 'STAFF' };
        }
        showModal.value = true;
    };

    // บันทึก (แยกเคส Create / Update)
    const saveUser = async () => {
        const token = localStorage.getItem('token');
        const headers = { Authorization: `Bearer ${token}` };

        try {
            if (isEdit.value) {
                // Update
                await axios.put(`/api/users/${form.value.id}`, form.value, { headers });
            } else {
                // Create
                await axios.post('/api/users', form.value, { headers });
            }
            showModal.value = false;
            fetchUsers(); // โหลดใหม่
            alert('บันทึกสำเร็จ');
        } catch (error) {
            // แสดง Error จาก Backend
            alert('เกิดข้อผิดพลาด: ' + (error.response?.data?.message || error.message));
        }
    };

    // ลบ
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
  max-width: 1200px;
  margin: 40px auto;
  background-color: #ffffff;
  border: 2px solid #deb887;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.08);
}

/* Section Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #8b4513; /* Brown */
  margin: 0;
  padding-bottom: 8px;
  border-bottom: 3px solid #ff8c42; /* Orange underline */
  display: inline-block;
}

.add-staff-button {
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

.add-staff-button:hover {
  background: linear-gradient(135deg, #ff7a29, #e67e22);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
}

/* Table Container */
.table-container {
  overflow-x: auto;
}

/* Staff Table */
.staff-table {
  width: 100% !important;
  border-collapse: separate;
  border-spacing: 0;
}

.staff-table thead tr {
  background-color: transparent;
}

.staff-table thead th {
  padding: 16px 12px;
  text-align: left;
  font-size: 14px;
  font-weight: 700;
  color: #8b4513; /* Brown */
  border-bottom: 2px solid #f5e6d3;
  background-color: transparent;
}

.staff-table tbody tr {
  border-bottom: 1px solid #f5e6d3;
  transition: background-color 0.2s ease;
}

.staff-table tbody tr:hover {
  background-color: #fffef9;
}

.staff-table tbody td {
  padding: 16px 12px;
  font-size: 15px;
  color: #654321; /* Dark brown */
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

.action-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(139, 69, 19, 0.15);
}

.edit-btn:hover {
  background-color: #ffe4b5;
  border-color: #ff8c42;
}

.delete-btn:hover {
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
  max-width: 480px;
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
.form-group select {
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

.form-group input::placeholder {
  color: #c19a6b;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #ff8c42;
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
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

.save-btn {
  background: linear-gradient(135deg, #ff8c42, #ff7a29);
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
}

.save-btn:hover {
  background: linear-gradient(135deg, #ff7a29, #e67e22);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 140, 66, 0.4);
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
    padding: 24px 20px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .add-staff-button {
    width: 100%;
  }

  .staff-table thead th,
  .staff-table tbody td {
    padding: 12px 8px;
    font-size: 14px;
  }

  .modal-content-custom {
    padding: 24px;
    margin: 16px;
  }
}
</style>
