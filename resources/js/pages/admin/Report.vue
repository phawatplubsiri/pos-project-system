<template>
  <div class="p-6 bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">รายงานรายได้ประจำวัน</h1>
      <button @click="$router.push('/admin/dashboard')" class="text-blue-600 hover:underline">
        &larr; กลับหน้าแดชบอร์ด
      </button>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
      <div class="flex items-center gap-2">
        <label class="font-medium text-gray-700">เลือกวันที่:</label>
        <input 
          type="date" 
          v-model="selectedDate" 
          @change="fetchReport"
          class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
      </div>
      <button 
        @click="downloadCSV"
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 transition"
      >
        <span>📥</span> ดาวน์โหลด CSV
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" v-if="reportData.summary">
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">รายได้รวม</p>
        <p class="text-2xl font-bold text-gray-800">฿{{ formatPrice(reportData.summary.total_revenue) }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
        <p class="text-sm text-gray-500">จำนวนโต๊ะที่เปิด</p>
        <p class="text-2xl font-bold text-gray-800">{{ reportData.summary.total_tables }} โต๊ะ</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500">ปิดโดยพนักงาน</p>
        <p class="text-2xl font-bold text-gray-800">{{ reportData.summary.closed_by_staff }} รายการ</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
        <p class="text-sm text-gray-500">ระบบปิดอัตโนมัติ</p>
        <p class="text-2xl font-bold text-gray-800">{{ reportData.summary.closed_by_system }} รายการ</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">โต๊ะ</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลูกค้า (คน)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวลา</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ปิดโดย</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดเงิน</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200" v-if="reportData.details && reportData.details.length > 0">
          <tr v-for="item in reportData.details" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ item.id }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.table_name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.pax }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ item.start_time }} - {{ item.end_time }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span 
                :class="item.closed_by === 'system' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ item.closed_by === 'system' ? 'Auto System' : 'Staff' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
              ฿{{ formatPrice(item.total_amount) }}
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
              ไม่พบข้อมูลรายได้ในวันที่เลือก
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      selectedDate: new Date().toISOString().substr(0, 10), // วันนี้ YYYY-MM-DD
      reportData: {
        summary: null,
        details: []
      }
    }
  },
  mounted() {
    this.fetchReport();
  },
  methods: {
    async fetchReport() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/reports/daily?date=${this.selectedDate}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        this.reportData = response.data;
      } catch (error) {
        console.error("Error fetching report:", error);
        alert('ไม่สามารถดึงข้อมูลรายงานได้ หรือคุณไม่มีสิทธิ์เข้าถึง');
      }
    },
    async downloadCSV() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/reports/export-csv?date=${this.selectedDate}`, {
          headers: { Authorization: `Bearer ${token}` },
          responseType: 'blob', // สำคัญมากสำหรับการดาวน์โหลดไฟล์
        });
        
        // สร้าง Link จำลองเพื่อกดดาวน์โหลด
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `report-${this.selectedDate}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error("Error downloading CSV:", error);
        alert('เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์');
      }
    },
    formatPrice(value) {
      return parseFloat(value).toLocaleString('th-TH', { minimumFractionDigits: 2 });
    }
  }
}
</script>
