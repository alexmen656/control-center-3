<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <div class="header-info">
              <h1 class="page-title">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
                Access Log
              </h1>
              <p class="page-subtitle">Monitor login attempts, security events, and access patterns</p>
            </div>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="refreshData">
              <ion-icon name="refresh-outline"></ion-icon>
              Refresh
            </button>
            <button class="action-btn secondary" @click="exportLogs">
              <ion-icon name="download-outline"></ion-icon>
              Export
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
          <div class="filters-grid">
            <div class="filter-group">
              <label class="filter-label">Status</label>
              <select v-model="filters.status" @change="loadLogs" class="modern-select">
                <option value="all">All Status</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">From Date</label>
              <input type="date" v-model="filters.dateFrom" @change="onDateFilterChange" class="modern-input" />
            </div>
            <div class="filter-group">
              <label class="filter-label">To Date</label>
              <input type="date" v-model="filters.dateTo" @change="onDateFilterChange" class="modern-input" />
            </div>
            <div class="filter-group">
              <label class="filter-label">Search</label>
              <div class="search-box">
                <ion-icon name="search-outline"></ion-icon>
                <input 
                  type="text" 
                  v-model="filters.search" 
                  @input="debounceSearch" 
                  placeholder="Email, IP, User Agent..." 
                  class="modern-input"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card primary">
            <div class="stat-icon">
              <ion-icon name="pulse-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total.toLocaleString() }}</h3>
              <p>Total Attempts</p>
            </div>
          </div>
          
          <div class="stat-card success">
            <div class="stat-icon">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.success.toLocaleString() }}</h3>
              <p>Successful Logins</p>
            </div>
          </div>
          
          <div class="stat-card danger">
            <div class="stat-icon">
              <ion-icon name="close-circle-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.failed.toLocaleString() }}</h3>
              <p>Failed Attempts</p>
            </div>
          </div>
          
          <div class="stat-card info">
            <div class="stat-icon">
              <ion-icon name="people-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.unique_users.toLocaleString() }}</h3>
              <p>Unique Users</p>
            </div>
          </div>
          
          <div class="stat-card warning">
            <div class="stat-icon">
              <ion-icon name="globe-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.unique_ips.toLocaleString() }}</h3>
              <p>Unique IPs</p>
            </div>
          </div>
          
          <div class="stat-card accent">
            <div class="stat-icon">
              <ion-icon name="trending-up-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.success_rate }}%</h3>
              <p>Success Rate</p>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
          <!-- Login Attempts Chart -->
          <div class="data-card chart-card">
            <div class="card-header">
              <div class="header-left">
                <h3>Login Attempts Over Time</h3>
                <span class="chart-description">Last {{ chartDays }} days activity</span>
              </div>
              <div class="header-right">
                <select v-model="chartDays" @change="loadChartData" class="chart-select">
                  <option :value="7">7 Days</option>
                  <option :value="14">14 Days</option>
                  <option :value="30">30 Days</option>
                  <option :value="90">90 Days</option>
                </select>
              </div>
            </div>
            <div class="card-content">
              <canvas ref="loginChart" height="80"></canvas>
            </div>
          </div>

          <!-- Top Failed Attempts -->
          <div class="data-card">
            <div class="card-header">
              <div class="header-left">
                <h3>Top Failed Login Attempts</h3>
                <span class="chart-description">Most frequent failed attempts</span>
              </div>
            </div>
            <div class="card-content">
              <div v-if="topFailedAttempts.length === 0" class="no-data-state">
                <ion-icon name="checkmark-circle-outline" class="no-data-icon"></ion-icon>
                <p>No failed attempts</p>
              </div>
              <div v-else class="failed-attempts-list">
                <div 
                  v-for="(attempt, index) in topFailedAttempts" 
                  :key="index" 
                  class="failed-attempt-item"
                >
                  <div class="attempt-rank">{{ index + 1 }}</div>
                  <div class="attempt-info">
                    <div class="attempt-email">{{ attempt.email }}</div>
                    <div class="attempt-time">Last: {{ formatDate(attempt.last_attempt) }}</div>
                  </div>
                  <div class="attempt-count">
                    <span class="count-badge">{{ attempt.attempt_count }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top IPs Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Top IP Addresses</h3>
              <span class="chart-description">Most active IP addresses</span>
            </div>
          </div>
          <div class="card-content">
            <div v-if="topIPs.length === 0" class="no-data-state">
              <ion-icon name="globe-outline" class="no-data-icon"></ion-icon>
              <p>No IP data available</p>
            </div>
            <div v-else class="ip-grid">
              <div v-for="(ip, index) in topIPs" :key="index" class="ip-card">
                <div class="ip-header">
                  <div class="ip-rank">#{{ index + 1 }}</div>
                  <div class="ip-address">{{ ip.ip_address }}</div>
                </div>
                <div class="ip-stats">
                  <div class="ip-stat">
                    <span class="stat-label">Total</span>
                    <span class="stat-value">{{ ip.attempt_count }}</span>
                  </div>
                  <div class="ip-stat success">
                    <span class="stat-label">Success</span>
                    <span class="stat-value">{{ ip.success_count }}</span>
                  </div>
                  <div class="ip-stat danger">
                    <span class="stat-label">Failed</span>
                    <span class="stat-value">{{ ip.failed_count }}</span>
                  </div>
                </div>
                <div class="ip-footer">
                  Last seen: {{ formatDate(ip.last_seen) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Logs Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Access Logs</h3>
              <span class="entry-count">{{ pagination.total_records.toLocaleString() }} entries found</span>
            </div>
            <div class="header-right">
              <div class="pagination-info">
                Page {{ pagination.current_page }} of {{ pagination.total_pages }}
              </div>
            </div>
          </div>
          
          <div class="table-wrapper">
            <div v-if="loading" class="loading-state">
              <ion-icon name="hourglass-outline" class="loading-icon"></ion-icon>
              <p>Loading access logs...</p>
            </div>

            <div v-else-if="logs.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="document-outline" class="no-data-icon"></ion-icon>
                <h4>No logs found</h4>
                <p>No access logs match your current filters.</p>
              </div>
            </div>

            <div v-else class="modern-table">
              <div class="table-header">
                <div class="header-cell" style="flex: 0.6;">
                  <span class="header-text">ID</span>
                </div>
                <div class="header-cell" style="flex: 1.5;">
                  <span class="header-text">Email</span>
                </div>
                <div class="header-cell" style="flex: 1;">
                  <span class="header-text">Status</span>
                </div>
                <div class="header-cell" style="flex: 1.2;">
                  <span class="header-text">IP Address</span>
                </div>
                <div class="header-cell" style="flex: 1.5;">
                  <span class="header-text">Timestamp</span>
                </div>
                <div class="header-cell" style="flex: 0.8;">
                  <span class="header-text">Actions</span>
                </div>
              </div>

              <div class="table-body">
                <div v-for="log in logs" :key="log.id" class="table-row">
                  <div class="table-cell" style="flex: 0.6;">
                    <span class="cell-content">#{{ log.id }}</span>
                  </div>
                  
                  <div class="table-cell" style="flex: 1.5;">
                    <div class="email-cell">
                      <ion-icon name="mail-outline"></ion-icon>
                      <span class="cell-content">{{ log.email }}</span>
                    </div>
                  </div>
                  
                  <div class="table-cell" style="flex: 1;">
                    <span 
                      class="status-badge" 
                      :class="log.status === 'success' ? 'status-success' : 'status-failed'"
                    >
                      {{ log.status }}
                    </span>
                  </div>
                  
                  <div class="table-cell" style="flex: 1.2;">
                    <div class="ip-cell">
                      <ion-icon name="globe-outline"></ion-icon>
                      <span class="cell-content">{{ log.ip_address }}</span>
                    </div>
                  </div>
                  
                  <div class="table-cell" style="flex: 1.5;">
                    <div class="timestamp-cell">
                      <ion-icon name="time-outline"></ion-icon>
                      <span class="cell-content">{{ formatDate(log.timestamp) }}</span>
                    </div>
                  </div>
                  
                  <div class="table-cell" style="flex: 0.8;">
                    <button class="icon-btn view-btn" @click="viewDetails(log)" title="View Details">
                      <ion-icon name="eye-outline"></ion-icon>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total_pages > 1" class="pagination-controls">
            <button class="pagination-btn" @click="prevPage" :disabled="pagination.current_page <= 1">
              <ion-icon name="chevron-back-outline"></ion-icon>
              Previous
            </button>
            <div class="page-numbers">
              <span class="current-page">{{ pagination.current_page }}</span>
              <span class="page-separator">of</span>
              <span class="total-pages">{{ pagination.total_pages }}</span>
            </div>
            <button class="pagination-btn" @click="nextPage" :disabled="pagination.current_page >= pagination.total_pages">
              Next
              <ion-icon name="chevron-forward-outline"></ion-icon>
            </button>
          </div>
        </div>
      </div>

      <!-- Details Modal -->
      <div v-if="showDetailsModal" class="custom-modal-overlay" @click="showDetailsModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Access Log Details</h3>
            <button class="modal-close-btn" @click="showDetailsModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="detail-grid">
              <div class="detail-item">
                <label>Log ID</label>
                <span>#{{ selectedLog.id }}</span>
              </div>
              <div class="detail-item">
                <label>Email</label>
                <span>{{ selectedLog.email }}</span>
              </div>
              <div class="detail-item">
                <label>Status</label>
                <span 
                  class="status-badge" 
                  :class="selectedLog.status === 'success' ? 'status-success' : 'status-failed'"
                >
                  {{ selectedLog.status }}
                </span>
              </div>
              <div class="detail-item">
                <label>IP Address</label>
                <span>{{ selectedLog.ip_address }}</span>
              </div>
              <div class="detail-item">
                <label>Timestamp</label>
                <span>{{ formatDate(selectedLog.timestamp) }}</span>
              </div>
              <div class="detail-item full-width">
                <label>User Agent</label>
                <span class="user-agent">{{ selectedLog.user_agent }}</span>
              </div>
              <div v-if="selectedLog.error_message" class="detail-item full-width">
                <label>Error Message</label>
                <span class="error-message">{{ selectedLog.error_message }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default defineComponent({
  name: 'AccessLog',
  data() {
    return {
      loading: false,
      logs: [],
      stats: {
        total: 0,
        success: 0,
        failed: 0,
        unique_users: 0,
        unique_ips: 0,
        success_rate: 0
      },
      pagination: {
        current_page: 1,
        total_pages: 1,
        total_records: 0,
        limit: 50
      },
      filters: {
        status: 'all',
        search: '',
        dateFrom: this.getDefaultDateFrom(),
        dateTo: this.getDefaultDateTo()
      },
      chartDays: 30,
      chartData: [],
      topFailedAttempts: [],
      topIPs: [],
      loginChart: null,
      showDetailsModal: false,
      selectedLog: {},
      searchTimeout: null
    };
  },
  mounted() {
    this.loadStats();
    this.loadLogs();
    this.loadChartData();
    this.loadTopFailedAttempts();
    this.loadTopIPs();
  },
  beforeUnmount() {
    if (this.loginChart) {
      this.loginChart.destroy();
    }
  },
  methods: {
    getDefaultDateFrom() {
      const date = new Date();
      date.setDate(date.getDate() - 30);
      return date.toISOString().split('T')[0];
    },
    getDefaultDateTo() {
      return new Date().toISOString().split('T')[0];
    },
    async loadStats() {
      try {
        const response = await this.$axios.get('v2/access-logs/stats', {
          params: {
            dateFrom: this.filters.dateFrom,
            dateTo: this.filters.dateTo
          }
        });
        
        if (response.data.status === 'success') {
          this.stats = response.data.stats;
        }
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },
    async loadLogs() {
      this.loading = true;
      try {
        const response = await this.$axios.get('v2/access-logs', {
          params: {
            page: this.pagination.current_page,
            limit: this.pagination.limit,
            status: this.filters.status,
            search: this.filters.search,
            dateFrom: this.filters.dateFrom,
            dateTo: this.filters.dateTo
          }
        });
        
        if (response.data.status === 'success') {
          this.logs = response.data.data;
          this.pagination = response.data.pagination;
        }
      } catch (error) {
        console.error('Error loading logs:', error);
      } finally {
        this.loading = false;
      }
    },
    async loadChartData() {
      try {
        const response = await this.$axios.get('v2/access-logs/chart', {
          params: {
            days: this.chartDays,
            dateFrom: this.filters.dateFrom,
            dateTo: this.filters.dateTo
          }
        });
        
        if (response.data.status === 'success') {
          this.chartData = response.data.data;
          this.renderChart();
        }
      } catch (error) {
        console.error('Error loading chart data:', error);
      }
    },
    async loadTopFailedAttempts() {
      try {
        const response = await this.$axios.get('v2/access-logs/top-failed', {
          params: {
            limit: 5,
            dateFrom: this.filters.dateFrom,
            dateTo: this.filters.dateTo
          }
        });
        
        if (response.data.status === 'success') {
          this.topFailedAttempts = response.data.data;
        }
      } catch (error) {
        console.error('Error loading top failed attempts:', error);
      }
    },
    async loadTopIPs() {
      try {
        const response = await this.$axios.get('v2/access-logs/top-ips', {
          params: {
            limit: 6,
            dateFrom: this.filters.dateFrom,
            dateTo: this.filters.dateTo
          }
        });
        
        if (response.data.status === 'success') {
          this.topIPs = response.data.data;
        }
      } catch (error) {
        console.error('Error loading top IPs:', error);
      }
    },
    renderChart() {
      if (this.loginChart) {
        this.loginChart.destroy();
      }

      const ctx = this.$refs.loginChart.getContext('2d');
      
      const labels = this.chartData.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('de-DE', { month: 'short', day: 'numeric' });
      });
      
      const successData = this.chartData.map(d => d.success);
      const failedData = this.chartData.map(d => d.failed);

      this.loginChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Success',
              data: successData,
              borderColor: '#059669',
              backgroundColor: 'rgba(5, 150, 105, 0.1)',
              tension: 0.4,
              fill: true
            },
            {
              label: 'Failed',
              data: failedData,
              borderColor: '#dc2626',
              backgroundColor: 'rgba(220, 38, 38, 0.1)',
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              mode: 'index',
              intersect: false,
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });
    },
    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.pagination.current_page = 1;
        this.loadLogs();
      }, 500);
    },
    onDateFilterChange() {
      // Reload all data when date filters change
      this.loadStats();
      this.loadLogs();
      this.loadChartData();
      this.loadTopFailedAttempts();
      this.loadTopIPs();
    },
    refreshData() {
      this.loadStats();
      this.loadLogs();
      this.loadChartData();
      this.loadTopFailedAttempts();
      this.loadTopIPs();
    },
    exportLogs() {
      // Create CSV content
      const headers = ['ID', 'Email', 'Status', 'IP Address', 'Timestamp', 'User Agent', 'Error Message'];
      const rows = this.logs.map(log => [
        log.id,
        log.email,
        log.status,
        log.ip_address,
        log.timestamp,
        log.user_agent || '',
        log.error_message || ''
      ]);
      
      let csvContent = headers.join(',') + '\n';
      rows.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
      });
      
      // Download
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `access_logs_${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    viewDetails(log) {
      this.selectedLog = log;
      this.showDetailsModal = true;
    },
    formatDate(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      return date.toLocaleString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    },
    nextPage() {
      if (this.pagination.current_page < this.pagination.total_pages) {
        this.pagination.current_page++;
        this.loadLogs();
      }
    },
    prevPage() {
      if (this.pagination.current_page > 1) {
        this.pagination.current_page--;
        this.loadLogs();
      }
    }
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
  --info-color: #0891b2;
  --accent-color: #7c3aed;
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
  background: var(--background);
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content {
  flex: 1;
  min-width: 300px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.025em;
}

.page-title ion-icon {
  font-size: 36px;
  color: var(--primary-color);
}

.page-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
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
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.secondary {
  background: var(--surface);
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Filters Card */
.filters-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: var(--shadow);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modern-input,
.modern-select,
.chart-select {
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box ion-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 16px;
  z-index: 1;
}

.search-box input {
  padding-left: 40px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  flex-shrink: 0;
}

.stat-card.primary .stat-icon {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.stat-card.success .stat-icon {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.stat-card.danger .stat-icon {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.stat-card.info .stat-icon {
  background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
}

.stat-card.warning .stat-icon {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
}

.stat-card.accent .stat-icon {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Charts Row */
.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(135deg, var(--background), var(--surface));
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.chart-description,
.entry-count {
  color: var(--text-secondary);
  font-size: 13px;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.card-content {
  padding: 24px;
}

/* Chart Styles */
.chart-card canvas {
  max-height: 300px;
}

/* Failed Attempts List */
.failed-attempts-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.failed-attempt-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.failed-attempt-item:hover {
  background: var(--surface);
  border-color: var(--danger-color);
  transform: translateX(4px);
}

.attempt-rank {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--danger-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
}

.attempt-info {
  flex: 1;
  min-width: 0;
}

.attempt-email {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attempt-time {
  font-size: 12px;
  color: var(--text-secondary);
}

.attempt-count {
  flex-shrink: 0;
}

.count-badge {
  display: inline-block;
  padding: 6px 12px;
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border-radius: 20px;
  font-size: 14px;
  font-weight: 700;
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* IP Grid */
.ip-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.ip-card {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  transition: all 0.2s ease;
}

.ip-card:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.ip-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}

.ip-rank {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 12px;
}

.ip-address {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
  font-family: 'Monaco', 'Menlo', monospace;
}

.ip-stats {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.ip-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.ip-stat .stat-label {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ip-stat .stat-value {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
}

.ip-stat.success .stat-value {
  color: var(--success-color);
}

.ip-stat.danger .stat-value {
  color: var(--danger-color);
}

.ip-footer {
  font-size: 12px;
  color: var(--text-secondary);
  text-align: center;
}

/* Table Styles */
.table-wrapper {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 800px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.email-cell,
.ip-cell,
.timestamp-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.email-cell ion-icon,
.ip-cell ion-icon,
.timestamp-cell ion-icon {
  color: var(--text-muted);
  font-size: 16px;
  flex-shrink: 0;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-failed {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* Action Buttons */
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 16px;
}

.view-btn {
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
}

.view-btn:hover {
  background: rgba(249, 115, 22, 0.2);
  transform: scale(1.05);
}

/* Loading and No Data States */
.loading-state,
.no-data-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon,
.no-data-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
  opacity: 0.5;
}

.loading-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Pagination */
.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: var(--text-secondary);
}

.current-page {
  font-weight: 600;
  color: var(--primary-color);
}

/* Modal */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 600px;
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-item label {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item span {
  font-size: 14px;
  color: var(--text-primary);
  word-break: break-word;
}

.user-agent {
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 12px;
  background: var(--background);
  padding: 12px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.error-message {
  color: var(--danger-color);
  background: rgba(220, 38, 38, 0.1);
  padding: 12px;
  border-radius: var(--radius);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive */
@media (max-width: 1200px) {
  .charts-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .ip-grid {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modern-table {
    min-width: 600px;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
  }
}
</style>
