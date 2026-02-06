<template>
  <div class="container">
    <header>
        <button @click="$router.push('/admin/dashboard')">⬅ กลับ Dashboard</button>
        <h2>👥 จัดการพนักงาน</h2>
        <button class="add-btn" @click="openModal()">+ เพิ่มพนักงาน</button>
    </header>

    <div class="table-responsive">
        <table id="staffTable" class="display staff-table" style="width:100%">
            <thead>
                <tr>
                    <th>ชื่อ</th>
                    <th>Email</th> 
                    <th>ตำแหน่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td> 
                    <td>
                        <span :class="['badge', user.role.toLowerCase()]">{{ user.role }}</span>
                    </td>
                    <td>
                        <button @click="openModal(user)" class="edit-btn">แก้ไข</button>
                        <button @click="deleteUser(user.id)" class="del-btn">ลบ</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal-overlay">
        <div class="modal">
            <h3>{{ isEdit ? 'แก้ไขข้อมูล' : 'เพิ่มพนักงานใหม่' }}</h3>
            
            <input v-model="form.name" placeholder="ชื่อ-นามสกุล" />
            
            <input type="email" v-model="form.email" placeholder="Email (สำหรับเข้าระบบ)" />
            
            <input v-model="form.password" type="password" placeholder="รหัสผ่าน (เว้นว่างถ้าไม่แก้)" />
            
            <select v-model="form.role">
                <option value="STAFF">Staff (พนักงานทั่วไป)</option>
                <option value="ADMIN">Admin (ผู้ดูแลระบบ)</option>
            </select>

            <div class="modal-actions">
                <button @click="showModal = false">ยกเลิก</button>
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
.container { padding: 30px; font-family: 'Sarabun', sans-serif; }
header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
header h2 { margin: 0; }

.table-responsive { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.staff-table { width: 100% !important; border-collapse: collapse; }
.staff-table th { background: #f8f9fa; }

/* Badge */
.badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; color: white; }
.badge.admin { background: #dc3545; }
.badge.staff { background: #28a745; }

/* Buttons */
.add-btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
.edit-btn { background: #ffc107; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; margin-right: 5px; }
.del-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal { background: white; padding: 30px; border-radius: 10px; width: 400px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.modal h3 { margin-top: 0; margin-bottom: 10px; }
.modal input, .modal select { padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.modal-actions button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.save-btn { background: #28a745; color: white; font-weight: bold; }
</style>