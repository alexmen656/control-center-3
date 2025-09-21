<template>
  <div class="api-key-manager modern-component">
    <!-- Header (nur anzeigen wenn nicht versteckt) -->
    <div v-if="!hideTitle" class="component-header">
      <h3>
        <ion-icon name="key-outline"></ion-icon>
        API Keys
      </h3>
      <button @click="showCreateKeyModal = true" class="btn btn-primary">
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
      <button @click="showCreateKeyModal = true" class="btn btn-primary">
        <ion-icon name="add-outline"></ion-icon>
        Create Your First API Key
      </button>
    </div>

    <!-- Create Key Modal -->
    <ion-modal :is-open="showCreateKeyModal" @didDismiss="closeCreateModal">
      <ion-header>
        <ion-toolbar>
          <ion-title>Create New API Key</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeCreateModal">Cancel</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="modal-content">
        <div class="modal-form">
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
            />
            <div class="form-help">
              Leave empty for no expiration. Key will be automatically deactivated after this date.
            </div>
          </div>

          <div class="form-actions">
            <button @click="closeCreateModal" class="btn btn-secondary">
              Cancel
            </button>
            <button @click="createKey" class="btn btn-primary" :disabled="isCreating || !newKey.name.trim()">
              <ion-icon :name="isCreating ? 'hourglass-outline' : 'add-outline'"></ion-icon>
              {{ isCreating ? 'Creating...' : 'Create API Key' }}
            </button>
          </div>
        </div>
      </ion-content>
    </ion-modal>

    <!-- Success Modal for New Key -->
    <ion-modal :is-open="showSuccessModal" @didDismiss="closeSuccessModal">
      <ion-header>
        <ion-toolbar>
          <ion-title>API Key Created Successfully</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeSuccessModal">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="modal-content">
        <div class="success-content">
          <div class="success-icon">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
          </div>
          <h3>Your API Key is Ready!</h3>
          <p>Copy this key now - it won't be shown again in full.</p>
          
          <div class="new-key-display">
            <code>{{ createdKey }}</code>
            <button @click="copyToClipboard(createdKey)" class="copy-btn-large">
              <ion-icon name="copy-outline"></ion-icon>
              Copy Key
            </button>
          </div>
          
          <div class="usage-example">
            <h4>Usage Example:</h4>
            <pre><code>curl -X POST https://your-api.com/logs \
  -H "X-Api-Key: {{ createdKey }}" \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello World"}'</code></pre>
          </div>
        </div>
      </ion-content>
    </ion-modal>
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
  data() {
    return {
      apiKeys: [],
      loading: false,
      showCreateKeyModal: false,
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
        const response = await this.$axios.get('/backend/api/api_keys.php', {
          params: {
            project_id: this.projectId,
            service: this.service
          },
          headers: {
            'Authorization': localStorage.getItem('token')
          }
        });

        if (response.data.success) {
          this.apiKeys = response.data.data;
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

    async createKey() {
      if (!this.newKey.name.trim()) {
        this.$toast.error('Please enter a key name');
        return;
      }

      this.isCreating = true;
      try {
        const response = await this.$axios.post('/backend/api/api_keys.php', {
          project_id: this.projectId,
          service: this.service,
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
          this.showCreateKeyModal = false;
          this.showSuccessModal = true;
          this.resetNewKey();
          this.fetchApiKeys();
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
    },

    closeCreateModal() {
      this.showCreateKeyModal = false;
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
  font-size: 12px;
  color: var(--primary-color);
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

/* Key Cards */
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
  transition: all 0.2s ease;
}

.key-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}

.key-card.inactive {
  opacity: 0.6;
  background: var(--background);
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

.status-inactive {
  background: #fef2f2;
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
  padding: 12px 16px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.key-value {
  flex: 1;
  font-family: monospace;
  font-size: 14px;
  color: var(--text-primary);
  background: none;
  border: none;
  word-break: break-all;
}

.copy-btn {
  padding: 6px;
  border: none;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.copy-btn:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.key-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: var(--background);
  border-top: 1px solid var(--border);
}

.key-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
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
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.action-btn.danger {
  color: var(--danger-color);
  border-color: #fecaca;
}

.action-btn.danger:hover {
  background: #fef2f2;
  color: var(--danger-color);
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

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Success Modal */
.success-content {
  padding: 24px;
  text-align: center;
}

.success-icon {
  font-size: 64px;
  color: var(--success-color);
  margin-bottom: 16px;
}

.success-content h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.success-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.new-key-display {
  background: var(--background);
  border: 2px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.new-key-display code {
  flex: 1;
  font-family: monospace;
  font-size: 14px;
  color: var(--text-primary);
  word-break: break-all;
  background: none;
  padding: 0;
  border: none;
}

.copy-btn-large {
  padding: 10px 16px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.copy-btn-large:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.usage-example {
  text-align: left;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
}

.usage-example h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.usage-example pre {
  margin: 0;
  background: #f8f9fa;
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 12px;
  font-size: 12px;
  line-height: 1.4;
  overflow-x: auto;
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

  .new-key-display {
    flex-direction: column;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
        description: '',
        expires_at: null
      },
      createdKey: {
        api_key: '',
        name: '',
        created_at: ''
      },
      apiEndpoint: window.location.origin
    };
  },
  mounted() {
    this.fetchApiKeys();
  },
  methods: {
    fetchApiKeys() {
      if (!this.projectId) return;
      
      this.loading = true;
      this.$axios.get(`api/api_keys.php?project_id=${this.projectId}`, {
        headers: {
          'Authorization': localStorage.getItem('token')
        }
      })
      .then(response => {
        if (response.data.success) {
          this.apiKeys = response.data.data || [];
        } else {
          console.error('Error fetching API keys:', response.data.error);
          this.$toast.error(`Failed to load API keys: ${response.data.error}`);
        }
        this.loading = false;
      })
      .catch(error => {
        console.error('Error fetching API keys:', error);
        this.$toast.error('Failed to load API keys');
        this.loading = false;
      });
    },
    
    createApiKey() {
      this.loading = true;
      
      const payload = {
        project_id: this.projectId,
        name: this.newKey.name,
        description: this.newKey.description || null,
        expires_at: this.newKey.expires_at || null,
        permissions: ['write_logs']
      };
      
      this.$axios.post('api/api_keys.php', payload, {
        headers: {
          'Authorization': localStorage.getItem('token'),
          'Content-Type': 'application/json'
        }
      })
      .then(response => {
        if (response.data.success) {
          // Store the full API key for display
          this.createdKey = response.data.data;
          
          // Reset form
          this.newKey = {
            name: '',
            description: '',
            expires_at: null
          };
          
          // Close create modal and show new key modal
          this.showCreateKeyModal = false;
          this.showNewKeyModal = true;
          
          // Refresh the API keys list
          this.fetchApiKeys();
        } else {
          console.error('Error creating API key:', response.data.error);
          this.$toast.error(`Failed to create API key: ${response.data.error}`);
        }
        this.loading = false;
      })
      .catch(error => {
        console.error('Error creating API key:', error);
        this.$toast.error('Failed to create API key');
        this.loading = false;
      });
    },
    
    revokeKey(key) {
      if (confirm(`Are you sure you want to revoke the API key "${key.name}"? This action cannot be undone.`)) {
        this.loading = true;
        
        this.$axios.delete(`api/api_keys.php?id=${key.id}`, {
          headers: {
            'Authorization': localStorage.getItem('token')
          }
        })
        .then(response => {
          if (response.data.success) {
            this.$toast.success('API key revoked successfully');
            this.fetchApiKeys();
          } else {
            console.error('Error revoking API key:', response.data.error);
            this.$toast.error(`Failed to revoke API key: ${response.data.error}`);
          }
          this.loading = false;
        })
        .catch(error => {
          console.error('Error revoking API key:', error);
          this.$toast.error('Failed to revoke API key');
          this.loading = false;
        });
      }
    },
    
    closeNewKeyModal() {
      this.showNewKeyModal = false;
      // Clear the sensitive API key data from memory
      setTimeout(() => {
        this.createdKey = {
          api_key: '',
          name: '',
          created_at: ''
        };
      }, 500);
    },
    
    copyApiKey() {
      navigator.clipboard.writeText(this.createdKey.api_key)
        .then(() => {
          this.$toast.success('API key copied to clipboard');
        })
        .catch(() => {
          this.$toast.error('Failed to copy API key');
        });
    },
    
    formatDate(dateString) {

  align-items: center;
  margin-bottom: 1rem;
}

.keys-description {
  margin-bottom: 1rem;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
}

.keys-description code {
  background-color: rgba(0, 0, 0, 0.05);
  padding: 2px 4px;
  border-radius: 4px;
  font-family: monospace;
}

.key-value {
  font-family: monospace;
  color: var(--ion-color-medium);
}

.key-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
  font-size: 0.8rem;
}

.key-date {
  color: var(--ion-color-medium);
}

.key-status {
  color: var(--ion-color-success);
}

.key-status.inactive {
  color: var(--ion-color-danger);
}

.empty-keys {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  color: var(--ion-color-medium);
}

.empty-keys .hint {
  font-size: 0.9rem;
  margin-top: 8px;
}

.loading-keys {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.form-actions {
  margin-top: 2rem;
}

.new-key-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 1rem;
}

.warning {
  color: var(--ion-color-danger);
  font-weight: bold;
  margin: 1rem 0;
}

.key-display {
  display: flex;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.05);
  padding: 12px;
  border-radius: 4px;
  margin: 1rem 0;
  width: 100%;
}

.key-value-full {
  font-family: monospace;
  overflow-wrap: break-word;
  word-break: break-all;
  flex: 1;
  text-align: left;
}

.key-usage {
  width: 100%;
  text-align: left;
  margin: 1rem 0 2rem;
}

.key-usage h3 {
  margin-bottom: 0.5rem;
}

.key-usage pre {
  background-color: rgba(0, 0, 0, 0.05);
  padding: 12px;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.85rem;
}

.key-usage code {
