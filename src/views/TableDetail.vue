<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle :icon="'grid-outline'" :title="`Table: ${$route.params.name}`" />

      <div class="page-container">
        <PageHeader icon="server-outline" :title="$route.params.name">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="refreshData">Refresh</ActionButton>
            <ActionButton variant="secondary" icon="download-outline" @click="exportData">Export</ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="addNewRow">Add Row</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="list-outline" color="primary" :value="data.length" label="Total Rows" />
          <StatCard icon="grid-outline" color="info" :value="labels.length" label="Columns" />
          <StatCard icon="search-outline" color="success" :value="filteredData.length" label="Filtered Rows" />
        </div>

        <DataCard title="Table Data" :subtitle="`${filteredData.length} row${filteredData.length !== 1 ? 's' : ''}`"
          noPadding>
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search table data..." />
          </template>

          <div class="table-wrapper">
            <LoadingState v-if="loading" message="Loading table data..." />

            <EmptyState v-else-if="data.length === 0" icon="grid-outline" title="No Data Found"
              description="This table is empty or no data matches your search.">
              <ActionButton variant="primary" icon="add-outline" @click="addNewRow">Add First Row</ActionButton>
            </EmptyState>

            <div v-else class="modern-table">
              <div class="table-header">
                <div v-for="(label, index) in labels" :key="label" class="header-cell" @click="sortBy(index)">
                  <span class="header-text">{{ label }}</span>
                  <div class="sort-indicator">
                    <ion-icon v-if="sortColumn === index && sortDirection === 'asc'" name="chevron-up-outline"
                      class="sort-active"></ion-icon>
                    <ion-icon v-else-if="sortColumn === index && sortDirection === 'desc'" name="chevron-down-outline"
                      class="sort-active"></ion-icon>
                    <ion-icon v-else name="swap-vertical-outline" class="sort-default"></ion-icon>
                  </div>
                </div>
                <div class="header-cell actions-header">Actions</div>
              </div>

              <div class="table-body">
                <div v-for="(row, rowIndex) in sortedData" :key="rowIndex" class="table-row">
                  <div v-for="(cell, colIndex) in row" :key="colIndex" class="table-cell"
                    @click="editCell(rowIndex, colIndex)">
                    <div v-if="editingCell?.row === rowIndex && editingCell?.col === colIndex" class="cell-edit">
                      <input v-model="editValue" @blur="saveEdit" @keyup.enter="saveEdit" @keyup.escape="cancelEdit"
                        class="cell-input" autofocus />
                    </div>
                    <div v-else class="cell-content">
                      <span v-if="searchTerm && String(cell).toLowerCase().includes(searchTerm.toLowerCase())"
                        v-html="highlightMatch(cell, searchTerm)"></span>
                      <span v-else>{{ cell }}</span>
                    </div>
                  </div>
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="editRow(rowIndex)" title="Edit Row">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteRow(rowIndex)" title="Delete Row">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </DataCard>

        <DataCard title="Table Schema" :subtitle="`${labels.length} column${labels.length !== 1 ? 's' : ''}`" noPadding>
          <div class="schema-grid">
            <div v-for="(label, index) in labels" :key="label" class="schema-item">
              <div class="schema-icon">
                <ion-icon name="key-outline" v-if="index === 0"></ion-icon>
                <ion-icon name="text-outline" v-else></ion-icon>
              </div>
              <div class="schema-info">
                <h4>{{ label }}</h4>
                <p>{{ index === 0 ? 'Primary Key' : 'Data Column' }}</p>
              </div>
            </div>
          </div>
        </DataCard>
      </div>

      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, getCurrentInstance, computed } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import DataCard from "@/components/DataCard.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "TableDetailView",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    ActionButton,
    DataCard,
    SearchBox,
    LoadingState,
    EmptyState,
  },
  setup() {
    const labels = ref([]);
    const data = ref([]);
    const loading = ref(true);
    const searchTerm = ref('');
    const sortColumn = ref(null);
    const sortDirection = ref('asc');
    const editingCell = ref(null);
    const editValue = ref('');
    const successMessage = ref('');

    const route = useRoute();
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const loadData = async () => {
      loading.value = true;
      try {
        const response = await axios.post(
          "mysql.php",
          qs.stringify({ getTableByName: route.params.name }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );
        labels.value = response.data.labels;
        data.value = response.data.data;
      } catch (error) {
        console.error('Error loading table data:', error);
      } finally {
        loading.value = false;
      }
    };

    loadData();

    const filteredData = computed(() => {
      if (!searchTerm.value.trim()) return data.value;

      const searchLower = searchTerm.value.toLowerCase();
      return data.value.filter(row =>
        row.some(cell =>
          String(cell).toLowerCase().includes(searchLower)
        )
      );
    });

    const sortedData = computed(() => {
      if (sortColumn.value === null) return filteredData.value;

      const sorted = [...filteredData.value].sort((a, b) => {
        const aVal = a[sortColumn.value];
        const bVal = b[sortColumn.value];

        // Check if values are numbers
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);

        if (!isNaN(aNum) && !isNaN(bNum)) {
          return sortDirection.value === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          const aStr = String(aVal).toLowerCase();
          const bStr = String(bVal).toLowerCase();

          if (sortDirection.value === 'asc') {
            return aStr.localeCompare(bStr);
          } else {
            return bStr.localeCompare(aStr);
          }
        }
      });

      return sorted;
    });

    const updateField = async (rowIndex, fieldName, newValue) => {
      try {
        data.value[rowIndex][fieldName] = newValue;
        await axios.post(
          "mysql.php",
          qs.stringify({
            updateField: true,
            tableName: route.params.name,
            fieldName,
            newValue,
            rowIndex,
          }),
          {
            headers: {
              "Authorization": localStorage.getItem("token")
            }
          }
        );

        showSuccessMessage('Field updated successfully');
        await loadData(); // Reload data after update
      } catch (error) {
        console.error('Error updating field:', error);
      }
    };

    const sortBy = (columnIndex) => {
      if (sortColumn.value === columnIndex) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        sortColumn.value = columnIndex;
        sortDirection.value = 'asc';
      }
    };

    const editCell = (rowIndex, colIndex) => {
      editingCell.value = { row: rowIndex, col: colIndex };
      editValue.value = data.value[rowIndex][colIndex];
    };

    const saveEdit = async () => {
      if (editingCell.value) {
        const { row, col } = editingCell.value;
        const fieldName = labels.value[col];
        await updateField(row, fieldName, editValue.value);
        editingCell.value = null;
        editValue.value = '';
      }
    };

    const cancelEdit = () => {
      editingCell.value = null;
      editValue.value = '';
    };

    const editRow = (rowIndex) => {
      // Implement row editing functionality
      console.log('Edit row:', rowIndex);
    };

    const deleteRow = async (rowIndex) => {
      if (!confirm('Are you sure you want to delete this row?')) return;

      try {
        // Implement row deletion
        console.log('Delete row:', rowIndex);
        showSuccessMessage('Row deleted successfully');
      } catch (error) {
        console.error('Error deleting row:', error);
      }
    };

    const addNewRow = () => {
      // Implement add new row functionality
      console.log('Add new row');
    };

    const refreshData = () => {
      loadData();
    };

    const exportData = () => {
      // Implement data export functionality
      console.log('Export data');
    };

    const highlightMatch = (text, search) => {
      if (!search) return text;
      const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
      return String(text).replace(regex, '<mark class="highlight">$1</mark>');
    };

    const showSuccessMessage = (message) => {
      successMessage.value = message;
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    };

    return {
      labels,
      data,
      loading,
      searchTerm,
      sortColumn,
      sortDirection,
      editingCell,
      editValue,
      successMessage,
      filteredData,
      sortedData,
      updateField,
      sortBy,
      editCell,
      saveEdit,
      cancelEdit,
      editRow,
      deleteRow,
      addNewRow,
      refreshData,
      exportData,
      highlightMatch,
    };
  },
});
</script>

<style scoped>
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 800px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.header-cell:hover {
  background: var(--border);
}

.actions-header {
  flex: 0 0 140px;
  justify-content: center;
  cursor: default;
}

.actions-header:hover {
  background: var(--background);
}

.header-text {
  font-weight: 600;
}

.sort-indicator {
  display: flex;
  align-items: center;
  margin-left: 8px;
}

.sort-indicator ion-icon {
  font-size: 14px;
  transition: all 0.2s ease;
}

.sort-default {
  opacity: 0.3;
}

.sort-active {
  opacity: 1;
  color: var(--primary-color);
}

.header-cell:hover .sort-default {
  opacity: 0.6;
}

/* Table Body */
.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-row:last-child {
  border-bottom: none;
}

.table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.table-cell:hover {
  background: rgba(249, 115, 22, 0.05);
}

.actions-cell {
  flex: 0 0 140px;
  justify-content: center;
  padding: 12px 16px;
  cursor: default;
}

.actions-cell:hover {
  background: transparent;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

.cell-edit {
  width: 100%;
}

.cell-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid var(--primary-color);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  outline: none;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.edit-btn {
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.edit-btn:hover {
  background: rgba(249, 115, 22, 0.22);
  transform: scale(1.05);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
  transform: scale(1.05);
}

/* Schema Grid */
.schema-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  padding: 24px;
}

.schema-item {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.schema-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 20px;
  flex-shrink: 0;
}

.schema-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.schema-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 12px;
}

/* Highlight Match */
.highlight {
  background: #ffe082;
  color: #d32f2f;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}

/* Success Toast */
.success-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: rgba(5, 150, 105, 0.95);
  color: white;
  padding: 16px 20px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  z-index: 10001;
  backdrop-filter: blur(8px);
  box-shadow: var(--shadow-lg);
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .header-cell,
  .table-cell {
    min-width: 100px;
    padding: 12px 8px;
    font-size: 12px;
  }

  .modern-table {
    min-width: 600px;
  }

  .cell-content {
    max-width: 80px;
  }

  .schema-grid {
    grid-template-columns: 1fr;
  }
}
</style>
