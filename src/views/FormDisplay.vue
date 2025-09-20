<template>
  <ion-page>
    <ion-content class="modern-content">
                  <SiteTitle v-if="true" icon="person-outline" title="Yyyyy"/>

      <div class="page-container">

        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn primary" @click="toggleFormView">
              <ion-icon name="add-outline"></ion-icon>
              <span>Add Entry</span>
            </button>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn secondary" @click="exportCSV()">
              <ion-icon name="download-outline"></ion-icon>
              <span>Export CSV</span>
            </button>
            <button class="action-btn secondary" @click="openTriggerModal()">
              <ion-icon name="notifications-outline"></ion-icon>
              <span>Triggers</span>
            </button>
            <div class="dropdown">
              <button class="action-btn secondary dropdown-toggle" @click="toggleDropdown">
                <ion-icon name="ellipsis-vertical-outline"></ion-icon>
              </button>
              <div class="dropdown-menu" :class="{ active: dropdownOpen }">
                <a @click="openRenameModal()" class="dropdown-item">
                  <ion-icon name="create-outline"></ion-icon>
                  Rename Form
                </a>
                <a @click="openEditModal()" class="dropdown-item">
                  <ion-icon name="settings-outline"></ion-icon>
                  Edit Form
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Data Table Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Data Overview</h3>
              <span class="entry-count">{{ data.length }} entries</span>
            </div>
            <div class="header-right">
              <div class="search-box">
                <ion-icon name="search-outline"></ion-icon>
                <input 
                  type="text" 
                  placeholder="Search entries..." 
                  v-model="searchTerm"
                  @input="handleSearch"
                >
              </div>
            </div>
          </div>

          <div class="table-wrapper">
            <div class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div 
                  v-for="(label, index) in labels" 
                  :key="label"
                  class="header-cell"
                  @click="sortBy(index)"
                >
                  <span class="header-text">{{ label }}</span>
                  <div class="sort-indicator">
                    <ion-icon 
                      v-if="sortColumn === index && sortDirection === 'asc'" 
                      name="chevron-up-outline"
                      class="sort-active"
                    ></ion-icon>
                    <ion-icon 
                      v-else-if="sortColumn === index && sortDirection === 'desc'" 
                      name="chevron-down-outline"
                      class="sort-active"
                    ></ion-icon>
                    <ion-icon 
                      v-else 
                      name="swap-vertical-outline" 
                      class="sort-default"
                    ></ion-icon>
                  </div>
                </div>
                <div class="header-cell actions-header">Actions</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <div 
                  v-for="(tr, rowIndex) in sortedData" 
                  :key="rowIndex"
                  class="table-row"
                  :class="{ 'row-hover': true }"
                >
                  <div 
                    v-for="(td, colIndex) in tr" 
                    :key="colIndex"
                    class="table-cell"
                  >
                    <span class="cell-content">{{ td }}</span>
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

          <!-- Load More Button -->
          <div v-if="load_more_btn" class="load-more-container">
            <button class="load-more-btn" @click="loadMore()">
              <ion-icon name="chevron-down-outline"></ion-icon>
              Load More Entries
            </button>
          </div>
        </div>

        <!-- Form Section -->
        <div class="form-section" :class="{ 'form-visible': showForm }">
          <div class="form-card">
            <div class="form-header">
              <h3>Add New Entry</h3>
              <button class="close-form-btn" @click="toggleFormView">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <div class="form-content">
              <DisplayForm @submit="handleSubmit" />
            </div>
          </div>
        </div>
      </div>
      <ion-modal
        :is-open="isOpenRef"
        css-class="my-custom-class"
        @didDismiss="closeModal(false)"
      >
        <EditEntry
          @submit="handleEdit"
          :data="{
            id: edit_id,
            form: $route.params.form,
            project: $route.params.project,
          }"
        />
      </ion-modal>
      <ion-modal
        :is-open="triggerModalOpen"
        css-class="modern-trigger-modal"
        @didDismiss="triggerModalOpen = false"
      >
        <TriggerManager 
          :project="$route.params.project"
          :form="$route.params.form"
          @close="triggerModalOpen = false"
        />
      </ion-modal>
      <ion-modal
        :is-open="renameModalOpen"
        css-class="modern-rename-modal"
        @didDismiss="renameModalOpen = false"
      >
        <RenameForm 
          :project="$route.params.project"
          :form="$route.params.form"
          @close="renameModalOpen = false"
          @success="handleRenameSuccess"
          @sidebarRefresh="refreshSidebar"
        />
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
//lang="ts"
import DisplayForm from "@/components/DisplayForm.vue";
import EditEntry from "@/components/EditEntry.vue";
import TriggerManager from "@/components/TriggerManager.vue";
import RenameForm from "@/components/RenameForm_new.vue";
import { defineComponent, ref } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";

export default defineComponent({
  name: "FormDisplay",
  components: {
    DisplayForm,
    EditEntry,
    TriggerManager,
    RenameForm,
    SiteTitle,
  },
  data() {
    return {
      form: {},
      labels: [],
      data: [],
      load_more_btn: false,
      current_limit: 0,
      sortColumn: null,
      sortDirection: 'asc',
      triggerModalOpen: false,
      renameModalOpen: false,
      showForm: false,
      dropdownOpen: false,
      searchTerm: '',
    };
  },
  computed: {
    sortedData() {
      // First apply search filter
      let dataToSort = this.data;
      
      if (this.searchTerm.trim()) {
        const searchLower = this.searchTerm.toLowerCase();
        dataToSort = this.data.filter(row => 
          row.some(cell => 
            String(cell).toLowerCase().includes(searchLower)
          )
        );
      }
      
      // Then apply sorting
      if (this.sortColumn === null) {
        return dataToSort;
      }
      
      const sorted = [...dataToSort].sort((a, b) => {
        const aVal = a[this.sortColumn];
        const bVal = b[this.sortColumn];
        
        // Check if values are numbers
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
          // Numeric sort
          return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          // String sort
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
    }; //: number
    const closeModal = (state) => {
      isOpenRef.value = state;
    }; //: number
    return { isOpenRef, edit, closeModal, edit_id };
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
    handleSearch() {
      // Search is now handled in computed property, so we don't need this method
      // But we keep it in case we need custom search logic later
    },
    sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        // Toggle direction if same column
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        // New column, start with ascending
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }
    },
    handleSubmit(data) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            submit_form: "submit_form",
            form: JSON.stringify(data),
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.loadData();
          this.showForm = false; // Hide form after successful submission
        });
    },
    handleEdit(data) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            update_entry: "update_entry",
            entry_id: this.edit_id,
            form: JSON.stringify(data),
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.closeModal(false);
          this.loadData();
        });
    },
    deletee(id) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            delete_entry: "delete_entry",
            entry_id: id,
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.loadData();
        });
    },
    exportCSV() {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/control-center-3_2/backend/triggers.php'; // Fixed path
      form.target = '_blank';
      
      const exportField = document.createElement('input');
      exportField.type = 'hidden';
      exportField.name = 'export_csv';
      exportField.value = 'true';
      
      const projectField = document.createElement('input');
      projectField.type = 'hidden';
      projectField.name = 'project';
      projectField.value = this.$route.params.project;
      
      const formField = document.createElement('input');
      formField.type = 'hidden';
      formField.name = 'form_name';
      formField.value = this.$route.params.form;
      
      form.appendChild(exportField);
      form.appendChild(projectField);
      form.appendChild(formField);
      
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    },
    openTriggerModal() {
      this.triggerModalOpen = true;
    },
    openRenameModal() {
      this.renameModalOpen = true;
    },
    openEditModal() {
      // Navigate to edit form using the existing NewTool interface
      this.$router.push({
        path: `/project/${this.$route.params.project}/edit-form/${this.$route.params.form}`
      });
    },
    handleRenameSuccess(newFormName) {
      this.renameModalOpen = false;
      // Navigate to the new form URL
      this.$router.push({
        name: 'FormDisplay',
        params: {
          project: this.$route.params.project,
          form: newFormName
        }
      });
    },
    refreshSidebar() {
      // Emit event to refresh the sidebar
      this.emitter.emit("updateSidebar");
    },
    loadData() {
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.form.replaceAll("-", "_")}`;
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
        });
    },
    loadMore(){
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.form}`;
      this.$axios.post("mysql.php", this.$this.$qs.stringify({load_more: "load_more", current_limit: this.current_limit,table: table_name})).then((res) => {
        this.current_limit = this.current_limit+1;

        res.data.data.forEach(element =>{
          this.data.push(element);
        });
      });
    }
  },
});
</script>

<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Action Bar */
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

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Dropdown */
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
  color: var(--text-primary);
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

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
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

/* Modern Table */
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
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

/* Load More */
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
  background: #eff6ff;
}

/* Form Section */
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

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
  
  .search-box input {
    background: var(--background);
    color: var(--text-primary);
  }
}

/* Modal Integration */
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

/* Responsive Design */
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
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
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
  .modern-table {
    min-width: 600px;
  }
  
  .cell-content {
    max-width: 80px;
  }
}
</style>
