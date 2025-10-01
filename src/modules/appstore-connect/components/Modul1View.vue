<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="logo-apple-appstore" title="App Store Analytics" bg="transparent"/>
      
      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <div class="period-selector">
              <button 
                v-for="period in periods" 
                :key="period.value"
                :class="['period-btn', { active: selectedPeriod === period.value }]"
                @click="selectedPeriod = period.value; loadDownloads()">
                {{ period.label }}
              </button>
            </div>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn" @click="refreshData">
              <ion-icon name="refresh-outline"></ion-icon>
              Aktualisieren
            </button>
            <button class="action-btn primary" @click="exportData">
              <ion-icon name="download-outline"></ion-icon>
              Export CSV
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-icon">
            <ion-icon name="hourglass-outline"></ion-icon>
          </div>
          <p>Lade App Store Daten...</p>
          <p class="loading-detail">Dies kann einige Sekunden dauern</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <ion-icon name="warning-outline"></ion-icon>
          <h4>Fehler beim Laden</h4>
          <p>{{ error }}</p>
          <button class="action-btn primary" @click="loadDownloads">
            <ion-icon name="refresh-outline"></ion-icon>
            Erneut versuchen
          </button>
        </div>

        <!-- Stats Cards -->
        <div v-else class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon primary">
              <ion-icon name="download-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(stats.total) }}</div>
              <div class="stat-label">Gesamt Downloads</div>
              <div class="stat-trend" :class="stats.totalTrend > 0 ? 'positive' : 'negative'" v-if="stats.totalTrend">
                <ion-icon :name="stats.totalTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
                {{ Math.abs(stats.totalTrend) }}% vs. vorher
              </div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon success">
              <ion-icon name="calendar-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(stats.dailyAverage) }}</div>
              <div class="stat-label">Ø pro Tag</div>
              <div class="stat-trend" :class="stats.avgTrend > 0 ? 'positive' : 'negative'" v-if="stats.avgTrend">
                <ion-icon :name="stats.avgTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
                {{ Math.abs(stats.avgTrend) }}% vs. vorher
              </div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon warning">
              <ion-icon name="location-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ getCountryFlag(stats.topCountry) }} {{ getCountryName(stats.topCountry) }}</div>
              <div class="stat-label">Top Land</div>
              <div class="stat-subtitle">{{ formatNumber(stats.topCountryDownloads) }} Downloads</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon info">
              <ion-icon name="code-working-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.latestVersion || 'N/A' }}</div>
              <div class="stat-label">Neueste Version</div>
              <div class="stat-subtitle">{{ stats.activeVersions }} aktive Version(en)</div>
            </div>
          </div>

          <div class="stat-card" v-if="stats.peakDay">
            <div class="stat-icon danger">
              <ion-icon name="trophy-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(stats.peakDay.downloads) }}</div>
              <div class="stat-label">Peak Day</div>
              <div class="stat-subtitle">{{ formatDate(stats.peakDay.date) }}</div>
            </div>
          </div>

          <div class="stat-card" v-if="stats.growthRate !== 0">
            <div class="stat-icon" :class="stats.growthRate > 0 ? 'success' : 'danger'">
              <ion-icon name="analytics-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.growthRate > 0 ? '+' : '' }}{{ stats.growthRate }}%</div>
              <div class="stat-label">7-Tage Wachstum</div>
              <div class="stat-subtitle">vs. vorherige Woche</div>
            </div>
          </div>

          <div class="stat-card" v-if="stats.revenue > 0">
            <div class="stat-icon success">
              <ion-icon name="cash-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ formatCurrency(stats.revenue) }}</div>
              <div class="stat-label">Geschätzter Umsatz</div>
              <div class="stat-subtitle">{{ selectedPeriod }} Tage</div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div v-if="!loading && !error">
          <!-- Timeline Chart -->
          <div class="data-card">
            <div class="card-header">
              <h3>Downloads Verlauf</h3>
            </div>
            <div class="chart-container">
              <canvas ref="timelineChart"></canvas>
            </div>
          </div>

          <!-- Country Distribution -->
          <div class="data-card">
            <div class="card-header">
              <h3>Downloads nach Ländern</h3>
            </div>
            <div class="chart-row">
              <div class="chart-container-half">
                <canvas ref="countryChart"></canvas>
              </div>
              <div class="country-list">
                <div v-for="country in topCountries" :key="country.code" class="country-item">
                  <div class="country-flag">{{ getCountryFlag(country.code) }}</div>
                  <div class="country-info">
                    <div class="country-name">{{ getCountryName(country.code) }}</div>
                    <div class="country-downloads">{{ formatNumber(country.downloads) }} Downloads</div>
                  </div>
                  <div class="country-percentage">{{ country.percentage }}%</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Analytics Grid -->
          <div class="analytics-grid">
            <!-- Platform Distribution -->
            <div class="data-card">
              <div class="card-header">
                <h3>Plattformen</h3>
              </div>
              <div class="chart-container-small">
                <canvas ref="platformChart"></canvas>
              </div>
              <div class="platform-list">
                <div v-for="(count, platform) in platformStats" :key="platform" class="platform-item">
                  <ion-icon :name="getPlatformIcon(platform)"></ion-icon>
                  <span class="platform-name">{{ platform }}</span>
                  <span class="platform-count">{{ formatNumber(count) }}</span>
                </div>
              </div>
            </div>

            <!-- Version Analysis -->
            <div class="data-card">
              <div class="card-header">
                <h3>App Versionen</h3>
              </div>
              <div class="chart-container-small">
                <canvas ref="versionChart"></canvas>
              </div>
              <div class="version-list">
                <div v-for="(count, version) in versionStats" :key="version" class="version-item">
                  <span class="version-badge" :class="getVersionClass(version)">{{ version }}</span>
                  <span class="version-count">{{ formatNumber(count) }}</span>
                </div>
              </div>
            </div>

            <!-- Weekday Pattern -->
            <div class="data-card">
              <div class="card-header">
                <h3>Wochentag Analyse</h3>
              </div>
              <div class="chart-container-small">
                <canvas ref="weekdayChart"></canvas>
              </div>
            </div>
          </div>

          <!-- Detailed Data Table -->
          <div class="data-card">
            <div class="card-header">
              <div class="header-left">
                <h3>Detaillierte Daten</h3>
                <span class="entry-count">{{ filteredData.length }} Einträge</span>
              </div>
              <div class="search-box">
                <ion-icon name="search-outline"></ion-icon>
                <input 
                  v-model="searchTerm" 
                  @input="filterData"
                  type="text" 
                  placeholder="Suchen...">
              </div>
            </div>

            <div class="table-filters">
              <select v-model="filterCountry" @change="filterData" class="filter-select">
                <option value="">Alle Länder</option>
                <option v-for="country in uniqueCountries" :key="country" :value="country">
                  {{ getCountryFlag(country) }} {{ getCountryName(country) }}
                </option>
              </select>

              <select v-model="filterPlatform" @change="filterData" class="filter-select">
                <option value="">Alle Plattformen</option>
                <option v-for="platform in uniquePlatforms" :key="platform" :value="platform">
                  {{ platform }}
                </option>
              </select>

              <select v-model="filterVersion" @change="filterData" class="filter-select">
                <option value="">Alle Versionen</option>
                <option v-for="version in uniqueVersions" :key="version" :value="version">
                  {{ version }}
                </option>
              </select>
            </div>

            <div class="table-wrapper">
              <div class="modern-table">
                <div class="table-header">
                  <div class="header-cell" @click="sortBy('date')">
                    <span class="header-text">Datum</span>
                    <div class="sort-indicator">
                      <ion-icon 
                        :name="getSortIcon('date')"
                        :class="sortField === 'date' ? 'sort-active' : 'sort-default'">
                      </ion-icon>
                    </div>
                  </div>
                  <div class="header-cell" @click="sortBy('count')">
                    <span class="header-text">Downloads</span>
                    <div class="sort-indicator">
                      <ion-icon 
                        :name="getSortIcon('count')"
                        :class="sortField === 'count' ? 'sort-active' : 'sort-default'">
                      </ion-icon>
                    </div>
                  </div>
                  <div class="header-cell" @click="sortBy('version')">
                    <span class="header-text">Version</span>
                    <div class="sort-indicator">
                      <ion-icon 
                        :name="getSortIcon('version')"
                        :class="sortField === 'version' ? 'sort-active' : 'sort-default'">
                      </ion-icon>
                    </div>
                  </div>
                  <div class="header-cell" @click="sortBy('country')">
                    <span class="header-text">Land</span>
                    <div class="sort-indicator">
                      <ion-icon 
                        :name="getSortIcon('country')"
                        :class="sortField === 'country' ? 'sort-active' : 'sort-default'">
                      </ion-icon>
                    </div>
                  </div>
                  <div class="header-cell" @click="sortBy('platform')">
                    <span class="header-text">Plattform</span>
                    <div class="sort-indicator">
                      <ion-icon 
                        :name="getSortIcon('platform')"
                        :class="sortField === 'platform' ? 'sort-active' : 'sort-default'">
                      </ion-icon>
                    </div>
                  </div>
                  <div class="header-cell">
                    <span class="header-text">Wochentag</span>
                  </div>
                </div>

                <div class="table-body">
                  <div v-if="paginatedData.length === 0" class="no-data-state">
                    <div class="no-data-content">
                      <ion-icon name="folder-open-outline" class="no-data-icon"></ion-icon>
                      <h4>Keine Daten gefunden</h4>
                      <p>Passe deine Filter an oder wähle einen anderen Zeitraum.</p>
                    </div>
                  </div>

                  <div 
                    v-for="(item, idx) in paginatedData" 
                    :key="idx" 
                    class="table-row"
                    :class="{ 'high-downloads': item.count > stats.dailyAverage * 1.5 }">
                    <div class="table-cell">
                      <span class="cell-content">{{ formatDate(item.date) }}</span>
                    </div>
                    <div class="table-cell">
                      <span class="cell-content number-cell">{{ formatNumber(item.count) }}</span>
                    </div>
                    <div class="table-cell">
                      <span class="version-badge" :class="getVersionClass(item.version)">
                        {{ item.version }}
                      </span>
                    </div>
                    <div class="table-cell">
                      <div class="country-cell">
                        <span class="flag">{{ getCountryFlag(item.country) }}</span>
                        <span class="cell-content">{{ getCountryName(item.country) }}</span>
                      </div>
                    </div>
                    <div class="table-cell">
                      <div class="platform-badge" :class="getPlatformClass(item.platform)">
                        <ion-icon :name="getPlatformIcon(item.platform)"></ion-icon>
                        {{ item.platform }}
                      </div>
                    </div>
                    <div class="table-cell">
                      <span class="cell-content">{{ getWeekday(item.date) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="pagination" v-if="totalPages > 1">
              <button 
                class="pagination-btn" 
                :disabled="currentPage === 1" 
                @click="currentPage = 1">
                <ion-icon name="play-back-outline"></ion-icon>
              </button>
              <button 
                class="pagination-btn" 
                :disabled="currentPage === 1" 
                @click="currentPage--">
                <ion-icon name="chevron-back-outline"></ion-icon>
              </button>
              <span class="pagination-info">Seite {{ currentPage }} von {{ totalPages }}</span>
              <button 
                class="pagination-btn" 
                :disabled="currentPage === totalPages" 
                @click="currentPage++">
                <ion-icon name="chevron-forward-outline"></ion-icon>
              </button>
              <button 
                class="pagination-btn" 
                :disabled="currentPage === totalPages" 
                @click="currentPage = totalPages">
                <ion-icon name="play-forward-outline"></ion-icon>
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
import countryService from '@/services/countryService';

export default {
  name: 'AppStoreDashboard',
  components: {
    SiteTitle
  },
  data() {
    return {
      downloads: [],
      filteredData: [],
      stats: {
        total: 0,
        dailyAverage: 0,
        topCountry: '',
        topCountryDownloads: 0,
        activeVersions: 0,
        latestVersion: '',
        totalTrend: 0,
        avgTrend: 0,
        growthRate: 0,
        peakDay: null,
        revenue: 0
      },
      loading: true,
      error: null,
      charts: {},
      selectedPeriod: 30,
      periods: [
        { value: 7, label: '7 Tage' },
        { value: 30, label: '30 Tage' },
        { value: 90, label: '90 Tage' }
      ],
      searchTerm: '',
      filterCountry: '',
      filterPlatform: '',
      filterVersion: '',
      sortField: 'date',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 50,
      platformStats: {},
      versionStats: {},
      lastUpdate: null
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

    uniquePlatforms() {
      return [...new Set(this.downloads.map(item => item.platform).filter(p => p && p !== 'Unknown'))].sort();
    },

    uniqueVersions() {
      return [...new Set(this.downloads.map(item => item.version).filter(v => v && v !== 'Unknown'))].sort((a, b) => {
        try {
          return this.compareVersions(b, a);
        } catch {
          return b.localeCompare(a);
        }
      });
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

  beforeUnmount() {
    this.destroyAllCharts();
  },
  
  methods: {
    async loadDownloads() {
      this.loading = true;
      this.error = null;
      
      try {
        const res = await this.$axios.get(`appstore_downloads.php?period=${this.selectedPeriod}`);
        
        if (res.data.error) {
          this.error = res.data.error;
          return;
        }

        this.downloads = res.data.downloads || [];
        this.stats = res.data.stats || this.getEmptyStats();
        this.lastUpdate = res.data.last_updated;
        
        this.calculateAdditionalStats();
        this.filteredData = [...this.downloads];
        this.sortData();
        
        this.$nextTick(() => {
          this.renderAllCharts();
        });
      } catch (e) {
        console.error('Error loading downloads:', e);
        this.error = e.message || 'Fehler beim Laden der Daten';
      } finally {
        this.loading = false;
      }
    },

    refreshData() {
      // Clear cache by adding timestamp
      this.loadDownloads();
    },
    
    calculateAdditionalStats() {
      // Calculate platform and version stats
      this.platformStats = {};
      this.versionStats = {};
      
      this.downloads.forEach(item => {
        const platform = item.platform && item.platform !== 'Unknown' ? item.platform : 'Unbekannt';
        const version = item.version && item.version !== 'Unknown' ? item.version : 'Unbekannt';
        
        this.platformStats[platform] = (this.platformStats[platform] || 0) + item.count;
        this.versionStats[version] = (this.versionStats[version] || 0) + item.count;
      });
    },

    getEmptyStats() {
      return {
        total: 0,
        dailyAverage: 0,
        topCountry: '',
        topCountryDownloads: 0,
        activeVersions: 0,
        latestVersion: '',
        totalTrend: 0,
        avgTrend: 0,
        growthRate: 0,
        peakDay: null,
        revenue: 0
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
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              titleFont: { size: 14 },
              bodyFont: { size: 13 }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => this.formatNumber(value)
              }
            }
          }
        }
      });
    },
    
    renderCountryChart() {
      const ctx = this.$refs.countryChart;
      if (!ctx) return;
      
      const topCountriesData = this.topCountries.slice(0, 8);
      
      const colors = [
        '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd',
        '#059669', '#10b981', '#34d399', '#6ee7b7'
      ];
      
      this.charts.country = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: topCountriesData.map(c => this.getCountryName(c.code)),
          datasets: [{
            data: topCountriesData.map(c => c.downloads),
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                padding: 15,
                font: { size: 12 }
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              callbacks: {
                label: (context) => {
                  const label = context.label || '';
                  const value = this.formatNumber(context.parsed);
                  const percent = topCountriesData[context.dataIndex].percentage;
                  return `${label}: ${value} (${percent}%)`;
                }
              }
            }
          }
        }
      });
    },
    
    renderPlatformChart() {
      const ctx = this.$refs.platformChart;
      if (!ctx) return;
      
      const platforms = Object.keys(this.platformStats).filter(p => p !== 'Unbekannt');
      const data = platforms.map(p => this.platformStats[p]);
      
      this.charts.platform = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: platforms,
          datasets: [{
            data: data,
            backgroundColor: ['#2563eb', '#059669', '#d97706', '#dc2626'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 10,
                font: { size: 11 }
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              callbacks: {
                label: (context) => {
                  const label = context.label || '';
                  const value = this.formatNumber(context.parsed);
                  return `${label}: ${value}`;
                }
              }
            }
          }
        }
      });
    },
    
    renderVersionChart() {
      const ctx = this.$refs.versionChart;
      if (!ctx) return;
      
      const versions = Object.keys(this.versionStats).filter(v => v !== 'Unbekannt').slice(0, 10);
      const data = versions.map(v => this.versionStats[v]);
      
      this.charts.version = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: versions,
          datasets: [{
            label: 'Downloads',
            data: data,
            backgroundColor: '#059669',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              callbacks: {
                label: (context) => `Downloads: ${this.formatNumber(context.parsed.y)}`
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => this.formatNumber(value)
              }
            }
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
            backgroundColor: '#d97706',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              callbacks: {
                label: (context) => `Downloads: ${this.formatNumber(context.parsed.y)}`
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => this.formatNumber(value)
              }
            }
          }
        }
      });
    },
    
    filterData() {
      let filtered = [...this.downloads];
      
      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        filtered = filtered.filter(item => 
          item.date.toLowerCase().includes(term) ||
          item.country.toLowerCase().includes(term) ||
          item.version.toLowerCase().includes(term) ||
          item.platform.toLowerCase().includes(term) ||
          this.getCountryName(item.country).toLowerCase().includes(term)
        );
      }
      
      if (this.filterCountry) {
        filtered = filtered.filter(item => item.country === this.filterCountry);
      }

      if (this.filterPlatform) {
        filtered = filtered.filter(item => item.platform === this.filterPlatform);
      }

      if (this.filterVersion) {
        filtered = filtered.filter(item => item.version === this.filterVersion);
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
        this.sortDirection = 'desc';
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
        } else if (this.sortField === 'version') {
          try {
            return this.sortDirection === 'asc' 
              ? this.compareVersions(aVal, bVal) 
              : this.compareVersions(bVal, aVal);
          } catch {
            // Fallback to string comparison
          }
        }
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },

    compareVersions(v1, v2) {
      const parts1 = v1.split('.').map(Number);
      const parts2 = v2.split('.').map(Number);
      
      for (let i = 0; i < Math.max(parts1.length, parts2.length); i++) {
        const part1 = parts1[i] || 0;
        const part2 = parts2[i] || 0;
        if (part1 !== part2) return part1 - part2;
      }
      return 0;
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return 'swap-vertical-outline';
      return this.sortDirection === 'asc' ? 'chevron-up-outline' : 'chevron-down-outline';
    },
    
    exportData() {
      const csvContent = [
        ['Datum', 'Downloads', 'Version', 'Land', 'Plattform', 'Wochentag'],
        ...this.filteredData.map(item => [
          item.date,
          item.count,
          item.version,
          this.getCountryName(item.country),
          item.platform,
          this.getWeekday(item.date)
        ])
      ].map(row => row.join(';')).join('\n');
      
      const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `appstore_downloads_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    },
    
    // Helper methods
    formatNumber(num) {
      if (num === null || num === undefined) return '0';
      return new Intl.NumberFormat('de-DE').format(num);
    },
    
    formatDate(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString('de-DE');
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    },
    
    getCountryName(code) {
      if (!code || code === 'Unknown') return 'Unbekannt';
      return countryService.getCountryName(code);
    },

    getCountryFlag(code) {
      if (!code || code === 'Unknown') return '🌍';
      return countryService.getCountryFlag(code) || '🌍';
    },
    
    getWeekday(dateStr) {
      if (!dateStr) return '';
      const days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
      return days[new Date(dateStr).getDay()];
    },
    
    getVersionClass(version) {
      if (!version || version === 'Unknown' || version === 'Unbekannt') return 'unknown';
      if (version.includes('beta') || version.includes('Beta')) return 'beta';
      if (version === this.stats.latestVersion) return 'latest';
      return 'stable';
    },

    getPlatformClass(platform) {
      if (!platform || platform === 'Unknown' || platform === 'Unbekannt') return 'unknown';
      return platform.toLowerCase().replace(/\s+/g, '-');
    },
    
    getPlatformIcon(platform) {
      if (!platform || platform === 'Unknown' || platform === 'Unbekannt') return 'help-circle-outline';
      const icons = {
        'iOS': 'logo-apple',
        'macOS': 'logo-apple',
        'tvOS': 'tv-outline',
        'watchOS': 'watch-outline',
        'iPadOS': 'tablet-landscape-outline'
      };
      return icons[platform] || 'phone-portrait-outline';
    }
  }
};
</script>

<style scoped>
/* Modern Design System - Gray Dark Mode like LinkAnalyticsView */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --info-color: #0891b2;
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

/* Gray Dark Mode */
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

ion-content.modern-content {
  --background: var(--background);
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Action Bar */
.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.action-group-left,
.action-group-right {
  display: flex;
  align-items: center;
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

.action-btn ion-icon {
  font-size: 16px;
}

/* Period Selector */
.period-selector {
  display: flex;
  gap: 8px;
}

.period-btn {
  padding: 8px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 500;
}

.period-btn:hover {
  background: var(--background);
}

.period-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: var(--surface);
  border-radius: var(--radius-lg);
  margin: 20px 0;
}

.loading-icon,
.error-state ion-icon {
  font-size: 64px;
  color: var(--primary-color);
  margin-bottom: 16px;
}

.error-state ion-icon {
  color: var(--danger-color);
}

.loading-state p,
.error-state h4,
.error-state p {
  color: var(--text-primary);
  margin: 8px 0;
}

.loading-detail {
  color: var(--text-secondary);
  font-size: 14px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 20px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  gap: 16px;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon ion-icon {
  font-size: 24px;
}

.stat-icon.primary {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.stat-icon.success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.stat-icon.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.stat-icon.info {
  background: rgba(8, 145, 178, 0.1);
  color: var(--info-color);
}

.stat-icon.danger {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
}

.stat-subtitle {
  color: var(--text-muted);
  font-size: 12px;
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: var(--radius);
  margin-top: 4px;
}

.stat-trend.positive {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.stat-trend.negative {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.stat-trend ion-icon {
  font-size: 14px;
}

/* Data Cards */
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
  background: var(--background);
  flex-wrap: wrap;
  gap: 16px;
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

/* Search Box */
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
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--background);
  color: var(--text-primary);
  min-width: 250px;
  transition: all 0.2s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Charts */
.chart-container {
  padding: 20px;
  height: 350px;
  position: relative;
}

.chart-container-half {
  padding: 20px;
  height: 300px;
  position: relative;
  flex: 1;
}

.chart-container-small {
  padding: 20px;
  height: 250px;
  position: relative;
}

.chart-row {
  display: flex;
  gap: 20px;
  align-items: stretch;
  flex-wrap: wrap;
}

/* Country List */
.country-list {
  padding: 20px;
  max-height: 340px;
  overflow-y: auto;
  min-width: 280px;
}

.country-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
  gap: 12px;
}

.country-item:last-child {
  border-bottom: none;
}

.country-flag {
  font-size: 24px;
  width: 32px;
  text-align: center;
}

.country-info {
  flex: 1;
  min-width: 0;
}

.country-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 2px;
}

.country-downloads {
  font-size: 12px;
  color: var(--text-secondary);
}

.country-percentage {
  font-weight: 700;
  color: var(--primary-color);
  font-size: 16px;
}

/* Analytics Grid */
.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

/* Platform & Version Lists */
.platform-list,
.version-list {
  padding: 20px;
  max-height: 200px;
  overflow-y: auto;
}

.platform-item,
.version-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
}

.platform-item:last-child,
.version-item:last-child {
  border-bottom: none;
}

.platform-item {
  gap: 12px;
}

.platform-item ion-icon {
  font-size: 20px;
  color: var(--text-secondary);
}

.platform-name {
  flex: 1;
  font-weight: 500;
  color: var(--text-primary);
}

.platform-count,
.version-count {
  font-weight: 700;
  color: var(--primary-color);
  background: var(--background);
  padding: 4px 12px;
  border-radius: var(--radius);
  font-size: 13px;
}

/* Filters */
.table-filters {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 150px;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* Modern Table */
.table-wrapper {
  overflow-x: auto;
  padding: 20px 0;
}

.modern-table {
  width: 100%;
  min-width: 900px;
  padding: 0 24px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
  border-radius: var(--radius);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.header-cell:hover {
  background: var(--border);
}

.header-text {
  font-weight: 600;
}

.sort-indicator {
  display: flex;
  align-items: center;
  margin-left: 8px;
}

.sort-indicator ion-icon {
  font-size: 14px;
  transition: all 0.2s ease;
}

.sort-default {
  opacity: 0.3;
}

.sort-active {
  opacity: 1;
  color: var(--primary-color);
}

.header-cell:hover .sort-default {
  opacity: 0.6;
}

/* Table Body */
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

.table-row.high-downloads {
  background: rgba(37, 99, 235, 0.05);
}

.table-row.high-downloads:hover {
  background: rgba(37, 99, 235, 0.1);
}

.table-row:last-child {
  border-bottom: none;
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
  max-width: 200px;
}

.number-cell {
  font-weight: 600;
  text-align: right;
  justify-content: flex-end;
}

.country-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.country-cell .flag {
  font-size: 18px;
}

/* Version Badges */
.version-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
}

.version-badge.latest {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.version-badge.beta {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.version-badge.stable {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.version-badge.unknown {
  background: rgba(100, 116, 139, 0.1);
  color: var(--text-muted);
}

/* Platform Badges */
.platform-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.platform-badge.ios,
.platform-badge.macos {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.platform-badge.tvos {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.platform-badge.watchos {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.platform-badge.unknown {
  background: rgba(100, 116, 139, 0.1);
  color: var(--text-muted);
}

.platform-badge ion-icon {
  font-size: 14px;
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
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
  line-height: 1.5;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
}

.pagination-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--background);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-btn ion-icon {
  font-size: 18px;
}

.pagination-info {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
}

/* Scrollbar Styling */
.country-list::-webkit-scrollbar,
.platform-list::-webkit-scrollbar,
.version-list::-webkit-scrollbar,
.table-wrapper::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.country-list::-webkit-scrollbar-track,
.platform-list::-webkit-scrollbar-track,
.version-list::-webkit-scrollbar-track,
.table-wrapper::-webkit-scrollbar-track {
  background: var(--background);
  border-radius: 4px;
}

.country-list::-webkit-scrollbar-thumb,
.platform-list::-webkit-scrollbar-thumb,
.version-list::-webkit-scrollbar-thumb,
.table-wrapper::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 4px;
}

.country-list::-webkit-scrollbar-thumb:hover,
.platform-list::-webkit-scrollbar-thumb:hover,
.version-list::-webkit-scrollbar-thumb:hover,
.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: var(--text-muted);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }

  .chart-row {
    flex-direction: column;
  }

  .country-list {
    max-height: none;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    justify-content: center;
  }

  .period-selector {
    flex-wrap: wrap;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box input {
    min-width: 100%;
  }

  .table-filters {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .modern-table {
    min-width: 700px;
  }

  .header-cell,
  .table-cell {
    min-width: 100px;
    padding: 12px 8px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .stat-icon {
    width: 40px;
    height: 40px;
  }

  .stat-icon ion-icon {
    font-size: 20px;
  }

  .stat-value {
    font-size: 1.25rem;
  }

  .period-btn {
    padding: 6px 12px;
    font-size: 13px;
  }
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-card,
.data-card {
  animation: fadeIn 0.3s ease;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.loading-icon ion-icon {
  animation: spin 2s linear infinite;
}
</style>


