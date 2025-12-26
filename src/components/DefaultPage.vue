<template>
  <div class="modern-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-content">
        <div class="header-info">
          <h1 class="dashboard-title">
            <ion-icon name="grid-outline"></ion-icon>
            Dashboard
          </h1>
          <p class="dashboard-subtitle">Overview of your system metrics and quick access to key features</p>
        </div>
        <div class="header-stats">
          <div class="stat-card">
            <span class="stat-number">{{ tableData.length }}</span>
            <span class="stat-label">Total Users</span>
          </div>
          <div class="stat-card">
            <span class="stat-number">{{ data.datasets[0].data.reduce((a, b) => a + b, 0) }}</span>
            <span class="stat-label">Total Sales</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="quick-actions">
      <h3 class="section-title">Quick Actions</h3>
      <div class="actions-grid">
        <div class="action-card" @click="navigateTo('/users')">
          <div class="action-icon">
            <ion-icon name="people-outline"></ion-icon>
          </div>
          <div class="action-content">
            <h4>Manage Users</h4>
            <p>View and manage user accounts</p>
          </div>
          <ion-icon name="chevron-forward-outline" class="action-arrow"></ion-icon>
        </div>

        <div class="action-card" @click="navigateTo('/statistics')">
          <div class="action-icon">
            <ion-icon name="analytics-outline"></ion-icon>
          </div>
          <div class="action-content">
            <h4>Statistics</h4>
            <p>View system analytics</p>
          </div>
          <ion-icon name="chevron-forward-outline" class="action-arrow"></ion-icon>
        </div>

        <div class="action-card" @click="navigateTo('/dashboard')">
          <div class="action-icon">
            <ion-icon name="speedometer-outline"></ion-icon>
          </div>
          <div class="action-content">
            <h4>Dashboard</h4>
            <p>Main dashboard view</p>
          </div>
          <ion-icon name="chevron-forward-outline" class="action-arrow"></ion-icon>
        </div>

        <div class="action-card" @click="navigateTo('/project/alexs-blog')">
          <div class="action-icon">
            <ion-icon name="newspaper-outline"></ion-icon>
          </div>
          <div class="action-content">
            <h4>Alex's Blog</h4>
            <p>Manage blog content</p>
          </div>
          <ion-icon name="chevron-forward-outline" class="action-arrow"></ion-icon>
        </div>
      </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-section">
      <h3 class="section-title">Analytics Overview</h3>
      <div class="analytics-grid">
        <!-- Charts Row -->
        <div class="chart-container large">
          <div class="chart-card">
            <div class="chart-header">
              <h4>Monthly Sales</h4>
              <span class="chart-period">Last 5 months</span>
            </div>
            <div class="chart-content">
              <BarChart
                :data="{
                  labels: ['January', 'February', 'March', 'April', 'May'],
                  datasets: [
                    {
                      label: 'Sales',
                      data: [40, 20, 12, 300, 123],
                      backgroundColor: '#2563eb',
                      borderRadius: 6,
                    },
                  ],
                }"
                :options="{
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: { display: false }
                  },
                  scales: {
                    y: { beginAtZero: true }
                  }
                }"
              ></BarChart>
            </div>
          </div>
        </div>

        <!-- Side Charts -->
        <div class="chart-container">
          <div class="chart-card">
            <div class="chart-header">
              <h4>Technology Usage</h4>
              <span class="chart-period">Current distribution</span>
            </div>
            <div class="chart-content">
              <DonutChart :data="data" :options="chartOptions"></DonutChart>
            </div>
          </div>
        </div>

        <div class="chart-container">
          <div class="chart-card">
            <div class="chart-header">
              <h4>Market Share</h4>
              <span class="chart-period">By framework</span>
            </div>
            <div class="chart-content">
              <PieChart :data="data" :options="chartOptions"></PieChart>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table Section -->
    <div class="data-section">
      <div class="data-card">
        <div class="card-header">
          <div class="header-left">
            <h3>User Management</h3>
            <span class="table-count">{{ tableData.length }} users</span>
          </div>
          <div class="header-right">
            <button class="action-btn primary" @click="navigateTo('/users')">
              <ion-icon name="add-outline"></ion-icon>
              Add User
            </button>
          </div>
        </div>
        <div class="card-content">
          <TableCard :labels="labels" :data="tableData"></TableCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import BarChart from "@/components/BarChart.vue";
import TableCard from "@/components/TableCard.vue";
import DonutChart from "@/components/DonutChart.vue";
import PieChart from "@/components/PieChart.vue";

export default defineComponent({
  name: "DefaultPage",
  data() {
    return {
      labels: ["ID", "Name"],
      tableData: [
        ["1", "Alex"],
        ["2", "Matej"],
        ["3", "Martin"],
        ["4", "John"],
        ["5", "Michael"],
        ["6", "Elias"],
        ["7", "Johann"],
      ],
      data: {
        labels: ["VueJs", "EmberJs", "ReactJs", "AngularJs"],
        datasets: [
          {
            backgroundColor: ["#2563eb", "#059669", "#dc2626", "#d97706"],
            label: "Usage",
            data: [990, 20, 80, 10],
            borderWidth: [0, 0, 0, 0],
          },
        ],
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { 
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 15
            }
          }
        }
      },
    };
  },
  components: {
    BarChart,
    DonutChart,
    PieChart,
    TableCard,
  },
  methods: {
    navigateTo(path: string) {
      this.$router.push(path);
    }
  }
});
</script>
<style scoped>
/* Modern Design System */
.modern-dashboard {
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
  
  padding: 20px;
  /*max-width: 1400px;*/
  margin: 0 auto;
  min-height: 100vh;
  background: var(--background);
  overflow: hidden;
}

/* Dashboard Header */
.dashboard-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 24px;
}

.header-info {
  flex: 1;
  min-width: 300px;
}

.dashboard-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.025em;
}

.dashboard-title ion-icon {
  font-size: 36px;
  color: var(--primary-color);
}

.dashboard-subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-stats {
  display: flex;
  gap: 16px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  min-width: 100px;
  overflow: hidden;
}

.stat-number {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary-color);
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
  text-align: center;
}

/* Section Titles */
.section-title {
  margin: 0 0 20px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Quick Actions */
.quick-actions {
  margin-bottom: 48px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-color);
}

.action-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: var(--radius);
  color: white;
  flex-shrink: 0;
}

.action-icon ion-icon {
  font-size: 24px;
}

.action-content {
  flex: 1;
  min-width: 0;
}

.action-content h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
}

.action-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.4;
}

.action-arrow {
  color: var(--text-muted);
  font-size: 20px;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.action-card:hover .action-arrow {
  color: var(--primary-color);
  transform: translateX(4px);
}

/* Analytics Section */
.analytics-section {
  margin-bottom: 48px;
}

.analytics-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 20px;
}

.chart-container {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.chart-container.large {
  grid-row: span 2;
}

.chart-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.chart-header {
  padding: 20px 20px 0 20px;
  border-bottom: 1px solid var(--border);
}

.chart-header h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.chart-period {
  color: var(--text-secondary);
  font-size: 12px;
}

.chart-content {
  flex: 1;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.chart-container.large .chart-content {
  min-height: 300px;
}

/* Data Section */
.data-section {
  margin-bottom: 32px;
}

.data-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(135deg, var(--background), var(--surface));
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.table-count {
  color: var(--text-secondary);
  font-size: 14px;
}

.header-right {
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

.action-btn ion-icon {
  font-size: 16px;
}

.card-content {
  padding: 24px;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .analytics-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .chart-container.large {
    grid-column: span 2;
    grid-row: span 1;
  }
}

@media (max-width: 768px) {
  .modern-dashboard {
    padding: 16px;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .dashboard-title {
    font-size: 28px;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .analytics-grid {
    grid-template-columns: 1fr;
  }
  
  .chart-container.large {
    grid-column: span 1;
    grid-row: span 1;
  }
  
  .card-header {
    padding: 20px;
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-right {
    justify-content: stretch;
  }
  
  .action-btn {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .modern-dashboard {
    padding: 12px;
  }
  
  .dashboard-title {
    font-size: 24px;
  }
  
  .header-stats {
    flex-direction: column;
    width: 100%;
  }
  
  .stat-card {
    flex-direction: row;
    gap: 12px;
  }
  
  .action-card {
    padding: 16px;
  }
  
  .card-header,
  .card-content {
    padding: 16px;
  }
  
  .chart-content {
    min-height: 180px;
  }
}

/* Dark mode compatibility */
@media (prefers-color-scheme: dark) {
  .modern-dashboard {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}
</style>
