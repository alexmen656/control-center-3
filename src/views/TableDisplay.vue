<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle v-if="true" icon="list-outline" :title="form2.title || 'Table'" />
      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>{{ form2.title || 'Table Data' }}</h1>
            <p>Manage and view table entries</p>
          </div>
        </div>
        <div class="action-bar">
          <div class="action-group-left">
            <ActionButton variant="primary" icon="add-outline" @click="toggleFormView">
              <span>Add Entry</span>
            </ActionButton>
          </div>
          <div class="action-group-right">
            <div class="dropdown">
              <button class="action-btn secondary dropdown-toggle" @click="toggleExportDropdown">
                <ion-icon name="download-outline"></ion-icon>
                <span>Export</span>
              </button>
              <div class="dropdown-menu" :class="{ active: exportDropdownOpen }">
                <a @click="exportData('csv')" class="dropdown-item">
                  <ion-icon name="document-text-outline"></ion-icon>
                  Export CSV
                </a>
                <a @click="exportData('excel')" class="dropdown-item">
                  <ion-icon name="grid-outline"></ion-icon>
                  Export Excel
                </a>
              </div>
            </div>
            <ActionButton variant="secondary" icon="notifications-outline" @click="openTriggerModal()">
              <span>Triggers</span>
            </ActionButton>
            <div class="dropdown">
              <button class="action-btn secondary dropdown-toggle" @click="toggleDropdown">
                <ion-icon name="ellipsis-vertical-outline"></ion-icon>
              </button>
              <div class="dropdown-menu" :class="{ active: dropdownOpen }">
                <a @click="openRenameModal()" class="dropdown-item">
                  <ion-icon name="create-outline"></ion-icon>
                  Rename Table
                </a>
                <a @click="openEditModal()" class="dropdown-item">
                  <ion-icon name="settings-outline"></ion-icon>
                  Edit Table
                </a>
                <a @click="openDeleteModal()" class="dropdown-item dropdown-item-danger">
                  <ion-icon name="trash-outline"></ion-icon>
                  Delete Table
                </a>
              </div>
            </div>
          </div>
        </div>
        <DataCard title="Data Overview" :subtitle="(data?.length || 0) + ' entries'" noPadding>
          <template #actions>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Search entries..." v-model="searchTerm" @input="handleSearch">
            </div>
          </template>
          <div class="table-wrapper">
            <div class="modern-table">
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
                <EmptyState v-if="!sortedData || sortedData.length === 0" icon="folder-open-outline"
                  title="No Data Available"
                  :description="searchTerm ? 'No entries match your search criteria.' : 'No entries have been created yet.'">
                  <ActionButton v-if="!searchTerm" variant="primary" icon="add-outline" @click="toggleFormView">
                    Add First Entry
                  </ActionButton>
                </EmptyState>
                <div v-for="(tr, rowIndex) in sortedData" :key="rowIndex" class="table-row"
                  :class="{ 'row-hover': true }">
                  <div v-for="(td, colIndex) in tr" :key="colIndex" class="table-cell">
                    <img v-if="isImageValue(td) && getThumbUrl(td)" :src="getThumbUrl(td)"
                      class="cell-image-preview" @click.stop="openImagePreview(getSignedImageUrl(td))"
                      @error="onCellImageError(td)" />
                    <span v-else class="cell-content">{{ td }}</span>
                  </div>
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="edit(tr[0])" title="Edit">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deletee(tr[0])" title="Delete">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="load_more_btn" class="load-more-container">
            <button class="load-more-btn" @click="loadMore()">
              <ion-icon name="chevron-down-outline"></ion-icon>
              Load More Entries
            </button>
          </div>
        </DataCard>
        <div class="form-section" :class="{ 'form-visible': showForm }">
          <div class="form-card">
            <div class="form-header">
              <h3>Add New Entry</h3>
              <button class="close-form-btn" @click="toggleFormView">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <div class="form-content">
              <DisplayTable @submit="handleSubmit" />
            </div>
          </div>
        </div>
      </div>
      <AppModal v-model="isOpenRef" title="Edit Entry">
        <div v-if="editFormData.length > 0" class="modern-edit-form">
          <div v-for="field in editFormData" :key="field.name" class="form-group">
            <label class="form-label">{{ field.label }}</label>
            <input v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
              v-model="editFormValues[field.name]" :type="field.type" :placeholder="field.placeholder || field.label"
              class="modern-input" />
            <textarea v-else-if="field.type === 'textarea'" v-model="editFormValues[field.name]"
              :placeholder="field.placeholder || field.label" class="modern-textarea" rows="4"></textarea>
            <select v-else-if="field.type === 'select'" v-model="editFormValues[field.name]" class="modern-select">
              <option value="">Select {{ field.label }}</option>
              <option v-for="option in field.options" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <label v-else-if="field.type === 'checkbox'" class="checkbox-container">
              <input type="checkbox" v-model="editFormValues[field.name]" class="modern-checkbox" />
              <span class="checkmark"></span>
              {{ field.label }}
            </label>
            <input v-else-if="field.type === 'date'" v-model="editFormValues[field.name]" type="date"
              class="modern-input" />
            <FloatingFileUpload v-else-if="field.type === 'image'" v-model="editFormValues[field.name]" :label="''"
              :project="$route.params.project" :projectID="projectID" />
            <input v-else v-model="editFormValues[field.name]" type="text"
              :placeholder="field.placeholder || field.label" class="modern-input" />
          </div>
        </div>
        <LoadingState v-else message="Loading entry data..." />
        <template #footer>
          <ActionButton v-if="editFormData.length > 0" variant="secondary" @click="closeModal(false)">
            Cancel
          </ActionButton>
          <ActionButton v-if="editFormData.length > 0" variant="primary" @click="saveEdit()">
            Save Changes
          </ActionButton>
        </template>
      </AppModal>
      <TriggerManager v-if="triggerModalOpen" :project="$route.params.project" :table="$route.params.table"
        @close="triggerModalOpen = false" />
      <RenameTable v-if="renameModalOpen" :project="$route.params.project" :table="$route.params.table"
        @close="renameModalOpen = false" @success="handleRenameSuccess" @sidebarRefresh="refreshSidebar" />
      <DeleteTable v-if="deleteModalOpen" :project="$route.params.project" :table="$route.params.table"
        @close="deleteModalOpen = false" @success="handleDeleteSuccess" />
      <div v-if="imagePreviewUrl" class="image-lightbox" @click="imagePreviewUrl = ''">
        <img :src="imagePreviewUrl" class="image-lightbox-img" />
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import DisplayTable from "@/components/DisplayTable.vue";
import FloatingFileUpload from "@/components/FloatingFileUpload.vue";
import TriggerManager from "@/components/TriggerManager.vue";
import RenameTable from "@/components/RenameTable.vue";
import DeleteTable from "@/components/DeleteTable.vue";
import { defineComponent, ref } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";
import ActionButton from "@/components/ActionButton.vue";
import DataCard from "@/components/DataCard.vue";
import EmptyState from "@/components/EmptyState.vue";
import LoadingState from "@/components/LoadingState.vue";
import AppModal from "@/components/AppModal.vue";

export default defineComponent({
  name: "TableDisplay",
  components: {
    DisplayTable,
    FloatingFileUpload,
    TriggerManager,
    RenameTable,
    DeleteTable,
    SiteTitle,
    ActionButton,
    DataCard,
    EmptyState,
    LoadingState,
    AppModal,
  },
  data() {
    return {
      form: {},
      form2: {},
      labels: [],
      data: [],
      load_more_btn: false,
      current_limit: 0,
      sortColumn: null,
      sortDirection: 'asc',
      triggerModalOpen: false,
      renameModalOpen: false,
      deleteModalOpen: false,
      showForm: false,
      dropdownOpen: false,
      exportDropdownOpen: false,
      searchTerm: '',
      editFormData: [],
      editFormValues: {},
      projectID: null,
      signedUrls: {},
      thumbUrls: {},
      imagePreviewUrl: '',
    };
  },
  computed: {
    sortedData() {
      if (!this.data || !Array.isArray(this.data)) {
        return [];
      }

      let dataToSort = this.data;

      if (this.searchTerm.trim()) {
        const searchLower = this.searchTerm.toLowerCase();
        dataToSort = this.data.filter(row =>
          row.some(cell =>
            String(cell).toLowerCase().includes(searchLower)
          )
        );
      }

      if (this.sortColumn === null) {
        return dataToSort;
      }

      const sorted = [...dataToSort].sort((a, b) => {
        const aVal = a[this.sortColumn];
        const bVal = b[this.sortColumn];

        const dateRegex = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/;
        const aIsDate = dateRegex.test(aVal);
        const bIsDate = dateRegex.test(bVal);

        if (aIsDate && bIsDate) {
          const aDate = new Date(aVal);
          const bDate = new Date(bVal);
          return this.sortDirection === 'asc' ? aDate - bDate : bDate - aDate;
        }

        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);

        if (!isNaN(aNum) && !isNaN(bNum)) {
          return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          const aStr = String(aVal).toLowerCase();
          const bStr = String(bVal).toLowerCase();

          if (this.sortDirection === 'asc') {
            return aStr.localeCompare(bStr);
          } else {
            return bStr.localeCompare(aStr);
          }
        }
      });

      return sorted;
    }
  },
  setup() {
    const isOpenRef = ref(false);
    const edit_id = ref("");
    const edit = (id) => {
      isOpenRef.value = true;
      edit_id.value = id;
    };
    const closeModal = (state) => {
      isOpenRef.value = state;
    };
    return { isOpenRef, edit, closeModal, edit_id };
  },
  watch: {
    isOpenRef(newVal) {
      if (newVal && this.edit_id) {
        this.loadEditFormData();
      }
    },
    '$route.params.table'() {
      this.loadData();
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    toggleFormView() {
      this.showForm = !this.showForm;
    },
    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },
    toggleExportDropdown() {
      this.exportDropdownOpen = !this.exportDropdownOpen;
    },
    sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }
    },
    handleSubmit(data) {
      this.$axios
        .post(
          "v2/tables/submit",
          {
            table: JSON.stringify(data),
            table_name: this.$route.params.table,
            project: this.$route.params.project,
          }
        )
        .then(() => {
          this.loadData();
          this.showForm = false;
        });
    },
    async loadEditFormData() {
      try {
        const formResponse = await this.$axios.get(
          "v2/tables/schema", {
          params:
          {
            table_name: this.$route.params.table,
            project: this.$route.params.project,
          }
        }
        );

        const entryResponse = await this.$axios.get(
          `v2/tables/entry/${this.edit_id}`, {
          params:
          {
            table_name: this.$route.params.table,
            project: this.$route.params.project,
          }
        }
        );

        this.editFormData = formResponse.data.schema || [];
        const entryData = entryResponse.data.entry || {};

        this.editFormValues = {};
        this.editFormData.forEach(field => {
          this.editFormValues[field.name] = entryData[field.name] || '';
        });

      } catch (error) {
        console.error('Error loading edit form data:', error);
        this.editFormData = this.labels.map((label) => ({
          name: label.toLowerCase().replace(/\s+/g, '_'),
          label: label,
          type: 'text'
        }));

        const currentRow = this.data.find(row => row[0] == this.edit_id);
        this.editFormValues = {};
        if (currentRow) {
          this.editFormData.forEach((field, index) => {
            this.editFormValues[field.name] = currentRow[index] || '';
          });
        }
      }
    },
    saveEdit() {
      this.$axios
        .put(
          `v2/tables/entry/${this.edit_id}`,
          {
            entry_id: this.edit_id,
            table: JSON.stringify(this.editFormValues),
            table_name: this.$route.params.table,
            project: this.$route.params.project,
          }
        )
        .then(() => {
          this.closeModal(false);
          this.loadData();
          this.editFormData = [];
          this.editFormValues = {};
        })
        .catch(error => {
          console.error('Error saving edit:', error);
        });
    },
    deletee(id) {
      this.$axios
        .delete(
          `v2/tables/entry/${id}`,
          {
            params: {
              table_name: this.$route.params.table,
              project: this.$route.params.project
            }
          }
        )
        .then(() => {
          this.loadData();
        });
    },
    async exportData(format) {
      this.exportDropdownOpen = false;

      const url = format === 'csv'
        ? `v2/tables/export/csv`
        : `v2/tables/export/excel`;

      try {
        const response = await this.$axios.get(url, {
          params: {
            table_name: this.$route.params.table,
            project: this.$route.params.project
          },
          responseType: 'blob'
        });

        const blob = new Blob([response.data], {
          type: format === 'csv'
            ? 'text/csv; charset=utf-8'
            : 'application/vnd.ms-excel'
        });

        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;

        const fileName = `${this.$route.params.table}_export_${new Date().toISOString().split('T')[0]}.${format === 'csv' ? 'csv' : 'xls'}`;
        link.setAttribute('download', fileName);

        document.body.appendChild(link);
        link.click();

        link.remove();
        window.URL.revokeObjectURL(downloadUrl);
      } catch (error) {
        console.error('Export error:', error);
        alert('Export failed. Please try again.');
      }
    },
    openTriggerModal() {
      this.triggerModalOpen = true;
    },
    openRenameModal() {
      this.renameModalOpen = true;
    },
    openDeleteModal() {
      this.deleteModalOpen = true;
    },
    openEditModal() {
      this.$router.push({
        path: `/project/${this.$route.params.project}/tables/${this.$route.params.table}/edit`
      });
    },
    handleRenameSuccess(newTableName) {
      this.renameModalOpen = false;
      this.$router.push({
        name: 'TableDisplay',
        params: {
          project: this.$route.params.project,
          table: newTableName
        }
      });
    },
    handleDeleteSuccess() {
      this.deleteModalOpen = false;
      this.refreshSidebar();
      this.$router.push({
        name: 'ManageTables',
        params: {
          project: this.$route.params.project
        }
      });
    },
    refreshSidebar() {
      this.emitter.emit("updateSidebar");
    },
    loadData() {
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.table.replaceAll("-", "_")}`;
      this.$axios
        .post(
          `mysql.php`,
          this.$qs.stringify({ getTableByName: table_name, limit: 30 })
        )
        .then((res) => {
          this.labels = res.data.labels;
          this.data = res.data.data;
          this.load_more_btn = res.data.load_more_btn;
          this.current_limit = 1;
          this.loadImagePreviews();
        });

      this.$axios
        .get(
          `v2/tables/info`,
          {
            params: {
              table_name: this.$route.params.table,
              project: this.$route.params.project,
            }
          }
        )
        .then((res) => {
          this.form2 = res.data || {};
          this.projectID = res.data ? res.data.projectID : null;
          this.loadImagePreviews();
        });
    },
    loadMore() {
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.table}`;
      this.$axios.post("mysql.php", this.$qs.stringify({ load_more: "load_more", current_limit: this.current_limit, table: table_name })).then((res) => {
        this.current_limit = this.current_limit + 1;

        res.data.data.forEach(element => {
          this.data.push(element);
        });
        this.loadImagePreviews();
      });
    },
    isImageValue(val) {
      return typeof val === 'string' && !/\s/.test(val) && /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(val);
    },
    getSignedImageUrl(val) {
      return this.signedUrls[val] || '';
    },
    getThumbUrl(val) {
      return this.thumbUrls[val] || '';
    },
    onCellImageError(val) {
      delete this.signedUrls[val];
      delete this.thumbUrls[val];
    },
    openImagePreview(url) {
      this.imagePreviewUrl = url;
    },
    async loadImagePreviews() {
      if (!this.projectID || !Array.isArray(this.data)) return;

      const seen = new Set();
      const files = [];
      this.data.forEach(row => {
        (row || []).forEach(cell => {
          if (this.isImageValue(cell) && !this.signedUrls[cell] && !seen.has(cell)) {
            seen.add(cell);
            files.push({ path: cell, location: cell, projectID: this.projectID });
          }
        });
      });

      if (files.length === 0) return;

      try {
        const res = await this.$axios.post('signed_url_generator.php', {
          files,
          validitySeconds: 3600,
        });
        if (res.data && res.data.success) {
          res.data.urls.forEach(item => {
            this.signedUrls[item.originalPath] = item.signedUrl;
            this.thumbUrls[item.originalPath] = `${item.signedUrl}&w=150`;
          });
        }
      } catch (error) {
        console.error('Error loading image previews:', error);
      }
    }
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

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.action-group-left,
.action-group-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn ion-icon {
  font-size: 16px;
}

.dropdown {
  position: relative;
}

.dropdown-toggle {
  padding: 10px 12px;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  min-width: 180px;
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px);
  transition: all 0.2s ease;
}

.dropdown-menu.active {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  color: var(--text-primary) !important;
  text-decoration: none;
  font-size: 14px;
  cursor: pointer;
  border-bottom: 1px solid var(--border);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background: var(--background);
}

.dropdown-item ion-icon {
  font-size: 16px;
  color: var(--text-secondary);
}

.dropdown-item-danger {
  color: var(--danger-color) !important;
}

.dropdown-item-danger ion-icon {
  color: var(--danger-color);
}

.dropdown-item-danger:hover {
  background: rgba(220, 38, 38, 0.08);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-box input {
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--background);
  color: var(--text-primary);
  min-width: 250px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: max-content;
  min-width: 100%;
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
  flex: 0 0 120px;
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
}

.actions-cell {
  flex: 0 0 120px;
  justify-content: center;
  padding: 12px 16px;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

.cell-image-preview {
  width: 44px;
  height: 44px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--border);
  cursor: zoom-in;
  transition: transform 0.15s ease;
}

.cell-image-preview:hover {
  transform: scale(1.05);
}

.image-lightbox {
  position: fixed;
  inset: 0;
  z-index: 20000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.8);
  cursor: zoom-out;
  padding: 40px;
}

.image-lightbox-img {
  max-width: 90vw;
  max-height: 90vh;
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
}

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

.load-more-container {
  padding: 24px;
  text-align: center;
  border-top: 1px solid var(--border);
}

.load-more-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: var(--surface);
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.load-more-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background: #fff7ed;
}

.form-section {
  position: fixed;
  top: 0;
  right: -600px;
  width: 600px;
  height: 100vh;
  background: var(--surface);
  box-shadow: var(--shadow-lg);
  transition: right 0.3s ease;
  z-index: 1000;
  border-left: 1px solid var(--border);
}

.form-section.form-visible {
  right: 0;
}

.form-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.form-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.close-form-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: var(--border);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-form-btn:hover {
  background: var(--text-muted);
  color: var(--surface);
}

.form-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

@media (prefers-color-scheme: dark) {
  .search-box input {
    background: var(--background);
    color: var(--text-primary);
  }
}

:global(.modern-trigger-modal .modal-content),
:global(.modern-rename-modal .modal-content) {
  --ion-backdrop-opacity: 0;
  --ion-backdrop-color: transparent;
}

:global(.modern-trigger-modal ion-modal),
:global(.modern-rename-modal ion-modal) {
  --background: transparent !important;
  --backdrop-opacity: 0 !important;
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    flex-wrap: wrap;
    justify-content: center;
  }

  .search-box input {
    min-width: 100%;
  }

  .header-cell,
  .table-cell {
    min-width: 100px;
    padding: 12px 8px;
    font-size: 12px;
  }

  .form-section {
    width: 100%;
    right: -100%;
  }

  .dropdown-menu {
    right: auto;
    left: 0;
  }
}

@media (max-width: 480px) {
  .cell-content {
    max-width: 80px;
  }
}

.modern-edit-form {
  width: 100%;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-textarea,
.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.modern-input:focus,
.modern-textarea:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-textarea {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
}

.modern-select {
  cursor: pointer;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--text-primary);
}

.modern-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
</style>
