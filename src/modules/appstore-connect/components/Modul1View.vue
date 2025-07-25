<template>
  <ion-page class="ion-padding">
    <ion-content>
      <div class="dashboard-header">
        <h2>App Store Downloads Dashboard</h2>
        <ion-segment v-model="selectedPeriod" @ionChange="loadDownloads">
          <ion-segment-button value="7">
            <ion-label>7 Tage</ion-label>
          </ion-segment-button>
          <ion-segment-button value="30">
            <ion-label>30 Tage</ion-label>
          </ion-segment-button>
          <ion-segment-button value="90">
            <ion-label>3 Monate</ion-label>
          </ion-segment-button>
        </ion-segment>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ formatNumber(stats.total) }}</div>
            <div class="kpi-label">Gesamt Downloads</div>
            <div class="kpi-trend" :class="stats.totalTrend > 0 ? 'positive' : 'negative'">
              <ion-icon :name="stats.totalTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
              {{ Math.abs(stats.totalTrend) }}%
            </div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ formatNumber(stats.dailyAverage) }}</div>
            <div class="kpi-label">Ø pro Tag</div>
            <div class="kpi-trend" :class="stats.avgTrend > 0 ? 'positive' : 'negative'">
              <ion-icon :name="stats.avgTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
              {{ Math.abs(stats.avgTrend) }}%
            </div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ stats.topCountry }}</div>
            <div class="kpi-label">Top Land</div>
            <div class="kpi-subtitle">{{ formatNumber(stats.topCountryDownloads) }} Downloads</div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ stats.activeVersions }}</div>
            <div class="kpi-label">Aktive Versionen</div>
            <div class="kpi-subtitle">{{ stats.latestVersion }} (neueste)</div>
          </ion-card-content>
        </ion-card>
      </div>

      <div v-if="loading" class="loading-container">
        <ion-spinner></ion-spinner>
        <p>Lade Daten...</p>
      </div>

      <div v-else-if="error" class="error-container">
        <ion-icon name="warning"></ion-icon>
        <p>Fehler: {{ error }}</p>
      </div>

      <div v-else class="charts-container">
        <!-- Downloads Timeline Chart -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Downloads Verlauf</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <canvas ref="timelineChart" height="300"></canvas>
          </ion-card-content>
        </ion-card>

        <!-- Country Distribution Chart -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Downloads nach Ländern</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="chart-row">
              <div class="chart-container">
                <canvas ref="countryChart" height="300"></canvas>
              </div>
              <div class="country-list">
                <div v-for="country in topCountries" :key="country.code" class="country-item">
                  <div class="country-flag">{{ getCountryFlag(country.code) }}</div>
                  <div class="country-info">
                    <div class="country-name">{{ getCountryName(country.code) }}</div>
                    <div class="country-downloads">{{ formatNumber(country.downloads) }}</div>
                  </div>
                  <div class="country-percentage">{{ country.percentage }}%</div>
                </div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Platform Distribution -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Plattform Verteilung</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <canvas ref="platformChart" height="250"></canvas>
          </ion-card-content>
        </ion-card>

        <!-- Version Analysis -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>App Versionen Performance</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <canvas ref="versionChart" height="300"></canvas>
          </ion-card-content>
        </ion-card>

        <!-- Weekly Pattern Analysis -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Wochentag Analyse</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <canvas ref="weekdayChart" height="250"></canvas>
          </ion-card-content>
        </ion-card>

        <!-- Detailed Data Table -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              Detaillierte Daten
              <ion-button size="small" fill="outline" @click="exportData">
                <ion-icon name="download"></ion-icon>
                Export CSV
              </ion-button>
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="table-controls">
              <ion-searchbar 
                v-model="searchTerm" 
                placeholder="Suchen..."
                @ionInput="filterData">
              </ion-searchbar>
              <ion-select v-model="filterCountry" placeholder="Land filtern" @ionChange="filterData">
                <ion-select-option value="">Alle Länder</ion-select-option>
                <ion-select-option v-for="country in uniqueCountries" :key="country" :value="country">
                  {{ getCountryName(country) }}
                </ion-select-option>
              </ion-select>
            </div>
            
            <div class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr>
                    <th @click="sortBy('date')" class="sortable">
                      Datum <ion-icon :name="getSortIcon('date')"></ion-icon>
                    </th>
                    <th @click="sortBy('count')" class="sortable">
                      Downloads <ion-icon :name="getSortIcon('count')"></ion-icon>
                    </th>
                    <th @click="sortBy('version')" class="sortable">
                      Version <ion-icon :name="getSortIcon('version')"></ion-icon>
                    </th>
                    <th @click="sortBy('country')" class="sortable">
                      Land <ion-icon :name="getSortIcon('country')"></ion-icon>
                    </th>
                    <th @click="sortBy('platform')" class="sortable">
                      Plattform <ion-icon :name="getSortIcon('platform')"></ion-icon>
                    </th>
                    <th>Wochentag</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in paginatedData" :key="idx" 
                      :class="{ 'high-downloads': item.count > stats.dailyAverage * 1.5 }">
                    <td>{{ formatDate(item.date) }}</td>
                    <td class="number-cell">{{ formatNumber(item.count) }}</td>
                    <td>
                      <span class="version-badge" :class="getVersionClass(item.version)">
                        {{ item.version }}
                      </span>
                    </td>
                    <td>
                      <div class="country-cell">
                        <span class="flag">{{ getCountryFlag(item.country) }}</span>
                        {{ getCountryName(item.country) }}
                      </div>
                    </td>
                    <td>
                      <ion-badge :color="getPlatformColor(item.platform)">
                        {{ item.platform }}
                      </ion-badge>
                    </td>
                    <td>{{ getWeekday(item.date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="pagination" v-if="totalPages > 1">
              <ion-button 
                fill="clear" 
                :disabled="currentPage === 1" 
                @click="currentPage--">
                <ion-icon name="chevron-back"></ion-icon>
              </ion-button>
              <span>Seite {{ currentPage }} von {{ totalPages }}</span>
              <ion-button 
                fill="clear" 
                :disabled="currentPage === totalPages" 
                @click="currentPage++">
                <ion-icon name="chevron-forward"></ion-icon>
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { Chart, LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, BarController, BarElement, DoughnutController, ArcElement, Legend, Tooltip } from 'chart.js';
Chart.register(LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, BarController, BarElement, DoughnutController, ArcElement, Legend, Tooltip);

export default {
  name: 'AppStoreDashboard',
  data() {
    return {
      downloads: [],
      filteredData: [],
      stats: {},
      loading: true,
      error: null,
      charts: {},
      selectedPeriod: '30',
      searchTerm: '',
      filterCountry: '',
      sortField: 'date',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 50,
      
      // Country mapping for flags and names
      countryNames: {
        'US': 'USA', 'DE': 'Deutschland', 'GB': 'Großbritannien',
        'FR': 'Frankreich', 'ES': 'Spanien', 'IT': 'Italien',
        'JP': 'Japan', 'CN': 'China', 'AU': 'Australien',
        'CA': 'Kanada', 'BR': 'Brasilien', 'IN': 'Indien',
        'KR': 'Südkorea', 'MX': 'Mexiko', 'NL': 'Niederlande',
        'SE': 'Schweden', 'NO': 'Norwegen', 'DK': 'Dänemark',
        'FI': 'Finnland', 'AT': 'Österreich', 'CH': 'Schweiz'
      },
      
      countryFlags: {
        'US': '🇺🇸', 'DE': '🇩🇪', 'GB': '🇬🇧', 'FR': '🇫🇷',
        'ES': '🇪🇸', 'IT': '🇮🇹', 'JP': '🇯🇵', 'CN': '🇨🇳',
        'AU': '🇦🇺', 'CA': '🇨🇦', 'BR': '🇧🇷', 'IN': '🇮🇳',
        'KR': '🇰🇷', 'MX': '🇲🇽', 'NL': '🇳🇱', 'SE': '🇸🇪',
        'NO': '🇳🇴', 'DK': '🇩🇰', 'FI': '🇫🇮', 'AT': '🇦🇹', 'CH': '🇨🇭'
      }
    };
  },
  
  computed: {
    topCountries() {
      const countryData = {};
      this.downloads.forEach(item => {
        if (!countryData[item.country]) {
          countryData[item.country] = 0;
        }
        countryData[item.country] += item.count;
      });
      
      const total = Object.values(countryData).reduce((sum, count) => sum + count, 0);
      
      return Object.entries(countryData)
        .map(([code, downloads]) => ({
          code,
          downloads,
          percentage: Math.round((downloads / total) * 100)
        }))
        .sort((a, b) => b.downloads - a.downloads)
        .slice(0, 10);
    },
    
    uniqueCountries() {
      return [...new Set(this.downloads.map(item => item.country))].sort();
    },
    
    paginatedData() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredData.slice(start, end);
    },
    
    totalPages() {
      return Math.ceil(this.filteredData.length / this.itemsPerPage);
    }
  },
  
  mounted() {
    this.loadDownloads();
  },
  
  methods: {
    async loadDownloads() {
      this.loading = true;
      this.error = null;
      
      try {
        const res = await this.$axios.get(`appstore_downloads.php?period=${this.selectedPeriod}`);
        this.downloads = res.data.downloads || [];
        this.calculateStats();
        this.filteredData = [...this.downloads];
        this.sortData();
        
        this.$nextTick(() => {
          this.renderAllCharts();
        });
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },
    
    calculateStats() {
      if (!this.downloads.length) return;
      
      const total = this.downloads.reduce((sum, item) => sum + item.count, 0);
      const dailyAverage = total / this.downloads.length;
      
      // Country analysis
      const countryData = {};
      this.downloads.forEach(item => {
        countryData[item.country] = (countryData[item.country] || 0) + item.count;
      });
      const topCountryEntry = Object.entries(countryData).sort((a, b) => b[1] - a[1])[0];
      
      // Version analysis
      const versions = [...new Set(this.downloads.map(item => item.version))];
      const latestVersion = versions.sort().reverse()[0];
      
      // Calculate trends (mock data for demo)
      const totalTrend = Math.floor(Math.random() * 30) - 15;
      const avgTrend = Math.floor(Math.random() * 20) - 10;
      
      this.stats = {
        total,
        dailyAverage: Math.round(dailyAverage),
        topCountry: topCountryEntry ? topCountryEntry[0] : '',
        topCountryDownloads: topCountryEntry ? topCountryEntry[1] : 0,
        activeVersions: versions.length,
        latestVersion,
        totalTrend,
        avgTrend
      };
    },
    
    renderAllCharts() {
      this.destroyAllCharts();
      this.renderTimelineChart();
      this.renderCountryChart();
      this.renderPlatformChart();
      this.renderVersionChart();
      this.renderWeekdayChart();
    },
    
    destroyAllCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart) chart.destroy();
      });
      this.charts = {};
    },
    
    renderTimelineChart() {
      const ctx = this.$refs.timelineChart;
      if (!ctx) return;
      
      // Group by date
      const dateData = {};
      this.downloads.forEach(item => {
        const date = item.date;
        dateData[date] = (dateData[date] || 0) + item.count;
      });
      
      const sortedDates = Object.keys(dateData).sort();
      const data = sortedDates.map(date => dateData[date]);
      
      this.charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
          labels: sortedDates.map(date => this.formatDate(date)),
          datasets: [{
            label: 'Downloads',
            data: data,
            borderColor: '#3880ff',
            backgroundColor: 'rgba(56,128,255,0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    },
    
    renderCountryChart() {
      const ctx = this.$refs.countryChart;
      if (!ctx) return;
      
      const topCountriesData = this.topCountries.slice(0, 8);
      
      this.charts.country = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: topCountriesData.map(c => this.getCountryName(c.code)),
          datasets: [{
            data: topCountriesData.map(c => c.downloads),
            backgroundColor: [
              '#3880ff', '#3dc2ff', '#2fdf75', '#ffce00',
              '#ff6b6b', '#c77dff', '#06ffa5', '#ffc409'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right'
            }
          }
        }
      });
    },
    
    renderPlatformChart() {
      const ctx = this.$refs.platformChart;
      if (!ctx) return;
      
      const platformData = {};
      this.downloads.forEach(item => {
        platformData[item.platform] = (platformData[item.platform] || 0) + item.count;
      });
      
      this.charts.platform = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: Object.keys(platformData),
          datasets: [{
            label: 'Downloads',
            data: Object.values(platformData),
            backgroundColor: ['#3880ff', '#2fdf75', '#ffce00']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          }
        }
      });
    },
    
    renderVersionChart() {
      const ctx = this.$refs.versionChart;
      if (!ctx) return;
      
      const versionData = {};
      this.downloads.forEach(item => {
        versionData[item.version] = (versionData[item.version] || 0) + item.count;
      });
      
      this.charts.version = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: Object.keys(versionData).sort(),
          datasets: [{
            label: 'Downloads',
            data: Object.keys(versionData).sort().map(v => versionData[v]),
            backgroundColor: '#2fdf75'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          }
        }
      });
    },
    
    renderWeekdayChart() {
      const ctx = this.$refs.weekdayChart;
      if (!ctx) return;
      
      const weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
      const weekdayData = new Array(7).fill(0);
      
      this.downloads.forEach(item => {
        const date = new Date(item.date);
        const dayIndex = (date.getDay() + 6) % 7; // Convert Sunday=0 to Monday=0
        weekdayData[dayIndex] += item.count;
      });
      
      this.charts.weekday = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: weekdays,
          datasets: [{
            label: 'Downloads',
            data: weekdayData,
            backgroundColor: '#ffce00'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          }
        }
      });
    },
    
    filterData() {
      let filtered = [...this.downloads];
      
      if (this.searchTerm) {
        filtered = filtered.filter(item => 
          Object.values(item).some(val => 
            val.toString().toLowerCase().includes(this.searchTerm.toLowerCase())
          )
        );
      }
      
      if (this.filterCountry) {
        filtered = filtered.filter(item => item.country === this.filterCountry);
      }
      
      this.filteredData = filtered;
      this.sortData();
      this.currentPage = 1;
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.sortData();
    },
    
    sortData() {
      this.filteredData.sort((a, b) => {
        let aVal = a[this.sortField];
        let bVal = b[this.sortField];
        
        if (this.sortField === 'count') {
          aVal = parseInt(aVal);
          bVal = parseInt(bVal);
        }
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return 'swap-vertical';
      return this.sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
    },
    
    exportData() {
      const csvContent = [
        ['Datum', 'Downloads', 'Version', 'Land', 'Plattform'],
        ...this.filteredData.map(item => [
          item.date, item.count, item.version, item.country, item.platform
        ])
      ].map(row => row.join(',')).join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `appstore_downloads_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    },
    
    // Helper methods
    formatNumber(num) {
      return new Intl.NumberFormat('de-DE').format(num);
    },
    
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('de-DE');
    },
    
    getCountryName(code) {
      return this.countryNames[code] || code;
    },
    
    getCountryFlag(code) {
      return this.countryFlags[code] || '🏳️';
    },
    
    getWeekday(dateStr) {
      const days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
      return days[new Date(dateStr).getDay()];
    },
    
    getVersionClass(version) {
      // Mock logic for version classification
      if (version.includes('beta')) return 'beta';
      if (parseFloat(version) >= 2.0) return 'latest';
      return 'stable';
    },
    
    getPlatformColor(platform) {
      const colors = {
        'iOS': 'primary',
        'Android': 'success',
        'tvOS': 'warning'
      };
      return colors[platform] || 'medium';
    }
  }
};
</script>

<style scoped>
.dashboard-header {
  margin-bottom: 20px;
}

.dashboard-header h2 {
  margin-bottom: 15px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 25px;
}

.kpi-card {
  margin: 0;
}

.kpi-card ion-card-content {
  text-align: center;
  padding: 20px;
}

.kpi-value {
  font-size: 2.5em;
  font-weight: bold;
  color: var(--ion-color-primary);
  margin-bottom: 5px;
}

.kpi-label {
  font-size: 0.9em;
  color: var(--ion-color-medium);
  margin-bottom: 10px;
}

.kpi-trend {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8em;
  font-weight: bold;
}

.kpi-trend.positive {
  color: var(--ion-color-success);
}

.kpi-trend.negative {
  color: var(--ion-color-danger);
}

.kpi-trend ion-icon {
  margin-right: 3px;
}

.kpi-subtitle {
  font-size: 0.8em;
  color: var(--ion-color-medium);
  margin-top: 5px;
}

.loading-container,
.error-container {
  text-align: center;
  padding: 40px;
}

.loading-container ion-spinner {
  margin-bottom: 10px;
}

.error-container ion-icon {
  font-size: 3em;
  color: var(--ion-color-danger);
  margin-bottom: 10px;
}

.charts-container ion-card {
  margin-bottom: 20px;
}

.chart-row {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 20px;
  align-items: start;
}

.chart-container {
  position: relative;
  height: 300px;
}

.country-list {
  padding: 10px;
}

.country-item {
  display: flex;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid var(--ion-color-light);
}

.country-flag {
  font-size: 1.5em;
  margin-right: 10px;
}

.country-info {
  flex: 1;
}

.country-name {
  font-weight: 500;
}

.country-downloads {
  font-size: 0.9em;
  color: var(--ion-color-medium);
}

.country-percentage {
  font-weight: bold;
  color: var(--ion-color-primary);
}

.table-controls {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  align-items: center;
}

.table-controls ion-searchbar {
  flex: 1;
}

.table-controls ion-select {
  min-width: 200px;
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.data-table th,
.data-table td {
  border: 1px solid var(--ion-color-light);
  padding: 12px 8px;
  text-align: left;
}

.data-table th {
  background: var(--ion-color-light);
  font-weight: 600;
  position: sticky;
  top: 0;
  z-index: 1;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table th.sortable:hover {
  background: var(--ion-color-step-200);
}

.data-table th ion-icon {
  margin-left: 5px;
  font-size: 0.8em;
}

.data-table tr.high-downloads {
  background: rgba(56, 128, 255, 0.05);
}

.number-cell {
  text-align: right;
  font-weight: 500;
}

.country-cell {
  display: flex;
  align-items: center;
}

.country-cell .flag {
  margin-right: 8px;
}

.version-badge {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 500;
}

.version-badge.stable {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary);
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
}

.pagination span {
  font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-row {
    grid-template-columns: 1fr;
  }
  
  .country-list {
    margin-top: 20px;
  }
  
  .table-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-controls ion-select {
    min-width: unset;
  }
  
  .kpi-value {
    font-size: 2em;
  }
  
  .data-table {
    font-size: 0.9em;
  }
  
  .data-table th,
  .data-table td {
    padding: 8px 4px;
  }
}

@media (max-width: 480px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .kpi-value {
    font-size: 1.8em;
  }
  
  .data-table {
    font-size: 0.8em;
  }
  
  .country-item {
    flex-wrap: wrap;
  }
  
  .country-info {
    min-width: 120px;
  }
}

/* Animation and Transitions */
.kpi-card {
  transition: transform 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-2px);
}

.kpi-trend {
  transition: all 0.3s ease;
}

.data-table tr {
  transition: background-color 0.2s ease;
}

.data-table tr:hover {
  background-color: var(--ion-color-step-50);
}

.version-badge {
  transition: all 0.2s ease;
}

.sortable:hover {
  transition: background-color 0.2s ease;
}

/* Loading and Error States */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  color: var(--ion-color-danger);
}

/* Chart Containers */
.charts-container canvas {
  max-height: 400px;
}

ion-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

ion-card-header ion-button {
  margin: 0;
}

/* Custom Scrollbar for Table */
.table-wrapper::-webkit-scrollbar {
  height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: var(--ion-color-step-100);
  border-radius: 4px;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: var(--ion-color-step-300);
  border-radius: 4px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: var(--ion-color-step-400);
}

/* Utility Classes */
.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.font-bold {
  font-weight: bold;
}

.text-muted {
  color: var(--ion-color-medium);
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .data-table th {
    background: var(--ion-color-step-200);
  }
  
  .data-table tr:hover {
    background-color: var(--ion-color-step-100);
  }
  
  .kpi-card:hover {
    box-shadow: 0 4px 8px rgba(255, 255, 255, 0.1);
  }
}latest {
  background: var(--ion-color-success-tint);
  color: var(--ion-color-success);
}

.version-badge.beta {
  background: var(--ion-color-warning-tint);
  color: var(--ion-color-warning);
}

.version-badge.stable {
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary);
}
</style>