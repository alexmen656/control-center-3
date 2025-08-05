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
                <ion-badge color="medium" class="category-badge">{{ api.category }}</ion-badge>
              </div>
              <div class="api-actions">
                <ion-button @click="testApi" color="secondary">
                  <ion-icon slot="start" name="play-outline"></ion-icon>
                  Test API
                </ion-button>
                <ion-button @click="regenerateKey" fill="outline">
                  <ion-icon slot="start" name="refresh-outline"></ion-icon>
                  Regenerate Key
                </ion-button>
              </div>
            </div>

            <!-- Tab Navigation -->
            <ion-segment v-model="selectedTab" class="api-tabs">
              <ion-segment-button value="overview">
                <ion-icon name="information-circle-outline"></ion-icon>
                <ion-label>Overview</ion-label>
              </ion-segment-button>
              <ion-segment-button value="docs">
                <ion-icon name="document-text-outline"></ion-icon>
                <ion-label>Documentation</ion-label>
              </ion-segment-button>
              <ion-segment-button value="usage">
                <ion-icon name="analytics-outline"></ion-icon>
                <ion-label>Usage & Stats</ion-label>
              </ion-segment-button>
              <ion-segment-button value="settings">
                <ion-icon name="settings-outline"></ion-icon>
                <ion-label>Settings</ion-label>
              </ion-segment-button>
            </ion-segment>

            <!-- Overview Tab -->
            <div v-if="selectedTab === 'overview'" class="tab-content">
              <div class="overview-cards">
                <ion-card>
                  <ion-card-header>
                    <ion-card-title>API Information</ion-card-title>
                  </ion-card-header>
                  <ion-card-content>
                    <div class="info-row">
                      <strong>Base URL:</strong> 
                      <code>{{ api.endpoint_base }}</code>
                    </div>
                    <div class="info-row">
                      <strong>Version:</strong> {{ api.version }}
                    </div>
                    <div class="info-row">
                      <strong>Rate Limit:</strong> {{ subscription.rate_limit }} requests/minute
                    </div>
                    <div class="info-row">
                      <strong>Total Usage:</strong> {{ subscription.usage_count }} requests
                    </div>
                    <div class="info-row" v-if="subscription.last_used">
                      <strong>Last Used:</strong> {{ formatDate(subscription.last_used) }}
                    </div>
                    <div class="info-row" v-if="api.description">
                      <strong>Description:</strong> {{ api.description }}
                    </div>
                  </ion-card-content>
                </ion-card>

                <ion-card>
                  <ion-card-header>
                    <ion-card-title>Authentication</ion-card-title>
                  </ion-card-header>
                  <ion-card-content>
                    <div class="auth-section">
                      <div class="api-key-section">
                        <strong>API Key:</strong>
                        <div class="key-display">
                          <code class="api-key">{{ showFullKey ? subscription.api_key : maskedKey }}</code>
                          <ion-button fill="clear" size="small" @click="toggleKeyVisibility">
                            <ion-icon :name="showFullKey ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                          </ion-button>
                          <ion-button fill="clear" size="small" @click="copyApiKey">
                            <ion-icon name="copy-outline"></ion-icon>
                          </ion-button>
                        </div>
                      </div>
                      <div class="auth-example">
                        <strong>Usage Example:</strong>
                        <pre class="code-example">curl -H "Authorization: Bearer {{ subscription.api_key }}" \
     {{ api.endpoint_base }}/endpoint</pre>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </div>

              <ion-card>
                <ion-card-header>
                  <ion-card-title>Quick Start</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <div class="quick-start">
                    <h3>1. Set up your API key</h3>
                    <pre class="code-example">const API_KEY = '{{ subscription.api_key }}';
const BASE_URL = '{{ api.endpoint_base }}';</pre>

                    <h3>2. Make your first request</h3>
                    <pre class="code-example">fetch(`${BASE_URL}/endpoint`, {
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));</pre>

                    <h3>3. Handle responses</h3>
                    <p>All API responses follow a consistent format. Check the Documentation tab for detailed endpoint specifications.</p>
                  </div>
                </ion-card-content>
              </ion-card>
            </div>

            <!-- Documentation Tab -->
            <div v-if="selectedTab === 'docs'" class="tab-content">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>API Endpoints</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <div v-if="api.endpoints && api.endpoints.length > 0" class="endpoints-documentation">
                    <div v-for="endpoint in api.endpoints" :key="endpoint.id" class="endpoint-doc">
                      <div class="endpoint-header">
                        <ion-badge :color="getMethodColor(endpoint.method)" class="method-badge">
                          {{ endpoint.method }}
                        </ion-badge>
                        <h3>{{ endpoint.name }}</h3>
                        <code class="endpoint-path">{{ api.endpoint_base }}{{ endpoint.endpoint }}</code>
                      </div>
                      
                      <div class="endpoint-description" v-if="endpoint.description">
                        <p>{{ endpoint.description }}</p>
                      </div>

                      <div v-if="endpoint.parameters && Object.keys(endpoint.parameters).length > 0" class="parameters-section">
                        <h4>Parameters</h4>
                        <div class="parameters-table">
                          <div v-for="(param, paramName) in endpoint.parameters" :key="paramName" class="parameter-row">
                            <div class="param-name">
                              <code>{{ paramName }}</code>
                              <ion-badge v-if="param.required" color="danger" size="small">required</ion-badge>
                            </div>
                            <div class="param-info">
                              <span class="param-type">{{ param.type }}</span>
                              <span v-if="param.description" class="param-description">{{ param.description }}</span>
                              <span v-if="param.default" class="param-default">Default: {{ param.default }}</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-if="endpoint.example_request && Object.keys(endpoint.example_request).length > 0" class="example-section">
                        <h4>Example Request</h4>
                        <pre class="code-example">{{ formatJson(endpoint.example_request) }}</pre>
                      </div>

                      <div v-if="endpoint.example_response && Object.keys(endpoint.example_response).length > 0" class="example-section">
                        <h4>Example Response</h4>
                        <pre class="code-example">{{ formatJson(endpoint.example_response) }}</pre>
                      </div>

                      <div v-if="endpoint.response_schema && Object.keys(endpoint.response_schema).length > 0" class="schema-section">
                        <h4>Response Schema</h4>
                        <div class="schema-table">
                          <div v-for="(field, fieldName) in endpoint.response_schema" :key="fieldName" class="schema-row">
                            <code>{{ fieldName }}</code>
                            <span class="field-type">{{ field.type }}</span>
                            <span v-if="field.description" class="field-description">{{ field.description }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="no-endpoints">
                    <ion-icon name="document-outline" size="large" color="medium"></ion-icon>
                    <p>No documentation available for this API yet.</p>
                  </div>
                </ion-card-content>
              </ion-card>
            </div>

            <!-- Usage & Stats Tab -->
            <div v-if="selectedTab === 'usage'" class="tab-content">
              <div class="usage-cards">
                <ion-card>
                  <ion-card-header>
                    <ion-card-title>Usage Statistics</ion-card-title>
                  </ion-card-header>
                  <ion-card-content>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <h3>{{ usageStats.totalRequests || 0 }}</h3>
                        <p>Total Requests</p>
                      </div>
                      <div class="stat-item">
                        <h3>{{ usageStats.avgResponseTime || 0 }}ms</h3>
                        <p>Avg Response Time</p>
                      </div>
                      <div class="stat-item">
                        <h3>{{ usageStats.successRate || 0 }}%</h3>
                        <p>Success Rate</p>
                      </div>
                      <div class="stat-item">
                        <h3>{{ usageStats.requestsToday || 0 }}</h3>
                        <p>Requests Today</p>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>

                <ion-card>
                  <ion-card-header>
                    <ion-card-title>Rate Limiting</ion-card-title>
                  </ion-card-header>
                  <ion-card-content>
                    <div class="rate-limit-info">
                      <div class="limit-display">
                        <h3>{{ subscription.rate_limit }} requests/minute</h3>
                        <p>Current limit for your project</p>
                      </div>
                      <div class="usage-bar">
                        <div class="bar-background">
                          <div class="bar-fill" :style="{ width: usagePercentage + '%' }"></div>
                        </div>
                        <p>{{ currentUsage }}/{{ subscription.rate_limit }} requests this minute</p>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </div>

              <ion-card>
                <ion-card-header>
                  <ion-card-title>Recent Activity</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <div v-if="recentActivity.length > 0" class="activity-list">
                    <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
                      <div class="activity-method">
                        <ion-badge :color="getMethodColor(activity.method)">{{ activity.method }}</ion-badge>
                      </div>
                      <div class="activity-details">
                        <div class="activity-path">{{ activity.path }}</div>
                        <div class="activity-meta">
                          {{ formatDate(activity.timestamp) }} • 
                          <span :class="getStatusClass(activity.status)">{{ activity.status }}</span> • 
                          {{ activity.response_time }}ms
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="no-activity">
                    <ion-icon name="pulse-outline" size="large" color="medium"></ion-icon>
                    <p>No recent activity</p>
                  </div>
                </ion-card-content>
              </ion-card>
            </div>

            <!-- Settings Tab -->
            <div v-if="selectedTab === 'settings'" class="tab-content">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Subscription Settings</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-item>
                    <ion-label position="floating">Rate Limit (requests/minute)</ion-label>
                    <ion-input v-model="settings.rate_limit" type="number" min="1"></ion-input>
                  </ion-item>
                  <ion-item>
                    <ion-checkbox v-model="settings.is_enabled"></ion-checkbox>
                    <ion-label class="ion-margin-start">
                      <h3>Enable API Access</h3>
                      <p>When disabled, all requests will return 403 Forbidden</p>
                    </ion-label>
                  </ion-item>
                  <div class="save-section">
                    <ion-button @click="saveSettings" color="primary">
                      <ion-icon slot="start" name="save-outline"></ion-icon>
                      Save Settings
                    </ion-button>
                  </div>
                </ion-card-content>
              </ion-card>

              <ion-card color="danger">
                <ion-card-header>
                  <ion-card-title>Danger Zone</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <div class="danger-item">
                    <div>
                      <h3>Regenerate API Key</h3>
                      <p>Generate a new API key. The old key will stop working immediately.</p>
                    </div>
                    <ion-button @click="confirmRegenerateKey" color="light" fill="outline">
                      <ion-icon slot="start" name="refresh-outline"></ion-icon>
                      Regenerate Key
                    </ion-button>
                  </div>
                  <div class="danger-item">
                    <div>
                      <h3>Unsubscribe from API</h3>
                      <p>Remove this API from your project. All data will be lost.</p>
                    </div>
                    <ion-button @click="confirmUnsubscribe" color="light">
                      <ion-icon slot="start" name="trash-outline"></ion-icon>
                      Unsubscribe
                    </ion-button>
                  </div>
                </ion-card-content>
              </ion-card>
            </div>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>

      <!-- Test Results Modal -->
      <ion-modal :is-open="isTestModalOpen" ref="testModal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeTestModal">Close</ion-button>
            </ion-buttons>
            <ion-title>API Test Results</ion-title>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <div v-if="testResults" class="test-results">
            <div class="result-status" :class="getStatusClass(testResults.status)">
              Status: {{ testResults.status }}
            </div>
            <div class="result-headers" v-if="testResults.headers">
              <h4>Response Headers</h4>
              <pre>{{ formatJson(testResults.headers) }}</pre>
            </div>
            <div class="result-data">
              <h4>Response Data</h4>
              <pre>{{ formatJson(testResults.data) }}</pre>
            </div>
          </div>
          <div v-if="testLoading" class="test-loading">
            <ion-spinner></ion-spinner>
            <p>Testing API connection...</p>
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
  IonBadge, IonSegment, IonSegmentButton, IonInput, IonCheckbox,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner,
  alertController, toastController
} from '@ionic/vue';

export default defineComponent({
  name: 'ApiDocumentation',
  components: {
    IonPage, IonContent, IonGrid, IonRow, IonCol, IonButton, IonIcon,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel,
    IonBadge, IonSegment, IonSegmentButton, IonInput, IonCheckbox,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const selectedTab = ref('overview');
    const showFullKey = ref(false);
    const isTestModalOpen = ref(false);
    const testLoading = ref(false);
    const testResults = ref(null);
    
    const api = ref({
      id: 1,
      name: 'User Management API',
      slug: 'user-management',
      description: 'Create, read, update and delete users in your project',
      icon: 'people-outline',
      category: 'user',
      version: '1.0',
      endpoint_base: '/api/v1/users',
      endpoints: []
    });

    const subscription = ref({
      id: 1,
      api_key: 'cms_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6_123',
      rate_limit: 100,
      usage_count: 1547,
      last_used: '2025-08-05T14:30:00Z',
      is_enabled: true
    });

    const settings = ref({
      rate_limit: 100,
      is_enabled: true
    });

    const usageStats = ref({
      totalRequests: 1547,
      avgResponseTime: 245,
      successRate: 99.2,
      requestsToday: 47
    });

    const currentUsage = ref(23);
    const recentActivity = ref([]);

    const maskedKey = computed(() => {
      if (!subscription.value.api_key) return '';
      const key = subscription.value.api_key;
      return key.substring(0, 8) + '...' + key.substring(key.length - 4);
    });

    const usagePercentage = computed(() => {
      return Math.min((currentUsage.value / subscription.value.rate_limit) * 100, 100);
    });

    const loadApiData = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        const apiSlug = route.params.apiSlug;
        const project = route.params.project;
        
        const response = await fetch('/backend/apis.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': token
          },
          body: new URLSearchParams({
            'getApiDetails': '1',
            'api_slug': apiSlug,
            'project': project
          })
        });
        
        if (response.ok) {
          const data = await response.json();
          api.value = data;
          subscription.value = {
            id: data.subscription_id,
            api_key: data.api_key,
            rate_limit: data.rate_limit,
            usage_count: data.usage_count,
            last_used: data.last_used,
            is_enabled: data.is_enabled
          };
          
          if (data.usage_stats) {
            usageStats.value = data.usage_stats;
          }
          
          if (data.recent_activity) {
            recentActivity.value = data.recent_activity;
          }
          
          settings.value = {
            rate_limit: data.rate_limit,
            is_enabled: data.is_enabled
          };
        }
      } catch (error) {
        console.error('Error loading API data:', error);
        // Fallback to mock data
        loadMockData();
      }
    };

    const loadMockData = () => {
      // Mock data for development/fallback
      api.value = {
        id: 1,
        name: 'User Management API',
        slug: 'user-management', 
        description: 'Create, read, update and delete users in your project',
        icon: 'people-outline',
        category: 'user',
        version: '1.0',
        endpoint_base: '/api/v1/users',
        endpoints: [
          {
            id: 1,
            name: 'List Users',
            method: 'GET',
            endpoint: '/list',
            description: 'Get all users with optional filtering and pagination',
            parameters: {
              'limit': { type: 'integer', default: 10, description: 'Number of users to return' },
              'offset': { type: 'integer', default: 0, description: 'Number of users to skip' },
              'search': { type: 'string', optional: true, description: 'Search term for user names or emails' }
            },
            response_schema: {
              'users': { type: 'array', description: 'Array of user objects' },
              'total': { type: 'integer', description: 'Total number of users' },
              'pagination': { type: 'object', description: 'Pagination information' }
            },
            example_request: { limit: 10, search: 'john' },
            example_response: {
              users: [{ id: 1, name: 'John Doe', email: 'john@example.com' }],
              total: 1,
              pagination: { limit: 10, offset: 0 }
            }
          },
          {
            id: 2,
            name: 'Create User',
            method: 'POST',
            endpoint: '/create',
            description: 'Create a new user in the system',
            parameters: {
              'name': { type: 'string', required: true, description: 'Full name of the user' },
              'email': { type: 'string', required: true, description: 'Email address (must be unique)' },
              'password': { type: 'string', required: true, description: 'Password (min 8 characters)' }
            },
            response_schema: {
              'user': { type: 'object', description: 'Created user object' },
              'success': { type: 'boolean', description: 'Whether the operation was successful' }
            },
            example_request: { name: 'Jane Doe', email: 'jane@example.com', password: 'secret123' },
            example_response: {
              user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
              success: true
            }
          }
        ]
      };

      recentActivity.value = [
        {
          id: 1,
          method: 'GET',
          path: '/api/v1/users/list',
          status: 200,
          response_time: 156,
          timestamp: '2025-08-05T14:25:00Z'
        },
        {
          id: 2,
          method: 'POST',
          path: '/api/v1/users/create',
          status: 201,
          response_time: 234,
          timestamp: '2025-08-05T14:20:00Z'
        }
      ];
    };

    const getStatusColor = (status: string) => {
      switch (status) {
        case 'active': return 'success';
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

    const getStatusClass = (status: number) => {
      if (status >= 200 && status < 300) return 'success';
      if (status >= 400 && status < 500) return 'warning';
      if (status >= 500) return 'danger';
      return 'medium';
    };

    const formatDate = (dateString: string) => {
      return new Date(dateString).toLocaleString();
    };

    const formatJson = (obj: any) => {
      return JSON.stringify(obj, null, 2);
    };

    const toggleKeyVisibility = () => {
      showFullKey.value = !showFullKey.value;
      if (showFullKey.value) {
        // Auto-hide after 10 seconds
        setTimeout(() => {
          showFullKey.value = false;
        }, 10000);
      }
    };

    const copyApiKey = async () => {
      try {
        await navigator.clipboard.writeText(subscription.value.api_key);
        showToast('API key copied to clipboard', 'success');
      } catch (error) {
        showToast('Failed to copy API key', 'danger');
      }
    };

    const testApi = () => {
      testLoading.value = true;
      isTestModalOpen.value = true;
      
      // Mock test
      setTimeout(() => {
        testResults.value = {
          status: 200,
          headers: {
            'Content-Type': 'application/json',
            'X-RateLimit-Remaining': '97'
          },
          data: {
            message: 'API is working correctly',
            timestamp: new Date().toISOString(),
            version: '1.0'
          }
        };
        testLoading.value = false;
      }, 2000);
    };

    const closeTestModal = () => {
      isTestModalOpen.value = false;
      testResults.value = null;
      testLoading.value = false;
    };

    const regenerateKey = async () => {
      const alert = await alertController.create({
        header: 'Regenerate API Key',
        message: 'This will generate a new API key. Your old key will stop working immediately. Are you sure?',
        buttons: [
          { text: 'Cancel', role: 'cancel' },
          {
            text: 'Regenerate',
            role: 'destructive',
            handler: () => performRegenerateKey()
          }
        ]
      });
      await alert.present();
    };

    const performRegenerateKey = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        
        const response = await fetch('/backend/apis.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': token
          },
          body: new URLSearchParams({
            'regenerateApiKey': '1',
            'subscription_id': subscription.value.id.toString()
          })
        });
        
        if (response.ok) {
          const data = await response.json();
          subscription.value.api_key = data.api_key;
          showToast('API key regenerated successfully', 'success');
        } else {
          showToast('Failed to regenerate API key', 'danger');
        }
      } catch (error) {
        console.error('Error regenerating API key:', error);
        showToast('Failed to regenerate API key', 'danger');
      }
    };

    const confirmRegenerateKey = () => {
      regenerateKey();
    };

    const confirmUnsubscribe = async () => {
      const alert = await alertController.create({
        header: 'Unsubscribe from API',
        message: 'This will remove this API from your project. All usage data will be lost. Are you sure?',
        buttons: [
          { text: 'Cancel', role: 'cancel' },
          {
            text: 'Unsubscribe',
            role: 'destructive',
            handler: () => performUnsubscribe()
          }
        ]
      });
      await alert.present();
    };

    const performUnsubscribe = async () => {
      try {
        // Mock unsubscribe
        showToast('Successfully unsubscribed from API', 'success');
        router.push(`/project/${route.params.project}/manage/apis`);
      } catch (error) {
        showToast('Failed to unsubscribe from API', 'danger');
      }
    };

    const saveSettings = async () => {
      try {
        const token = localStorage.getItem('controlCenter_auth_token');
        
        const response = await fetch('/backend/apis.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': token
          },
          body: new URLSearchParams({
            'updateApiSettings': '1',
            'subscription_id': subscription.value.id.toString(),
            'rate_limit': settings.value.rate_limit.toString(),
            'is_enabled': settings.value.is_enabled.toString()
          })
        });
        
        if (response.ok) {
          subscription.value.rate_limit = settings.value.rate_limit;
          subscription.value.is_enabled = settings.value.is_enabled;
          showToast('Settings saved successfully', 'success');
        } else {
          showToast('Failed to save settings', 'danger');
        }
      } catch (error) {
        console.error('Error saving settings:', error);
        showToast('Failed to save settings', 'danger');
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
      loadApiData();
      settings.value = {
        rate_limit: subscription.value.rate_limit,
        is_enabled: subscription.value.is_enabled
      };
    });

    return {
      selectedTab,
      showFullKey,
      isTestModalOpen,
      testLoading,
      testResults,
      api,
      subscription,
      settings,
      usageStats,
      currentUsage,
      recentActivity,
      maskedKey,
      usagePercentage,
      getStatusColor,
      getMethodColor,
      getStatusClass,
      formatDate,
      formatJson,
      toggleKeyVisibility,
      copyApiKey,
      testApi,
      closeTestModal,
      regenerateKey,
      confirmRegenerateKey,
      confirmUnsubscribe,
      saveSettings
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

.api-title {
  flex: 1;
  text-align: center;
}

.api-title h1 {
  color: var(--ion-color-primary);
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.title-icon {
  font-size: 2rem;
}

.category-badge {
  margin-left: 8px;
}

.api-actions {
  display: flex;
  gap: 8px;
  align-self: flex-start;
}

.api-tabs {
  margin-bottom: 24px;
}

.tab-content {
  min-height: 400px;
}

.overview-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

@media (max-width: 768px) {
  .overview-cards {
    grid-template-columns: 1fr;
  }
  
  .api-header {
    flex-direction: column;
    align-items: center;
  }
}

.info-row {
  margin-bottom: 12px;
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.info-row strong {
  min-width: 120px;
  color: var(--ion-color-primary);
}

.info-row code {
  background: var(--ion-color-step-100);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

.auth-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.key-display {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}

.api-key {
  background: var(--ion-color-step-100);
  padding: 8px 12px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  flex: 1;
}

.code-example {
  background: var(--ion-color-step-100);
  padding: 16px;
  border-radius: 8px;
  overflow-x: auto;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
  margin: 8px 0;
}

.quick-start h3 {
  color: var(--ion-color-primary);
  margin: 24px 0 8px 0;
}

.quick-start h3:first-child {
  margin-top: 0;
}

.endpoints-documentation {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.endpoint-doc {
  border: 1px solid var(--ion-color-step-200);
  border-radius: 8px;
  padding: 20px;
  background: var(--ion-color-step-50);
}

.endpoint-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.endpoint-header h3 {
  margin: 0;
  color: var(--ion-color-primary);
}

.endpoint-path {
  background: var(--ion-color-step-200);
  padding: 4px 8px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

.method-badge {
  min-width: 60px;
  text-align: center;
}

.endpoint-description {
  margin-bottom: 16px;
  color: var(--ion-color-medium);
}

.parameters-section,
.example-section,
.schema-section {
  margin-top: 20px;
}

.parameters-section h4,
.example-section h4,
.schema-section h4 {
  color: var(--ion-color-primary);
  margin-bottom: 12px;
}

.parameters-table,
.schema-table {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.parameter-row,
.schema-row {
  display: flex;
  gap: 16px;
  padding: 8px;
  background: var(--ion-color-step-100);
  border-radius: 4px;
}

.param-name {
  min-width: 120px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.param-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.param-type {
  color: var(--ion-color-primary);
  font-weight: 500;
}

.param-description {
  color: var(--ion-color-medium);
  font-size: 0.9em;
}

.param-default {
  color: var(--ion-color-tertiary);
  font-size: 0.8em;
}

.field-type {
  color: var(--ion-color-primary);
  font-weight: 500;
  margin-left: 16px;
}

.field-description {
  color: var(--ion-color-medium);
  margin-left: 16px;
}

.no-endpoints,
.no-activity {
  text-align: center;
  padding: 32px;
  color: var(--ion-color-medium);
}

.usage-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.stat-item {
  text-align: center;
  padding: 16px;
  background: var(--ion-color-step-100);
  border-radius: 8px;
}

.stat-item h3 {
  margin: 0 0 4px 0;
  color: var(--ion-color-primary);
  font-size: 1.5em;
}

.stat-item p {
  margin: 0;
  color: var(--ion-color-medium);
  font-size: 0.9em;
}

.rate-limit-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.limit-display {
  text-align: center;
}

.limit-display h3 {
  margin: 0 0 4px 0;
  color: var(--ion-color-primary);
}

.usage-bar {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bar-background {
  height: 8px;
  background: var(--ion-color-step-200);
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: var(--ion-color-primary);
  transition: width 0.3s ease;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  background: var(--ion-color-step-100);
  border-radius: 8px;
}

.activity-method {
  flex-shrink: 0;
}

.activity-details {
  flex: 1;
}

.activity-path {
  font-family: 'Courier New', monospace;
  font-weight: 500;
}

.activity-meta {
  font-size: 0.8em;
  color: var(--ion-color-medium);
  margin-top: 4px;
}

.activity-meta .success {
  color: var(--ion-color-success);
}

.activity-meta .warning {
  color: var(--ion-color-warning);
}

.activity-meta .danger {
  color: var(--ion-color-danger);
}

.save-section {
  margin-top: 20px;
  text-align: center;
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

.result-headers,
.result-data {
  margin-bottom: 16px;
}

.result-headers h4,
.result-data h4 {
  color: var(--ion-color-primary);
  margin-bottom: 8px;
}

.result-headers pre,
.result-data pre {
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

@media (max-width: 768px) {
  .usage-cards {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .danger-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
