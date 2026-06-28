<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="settings-outline" title="Store Management" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Store Management</h1>
            <p>Manage available modules in the store</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="loadModules">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn primary" @click="showCreateModal = true">
              <ion-icon name="add-outline"></ion-icon>
              New Module
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="cube-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ modules.length }}</h3>
              <p>Total Modules</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ activeModules }}</h3>
              <p>Active</p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="download-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ totalInstalls }}</h3>
              <p>Total Installs</p>
            </div>
          </div>
        </div>

        <!-- Modules Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>All Modules</h3>
              <span class="entry-count">{{ filteredModules.length }} module{{ filteredModules.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Search modules..." v-model="searchTerm">
            </div>
          </div>

          <div class="table-wrapper">
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Loading modules...</p>
            </div>

            <div v-else-if="filteredModules.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="cube-outline" class="no-data-icon"></ion-icon>
                <h4>No Modules Found</h4>
                <p>{{ searchTerm ? 'No modules match your search.' : 'No modules available.' }}</p>
              </div>
            </div>

            <div v-else class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="header-cell">Icon</div>
                <div class="header-cell">Name</div>
                <div class="header-cell">Description</div>
                <div class="header-cell">Status</div>
                <div class="header-cell actions-header">Actions</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <div v-for="module in filteredModules" :key="module.id" class="table-row">
                  <!-- Icon -->
                  <div class="table-cell cell-icon">
                    <div class="module-icon-wrapper">
                      <ion-icon :name="module.icon || 'cube-outline'" class="module-icon"></ion-icon>
                    </div>
                  </div>

                  <!-- Name -->
                  <div class="table-cell cell-name">
                    <span class="cell-content">{{ module.display_name || module.name }}</span>
                  </div>

                  <!-- Description -->
                  <div class="table-cell cell-description">
                    <span class="cell-content">{{ module.description || 'No description' }}</span>
                  </div>

                  <!-- Status -->
                  <div class="table-cell cell-status">
                    <span class="status-badge" :class="module.active ? 'status-active' : 'status-inactive'">
                      {{ module.active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>

                  <!-- Actions -->
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="editModule(module)" title="Edit Module">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteModule(module)" title="Delete Module">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Module Modal -->
      <div v-if="showCreateModal" class="custom-modal-overlay" @click="showCreateModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Create New Module</h3>
            <button class="modal-close-btn" @click="showCreateModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Module Name *</label>
              <input type="text" v-model="newModule.name" class="modern-input" placeholder="Enter module name" />
            </div>
            <div class="form-group">
              <label class="form-label">Display Name *</label>
              <input type="text" v-model="newModule.display_name" class="modern-input" placeholder="Enter display name" />
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea v-model="newModule.description" class="modern-input" rows="3" placeholder="Enter module description"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Icon Name</label>
              <input type="text" v-model="newModule.icon" class="modern-input" placeholder="e.g., cube-outline" />
              <p class="form-help">Use Ionicons icon name (e.g., cube-outline, analytics-outline)</p>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select v-model="newModule.active" class="modern-select">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showCreateModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="createModule()">
                Create Module
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Module Modal -->
      <div v-if="showEditModal" class="custom-modal-overlay" @click="showEditModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Edit Module</h3>
            <button class="modal-close-btn" @click="showEditModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Module Name *</label>
              <input type="text" v-model="editModuleData.name" class="modern-input" placeholder="Enter module name" />
            </div>
            <div class="form-group">
              <label class="form-label">Display Name *</label>
              <input type="text" v-model="editModuleData.display_name" class="modern-input" placeholder="Enter display name" />
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea v-model="editModuleData.description" class="modern-input" rows="3" placeholder="Enter module description"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Icon Name</label>
              <input type="text" v-model="editModuleData.icon" class="modern-input" placeholder="e.g., cube-outline" />
              <p class="form-help">Use Ionicons icon name</p>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select v-model="editModuleData.active" class="modern-select">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button class="action-btn primary" @click="saveModuleEdit()">
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Toast -->
      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";

export default {
  name: "ManageStore",
  components: {
    SiteTitle,
  },
  data() {
    return {
      modules: [],
      searchTerm: "",
      loading: true,
      showCreateModal: false,
      showEditModal: false,
      successMessage: "",
      newModule: {
        name: "",
        display_name: "",
        description: "",
        icon: "",
        active: true
      },
      editModuleData: {
        id: null,
        name: "",
        display_name: "",
        description: "",
        icon: "",
        active: true
      }
    };
  },
  computed: {
    filteredModules() {
      if (!this.searchTerm) return this.modules;
      
      const term = this.searchTerm.toLowerCase();
      return this.modules.filter(module => 
        (module.name && module.name.toLowerCase().includes(term)) ||
        (module.display_name && module.display_name.toLowerCase().includes(term)) ||
        (module.description && module.description.toLowerCase().includes(term))
      );
    },
    activeModules() {
      return this.modules.filter(m => m.active).length;
    },
    totalInstalls() {
      return this.modules.reduce((sum, m) => sum + (m.install_count || 0), 0);
    }
  },
  async created() {
    await this.loadModules();
  },
  methods: {
    async loadModules() {
      this.loading = true;

      try {
        const response = await this.$axios.post(
          "install.php",
          this.$qs.stringify({
            getAvailableModules: "getAvailableModules"
          })
        );

        if (response.data) {
          this.modules = response.data.map(module => ({
            ...module,
            active: module.active !== false
          }));
        }
      } catch (error) {
        console.error('Error loading modules:', error);
        alert("Failed to load modules");
      } finally {
        this.loading = false;
      }
    },

    async createModule() {
      if (!this.newModule.name || !this.newModule.display_name) {
        alert('Please fill in all required fields');
        return;
      }

      try {
        const response = await this.$axios.post(
          "install.php",
          this.$qs.stringify({
            createModule: "createModule",
            name: this.newModule.name,
            display_name: this.newModule.display_name,
            description: this.newModule.description,
            icon: this.newModule.icon,
            active: this.newModule.active
          })
        );

        if (response.data.success) {
          this.showSuccessMessage('Module created successfully');
          this.showCreateModal = false;
          this.resetNewModule();
          this.loadModules();
        } else {
          alert('Error creating module: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error creating module:', error);
        alert('Error creating module');
      }
    },

    editModule(module) {
      this.editModuleData = {
        id: module.id,
        name: module.name || '',
        display_name: module.display_name || '',
        description: module.description || '',
        icon: module.icon || '',
        active: module.active !== false
      };
      this.showEditModal = true;
    },

    async saveModuleEdit() {
      if (!this.editModuleData.name || !this.editModuleData.display_name) {
        alert('Please fill in all required fields');
        return;
      }

      try {
        const response = await this.$axios.post(
          "install.php",
          this.$qs.stringify({
            updateModule: "updateModule",
            moduleID: this.editModuleData.id,
            name: this.editModuleData.name,
            display_name: this.editModuleData.display_name,
            description: this.editModuleData.description,
            icon: this.editModuleData.icon,
            active: this.editModuleData.active
          })
        );

        if (response.data.success) {
          this.showSuccessMessage('Module updated successfully');
          this.showEditModal = false;
          this.loadModules();
        } else {
          alert('Error updating module: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error updating module:', error);
        alert('Error updating module');
      }
    },

    async deleteModule(module) {
      if (!confirm(`Are you sure you want to delete "${module.display_name || module.name}"?`)) {
        return;
      }

      try {
        const response = await this.$axios.post(
          "install.php",
          this.$qs.stringify({
            deleteModule: "deleteModule",
            moduleID: module.id
          })
        );

        if (response.data.success) {
          this.showSuccessMessage('Module deleted successfully');
          this.loadModules();
        } else {
          alert('Error deleting module: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error deleting module:', error);
        alert('Error deleting module');
      }
    },

    resetNewModule() {
      this.newModule = {
        name: "",
        display_name: "",
        description: "",
        icon: "",
        active: true
      };
    },

    showSuccessMessage(message) {
      this.successMessage = message;
      setTimeout(() => {
        this.successMessage = "";
      }, 3000);
    }
  },
};
</script>

<style scoped>
.modern-content {
  --background: #f5f7fa;
  --surface: #ffffff;
  --border: #e1e4e8;
  --text-primary: #24292f;
  --text-secondary: #57606a;
  --text-muted: #8c959f;
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --radius: 8px;
  --radius-lg: 12px;
  --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

/* Header */
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

.header-actions {
  display: flex;
  gap: 12px;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
}

.action-btn.primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover {
  background: var(--background);
  color: var(--text-primary);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: rgba(37, 99, 235, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 24px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: var(--background);
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.entry-count {
  padding: 4px 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

/* Search Box */
.search-box {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 280px;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 18px;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
}

/* Loading & Empty States */
.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.4;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0;
  font-size: 14px;
}

/* Modern Table */
.modern-table {
  display: flex;
  flex-direction: column;
  min-width: 800px;
}

.table-header,
.table-row {
  display: grid;
  grid-template-columns: 80px 200px 1fr 120px 140px;
  border-bottom: 1px solid var(--border);
}

.table-header {
  background: var(--background);
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.table-row {
  background: var(--surface);
  transition: background 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.header-cell,
.table-cell {
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.actions-cell {
  justify-content: center;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Module Icon in Table */
.cell-icon .module-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  background: var(--background);
  display: flex;
  align-items: center;
  justify-content: center;
}

.cell-icon .module-icon {
  font-size: 24px;
  color: var(--primary-color);
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-inactive {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
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
}

.edit-btn {
  background: rgba(59, 130, 246, 0.12);
  color: var(--primary-color);
}

.edit-btn:hover {
  background: rgba(59, 130, 246, 0.22);
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
  width: 600px;
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
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

/* Form Styles */
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
  font-family: inherit;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

textarea.modern-input {
  resize: vertical;
  min-height: 80px;
}

.form-help {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-secondary);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
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

/* Animations */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
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

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    min-width: 100%;
  }

  .custom-modal-content {
    width: 95vw;
    margin: 20px;
  }
}
</style>
