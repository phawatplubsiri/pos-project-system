<template>
  <div class="report-container">
    <!-- Header Section -->
    <header class="page-header">
      <button @click="$router.push('/admin/dashboard')" class="btn-back" aria-label="Back to Dashboard">
        <span class="icon">←</span>
      </button>
      <div class="header-content">
        <h1 class="page-title">รายงานรายได้ประจำวัน</h1>
        <p class="page-subtitle">Daily Revenue Report</p>
      </div>
    </header>

    <main class="report-content">
      <!-- Filter Bar -->
      <section class="filter-bar">
        <div class="date-selector">
          <label for="report-date" class="filter-label">เลือกวันที่:</label>
          <input 
            id="report-date"
            type="date" 
            v-model="selectedDate" 
            @change="fetchReport"
            class="input-date"
            :disabled="loading"
          >
        </div>
        <button 
          @click="downloadCSV"
          class="btn-download"
          :disabled="loading || !reportData.details.length"
        >
          <span class="icon">⬇</span> 
          <span class="text">ดาวน์โหลด CSV</span>
        </button>
      </section>

      <!-- Summary Section -->
      <div v-if="loading" class="loading-overlay">
        <div class="spinner-large"></div>
        <p>กำลังสรุปข้อมูล...</p>
      </div>

      <template v-else>
        <section class="summary-grid" v-if="reportData.summary">
          <div class="summary-card border-accent">
            <p class="summary-label">รายได้รวม</p>
            <p class="summary-value text-accent">฿{{ formatPrice(reportData.summary.total_revenue) }}</p>
          </div>
          <div class="summary-card border-primary">
            <p class="summary-label">จำนวนโต๊ะที่เปิด</p>
            <p class="summary-value text-primary">{{ reportData.summary.total_tables }}</p>
          </div>
          <div class="summary-card border-warning">
            <p class="summary-label">ปิดโดยพนักงาน</p>
            <p class="summary-value text-warning">{{ reportData.summary.closed_by_staff }}</p>
          </div>
          <div class="summary-card border-danger">
            <p class="summary-label">ระบบปิดอัตโนมัติ</p>
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
                <th>ช่วงเวลา</th>
                <th>ปิดโดย</th>
                <th class="text-right">ยอดเงิน</th>
              </tr>
            </thead>
            <tbody v-if="reportData.details && reportData.details.length > 0">
              <tr v-for="item in reportData.details" :key="item.id">
                <td class="id-cell">#{{ String(item.id).padStart(3, '0') }}</td>
                <td class="font-bold">{{ item.table_name }}</td>
                <td>{{ item.pax }} คน</td>
                <td class="time-cell">{{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</td>
                <td>
                  <span :class="['badge', item.closed_by === 'system' ? 'badge-danger' : 'badge-warning']">
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
                    <span class="empty-icon">📂</span>
                    <p>ไม่พบข้อมูลรายได้ในวันที่เลือก</p>
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

export default {
  name: 'DailyReport',
  setup() {
    const selectedDate = ref(new Date().toISOString().substr(0, 10));
    const loading = ref(false);
    const reportData = ref({
      summary: null,
      details: []
    });

    const fetchReport = async () => {
      loading.value = true;
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.get(`/api/reports/daily?date=${selectedDate.value}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        reportData.value = data;
      } catch (error) {
        console.error("Error fetching report:", error);
        // สามารถเปลี่ยน alert เป็น Toast Notification ได้ในอนาคต
      } finally {
        loading.value = false;
      }
    };

    const downloadCSV = async () => {
      try {
        const token = localStorage.getItem('token');
        const { data } = await axios.get(`/api/reports/export-csv?date=${selectedDate.value}`, {
          headers: { Authorization: `Bearer ${token}` },
          responseType: 'blob',
        });
        
        const url = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `report-${selectedDate.value}.csv`);
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
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
      });
    };

    const formatTime = (timeString) => {
      if (!timeString) return '';
      return timeString.substring(0, 5);
    };

    onMounted(fetchReport);

    return {
      selectedDate,
      loading,
      reportData,
      fetchReport,
      downloadCSV,
      formatPrice,
      formatTime
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
  padding: 30px 40px;
  display: flex;
  align-items: center;
  gap: 24px;
  box-shadow: var(--shadow-md);
}

.btn-back {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition-normal);
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateX(-4px);
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--color-highlight-light);
  margin: 0;
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

.date-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.filter-label {
  font-weight: 600;
  color: var(--color-accent);
}

.input-date {
  padding: 10px 16px;
  border: 2px solid var(--color-border-light);
  border-radius: var(--radius-md);
  font-size: 15px;
  color: var(--color-text-primary);
}

.btn-download {
  background: linear-gradient(135deg, var(--color-accent), var(--color-accent-light));
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: var(--transition-normal);
}

.btn-download:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.2);
}

.btn-download:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.summary-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.summary-label {
  font-size: 14px;
  color: var(--color-text-light);
  margin-bottom: 10px;
  font-weight: 600;
}

.summary-value {
  font-size: 2rem;
  font-weight: 800;
  margin: 0;
}

/* Card Border Colors */
.border-accent { border-color: var(--color-accent); }
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
  background-color: var(--color-bg-secondary);
  color: var(--color-accent);
  text-align: left;
  padding: 18px 20px;
  font-weight: 700;
  font-size: 14px;
  text-transform: uppercase;
  border-bottom: 2px solid var(--color-border-light);
}

.data-table td {
  padding: 18px 20px;
  border-bottom: 1px solid var(--color-border-light);
  color: var(--color-text-primary);
}

.data-table tr:hover {
  background-color: var(--color-secondary-light);
}

.id-cell {
  color: var(--color-text-light);
  font-family: monospace;
}

.font-bold { font-weight: 700; }
.text-right { text-align: right; }
.text-accent { color: var(--color-accent); }
.text-primary { color: var(--color-primary); }
.text-warning { color: var(--color-warning); }
.text-danger { color: var(--color-danger); }

/* Badges */
.badge {
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
  color: white;
}

.badge-warning { background-color: var(--color-warning); }
.badge-danger { background-color: var(--color-danger); }

/* Loading & Empty States */
.loading-overlay {
  text-align: center;
  padding: 100px 0;
  color: var(--color-accent);
}

.spinner-large {
  width: 50px;
  height: 50px;
  border: 5px solid var(--color-border-light);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

.empty-row {
  padding: 0;
}

.empty-content {
  padding: 80px 0;
  text-align: center;
  color: var(--color-text-light);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .page-header { padding: 20px; }
  .report-content { padding: 20px 16px; }
  .filter-bar { 
    flex-direction: column; 
    align-items: stretch; 
  }
  .summary-grid { grid-template-columns: 1fr; }
  .table-wrapper { overflow-x: auto; }
}
</style>