<template>
  <div class="modern-component">
    <!-- Component Header -->
    <div class="component-header">
      <h3>
        <ion-icon name="list-outline"></ion-icon>
        Service Logs
      </h3>
      <div class="header-actions">
        <div class="filter-controls">
          <ion-select v-model="typeFilter" placeholder="Log Type" interface="popover" @ionChange="refreshLogs">
            <ion-select-option value="">All Types</ion-select-option>
            <ion-select-option value="info">Info</ion-select-option>
            <ion-select-option value="warn">Warning</ion-select-option>
            <ion-select-option value="error">Error</ion-select-option>
            <ion-select-option value="success">Success</ion-select-option>
            <ion-select-option value="debug">Debug</ion-select-option>
          </ion-select>
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search logs..."
            class="search-input"
            @input="onSearch"
          />
        </div>
        <button class="btn btn-secondary" @click="refreshLogs">
          <ion-icon name="refresh"></ion-icon>
          Refresh
        </button>
      </div>
    </div>

    <!-- Component Description -->
    <div class="component-description">
      <p>View and search through service logs. Logs are automatically captured from your service API calls and system events.</p>
    </div>

    <!-- Log Stats -->
    <div class="log-stats" v-if="logStats">
      <div class="stat-card">
        <div class="stat-icon info">
          <ion-icon name="information-circle"></ion-icon>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ logStats.info }}</div>
          <div class="stat-label">Info</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning">
          <ion-icon name="warning"></ion-icon>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ logStats.warn }}</div>
          <div class="stat-label">Warnings</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon error">
          <ion-icon name="alert-circle"></ion-icon>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ logStats.error }}</div>
          <div class="stat-label">Errors</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success">
          <ion-icon name="checkmark-circle"></ion-icon>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ logStats.success }}</div>
          <div class="stat-label">Success</div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && logs.length === 0" class="loading-state">
      <div class="loading-icon">
        <ion-icon name="list-outline"></ion-icon>
      </div>
      <p>Loading logs...</p>
    </div>

    <!-- Log Entries -->
    <div class="logs-container" v-else-if="logs.length > 0">
      <div class="log-list">
        <div
          v-for="log in filteredLogs"
          :key="log.id"
          :class="getLogCardClass(log)"
          @click="showLogDetails(log)"
        >
          <div class="log-header">
            <div class="log-icon" :class="getLogTypeClass(log.type)">
              <ion-icon :name="getLogIcon(log.type)"></ion-icon>
            </div>
            <div class="log-info">
              <div class="log-message">{{ log.message }}</div>
              <div class="log-meta">
                <span class="log-time">{{ formatDateTime(log.timestamp) }}</span>
                <span class="log-type" :class="getLogTypeClass(log.type)">{{ log.type.toUpperCase() }}</span>
                <span v-if="log.environment" class="log-env">{{ log.environment }}</span>
              </div>
            </div>
            <div class="log-actions">
              <button class="action-btn" @click.stop="copyLog(log)">
                <ion-icon name="copy-outline"></ion-icon>
              </button>
              <button class="action-btn" @click.stop="showLogDetails(log)">
                <ion-icon name="eye-outline"></ion-icon>
              </button>
            </div>
          </div>
          
          <!-- Preview of additional data -->
          <div class="log-preview" v-if="log.data && Object.keys(log.data).length > 0">
            <div class="preview-content">
              <code>{{ getDataPreview(log.data) }}</code>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div class="load-more-container" v-if="hasMore">
        <button class="btn btn-secondary" @click="loadMoreLogs" :disabled="loading">
          <ion-icon v-if="loading" name="refresh" class="loading-spin"></ion-icon>
          <ion-icon v-else name="chevron-down"></ion-icon>
          {{ loading ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <ion-icon name="document-text-outline"></ion-icon>
      </div>
      <h3>No Logs Found</h3>
      <p v-if="typeFilter || searchQuery">No logs match your current filters.</p>
      <p v-else>No logs have been recorded for this service yet.</p>
      <div class="empty-actions">
        <button class="btn btn-primary" @click="clearFilters" v-if="typeFilter || searchQuery">
          <ion-icon name="refresh"></ion-icon>
          Clear Filters
        </button>
        <button class="btn btn-secondary" @click="refreshLogs">
          <ion-icon name="refresh"></ion-icon>
          Refresh
        </button>
      </div>
    </div>

    <!-- Log Details Modal -->
    <ion-modal :is-open="showDetailsModal" @didDismiss="showDetailsModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Log Details</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeDetailsModal">
              <ion-icon name="close"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="modal-content">
        <div class="modal-form" v-if="selectedLog">
          <!-- Log Header -->
          <div class="detail-header">
            <div class="log-type-indicator" :class="getLogTypeClass(selectedLog.type)">
              <ion-icon :name="getLogIcon(selectedLog.type)"></ion-icon>
              <span>{{ selectedLog.type.toUpperCase() }}</span>
            </div>
            <div class="detail-time">{{ formatDateTime(selectedLog.timestamp) }}</div>
          </div>

          <!-- Log Details -->
          <div class="detail-content">
            <div class="detail-section">
              <h4>Message</h4>
              <div class="detail-value message">{{ selectedLog.message }}</div>
            </div>

            <div class="detail-section" v-if="selectedLog.environment">
              <h4>Environment</h4>
              <div class="detail-value">{{ selectedLog.environment }}</div>
            </div>

            <div class="detail-section" v-if="selectedLog.data">
              <h4>Additional Data</h4>
              <div class="detail-value code">
                <pre><code>{{ JSON.stringify(selectedLog.data, null, 2) }}</code></pre>
              </div>
            </div>

            <div class="detail-section" v-if="selectedLog.meta">
              <h4>Metadata</h4>
              <div class="detail-value code">
                <pre><code>{{ JSON.stringify(selectedLog.meta, null, 2) }}</code></pre>
              </div>
            </div>

            <div class="detail-section">
              <h4>Raw Log Entry</h4>
              <div class="detail-value code">
                <pre><code>{{ JSON.stringify(selectedLog, null, 2) }}</code></pre>
              </div>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="modal-actions">
            <button class="btn btn-secondary" @click="copyLog(selectedLog)">
              <ion-icon name="copy"></ion-icon>
              Copy JSON
            </button>
            <button class="btn btn-primary" @click="closeDetailsModal">
              <ion-icon name="checkmark"></ion-icon>
              Done
            </button>
          </div>
        </div>
      </ion-content>
    </ion-modal>
  </div>
</template>

<script>
export default {
  name: 'ServiceLogs',
  props: {
    projectId: {
      type: String,
      required: true
    },
    service: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      logs: [],
      loading: false,
      typeFilter: '',
      searchQuery: '',
      searchTimeout: null,
      hasMore: true,
      offset: 0,
      limit: 50,
      logStats: null,
      showDetailsModal: false,
      selectedLog: null
    };
  },
  computed: {
    filteredLogs() {
      let filtered = [...this.logs];
      
      if (this.typeFilter) {
        filtered = filtered.filter(log => log.type === this.typeFilter);
      }
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(log => 
          log.message.toLowerCase().includes(query) ||
          (log.environment && log.environment.toLowerCase().includes(query)) ||
          (log.data && JSON.stringify(log.data).toLowerCase().includes(query))
        );
      }
      
      return filtered;
    }
  },
  watch: {
    projectId() {
      this.resetAndRefresh();
    },
    service() {
      this.resetAndRefresh();
    }
  },
  mounted() {
    this.fetchLogs();
  },
  methods: {
    resetAndRefresh() {
      this.logs = [];
      this.offset = 0;
      this.hasMore = true;
      this.fetchLogs();
    },

    onSearch() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        // Search is handled by computed property
      }, 300);
    },

    getLogCardClass(log) {
      return {
        'log-card': true,
        [`log-${log.type}`]: true
      };
    },

    getLogTypeClass(type) {
      return `log-type-${type}`;
    },

    getLogIcon(type) {
      const icons = {
        info: 'information-circle',
        warn: 'warning',
        error: 'alert-circle',
        success: 'checkmark-circle',
        debug: 'bug'
      };
      return icons[type] || 'document-text';
    },

    getDataPreview(data) {
      const str = JSON.stringify(data);
      return str.length > 100 ? str.substring(0, 100) + '...' : str;
    },

    showLogDetails(log) {
      this.selectedLog = log;
      this.showDetailsModal = true;
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedLog = null;
    },

    async copyLog(log) {
      try {
        await navigator.clipboard.writeText(JSON.stringify(log, null, 2));
        this.$toast.success('Log copied to clipboard');
      } catch (error) {
        console.error('Failed to copy log:', error);
        this.$toast.error('Failed to copy log');
      }
    },

    clearFilters() {
      this.typeFilter = '';
      this.searchQuery = '';
    },

    calculateLogStats() {
      const stats = { info: 0, warn: 0, error: 0, success: 0, debug: 0 };
      
      for (const log of this.logs) {
        if (Object.prototype.hasOwnProperty.call(stats, log.type)) {
          stats[log.type]++;
        }
      }
      
      this.logStats = stats;
    },

    async fetchLogs() {
      if (!this.projectId || !this.service) return;
      
      this.loading = true;
      
      try {
        const params = {
          project_id: this.projectId,
          service: this.service,
          limit: this.limit,
          offset: this.offset
        };
        
        if (this.typeFilter) {
          params.type = this.typeFilter;
        }
        
        const queryParams = new URLSearchParams();
        for (const key in params) {
          queryParams.append(key, params[key]);
        }
        
        const response = await this.$axios.get(`/backend/api/service_logs.php?${queryParams.toString()}`, {
          headers: {
            'Authorization': localStorage.getItem('token')
          }
        });
        
        if (response.data.success) {
          const newLogs = response.data.data;
          
          if (this.offset === 0) {
            this.logs = newLogs;
          } else {
            this.logs.push(...newLogs);
          }
          
          this.hasMore = newLogs.length === this.limit;
          this.calculateLogStats();
        } else {
          console.error('Failed to fetch logs:', response.data.error);
          this.$toast.error('Failed to load logs');
        }
      } catch (error) {
        console.error('Error fetching logs:', error);
        this.$toast.error('Error loading logs');
      } finally {
        this.loading = false;
      }
    },

    refreshLogs() {
      this.offset = 0;
      this.hasMore = true;
      this.fetchLogs();
    },

    loadMoreLogs() {
      if (!this.loading && this.hasMore) {
        this.offset += this.limit;
        this.fetchLogs();
      }
    },

    formatDateTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('default', {
        month: 'short',
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
/* Modern Component Styles */
.modern-component {
  --primary-color: #2563eb;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --info-color: #0891b2;
  --debug-color: #7c3aed;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.component-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.component-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.component-header h3 ion-icon {
  font-size: 20px;
  color: var(--primary-color);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-input {
  padding: 8px 12px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  width: 200px;
  background: var(--surface);
  color: var(--text-primary);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.component-description {
  margin-bottom: 20px;
  padding: 12px 16px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.component-description p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Log Stats */
.log-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px;
  box-shadow: var(--shadow);
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  font-size: 20px;
}

.stat-icon.info {
  background: rgba(8, 145, 178, 0.1);
  color: var(--info-color);
}

.stat-icon.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.stat-icon.error {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.stat-icon.success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.stat-label {
  font-size: 12px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

/* Log Cards */
.logs-container {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
}

.log-list {
  display: flex;
  flex-direction: column;
}

.log-card {
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
}

.log-card:last-child {
  border-bottom: none;
}

.log-card:hover {
  background: var(--background);
}

.log-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
}

.log-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 16px;
  flex-shrink: 0;
}

.log-type-info {
  background: rgba(8, 145, 178, 0.1);
  color: var(--info-color);
}

.log-type-warn {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.log-type-error {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.log-type-success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.log-type-debug {
  background: rgba(124, 58, 237, 0.1);
  color: var(--debug-color);
}

.log-info {
  flex: 1;
  min-width: 0;
}

.log-message {
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 4px;
  word-break: break-word;
}

.log-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.log-time {
  font-size: 12px;
  color: var(--text-muted);
}

.log-type {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 2px 6px;
  border-radius: 4px;
}

.log-env {
  font-size: 11px;
  color: var(--text-muted);
  background: var(--background);
  padding: 2px 6px;
  border-radius: 4px;
}

.log-actions {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.log-card:hover .log-actions {
  opacity: 1;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: var(--background);
  color: var(--text-secondary);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.log-preview {
  padding: 0 20px 16px 64px;
}

.preview-content {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px 12px;
}

.preview-content code {
  font-family: monospace;
  font-size: 12px;
  color: var(--text-secondary);
}

/* Load More */
.load-more-container {
  padding: 20px;
  text-align: center;
  border-top: 1px solid var(--border);
}

.loading-spin {
  animation: spin 1s linear infinite;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-secondary);
}

.empty-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.empty-state p {
  margin: 0 0 16px 0;
  font-size: 14px;
}

.empty-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  background: #1d4ed8;
}

.btn-secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 2px solid var(--border);
}

.btn-secondary:not(:disabled):hover {
  background: var(--background);
}

/* Modal Styles */
.modal-content {
  background: var(--background);
}

.modal-form {
  max-width: 600px;
  margin: 0 auto;
  padding: 24px;
}

.detail-header {
  text-align: center;
  margin-bottom: 24px;
}

.log-type-indicator {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-lg);
  font-weight: 600;
  margin-bottom: 8px;
}

.detail-time {
  font-size: 14px;
  color: var(--text-secondary);
}

.detail-content {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.detail-section {
  padding: 16px;
  border-bottom: 1px solid var(--border);
}

.detail-section:last-child {
  border-bottom: none;
}

.detail-section h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.detail-value {
  color: var(--text-secondary);
  line-height: 1.5;
}

.detail-value.message {
  font-weight: 500;
  color: var(--text-primary);
}

.detail-value.code {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px;
  margin-top: 8px;
}

.detail-value.code pre {
  margin: 0;
  font-family: monospace;
  font-size: 12px;
  line-height: 1.4;
  white-space: pre-wrap;
  word-break: break-all;
}

.modal-actions {
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

/* Responsive */
@media (max-width: 768px) {
  .component-header,
  .header-actions {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .header-actions {
    width: 100%;
  }

  .filter-controls {
    width: 100%;
    flex-direction: column;
  }

  .search-input {
    width: 100%;
  }

  .log-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .log-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .log-actions {
    opacity: 1;
    align-self: flex-end;
  }

  .log-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .empty-actions {
    flex-direction: column;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>
