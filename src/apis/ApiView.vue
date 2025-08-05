<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <div class="api-header">
              <ion-button fill="clear" @click="$router.go(-1)" class="back-button">
                <ion-icon slot="start" name="arrow-back-outline"></ion-icon>
                Back
              </ion-button>
              <div class="api-title">
                <h1>
                  <ion-icon :name="api.icon" class="title-icon"></ion-icon>
                  {{ api.name }}
                </h1>
                <ion-badge :color="getStatusColor(api.status)">{{ api.status }}</ion-badge>
              </div>
              <div class="api-actions">
                <ion-button @click="testApi" color="secondary">
                  <ion-icon slot="start" name="play-outline"></ion-icon>
                  Test API
                </ion-button>
                <ion-button @click="openSettings" fill="outline">
                  <ion-icon slot="start" name="settings-outline"></ion-icon>
                  Settings
                </ion-button>
              </div>
            </div>

            <div class="api-info-cards">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>API Information</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <div class="info-row">
                    <strong>Type:</strong> {{ api.type }}
                  </div>
                  <div class="info-row">
                    <strong>Base URL:</strong> 
                    <a :href="api.base_url" target="_blank" v-if="api.base_url">{{ api.base_url }}</a>
                    <span v-else class="no-data">Not configured</span>
                  </div>
                  <div class="info-row">
                    <strong>Authentication:</strong> {{ formatAuthType(api.auth_type) }}
                  </div>
                  <div class="info-row">
                    <strong>Rate Limit:</strong> {{ api.rate_limit }} requests/minute
                  </div>
                  <div class="info-row" v-if="api.description">
                    <strong>Description:</strong> {{ api.description }}
                  </div>
                </ion-card-content>
              </ion-card>

              <ion-card v-if="api.keys && api.keys.length > 0">
                <ion-card-header>
                  <ion-card-title>Authentication Keys</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-item v-for="key in api.keys" :key="key.id" lines="none">
                    <ion-label>
                      <h3>{{ key.key_name }}</h3>
                      <p>{{ key.key_value }}</p>
                    </ion-label>
                    <ion-icon v-if="key.is_encrypted" name="lock-closed-outline" slot="end" color="warning"></ion-icon>
                  </ion-item>
                </ion-card-content>
              </ion-card>
            </div>

            <ion-card class="endpoints-card">
              <ion-card-header>
                <div class="card-header-with-action">
                  <ion-card-title>Endpoints</ion-card-title>
                  <ion-button @click="openAddEndpointModal" size="small">
                    <ion-icon slot="start" name="add-outline"></ion-icon>
                    Add Endpoint
                  </ion-button>
                </div>
              </ion-card-header>
              <ion-card-content>
                <div v-if="api.endpoints && api.endpoints.length > 0" class="endpoints-list">
                  <div v-for="endpoint in api.endpoints" :key="endpoint.id" class="endpoint-item">
                    <div class="endpoint-header">
                      <ion-badge :color="getMethodColor(endpoint.method)" class="method-badge">
                        {{ endpoint.method }}
                      </ion-badge>
                      <h3>{{ endpoint.name }}</h3>
                      <div class="endpoint-actions">
                        <ion-button fill="clear" size="small" @click="testEndpoint(endpoint)">
                          <ion-icon slot="icon-only" name="play-outline"></ion-icon>
                        </ion-button>
                        <ion-button fill="clear" size="small" @click="editEndpoint(endpoint)">
                          <ion-icon slot="icon-only" name="create-outline"></ion-icon>
                        </ion-button>
                      </div>
                    </div>
                    <div class="endpoint-details">
                      <p class="endpoint-path">{{ endpoint.endpoint }}</p>
                      <p class="endpoint-description" v-if="endpoint.description">{{ endpoint.description }}</p>
                    </div>
                    <div class="endpoint-params" v-if="endpoint.parameters && endpoint.parameters.length > 0">
                      <strong>Parameters:</strong>
                      <div class="params-list">
                        <span v-for="param in endpoint.parameters" :key="param.name" class="param-tag">
                          {{ param.name }} ({{ param.type }})
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="no-endpoints">
                  <ion-icon name="code-slash-outline" size="large" color="medium"></ion-icon>
                  <p>No endpoints configured yet</p>
                  <ion-button @click="openAddEndpointModal" fill="outline">Add First Endpoint</ion-button>
                </div>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>

      <!-- Add/Edit Endpoint Modal -->
      <ion-modal :is-open="isEndpointModalOpen" ref="endpointModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeEndpointModal">Cancel</ion-button>
            </ion-buttons>
            <ion-title>{{ isEditingEndpoint ? 'Edit Endpoint' : 'Add New Endpoint' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="saveEndpoint">Save</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-item>
            <ion-label position="floating">Name *</ion-label>
            <ion-input v-model="currentEndpoint.name" type="text" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label>Method</ion-label>
            <ion-select v-model="currentEndpoint.method" placeholder="Select Method">
              <ion-select-option value="GET">GET</ion-select-option>
              <ion-select-option value="POST">POST</ion-select-option>
              <ion-select-option value="PUT">PUT</ion-select-option>
              <ion-select-option value="DELETE">DELETE</ion-select-option>
              <ion-select-option value="PATCH">PATCH</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Endpoint Path *</ion-label>
            <ion-input v-model="currentEndpoint.endpoint" type="text" placeholder="/users" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Description</ion-label>
            <ion-textarea v-model="currentEndpoint.description"></ion-textarea>
          </ion-item>
        </ion-content>
      </ion-modal>

      <!-- Test Results Modal -->
      <ion-modal :is-open="isTestModalOpen" ref="testModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeTestModal">Close</ion-button>
            </ion-buttons>
            <ion-title>Test Results</ion-title>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <div v-if="testResults" class="test-results">
            <div class="result-status" :class="getStatusClass(testResults.status)">
              Status: {{ testResults.status }}
            </div>
            <pre class="result-data">{{ JSON.stringify(testResults.data, null, 2) }}</pre>
          </div>
          <div v-if="testLoading" class="test-loading">
            <ion-spinner></ion-spinner>
            <p>Testing API...</p>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  IonPage, IonContent, IonGrid, IonRow, IonCol, IonButton, IonIcon, 
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel,
  IonBadge, IonModal, IonHeader, IonToolbar, IonButtons, IonTitle,
  IonInput, IonTextarea, IonSelect, IonSelectOption, IonSpinner,
  toastController
} from '@ionic/vue';
import axios from 'axios';

interface ApiEndpoint {
  id?: number;
  name: string;
  method: string;
  endpoint: string;
  description: string;
  parameters: any[];
  headers: any[];
  response_example: any;
  is_active: boolean;
}

interface ApiKey {
  id: number;
  key_name: string;
  key_value: string;
  is_encrypted: boolean;
}

interface ApiData {
  id?: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  type: string;
  base_url: string;
  auth_type: string;
  status: string;
  rate_limit: number;
  keys?: ApiKey[];
  endpoints?: ApiEndpoint[];
}

export default defineComponent({
  name: 'ApiView',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonButton, IonIcon,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel,
    IonBadge, IonModal, IonHeader, IonToolbar, IonButtons, IonTitle,
    IonInput, IonTextarea, IonSelect, IonSelectOption, IonSpinner
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const api = ref<ApiData>({
      name: '',
      slug: '',
      description: '',
      icon: 'code-outline',
      type: 'REST',
      base_url: '',
      auth_type: 'none',
      status: 'inactive',
      rate_limit: 100
    });

    const isEndpointModalOpen = ref(false);
    const isEditingEndpoint = ref(false);
    const isTestModalOpen = ref(false);
    const testLoading = ref(false);
    const testResults = ref(null);

    const currentEndpoint = ref<ApiEndpoint>({
      name: '',
      method: 'GET',
      endpoint: '',
      description: '',
      parameters: [],
      headers: [],
      response_example: {},
      is_active: true
    });

    const loadApiData = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        // For now, we'll load a mock API since we need the API ID
        // In real implementation, you'd get this from the route or make a separate call
        api.value = {
          id: 1,
          name: 'Weather API',
          slug: 'weather-api',
          description: 'Get weather information for any location',
          icon: 'cloud-outline',
          type: 'REST',
          base_url: 'https://api.openweathermap.org/data/2.5',
          auth_type: 'api_key',
          status: 'active',
          rate_limit: 1000,
          keys: [
            {
              id: 1,
              key_name: 'API Key',
              key_value: '***hidden***',
              is_encrypted: true
            }
          ],
          endpoints: [
            {
              id: 1,
              name: 'Current Weather',
              method: 'GET',
              endpoint: '/weather',
              description: 'Get current weather data for a location',
              parameters: [
                { name: 'q', type: 'string', required: true, description: 'City name' },
                { name: 'appid', type: 'string', required: true, description: 'API key' }
              ],
              headers: [],
              response_example: {},
              is_active: true
            }
          ]
        };
      } catch (error) {
        console.error('Error loading API data:', error);
        showToast('Error loading API data', 'danger');
      }
    };

    const getStatusColor = (status: string) => {
      switch (status) {
        case 'active': return 'success';
        case 'testing': return 'warning';
        case 'inactive': return 'danger';
        default: return 'medium';
      }
    };

    const getMethodColor = (method: string) => {
      switch (method) {
        case 'GET': return 'primary';
        case 'POST': return 'success';
        case 'PUT': return 'warning';
        case 'DELETE': return 'danger';
        case 'PATCH': return 'tertiary';
        default: return 'medium';
      }
    };

    const formatAuthType = (authType: string) => {
      const types = {
        'none': 'No Authentication',
        'api_key': 'API Key',
        'bearer': 'Bearer Token',
        'oauth2': 'OAuth 2.0',
        'basic': 'Basic Auth'
      };
      return types[authType] || authType;
    };

    const openSettings = () => {
      router.push(`/project/${route.params.project}/apis/${route.params.apiSlug}/settings`);
    };

    const testApi = () => {
      testLoading.value = true;
      isTestModalOpen.value = true;
      
      // Mock test results
      setTimeout(() => {
        testResults.value = {
          status: 200,
          data: {
            message: 'API is working correctly',
            timestamp: new Date().toISOString()
          }
        };
        testLoading.value = false;
      }, 2000);
    };

    const testEndpoint = (endpoint: ApiEndpoint) => {
      console.log('Testing endpoint:', endpoint);
      showToast(`Testing ${endpoint.name}...`, 'primary');
    };

    const openAddEndpointModal = () => {
      isEditingEndpoint.value = false;
      currentEndpoint.value = {
        name: '',
        method: 'GET',
        endpoint: '',
        description: '',
        parameters: [],
        headers: [],
        response_example: {},
        is_active: true
      };
      isEndpointModalOpen.value = true;
    };

    const editEndpoint = (endpoint: ApiEndpoint) => {
      isEditingEndpoint.value = true;
      currentEndpoint.value = { ...endpoint };
      isEndpointModalOpen.value = true;
    };

    const closeEndpointModal = () => {
      isEndpointModalOpen.value = false;
      isEditingEndpoint.value = false;
    };

    const closeTestModal = () => {
      isTestModalOpen.value = false;
      testResults.value = null;
      testLoading.value = false;
    };

    const saveEndpoint = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        const payload = {
          addEndpoint: true,
          apiId: api.value.id,
          name: currentEndpoint.value.name,
          method: currentEndpoint.value.method,
          endpoint: currentEndpoint.value.endpoint,
          description: currentEndpoint.value.description,
          parameters: JSON.stringify(currentEndpoint.value.parameters),
          headers: JSON.stringify(currentEndpoint.value.headers),
          responseExample: JSON.stringify(currentEndpoint.value.response_example)
        };

        // In real implementation, make API call here
        showToast('Endpoint saved successfully', 'success');
        closeEndpointModal();
        // Reload API data
        loadApiData();
      } catch (error) {
        console.error('Error saving endpoint:', error);
        showToast('Error saving endpoint', 'danger');
      }
    };

    const getStatusClass = (status: number) => {
      if (status >= 200 && status < 300) return 'success';
      if (status >= 400 && status < 500) return 'warning';
      if (status >= 500) return 'danger';
      return 'medium';
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
      loadApiData();
    });

    return {
      api,
      isEndpointModalOpen,
      isEditingEndpoint,
      isTestModalOpen,
      testLoading,
      testResults,
      currentEndpoint,
      getStatusColor,
      getMethodColor,
      formatAuthType,
      openSettings,
      testApi,
      testEndpoint,
      openAddEndpointModal,
      editEndpoint,
      closeEndpointModal,
      closeTestModal,
      saveEndpoint,
      getStatusClass
    };
  }
});
</script>

<style scoped>
.api-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 16px;
}

.back-button {
  align-self: flex-start;
}

.api-title {
  flex: 1;
  text-align: center;
}

.api-title h1 {
  color: var(--ion-color-primary);
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.title-icon {
  font-size: 2rem;
}

.api-actions {
  display: flex;
  gap: 8px;
  align-self: flex-start;
}

.api-info-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

@media (max-width: 768px) {
  .api-info-cards {
    grid-template-columns: 1fr;
  }
  
  .api-header {
    flex-direction: column;
    align-items: center;
  }
  
  .api-title {
    text-align: center;
    order: -1;
  }
}

.info-row {
  margin-bottom: 12px;
  display: flex;
  gap: 8px;
}

.info-row strong {
  min-width: 120px;
  color: var(--ion-color-primary);
}

.no-data {
  color: var(--ion-color-medium);
  font-style: italic;
}

.endpoints-card {
  margin-top: 24px;
}

.card-header-with-action {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.endpoints-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.endpoint-item {
  border: 1px solid var(--ion-color-step-200);
  border-radius: 8px;
  padding: 16px;
  background: var(--ion-color-step-50);
}

.endpoint-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.endpoint-header h3 {
  flex: 1;
  margin: 0;
  color: var(--ion-color-primary);
}

.method-badge {
  min-width: 60px;
  text-align: center;
}

.endpoint-actions {
  display: flex;
  gap: 4px;
}

.endpoint-details {
  margin-bottom: 12px;
}

.endpoint-path {
  font-family: 'Courier New', monospace;
  background: var(--ion-color-step-100);
  padding: 4px 8px;
  border-radius: 4px;
  margin: 4px 0;
  font-size: 0.9em;
}

.endpoint-description {
  color: var(--ion-color-medium);
  margin: 4px 0;
}

.endpoint-params {
  border-top: 1px solid var(--ion-color-step-200);
  padding-top: 8px;
}

.params-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}

.param-tag {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary-contrast);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8em;
}

.no-endpoints {
  text-align: center;
  padding: 32px;
  color: var(--ion-color-medium);
}

.no-endpoints ion-icon {
  margin-bottom: 16px;
}

.test-results {
  padding: 16px;
}

.result-status {
  padding: 8px 16px;
  border-radius: 4px;
  margin-bottom: 16px;
  font-weight: bold;
}

.result-status.success {
  background: var(--ion-color-success-tint);
  color: var(--ion-color-success-contrast);
}

.result-status.warning {
  background: var(--ion-color-warning-tint);
  color: var(--ion-color-warning-contrast);
}

.result-status.danger {
  background: var(--ion-color-danger-tint);
  color: var(--ion-color-danger-contrast);
}

.result-data {
  background: var(--ion-color-step-100);
  padding: 16px;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.9em;
}

.test-loading {
  text-align: center;
  padding: 32px;
}

.test-loading ion-spinner {
  margin-bottom: 16px;
}
</style>
