<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle :icon="service?.icon || 'cog-outline'" :title="service?.name || 'Service'"/>

      <div class="page-container">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Loading service details...</p>
        </div>

        <!-- Service Not Found -->
        <div v-else-if="!service" class="no-data-state">
          <ion-icon name="alert-circle-outline" class="no-data-icon"></ion-icon>
          <h3>Service Not Found</h3>
          <p>The requested service could not be found.</p>
          <button class="action-btn primary" @click="$router.push(`/project/${$route.params.project}`)">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Return to Project
          </button>
        </div>

        <!-- Service Content -->
        <div v-else>
          <!-- Service Header -->
          <div class="service-header">
            <div class="service-info">
              <div class="service-icon">
                <ion-icon :name="service.icon || 'cog-outline'"></ion-icon>
              </div>
              <div class="service-details">
                <h1>{{ service.name }}</h1>
                <p>{{ service.description || 'No description available' }}</p>
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

          <!-- Service Cards Grid -->
          <div class="service-cards-grid">
            <!-- API Key Manager Card -->
            <div class="service-card" v-if="projectId">
              <div class="card-header">
                <h2>
                  <ion-icon name="key-outline"></ion-icon>
                  API Keys
                </h2>
              </div>
              <div class="card-content">
                <ApiKeyManager 
                  :projectId="projectId" 
                  :service="service.link" 
                />
              </div>
            </div>

            <!-- Service Status History Card -->
            <div class="service-card" v-if="projectId">
              <div class="card-header">
                <h2>
                  <ion-icon name="pulse-outline"></ion-icon>
                  Status Monitoring
                </h2>
              </div>
              <div class="card-content">
                <ServiceStatusHistory
                  title="Service Status Überwachung"
                  :projectId="projectId"
                  :service="service.link"
                />
              </div>
            </div>

            <!-- Service Logs Card -->
            <div class="service-card logs-card" v-if="projectId">
              <div class="card-header">
                <h2>
                  <ion-icon name="document-text-outline"></ion-icon>
                  Service Logs
                </h2>
                <div class="card-actions">
                  <button class="icon-btn" @click="fetchLogs" title="Refresh Logs">
                    <ion-icon name="refresh-outline"></ion-icon>
                  </button>
                </div>
              </div>
              <div class="card-content">
                <ServiceLogs
                  title="Service Logs"
                  :projectId="projectId"
                  :service="service.link"
                  :limit="logLimit"
                />
              </div>
            </div>

            <!-- Service Configuration Card -->
            <div class="service-card">
              <div class="card-header">
                <h2>
                  <ion-icon name="settings-outline"></ion-icon>
                  Configuration
                </h2>
                <div class="card-actions">
                  <button 
                    class="action-btn secondary"
                    @click="$router.push(`/project/${$route.params.project}/services/${service.link}/config`)"
                  >
                    <ion-icon name="cog-outline"></ion-icon>
                    Configure
                  </button>
                </div>
              </div>
              <div class="card-content">
                <div class="config-info">
                  <div class="config-item">
                    <span class="config-label">Service Link:</span>
                    <span class="config-value">{{ service.link }}</span>
                  </div>
                  <div class="config-item">
                    <span class="config-label">Project:</span>
                    <span class="config-value">{{ $route.params.project }}</span>
                  </div>
                  <div class="config-item">
                    <span class="config-label">Status:</span>
                    <span class="config-value">{{ getStatusText(service.status) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Log Details Modal -->
      <ion-modal :is-open="showLogModal" @didDismiss="showLogModal = false">
        <ion-header>
          <ion-toolbar>
            <ion-title>Log Details</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="showLogModal = false">Close</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-list>
            <ion-item>
              <ion-label>
                <h2>Timestamp</h2>
                <p>{{ selectedLog ? formatDateFull(selectedLog.timestamp) : '' }}</p>
              </ion-label>
            </ion-item>
            <ion-item>
              <ion-label>
                <h2>Type</h2>
                <p>
                  <ion-badge :color="selectedLog ? getLogColor(selectedLog.type) : 'medium'">
                    {{ selectedLog ? selectedLog.type.toUpperCase() : '' }}
                  </ion-badge>
                </p>
              </ion-label>
            </ion-item>
            <ion-item>
              <ion-label>
                <h2>Message</h2>
                <p>{{ selectedLog ? selectedLog.message : '' }}</p>
              </ion-label>
            </ion-item>
            <ion-item v-if="selectedLog && selectedLog.environment">
              <ion-label>
                <h2>Environment</h2>
                <p>{{ selectedLog.environment }}</p>
              </ion-label>
            </ion-item>
            <ion-item v-if="selectedLog && selectedLog.data">
              <ion-label>
                <h2>Data</h2>
                <pre>{{ JSON.stringify(selectedLog.data, null, 2) }}</pre>
              </ion-label>
            </ion-item>
            <ion-item v-if="selectedLog && selectedLog.meta">
              <ion-label>
                <h2>Meta</h2>
                <pre>{{ JSON.stringify(selectedLog.meta, null, 2) }}</pre>
              </ion-label>
            </ion-item>
            <ion-item v-if="selectedLog && selectedLog.ip_address">
              <ion-label>
                <h2>IP Address</h2>
                <p>{{ selectedLog.ip_address }}</p>
              </ion-label>
            </ion-item>
            <ion-item v-if="selectedLog">
              <ion-label>
                <h2>Log ID</h2>
                <p>{{ selectedLog.id }}</p>
              </ion-label>
            </ion-item>
          </ion-list>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import ApiKeyManager from '../components/ApiKeyManager.vue';
import ServiceStatusHistory from '../components/ServiceStatusHistory.vue';
import ServiceLogs from '../components/ServiceLogs.vue';

export default {
  name: 'ServiceView',
  components: {
    SiteTitle,
    ApiKeyManager,
    ServiceStatusHistory,
    ServiceLogs
  },
  data() {
    return {
      service: null,
      loading: true,
      logs: [],
      loadingLogs: false,
      logOffset: 0,
      logLimit: 20,
      logTypeFilter: '',
      showLogModal: false,
      selectedLog: null,
      hasMoreLogs: true,
      projectId: null,
    };
  },
  created() {
    this.fetchServiceDetails();
  },
  watch: {
    // Watch for route changes to update the service details
    '$route.params.service': 'fetchServiceDetails',
    // Watch for logTypeFilter changes to refresh logs
    logTypeFilter() {
      this.resetLogs();
      this.fetchLogs();
    }
  },
  methods: {
    fetchServiceDetails() {
      this.loading = true;
      
      // Find the service in the project's services list
      this.$axios.post(
        'services.php',
        this.$qs.stringify({
          getServices: 'getServices',
          project: this.$route.params.project,
        })
      )
      .then(response => {
        const services = response.data || [];
        this.service = services.find(s => s.link === this.$route.params.service);
        
        // Get project ID for logs
        if (this.service) {
          this.fetchProjectId();
        }
        
        this.loading = false;
      })
      .catch(error => {
        console.error('Error fetching service:', error);
        this.loading = false;
      });
    },
    fetchProjectId() {
      this.$axios.post(
        'services.php',
        this.$qs.stringify({
          getProject: 'getProject',
          project: this.$route.params.project,
        })
      )
      .then(response => {
        if (response.data && response.data.projectID) {
          this.projectId = response.data.projectID;
          this.fetchLogs();
        }
      })
      .catch(error => {
        console.error('Error fetching project ID:', error);
      });
    },
    resetLogs() {
      this.logs = [];
      this.logOffset = 0;
      this.hasMoreLogs = true;
    },
    fetchLogs() {
      if (!this.projectId || !this.service) return;
      
      this.loadingLogs = true;
      const params = {
        project_id: this.projectId,
        service: this.service.link,
        limit: this.logLimit,
        offset: this.logOffset
      };
      
      if (this.logTypeFilter) {
        params.type = this.logTypeFilter;
      }
      
      // Convert params to query string
      const queryParams = new URLSearchParams();
      for (const key in params) {
        queryParams.append(key, params[key]);
      }
      
      this.$axios.get(`api/service_logs.php?${queryParams.toString()}`, {
        headers: {
          'Authorization': localStorage.getItem('token')
        }
      })
      .then(response => {
        if (response.data.success && response.data.data) {
          if (response.data.data.length < this.logLimit) {
            this.hasMoreLogs = false;
          }
          
          if (this.logOffset === 0) {
            this.logs = response.data.data;
          } else {
            this.logs = [...this.logs, ...response.data.data];
          }
        }
        this.loadingLogs = false;
      })
      .catch(error => {
        console.error('Error fetching logs:', error);
        this.loadingLogs = false;
      });
    },
    loadMoreLogs(event) {
      if (!this.hasMoreLogs) {
        event.target.complete();
        return;
      }
      
      this.logOffset += this.logLimit;
      this.fetchLogs();
      event.target.complete();
    },
    showLogDetails(log) {
      this.selectedLog = log;
      this.showLogModal = true;
    },
    getBadgeColor(status) {
      switch(status) {
        case 'active': return 'success';
        case 'maintenance': return 'warning';
        case 'inactive': return 'danger';
        default: return 'medium';
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
    getStatusClass(status) {
      switch(status) {
        case 'active': return 'status-active';
        case 'maintenance': return 'status-maintenance';
        case 'inactive': return 'status-inactive';
        default: return 'status-unknown';
      }
    },
    getStatusIcon(status) {
      switch(status) {
        case 'active': return 'checkmark-circle-outline';
        case 'maintenance': return 'construct-outline';
        case 'inactive': return 'close-circle-outline';
        default: return 'help-circle-outline';
      }
    },
    getLogIcon(type) {
      switch(type) {
        case 'info': return 'information-circle-outline';
        case 'warn': return 'warning-outline';
        case 'error': return 'alert-circle-outline';
        case 'success': return 'checkmark-circle-outline';
        default: return 'information-circle-outline';
      }
    },
    getLogColor(type) {
      switch(type) {
        case 'info': return 'primary';
        case 'warn': return 'warning';
        case 'error': return 'danger';
        case 'success': return 'success';
        default: return 'medium';
      }
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('default', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit'
      }).format(date);
    },
    formatDateFull(dateString) {
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('default', { 
        year: 'numeric',
        month: 'long', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
      }).format(date);
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
  max-width: 1400px;
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

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
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
  flex-wrap: wrap;
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

/* Service Cards Grid */
.service-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
}

.service-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
  transition: all 0.2s ease;
}

.service-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.logs-card {
  grid-column: 1 / -1;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.card-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-header h2 ion-icon {
  font-size: 20px;
  color: var(--primary-color);
}

.card-actions {
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
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.card-content {
  padding: 24px;
}

/* Configuration Info */
.config-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.config-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.config-item:last-child {
  border-bottom: none;
}

.config-label {
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 14px;
}

.config-value {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
  font-family: monospace;
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Pre formatting for logs */
pre {
  white-space: pre-wrap;
  background-color: rgba(0, 0, 0, 0.05);
  padding: 8px;
  border-radius: 4px;
  font-family: monospace;
  overflow-x: auto;
  font-size: 12px;
  line-height: 1.4;
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

  pre {
    background-color: rgba(255, 255, 255, 0.05);
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
  
  .service-header {
    flex-direction: column;
    align-items: flex-start;
    text-align: center;
  }
  
  .service-info {
    flex-direction: column;
    text-align: center;
    width: 100%;
  }
  
  .service-status {
    align-self: center;
  }
  
  .service-cards-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .card-actions {
    align-self: flex-end;
  }
  
  .config-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
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
  
  .card-content {
    padding: 16px;
  }
}
</style>