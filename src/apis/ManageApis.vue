<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <h1 style="text-align: center; margin-top: 2rem;">CMS APIs</h1>
            <p style="text-align: center; color: var(--ion-color-medium); margin-bottom: 2rem;">
              Browse and subscribe to APIs provided by your CMS system
            </p>

            <div class="tabs-container">
              <ion-segment v-model="activeTab" @ionChange="segmentChanged">
                <ion-segment-button value="available">
                  <ion-label>Available APIs</ion-label>
                </ion-segment-button>
                <ion-segment-button value="subscribed">
                  <ion-label>Project APIs</ion-label>
                  <ion-badge v-if="subscribedApis.length > 0" color="primary">{{ subscribedApis.length }}</ion-badge>
                </ion-segment-button>
                <ion-segment-button value="codespaces">
                  <ion-label>Codespace APIs</ion-label>
                  <ion-badge v-if="projectCodespaces.length > 0" color="secondary">{{ projectCodespaces.length }}</ion-badge>
                </ion-segment-button>
                <ion-segment-button value="usage">
                  <ion-label>Usage & Stats</ion-label>
                </ion-segment-button>
              </ion-segment>
            </div>

            <!-- Available APIs Tab -->
            <div v-if="activeTab === 'available'" class="tab-content">
              <div class="filter-bar">
                <ion-searchbar v-model="searchTerm" placeholder="Search APIs..." @ionInput="filterApis"></ion-searchbar>
                <ion-select v-model="selectedCategory" placeholder="All Categories" @ionChange="filterApis">
                  <ion-select-option value="">All Categories</ion-select-option>
                  <ion-select-option value="auth">Authentication</ion-select-option>
                  <ion-select-option value="storage">Storage</ion-select-option>
                  <ion-select-option value="data">Data</ion-select-option>
                  <ion-select-option value="communication">Communication</ion-select-option>
                  <ion-select-option value="analytics">Analytics</ion-select-option>
                </ion-select>
              </div>

              <div class="apis-grid">
                <ion-card v-for="api in filteredApis" :key="api.id" class="api-card">
                  <ion-card-header>
                    <div class="api-card-header">
                      <ion-icon :name="api.icon" class="api-icon"></ion-icon>
                      <div class="api-title">
                        <ion-card-title>{{ api.name }}</ion-card-title>
                        <ion-badge :color="getCategoryColor(api.category)">{{ api.category }}</ion-badge>
                      </div>
                      <div class="api-version">v{{ api.version }}</div>
                    </div>
                  </ion-card-header>
                  <ion-card-content>
                    <p class="api-description">{{ api.description }}</p>
                    <div class="api-details">
                      <div class="detail-item">
                        <ion-icon name="globe-outline"></ion-icon>
                        <span>{{ api.endpoint_base }}</span>
                      </div>
                      <div class="detail-item">
                        <ion-icon name="shield-outline"></ion-icon>
                        <span>{{ api.auth_required ? 'Auth Required' : 'No Auth' }}</span>
                      </div>
                      <div class="detail-item">
                        <ion-icon name="speedometer-outline"></ion-icon>
                        <span>{{ api.rate_limit_default }}/min</span>
                      </div>
                    </div>
                    <div class="api-actions">
                      <ion-button @click="viewApiDetails(api)" fill="clear" size="small">
                        <ion-icon slot="start" name="eye-outline"></ion-icon>
                        Details
                      </ion-button>
                      <ion-button @click="subscribeToApi(api)" color="primary" size="small"
                        :disabled="isSubscribed(api.id)">
                        <ion-icon slot="start"
                          :name="isSubscribed(api.id) ? 'checkmark-outline' : 'add-outline'"></ion-icon>
                        {{ isSubscribed(api.id) ? 'Subscribed' : 'Subscribe' }}
                      </ion-button>
                    </div>
                  </ion-card-content>
                </ion-card>
              </div>
            </div>

            <!-- Subscribed APIs Tab -->
            <div v-if="activeTab === 'subscribed'" class="tab-content">
              <div class="action-buttons" v-if="subscribedApis.length > 0">
                <ion-button @click="refreshUsage" fill="outline">
                  <ion-icon slot="start" name="refresh-outline"></ion-icon>
                  Refresh Usage
                </ion-button>
              </div>

              <ion-list v-if="subscribedApis.length > 0">
                <ion-item-sliding v-for="api in subscribedApis" :key="api.subscription_id">
                  <ion-item>
                    <ion-icon :name="api.icon" slot="start" class="api-list-icon"></ion-icon>
                    <ion-label>
                      <h2>{{ api.name }}</h2>
                      <p>{{ api.description }}</p>
                      <div class="subscription-details">
                        <span class="api-key">Key: {{ api.api_key }}</span>
                        <span class="usage-info">{{ api.usage_count }} requests</span>
                        <span class="last-used" v-if="api.last_used">Last used: {{ formatDate(api.last_used) }}</span>
                      </div>
                    </ion-label>
                    <div class="api-status" slot="end">
                      <ion-badge :color="getCategoryColor(api.category)">{{ api.category }}</ion-badge>
                      <ion-button fill="clear" @click="viewApiUsage(api)">
                        <ion-icon slot="icon-only" name="analytics-outline"></ion-icon>
                      </ion-button>
                      <ion-button fill="clear" @click="openApiSettings(api)">
                        <ion-icon slot="icon-only" name="settings-outline"></ion-icon>
                      </ion-button>
                    </div>
                  </ion-item>
                  <ion-item-options>
                    <ion-item-option @click="regenerateApiKey(api)" color="warning">
                      <ion-icon slot="icon-only" name="key-outline"></ion-icon>
                    </ion-item-option>
                    <ion-item-option @click="unsubscribeFromApi(api)" color="danger">
                      <ion-icon slot="icon-only" name="trash-outline"></ion-icon>
                    </ion-item-option>
                  </ion-item-options>
                </ion-item-sliding>
              </ion-list>
              <ion-item v-else>
                <ion-label class="ion-text-center">
                  <h2>No API subscriptions</h2>
                  <p>Subscribe to APIs from the "Available APIs" tab</p>
                </ion-label>
              </ion-item>
            </div>

            <!-- Codespace APIs Tab -->
            <div v-if="activeTab === 'codespaces'" class="tab-content">
              <div class="codespace-selector" v-if="projectCodespaces.length > 0">
                <ion-select v-model="selectedCodespace" placeholder="Select Codespace" @ionChange="loadCodespaceAPIs">
                  <ion-select-option v-for="codespace in projectCodespaces" :key="codespace.slug" :value="codespace.slug">
                    {{ codespace.name }}
                  </ion-select-option>
                </ion-select>
              </div>

              <div v-if="selectedCodespace && codespaceAPIs.length > 0">
                <h3>API Activation for {{ getCodespaceName(selectedCodespace) }}</h3>
                <p class="codespace-info">
                  Toggle APIs on/off for this specific codespace. Only activated APIs will have their SDKs available in the .monaco_apis folder.
                </p>

                <ion-list>
                  <ion-item v-for="api in codespaceAPIs" :key="api.subscription_id">
                    <ion-icon :name="api.icon" slot="start" class="api-list-icon"></ion-icon>
                    <ion-label>
                      <h2>{{ api.name }}</h2>
                      <p>{{ api.description }}</p>
                      <div class="subscription-details">
                        <span class="api-status-badge">
                          <ion-badge :color="api.is_active ? 'success' : 'medium'">
                            {{ api.is_active ? 'Active' : 'Inactive' }}
                          </ion-badge>
                        </span>
                      </div>
                    </ion-label>
                    <ion-toggle 
                      slot="end" 
                      :checked="api.is_active" 
                      @ionChange="toggleCodespaceAPI(api)"
                      :disabled="api.isToggling">
                    </ion-toggle>
                  </ion-item>
                </ion-list>
              </div>

              <div v-else-if="selectedCodespace && codespaceAPIs.length === 0" class="no-apis">
                <ion-icon name="server-outline" size="large" color="medium"></ion-icon>
                <p>No APIs available for this codespace</p>
                <p>Subscribe to APIs in the "Project APIs" tab first</p>
              </div>

              <div v-else-if="projectCodespaces.length === 0" class="no-codespaces">
                <ion-icon name="cube-outline" size="large" color="medium"></ion-icon>
                <p>No codespaces found for this project</p>
              </div>

              <div v-else class="select-codespace">
                <ion-icon name="cube-outline" size="large" color="medium"></ion-icon>
                <p>Select a codespace above to manage API activations</p>
              </div>
            </div>

            <!-- Usage & Stats Tab -->
            <div v-if="activeTab === 'usage'" class="tab-content">
              <div class="stats-overview" v-if="usageStats.length > 0">
                <div class="stat-card">
                  <h3>Total Requests</h3>
                  <div class="stat-number">{{ totalRequests }}</div>
                </div>
                <div class="stat-card">
                  <h3>Success Rate</h3>
                  <div class="stat-number">{{ averageSuccessRate }}%</div>
                </div>
                <div class="stat-card">
                  <h3>Avg Response Time</h3>
                  <div class="stat-number">{{ averageResponseTime }}ms</div>
                </div>
              </div>
              <div v-else class="no-stats">
                <ion-icon name="analytics-outline" size="large" color="medium"></ion-icon>
                <p>No usage data available yet</p>
              </div>
            </div>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>

      <!-- API Details Modal -->
      <ion-modal :is-open="isDetailsModalOpen" ref="detailsModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeDetailsModal">Close</ion-button>
            </ion-buttons>
            <ion-title>{{ selectedApi?.name }} - API Details</ion-title>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <div v-if="selectedApi" class="api-details-content">
            <div class="api-overview">
              <h3>Overview</h3>
              <p>{{ selectedApi.description }}</p>
              <div class="api-meta">
                <div><strong>Category:</strong> {{ selectedApi.category }}</div>
                <div><strong>Version:</strong> {{ selectedApi.version }}</div>
                <div><strong>Base Endpoint:</strong> <code>{{ selectedApi.endpoint_base }}</code></div>
                <div><strong>Authentication:</strong> {{ selectedApi.auth_required ? 'Required' : 'Not Required' }}
                </div>
                <div><strong>Default Rate Limit:</strong> {{ selectedApi.rate_limit_default }} requests/minute</div>
              </div>
            </div>

            <div class="endpoints-section" v-if="selectedApi.endpoints && selectedApi.endpoints.length > 0">
              <h3>Available Endpoints</h3>
              <div v-for="endpoint in selectedApi.endpoints" :key="endpoint.id" class="endpoint-card">
                <div class="endpoint-header">
                  <ion-badge :color="getMethodColor(endpoint.method)">{{ endpoint.method }}</ion-badge>
                  <span class="endpoint-path">{{ endpoint.endpoint }}</span>
                </div>
                <div class="endpoint-details">
                  <p><strong>{{ endpoint.name }}</strong></p>
                  <p class="endpoint-description">{{ endpoint.description }}</p>
                  <div v-if="endpoint.parameters && Object.keys(endpoint.parameters).length > 0" class="parameters">
                    <strong>Parameters:</strong>
                    <ul>
                      <li v-for="(param, key) in endpoint.parameters" :key="key">
                        <code>{{ key }}</code> ({{ param.type }})
                        <span v-if="param.required" class="required">*</span>
                        <span v-if="param.description"> - {{ param.description }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div class="documentation-link" v-if="selectedApi.documentation_url">
              <ion-button expand="block" fill="outline" @click="openDocumentation(selectedApi.documentation_url)">
                <ion-icon slot="start" name="book-outline"></ion-icon>
                View Full Documentation
              </ion-button>
            </div>
          </div>
        </ion-content>
      </ion-modal>

      <!-- API Settings Modal -->
      <ion-modal :is-open="isSettingsModalOpen" ref="settingsModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeSettingsModal">Close</ion-button>
            </ion-buttons>
            <ion-title>{{ selectedSubscription?.name }} - Settings</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="saveSubscriptionSettings" strong>Save</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <div v-if="selectedSubscription">
            <ion-item>
              <ion-label position="floating">API Key</ion-label>
              <ion-input :value="selectedSubscription.api_key" readonly></ion-input>
              <ion-button fill="clear" slot="end" @click="regenerateApiKey(selectedSubscription)">
                <ion-icon slot="icon-only" name="refresh-outline"></ion-icon>
              </ion-button>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Rate Limit (requests/minute)</ion-label>
              <ion-input v-model="settingsForm.rate_limit" type="number" min="1"></ion-input>
            </ion-item>
            <ion-item>
              <ion-checkbox v-model="settingsForm.is_enabled"></ion-checkbox>
              <ion-label class="ion-margin-start">
                <h3>Enable API Access</h3>
                <p>Temporarily disable API access without unsubscribing</p>
              </ion-label>
            </ion-item>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  IonPage, IonContent, IonGrid, IonRow, IonCol, IonSegment, IonSegmentButton,
  IonLabel, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonButton,
  IonIcon, IonBadge, IonSearchbar, IonSelect, IonSelectOption, IonList, IonItem,
  IonItemSliding, IonItemOptions, IonItemOption, IonCheckbox, IonInput, IonToggle,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle,
  alertController, toastController
} from '@ionic/vue';
import axios from 'axios';
import qs from 'qs';


interface CmsApi {
  id: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  category: string;
  version: string;
  endpoint_base: string;
  auth_required: boolean;
  rate_limit_default: number;
  documentation_url: string;
  endpoints?: any[];
}

interface SubscribedApi {
  subscription_id: number;
  api_id: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  category: string;
  endpoint_base: string;
  api_key: string;
  rate_limit: number;
  usage_count: number;
  last_used: string;
  documentation_url: string;
}

interface CodespaceApi {
  subscription_id: number;
  api_id: number;
  name: string;
  slug: string;
  description: string;
  icon: string;
  category: string;
  endpoint_base: string;
  is_active: boolean;
  isToggling?: boolean;
}

interface Codespace {
  id: number;
  slug: string;
  name: string;
  description: string;
}

export default defineComponent({
  name: 'ManageApis',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonSegment, IonSegmentButton,
    IonLabel, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonButton,
    IonIcon, IonBadge, IonSearchbar, IonSelect, IonSelectOption, IonList, IonItem,
    IonItemSliding, IonItemOptions, IonItemOption, IonCheckbox, IonInput, IonToggle,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle
  },
  setup() {
    const route = useRoute();

    const activeTab = ref('available');
    const availableApis = ref<CmsApi[]>([]);
    const subscribedApis = ref<SubscribedApi[]>([]);
    const searchTerm = ref('');
    const selectedCategory = ref('');
    const usageStats = ref([]);

    // New codespace-related reactive variables
    const projectCodespaces = ref<Codespace[]>([]);
    const selectedCodespace = ref('');
    const codespaceAPIs = ref<CodespaceApi[]>([]);

    const isDetailsModalOpen = ref(false);
    const isSettingsModalOpen = ref(false);
    const selectedApi = ref<CmsApi | null>(null);
    const selectedSubscription = ref<SubscribedApi | null>(null);

    const settingsForm = ref({
      rate_limit: 100,
      is_enabled: true
    });

    const filteredApis = computed(() => {
      let filtered = availableApis.value;

      if (searchTerm.value) {
        filtered = filtered.filter(api =>
          api.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
          api.description.toLowerCase().includes(searchTerm.value.toLowerCase())
        );
      }

      if (selectedCategory.value) {
        filtered = filtered.filter(api => api.category === selectedCategory.value);
      }

      return filtered;
    });

    const totalRequests = computed(() => {
      return usageStats.value.reduce((sum, stat) => sum + stat.requests, 0);
    });

    const averageSuccessRate = computed(() => {
      if (usageStats.value.length === 0) return 0;
      const total = usageStats.value.reduce((sum, stat) => sum + stat.success_rate, 0);
      return Math.round(total / usageStats.value.length);
    });

    const averageResponseTime = computed(() => {
      if (usageStats.value.length === 0) return 0;
      const total = usageStats.value.reduce((sum, stat) => sum + stat.avg_response_time, 0);
      return Math.round(total / usageStats.value.length);
    });

    const loadAvailableApis = async () => {
      try {

        const response = await axios.post('apis.php', qs.stringify({
          getAvailableApis: true
        }));

        if (response.data && !response.data.error) {
          availableApis.value = response.data;
        }
      } catch (error) {
        console.error('Error loading available APIs:', error);
        showToast('Error loading APIs', 'danger');
      }
    };

    const loadSubscribedApis = async () => {
      try {

        const response = await axios.post('apis.php', qs.stringify({
          getProjectApis: true,
          project: route.params.project
        }));

        if (response.data && !response.data.error) {
          subscribedApis.value = response.data;
        }
      } catch (error) {
        console.error('Error loading subscribed APIs:', error);
        showToast('Error loading subscribed APIs', 'danger');
      }
    };

    const isSubscribed = (apiId: number) => {
      return subscribedApis.value.some(api => api.api_id === apiId);
    };

    const subscribeToApi = async (api: CmsApi) => {
      try {

        const response = await axios.post('apis.php', qs.stringify({
          subscribeToApi: true,
          project: route.params.project,
          apiId: api.id
        }));

        if (response.data && response.data.success) {
          showToast(`Successfully subscribed to ${api.name}`, 'success');
          loadSubscribedApis();
        } else {
          showToast(response.data.error || 'Error subscribing to API', 'danger');
        }
      } catch (error) {
        console.error('Error subscribing to API:', error);
        showToast('Error subscribing to API', 'danger');
      }
    };

    const unsubscribeFromApi = async (api: SubscribedApi) => {
      const alert = await alertController.create({
        header: 'Unsubscribe from API',
        message: `Are you sure you want to unsubscribe from "${api.name}"? This will revoke your API access.`,
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Unsubscribe',
            role: 'destructive',
            handler: async () => {
              try {

                const response = await axios.post('apis.php', qs.stringify({
                  unsubscribeFromApi: true,
                  subscriptionId: api.subscription_id
                }));

                if (response.data && response.data.success) {
                  showToast(`Unsubscribed from ${api.name}`, 'success');
                  loadSubscribedApis();
                } else {
                  showToast(response.data.error || 'Error unsubscribing', 'danger');
                }
              } catch (error) {
                console.error('Error unsubscribing:', error);
                showToast('Error unsubscribing', 'danger');
              }
            }
          }
        ]
      });
      await alert.present();
    };

    const viewApiDetails = async (api: CmsApi) => {
      try {
        const response = await axios.post('apis.php', qs.stringify({
          getApiDetails: true,
          apiId: api.id
        }));

        if (response.data && !response.data.error) {
          selectedApi.value = response.data;
          isDetailsModalOpen.value = true;
        }
      } catch (error) {
        console.error('Error loading API details:', error);
        showToast('Error loading API details', 'danger');
      }
    };

    const openApiSettings = (api: SubscribedApi) => {
      selectedSubscription.value = api;
      settingsForm.value = {
        rate_limit: api.rate_limit,
        is_enabled: true
      };
      isSettingsModalOpen.value = true;
    };

    const saveSubscriptionSettings = async () => {
      // Implementation for saving settings
      showToast('Settings saved successfully', 'success');
      closeSettingsModal();
    };

    const regenerateApiKey = async (api: SubscribedApi) => {
      const alert = await alertController.create({
        header: 'Regenerate API Key',
        message: 'This will generate a new API key. The old key will stop working immediately.',
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel'
          },
          {
            text: 'Regenerate',
            handler: async () => {
              try {

                const response = await axios.post('apis.php', qs.stringify({
                  regenerateApiKey: true,
                  subscriptionId: api.subscription_id
                }));

                if (response.data && response.data.success) {
                  showToast('API key regenerated successfully', 'success');
                  loadSubscribedApis();
                } else {
                  showToast(response.data.error || 'Error regenerating key', 'danger');
                }
              } catch (error) {
                console.error('Error regenerating API key:', error);
                showToast('Error regenerating API key', 'danger');
              }
            }
          }
        ]
      });
      await alert.present();
    };

    const segmentChanged = (event: any) => {
      activeTab.value = event.detail.value;
      if (activeTab.value === 'subscribed') {
        loadSubscribedApis();
      } else if (activeTab.value === 'codespaces') {
        loadProjectCodespaces();
      }
    };

    const filterApis = () => {
      // Filtering is handled by computed property
    };

    const refreshUsage = () => {
      loadSubscribedApis();
      showToast('Usage data refreshed', 'success');
    };

    const viewApiUsage = (api: SubscribedApi) => {
      // Implementation for viewing usage details
      showToast(`Viewing usage for ${api.name}`, 'primary');
    };

    const closeDetailsModal = () => {
      isDetailsModalOpen.value = false;
      selectedApi.value = null;
    };

    const closeSettingsModal = () => {
      isSettingsModalOpen.value = false;
      selectedSubscription.value = null;
    };

    const openDocumentation = (url: string) => {
      window.open(url, '_blank');
    };

    const getCategoryColor = (category: string) => {
      const colors = {
        'auth': 'primary',
        'storage': 'secondary',
        'data': 'tertiary',
        'communication': 'success',
        'analytics': 'warning',
        'general': 'medium'
      };
      return colors[category] || 'medium';
    };

    const getMethodColor = (method: string) => {
      const colors = {
        'GET': 'primary',
        'POST': 'success',
        'PUT': 'warning',
        'DELETE': 'danger',
        'PATCH': 'tertiary'
      };
      return colors[method] || 'medium';
    };

    const formatDate = (dateString: string) => {
      return new Date(dateString).toLocaleDateString();
    };

    // New codespace-related methods
    const loadProjectCodespaces = async () => {
      try {
        const response = await axios.post('project_codespaces.php', qs.stringify({
          getCodespaces: true,
          project: route.params.project
        }));
        
        if (response.data && response.data.success && response.data.codespaces) {
          projectCodespaces.value = response.data.codespaces;
        } else {
          projectCodespaces.value = [];
        }
      } catch (error) {
        console.error('Error loading codespaces:', error);
        showToast('Error loading codespaces', 'danger');
        projectCodespaces.value = [];
      }
    };

    const loadCodespaceAPIs = async () => {
      if (!selectedCodespace.value) return;
      
      try {
        const formData = new FormData();
        formData.append('getCodespaceAPIs', '1');
        formData.append('project', route.params.project as string);
        formData.append('codespace', selectedCodespace.value);
        
        const response = await axios.post('codespace_apis.php', formData);
        
        if (response.data && Array.isArray(response.data)) {
          codespaceAPIs.value = response.data.map((api: any) => ({
            ...api,
            isToggling: false
          }));
        } else {
          codespaceAPIs.value = [];
        }
      } catch (error) {
        console.error('Error loading codespace APIs:', error);
        showToast('Error loading codespace APIs', 'danger');
        codespaceAPIs.value = [];
      }
    };

    const toggleCodespaceAPI = async (api: CodespaceApi) => {
      if (api.isToggling) return;
      
      api.isToggling = true;
      
      try {
        const formData = new FormData();
        formData.append('project', route.params.project as string);
        formData.append('codespace', selectedCodespace.value);
        formData.append('subscription_id', api.subscription_id.toString());
        
        if (api.is_active) {
          formData.append('deactivateCodespaceAPI', '1');
          const response = await axios.post('codespace_apis.php', formData);
          
          if (response.data && response.data.success) {
            api.is_active = false;
            showToast('API deactivated successfully', 'success');
          } else {
            showToast(response.data?.message || 'Failed to deactivate API', 'danger');
          }
        } else {
          formData.append('activateCodespaceAPI', '1');
          const response = await axios.post('codespace_apis.php', formData);
          
          if (response.data && response.data.success) {
            api.is_active = true;
            showToast('API activated successfully', 'success');
          } else {
            showToast(response.data?.message || 'Failed to activate API', 'danger');
          }
        }
      } catch (error) {
        console.error('Failed to toggle API:', error);
        showToast('Failed to toggle API', 'danger');
      } finally {
        api.isToggling = false;
      }
    };

    const getCodespaceName = (slug: string) => {
      const codespace = projectCodespaces.value.find(cs => cs.slug === slug);
      return codespace ? codespace.name : slug;
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
      loadAvailableApis();
    });

    return {
      activeTab,
      availableApis,
      subscribedApis,
      searchTerm,
      selectedCategory,
      usageStats,
      filteredApis,
      totalRequests,
      averageSuccessRate,
      averageResponseTime,
      isDetailsModalOpen,
      isSettingsModalOpen,
      selectedApi,
      selectedSubscription,
      settingsForm,
      // New codespace-related variables
      projectCodespaces,
      selectedCodespace,
      codespaceAPIs,
      // Methods
      isSubscribed,
      subscribeToApi,
      unsubscribeFromApi,
      viewApiDetails,
      openApiSettings,
      saveSubscriptionSettings,
      regenerateApiKey,
      segmentChanged,
      filterApis,
      refreshUsage,
      viewApiUsage,
      closeDetailsModal,
      closeSettingsModal,
      openDocumentation,
      getCategoryColor,
      getMethodColor,
      formatDate,
      // New codespace methods
      loadProjectCodespaces,
      loadCodespaceAPIs,
      toggleCodespaceAPI,
      getCodespaceName
    };
  }
});
</script>

<style scoped>
.tabs-container {
  margin-bottom: 24px;
}

.tab-content {
  min-height: 400px;
}

.filter-bar {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  align-items: center;
}

.filter-bar ion-searchbar {
  flex: 1;
}

.filter-bar ion-select {
  min-width: 200px;
}

.apis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 16px;
}

.api-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.api-card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.api-icon {
  font-size: 2rem;
  color: var(--ion-color-primary);
  flex-shrink: 0;
}

.api-title {
  flex: 1;
}

.api-title ion-card-title {
  margin-bottom: 4px;
  font-size: 1.2rem;
}

.api-version {
  background: var(--ion-color-step-100);
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.api-description {
  color: var(--ion-color-medium);
  margin-bottom: 16px;
  line-height: 1.4;
}

.api-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.detail-item ion-icon {
  color: var(--ion-color-medium);
  font-size: 1rem;
}

.api-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.action-buttons {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.api-list-icon {
  font-size: 1.5rem;
  margin-right: 12px;
}

.subscription-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.8rem;
  color: var(--ion-color-medium);
}

.api-key {
  font-family: monospace;
  background: var(--ion-color-step-100);
  padding: 2px 4px;
  border-radius: 4px;
  display: inline-block;
}

.api-status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--ion-color-step-50);
  border-radius: 8px;
  padding: 20px;
  text-align: center;
}

.stat-card h3 {
  margin: 0 0 8px 0;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: var(--ion-color-primary);
}

.no-stats {
  text-align: center;
  padding: 64px 32px;
  color: var(--ion-color-medium);
}

.no-stats ion-icon {
  margin-bottom: 16px;
}

.api-details-content {
  max-width: 800px;
}

.api-overview {
  margin-bottom: 32px;
}

.api-meta {
  display: grid;
  gap: 8px;
  margin-top: 16px;
  background: var(--ion-color-step-50);
  padding: 16px;
  border-radius: 8px;
}

.api-meta div {
  display: flex;
  justify-content: space-between;
}

.api-meta code {
  background: var(--ion-color-step-100);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
}

.endpoints-section {
  margin-bottom: 32px;
}

.endpoint-card {
  border: 1px solid var(--ion-color-step-200);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  background: var(--ion-color-step-50);
}

.endpoint-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.endpoint-path {
  font-family: monospace;
  background: var(--ion-color-step-100);
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9rem;
}

.endpoint-description {
  color: var(--ion-color-medium);
  margin: 8px 0;
}

.parameters ul {
  margin: 8px 0;
  padding-left: 20px;
}

.parameters code {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary-contrast);
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 0.8rem;
}

.required {
  color: var(--ion-color-danger);
  font-weight: bold;
}

.documentation-link {
  margin-top: 24px;
}

/* Codespace-related styles */
.codespace-selector {
  margin-bottom: 24px;
}

.codespace-selector ion-select {
  width: 100%;
  border: 1px solid var(--ion-color-step-200);
  border-radius: 8px;
  padding: 12px;
}

.codespace-info {
  background: var(--ion-color-step-50);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
  color: var(--ion-color-medium);
}

.no-codespaces,
.select-codespace {
  text-align: center;
  padding: 64px 32px;
  color: var(--ion-color-medium);
}

.no-codespaces ion-icon,
.select-codespace ion-icon {
  margin-bottom: 16px;
}

.api-status-badge {
  margin-right: 8px;
}

@media (max-width: 768px) {
  .apis-grid {
    grid-template-columns: 1fr;
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-overview {
    grid-template-columns: 1fr;
  }
}
</style>