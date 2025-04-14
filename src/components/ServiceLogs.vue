<template>
  <div class="service-logs">
    <div class="logs-header">
      <div>
        <ion-icon name="list" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
        {{ title || 'Service Logs' }}
      </div>
      <div class="logs-filters">
        <ion-select v-model="typeFilter" placeholder="Filter by type" interface="popover">
          <ion-select-option value="">All Types</ion-select-option>
          <ion-select-option value="info">Info</ion-select-option>
          <ion-select-option value="warn">Warning</ion-select-option>
          <ion-select-option value="error">Error</ion-select-option>
          <ion-select-option value="success">Success</ion-select-option>
        </ion-select>
        <ion-button size="small" @click="refreshLogs">
          <ion-icon slot="icon-only" name="refresh"></ion-icon>
        </ion-button>
      </div>
    </div>

    <div class="logs-container">
      <ion-list v-if="logs.length > 0">
        <ion-item v-for="log in logs" :key="log.id" lines="full" button @click="showDetails(log)">
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
      <div v-else-if="!loading" class="empty-logs">
        <ion-icon name="document-text-outline" size="large"></ion-icon>
        <p>No logs available</p>
      </div>
      <div v-if="loading" class="loading-logs">
        <ion-spinner name="circular"></ion-spinner>
        <p>Loading logs...</p>
      </div>
      
      <ion-infinite-scroll @ionInfinite="loadMore($event)" threshold="100px">
        <ion-infinite-scroll-content loading-spinner="circular" loading-text="Loading more logs...">
        </ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </div>

    <!-- Log Details Modal -->
    <ion-modal :is-open="showModal" @didDismiss="showModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Log Details</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showModal = false">Close</ion-button>
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
  </div>
</template>

<script>
export default {
  name: 'ServiceLogs',
  props: {
    title: {
      type: String,
      default: 'Service Logs'
    },
    projectId: {
      type: String,
      required: true
    },
    service: {
      type: String,
      required: true
    },
    limit: {
      type: Number,
      default: 20
    }
  },
  data() {
    return {
      logs: [],
      loading: false,
      offset: 0,
      typeFilter: '',
      showModal: false,
      selectedLog: null,
      hasMoreLogs: true
    };
  },
  watch: {
    typeFilter() {
      this.resetLogs();
      this.fetchLogs();
    },
    projectId() {
      this.resetLogs();
      this.fetchLogs();
    },
    service() {
      this.resetLogs();
      this.fetchLogs();
    }
  },
  mounted() {
    this.fetchLogs();
  },
  methods: {
    resetLogs() {
      this.logs = [];
      this.offset = 0;
      this.hasMoreLogs = true;
    },
    refreshLogs() {
      this.resetLogs();
      this.fetchLogs();
    },
    fetchLogs() {
      if (!this.projectId || !this.service) return;
      
      this.loading = true;
      const params = {
        project_id: this.projectId,
        service: this.service,
        limit: this.limit,
        offset: this.offset
      };
      
      if (this.typeFilter) {
        params.type = this.typeFilter;
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
          if (response.data.data.length < this.limit) {
            this.hasMoreLogs = false;
          }
          
          if (this.offset === 0) {
            this.logs = response.data.data;
          } else {
            this.logs = [...this.logs, ...response.data.data];
          }
        }
        this.loading = false;
      })
      .catch(error => {
        console.error('Error fetching logs:', error);
        this.loading = false;
      });
    },
    loadMore(event) {
      if (!this.hasMoreLogs) {
        event.target.complete();
        return;
      }
      
      this.offset += this.limit;
      this.fetchLogs();
      event.target.complete();
    },
    showDetails(log) {
      this.selectedLog = log;
      this.showModal = true;
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
.service-logs {
  width: 100%;
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
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