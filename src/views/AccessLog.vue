<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="shield-checkmark-outline" title="Access Log" />
      <div class="page-container">
        <PageHeader icon="shield-checkmark-outline" title="Access Log">
          <template #actions>
            <ActionButton variant="secondary" icon="refresh-outline" @click="refreshData">Refresh</ActionButton>
            <ActionButton variant="secondary" icon="download-outline" @click="exportLogs">Export</ActionButton>
          </template>
        </PageHeader>

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
                <input type="text" v-model="filters.search" @input="debounceSearch"
                  placeholder="Email, IP, User Agent..." class="modern-input" />
              </div>
            </div>
          </div>
        </div>

        <div class="stats-grid">
          <StatCard icon="pulse-outline" color="primary" :value="stats.total.toLocaleString()" label="Total Attempts" />
          <StatCard icon="checkmark-circle-outline" color="success" :value="stats.success.toLocaleString()"
            label="Successful Logins" />
          <StatCard icon="close-circle-outline" color="danger" :value="stats.failed.toLocaleString()"
            label="Failed Attempts" />
          <StatCard icon="people-outline" color="info" :value="stats.unique_users.toLocaleString()"
            label="Unique Users" />
          <StatCard icon="globe-outline" color="warning" :value="stats.unique_ips.toLocaleString()"
            label="Unique IPs" />
          <StatCard icon="trending-up-outline" color="accent" :value="stats.success_rate + '%'" label="Success Rate" />
        </div>

        <div class="charts-row">
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

          <DataCard title="Top Failed Login Attempts" subtitle="Most frequent failed attempts">
            <EmptyState v-if="topFailedAttempts.length === 0" icon="checkmark-circle-outline"
              title="No failed attempts" />
            <div v-else class="failed-attempts-list">
              <div v-for="(attempt, index) in topFailedAttempts" :key="index" class="failed-attempt-item">
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
          </DataCard>
        </div>

        <DataCard title="Top IP Addresses" subtitle="Most active IP addresses">
          <EmptyState v-if="topIPs.length === 0" icon="globe-outline" title="No IP data available" />
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
        </DataCard>

        <DataCard title="Access Logs" :subtitle="pagination.total_records.toLocaleString() + ' entries found'"
          noPadding>
          <template #actions>
            <div class="pagination-info">
              Page {{ pagination.current_page }} of {{ pagination.total_pages }}
            </div>
          </template>

          <div class="table-wrapper">
            <LoadingState v-if="loading" message="Loading access logs..." icon="hourglass-outline" />

            <EmptyState v-else-if="logs.length === 0" icon="document-outline" title="No logs found"
              description="No access logs match your current filters." />

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
                    <span class="status-badge" :class="log.status === 'success' ? 'status-success' : 'status-failed'">
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
            <button class="pagination-btn" @click="nextPage"
              :disabled="pagination.current_page >= pagination.total_pages">
              Next
              <ion-icon name="chevron-forward-outline"></ion-icon>
            </button>
          </div>
        </DataCard>
      </div>

      <AppModal v-model="showDetailsModal" title="Access Log Details">
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
            <span class="status-badge" :class="selectedLog.status === 'success' ? 'status-success' : 'status-failed'">
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
      </AppModal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import { Chart, registerables } from 'chart.js';
import SiteTitle from "@/components/SiteTitle.vue";
import StatCard from "@/components/StatCard.vue";
import PageHeader from "@/components/PageHeader.vue";
import ActionButton from "@/components/ActionButton.vue";
import DataCard from "@/components/DataCard.vue";
import EmptyState from "@/components/EmptyState.vue";
import LoadingState from "@/components/LoadingState.vue";
import AppModal from "@/components/AppModal.vue";

Chart.register(...registerables);

export default defineComponent({
  name: 'AccessLog',
  components: { SiteTitle, StatCard, PageHeader, ActionButton, DataCard, EmptyState, LoadingState, AppModal },
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
  background: var(--background);
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
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

.chart-description {
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
</style>
