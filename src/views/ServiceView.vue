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
                  ref="apiKeyManager"
                  :projectId="projectId" 
                  :service="service.link"
                  :hideTitle="true"
                  @create-key="showApiKeyModal = true"
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
                <div class="card-actions">
                  <ion-select v-model="statusSelectedDays" placeholder="Zeitraum" interface="popover" @ionChange="refreshStatusHistory" style="width: 120px; margin-right: 8px;">
                    <ion-select-option value="1">24 Stunden</ion-select-option>
                    <ion-select-option value="3">3 Tage</ion-select-option>
                    <ion-select-option value="7">7 Tage</ion-select-option>
                    <ion-select-option value="14">14 Tage</ion-select-option>
                    <ion-select-option value="30">30 Tage</ion-select-option>
                  </ion-select>
                  <button class="icon-btn" @click="refreshStatusHistory" title="Refresh Status">
                    <ion-icon name="refresh-outline"></ion-icon>
                  </button>
                </div>
              </div>
              <div class="card-content">
                <ServiceStatusHistory
                  ref="statusHistory"
                  :projectId="projectId"
                  :service="service.link"
                  :hideTitle="true"
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
                  <ion-select v-model="logTypeFilter" placeholder="Log Type" interface="popover" @ionChange="refreshLogs" style="width: 120px; margin-right: 8px;">
                    <ion-select-option value="">All Types</ion-select-option>
                    <ion-select-option value="info">Info</ion-select-option>
                    <ion-select-option value="warn">Warning</ion-select-option>
                    <ion-select-option value="error">Error</ion-select-option>
                    <ion-select-option value="success">Success</ion-select-option>
                    <ion-select-option value="debug">Debug</ion-select-option>
                  </ion-select>
                  <input
                    type="text"
                    v-model="logSearchQuery"
                    placeholder="Search logs..."
                    style="width: 150px; margin-right: 8px; padding: 6px 12px; border: 1px solid var(--border); border-radius: 4px;"
                    @input="onLogSearch"
                  />
                  <button class="icon-btn" @click="refreshLogs" title="Refresh Logs">
                    <ion-icon name="refresh-outline"></ion-icon>
                  </button>
                </div>
              </div>
              <div class="card-content">
                <ServiceLogs
                  ref="serviceLogs"
                  :projectId="projectId"
                  :service="service.link"
                  :limit="logLimit"
                  :hideTitle="true"
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

      <!-- API Key Creation Modal -->
      <div v-if="showApiKeyModal" class="modal-overlay" @click.self="closeApiKeyModal">
        <div class="modal-container">
          <!-- Modal Header -->
          <div class="modal-header">
            <h2>Create New API Key</h2>
            <button @click="closeApiKeyModal" class="modal-close-btn">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="modal-body">
            <div class="modal-description">
              <p>API keys allow external services to send logs to this service. Use these keys in your API requests with the <code>X-Api-Key</code> header.</p>
            </div>

            <!-- Form Fields -->
            <div class="form-fields">
              <div class="form-group">
                <label class="form-label">Key Name</label>
                <input
                  v-model="newKey.name"
                  type="text"
                  class="form-input"
                  placeholder="Enter a descriptive name"
                  required
                />
                <div class="form-help">
                  A descriptive name to identify this API key.
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Description (Optional)</label>
                <textarea
                  v-model="newKey.description"
                  class="form-textarea"
                  placeholder="Enter description"
                  rows="3"
                ></textarea>
                <div class="form-help">
                  Optional description explaining what this key is used for.
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Expiration Date (Optional)</label>
                <input
                  v-model="newKey.expires_at"
                  type="datetime-local"
                  class="form-input"
                  placeholder="mm/dd/yyyy, --:-- --"
                />
                <div class="form-help">
                  Leave empty for no expiration. Key will be automatically deactivated after this date.
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button @click="closeApiKeyModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="createKey" class="btn btn-primary" :disabled="isCreating || !newKey.name.trim()">
              <ion-icon :name="isCreating ? 'hourglass-outline' : 'add-outline'"></ion-icon>
              {{ isCreating ? 'Creating...' : 'Create API Key' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Success Modal for New Key -->
      <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
        <div class="modal-container success-modal">
          <!-- Modal Header -->
          <div class="modal-header">
            <h2>API Key Created Successfully</h2>
            <button @click="closeSuccessModal" class="modal-close-btn">
              <ion-icon name="close"></ion-icon>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="modal-body">
            <div class="success-content">
              <div class="success-icon">
                <ion-icon name="checkmark-circle"></ion-icon>
              </div>
              <h3>Your API Key is Ready!</h3>
              <p>Copy this key now - it won't be shown again in full.</p>
              
              <div class="new-key-display">
                <div class="key-container">
                  <code class="api-key-code">{{ createdKey }}</code>
                  <button @click="copyToClipboard(createdKey)" class="copy-btn-inline" title="Copy API Key">
                    <ion-icon name="copy-outline"></ion-icon>
                  </button>
                </div>
              </div>
              
              <div class="usage-example">
                <h4>Usage Example:</h4>
                <div class="code-block">
                  <pre><code>curl -X POST https://your-api.com/logs \
  -H "X-Api-Key: {{ createdKey }}" \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello World"}'</code></pre>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button @click="closeSuccessModal" class="btn btn-primary">
              <ion-icon name="checkmark-outline"></ion-icon>
              Got it!
            </button>
          </div>
        </div>
      </div>
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
      logSearchQuery: '',
      logSearchTimeout: null,
      statusSelectedDays: '7',
      showLogModal: false,
      selectedLog: null,
      hasMoreLogs: true,
      projectId: null,
      // API Key Modal
      showApiKeyModal: false,
      showSuccessModal: false,
      isCreating: false,
      newKey: {
        name: '',
        description: '',
        expires_at: ''
      },
      createdKey: ''
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
    refreshStatusHistory() {
      if (this.$refs.statusHistory) {
        this.$refs.statusHistory.selectedDays = this.statusSelectedDays;
        this.$refs.statusHistory.fetchStatusHistory();
      }
    },
    refreshLogs() {
      if (this.$refs.serviceLogs) {
        this.$refs.serviceLogs.typeFilter = this.logTypeFilter;
        this.$refs.serviceLogs.searchQuery = this.logSearchQuery;
        this.$refs.serviceLogs.refreshLogs();
      }
    },
    onLogSearch() {
      // Debounce search
      if (this.logSearchTimeout) {
        clearTimeout(this.logSearchTimeout);
      }
      this.logSearchTimeout = setTimeout(() => {
        this.refreshLogs();
      }, 300);
    },
    closeApiKeyModal() {
      this.showApiKeyModal = false;
      this.resetNewKey();
    },
    closeSuccessModal() {
      this.showSuccessModal = false;
      this.createdKey = '';
    },
    resetNewKey() {
      this.newKey = {
        name: '',
        description: '',
        expires_at: ''
      };
    },
    async createKey() {
      if (!this.newKey.name.trim()) {
        this.$toast.error('Please enter a key name');
        return;
      }

      this.isCreating = true;
      try {
        const response = await this.$axios.post('api/api_keys.php', {
          project_id: this.projectId,
          service: this.service.link,
          name: this.newKey.name.trim(),
          description: this.newKey.description.trim(),
          expires_at: this.newKey.expires_at || null
        }, {
          headers: {
            'Authorization': localStorage.getItem('token'),
            'Content-Type': 'application/json'
          }
        });

        if (response.data.success) {
          this.createdKey = response.data.data.api_key;
          this.showApiKeyModal = false;
          this.showSuccessModal = true;
          this.resetNewKey();
          // Refresh API keys in the component
          if (this.$refs.apiKeyManager) {
            this.$refs.apiKeyManager.fetchApiKeys();
          }
          this.$toast.success('API key created successfully');
        } else {
          this.$toast.error('Failed to create API key: ' + response.data.error);
        }
      } catch (error) {
        console.error('Error creating API key:', error);
        this.$toast.error('Failed to create API key');
      } finally {
        this.isCreating = false;
      }
    },
    copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        this.$toast.success('Copied to clipboard');
      }).catch(() => {
        this.$toast.error('Failed to copy to clipboard');
      });
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
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
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

/* Modal Styles - Modern Design */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.modal-container {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 24px 20px 24px;
  border-bottom: 1px solid var(--border);
}

.modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 20px;
}

.modal-close-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.modal-description {
  margin-bottom: 32px;
}

.modal-description p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

.modal-description code {
  background: var(--background);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  color: var(--text-primary);
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px 24px 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

.form-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
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
.form-textarea {
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
.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

.form-help {
  margin-top: 6px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.4;
}

/* Success Modal Specific Styles */
.success-modal {
  max-width: 700px;
}

.success-content {
  text-align: center;
}

.success-icon {
  font-size: 64px;
  color: var(--success-color);
  margin-bottom: 20px;
}

.success-content h3 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

.success-content p {
  margin: 0 0 32px 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.new-key-display {
  margin-bottom: 32px;
}

.key-container {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  margin-bottom: 16px;
}

.api-key-code {
  flex: 1;
  font-family: monospace;
  font-size: 14px;
  color: var(--text-primary);
  background: none;
  border: none;
  word-break: break-all;
  padding: 0;
}

.copy-btn-inline {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 18px;
  flex-shrink: 0;
}

.copy-btn-inline:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.usage-example {
  text-align: left;
}

.usage-example h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.code-block {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  overflow-x: auto;
}

.code-block pre {
  margin: 0;
  font-family: monospace;
  font-size: 13px;
  line-height: 1.4;
  color: var(--text-secondary);
  white-space: pre-wrap;
  word-break: break-all;
}
</style>