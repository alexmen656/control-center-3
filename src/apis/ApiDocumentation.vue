<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle v-if="true" icon="cloud-outline" title="API Documentation"/>
      
      <div class="page-container">
        <!-- Navigation Header -->
        <div class="nav-header">
          <button class="back-btn" @click="$router.go(-1)">
            <ion-icon name="arrow-back-outline"></ion-icon>
            <span>Back to APIs</span>
          </button>
          
          <div class="api-info">
            <div class="api-icon">
              <ion-icon :name="api.icon || 'cloud-outline'"></ion-icon>
            </div>
            <div class="api-details">
              <h1>{{ api.name }}</h1>
              <p>{{ api.description }}</p>
              <div class="api-meta">
                <ion-badge :color="getStatusColor('active')" class="status-badge">Active</ion-badge>
                <span class="version">v{{ api.version }}</span>
                <span class="category">{{ api.category }}</span>
              </div>
            </div>
          </div>
          
          <div class="header-actions">
            <button class="action-btn primary" @click="testApi">
              <ion-icon name="flash-outline"></ion-icon>
              <span>Test API</span>
            </button>
            <button class="action-btn secondary" @click="regenerateKey">
              <ion-icon name="refresh-outline"></ion-icon>
              <span>Regenerate Key</span>
            </button>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
          <div class="tab-buttons">
            <button 
              class="tab-btn" 
              :class="{ active: selectedTab === 'overview' }"
              @click="selectedTab = 'overview'"
            >
              <ion-icon name="information-circle-outline"></ion-icon>
              <span>Overview</span>
            </button>
            <button 
              class="tab-btn" 
              :class="{ active: selectedTab === 'docs' }"
              @click="selectedTab = 'docs'"
            >
              <ion-icon name="document-text-outline"></ion-icon>
              <span>Documentation</span>
            </button>
            <button 
              class="tab-btn" 
              :class="{ active: selectedTab === 'usage' }"
              @click="selectedTab = 'usage'"
            >
              <ion-icon name="analytics-outline"></ion-icon>
              <span>Usage & Stats</span>
            </button>
            <button 
              class="tab-btn" 
              :class="{ active: selectedTab === 'settings' }"
              @click="selectedTab = 'settings'"
            >
              <ion-icon name="settings-outline"></ion-icon>
              <span>Settings</span>
            </button>
          </div>
        </div>

        <!-- Content Sections -->
        <!-- Overview Tab -->
        <div v-if="selectedTab === 'overview'" class="tab-content">
          <div class="content-cards">
            <div class="card modern-card">
              <div class="card-header">
                <h3>API Information</h3>
              </div>
              <div class="card-content">
                <div class="info-grid">
                  <div class="info-item">
                    <div class="info-label">Base URL</div>
                    <div class="info-value">
                      <code>{{ api.endpoint_base }}</code>
                    </div>
                  </div>
                  <div class="info-item">
                    <div class="info-label">Version</div>
                    <div class="info-value">{{ api.version }}</div>
                  </div>
                  <div class="info-item">
                    <div class="info-label">Rate Limit</div>
                    <div class="info-value">{{ subscription.rate_limit }} requests/minute</div>
                  </div>
                  <div class="info-item">
                    <div class="info-label">Total Usage</div>
                    <div class="info-value">{{ subscription.usage_count }} requests</div>
                  </div>
                  <div class="info-item" v-if="subscription.last_used">
                    <div class="info-label">Last Used</div>
                    <div class="info-value">{{ formatDate(subscription.last_used) }}</div>
                  </div>
                  <div class="info-item" v-if="api.description">
                    <div class="info-label">Description</div>
                    <div class="info-value">{{ api.description }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card modern-card">
              <div class="card-header">
                <h3>Authentication</h3>
              </div>
              <div class="card-content">
                <div class="auth-section">
                  <div class="api-key-section">
                    <div class="key-label">API Key</div>
                    <div class="key-display">
                      <div class="api-key">{{ showFullKey ? subscription.api_key : maskedKey }}</div>
                      <div class="key-actions">
                        <button class="icon-btn" @click="toggleKeyVisibility" :title="showFullKey ? 'Hide key' : 'Show key'">
                          <ion-icon :name="showFullKey ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                        </button>
                        <button class="icon-btn" @click="copyApiKey" title="Copy to clipboard">
                          <ion-icon name="copy-outline"></ion-icon>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="auth-example">
                    <div class="example-label">Usage Example</div>
                    <div class="code-example">
curl -H "Authorization: Bearer {{ subscription.api_key }}" \
     {{ api.endpoint_base }}/endpoint
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card modern-card">
            <div class="card-header">
              <h3>Quick Start Guide</h3>
            </div>
            <div class="card-content">
              <div class="quick-start">
                <div class="step">
                  <div class="step-number">1</div>
                  <div class="step-content">
                    <h4>Set up your API key</h4>
                    <div class="code-example">
const API_KEY = '{{ subscription.api_key }}';
const BASE_URL = '{{ api.endpoint_base }}';
                    </div>
                  </div>
                </div>

                <div class="step">
                  <div class="step-number">2</div>
                  <div class="step-content">
                    <h4>Make your first request</h4>
                    <div class="code-example">
fetch(`${BASE_URL}/endpoint`, {
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
                    </div>
                  </div>
                </div>

                <div class="step">
                  <div class="step-number">3</div>
                  <div class="step-content">
                    <h4>Handle responses</h4>
                    <p>All API responses follow a consistent format. Check the Documentation tab for detailed endpoint specifications.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documentation Tab -->
        <div v-if="selectedTab === 'docs'" class="tab-content">
          <div class="card modern-card">
            <div class="card-header">
              <h3>API Endpoints</h3>
            </div>
            <div class="card-content">
              <div v-if="api.endpoints && api.endpoints.length > 0" class="endpoints-documentation">
                <div v-for="endpoint in api.endpoints" :key="endpoint.id" class="endpoint-doc">
                  <div class="endpoint-header">
                    <div class="method-badge" :class="getMethodColor(endpoint.method)">
                      {{ endpoint.method }}
                    </div>
                    <div class="endpoint-info">
                      <h4>{{ endpoint.name }}</h4>
                      <div class="endpoint-path">{{ api.endpoint_base }}{{ endpoint.endpoint }}</div>
                    </div>
                  </div>
                  
                  <div class="endpoint-description" v-if="endpoint.description">
                    <p>{{ endpoint.description }}</p>
                  </div>

                  <div v-if="endpoint.parameters && Object.keys(endpoint.parameters).length > 0" class="parameters-section">
                    <h5>Parameters</h5>
                    <div class="parameters-list">
                      <div v-for="(param, paramName) in endpoint.parameters" :key="paramName" class="parameter-item">
                        <div class="param-header">
                          <code class="param-name">{{ paramName }}</code>
                          <span class="param-type">{{ param.type }}</span>
                          <span v-if="param.required" class="param-required">required</span>
                        </div>
                        <div v-if="param.description" class="param-description">{{ param.description }}</div>
                        <div v-if="param.default" class="param-default">Default: {{ param.default }}</div>
                      </div>
                    </div>
                  </div>

                  <div v-if="endpoint.example_request && Object.keys(endpoint.example_request).length > 0" class="example-section">
                    <h5>Example Request</h5>
                    <div class="code-example">{{ formatJson(endpoint.example_request) }}</div>
                  </div>

                  <div v-if="endpoint.example_response && Object.keys(endpoint.example_response).length > 0" class="example-section">
                    <h5>Example Response</h5>
                    <div class="code-example">{{ formatJson(endpoint.example_response) }}</div>
                  </div>

                  <div v-if="endpoint.response_schema && Object.keys(endpoint.response_schema).length > 0" class="schema-section">
                    <h5>Response Schema</h5>
                    <div class="schema-list">
                      <div v-for="(field, fieldName) in endpoint.response_schema" :key="fieldName" class="schema-item">
                        <code class="field-name">{{ fieldName }}</code>
                        <span class="field-type">{{ field.type }}</span>
                        <span v-if="field.description" class="field-description">{{ field.description }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="no-endpoints">
                <div class="empty-state">
                  <ion-icon name="document-outline" size="large"></ion-icon>
                  <h4>No Documentation Available</h4>
                  <p>No documentation available for this API yet.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Usage & Stats Tab -->
        <div v-if="selectedTab === 'usage'" class="tab-content">
          <div class="content-cards">
            <div class="card modern-card">
              <div class="card-header">
                <h3>Usage Statistics</h3>
              </div>
              <div class="card-content">
                <div class="stats-grid">
                  <div class="stat-item">
                    <div class="stat-value">{{ usageStats.totalRequests || 0 }}</div>
                    <div class="stat-label">Total Requests</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">{{ usageStats.avgResponseTime || 0 }}ms</div>
                    <div class="stat-label">Avg Response Time</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">{{ usageStats.successRate || 0 }}%</div>
                    <div class="stat-label">Success Rate</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">{{ usageStats.requestsToday || 0 }}</div>
                    <div class="stat-label">Requests Today</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card modern-card">
              <div class="card-header">
                <h3>Rate Limiting</h3>
              </div>
              <div class="card-content">
                <div class="rate-limit-info">
                  <div class="limit-display">
                    <div class="limit-value">{{ subscription.rate_limit }} requests/minute</div>
                    <div class="limit-description">Current limit for your project</div>
                  </div>
                  <div class="usage-bar">
                    <div class="bar-background">
                      <div class="bar-fill" :style="{ width: usagePercentage + '%' }"></div>
                    </div>
                    <div class="bar-text">{{ currentUsage }}/{{ subscription.rate_limit }} requests this minute</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card modern-card">
            <div class="card-header">
              <h3>Recent Activity</h3>
            </div>
            <div class="card-content">
              <div v-if="recentActivity.length > 0" class="activity-list">
                <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
                  <div class="activity-method" :class="getMethodColor(activity.method)">
                    {{ activity.method }}
                  </div>
                  <div class="activity-details">
                    <div class="activity-path">{{ activity.path }}</div>
                    <div class="activity-meta">
                      <span class="activity-status" :class="getStatusClass(activity.status)">{{ activity.status }}</span>
                      <span class="activity-time">{{ activity.response_time }}ms</span>
                      <span class="activity-timestamp">{{ formatDate(activity.timestamp) }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="no-activity">
                <div class="empty-state">
                  <ion-icon name="pulse-outline" size="large"></ion-icon>
                  <h4>No Recent Activity</h4>
                  <p>No recent activity</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Settings Tab -->
        <div v-if="selectedTab === 'settings'" class="tab-content">
          <div class="card modern-card">
            <div class="card-header">
              <h3>Subscription Settings</h3>
            </div>
            <div class="card-content">
              <div class="settings-form">
                <div class="form-field">
                  <label>Rate Limit (requests/minute)</label>
                  <input 
                    v-model.number="settings.rate_limit" 
                    type="number" 
                    min="1"
                    class="form-input"
                  />
                </div>
                <div class="form-field checkbox-field">
                  <label class="checkbox-label">
                    <input 
                      type="checkbox" 
                      v-model="settings.is_enabled"
                      class="form-checkbox"
                    />
                    <span class="checkbox-content">
                      <strong>Enable API Access</strong>
                      <span class="checkbox-description">When disabled, all requests will return 403 Forbidden</span>
                    </span>
                  </label>
                </div>
                <div class="form-actions">
                  <button class="action-btn primary" @click="saveSettings">
                    <ion-icon name="save-outline"></ion-icon>
                    <span>Save Settings</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="card modern-card danger-card">
            <div class="card-header">
              <h3>Danger Zone</h3>
            </div>
            <div class="card-content">
              <div class="danger-actions">
                <div class="danger-item">
                  <div class="danger-info">
                    <h4>Regenerate API Key</h4>
                    <p>Generate a new API key. The old key will stop working immediately.</p>
                  </div>
                  <button class="action-btn danger-outline" @click="confirmRegenerateKey">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Regenerate Key</span>
                  </button>
                </div>
                <div class="danger-item">
                  <div class="danger-info">
                    <h4>Unsubscribe from API</h4>
                    <p>Remove this API from your project. All data will be lost.</p>
                  </div>
                  <button class="action-btn danger" @click="confirmUnsubscribe">
                    <ion-icon name="trash-outline"></ion-icon>
                    <span>Unsubscribe</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

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
  IonPage, IonContent, IonButton, IonIcon, IonBadge,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner,
  alertController, toastController
} from '@ionic/vue';
import SiteTitle from '@/components/SiteTitle.vue';

export default defineComponent({
  name: 'ApiDocumentation',
  components: {
    IonPage, IonContent, IonButton, IonIcon, IonBadge,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner,
    SiteTitle
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
        const apiSlug = route.params.apiSlug as string;
        const project = route.params.project as string;
        
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
        case 'GET': return 'GET';
        case 'POST': return 'POST';
        case 'PUT': return 'PUT';
        case 'DELETE': return 'DELETE';
        case 'PATCH': return 'PATCH';
        default: return 'GET';
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
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
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
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Navigation Header */
.nav-header {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 32px;
  padding: 24px;
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
  cursor: pointer;
}

.back-btn:hover {
  background: var(--border);
  transform: translateY(-1px);
}

.api-info {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.api-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.api-details h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

.api-details p {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.api-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  background: var(--success-color);
  color: white;
}

.version,
.category {
  padding: 4px 8px;
  background: var(--background);
  border-radius: 4px;
  font-size: 12px;
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--background);
  color: var(--text-secondary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn.danger-outline {
  background: var(--surface);
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.action-btn.danger-outline:hover {
  background: #fef2f2;
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Tab Navigation */
.tab-navigation {
  margin-bottom: 24px;
}

.tab-buttons {
  display: flex;
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 4px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: transparent;
  border: none;
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
  justify-content: center;
}

.tab-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.tab-btn.active {
  background: var(--primary-color);
  color: white;
  box-shadow: var(--shadow);
}

.tab-btn ion-icon {
  font-size: 16px;
}

/* Content Cards */
.tab-content {
  margin-bottom: 24px;
}

.content-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

.card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.modern-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.card-content {
  padding: 24px;
}

/* Info Grid */
.info-grid {
  display: grid;
  gap: 16px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 500;
  color: var(--text-secondary);
  min-width: 120px;
}

.info-value {
  color: var(--text-primary);
  text-align: right;
  flex: 1;
}

.info-value code {
  background: var(--background);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  color: var(--primary-color);
}

/* Authentication Section */
.auth-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.api-key-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.key-label {
  font-weight: 500;
  color: var(--text-secondary);
}

.key-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.api-key {
  flex: 1;
  background: var(--background);
  padding: 8px 12px;
  border-radius: var(--radius);
  font-family: 'Courier New', monospace;
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.key-actions {
  display: flex;
  gap: 4px;
}

.icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: var(--background);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.auth-example {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.example-label {
  font-weight: 500;
  color: var(--text-secondary);
}

.code-example {
  background: var(--background);
  padding: 16px;
  border-radius: var(--radius);
  font-family: 'Courier New', monospace;
  font-size: 13px;
  color: var(--text-primary);
  border: 1px solid var(--border);
  overflow-x: auto;
}

/* Quick Start */
.quick-start {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.step {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.step-content {
  flex: 1;
}

.step-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.step-content p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

/* Endpoints Documentation */
.endpoints-documentation {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.endpoint-doc {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  background: var(--background);
}

.endpoint-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.method-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  min-width: 60px;
  text-align: center;
}

.method-badge.GET {
  background: #dbeafe;
  color: var(--primary-color);
}

.method-badge.POST {
  background: #dcfce7;
  color: var(--success-color);
}

.method-badge.PUT {
  background: #fef3c7;
  color: var(--warning-color);
}

.method-badge.DELETE {
  background: #fee2e2;
  color: var(--danger-color);
}

.endpoint-info {
  flex: 1;
}

.endpoint-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.endpoint-path {
  font-family: 'Courier New', monospace;
  color: var(--text-secondary);
  font-size: 14px;
}

.endpoint-description {
  margin-bottom: 16px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.parameters-section,
.example-section,
.schema-section {
  margin-top: 20px;
}

.parameters-section h5,
.example-section h5,
.schema-section h5 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.parameters-list,
.schema-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.parameter-item,
.schema-item {
  padding: 12px;
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.param-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.param-name,
.field-name {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: var(--primary-color);
}

.param-type,
.field-type {
  padding: 2px 6px;
  background: var(--background);
  border-radius: 4px;
  font-size: 12px;
  color: var(--text-secondary);
}

.param-required {
  padding: 2px 6px;
  background: var(--danger-color);
  color: white;
  border-radius: 4px;
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 600;
}

.param-description,
.field-description {
  color: var(--text-secondary);
  font-size: 13px;
  margin-top: 4px;
}

.param-default {
  color: var(--text-muted);
  font-size: 12px;
  margin-top: 4px;
}

/* Empty States */
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: var(--text-muted);
}

.empty-state ion-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h4 {
  margin: 0 0 8px 0;
  color: var(--text-secondary);
  font-size: 16px;
  font-weight: 500;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.stat-item {
  text-align: center;
  padding: 20px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Rate Limiting */
.rate-limit-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.limit-display {
  text-align: center;
}

.limit-value {
  font-size: 18px;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.limit-description {
  font-size: 14px;
  color: var(--text-secondary);
}

.usage-bar {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bar-background {
  height: 8px;
  background: var(--border);
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.3s ease;
  border-radius: 4px;
}

.bar-text {
  font-size: 12px;
  color: var(--text-secondary);
  text-align: center;
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.activity-method {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  min-width: 60px;
  text-align: center;
  flex-shrink: 0;
}

.activity-method.GET {
  background: #dbeafe;
  color: var(--primary-color);
}

.activity-method.POST {
  background: #dcfce7;
  color: var(--success-color);
}

.activity-method.PUT {
  background: #fef3c7;
  color: var(--warning-color);
}

.activity-method.DELETE {
  background: #fee2e2;
  color: var(--danger-color);
}

.activity-details {
  flex: 1;
}

.activity-path {
  font-family: 'Courier New', monospace;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.activity-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: var(--text-muted);
}

.activity-status {
  font-weight: 500;
}

.activity-status.success {
  color: var(--success-color);
}

.activity-status.warning {
  color: var(--warning-color);
}

.activity-status.danger {
  color: var(--danger-color);
}

/* Settings Form */
.settings-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-field label {
  font-weight: 500;
  color: var(--text-secondary);
  font-size: 14px;
}

.form-input {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.checkbox-field {
  margin: 8px 0;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
}

.form-checkbox {
  width: 16px;
  height: 16px;
  margin-top: 2px;
}

.checkbox-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checkbox-content strong {
  color: var(--text-primary);
  font-weight: 500;
}

.checkbox-description {
  color: var(--text-secondary);
  font-size: 13px;
}

.form-actions {
  padding-top: 8px;
}

/* Danger Zone */
.danger-card {
  border-color: var(--danger-color);
}

.danger-card .card-header {
  background: #fef2f2;
  border-bottom-color: var(--danger-color);
}

.danger-card .card-header h3 {
  color: var(--danger-color);
}

.danger-actions {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.danger-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid var(--border);
}

.danger-item:last-child {
  border-bottom: none;
}

.danger-info {
  flex: 1;
}

.danger-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 500;
}

.danger-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .nav-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .api-info {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .tab-buttons {
    flex-direction: column;
  }
  
  .content-cards {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .danger-item {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .activity-item {
    flex-direction: column;
    gap: 8px;
  }
  
  .endpoint-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .param-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}

@media (max-width: 480px) {
  .api-details h1 {
    font-size: 20px;
  }
  
  .tab-btn {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .tab-btn span {
    display: none;
  }
}
</style>
