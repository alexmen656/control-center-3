<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <div class="settings-header">
              <ion-button fill="clear" @click="$router.go(-1)" class="back-button">
                <ion-icon slot="start" name="arrow-back-outline"></ion-icon>
                Back to {{ api.name }}
              </ion-button>
              <h1>
                <ion-icon name="settings-outline" class="title-icon"></ion-icon>
                API Settings
              </h1>
            </div>

            <!-- General Settings -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>General Configuration</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-item>
                  <ion-label position="floating">API Name</ion-label>
                  <ion-input v-model="settings.name" type="text"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-label position="floating">Description</ion-label>
                  <ion-textarea v-model="settings.description"></ion-textarea>
                </ion-item>
                <ion-item>
                  <ion-label position="floating">Base URL</ion-label>
                  <ion-input v-model="settings.base_url" type="url"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-label>Status</ion-label>
                  <ion-select v-model="settings.status" placeholder="Select Status">
                    <ion-select-option value="active">Active</ion-select-option>
                    <ion-select-option value="inactive">Inactive</ion-select-option>
                    <ion-select-option value="testing">Testing</ion-select-option>
                  </ion-select>
                </ion-item>
                <ion-item>
                  <ion-label position="floating">Rate Limit (requests/minute)</ion-label>
                  <ion-input v-model="settings.rate_limit" type="number" min="1"></ion-input>
                </ion-item>
                <div class="save-section">
                  <ion-button @click="saveGeneralSettings" color="primary">
                    <ion-icon slot="start" name="save-outline"></ion-icon>
                    Save General Settings
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- Authentication Settings -->
            <ion-card>
              <ion-card-header>
                <div class="card-header-with-action">
                  <ion-card-title>Authentication Settings</ion-card-title>
                  <ion-button @click="openAddKeyModal" size="small">
                    <ion-icon slot="start" name="add-outline"></ion-icon>
                    Add Key
                  </ion-button>
                </div>
              </ion-card-header>
              <ion-card-content>
                <ion-item>
                  <ion-label>Authentication Type</ion-label>
                  <ion-select v-model="settings.auth_type" placeholder="Select Auth Type">
                    <ion-select-option value="none">No Authentication</ion-select-option>
                    <ion-select-option value="api_key">API Key</ion-select-option>
                    <ion-select-option value="bearer">Bearer Token</ion-select-option>
                    <ion-select-option value="oauth2">OAuth 2.0</ion-select-option>
                    <ion-select-option value="basic">Basic Auth</ion-select-option>
                  </ion-select>
                </ion-item>
                
                <div v-if="apiKeys.length > 0" class="keys-section">
                  <h3>API Keys</h3>
                  <div v-for="key in apiKeys" :key="key.id" class="key-item">
                    <div class="key-info">
                      <strong>{{ key.key_name }}</strong>
                      <div class="key-value">
                        <span v-if="key.is_encrypted">***encrypted***</span>
                        <span v-else class="key-visible">{{ key.key_value }}</span>
                        <ion-button fill="clear" size="small" @click="toggleKeyVisibility(key)">
                          <ion-icon :name="key.is_encrypted ? 'eye-outline' : 'eye-off-outline'"></ion-icon>
                        </ion-button>
                      </div>
                    </div>
                    <div class="key-actions">
                      <ion-button fill="clear" size="small" @click="editKey(key)" color="primary">
                        <ion-icon slot="icon-only" name="create-outline"></ion-icon>
                      </ion-button>
                      <ion-button fill="clear" size="small" @click="deleteKey(key)" color="danger">
                        <ion-icon slot="icon-only" name="trash-outline"></ion-icon>
                      </ion-button>
                    </div>
                  </div>
                </div>
                <div v-else class="no-keys">
                  <ion-icon name="key-outline" size="large" color="medium"></ion-icon>
                  <p>No API keys configured</p>
                  <ion-button @click="openAddKeyModal" fill="outline">Add First Key</ion-button>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- Security Settings -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>Security & Monitoring</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-item>
                  <ion-checkbox v-model="settings.enable_logging"></ion-checkbox>
                  <ion-label class="ion-margin-start">
                    <h3>Enable Request Logging</h3>
                    <p>Log all API requests for monitoring and debugging</p>
                  </ion-label>
                </ion-item>
                <ion-item>
                  <ion-checkbox v-model="settings.enable_rate_limiting"></ion-checkbox>
                  <ion-label class="ion-margin-start">
                    <h3>Enable Rate Limiting</h3>
                    <p>Apply rate limits to prevent API abuse</p>
                  </ion-label>
                </ion-item>
                <ion-item>
                  <ion-checkbox v-model="settings.require_https"></ion-checkbox>
                  <ion-label class="ion-margin-start">
                    <h3>Require HTTPS</h3>
                    <p>Only allow secure HTTPS connections</p>
                  </ion-label>
                </ion-item>
                <div class="save-section">
                  <ion-button @click="saveSecuritySettings" color="primary">
                    <ion-icon slot="start" name="shield-checkmark-outline"></ion-icon>
                    Save Security Settings
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- Danger Zone -->
            <ion-card color="danger">
              <ion-card-header>
                <ion-card-title>Danger Zone</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div class="danger-item">
                  <div>
                    <h3>Reset API</h3>
                    <p>Remove all endpoints, keys, and reset to default configuration</p>
                  </div>
                  <ion-button @click="confirmReset" color="light" fill="outline">
                    <ion-icon slot="start" name="refresh-outline"></ion-icon>
                    Reset API
                  </ion-button>
                </div>
                <div class="danger-item">
                  <div>
                    <h3>Delete API</h3>
                    <p>Permanently delete this API and all associated data</p>
                  </div>
                  <ion-button @click="confirmDelete" color="light">
                    <ion-icon slot="start" name="trash-outline"></ion-icon>
                    Delete API
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>

      <!-- Add/Edit Key Modal -->
      <ion-modal :is-open="isKeyModalOpen" ref="keyModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeKeyModal">Cancel</ion-button>
            </ion-buttons>
            <ion-title>{{ isEditingKey ? 'Edit API Key' : 'Add API Key' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="saveKey">Save</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-item>
            <ion-label position="floating">Key Name *</ion-label>
            <ion-input v-model="currentKey.key_name" type="text" placeholder="e.g., API Key, Authorization Token"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Key Value *</ion-label>
            <ion-input v-model="currentKey.key_value" type="text" placeholder="Enter the actual key/token value"></ion-input>
          </ion-item>
          <ion-item>
            <ion-checkbox v-model="currentKey.is_encrypted"></ion-checkbox>
            <ion-label class="ion-margin-start">
              <h3>Encrypt this key</h3>
              <p>Store the key value encrypted in the database</p>
            </ion-label>
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  IonPage, IonContent, IonGrid, IonRow, IonCol, IonButton, IonIcon,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel,
  IonInput, IonTextarea, IonSelect, IonSelectOption, IonCheckbox,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle,
  alertController, toastController
} from '@ionic/vue';
import axios from 'axios';

interface ApiKey {
  id?: number;
  key_name: string;
  key_value: string;
  is_encrypted: boolean;
}

interface ApiSettings {
  id?: number;
  name: string;
  description: string;
  base_url: string;
  auth_type: string;
  status: string;
  rate_limit: number;
  enable_logging: boolean;
  enable_rate_limiting: boolean;
  require_https: boolean;
}

export default defineComponent({
  name: 'ApiSettings',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonButton, IonIcon,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel,
    IonInput, IonTextarea, IonSelect, IonSelectOption, IonCheckbox,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const api = ref({ name: 'Weather API', id: 1 });
    const settings = ref<ApiSettings>({
      name: '',
      description: '',
      base_url: '',
      auth_type: 'none',
      status: 'inactive',
      rate_limit: 100,
      enable_logging: true,
      enable_rate_limiting: true,
      require_https: true
    });

    const apiKeys = ref<ApiKey[]>([]);
    const isKeyModalOpen = ref(false);
    const isEditingKey = ref(false);
    const currentKey = ref<ApiKey>({
      key_name: '',
      key_value: '',
      is_encrypted: false
    });

    const loadApiSettings = async () => {
      try {
        // Mock data - in real implementation, load from API
        settings.value = {
          id: 1,
          name: 'Weather API',
          description: 'Get weather information for any location',
          base_url: 'https://api.openweathermap.org/data/2.5',
          auth_type: 'api_key',
          status: 'active',
          rate_limit: 1000,
          enable_logging: true,
          enable_rate_limiting: true,
          require_https: true
        };

        apiKeys.value = [
          {
            id: 1,
            key_name: 'OpenWeather API Key',
            key_value: 'sk-1234567890abcdef',
            is_encrypted: false
          }
        ];
      } catch (error) {
        console.error('Error loading API settings:', error);
        showToast('Error loading settings', 'danger');
      }
    };

    const saveGeneralSettings = async () => {
      try {
        // In real implementation, make API call to save settings
        showToast('General settings saved successfully', 'success');
      } catch (error) {
        console.error('Error saving general settings:', error);
        showToast('Error saving settings', 'danger');
      }
    };

    const saveSecuritySettings = async () => {
      try {
        // In real implementation, make API call to save security settings
        showToast('Security settings saved successfully', 'success');
      } catch (error) {
        console.error('Error saving security settings:', error);
        showToast('Error saving settings', 'danger');
      }
    };

    const openAddKeyModal = () => {
      isEditingKey.value = false;
      currentKey.value = {
        key_name: '',
        key_value: '',
        is_encrypted: false
      };
      isKeyModalOpen.value = true;
    };

    const editKey = (key: ApiKey) => {
      isEditingKey.value = true;
      currentKey.value = { ...key };
      isKeyModalOpen.value = true;
    };

    const closeKeyModal = () => {
      isKeyModalOpen.value = false;
      isEditingKey.value = false;
    };

    const saveKey = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        
        if (isEditingKey.value) {
          // Update existing key
          const index = apiKeys.value.findIndex(k => k.id === currentKey.value.id);
          if (index !== -1) {
            apiKeys.value[index] = { ...currentKey.value };
          }
        } else {
          // Add new key
          const newKey = {
            ...currentKey.value,
            id: Date.now() // Mock ID
          };
          apiKeys.value.push(newKey);
        }

        showToast('API key saved successfully', 'success');
        closeKeyModal();
      } catch (error) {
        console.error('Error saving API key:', error);
        showToast('Error saving API key', 'danger');
      }
    };

    const deleteKey = async (key: ApiKey) => {
      const alert = await alertController.create({
        header: 'Confirm Delete',
        message: `Are you sure you want to delete the key "${key.key_name}"?`,
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Delete',
            role: 'destructive',
            handler: () => {
              const index = apiKeys.value.findIndex(k => k.id === key.id);
              if (index !== -1) {
                apiKeys.value.splice(index, 1);
                showToast('API key deleted successfully', 'success');
              }
            }
          }
        ]
      });
      await alert.present();
    };

    const toggleKeyVisibility = (key: ApiKey) => {
      // In real implementation, you might want to make an API call to decrypt
      key.is_encrypted = !key.is_encrypted;
      if (!key.is_encrypted) {
        // Show actual key value
        setTimeout(() => {
          key.is_encrypted = true; // Auto-hide after 10 seconds
        }, 10000);
      }
    };

    const confirmReset = async () => {
      const alert = await alertController.create({
        header: 'Reset API',
        message: 'This will remove all endpoints, keys, and reset the API to default configuration. This action cannot be undone.',
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Reset',
            role: 'destructive',
            handler: () => resetApi()
          }
        ]
      });
      await alert.present();
    };

    const resetApi = async () => {
      try {
        // In real implementation, make API call to reset
        showToast('API reset successfully', 'success');
        router.push(`/project/${route.params.project}/apis/${route.params.apiSlug}`);
      } catch (error) {
        console.error('Error resetting API:', error);
        showToast('Error resetting API', 'danger');
      }
    };

    const confirmDelete = async () => {
      const alert = await alertController.create({
        header: 'Delete API',
        message: 'This will permanently delete this API and all associated data. This action cannot be undone.',
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Delete',
            role: 'destructive',
            handler: () => deleteApi()
          }
        ]
      });
      await alert.present();
    };

    const deleteApi = async () => {
      try {
        // In real implementation, make API call to delete
        showToast('API deleted successfully', 'success');
        router.push(`/project/${route.params.project}/manage/apis`);
      } catch (error) {
        console.error('Error deleting API:', error);
        showToast('Error deleting API', 'danger');
      }
    };

    const showToast = async (message: string, color: string) => {
      const toast = await toastController.create({
        message,
        duration: 3000,
        color,
        position: 'top'
      });
      await toast.present();
    };

    onMounted(() => {
      loadApiSettings();
    });

    return {
      api,
      settings,
      apiKeys,
      isKeyModalOpen,
      isEditingKey,
      currentKey,
      saveGeneralSettings,
      saveSecuritySettings,
      openAddKeyModal,
      editKey,
      closeKeyModal,
      saveKey,
      deleteKey,
      toggleKeyVisibility,
      confirmReset,
      confirmDelete
    };
  }
});
</script>

<style scoped>
.settings-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.settings-header h1 {
  color: var(--ion-color-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.title-icon {
  font-size: 2rem;
}

.card-header-with-action {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.save-section {
  margin-top: 20px;
  text-align: center;
}

.keys-section {
  margin-top: 20px;
}

.keys-section h3 {
  color: var(--ion-color-primary);
  margin-bottom: 16px;
}

.key-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid var(--ion-color-step-200);
  border-radius: 8px;
  margin-bottom: 8px;
  background: var(--ion-color-step-50);
}

.key-info {
  flex: 1;
}

.key-info strong {
  display: block;
  color: var(--ion-color-primary);
  margin-bottom: 4px;
}

.key-value {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
}

.key-visible {
  background: var(--ion-color-warning-tint);
  color: var(--ion-color-warning-contrast);
  padding: 2px 6px;
  border-radius: 4px;
}

.key-actions {
  display: flex;
  gap: 4px;
}

.no-keys {
  text-align: center;
  padding: 32px;
  color: var(--ion-color-medium);
}

.no-keys ion-icon {
  margin-bottom: 16px;
}

.danger-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.danger-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.danger-item h3 {
  color: var(--ion-color-light);
  margin: 0 0 4px 0;
}

.danger-item p {
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  font-size: 0.9em;
}

@media (max-width: 768px) {
  .settings-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .danger-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .key-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .key-actions {
    align-self: flex-end;
  }
}
</style>
