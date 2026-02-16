<template>
  <div class="report-container">
    <!-- Header Section -->
    <header class="page-header">
      <button class="back-button" @click="$router.push('/admin/dashboard')">
        <ArrowLeft :size="24" />
      </button>
      <div class="header-content">
        <h1 class="page-title">รายงานรายได้</h1>
        <p class="page-subtitle">Revenue Report</p>
      </div>
    </header>

    <main class="report-content">
      <!-- Filter Bar -->
      <section class="filter-bar">
        <div class="date-range-selector">
          <!-- Start Date -->
          <div class="date-input-group">
            <label class="filter-label">ตั้งแต่:</label>
            <div class="date-input-wrapper">
              <Calendar class="date-icon-inside" :size="18" />
              <input 
                type="date" 
                v-model="startDate" 
                @change="fetchReport"
                class="custom-native-date"
              >
            </div>
          </div>
          <!-- End Date -->
          <div class="date-input-group">
            <label class="filter-label">ถึง:</label>
            <div class="date-input-wrapper">
              <Calendar class="date-icon-inside" :size="18" />
              <input 
                type="date" 
                v-model="endDate" 
                @change="fetchReport"
                :min="startDate"
                class="custom-native-date"
              >
            </div>
          </div>
        </div>
        
        <button 
          @click="downloadCSV"
          class="btn-download"
          :disabled="loading || !reportData.details.length"
        >
          <Download :size="20" />
          <span class="text">ดาวน์โหลด CSV</span>
        </button>
      </section>

      <!-- Summary Section -->
      <div v-if="loading" class="loading-overlay">
        <Loader2 class="spinner-icon animate-spin" :size="48" />
        <p>กำลังสรุปข้อมูล...</p>
      </div>

      <template v-else>
        <section class="summary-grid" v-if="reportData.summary">
          <div class="summary-card border-action">
            <div class="summary-header">
              <p class="summary-label">รายได้รวม</p>
              <CircleDollarSign class="text-accent" :size="20" />
            </div>
            <p class="summary-value text-accent">฿{{ formatPrice(reportData.summary.total_revenue) }}</p>
          </div>
          <div class="summary-card border-primary">
            <div class="summary-header">
              <p class="summary-label">จำนวนโต๊ะที่เปิด</p>
              <LayoutGrid class="text-primary" :size="20" />
            </div>
            <p class="summary-value text-primary">{{ reportData.summary.total_tables }}</p>
          </div>
          <div class="summary-card border-warning">
            <div class="summary-header">
              <p class="summary-label">ปิดโดยพนักงาน</p>
              <UserCheck class="text-warning" :size="20" />
            </div>
            <p class="summary-value text-warning">{{ reportData.summary.closed_by_staff }}</p>
          </div>
          <div class="summary-card border-danger">
            <div class="summary-header">
              <p class="summary-label">ระบบปิดอัตโนมัติ</p>
              <MonitorOff class="text-danger" :size="20" />
            </div>
            <p class="summary-value text-danger">{{ reportData.summary.closed_by_system }}</p>
          </div>
        </section>

        <!-- Detailed Table -->
        <section class="table-wrapper card-theme">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>โต๊ะ</th>
                <th>ลูกค้า</th>
                <th class="text-center">วัน-เวลา</th>
                <th>ปิดโดย</th>
                <th class="text-right">ยอดเงิน</th>
              </tr>
            </thead>
            <tbody v-if="reportData.details && reportData.details.length > 0">
              <tr v-for="item in reportData.details" :key="item.id">
                <td class="id-cell">#{{ String(item.id).padStart(3, '0') }}</td>
                <td class="font-bold">{{ item.table_name }}</td>
                <td>
                   <div class="pax-info">
                     <Users :size="14" class="opacity-50" />
                     <span>{{ item.pax }} คน</span>
                   </div>
                </td>
                <td class="time-cell text-center">
                   <div class="time-range text-xs">
                     <span class="font-medium text-gray-700">{{ item.start_time }}</span>
                     <span class="time-divider">to</span>
                     <span class="font-medium text-gray-700">{{ item.end_time }}</span>
                   </div>
                </td>
                <td>
                  <span :class="['badge-icon', item.closed_by === 'system' ? 'badge-danger' : 'badge-warning']">
                    <component :is="item.closed_by === 'system' ? 'MonitorOff' : 'User'" :size="12" />
                    {{ item.closed_by === 'system' ? 'Auto System' : 'Staff' }}
                  </span>
                </td>
                <td class="text-right font-bold text-accent">
                  ฿{{ formatPrice(item.total_amount) }}
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="6" class="empty-row">
                  <div class="empty-content">
                    <FolderOpen :size="48" class="empty-icon" />
                    <p>ไม่พบข้อมูลรายได้ในช่วงวันที่เลือก</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </section>
      </template>
    </main>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
  ArrowLeft, Calendar, Download, Loader2, CircleDollarSign, 
  LayoutGrid, UserCheck, MonitorOff, Users, User, FolderOpen 
} from 'lucide-vue-next';

export default {
  name: 'DailyReport',
  components: {
    ArrowLeft, Calendar, Download, Loader2, CircleDollarSign, 
    LayoutGrid, UserCheck, MonitorOff, Users, User, FolderOpen
  },
  setup() {
    const todayStr = new Date().toISOString().split('T')[0];
    const startDate = ref(todayStr);
    const endDate = ref(todayStr);
    const loading = ref(false);
    const reportData = ref({
      summary: null,
      details: []
    });

    const fetchReport = async () => {
      loading.value = true;
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.get(`/api/reports/daily`, {
          params: { start_date: startDate.value, end_date: endDate.value },
          headers: { Authorization: `Bearer ${token}` }
        });
        reportData.value = data;
      } catch (error) {
        console.error("Error fetching report:", error);
      } finally {
        loading.value = false;
      }
    };

    const downloadCSV = async () => {
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.get(`/api/reports/export-csv`, {
          params: { start_date: startDate.value, end_date: endDate.value },
          headers: { Authorization: `Bearer ${token}` },
          responseType: 'blob',
        });
        
        const url = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `report-${startDate.value}-to-${endDate.value}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Error downloading CSV:", error);
      }
    };

    const formatPrice = (value) => {
      return parseFloat(value || 0).toLocaleString('th-TH', { 
        minimumFractionDigits: 2, maximumFractionDigits: 2 
      });
    };

    onMounted(fetchReport);

    return {
      startDate, endDate, loading, reportData,
      fetchReport, downloadCSV, formatPrice
    };
  }
}
</script>

<style scoped>
.report-container {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
}

/* Header */
.page-header {
  background-color: var(--color-primary);
  padding: 20px 40px;
  display: flex;
  align-items: center;
  gap: 24px;
  box-shadow: var(--shadow-md);
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

/* Main Content */
.report-content {
  padding: 30px 40px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Filter Bar */
.filter-bar {
  background: white;
  padding: 20px 24px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

.date-range-selector {
  display: flex;
  align-items: center;
  gap: 16px;
}

.date-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-label {
  font-weight: 600;
  color: var(--color-primary);
  font-size: 14px;
}

/* Styled Native Date Input Wrapper */
.date-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.date-icon-inside {
  position: absolute;
  left: 12px;
  color: var(--color-primary);
  pointer-events: none; /* เพื่อให้คลิกผ่านไอคอนไปยัง input ได้ */
}

.custom-native-date {
  width: 170px;
  padding: 10px 12px 10px 38px;
  border: 2px solid var(--color-table-border);
  border-radius: 12px;
  font-family: 'Sarabun', sans-serif;
  font-size: 14px;
  color: var(--color-text-primary);
  background-color: #ffffff;
  outline: none;
  transition: all 0.3s ease;
  cursor: pointer;
}

.custom-native-date:hover {
  border-color: var(--color-primary-light);
}

.custom-native-date:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(107, 79, 63, 0.1);
}

/* ซ่อนไอคอนเดิมของบราวเซอร์และขยายพื้นที่คลิก */
.custom-native-date::-webkit-calendar-picker-indicator {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
  cursor: pointer;
  opacity: 0; /* ซ่อนไอคอนจริงแต่ยังรับคลิกได้ทั่วทั้งช่อง */
}

.btn-download {
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(135deg, var(--color-action), var(--color-action-hover));
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(76, 175, 142, 0.3);
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-download:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 142, 0.4);
}

/* Summary Grid */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.summary-card {
  background: white;
  padding: 24px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border-top: 5px solid;
  transition: var(--transition-normal);
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.summary-label {
  font-size: 14px;
  color: var(--color-text-secondary);
  font-weight: 600;
  margin: 0;
}

.summary-value {
  font-size: 2rem;
  font-weight: 800;
  margin: 0;
}

.border-action{ border-color: var(--color-action); }
.border-primary { border-color: var(--color-primary); }
.border-warning { border-color: var(--color-warning); }
.border-danger { border-color: var(--color-danger); }

/* Table Section */
.table-wrapper {
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background-color: #F9FAFB;
  color: var(--color-primary);
  text-align: left;
  padding: 18px 20px;
  font-weight: 700;
  font-size: 13px;
  text-transform: uppercase;
  border-bottom: 2px solid var(--color-table-border);
}

.data-table td {
  padding: 18px 20px;
  border-bottom: 1px solid var(--color-table-border);
  color: var(--color-text-primary);
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.text-accent { color: var(--color-action); }
.text-primary { color: var(--color-primary); }
.text-warning { color: var(--color-warning); }
.text-danger { color: var(--color-danger); }

.pax-info {
  display: flex;
  align-items: center;
  gap: 6px;
}

.time-range {
  display: flex;
  flex-direction: column;
  color: var(--color-text-secondary);
}

.time-divider {
  font-size: 10px;
  opacity: 0.5;
  margin: 2px 0;
}

.badge-icon {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 700;
  color: white;
}

.badge-warning { background-color: var(--color-warning); }
.badge-danger { background-color: var(--color-danger); }

/* Loading & Empty */
.loading-overlay {
  text-align: center;
  padding: 100px 0;
  color: var(--color-primary);
}

.spinner-icon {
  margin: 0 auto 20px;
}

.empty-content {
  padding: 80px 0;
  text-align: center;
  color: #D1D5DB;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon {
  margin-bottom: 16px;
}

@media (max-width: 768px) {
  .page-header { padding: 20px; }
  .report-content { padding: 20px; }
  .filter-bar { flex-direction: column; align-items: stretch; }
  .date-range-selector { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>