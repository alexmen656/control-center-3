<template>
  <ion-page>
    <ion-content class="apis-modern">
      <SiteTitle icon="cloud-outline" title="CMS APIs" />
      <div class="apis-container">
        <div class="page-header">
          <div class="header-content">
            <h1>CMS APIs</h1>
            <p>Browse, subscribe and monitor the APIs provided by your CMS system.</p>
          </div>
        </div>

        <div class="apis-tabs">
          <button class="apis-tab" :class="{ active: activeTab === 'available' }" @click="setTab('available')">
            <ion-icon name="grid-outline"></ion-icon>
            <span>Available APIs</span>
          </button>
          <button class="apis-tab" :class="{ active: activeTab === 'subscribed' }" @click="setTab('subscribed')">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
            <span>Project APIs</span>
            <span v-if="subscribedApis.length > 0" class="tab-count">{{ subscribedApis.length }}</span>
          </button>
          <button class="apis-tab" :class="{ active: activeTab === 'codespaces' }" @click="setTab('codespaces')">
            <ion-icon name="cube-outline"></ion-icon>
            <span>Codespace APIs</span>
            <span v-if="projectCodespaces.length > 0" class="tab-count">{{ projectCodespaces.length }}</span>
          </button>
          <button class="apis-tab" :class="{ active: activeTab === 'usage' }" @click="setTab('usage')">
            <ion-icon name="analytics-outline"></ion-icon>
            <span>Usage &amp; Stats</span>
          </button>
        </div>

        <div v-if="activeTab === 'available'" class="tab-content">
          <div class="filter-bar">
            <div class="filter-search">
              <ion-icon name="search-outline"></ion-icon>
              <input v-model="searchTerm" type="text" placeholder="Search APIs…" />
            </div>
            <select v-model="selectedCategory" class="filter-select">
              <option value="">All Categories</option>
              <option value="auth">Authentication</option>
              <option value="storage">Storage</option>
              <option value="data">Data</option>
              <option value="communication">Communication</option>
              <option value="analytics">Analytics</option>
            </select>
          </div>

          <div class="apis-grid">
            <article v-for="api in filteredApis" :key="api.id" class="api-tile">
              <div class="tile-top">
                <div class="tile-icon">
                  <ion-icon :name="api.icon || 'cloud-outline'"></ion-icon>
                </div>
                <span class="category-pill" :class="'cat-' + api.category">{{ api.category }}</span>
              </div>
              <h3 class="tile-title">{{ api.name }}</h3>
              <p class="tile-description">{{ api.description }}</p>
              <div class="tile-meta">
                <div class="meta-line">
                  <ion-icon name="globe-outline"></ion-icon>
                  <code>{{ api.endpoint_base }}</code>
                </div>
                <div class="meta-line">
                  <ion-icon name="shield-checkmark-outline"></ion-icon>
                  <span>{{ api.auth_required ? 'Auth required' : 'No auth' }}</span>
                </div>
                <div class="meta-line">
                  <ion-icon name="speedometer-outline"></ion-icon>
                  <span>{{ api.rate_limit_default }}/min</span>
                </div>
              </div>
              <div class="tile-footer">
                <span class="tile-version">v{{ api.version }}</span>
                <div class="tile-actions">
                  <button class="btn ghost" @click="viewApiDetails(api)">
                    <ion-icon name="eye-outline"></ion-icon>
                    Details
                  </button>
                  <button class="btn primary" @click="subscribeToApi(api)" :disabled="isSubscribed(api.id)">
                    <ion-icon :name="isSubscribed(api.id) ? 'checkmark-outline' : 'add-outline'"></ion-icon>
                    {{ isSubscribed(api.id) ? 'Subscribed' : 'Subscribe' }}
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-if="filteredApis.length === 0" class="empty-block">
            <ion-icon name="cloud-offline-outline"></ion-icon>
            <h4>No APIs found</h4>
            <p>Try adjusting your search or category filter.</p>
          </div>
        </div>

        <div v-if="activeTab === 'subscribed'" class="tab-content">
          <div class="action-row" v-if="subscribedApis.length > 0">
            <p class="section-hint">Open an API to inspect its detailed call log and usage.</p>
            <button class="btn ghost" @click="refreshUsage">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
          </div>

          <div v-if="subscribedApis.length > 0" class="apis-grid">
            <article
              v-for="api in subscribedApis"
              :key="api.subscription_id"
              class="api-tile subscribed"
              @click="openApiDetail(api)"
            >
              <div class="tile-top">
                <div class="tile-icon">
                  <ion-icon :name="api.icon || 'cloud-outline'"></ion-icon>
                </div>
                <span class="category-pill" :class="'cat-' + api.category">{{ api.category }}</span>
              </div>
              <h3 class="tile-title">{{ api.name }}</h3>
              <p class="tile-description">{{ api.description }}</p>

              <div class="sub-metrics">
                <div class="sub-metric">
                  <div class="sub-metric-value">{{ api.usage_count || 0 }}</div>
                  <div class="sub-metric-label">Requests</div>
                </div>
                <div class="sub-metric">
                  <div class="sub-metric-value">{{ api.last_used ? formatDate(api.last_used) : '—' }}</div>
                  <div class="sub-metric-label">Last used</div>
                </div>
              </div>

              <div class="key-chip">
                <ion-icon name="key-outline"></ion-icon>
                <code>{{ api.api_key }}</code>
              </div>

              <div class="tile-footer">
                <button class="btn primary" @click.stop="openApiDetail(api)">
                  <ion-icon name="list-outline"></ion-icon>
                  Call Log
                </button>
                <div class="tile-actions">
                  <button class="icon-action" title="Settings" @click.stop="openApiSettings(api)">
                    <ion-icon name="settings-outline"></ion-icon>
                  </button>
                  <button class="icon-action" title="Regenerate key" @click.stop="regenerateApiKey(api)">
                    <ion-icon name="refresh-outline"></ion-icon>
                  </button>
                  <button class="icon-action danger" title="Unsubscribe" @click.stop="unsubscribeFromApi(api)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="empty-block">
            <ion-icon name="albums-outline"></ion-icon>
            <h4>No API subscriptions</h4>
            <p>Subscribe to APIs from the "Available APIs" tab to get started.</p>
          </div>
        </div>

        <div v-if="activeTab === 'codespaces'" class="tab-content">
          <div class="codespace-selector" v-if="projectCodespaces.length > 0">
            <label>Codespace</label>
            <select v-model="selectedCodespace" class="filter-select" @change="loadCodespaceAPIs">
              <option value="" disabled>Select Codespace</option>
              <option v-for="codespace in projectCodespaces" :key="codespace.slug" :value="codespace.slug">
                {{ codespace.name }}
              </option>
            </select>
          </div>

          <div v-if="selectedCodespace && codespaceAPIs.length > 0">
            <div class="codespace-info">
              <ion-icon name="information-circle-outline"></ion-icon>
              <span>Toggle APIs on/off for this codespace. Only activated APIs have their SDKs available in the .monaco_apis folder.</span>
            </div>

            <div class="apis-grid">
              <article v-for="api in codespaceAPIs" :key="api.subscription_id" class="api-tile">
                <div class="tile-top">
                  <div class="tile-icon">
                    <ion-icon :name="api.icon || 'cloud-outline'"></ion-icon>
                  </div>
                  <ion-toggle
                    :checked="api.is_active"
                    @ionChange="toggleCodespaceAPI(api)"
                    :disabled="api.isToggling">
                  </ion-toggle>
                </div>
                <h3 class="tile-title">{{ api.name }}</h3>
                <p class="tile-description">{{ api.description }}</p>
                <div class="tile-footer">
                  <span class="status-tag" :class="api.is_active ? 'on' : 'off'">
                    {{ api.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </article>
            </div>
          </div>

          <div v-else-if="selectedCodespace && codespaceAPIs.length === 0" class="empty-block">
            <ion-icon name="server-outline"></ion-icon>
            <h4>No APIs for this codespace</h4>
            <p>Subscribe to APIs in the "Project APIs" tab first.</p>
          </div>

          <div v-else-if="projectCodespaces.length === 0" class="empty-block">
            <ion-icon name="cube-outline"></ion-icon>
            <h4>No codespaces found</h4>
            <p>This project has no codespaces yet.</p>
          </div>

          <div v-else class="empty-block">
            <ion-icon name="cube-outline"></ion-icon>
            <h4>Select a codespace</h4>
            <p>Choose a codespace above to manage API activations.</p>
          </div>
        </div>

        <div v-if="activeTab === 'usage'" class="tab-content">
          <div class="stats-overview" v-if="usageStats.length > 0">
            <div class="stat-tile">
              <div class="stat-tile-value">{{ totalRequests }}</div>
              <div class="stat-tile-label">Total Requests</div>
            </div>
            <div class="stat-tile">
              <div class="stat-tile-value">{{ averageSuccessRate }}%</div>
              <div class="stat-tile-label">Success Rate</div>
            </div>
            <div class="stat-tile">
              <div class="stat-tile-value">{{ averageResponseTime }}ms</div>
              <div class="stat-tile-label">Avg Response Time</div>
            </div>
          </div>
          <div v-else class="empty-block">
            <ion-icon name="analytics-outline"></ion-icon>
            <h4>No usage data yet</h4>
            <p>Aggregate statistics will appear here once your APIs are used.</p>
          </div>
        </div>
      </div>

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
import { useRoute, useRouter } from 'vue-router';
import {
  IonPage, IonContent, IonLabel, IonButton,
  IonIcon, IonBadge, IonCheckbox, IonInput, IonToggle,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonItem,
  alertController, toastController
} from '@ionic/vue';
import SiteTitle from '@/components/SiteTitle.vue';
import axios from 'axios';


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
    IonPage, IonContent, IonLabel, IonButton,
    IonIcon, IonBadge, IonCheckbox, IonInput, IonToggle,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonItem,
    SiteTitle
  },
  setup() {
    const route = useRoute();
    const router = useRouter();

    const activeTab = ref('available');
    const availableApis = ref<CmsApi[]>([]);
    const subscribedApis = ref<SubscribedApi[]>([]);
    const searchTerm = ref('');
    const selectedCategory = ref('');
    const usageStats = ref([]);

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

        const response = await axios.get('v2/apis/available');

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

        const response = await axios.get(`v2/apis/project?project=${route.params.project}`);

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

        const response = await axios.post('v2/apis/subscribe', {
          project: route.params.project,
          apiId: api.id
        });

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

                const response = await axios.delete(`v2/apis/subscriptions/${api.subscription_id}`);

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
        const response = await axios.get(`v2/apis/by-id/${api.id}`);

        if (response.data && !response.data.error) {
          selectedApi.value = response.data;
          isDetailsModalOpen.value = true;
        }
      } catch (error) {
        console.error('Error loading API details:', error);
        showToast('Error loading API details', 'danger');
      }
    };

    const openApiDetail = (api: SubscribedApi) => {
      router.push(`/project/${route.params.project}/apis/${api.slug}`);
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

                const response = await axios.post(`v2/apis/subscriptions/${api.subscription_id}/regenerate-key`);

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

    const setTab = (tab: string) => {
      activeTab.value = tab;
      if (tab === 'subscribed') {
        loadSubscribedApis();
      } else if (tab === 'codespaces') {
        loadProjectCodespaces();
      }
    };

    const refreshUsage = () => {
      loadSubscribedApis();
      showToast('Usage data refreshed', 'success');
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

    const loadProjectCodespaces = async () => {
      try {
        const response = await axios.post('project_codespaces.php', new URLSearchParams({
          getCodespaces: 'true',
          project: route.params.project as string
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
      projectCodespaces,
      selectedCodespace,
      codespaceAPIs,
      setTab,
      isSubscribed,
      subscribeToApi,
      unsubscribeFromApi,
      viewApiDetails,
      openApiDetail,
      openApiSettings,
      saveSubscriptionSettings,
      regenerateApiKey,
      refreshUsage,
      closeDetailsModal,
      closeSettingsModal,
      openDocumentation,
      getCategoryColor,
      getMethodColor,
      formatDate,
      loadProjectCodespaces,
      loadCodespaceAPIs,
      toggleCodespaceAPI
    };
  }
});
</script>

<style scoped>
.apis-modern {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
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
  --ion-background-color: var(--background);
}

.apis-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

.apis-tabs {
  display: flex;
  gap: 4px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 4px;
  box-shadow: var(--shadow);
  margin-bottom: 24px;
}

.apis-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  background: transparent;
  border: none;
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.apis-tab:hover {
  background: var(--background);
  color: var(--text-primary);
}

.apis-tab.active {
  background: var(--primary-color);
  color: white;
  box-shadow: var(--shadow);
}

.apis-tab ion-icon {
  font-size: 18px;
}

.tab-count {
  padding: 1px 7px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.25);
  font-size: 12px;
  font-weight: 600;
}

.apis-tab:not(.active) .tab-count {
  background: var(--primary-color);
  color: white;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filter-search {
  flex: 1;
  min-width: 220px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.filter-search ion-icon {
  color: var(--text-muted);
  font-size: 18px;
}

.filter-search input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 11px 0;
  font-size: 14px;
  color: var(--text-primary);
  outline: none;
}

.filter-select {
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  min-width: 190px;
  cursor: pointer;
}

.apis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 20px;
}

.api-tile {
  display: flex;
  flex-direction: column;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  box-shadow: var(--shadow);
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.api-tile.subscribed {
  cursor: pointer;
}

.api-tile.subscribed:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.tile-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.tile-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius);
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.category-pill {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
  background: var(--background);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.cat-auth { background: #eef2ff; color: #4f46e5; border-color: transparent; }
.cat-storage, .cat-file { background: #f0fdfa; color: #0d9488; border-color: transparent; }
.cat-data, .cat-database { background: #fff7ed; color: #f97316; border-color: transparent; }
.cat-communication, .cat-notification { background: #ecfdf5; color: #059669; border-color: transparent; }
.cat-analytics { background: #fffbeb; color: #d97706; border-color: transparent; }
.cat-user { background: #fdf4ff; color: #a21caf; border-color: transparent; }

.tile-title {
  margin: 0 0 6px 0;
  font-size: 17px;
  font-weight: 600;
  color: var(--text-primary);
}

.tile-description {
  margin: 0 0 16px 0;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.5;
  flex: 1;
}

.tile-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.meta-line {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
}

.meta-line ion-icon {
  font-size: 15px;
  color: var(--text-muted);
  flex-shrink: 0;
}

.meta-line code {
  font-family: 'Courier New', monospace;
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tile-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: auto;
}

.tile-version {
  font-size: 12px;
  color: var(--text-muted);
  background: var(--background);
  padding: 4px 8px;
  border-radius: 4px;
}

.tile-actions {
  display: flex;
  gap: 6px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.btn ion-icon {
  font-size: 16px;
}

.btn:hover {
  background: var(--background);
}

.btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.btn.primary:hover {
  background: var(--primary-hover);
}

.btn.primary:disabled {
  background: var(--success-color);
  border-color: var(--success-color);
  opacity: 0.85;
  cursor: default;
}

.btn.ghost {
  background: transparent;
  color: var(--text-secondary);
}

.icon-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-action:hover {
  background: var(--background);
  color: var(--text-primary);
}

.icon-action.danger:hover {
  background: #fef2f2;
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 20px;
}

.section-hint {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

.sub-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}

.sub-metric {
  padding: 10px 12px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.sub-metric-value {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.sub-metric-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.key-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  margin-bottom: 16px;
  overflow: hidden;
}

.key-chip ion-icon {
  color: var(--text-muted);
  flex-shrink: 0;
}

.key-chip code {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-tag {
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.status-tag.on {
  background: #dcfce7;
  color: var(--success-color);
}

.status-tag.off {
  background: var(--background);
  color: var(--text-muted);
}

.codespace-selector {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 20px;
  max-width: 360px;
}

.codespace-selector label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
}

.codespace-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: var(--radius);
  color: #9a3412;
  font-size: 13px;
  margin-bottom: 20px;
}

.codespace-info ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.stat-tile {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px;
  text-align: center;
  box-shadow: var(--shadow);
}

.stat-tile-value {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-tile-label {
  margin-top: 6px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.empty-block {
  text-align: center;
  padding: 64px 32px;
  color: var(--text-muted);
}

.empty-block ion-icon {
  font-size: 48px;
  opacity: 0.5;
  margin-bottom: 16px;
}

.empty-block h4 {
  margin: 0 0 8px 0;
  color: var(--text-secondary);
  font-size: 16px;
  font-weight: 500;
}

.empty-block p {
  margin: 0;
  font-size: 14px;
}

.api-details-content { max-width: 800px; }
.api-overview { margin-bottom: 32px; }
.api-meta {
  display: grid;
  gap: 8px;
  margin-top: 16px;
  background: var(--ion-color-step-50, #f4f5f8);
  padding: 16px;
  border-radius: 8px;
}
.api-meta div { display: flex; justify-content: space-between; }
.api-meta code {
  background: var(--ion-color-step-100, #e9eaed);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
}
.endpoints-section { margin-bottom: 32px; }
.endpoint-card {
  border: 1px solid var(--ion-color-step-200, #d7d8da);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  background: var(--ion-color-step-50, #f4f5f8);
}
.endpoint-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.endpoint-path {
  font-family: monospace;
  background: var(--ion-color-step-100, #e9eaed);
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9rem;
}
.endpoint-description { color: var(--ion-color-medium); margin: 8px 0; }
.parameters ul { margin: 8px 0; padding-left: 20px; }
.parameters code {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary-contrast);
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 0.8rem;
}
.required { color: var(--ion-color-danger); font-weight: bold; }
.documentation-link { margin-top: 24px; }

@media (prefers-color-scheme: dark) {
  .apis-modern {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
  .codespace-info { background: rgba(249, 115, 22, 0.12); border-color: rgba(249, 115, 22, 0.3); color: #fed7aa; }
  .status-tag.on { background: rgba(5, 150, 105, 0.2); }
}

@media (max-width: 768px) {
  .apis-container { padding: 16px; }
  .apis-tabs { flex-wrap: wrap; }
  .apis-tab { flex: 1 1 45%; }
  .apis-grid { grid-template-columns: 1fr; }
  .filter-bar { flex-direction: column; }
  .filter-select { min-width: 0; width: 100%; }
}
</style>
