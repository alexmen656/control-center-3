<template>
  <div class="api-key-manager modern-component">
    <!-- Header (nur anzeigen wenn nicht versteckt) -->
    <div v-if="!hideTitle" class="component-header">
      <h3>
        <ion-icon name="key-outline"></ion-icon>
        API Keys
      </h3>
      <button @click="$emit('create-key')" class="btn btn-primary">
        <ion-icon name="add-outline"></ion-icon>
        Create New Key
      </button>
    </div>

    <!-- Description -->
    <div class="component-description">
      <p>
        API keys allow external services to send logs to this service.
        Use these keys in your API requests with the <code>X-Api-Key</code> header.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
      <p>Loading API keys...</p>
    </div>

    <!-- API Keys List -->
    <div v-else-if="apiKeys.length > 0" class="keys-list">
      <div 
        v-for="key in apiKeys" 
        :key="key.id" 
        class="key-card"
        :class="{ 'inactive': !key.active }"
      >
        <div class="key-header">
          <div class="key-info">
            <h4>{{ key.name }}</h4>
            <p v-if="key.description">{{ key.description }}</p>
          </div>
          <div class="key-status">
            <span 
              class="status-badge"
              :class="key.active ? 'status-active' : 'status-inactive'"
            >
              <ion-icon :name="key.active ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              {{ key.active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <div class="key-body">
          <div class="key-value-container">
            <code class="key-value">{{ key.api_key }}</code>
            <button @click="copyToClipboard(key.api_key)" class="copy-btn" title="Copy API Key">
              <ion-icon name="copy-outline"></ion-icon>
            </button>
          </div>
        </div>

        <div class="key-footer">
          <div class="key-meta">
            <span class="meta-item">
              <ion-icon name="calendar-outline"></ion-icon>
              Created: {{ formatDate(key.created_at) }}
            </span>
            <span v-if="key.expires_at" class="meta-item">
              <ion-icon name="time-outline"></ion-icon>
              Expires: {{ formatDate(key.expires_at) }}
            </span>
            <span v-if="key.service" class="meta-item">
              <ion-icon name="layers-outline"></ion-icon>
              Service: {{ key.service }}
            </span>
          </div>
          <div class="key-actions">
            <button @click="revokeKey(key)" class="action-btn danger" title="Revoke Key">
              <ion-icon name="trash-outline"></ion-icon>
              Revoke
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <ion-icon name="key-outline" class="empty-icon"></ion-icon>
      <h3>No API Keys Found</h3>
      <p>Create an API key to allow external services to send logs to this service.</p>
      <button @click="$emit('create-key')" class="btn btn-primary">
        <ion-icon name="add-outline"></ion-icon>
        Create Your First API Key
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ApiKeyManager',
  props: {
    projectId: {
      type: String,
      required: true
    },
    service: {
      type: String,
      required: true
    },
    hideTitle: {
      type: Boolean,
      default: false
    }
  },
  emits: ['create-key'],
  data() {
    return {
      apiKeys: [],
      loading: false
    };
  },
  computed: {
    apiEndpoint() {
      return window.location.origin + '/backend/api';
    }
  },
  created() {
    this.fetchApiKeys();
  },
  methods: {
    async fetchApiKeys() {
      this.loading = true;
      try {
        const response = await this.$axios.get('/api/api_keys.php', {
          params: {
            project_id: this.projectId,
            service: this.service
          },
          headers: {
            'Authorization': localStorage.getItem('token')
          }
        });

        if (response.data.success) {
          this.apiKeys = response.data.data || [];
        } else {
          this.$toast.error('Failed to load API keys: ' + response.data.error);
        }
      } catch (error) {
        console.error('Error fetching API keys:', error);
        this.$toast.error('Failed to load API keys');
      } finally {
        this.loading = false;
      }
    },

    async revokeKey(key) {
      if (!confirm(`Are you sure you want to revoke the API key "${key.name}"?`)) {
        return;
      }

      try {
        const response = await this.$axios.delete(`/backend/api/api_keys.php?id=${key.id}`, {
          headers: {
            'Authorization': localStorage.getItem('token')
          }
        });

        if (response.data.success) {
          this.fetchApiKeys();
          this.$toast.success('API key revoked successfully');
        } else {
          this.$toast.error('Failed to revoke API key: ' + response.data.error);
        }
      } catch (error) {
        console.error('Error revoking API key:', error);
        this.$toast.error('Failed to revoke API key');
      }
    },

    copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        this.$toast.success('Copied to clipboard');
      }).catch(() => {
        this.$toast.error('Failed to copy to clipboard');
      });
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('default', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

.component-description code {
  background: var(--surface);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  color: var(--text-primary);
  border: 1px solid var(--border);
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

/* API Keys List */
.keys-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.key-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: all 0.2s ease;
}

.key-card:hover {
  box-shadow: var(--shadow-md);
}

.key-card.inactive {
  opacity: 0.6;
}

.key-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}

.key-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.key-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-inactive {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.key-body {
  padding: 16px 20px;
}

.key-value-container {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 16px;
}

.key-value {
  flex: 1;
  font-family: monospace;
  font-size: 14px;
  color: var(--text-primary);
  word-break: break-all;
  background: none;
  border: none;
  padding: 0;
}

.copy-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 16px;
}

.copy-btn:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.key-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: var(--background);
  border-top: 1px solid var(--border);
}

.key-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--text-muted);
}

.meta-item ion-icon {
  font-size: 14px;
}

.key-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border: none;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn.danger {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.action-btn.danger:hover {
  background: var(--danger-color);
  color: white;
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

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .component-header,
  .key-header,
  .key-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .key-meta {
    flex-direction: column;
    gap: 8px;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
