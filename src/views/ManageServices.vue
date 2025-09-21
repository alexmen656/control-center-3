<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <SiteTitle title="Manage Services" icon="construct-outline" />
        
        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="layers-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>Total Services</h3>
              <p class="stat-number">{{ services.length }}</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon active">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>Active Services</h3>
              <p class="stat-number">{{ activeServicesCount }}</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon warning">
              <ion-icon name="construct-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>Maintenance</h3>
              <p class="stat-number">{{ maintenanceServicesCount }}</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon danger">
              <ion-icon name="close-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>Inactive Services</h3>
              <p class="stat-number">{{ inactiveServicesCount }}</p>
            </div>
          </div>
        </div>

        <!-- Actions Bar -->
        <div class="actions-bar">
          <div class="search-container">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search services..."
              class="search-input"
            />
          </div>
          
          <div class="action-buttons">
            <button
              @click="$router.push(`/project/${$route.params.project}/new/service`)"
              class="btn btn-primary"
            >
              <ion-icon name="add-outline"></ion-icon>
              Add New Service
            </button>
            
            <button
              @click="toggleSelectMode"
              class="btn"
              :class="selectMode ? 'btn-secondary' : 'btn-outline'"
            >
              <ion-icon :name="selectMode ? 'close-outline' : 'checkbox-outline'"></ion-icon>
              {{ selectMode ? 'Cancel Selection' : 'Select Multiple' }}
            </button>
            
            <button
              v-if="selectMode && selectedServices.length > 0"
              @click="confirmBulkDelete"
              class="btn btn-danger"
            >
              <ion-icon name="trash-outline"></ion-icon>
              Delete Selected ({{ selectedServices.length }})
            </button>
          </div>
        </div>

        <!-- Services Grid -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Loading services...</p>
        </div>

        <div v-else-if="filteredServices.length === 0" class="no-data-state">
          <ion-icon name="construct-outline" class="no-data-icon"></ion-icon>
          <h3>{{ services.length === 0 ? 'No Services Found' : 'No Matching Services' }}</h3>
          <p>{{ services.length === 0 ? 'Create your first service to get started' : 'Try adjusting your search query' }}</p>
          <button
            v-if="services.length === 0"
            @click="$router.push(`/project/${$route.params.project}/new/service`)"
            class="btn btn-primary"
          >
            <ion-icon name="add-outline"></ion-icon>
            Add New Service
          </button>
        </div>

        <div v-else class="services-grid">
          <div
            v-for="service in filteredServices"
            :key="service.id"
            class="service-card"
            :class="{ 'selected': selectedServices.includes(service.id) }"
          >
            <!-- Selection Checkbox -->
            <div v-if="selectMode" class="selection-checkbox">
              <input
                type="checkbox"
                :checked="selectedServices.includes(service.id)"
                @change="toggleServiceSelection(service.id)"
                class="checkbox"
              />
            </div>

            <!-- Service Header -->
            <div class="service-header">
              <div class="service-icon-container">
                <ion-icon :name="service.icon || 'cog-outline'" class="service-icon"></ion-icon>
              </div>
              <div class="service-info">
                <h3>{{ service.name }}</h3>
                <p>{{ service.description || 'No description available' }}</p>
              </div>
              <div class="service-status">
                <span
                  class="status-badge"
                  :class="getStatusClass(service.status)"
                >
                  {{ getStatusText(service.status) }}
                </span>
              </div>
            </div>

            <!-- Service Actions -->
            <div class="service-actions">
              <button
                @click="viewService(service)"
                class="action-btn"
                title="View Service"
              >
                <ion-icon name="eye-outline"></ion-icon>
                View
              </button>
              <button
                @click="editService(service)"
                class="action-btn"
                title="Edit Service"
              >
                <ion-icon name="create-outline"></ion-icon>
                Edit
              </button>
              <button
                @click="viewServiceLogs(service)"
                class="action-btn"
                title="View Logs"
              >
                <ion-icon name="document-text-outline"></ion-icon>
                Logs
              </button>
              <button
                @click="confirmDelete(service)"
                class="action-btn danger"
                title="Delete Service"
              >
                <ion-icon name="trash-outline"></ion-icon>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Service Modal -->
      <ion-modal :is-open="isEditModalOpen" @didDismiss="closeEditModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>Edit Service</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeEditModal">Cancel</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="modal-content">
          <div class="modal-form">
            <div class="form-group">
              <label class="form-label">Service Icon</label>
              <div class="icon-input-group">
                <div class="icon-preview">
                  <ion-icon :name="editingService.icon || 'help-circle-outline'"></ion-icon>
                </div>
                <input
                  v-model="editingService.icon"
                  type="text"
                  class="form-input"
                  placeholder="Enter icon name"
                />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Service Name</label>
              <input
                v-model="editingService.name"
                type="text"
                class="form-input"
                placeholder="Enter service name"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea
                v-model="editingService.description"
                class="form-textarea"
                placeholder="Enter service description"
                rows="3"
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Status</label>
              <select v-model="editingService.status" class="form-select">
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div class="form-actions">
              <button @click="closeEditModal" class="btn btn-secondary">
                Cancel
              </button>
              <button @click="saveServiceChanges" class="btn btn-primary" :disabled="isSaving">
                <ion-icon :name="isSaving ? 'hourglass-outline' : 'save-outline'"></ion-icon>
                {{ isSaving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import SiteTitle from '@/components/SiteTitle.vue';

export default defineComponent({
  name: "ManageServices",
  components: {
    SiteTitle
  },
  data() {
    return {
      services: [],
      loading: true,
      searchQuery: '',
      isEditModalOpen: false,
      isSaving: false,
      editingService: {
        id: null,
        name: "",
        icon: "",
        description: "",
        status: "active"
      },
      selectedServices: [],
      selectMode: false
    };
  },
  computed: {
    filteredServices() {
      if (!this.searchQuery) return this.services;
      const query = this.searchQuery.toLowerCase();
      return this.services.filter(service =>
        service.name.toLowerCase().includes(query) ||
        (service.description && service.description.toLowerCase().includes(query)) ||
        service.status.toLowerCase().includes(query)
      );
    },
    activeServicesCount() {
      return this.services.filter(service => service.status === 'active').length;
    },
    maintenanceServicesCount() {
      return this.services.filter(service => service.status === 'maintenance').length;
    },
    inactiveServicesCount() {
      return this.services.filter(service => service.status === 'inactive').length;
    }
  },
  created() {
    this.loadServices();
  },
  methods: {
    async loadServices() {
      this.loading = true;
      try {
        const response = await this.$axios.post(
          "services.php",
          this.$qs.stringify({
            getServices: "getServices",
            project: this.$route.params.project
          })
        );
        this.services = response.data || [];
      } catch (error) {
        console.error("Error loading services:", error);
        this.$toast.error("Failed to load services");
      } finally {
        this.loading = false;
      }
    },
    
    viewService(service) {
      this.$router.push(`/project/${this.$route.params.project}/services/${service.link}`);
    },
    
    viewServiceLogs(service) {
      this.$router.push(`/project/${this.$route.params.project}/services/${service.link}`);
    },
    
    editService(service) {
      this.editingService = { ...service };
      this.isEditModalOpen = true;
    },
    
    closeEditModal() {
      this.isEditModalOpen = false;
      this.isSaving = false;
    },
    
    async saveServiceChanges() {
      if (!this.editingService.name.trim()) {
        this.$toast.error("Service name is required");
        return;
      }

      this.isSaving = true;
      
      try {
        const response = await this.$axios.post(
          "services.php",
          this.$qs.stringify({
            updateService: "updateService",
            serviceId: this.editingService.id,
            name: this.editingService.name.trim(),
            icon: this.editingService.icon || "cog-outline",
            description: this.editingService.description.trim(),
            status: this.editingService.status
          })
        );

        if (response.data === "success") {
          this.$toast.success("Service updated successfully");
          this.loadServices();
          this.emitter.emit("updateSidebar");
        } else {
          this.$toast.error("Error updating service: " + response.data);
        }
      } catch (error) {
        console.error("Error updating service:", error);
        this.$toast.error("Failed to update service");
      } finally {
        this.closeEditModal();
      }
    },
    
    confirmDelete(service) {
      if (confirm(`Are you sure you want to PERMANENTLY delete the service "${service.name}"?\n\nThis will also delete:\n- All API keys associated with this service\n- All logs and monitoring data\n- All configuration settings\n\nThis action cannot be undone!`)) {
        this.deleteService(service.id);
      }
    },
    
    async deleteService(serviceId) {
      try {
        const response = await this.$axios.post(
          "services.php",
          this.$qs.stringify({
            deleteServiceComplete: "deleteServiceComplete",
            serviceId: serviceId
          })
        );

        if (response.data === "success" || response.data.status === "success") {
          this.$toast.success("Service and all associated data deleted successfully");
          this.loadServices();
          this.emitter.emit("updateSidebar");
        } else {
          this.$toast.error("Error deleting service: " + (response.data.message || response.data));
        }
      } catch (error) {
        console.error("Error deleting service:", error);
        this.$toast.error("Failed to delete service");
      }
    },
    
    toggleSelectMode() {
      this.selectMode = !this.selectMode;
      if (!this.selectMode) {
        this.selectedServices = [];
      }
    },
    
    toggleServiceSelection(serviceId) {
      const index = this.selectedServices.indexOf(serviceId);
      if (index === -1) {
        this.selectedServices.push(serviceId);
      } else {
        this.selectedServices.splice(index, 1);
      }
    },
    
    async confirmBulkDelete() {
      if (this.selectedServices.length === 0) {
        this.$toast.error("No services selected");
        return;
      }
      
      if (confirm(`Are you sure you want to PERMANENTLY delete ${this.selectedServices.length} selected services?\n\nThis will also delete:\n- All API keys associated with these services\n- All logs and monitoring data\n- All configuration settings\n\nThis action cannot be undone!`)) {
        try {
          const deletePromises = this.selectedServices.map(serviceId => {
            return this.$axios.post(
              "services.php",
              this.$qs.stringify({
                deleteServiceComplete: "deleteServiceComplete",
                serviceId: serviceId
              })
            );
          });
          
          await Promise.all(deletePromises);
          this.$toast.success(`${this.selectedServices.length} services and all associated data deleted successfully`);
          this.selectedServices = [];
          this.selectMode = false;
          this.loadServices();
          this.emitter.emit("updateSidebar");
        } catch (error) {
          console.error("Error deleting services:", error);
          this.$toast.error("Failed to delete some services");
        }
      }
    },

    getStatusClass(status) {
      switch(status) {
        case 'active': return 'status-active';
        case 'maintenance': return 'status-maintenance';
        case 'inactive': return 'status-inactive';
        default: return 'status-unknown';
      }
    },

    getStatusText(status) {
      switch(status) {
        case 'active': return 'Active';
        case 'maintenance': return 'Maintenance';
        case 'inactive': return 'Inactive';
        default: return 'Unknown';
      }
    }
  }
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

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  background: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  flex-shrink: 0;
}

.stat-icon.active {
  background: var(--success-color);
}

.stat-icon.warning {
  background: var(--warning-color);
}

.stat-icon.danger {
  background: var(--danger-color);
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.stat-number {
  margin: 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

/* Actions Bar */
.actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 32px;
  background: var(--surface);
  padding: 20px 24px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.search-container {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 20px;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  background: var(--background);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-input::placeholder {
  color: var(--text-muted);
}

.action-buttons {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:not(:disabled):hover {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 2px solid var(--border);
}

.btn-secondary:not(:disabled):hover {
  background: var(--background);
  border-color: var(--text-secondary);
}

.btn-outline {
  background: transparent;
  color: var(--text-primary);
  border: 2px solid var(--border);
}

.btn-outline:not(:disabled):hover {
  background: var(--background);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.btn-danger {
  background: var(--danger-color);
  color: white;
}

.btn-danger:not(:disabled):hover {
  background: #b91c1c;
}

/* Loading and No Data States */
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
  opacity: 0.5;
}

.no-data-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-state p {
  margin: 0 0 16px 0;
  font-size: 14px;
}

/* Services Grid */
.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 24px;
}

.service-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 2px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
  transition: all 0.2s ease;
  position: relative;
}

.service-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.service-card.selected {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.selection-checkbox {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
}

.checkbox {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  border: 2px solid var(--border);
  background: var(--surface);
  cursor: pointer;
}

.checkbox:checked {
  background: var(--primary-color);
  border-color: var(--primary-color);
}

/* Service Header */
.service-header {
  padding: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.service-icon-container {
  flex-shrink: 0;
}

.service-icon {
  width: 48px;
  height: 48px;
  padding: 12px;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius-lg);
  font-size: 24px;
}

.service-info {
  flex: 1;
  min-width: 0;
}

.service-info h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
  line-height: 1.3;
}

.service-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.service-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: #f0fdf4;
  color: var(--success-color);
}

.status-maintenance {
  background: #fef3c7;
  color: var(--warning-color);
}

.status-inactive {
  background: #fef2f2;
  color: var(--danger-color);
}

.status-unknown {
  background: #f1f5f9;
  color: var(--text-muted);
}

/* Service Actions */
.service-actions {
  display: flex;
  padding: 16px 24px;
  background: var(--background);
  border-top: 1px solid var(--border);
  gap: 8px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn:hover {
  background: var(--border);
  color: var(--text-primary);
  transform: translateY(-1px);
}

.action-btn.danger {
  color: var(--danger-color);
}

.action-btn.danger:hover {
  background: #fef2f2;
  color: var(--danger-color);
}

/* Modal Styles */
.modal-content {
  background: var(--background);
}

.modal-form {
  max-width: 500px;
  margin: 0 auto;
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

.icon-input-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.icon-preview {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 24px;
}

.icon-input-group .form-input {
  flex: 1;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }

  .status-active {
    background: rgba(5, 150, 105, 0.2);
  }

  .status-maintenance {
    background: rgba(217, 119, 6, 0.2);
  }

  .status-inactive {
    background: rgba(220, 38, 38, 0.2);
  }

  .status-unknown {
    background: rgba(100, 116, 139, 0.2);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .actions-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .search-container {
    max-width: none;
  }
  
  .action-buttons {
    justify-content: stretch;
  }
  
  .btn {
    flex: 1;
    justify-content: center;
  }
  
  .services-grid {
    grid-template-columns: 1fr;
  }
  
  .service-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .service-actions {
    flex-wrap: wrap;
  }
  
  .form-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .service-actions {
    padding: 12px 16px;
  }
  
  .action-btn {
    flex: 1;
    justify-content: center;
  }
  
  .modal-form {
    padding: 16px;
  }
}
</style>