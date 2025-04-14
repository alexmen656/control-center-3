<template>
  <div class="api-key-manager">
    <div class="keys-header">
      <h3>
        <ion-icon name="key-outline" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
        API Keys
      </h3>
      <ion-button size="small" @click="showCreateKeyModal = true">
        <ion-icon slot="start" name="add-outline"></ion-icon>
        Create New Key
      </ion-button>
    </div>

    <div class="keys-description">
      <p>
        API keys allow external services to send logs to this service.
        Use these keys in your API requests with the <code>X-Api-Key</code> header.
      </p>
    </div>

    <div class="keys-container">
      <ion-list v-if="apiKeys.length > 0">
        <ion-item v-for="key in apiKeys" :key="key.id" lines="full">
          <ion-label>
            <h2>{{ key.name }}</h2>
            <p class="key-value">{{ key.api_key }}</p>
            <p v-if="key.description">{{ key.description }}</p>
            <div class="key-meta">
              <span class="key-date">Created: {{ formatDate(key.created_at) }}</span>
              <span class="key-status" :class="{ inactive: !key.active }">
                {{ key.active ? 'Active' : 'Inactive' }}
              </span>
              <span v-if="key.expires_at" class="key-date">
                Expires: {{ formatDate(key.expires_at) }}
              </span>
            </div>
          </ion-label>
          <ion-button fill="clear" slot="end" color="danger" @click="revokeKey(key)">
            <ion-icon slot="icon-only" name="trash-outline"></ion-icon>
          </ion-button>
        </ion-item>
      </ion-list>
      <div v-else-if="!loading" class="empty-keys">
        <ion-icon name="key" size="large"></ion-icon>
        <p>No API keys found</p>
        <p class="hint">Create an API key to allow external services to send logs</p>
      </div>
      <div v-if="loading" class="loading-keys">
        <ion-spinner name="circular"></ion-spinner>
        <p>Loading API keys...</p>
      </div>
    </div>

    <!-- Create API Key Modal -->
    <ion-modal :is-open="showCreateKeyModal" @didDismiss="showCreateKeyModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Create API Key</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showCreateKeyModal = false">Cancel</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <form @submit.prevent="createApiKey">
          <ion-item>
            <ion-label position="floating">Name <span class="required">*</span></ion-label>
            <ion-input v-model="newKey.name" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Description</ion-label>
            <ion-textarea v-model="newKey.description" rows="3"></ion-textarea>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Expires At</ion-label>
            <ion-datetime v-model="newKey.expires_at" presentation="date"></ion-datetime>
          </ion-item>
          
          <div class="form-actions">
            <ion-button type="submit" expand="block">Create API Key</ion-button>
          </div>
        </form>
      </ion-content>
    </ion-modal>

    <!-- New Key Created Modal -->
    <ion-modal :is-open="showNewKeyModal" @didDismiss="closeNewKeyModal">
      <ion-header>
        <ion-toolbar>
          <ion-title>API Key Created</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeNewKeyModal">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <div class="new-key-container">
          <ion-icon name="checkmark-circle" color="success" size="large"></ion-icon>
          <h2>Your API key has been created</h2>
          <p class="warning">Save this key now! You won't be able to see it again.</p>
          
          <div class="key-display">
            <div class="key-value-full">{{ createdKey.api_key }}</div>
            <ion-button size="small" fill="clear" @click="copyApiKey">
              <ion-icon slot="icon-only" name="copy-outline"></ion-icon>
            </ion-button>
          </div>

          <div class="key-usage">
            <h3>Usage Example</h3>
            <pre><code>curl -X POST "{{ apiEndpoint }}/service_logs.php" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: {{ createdKey.api_key }}" \
  -d '{
    "project_id": "{{ projectId }}",
    "service": "{{ service }}",
    "message": "Your log message",
    "type": "info",
    "environment": "production",
    "data": { "additional": "data" }
  }'</code></pre>
          </div>

          <ion-button expand="block" @click="closeNewKeyModal">Done</ion-button>
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
    }
  },
  data() {
    return {
      apiKeys: [],
      loading: false,
      showCreateKeyModal: false,
      showNewKeyModal: false,
      newKey: {
        name: '',
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
      if (!dateString) return '';
      
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('default', { 
        year: 'numeric',
        month: 'short', 
        day: 'numeric'
      }).format(date);
    }
  }
};
</script>

<style scoped>
.api-key-manager {
  margin: 1rem 0;
}

.keys-header {
  display: flex;
  justify-content: space-between;
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
  font-family: monospace;
}

.required {
  color: var(--ion-color-danger);
}
</style>