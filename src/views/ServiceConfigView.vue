<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <SiteTitle 
          :title="`Configure ${service?.name || 'Service'}`" 
          :icon="service?.icon || 'settings-outline'"
        />

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Loading service configuration...</p>
        </div>

        <!-- Service Not Found -->
        <div v-else-if="!service" class="no-data-state">
          <ion-icon name="alert-circle-outline" class="no-data-icon"></ion-icon>
          <h3>Service Not Found</h3>
          <p>The requested service could not be found or you don't have permission to configure it.</p>
          <button 
            class="btn btn-primary"
            @click="$router.push(`/project/${$route.params.project}`)"
          >
            <ion-icon name="arrow-back-outline"></ion-icon>
            Return to Project
          </button>
        </div>

        <!-- Service Configuration -->
        <div v-else class="config-container">
          <!-- Service Header -->
          <div class="service-header">
            <div class="service-info">
              <div class="service-icon">
                <ion-icon :name="service.icon || 'settings-outline'"></ion-icon>
              </div>
              <div class="service-details">
                <h1>{{ service.name }}</h1>
                <p>Configure service settings and manage its status</p>
              </div>
            </div>
            <div class="service-status">
              <span 
                class="status-badge"
                :class="getStatusClass(service.status)"
              >
                <ion-icon :name="getStatusIcon(service.status)"></ion-icon>
                {{ getStatusText(service.status) }}
              </span>
            </div>
          </div>

          <!-- Configuration Form -->
          <div class="config-form-container">
            <form @submit.prevent="saveChanges" class="config-form">
              <div class="form-header">
                <h2>Service Configuration</h2>
                <p>Update your service settings below. Changes will be applied immediately.</p>
              </div>

              <div class="form-grid">
                <!-- Service Name -->
                <div class="form-group">
                  <label class="form-label" for="service-name">Service Name</label>
                  <input
                    id="service-name"
                    v-model="service.name"
                    type="text"
                    class="form-input"
                    placeholder="Enter service name"
                    required
                  />
                  <div class="form-help">
                    The display name for this service in your project dashboard.
                  </div>
                </div>

                <!-- Service Icon -->
                <div class="form-group">
                  <label class="form-label" for="service-icon">Service Icon</label>
                  <div class="icon-input-group">
                    <div class="icon-preview">
                      <ion-icon :name="service.icon || 'help-circle-outline'"></ion-icon>
                    </div>
                    <input
                      id="service-icon"
                      v-model="service.icon"
                      type="text"
                      class="form-input"
                      placeholder="Enter icon name"
                    />
                  </div>
                  <div class="form-help">
                    Choose an Ionic icon name. Preview appears on the left.
                  </div>
                </div>

                <!-- Service Description -->
                <div class="form-group full-width">
                  <label class="form-label" for="service-description">Description</label>
                  <textarea
                    id="service-description"
                    v-model="service.description"
                    class="form-textarea"
                    placeholder="Enter service description"
                    rows="4"
                  ></textarea>
                  <div class="form-help">
                    Optional description explaining what this service does.
                  </div>
                </div>

                <!-- Service Status -->
                <div class="form-group">
                  <label class="form-label" for="service-status">Service Status</label>
                  <select id="service-status" v-model="service.status" class="form-select">
                    <option value="active">Active - Service is operational</option>
                    <option value="maintenance">Maintenance - Service is under maintenance</option>
                    <option value="inactive">Inactive - Service is disabled</option>
                  </select>
                  <div class="form-help">
                    Current operational status of the service.
                  </div>
                </div>

                <!-- Service Link (Read-only) -->
                <div class="form-group">
                  <label class="form-label">Service Link</label>
                  <div class="readonly-field">
                    <ion-icon name="link-outline"></ion-icon>
                    <span>{{ service.link }}</span>
                  </div>
                  <div class="form-help">
                    Unique identifier used in URLs and API calls.
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions">
                <button
                  type="button"
                  @click="goToManageServices"
                  class="btn btn-secondary"
                >
                  <ion-icon name="list-outline"></ion-icon>
                  Manage All Services
                </button>
                <button
                  type="button"
                  @click="viewService"
                  class="btn btn-outline"
                >
                  <ion-icon name="eye-outline"></ion-icon>
                  View Service
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="isSaving"
                >
                  <ion-icon 
                    :name="isSaving ? 'hourglass-outline' : 'save-outline'"
                    :class="{ 'spinning': isSaving }"
                  ></ion-icon>
                  {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </form>

            <!-- Danger Zone -->
            <div class="danger-zone">
              <div class="danger-header">
                <h3>
                  <ion-icon name="warning-outline"></ion-icon>
                  Danger Zone
                </h3>
                <p>Irreversible and destructive actions</p>
              </div>
              <div class="danger-actions">
                <button
                  @click="confirmDeleteService"
                  class="btn btn-danger"
                >
                  <ion-icon name="trash-outline"></ion-icon>
                  Delete Service Permanently
                </button>
                <div class="danger-help">
                  This will permanently delete the service and all associated data including API keys, logs, and configuration. This action cannot be undone.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from '@/components/SiteTitle.vue';

export default {
  name: 'ServiceConfigView',
  components: {
    SiteTitle
  },
  data() {
    return {
      service: null,
      loading: true,
      isSaving: false,
    };
  },
  created() {
    this.fetchServiceDetails();
  },
  watch: {
    '$route.params.service': 'fetchServiceDetails'
  },
  methods: {
    async fetchServiceDetails() {
      this.loading = true;
      
      try {
        const response = await this.$axios.post(
          'services.php',
          this.$qs.stringify({
            getServices: 'getServices',
            project: this.$route.params.project,
          })
        );
        
        const services = response.data || [];
        this.service = services.find(s => s.link === this.$route.params.service);
        
        if (!this.service) {
          this.$toast.error('Service not found');
        }
      } catch (error) {
        console.error('Error fetching service:', error);
        this.$toast.error('Failed to load service details');
      } finally {
        this.loading = false;
      }
    },
    
    async saveChanges() {
      if (!this.service) return;
      
      if (!this.service.name.trim()) {
        this.$toast.error('Service name is required');
        return;
      }

      this.isSaving = true;
      
      try {
        const response = await this.$axios.post(
          'services.php',
          this.$qs.stringify({
            updateService: 'updateService',
            serviceId: this.service.id,
            name: this.service.name.trim(),
            icon: this.service.icon || 'cog-outline',
            description: this.service.description.trim(),
            status: this.service.status
          })
        );

        if (response.data === 'success') {
          this.$toast.success('Service updated successfully');
          this.emitter.emit('updateSidebar');
        } else {
          this.$toast.error('Error updating service: ' + response.data);
        }
      } catch (error) {
        console.error('Error updating service:', error);
        this.$toast.error('Failed to update service');
      } finally {
        this.isSaving = false;
      }
    },
    
    goToManageServices() {
      this.$router.push(`/project/${this.$route.params.project}/manage/services`);
    },

    viewService() {
      this.$router.push(`/project/${this.$route.params.project}/services/${this.service.link}`);
    },

    async confirmDeleteService() {
      if (!this.service) return;

      const confirmed = confirm(
        `Are you sure you want to PERMANENTLY delete the service "${this.service.name}"?\n\n` +
        `This will also delete:\n` +
        `- All API keys associated with this service\n` +
        `- All logs and monitoring data\n` +
        `- All configuration settings\n\n` +
        `This action cannot be undone!`
      );

      if (confirmed) {
        await this.deleteService();
      }
    },

    async deleteService() {
      try {
        const response = await this.$axios.post(
          'services.php',
          this.$qs.stringify({
            deleteServiceComplete: 'deleteServiceComplete',
            serviceId: this.service.id
          })
        );

        if (response.data === 'success' || response.data.status === 'success') {
          this.$toast.success('Service and all associated data deleted successfully');
          this.emitter.emit('updateSidebar');
          // Redirect to manage services or project overview
          this.$router.push(`/project/${this.$route.params.project}/manage/services`);
        } else {
          this.$toast.error('Error deleting service: ' + (response.data.message || response.data));
        }
      } catch (error) {
        console.error('Error deleting service:', error);
        this.$toast.error('Failed to delete service');
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
    },

    getStatusIcon(status) {
      switch(status) {
        case 'active': return 'checkmark-circle-outline';
        case 'maintenance': return 'construct-outline';
        case 'inactive': return 'close-circle-outline';
        default: return 'help-circle-outline';
      }
    }
  }
};
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
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
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

/* Service Header */
.service-header {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 32px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.service-info {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.service-icon {
  width: 64px;
  height: 64px;
  border-radius: var(--radius-lg);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 32px;
  flex-shrink: 0;
}

.service-details h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 700;
}

.service-details p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.service-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
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

/* Configuration Container */
.config-container {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.config-form-container {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}

/* Form Header */
.form-header {
  padding: 24px 32px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: white;
}

.form-header h2 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 700;
}

.form-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 14px;
  line-height: 1.5;
}

/* Form Styles */
.config-form {
  padding: 32px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
}

.form-input,
.form-textarea,
.form-select {
  padding: 12px 16px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 16px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
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
  min-height: 100px;
  font-family: inherit;
}

.form-help {
  margin-top: 6px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

/* Icon Input Group */
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

/* Read-only Field */
.readonly-field {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-family: monospace;
  font-size: 14px;
}

.readonly-field ion-icon {
  color: var(--text-muted);
  font-size: 20px;
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

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

/* Danger Zone */
.danger-zone {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 2px solid #fecaca;
  overflow: hidden;
}

.danger-header {
  padding: 20px 24px;
  background: #fef2f2;
  border-bottom: 1px solid #fecaca;
}

.danger-header h3 {
  margin: 0 0 8px 0;
  color: var(--danger-color);
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.danger-header p {
  margin: 0;
  color: #7f1d1d;
  font-size: 14px;
}

.danger-actions {
  padding: 24px;
}

.danger-help {
  margin-top: 12px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

/* Animations */
.spinning {
  animation: spin 1s linear infinite;
}

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

  .danger-zone {
    border-color: rgba(220, 38, 38, 0.3);
  }

  .danger-header {
    background: rgba(220, 38, 38, 0.1);
    border-bottom-color: rgba(220, 38, 38, 0.3);
  }

  .danger-header p {
    color: #fca5a5;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .service-header {
    flex-direction: column;
    align-items: flex-start;
    text-align: center;
    gap: 20px;
  }
  
  .service-info {
    flex-direction: column;
    text-align: center;
    width: 100%;
  }
  
  .service-status {
    align-self: center;
  }
  
  .config-form {
    padding: 24px 20px;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  .danger-actions {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .service-icon {
    width: 48px;
    height: 48px;
    font-size: 24px;
  }
  
  .service-details h1 {
    font-size: 20px;
  }
  
  .form-header {
    padding: 20px;
  }
  
  .config-form {
    padding: 20px 16px;
  }
  
  .icon-input-group {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .icon-preview {
    align-self: center;
  }
}
</style>