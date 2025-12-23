<template>
  <ion-page>
    <ion-content class="modern-content">

      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Form Tables</h1>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshTables">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>
        </div>

        <!-- Simple Stats Bar -->
        <div class="stats-bar">
          <div class="stat-item">
            <span class="stat-value">{{ tables.length }}</span>
            <span class="stat-label">Tables</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ totalRows }}</span>
            <span class="stat-label">Records</span>
          </div>
        </div>

        <!-- Tables List -->
        <div class="tables-card">
          <div class="card-header">
            <h2>Form Tables</h2>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input 
                type="text" 
                placeholder="Search tables..." 
                v-model="searchTerm"
              >
            </div>
          </div>

          <div class="tables-container">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading tables...</p>
            </div>

            <div v-else-if="filteredTables.length === 0" class="no-data-state">
              <ion-icon name="layers-outline" class="no-data-icon"></ion-icon>
              <h3>No Tables Found</h3>
              <p>{{ searchTerm ? 'No tables match your search criteria.' : 'No form tables exist for this project yet.' }}</p>
            </div>

            <div v-else class="tables-grid">
              <div 
                v-for="table in filteredTables" 
                :key="table.name"
                class="table-card"
                :class="{
                  'table-active': table.exists,
                  'table-inactive': !table.exists
                }"
              >
                <div class="table-header">
                  <div class="table-status">
                    <ion-icon 
                      :name="table.exists ? 'checkmark-circle' : 'alert-circle'"
                      :class="table.exists ? 'status-active' : 'status-inactive'"
                    ></ion-icon>
                    <span class="table-name">{{ table.name }}</span>
                  </div>
                  <div class="table-actions">
                    <button 
                      class="icon-btn view-btn" 
                      @click="viewTable(table.name)"
                      :disabled="!table.exists"
                      title="View Data"
                    >
                      <ion-icon name="eye-outline"></ion-icon>
                    </button>
                    <button 
                      class="icon-btn edit-btn" 
                      @click="editForm(table.name)"
                      title="Edit Form"
                    >
                      <ion-icon name="create-outline"></ion-icon>
                    </button>
                    <button 
                      class="icon-btn delete-btn" 
                      @click="confirmDelete(table)"
                      title="Delete Table"
                    >
                      <ion-icon name="trash-outline"></ion-icon>
                    </button>
                  </div>
                </div>

                <div class="table-details">
                  <div class="detail-item">
                    <span class="detail-label">Records:</span>
                    <span class="detail-value">{{ table.row_count.toLocaleString() }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Fields:</span>
                    <span class="detail-value">{{ table.field_count }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">{{ formatDate(table.created_at) }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Table:</span>
                    <span class="detail-value table-name-code">{{ table.table_name }}</span>
                  </div>
                </div>

                <div v-if="!table.exists" class="table-warning">
                  <ion-icon name="warning-outline"></ion-icon>
                  <span>Database table missing - form exists but no data table found</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="deleteModal.show" class="custom-modal-overlay" @click="deleteModal.show = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Delete Table</h3>
            <button class="modal-close-btn" @click="deleteModal.show = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="warning-content">
              <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
              <h4>Are you sure?</h4>
              <p>This will permanently delete the form <strong>"{{ deleteModal.table?.name }}"</strong> and all its data ({{ deleteModal.table?.row_count }} records).</p>
              <p class="warning-text">This action cannot be undone!</p>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="deleteModal.show = false">
                Cancel
              </button>
              <button class="action-btn danger" @click="deleteTable()">
                Delete Permanently
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "ManageTables",
  data() {
    return {
      tables: [],
      loading: true,
      searchTerm: '',
      deleteModal: {
        show: false,
        table: null
      }
    };
  },
  computed: {
    filteredTables() {
      if (!this.searchTerm.trim()) {
        return this.tables;
      }
      
      const searchLower = this.searchTerm.toLowerCase();
      return this.tables.filter(table =>
        table.name.toLowerCase().includes(searchLower) ||
        table.table_name.toLowerCase().includes(searchLower)
      );
    },
    totalRows() {
      return this.tables.reduce((sum, table) => sum + Number(table.row_count), 0);
    },
    activeTables() {
      return this.tables.filter(table => table.exists).length;
    },
    inactiveTables() {
      return this.tables.filter(table => !table.exists).length;
    }
  },
  created() {
    this.loadTables();
  },
  methods: {
    async loadTables() {
      this.loading = true;
      try {
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            get_all_tables: "get_all_tables",
            project: this.$route.params.project,
          })
        );
        
        if (response.data.success) {
          this.tables = response.data.tables;
        } else {
          console.error('Error loading tables:', response.data.error);
          this.tables = [];
        }
      } catch (error) {
        console.error('Error loading tables:', error);
        this.tables = [];
      } finally {
        this.loading = false;
      }
    },
    refreshTables() {
      this.loadTables();
    },
    viewTable(formName) {
      this.$router.push({
        path: `/project/${this.$route.params.project}/forms/${formName}`
      });
    },
    editForm(formName) {
      this.$router.push({
        path: `/project/${this.$route.params.project}/forms/${formName}/edit`
      });
    },
    confirmDelete(table) {
      this.deleteModal.table = table;
      this.deleteModal.show = true;
    },
    async deleteTable() {
      if (!this.deleteModal.table) return;
      
      try {
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            drop_table: "drop_table",
            form_name: this.deleteModal.table.name,
            project: this.$route.params.project,
          })
        );
        
        if (response.data.success) {
          // Remove from local array
          this.tables = this.tables.filter(t => t.name !== this.deleteModal.table.name);
          this.deleteModal.show = false;
          this.deleteModal.table = null;
          
          // Emit sidebar refresh
          this.emitter.emit("updateSidebar");
        } else {
          alert('Error deleting table: ' + response.data.error);
        }
      } catch (error) {
        console.error('Error deleting table:', error);
        alert('Error deleting table');
      }
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
  },
});
</script>

<style scoped>
/* Mobile-First Design System */
.modern-content {
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --radius: 12px;
}

.page-container {
  padding: 16px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
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
  font-size: 16px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  -webkit-tap-highlight-color: transparent;
  min-height: 44px;
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

/* Stats Bar */
.stats-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  padding: 12px;
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.stat-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-label {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}

/* Tables Card */
.tables-card {
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid var(--border);
}

.card-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-secondary);
  font-size: 18px;
  z-index: 1;
}

.search-box input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--background);
  color: var(--text-primary);
  min-height: 44px;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
}

/* Tables Container */
.tables-container {
  padding: 16px;
}

.loading-state,
.no-data-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 40px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

.no-data-icon {
  font-size: 48px;
  color: var(--text-secondary);
  margin-bottom: 12px;
  opacity: 0.5;
}

.no-data-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.no-data-state p {
  margin: 0;
  font-size: 14px;
}

/* Tables Grid - Mobile First */
.tables-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.table-card.table-active {
  border-left: 3px solid var(--success-color);
}

.table-card.table-inactive {
  border-left: 3px solid var(--warning-color);
  opacity: 0.6;
}

.table-info {
  flex: 1;
  min-width: 0;
}

.table-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.table-name {
  font-weight: 600;
  font-size: 15px;
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.status-active {
  color: var(--success-color);
  font-size: 18px;
  flex-shrink: 0;
}

.status-inactive {
  color: var(--warning-color);
  font-size: 18px;
  flex-shrink: 0;
}

.table-meta {
  font-size: 13px;
  color: var(--text-secondary);
}

.table-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.action-btn-mobile {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: var(--radius);
  font-size: 18px;
  -webkit-tap-highlight-color: transparent;
}

.action-btn-mobile:disabled {
  opacity: 0.3;
}

.view-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn {
  background: #f0fdf4;
  color: var(--success-color);
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

/* Modal Styles */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 500px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  -webkit-tap-highlight-color: transparent;
}

.custom-modal-body {
  padding: 20px;
}

.warning-content {
  text-align: center;
  margin-bottom: 24px;
}

.warning-icon {
  font-size: 48px;
  color: var(--warning-color);
  margin-bottom: 16px;
}

.warning-content h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.warning-content p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

.warning-text {
  color: var(--danger-color);
  font-weight: 600;
}

.form-actions {
  display: flex;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.form-actions .action-btn {
  width: 100%;
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

</style>
