<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="settings-outline" title="Store Management" />

      <div class="page-container">
        <PageHeader icon="settings-outline" title="Store Management">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="loadModules">Refresh</ActionButton>
            <ActionButton variant="primary" icon="add-outline" @click="showCreateModal = true">New Module</ActionButton>
          </template>
        </PageHeader>

        <div class="stats-grid">
          <StatCard icon="cube-outline" color="primary" :value="modules.length" label="Total Modules" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="activeModules" label="Active" />
          <StatCard icon="download-outline" color="info" :value="totalInstalls" label="Total Installs" />
        </div>

        <DataCard title="All Modules"
          :subtitle="`${filteredModules.length} module${filteredModules.length !== 1 ? 's' : ''}`" no-padding>
          <template #actions>
            <SearchBox v-model="searchTerm" placeholder="Search modules..." />
          </template>

          <div class="table-wrapper">
            <LoadingState v-if="loading" message="Loading modules..." />

            <EmptyState v-else-if="filteredModules.length === 0" icon="cube-outline" title="No Modules Found"
              :description="searchTerm ? 'No modules match your search.' : 'No modules available.'" />

            <div v-else class="modern-table">
              <div class="table-header">
                <div class="header-cell">Icon</div>
                <div class="header-cell">Name</div>
                <div class="header-cell">Description</div>
                <div class="header-cell">Status</div>
                <div class="header-cell actions-header">Actions</div>
              </div>

              <div class="table-body">
                <div v-for="module in filteredModules" :key="module.id" class="table-row">
                  <div class="table-cell cell-icon">
                    <div class="module-icon-wrapper">
                      <ion-icon :name="module.icon || 'cube-outline'" class="module-icon"></ion-icon>
                    </div>
                  </div>

                  <div class="table-cell cell-name">
                    <span class="cell-content">{{ module.display_name || module.name }}</span>
                  </div>

                  <div class="table-cell cell-description">
                    <span class="cell-content">{{ module.description || 'No description' }}</span>
                  </div>

                  <div class="table-cell cell-status">
                    <span class="status-badge" :class="module.active ? 'status-active' : 'status-inactive'">
                      {{ module.active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>

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
        </DataCard>
      </div>

      <AppModal v-model="showCreateModal" title="Create New Module">
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
          <textarea v-model="newModule.description" class="modern-input" rows="3"
            placeholder="Enter module description"></textarea>
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
        <template #footer>
          <ActionButton variant="secondary" @click="showCreateModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="createModule()">Create Module</ActionButton>
        </template>
      </AppModal>

      <AppModal v-model="showEditModal" title="Edit Module">
        <div class="form-group">
          <label class="form-label">Module Name *</label>
          <input type="text" v-model="editModuleData.name" class="modern-input" placeholder="Enter module name" />
        </div>
        <div class="form-group">
          <label class="form-label">Display Name *</label>
          <input type="text" v-model="editModuleData.display_name" class="modern-input"
            placeholder="Enter display name" />
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea v-model="editModuleData.description" class="modern-input" rows="3"
            placeholder="Enter module description"></textarea>
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
        <template #footer>
          <ActionButton variant="secondary" @click="showEditModal = false">Cancel</ActionButton>
          <ActionButton variant="primary" @click="saveModuleEdit()">Save Changes</ActionButton>
        </template>
      </AppModal>

      <div v-if="successMessage" class="success-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        {{ successMessage }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import DataCard from "@/components/DataCard.vue";
import SearchBox from "@/components/SearchBox.vue";
import LoadingState from "@/components/LoadingState.vue";
import EmptyState from "@/components/EmptyState.vue";
import ActionButton from "@/components/ActionButton.vue";
import AppModal from "@/components/AppModal.vue";

export default {
  name: "ManageStore",
  components: {
    SiteTitle,
    StatCard,
    PageHeader,
    DataCard,
    SearchBox,
    LoadingState,
    EmptyState,
    ActionButton,
    AppModal,
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.table-wrapper {
  overflow-x: auto;
}

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

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
