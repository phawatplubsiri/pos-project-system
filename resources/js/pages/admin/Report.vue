<template>
  <div class="min-h-screen bg-[var(--color-bg-primary)]">
    <AdminHeader title="รายงานรายได้" subtitle="Revenue Report" />

    <main class="py-7.5 px-10 max-w-[1400px] mx-auto">
      <!-- Filter Bar -->
      <section class="bg-white p-5 px-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] mb-7.5 flex justify-between items-center gap-5">
        <div class="flex items-center gap-4">
          <!-- Start Date -->
          <div class="flex items-center gap-2">
            <label class="font-semibold text-[var(--color-primary)] text-sm">ตั้งแต่:</label>
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
          <div class="flex items-center gap-2">
            <label class="font-semibold text-[var(--color-primary)] text-sm">ถึง:</label>
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
          class="py-3 px-6 text-[15px] font-semibold text-white bg-gradient-to-br from-[var(--color-action)] to-[var(--color-action-hover)] border-none rounded-xl cursor-pointer transition-all duration-300 shadow-[0_2px_8px_rgba(76,175,142,0.3)] flex items-center gap-2.5 hover:not-disabled:-translate-y-0.5 hover:not-disabled:shadow-[0_4px_12px_rgba(76,175,142,0.4)]"
          :disabled="loading || !reportData.details.length"
        >
          <Download :size="20" />
          <span>ดาวน์โหลด CSV</span>
        </button>
      </section>

      <!-- Summary Section -->
      <div v-if="loading" class="text-center py-25 text-[var(--color-primary)]">
        <Loader2 class="mx-auto mb-5 animate-spin" :size="48" />
        <p>กำลังสรุปข้อมูล...</p>
      </div>

      <template v-else>
        <section v-if="reportData.summary" class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-5 mb-7.5">
          <div class="bg-white p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] border-t-[5px] border-[var(--color-action)] transition-[var(--transition-normal)]">
            <div class="flex justify-between items-start mb-3">
              <p class="text-sm text-[var(--color-text-secondary)] font-semibold m-0">รายได้รวม</p>
              <CircleDollarSign class="text-[var(--color-action)]" :size="20" />
            </div>
            <p class="text-[2rem] font-extrabold m-0 text-[var(--color-action)]">฿{{ formatPrice(reportData.summary.total_revenue) }}</p>
          </div>
          <div class="bg-white p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] border-t-[5px] border-[var(--color-primary)] transition-[var(--transition-normal)]">
            <div class="flex justify-between items-start mb-3">
              <p class="text-sm text-[var(--color-text-secondary)] font-semibold m-0">จำนวนโต๊ะที่เปิด</p>
              <LayoutGrid class="text-[var(--color-primary)]" :size="20" />
            </div>
            <p class="text-[2rem] font-extrabold m-0 text-[var(--color-primary)]">{{ reportData.summary.total_tables }}</p>
          </div>
          <div class="bg-white p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] border-t-[5px] border-[var(--color-warning)] transition-[var(--transition-normal)]">
            <div class="flex justify-between items-start mb-3">
              <p class="text-sm text-[var(--color-text-secondary)] font-semibold m-0">ปิดโดยพนักงาน</p>
              <UserCheck class="text-[var(--color-warning)]" :size="20" />
            </div>
            <p class="text-[2rem] font-extrabold m-0 text-[var(--color-warning)]">{{ reportData.summary.closed_by_staff }}</p>
          </div>
          <div class="bg-white p-6 rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] border-t-[5px] border-[var(--color-danger)] transition-[var(--transition-normal)]">
            <div class="flex justify-between items-start mb-3">
              <p class="text-sm text-[var(--color-text-secondary)] font-semibold m-0">ระบบปิดอัตโนมัติ</p>
              <MonitorOff class="text-[var(--color-danger)]" :size="20" />
            </div>
            <p class="text-[2rem] font-extrabold m-0 text-[var(--color-danger)]">{{ reportData.summary.closed_by_system }}</p>
          </div>
        </section>

        <!-- Detailed Table -->
        <section class="bg-white rounded-[var(--radius-lg)] shadow-[var(--shadow-sm)] overflow-hidden">
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
                <td class="text-[var(--color-text-secondary)] text-xs">#{{ String(item.id).padStart(3, '0') }}</td>
                <td class="font-bold">{{ item.table_name }}</td>
                <td>
                   <div class="flex items-center gap-1.5">
                     <Users :size="14" class="opacity-50" />
                     <span>{{ item.pax }} คน</span>
                   </div>
                </td>
                <td class="text-center">
                   <div class="flex flex-col text-xs text-[var(--color-text-secondary)]">
                     <span class="font-medium text-gray-700">{{ item.start_time }}</span>
                     <span class="text-[10px] opacity-50 my-0.5">to</span>
                     <span class="font-medium text-gray-700">{{ item.end_time }}</span>
                   </div>
                </td>
                <td>
                  <span :class="['inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-[11px] font-bold text-white', item.closed_by === 'system' ? 'bg-[var(--color-danger)]' : 'bg-[var(--color-warning)]']">
                    <component :is="item.closed_by === 'system' ? 'MonitorOff' : 'User'" :size="12" />
                    {{ item.closed_by === 'system' ? 'Auto System' : 'Staff' }}
                  </span>
                </td>
                <td class="text-right font-bold text-[var(--color-action)]">
                  ฿{{ formatPrice(item.total_amount) }}
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="6">
                  <div class="py-20 text-center text-[#D1D5DB] flex flex-col items-center">
                    <FolderOpen :size="48" class="mb-4" />
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
import AdminHeader from '../../components/AdminHeader.vue';
import { 
  Calendar, Download, Loader2, CircleDollarSign, 
  LayoutGrid, UserCheck, MonitorOff, Users, User, FolderOpen 
} from 'lucide-vue-next';

export default {
  name: 'DailyReport',
  components: {
    AdminHeader, Calendar, Download, Loader2, CircleDollarSign, 
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
/* Date Input Styling */
.date-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.date-icon-inside {
  position: absolute;
  left: 12px;
  color: var(--color-primary);
  pointer-events: none;
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

.custom-native-date:hover { border-color: var(--color-primary-light); }
.custom-native-date:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(107, 79, 63, 0.1); }

.custom-native-date::-webkit-calendar-picker-indicator {
  position: absolute;
  left: 0; top: 0;
  width: 100%; height: 100%;
  margin: 0; padding: 0;
  cursor: pointer;
  opacity: 0;
}

/* Data Table */
.data-table { width: 100%; border-collapse: collapse; }

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

@media (max-width: 768px) {
  main { padding: 20px; }
  .filter-bar { flex-direction: column; align-items: stretch; }
}
</style>