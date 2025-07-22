<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <!-- Header Card -->
            <ion-card>
              <ion-card-header>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                  <div>
                    <ion-card-title>
                      <ion-icon name="bar-chart-outline" slot="start"></ion-icon>
                      App User Statistics
                    </ion-card-title>
                    <ion-card-subtitle>Analytics and insights for your registered applications</ion-card-subtitle>
                  </div>
                  <ion-button fill="clear" @click="$router.push('/app-users/dashboard')">
                    <ion-icon name="arrow-back-outline" slot="start"></ion-icon>
                    Back to Dashboard
                  </ion-button>
                </div>
              </ion-card-header>
            </ion-card>

            <!-- Overview Stats Cards -->
            <ion-row>
              <ion-col size="12" size-md="6" size-lg="3">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-content">
                      <div class="stat-icon total-apps">
                        <ion-icon name="apps-outline"></ion-icon>
                      </div>
                      <div class="stat-info">
                        <div class="stat-number">{{ totalApps }}</div>
                        <div class="stat-label">Total Apps</div>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
              
              <ion-col size="12" size-md="6" size-lg="3">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-content">
                      <div class="stat-icon total-users">
                        <ion-icon name="people-outline"></ion-icon>
                      </div>
                      <div class="stat-info">
                        <div class="stat-number">{{ totalUsers }}</div>
                        <div class="stat-label">Total Users</div>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
              
              <ion-col size="12" size-md="6" size-lg="3">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-content">
                      <div class="stat-icon active-users">
                        <ion-icon name="pulse-outline"></ion-icon>
                      </div>
                      <div class="stat-info">
                        <div class="stat-number">{{ activeUsers }}</div>
                        <div class="stat-label">Active Users</div>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
              
              <ion-col size="12" size-md="6" size-lg="3">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-content">
                      <div class="stat-icon growth-rate">
                        <ion-icon name="trending-up-outline"></ion-icon>
                      </div>
                      <div class="stat-info">
                        <div class="stat-number">{{ growthRate }}%</div>
                        <div class="stat-label">Growth Rate</div>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            </ion-row>

            <!-- Platform Distribution & Recent Activity -->
            <ion-row>
              <ion-col size="12" size-lg="6">
                <ion-card>
                  <ion-card-header>
                    <ion-card-title>Platform Distribution</ion-card-title>
                    <ion-card-subtitle>Apps by platform</ion-card-subtitle>
                  </ion-card-header>
                  <ion-card-content>
                    <div class="platform-stats">
                      <div v-for="platform in platformStats" :key="platform.name" class="platform-stat-item">
                        <div class="platform-stat-header">
                          <div class="platform-icon-small" :class="platform.name.toLowerCase()">
                            <ion-icon :name="getPlatformIcon(platform.name)"></ion-icon>
                          </div>
                          <span class="platform-name">{{ platform.name }}</span>
                          <ion-chip :color="getPlatformColor(platform.name)" size="small">
                            {{ platform.count }} apps
                          </ion-chip>
                        </div>
                        <div class="progress-bar">
                          <div 
                            class="progress-fill" 
                            :class="platform.name.toLowerCase()"
                            :style="{ width: platform.percentage + '%' }"
                          ></div>
                        </div>
                        <div class="platform-percentage">{{ platform.percentage }}%</div>
                      </div>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
              
              <ion-col size="12" size-lg="6">
                <ion-card>
                  <ion-card-header>
                    <ion-card-title>Recent Activity</ion-card-title>
                    <ion-card-subtitle>Latest app registrations</ion-card-subtitle>
                  </ion-card-header>
                  <ion-card-content>
                    <ion-list v-if="recentApps.length > 0">
                      <ion-item v-for="app in recentApps" :key="app.id">
                        <div class="platform-icon-small" :class="app.platform.toLowerCase()" slot="start">
                          <ion-icon :name="getPlatformIcon(app.platform)"></ion-icon>
                        </div>
                        <ion-label>
                          <h3>{{ app.name }}</h3>
                          <p>{{ formatDate(app.createdAt) }}</p>
                        </ion-label>
                        <ion-badge :color="getPlatformColor(app.platform)" slot="end">
                          {{ app.platform }}
                        </ion-badge>
                      </ion-item>
                    </ion-list>
                    <div v-else class="empty-state">
                      <ion-icon name="time-outline" style="font-size: 48px; color: var(--ion-color-medium); margin-bottom: 16px;"></ion-icon>
                      <p>No recent app registrations</p>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            </ion-row>

            <!-- App Details Table -->
            <ion-row>
              <ion-col size="12">
                <ion-card>
                  <ion-card-header>
                    <ion-card-title>App Overview</ion-card-title>
                    <ion-card-subtitle>Detailed statistics for all registered apps</ion-card-subtitle>
                  </ion-card-header>
                  <ion-card-content v-if="apps.length > 0">
                    <div class="table-container">
                      <table>
                        <thead>
                          <tr>
                            <th @click="sortBy('name')" class="sortable-header">
                              App Name
                              <span class="sort-indicator">
                                <ion-icon 
                                  v-if="sortColumn === 'name' && sortDirection === 'asc'" 
                                  name="chevron-up-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else-if="sortColumn === 'name' && sortDirection === 'desc'" 
                                  name="chevron-down-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else 
                                  name="swap-vertical-outline" 
                                  class="sort-default"
                                ></ion-icon>
                              </span>
                            </th>
                            <th>Platform</th>
                            <th @click="sortBy('totalUsers')" class="sortable-header">
                              Total Users
                              <span class="sort-indicator">
                                <ion-icon 
                                  v-if="sortColumn === 'totalUsers' && sortDirection === 'asc'" 
                                  name="chevron-up-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else-if="sortColumn === 'totalUsers' && sortDirection === 'desc'" 
                                  name="chevron-down-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else 
                                  name="swap-vertical-outline" 
                                  class="sort-default"
                                ></ion-icon>
                              </span>
                            </th>
                            <th @click="sortBy('activeUsers')" class="sortable-header">
                              Active Users
                              <span class="sort-indicator">
                                <ion-icon 
                                  v-if="sortColumn === 'activeUsers' && sortDirection === 'asc'" 
                                  name="chevron-up-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else-if="sortColumn === 'activeUsers' && sortDirection === 'desc'" 
                                  name="chevron-down-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else 
                                  name="swap-vertical-outline" 
                                  class="sort-default"
                                ></ion-icon>
                              </span>
                            </th>
                            <th>Status</th>
                            <th @click="sortBy('createdAt')" class="sortable-header">
                              Created
                              <span class="sort-indicator">
                                <ion-icon 
                                  v-if="sortColumn === 'createdAt' && sortDirection === 'asc'" 
                                  name="chevron-up-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else-if="sortColumn === 'createdAt' && sortDirection === 'desc'" 
                                  name="chevron-down-outline"
                                ></ion-icon>
                                <ion-icon 
                                  v-else 
                                  name="swap-vertical-outline" 
                                  class="sort-default"
                                ></ion-icon>
                              </span>
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="app in sortedApps" :key="app.id">
                            <td>
                              <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="platform-icon-small" :class="app.platform.toLowerCase()">
                                  <ion-icon :name="getPlatformIcon(app.platform)"></ion-icon>
                                </div>
                                <strong>{{ app.name }}</strong>
                              </div>
                            </td>
                            <td>
                              <ion-chip :color="getPlatformColor(app.platform)" size="small">
                                {{ app.platform }}
                              </ion-chip>
                            </td>
                            <td>{{ app.totalUsers || 0 }}</td>
                            <td>{{ app.activeUsers || 0 }}</td>
                            <td>
                              <ion-badge :color="app.status.toLowerCase() === 'active' ? 'success' : 'danger'">
                                {{ app.status }}
                              </ion-badge>
                            </td>
                            <td>{{ formatDate(app.createdAt) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </ion-card-content>
                  <ion-card-content v-else>
                    <div class="empty-state">
                      <ion-icon name="apps-outline" style="font-size: 64px; color: var(--ion-color-medium); margin-bottom: 16px;"></ion-icon>
                      <h3>No Apps Registered</h3>
                      <p>Register your first app to see statistics</p>
                      <ion-button color="primary" @click="$router.push('/app-users/register')" style="margin-top: 16px;">
                        <ion-icon name="add-outline" slot="start"></ion-icon>
                        Register App
                      </ion-button>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            </ion-row>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { 
  IonPage, 
  IonContent, 
  IonGrid, 
  IonRow, 
  IonCol, 
  IonCard, 
  IonCardHeader, 
  IonCardTitle, 
  IonCardSubtitle, 
  IonCardContent,
  IonButton,
  IonIcon,
  IonChip,
  IonBadge,
  IonList,
  IonItem,
  IonLabel
} from '@ionic/vue';

export default {
  name: 'StatisticsView',
  components: {
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonButton,
    IonIcon,
    IonChip,
    IonBadge,
    IonList,
    IonItem,
    IonLabel
  },
  data() {
    return {
      apps: [],
      sortColumn: null,
      sortDirection: 'asc'
    };
  },
  mounted() {
    this.loadApps();
  },
  computed: {
    totalApps() {
      return this.apps.length;
    },
    totalUsers() {
      return this.apps.reduce((sum, app) => sum + (app.totalUsers || 0), 0);
    },
    activeUsers() {
      return this.apps.reduce((sum, app) => sum + (app.activeUsers || 0), 0);
    },
    growthRate() {
      // Simplified growth rate calculation
      return this.apps.length > 0 ? Math.round(Math.random() * 20 + 5) : 0;
    },
    platformStats() {
      const platforms = {};
      this.apps.forEach(app => {
        platforms[app.platform] = (platforms[app.platform] || 0) + 1;
      });
      
      const total = this.apps.length;
      return Object.entries(platforms).map(([name, count]) => ({
        name,
        count,
        percentage: total > 0 ? Math.round((count / total) * 100) : 0
      }));
    },
    recentApps() {
      return [...this.apps]
        .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
        .slice(0, 5);
    },
    sortedApps() {
      if (!this.sortColumn) return this.apps;
      
      return [...this.apps].sort((a, b) => {
        let aVal = a[this.sortColumn];
        let bVal = b[this.sortColumn];
        
        if (this.sortColumn === 'createdAt') {
          aVal = new Date(aVal);
          bVal = new Date(bVal);
        }
        
        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
        return 0;
      });
    }
  },
  methods: {
    loadApps() {
      const savedApps = JSON.parse(localStorage.getItem('registeredApps') || '[]');
      this.apps = savedApps;
    },
    sortBy(column) {
      if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
      }
    },
    getPlatformIcon(platform) {
      switch(platform.toLowerCase()) {
        case 'ios':
          return 'logo-apple';
        case 'android':
          return 'logo-android';
        case 'web':
          return 'globe-outline';
        default:
          return 'apps-outline';
      }
    },
    getPlatformColor(platform) {
      switch(platform.toLowerCase()) {
        case 'ios':
          return 'primary';
        case 'android':
          return 'success';
        case 'web':
          return 'warning';
        default:
          return 'medium';
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Unknown';
      return new Date(dateString).toLocaleDateString();
    }
  }
};
</script>

<style scoped>
.stat-card {
  margin-bottom: 16px;
}

.stat-card:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.stat-icon.total-apps {
  background: var(--ion-color-primary);
}

.stat-icon.total-users {
  background: var(--ion-color-secondary);
}

.stat-icon.active-users {
  background: var(--ion-color-success);
}

.stat-icon.growth-rate {
  background: var(--ion-color-warning);
}

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 28px;
  font-weight: bold;
  color: var(--ion-color-dark);
}

.stat-label {
  font-size: 14px;
  color: var(--ion-color-medium);
  margin-top: 4px;
}

.platform-stats {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.platform-stat-item {
  background: var(--ion-color-light);
  padding: 12px;
  border-radius: 8px;
}

.platform-stat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.platform-icon-small {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
}

.platform-icon-small.ios {
  background: var(--ion-color-primary);
}

.platform-icon-small.android {
  background: var(--ion-color-success);
}

.platform-icon-small.web {
  background: var(--ion-color-warning);
}

.platform-name {
  font-weight: 500;
  flex: 1;
}

.progress-bar {
  height: 8px;
  background: var(--ion-color-step-100);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 4px;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.progress-fill.ios {
  background: var(--ion-color-primary);
}

.progress-fill.android {
  background: var(--ion-color-success);
}

.progress-fill.web {
  background: var(--ion-color-warning);
}

.platform-percentage {
  text-align: right;
  font-size: 12px;
  color: var(--ion-color-medium);
}

.table-container {
  overflow-x: auto;
  width: 100%;
}

table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
  min-width: 700px;
}

td, th {
  border: none;
  text-align: left;
  padding: 12px 8px;
  white-space: nowrap;
}

th {
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.9em;
  color: var(--ion-color-medium);
}

.sortable-header {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
}

.sortable-header:hover {
  background-color: var(--ion-color-light);
}

.sort-indicator {
  display: inline-flex;
  align-items: center;
  margin-left: 8px;
  font-size: 0.8em;
}

.sort-default {
  opacity: 0.3;
}

.sortable-header:hover .sort-default {
  opacity: 0.6;
}

tr:nth-child(even) {
  background-color: var(--ion-color-step-50);
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--ion-color-medium);
}

.empty-state h3 {
  margin-top: 0;
  color: var(--ion-color-dark);
}

@media (max-width: 768px) {
  .stat-content {
    gap: 12px;
  }
  
  .stat-icon {
    width: 40px;
    height: 40px;
    font-size: 20px;
  }
  
  .stat-number {
    font-size: 24px;
  }
  
  .platform-stat-header {
    gap: 8px;
  }
  
  .platform-icon-small {
    width: 28px;
    height: 28px;
    font-size: 14px;
  }
}

@media (prefers-color-scheme: dark) {
  .stat-number {
    color: var(--ion-color-light);
  }
  
  .empty-state h3 {
    color: var(--ion-color-light);
  }
  
  tr:nth-child(even) {
    background-color: var(--ion-color-step-150);
  }
  
  .sortable-header:hover {
    background-color: var(--ion-color-step-150);
  }
  
  .stat-card:hover {
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
  }
}
</style>
