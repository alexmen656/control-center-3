<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-card v-if="service">
              <ion-card-header>
                <ion-card-title>
                  <ion-icon :name="service.icon" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
                  {{ service.name }}
                </ion-card-title>
                <ion-card-subtitle>
                  <ion-badge :color="getBadgeColor(service.status)">{{ getStatusText(service.status) }}</ion-badge>
                </ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <p>{{ service.description || 'No description available' }}</p>
                
                <!-- Service Details and Content will be added here -->
                <div class="service-content">
                  <h2>Service Details</h2>
                  <p>This is the main service view. Configure this service in your project settings.</p>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- API Key Manager Section -->
            <ion-card v-if="service && projectId">
              <ion-card-content>
                <ApiKeyManager 
                  :projectId="projectId" 
                  :service="service.link" 
                />
              </ion-card-content>
            </ion-card>

            <!-- Service Logs Section -->
            <ion-card v-if="service">
              <ion-card-header>
                <ion-card-title>
                  <div class="logs-header">
                    <div>
                      <ion-icon name="list" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
                      Service Logs
                    </div>
                    <div class="logs-filters">
                      <ion-select v-model="logTypeFilter" placeholder="Filter by type" interface="popover">
                        <ion-select-option value="">All Types</ion-select-option>
                        <ion-select-option value="info">Info</ion-select-option>
                        <ion-select-option value="warn">Warning</ion-select-option>
                        <ion-select-option value="error">Error</ion-select-option>
                        <ion-select-option value="success">Success</ion-select-option>
                      </ion-select>
                      <ion-button size="small" @click="fetchLogs">
                        <ion-icon slot="icon-only" name="refresh"></ion-icon>
                      </ion-button>
                    </div>
                  </div>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div class="logs-container">
                  <ion-list v-if="logs.length > 0">
                    <ion-item v-for="log in logs" :key="log.id" lines="full" button @click="showLogDetails(log)">
                      <ion-icon :name="getLogIcon(log.type)" :color="getLogColor(log.type)" slot="start"></ion-icon>
                      <ion-label>
                        <div class="log-header">
                          <span class="log-message">{{ log.message }}</span>
                          <span class="log-time">{{ formatDate(log.timestamp) }}</span>
                        </div>
                        <p class="log-env" v-if="log.environment">
                          Environment: {{ log.environment }}
                        </p>
                      </ion-label>
                    </ion-item>
                  </ion-list>
                  <div v-else-if="!loadingLogs" class="empty-logs">
                    <ion-icon name="document-text-outline" size="large"></ion-icon>
                    <p>No logs available for this service</p>
                  </div>
                  <div v-if="loadingLogs" class="loading-logs">
                    <ion-spinner name="circular"></ion-spinner>
                    <p>Loading logs...</p>
                  </div>
                  
                  <ion-infinite-scroll @ionInfinite="loadMoreLogs($event)" threshold="100px">
                    <ion-infinite-scroll-content loading-spinner="circular" loading-text="Loading more logs...">
                    </ion-infinite-scroll-content>
                  </ion-infinite-scroll>
                </div>
              </ion-card-content>
            </ion-card>

            <ion-card v-if="!service && !loading">
              <ion-card-header>
                <ion-card-title>Service Not Found</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <p>The requested service could not be found.</p>
                <ion-button @click="$router.push(`/project/${$route.params.project}`)">
                  Return to Project
                </ion-button>
              </ion-card-content>
            </ion-card>
            <ion-card v-if="loading">
              <ion-card-content class="loading-container">
                <ion-spinner name="circular"></ion-spinner>
                <p>Loading service details...</p>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>

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
  </ion-page>
</template>

<script>
import ApiKeyManager from '../components/ApiKeyManager.vue';

export default {
  name: 'ServiceView',
  components: {
    ApiKeyManager
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
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.service-content {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--ion-color-light);
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logs-filters {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logs-container {
  max-height: 500px;
  overflow-y: auto;
}

.empty-logs {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  color: var(--ion-color-medium);
}

.loading-logs {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.log-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.log-message {
  font-weight: 500;
}

.log-time {
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.log-env {
  font-size: 0.8rem;
  margin-top: 4px;
}

pre {
  white-space: pre-wrap;
  background-color: rgba(0, 0, 0, 0.05);
  padding: 8px;
  border-radius: 4px;
  font-family: monospace;
  overflow-x: auto;
}
</style>