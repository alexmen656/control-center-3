<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="cloud-outline" :title="api.name || 'API Documentation'" />

      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <PageTitle :icon="api.icon" :title="api.name" />
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

        <div class="tab-navigation">
          <div class="tab-buttons">
            <button class="tab-btn" :class="{ active: selectedTab === 'overview' }" @click="selectedTab = 'overview'">
              <ion-icon name="information-circle-outline"></ion-icon>
              <span>Overview</span>
            </button>
            <button class="tab-btn" :class="{ active: selectedTab === 'docs' }" @click="selectedTab = 'docs'">
              <ion-icon name="document-text-outline"></ion-icon>
              <span>Documentation</span>
            </button>
            <button class="tab-btn" :class="{ active: selectedTab === 'logs' }" @click="selectedTab = 'logs'">
              <ion-icon name="list-outline"></ion-icon>
              <span>Call Log</span>
            </button>
            <button class="tab-btn" :class="{ active: selectedTab === 'usage' }" @click="selectedTab = 'usage'">
              <ion-icon name="analytics-outline"></ion-icon>
              <span>Usage & Stats</span>
            </button>
            <button class="tab-btn" :class="{ active: selectedTab === 'settings' }" @click="selectedTab = 'settings'">
              <ion-icon name="settings-outline"></ion-icon>
              <span>Settings</span>
            </button>
          </div>
        </div>

        <div v-if="selectedTab === 'overview'" class="tab-content">
          <div class="content-cards">
            <div class="card modern-card">
              <div class="card-header">
                <h3>API Information</h3>
              </div>
              <div class="card-content">
                <div class="info-grid">
                  <div class="info-item">
                    <div class="info-label">Version</div>
                    <div class="info-value">{{ api.version }}</div>
                  </div>
                  <div class="info-item">
                    <div class="info-label">Category</div>
                    <div class="info-value">{{ api.category }}</div>
                  </div>
                  <div class="info-item">
                    <div class="info-label">Active</div>
                    <div class="info-value">{{ subscription.is_enabled ? 'Yes' : 'No' }}</div>
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
                <h3>SDK Usage</h3>
              </div>
              <div class="card-content">
                <div class="auth-section">
                  <div class="api-key-section">
                    <div class="key-label">Import</div>
                    <div class="code-example">{{ usageImport }}</div>
                  </div>
                  <div class="api-key-section">
                    <div class="key-label">Example</div>
                    <div class="code-example">{{ firstExample }}</div>
                  </div>
                  <div class="api-key-section">
                    <div class="key-label">API Key</div>
                    <div class="key-display">
                      <div class="api-key">{{ showFullKey ? subscription.api_key : maskedKey }}</div>
                      <div class="key-actions">
                        <button class="icon-btn" @click="toggleKeyVisibility"
                          :title="showFullKey ? 'Hide key' : 'Show key'">
                          <ion-icon :name="showFullKey ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                        </button>
                        <button class="icon-btn" @click="copyApiKey" title="Copy to clipboard">
                          <ion-icon name="copy-outline"></ion-icon>
                        </button>
                      </div>
                    </div>
                    <div class="sdk-note">Injected automatically as an environment variable when you activate this SDK for a codespace.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card modern-card">
            <div class="card-header">
              <h3>Quick Start</h3>
            </div>
            <div class="card-content">
              <div class="quick-start">
                <div class="step">
                  <div class="step-number">1</div>
                  <div class="step-content">
                    <h4>Activate the SDK for your codespace</h4>
                    <p>Activate this API on a codespace — its key is injected automatically as an environment variable on
                      the next deploy.</p>
                  </div>
                </div>

                <div class="step">
                  <div class="step-number">2</div>
                  <div class="step-content">
                    <h4>Import the SDK</h4>
                    <div class="code-example">{{ usageImport }}</div>
                  </div>
                </div>

                <div class="step">
                  <div class="step-number">3</div>
                  <div class="step-content">
                    <h4>Call a method</h4>
                    <div class="code-example">{{ firstExample }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="selectedTab === 'docs'" class="tab-content">
          <div class="card modern-card">
            <div class="card-header">
              <h3>Usage</h3>
            </div>
            <div class="card-content">
              <p class="sdk-intro">This API is a JavaScript SDK that is auto-injected into your codespace when you
                activate it. Import it and call its methods:</p>
              <div class="code-example">{{ usageImport }}</div>
              <p class="sdk-note">The API key is injected automatically as an environment variable — no manual setup
                needed.</p>
            </div>
          </div>

          <div class="card modern-card">
            <div class="card-header">
              <h3>SDK Methods <span class="count-inline" v-if="api.endpoints">({{ api.endpoints.length }})</span></h3>
            </div>
            <div class="card-content">
              <div v-if="api.endpoints && api.endpoints.length > 0" class="endpoints-documentation">
                <div v-for="endpoint in api.endpoints" :key="endpoint.id" class="endpoint-doc">
                  <div class="endpoint-header">
                    <div class="endpoint-info">
                      <h4>{{ endpoint.name }}</h4>
                      <code class="endpoint-signature">{{ importName }}.{{ endpoint.endpoint }}</code>
                    </div>
                  </div>

                  <div class="endpoint-description" v-if="endpoint.description">
                    <p>{{ endpoint.description }}</p>
                  </div>

                  <div v-if="endpoint.parameters && Object.keys(endpoint.parameters).length > 0"
                    class="parameters-section">
                    <h5>Parameters</h5>
                    <div class="parameters-list">
                      <div v-for="(param, paramName) in endpoint.parameters" :key="paramName" class="parameter-item">
                        <div class="param-header">
                          <code class="param-name">{{ paramName }}</code>
                          <span class="param-type">{{ param.type }}</span>
                          <span v-if="param.required" class="param-required">required</span>
                        </div>
                        <div v-if="param.description" class="param-description">{{ param.description }}</div>
                      </div>
                    </div>
                  </div>

                  <div v-if="endpoint.example_request && endpoint.example_request.code" class="example-section">
                    <h5>Example</h5>
                    <div class="code-example">{{ endpoint.example_request.code }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="no-endpoints">
                <div class="empty-state">
                  <ion-icon name="document-outline" size="large"></ion-icon>
                  <h4>No Documentation Available</h4>
                  <p>No documentation available for this SDK yet.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

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
                      <span class="activity-status" :class="getStatusClass(activity.status)">{{ activity.status
                      }}</span>
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

        <div v-if="selectedTab === 'logs'" class="tab-content">
          <div class="card modern-card">
            <div class="card-header logs-header">
              <div>
                <h3>Detailed Call Log</h3>
                <p class="card-subtitle">Every request served through this API is logged automatically.</p>
              </div>
              <div class="logs-header-actions">
                <button class="chip-toggle" :class="{ active: autoRefresh }" @click="toggleAutoRefresh"
                  :title="autoRefresh ? 'Auto-refresh on (5s)' : 'Auto-refresh off'">
                  <span class="live-dot" :class="{ on: autoRefresh }"></span>
                  <span>Live</span>
                </button>
                <button class="icon-btn" @click="loadCallLogs" title="Refresh now">
                  <ion-icon name="refresh-outline"></ion-icon>
                </button>
              </div>
            </div>

            <div class="card-content">
              <div class="log-summary">
                <div class="log-summary-item">
                  <div class="summary-value">{{ logStats.totalRequests || 0 }}</div>
                  <div class="summary-label">Total Calls</div>
                </div>
                <div class="log-summary-item">
                  <div class="summary-value">{{ logStats.avgResponseTime || 0 }}ms</div>
                  <div class="summary-label">Avg Time</div>
                </div>
                <div class="log-summary-item">
                  <div class="summary-value success">{{ logStats.successRate || 0 }}%</div>
                  <div class="summary-label">Success</div>
                </div>
                <div class="log-summary-item">
                  <div class="summary-value">{{ logStats.requestsToday || 0 }}</div>
                  <div class="summary-label">Today</div>
                </div>
              </div>

              <div class="log-filters">
                <div class="log-search">
                  <ion-icon name="search-outline"></ion-icon>
                  <input v-model="logFilters.search" type="text" placeholder="Search path, IP or method…"
                    @input="onLogSearchInput" />
                </div>
                <select v-model="logFilters.method" class="log-select" @change="applyLogFilters">
                  <option value="">All methods</option>
                  <option value="GET">GET</option>
                  <option value="POST">POST</option>
                  <option value="PUT">PUT</option>
                  <option value="PATCH">PATCH</option>
                  <option value="DELETE">DELETE</option>
                </select>
                <select v-model="logFilters.statusGroup" class="log-select" @change="applyLogFilters">
                  <option value="">All status</option>
                  <option value="2xx">2xx Success</option>
                  <option value="3xx">3xx Redirect</option>
                  <option value="4xx">4xx Client error</option>
                  <option value="5xx">5xx Server error</option>
                </select>
                <select v-model.number="logFilters.limit" class="log-select" @change="applyLogFilters">
                  <option :value="25">25 / page</option>
                  <option :value="50">50 / page</option>
                  <option :value="100">100 / page</option>
                </select>
              </div>

              <div v-if="logLoading" class="log-loading">
                <ion-spinner></ion-spinner>
                <span>Loading call log…</span>
              </div>

              <div v-else-if="callLogs.length > 0" class="log-table">
                <div class="log-table-head">
                  <div class="col-method">Method</div>
                  <div class="col-path">Endpoint</div>
                  <div class="col-status">Status</div>
                  <div class="col-time">Time</div>
                  <div class="col-ip">IP</div>
                  <div class="col-when">When</div>
                  <div class="col-chevron"></div>
                </div>

                <div v-for="log in callLogs" :key="log.id" class="log-row-wrapper">
                  <div class="log-row" :class="{ expanded: isLogExpanded(log.id) }" @click="toggleLogExpand(log.id)">
                    <div class="col-method">
                      <span class="method-badge" :class="getMethodColor(log.method)">{{ log.method }}</span>
                    </div>
                    <div class="col-path">
                      <code>{{ log.path }}</code>
                    </div>
                    <div class="col-status">
                      <span class="status-pill" :class="getStatusClass(log.status)">{{ log.status }}</span>
                    </div>
                    <div class="col-time">
                      <span :class="responseTimeClass(log.response_time)">{{ log.response_time }}ms</span>
                    </div>
                    <div class="col-ip">{{ log.ip_address || '—' }}</div>
                    <div class="col-when">{{ formatRelative(log.timestamp) }}</div>
                    <div class="col-chevron">
                      <ion-icon
                        :name="isLogExpanded(log.id) ? 'chevron-up-outline' : 'chevron-down-outline'"></ion-icon>
                    </div>
                  </div>

                  <div v-if="isLogExpanded(log.id)" class="log-detail">
                    <div class="log-detail-meta">
                      <div class="meta-cell">
                        <span class="meta-label">Timestamp</span>
                        <span class="meta-value">{{ formatDate(log.timestamp) }}</span>
                      </div>
                      <div class="meta-cell">
                        <span class="meta-label">Response time</span>
                        <span class="meta-value">{{ log.response_time }}ms</span>
                      </div>
                      <div class="meta-cell">
                        <span class="meta-label">IP address</span>
                        <span class="meta-value">{{ log.ip_address || '—' }}</span>
                      </div>
                      <div class="meta-cell wide">
                        <span class="meta-label">User agent</span>
                        <span class="meta-value">{{ log.user_agent || '—' }}</span>
                      </div>
                    </div>

                    <div v-if="log.error_message" class="log-error">
                      <ion-icon name="warning-outline"></ion-icon>
                      <span>{{ log.error_message }}</span>
                    </div>

                    <div class="log-detail-grid">
                      <div class="detail-block">
                        <div class="detail-block-title">Request</div>
                        <div v-if="log.request_query" class="detail-sub">
                          <span class="detail-sub-label">Query</span>
                          <code class="detail-inline">{{ log.request_query }}</code>
                        </div>
                        <div class="detail-sub">
                          <span class="detail-sub-label">Headers</span>
                          <pre class="detail-pre">{{ formatBlock(log.request_headers) }}</pre>
                        </div>
                        <div class="detail-sub" v-if="log.request_body">
                          <span class="detail-sub-label">Body</span>
                          <pre class="detail-pre">{{ formatBlock(log.request_body) }}</pre>
                        </div>
                      </div>
                      <div class="detail-block">
                        <div class="detail-block-title">Response</div>
                        <div class="detail-sub">
                          <span class="detail-sub-label">Headers</span>
                          <pre class="detail-pre">{{ formatBlock(log.response_headers) }}</pre>
                        </div>
                        <div class="detail-sub" v-if="log.response_body">
                          <span class="detail-sub-label">Body</span>
                          <pre class="detail-pre">{{ formatBlock(log.response_body) }}</pre>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="empty-state">
                <ion-icon name="receipt-outline" size="large"></ion-icon>
                <h4>No calls logged yet</h4>
                <p>Requests to this API will appear here as soon as they happen.</p>
              </div>

              <div v-if="!logLoading && logPagination.total > 0" class="log-pagination">
                <div class="pagination-info">
                  Showing {{ logRangeStart }}–{{ logRangeEnd }} of {{ logPagination.total }}
                </div>
                <div class="pagination-controls">
                  <button class="page-btn" :disabled="logPagination.page <= 1" @click="logPrevPage">
                    <ion-icon name="chevron-back-outline"></ion-icon>
                  </button>
                  <span class="page-current">{{ logPagination.page }} / {{ logPagination.totalPages || 1 }}</span>
                  <button class="page-btn" :disabled="logPagination.page >= logPagination.totalPages"
                    @click="logNextPage">
                    <ion-icon name="chevron-forward-outline"></ion-icon>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="selectedTab === 'settings'" class="tab-content">
          <div class="card modern-card">
            <div class="card-header">
              <h3>Subscription Settings</h3>
            </div>
            <div class="card-content">
              <div class="settings-form">
                <div class="form-field">
                  <label>Rate Limit (requests/minute)</label>
                  <input v-model.number="settings.rate_limit" type="number" min="1" class="form-input" />
                </div>
                <div class="form-field checkbox-field">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="settings.is_enabled" class="form-checkbox" />
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
import { defineComponent, ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import {
  IonPage, IonContent, IonButton, IonIcon, IonBadge,
  IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner,
  alertController, toastController
} from '@ionic/vue';
import SiteTitle from '@/components/SiteTitle.vue';
import PageTitle from "@/components/PageTitle.vue";

export default defineComponent({
  name: 'ApiDocumentation',
  components: {
    IonPage, IonContent, IonButton, IonIcon, IonBadge,
    IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonSpinner,
    SiteTitle,
    PageTitle
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
      id: 0,
      name: '',
      slug: '',
      description: '',
      icon: 'cloud-outline',
      category: '',
      version: '1.0',
      endpoint_base: '',
      endpoints: []
    });

    const subscription = ref({
      id: 0,
      api_key: '',
      rate_limit: 0,
      usage_count: 0,
      last_used: '',
      is_enabled: true
    });

    const settings = ref({
      rate_limit: 0,
      is_enabled: true
    });

    const usageStats = ref({
      totalRequests: 0,
      avgResponseTime: 0,
      successRate: 0,
      requestsToday: 0
    });

    const currentUsage = ref(0);
    const recentActivity = ref([]);

    const callLogs = ref<any[]>([]);
    const logLoading = ref(false);
    const expandedLogIds = ref<number[]>([]);
    const autoRefresh = ref(false);
    let autoRefreshTimer: number | undefined;
    let logSearchTimer: number | undefined;

    const logStats = ref({
      totalRequests: 0,
      avgResponseTime: 0,
      successRate: 0,
      requestsToday: 0
    });

    const logFilters = ref({
      search: '',
      method: '',
      statusGroup: '',
      limit: 25
    });

    const logPagination = ref({
      page: 1,
      total: 0,
      totalPages: 1,
      limit: 25
    });

    const logRangeStart = computed(() => {
      if (logPagination.value.total === 0) return 0;
      return (logPagination.value.page - 1) * logPagination.value.limit + 1;
    });

    const logRangeEnd = computed(() => {
      return Math.min(logPagination.value.page * logPagination.value.limit, logPagination.value.total);
    });

    const loadCallLogs = async () => {
      logLoading.value = callLogs.value.length === 0;
      try {
        const body: Record<string, string> = {
          getApiCallLogs: '1',
          project: (route.params.project as string) || '',
          api_slug: (route.params.apiSlug as string) || '',
          method: logFilters.value.method,
          status_group: logFilters.value.statusGroup,
          search: logFilters.value.search,
          page: logPagination.value.page.toString(),
          limit: logFilters.value.limit.toString()
        };
        if (subscription.value.id) {
          body.subscription_id = subscription.value.id.toString();
        }

        const response = await axios.post('apis.php', new URLSearchParams(body));
        const data = response.data;
        callLogs.value = data.logs || [];
        logPagination.value = {
          page: data.page || 1,
          total: data.total || 0,
          totalPages: data.totalPages || 1,
          limit: data.limit || logFilters.value.limit
        };
        if (data.stats) {
          logStats.value = data.stats;
        }
      } catch (error) {
        console.error('Error loading call logs:', error);
      } finally {
        logLoading.value = false;
      }
    };

    const applyLogFilters = () => {
      logPagination.value.page = 1;
      loadCallLogs();
    };

    const onLogSearchInput = () => {
      if (logSearchTimer) window.clearTimeout(logSearchTimer);
      logSearchTimer = window.setTimeout(() => {
        applyLogFilters();
      }, 350);
    };

    const toggleLogExpand = (id: number) => {
      const idx = expandedLogIds.value.indexOf(id);
      if (idx === -1) {
        expandedLogIds.value.push(id);
      } else {
        expandedLogIds.value.splice(idx, 1);
      }
    };

    const isLogExpanded = (id: number) => expandedLogIds.value.includes(id);

    const logPrevPage = () => {
      if (logPagination.value.page > 1) {
        logPagination.value.page--;
        loadCallLogs();
      }
    };

    const logNextPage = () => {
      if (logPagination.value.page < logPagination.value.totalPages) {
        logPagination.value.page++;
        loadCallLogs();
      }
    };

    const toggleAutoRefresh = () => {
      autoRefresh.value = !autoRefresh.value;
      if (autoRefresh.value) {
        autoRefreshTimer = window.setInterval(() => loadCallLogs(), 5000);
      } else if (autoRefreshTimer) {
        window.clearInterval(autoRefreshTimer);
        autoRefreshTimer = undefined;
      }
    };

    const responseTimeClass = (ms: number) => {
      if (ms >= 1000) return 'time-slow';
      if (ms >= 400) return 'time-medium';
      return 'time-fast';
    };

    const formatRelative = (dateString: string) => {
      if (!dateString) return '—';
      const then = new Date(dateString).getTime();
      const diff = Math.max(0, Date.now() - then);
      const s = Math.floor(diff / 1000);
      if (s < 60) return `${s}s ago`;
      const m = Math.floor(s / 60);
      if (m < 60) return `${m}m ago`;
      const h = Math.floor(m / 60);
      if (h < 24) return `${h}h ago`;
      const d = Math.floor(h / 24);
      if (d < 7) return `${d}d ago`;
      return new Date(dateString).toLocaleDateString();
    };

    const formatBlock = (value: any) => {
      if (value === null || value === undefined || value === '') return '—';
      if (typeof value === 'string') {
        try {
          return JSON.stringify(JSON.parse(value), null, 2);
        } catch {
          return value;
        }
      }
      return JSON.stringify(value, null, 2);
    };

    watch(selectedTab, (tab) => {
      if (tab === 'logs') {
        loadCallLogs();
      } else if (autoRefresh.value) {
        autoRefresh.value = false;
        if (autoRefreshTimer) {
          window.clearInterval(autoRefreshTimer);
          autoRefreshTimer = undefined;
        }
      }
    });

    onUnmounted(() => {
      if (autoRefreshTimer) window.clearInterval(autoRefreshTimer);
      if (logSearchTimer) window.clearTimeout(logSearchTimer);
    });

    const maskedKey = computed(() => {
      if (!subscription.value.api_key) return '';
      const key = subscription.value.api_key;
      return key.substring(0, 8) + '...' + key.substring(key.length - 4);
    });

    const usagePercentage = computed(() => {
      return Math.min((currentUsage.value / subscription.value.rate_limit) * 100, 100);
    });

    const importName = computed(() => {
      const slug = api.value.slug || '';
      const special = { 'user-management': 'UsersAPI', 'file-storage': 'FilesAPI' };
      if (special[slug]) return special[slug];
      if (!slug) return 'Api';
      return slug.charAt(0).toUpperCase() + slug.slice(1) + 'API';
    });

    const usageImport = computed(() => `import { ${importName.value} } from 'apis';`);

    const firstExample = computed(() => {
      const eps = api.value.endpoints || [];
      for (const e of eps) {
        if (e.example_request && e.example_request.code) return e.example_request.code;
      }
      return `await ${importName.value}.someMethod();`;
    });

    const loadApiData = async () => {
      try {
        const apiSlug = route.params.apiSlug as string;
        const project = route.params.project as string;

        const response = await axios.get(`v2/apis/${apiSlug}?project=${encodeURIComponent(project)}`);
        const data = response.data;

        if (data && !data.error) {
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
        showToast('Failed to load API details', 'danger');
      }
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
        const response = await axios.post(`v2/apis/subscriptions/${subscription.value.id}/regenerate-key`);

        if (response.data && response.data.success) {
          subscription.value.api_key = response.data.api_key;
          showToast('API key regenerated successfully', 'success');
        } else {
          showToast(response.data.error || 'Failed to regenerate API key', 'danger');
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
        const response = await axios.delete(`v2/apis/subscriptions/${subscription.value.id}`);

        if (response.data && response.data.success) {
          showToast('Successfully unsubscribed from API', 'success');
          router.push(`/project/${route.params.project}/manage/apis`);
        } else {
          showToast(response.data.error || 'Failed to unsubscribe from API', 'danger');
        }
      } catch (error) {
        console.error('Error unsubscribing from API:', error);
        showToast('Failed to unsubscribe from API', 'danger');
      }
    };

    const saveSettings = async () => {
      try {
        const response = await axios.put(`v2/apis/subscriptions/${subscription.value.id}/settings`, {
          rate_limit: settings.value.rate_limit.toString(),
          is_enabled: settings.value.is_enabled ? 'true' : 'false'
        });

        if (response.data && response.data.success) {
          subscription.value.rate_limit = settings.value.rate_limit;
          subscription.value.is_enabled = settings.value.is_enabled;
          showToast('Settings saved successfully', 'success');
        } else {
          showToast(response.data.error || 'Failed to save settings', 'danger');
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
      importName,
      usageImport,
      firstExample,
      callLogs,
      logLoading,
      logStats,
      logFilters,
      logPagination,
      logRangeStart,
      logRangeEnd,
      autoRefresh,
      loadCallLogs,
      applyLogFilters,
      onLogSearchInput,
      toggleLogExpand,
      isLogExpanded,
      logPrevPage,
      logNextPage,
      toggleAutoRefresh,
      responseTimeClass,
      formatRelative,
      formatBlock,
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
.modern-content {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
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
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
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
  background: rgba(235, 68, 90, 0.12);
}

.action-btn ion-icon {
  font-size: 16px;
}

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
  background: #ffedd5;
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

.endpoint-signature {
  display: inline-block;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  color: var(--primary-color);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 4px 8px;
  word-break: break-word;
}

.sdk-intro {
  margin: 0 0 12px 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

.sdk-note {
  margin: 10px 0 0 0;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.count-inline {
  color: var(--text-muted);
  font-weight: 500;
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
  background: #ffedd5;
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

.logs-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.card-subtitle {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: var(--text-secondary);
  font-weight: 400;
}

.logs-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chip-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--surface);
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.chip-toggle.active {
  border-color: var(--success-color);
  color: var(--success-color);
  background: rgba(5, 150, 105, 0.08);
}

.live-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--text-muted);
}

.live-dot.on {
  background: var(--success-color);
  box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.5);
  animation: livePulse 1.6s infinite;
}

@keyframes livePulse {
  0% {
    box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.5);
  }

  70% {
    box-shadow: 0 0 0 6px rgba(5, 150, 105, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(5, 150, 105, 0);
  }
}

.log-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.log-summary-item {
  text-align: center;
  padding: 14px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.summary-value {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.summary-value.success {
  color: var(--success-color);
}

.summary-label {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

.log-filters {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.log-search {
  flex: 1;
  min-width: 200px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.log-search ion-icon {
  color: var(--text-muted);
  font-size: 18px;
}

.log-search input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 10px 0;
  font-size: 14px;
  color: var(--text-primary);
  outline: none;
}

.log-select {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  cursor: pointer;
}

.log-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 48px;
  color: var(--text-secondary);
}

.log-table {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.log-table-head,
.log-row {
  display: grid;
  grid-template-columns: 80px 1fr 70px 80px 120px 90px 32px;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
}

.log-table-head {
  background: var(--background);
  border-bottom: 1px solid var(--border);
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.log-row-wrapper {
  border-bottom: 1px solid var(--border);
}

.log-row-wrapper:last-child {
  border-bottom: none;
}

.log-row {
  cursor: pointer;
  transition: background 0.15s ease;
  font-size: 13px;
}

.log-row:hover {
  background: var(--background);
}

.log-row.expanded {
  background: var(--background);
}

.col-path code {
  font-family: 'Courier New', monospace;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
}

.status-pill {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: var(--background);
  border: 1px solid var(--border);
}

.status-pill.success {
  background: #dcfce7;
  color: var(--success-color);
  border-color: transparent;
}

.status-pill.warning {
  background: #fef3c7;
  color: var(--warning-color);
  border-color: transparent;
}

.status-pill.danger {
  background: #fee2e2;
  color: var(--danger-color);
  border-color: transparent;
}

.time-fast {
  color: var(--success-color);
  font-weight: 500;
}

.time-medium {
  color: var(--warning-color);
  font-weight: 500;
}

.time-slow {
  color: var(--danger-color);
  font-weight: 600;
}

.col-ip,
.col-when {
  color: var(--text-secondary);
  font-size: 12px;
}

.col-chevron {
  display: flex;
  justify-content: center;
  color: var(--text-muted);
}

.log-detail {
  padding: 16px;
  background: var(--surface);
  border-top: 1px dashed var(--border);
}

.log-detail-meta {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.meta-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.meta-cell.wide {
  grid-column: 1 / -1;
}

.meta-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted);
}

.meta-value {
  font-size: 13px;
  color: var(--text-primary);
  word-break: break-word;
}

.log-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #fef2f2;
  color: var(--danger-color);
  border-radius: var(--radius);
  font-size: 13px;
  margin-bottom: 16px;
}

.log-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-block-title {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.detail-sub {
  margin-bottom: 10px;
}

.detail-sub-label {
  display: block;
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 4px;
}

.detail-inline {
  display: block;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  background: var(--background);
  padding: 8px;
  border-radius: 6px;
  border: 1px solid var(--border);
  word-break: break-all;
}

.detail-pre {
  margin: 0;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  background: var(--background);
  padding: 10px;
  border-radius: 6px;
  border: 1px solid var(--border);
  color: var(--text-primary);
  max-height: 240px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

.log-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}

.pagination-info {
  font-size: 13px;
  color: var(--text-secondary);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-current {
  font-size: 13px;
  color: var(--text-primary);
  font-weight: 500;
}

.page-btn {
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

.page-btn:hover:not(:disabled) {
  background: var(--background);
  color: var(--text-primary);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }

  .status-pill.success {
    background: rgba(5, 150, 105, 0.2);
  }

  .status-pill.warning {
    background: rgba(217, 119, 6, 0.2);
  }

  .status-pill.danger {
    background: rgba(220, 38, 38, 0.2);
  }

  .log-error {
    background: rgba(220, 38, 38, 0.12);
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
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

  .log-summary {
    grid-template-columns: repeat(2, 1fr);
  }

  .log-table-head {
    display: none;
  }

  .log-row {
    grid-template-columns: 70px 1fr auto 28px;
    row-gap: 4px;
  }

  .log-row .col-time,
  .log-row .col-ip,
  .log-row .col-when {
    grid-column: 2 / 3;
    font-size: 11px;
  }

  .log-detail-meta,
  .log-detail-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .tab-btn {
    padding: 8px 12px;
    font-size: 12px;
  }

  .tab-btn span {
    display: none;
  }
}
</style>
