import { ref, nextTick, onBeforeUnmount } from 'vue';
import $ from 'jquery';

/**
 * useDataTable - Composable for managing jQuery DataTables in Vue components
 * 
 * @param {Object} options Configuration options
 * @param {String} options.tableId The ID of the table element (default: '#dataTable')
 * @param {Object} options.config Extra DataTable configurations
 */
export function useDataTable(options = {}) {
  const { 
    tableId = '#dataTable', 
    config = {} 
  } = options;

  const dataTableInstance = ref(null);
  const isTableLoading = ref(false);

  /**
   * Initialize or Refresh the DataTable
   */
  const initDataTable = async () => {
    isTableLoading.value = true;
    
    // Wait for DOM to update
    await nextTick();

    // Ensure jQuery and DataTable plugin are available
    if (typeof $ === 'undefined' || !$.fn.DataTable) {
      console.warn('DataTable or jQuery not found');
      isTableLoading.value = false;
      return;
    }

    const $el = $(tableId);
    if ($el.length === 0) {
      console.warn(`Table element ${tableId} not found`);
      isTableLoading.value = false;
      return;
    }

    // Destroy existing instance if it exists
    if ($.fn.DataTable.isDataTable(tableId)) {
      $(tableId).DataTable().destroy();
    }

    // Default Configuration
    const defaultConfig = {
      responsive: true,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
      },
      pageLength: 10,
      order: [[0, 'asc']],
      ...config
    };

    // Initialize new instance
    dataTableInstance.value = $el.DataTable(defaultConfig);
    
    // Small timeout to show loading state smoothly
    setTimeout(() => {
        isTableLoading.value = false;
    }, 300);
  };

  /**
   * Destroy the DataTable instance manually
   */
  const destroyDataTable = () => {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
      return;
    }

    if (dataTableInstance.value) {
      dataTableInstance.value.destroy();
      dataTableInstance.value = null;
    } else if ($.fn.DataTable.isDataTable(tableId)) {
      $(tableId).DataTable().destroy();
    }
  };

  /**
   * Helper to refresh table when data changes
   */
  const refreshDataTable = async () => {
    await initDataTable();
  };

  // Auto cleanup when component is unmounted
  onBeforeUnmount(() => {
    destroyDataTable();
  });

  return {
    dataTableInstance,
    isTableLoading,
    initDataTable,
    destroyDataTable,
    refreshDataTable
  };
}
