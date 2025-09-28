<template>
  <ion-page class="ion-padding">
    <ion-content>
      <div class="dashboard-header">
        <h2>Link Tracker Dashboard</h2>
        <ion-segment v-model="selectedPeriod" @ionChange="loadAnalytics">
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
            <div class="kpi-value">{{ formatNumber(stats.totalClicks) }}</div>
            <div class="kpi-label">Gesamt Klicks</div>
            <div class="kpi-trend" :class="stats.clicksTrend > 0 ? 'positive' : 'negative'">
              <ion-icon :name="stats.clicksTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
              {{ Math.abs(stats.clicksTrend) }}%
            </div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ formatNumber(stats.uniqueVisitors) }}</div>
            <div class="kpi-label">Eindeutige Besucher</div>
            <div class="kpi-trend" :class="stats.visitorsTrend > 0 ? 'positive' : 'negative'">
              <ion-icon :name="stats.visitorsTrend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
              {{ Math.abs(stats.visitorsTrend) }}%
            </div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ stats.activeLinks }}</div>
            <div class="kpi-label">Aktive Links</div>
            <div class="kpi-subtitle">{{ stats.totalLinks }} gesamt</div>
          </ion-card-content>
        </ion-card>

        <ion-card class="kpi-card">
          <ion-card-content>
            <div class="kpi-value">{{ formatNumber(stats.avgClicksPerDay) }}</div>
            <div class="kpi-label">Ø Klicks/Tag</div>
            <div class="kpi-subtitle">{{ formatNumber(stats.clickRate) }}% CTR</div>
          </ion-card-content>
        </ion-card>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <ion-button expand="block" @click="openCreateLinkModal">
          <ion-icon name="add-circle" slot="start"></ion-icon>
          Neuen Link erstellen
        </ion-button>
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
        <!-- Clicks Timeline Chart -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Klicks Verlauf</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <canvas ref="timelineChart" height="300"></canvas>
          </ion-card-content>
        </ion-card>

        <!-- Links Performance -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Top Performance Links</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="links-list">
              <div v-for="link in topLinks" :key="link.id" class="link-item">
                <div class="link-info">
                  <div class="link-title">{{ link.title }}</div>
                  <div class="link-url">{{ link.shortUrl }}</div>
                </div>
                <div class="link-stats">
                  <div class="stat">
                    <span class="stat-value">{{ formatNumber(link.clicks) }}</span>
                    <span class="stat-label">Klicks</span>
                  </div>
                  <div class="stat">
                    <span class="stat-value">{{ formatNumber(link.uniqueVisitors) }}</span>
                    <span class="stat-label">Besucher</span>
                  </div>
                </div>
                <div class="link-actions">
                  <ion-button size="small" fill="clear" @click="viewLinkDetails(link.id)">
                    <ion-icon name="analytics"></ion-icon>
                  </ion-button>
                  <ion-button size="small" fill="clear" @click="copyLink(link.shortUrl)">
                    <ion-icon name="copy"></ion-icon>
                  </ion-button>
                  <ion-button size="small" fill="clear" @click="editLink(link.id)">
                    <ion-icon name="pencil"></ion-icon>
                  </ion-button>
                </div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Device & Browser Analysis -->
        <div class="chart-row">
          <ion-card>
            <ion-card-header>
              <ion-card-title>Geräte Verteilung</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <canvas ref="deviceChart" height="250"></canvas>
            </ion-card-content>
          </ion-card>

          <ion-card>
            <ion-card-header>
              <ion-card-title>Browser Verteilung</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <canvas ref="browserChart" height="250"></canvas>
            </ion-card-content>
          </ion-card>
        </div>

        <!-- Geographic Distribution -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Geografische Verteilung</ion-card-title>
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
                    <div class="country-clicks">{{ formatNumber(country.clicks) }} Klicks</div>
                  </div>
                  <div class="country-percentage">{{ country.percentage }}%</div>
                </div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- All Links Management -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              Alle Links verwalten
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
                placeholder="Links durchsuchen..."
                @ionInput="filterLinks">
              </ion-searchbar>
              <ion-select v-model="filterStatus" placeholder="Status filtern" @ionChange="filterLinks">
                <ion-select-option value="">Alle Status</ion-select-option>
                <ion-select-option value="active">Aktiv</ion-select-option>
                <ion-select-option value="paused">Pausiert</ion-select-option>
                <ion-select-option value="expired">Abgelaufen</ion-select-option>
              </ion-select>
            </div>
            
            <div class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr>
                    <th @click="sortBy('title')" class="sortable">
                      Titel <ion-icon :name="getSortIcon('title')"></ion-icon>
                    </th>
                    <th>Kurz-URL</th>
                    <th>Ziel-URL</th>
                    <th @click="sortBy('clicks')" class="sortable">
                      Klicks <ion-icon :name="getSortIcon('clicks')"></ion-icon>
                    </th>
                    <th @click="sortBy('created')" class="sortable">
                      Erstellt <ion-icon :name="getSortIcon('created')"></ion-icon>
                    </th>
                    <th>Status</th>
                    <th>Aktionen</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="link in paginatedLinks" :key="link.id">
                    <td>
                      <div class="link-title-cell">
                        <strong>{{ link.title }}</strong>
                        <div class="link-meta">{{ link.description }}</div>
                      </div>
                    </td>
                    <td>
                      <div class="short-url-cell" @click="copyLink(link.shortUrl)">
                        <ion-icon name="link"></ion-icon>
                        {{ link.shortUrl }}
                      </div>
                    </td>
                    <td class="target-url">{{ truncateUrl(link.targetUrl) }}</td>
                    <td class="number-cell">{{ formatNumber(link.clicks) }}</td>
                    <td>{{ formatDate(link.created) }}</td>
                    <td>
                      <ion-badge :color="getStatusColor(link.status)">
                        {{ getStatusText(link.status) }}
                      </ion-badge>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <ion-button size="small" fill="clear" @click="viewLinkDetails(link.id)">
                          <ion-icon name="analytics"></ion-icon>
                        </ion-button>
                        <ion-button size="small" fill="clear" @click="editLink(link.id)">
                          <ion-icon name="pencil"></ion-icon>
                        </ion-button>
                        <ion-button size="small" fill="clear" color="danger" @click="deleteLink(link.id)">
                          <ion-icon name="trash"></ion-icon>
                        </ion-button>
                      </div>
                    </td>
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

      <!-- Create Link Modal -->
      <ion-modal :is-open="createModalOpen" @did-dismiss="createModalOpen = false">
        <ion-header>
          <ion-toolbar>
            <ion-title>Neuen Link erstellen</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="createModalOpen = false">
                <ion-icon name="close"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <form @submit.prevent="createLink">
            <ion-item>
              <ion-label position="stacked">Titel</ion-label>
              <ion-input v-model="newLink.title" placeholder="Link Titel eingeben" required></ion-input>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Beschreibung (optional)</ion-label>
              <ion-textarea v-model="newLink.description" placeholder="Kurze Beschreibung"></ion-textarea>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Ziel-URL</ion-label>
              <ion-input v-model="newLink.targetUrl" placeholder="https://example.com" type="url" required></ion-input>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Custom Slug (optional)</ion-label>
              <ion-input v-model="newLink.slug" placeholder="mein-link"></ion-input>
              <ion-note slot="helper">Leer lassen für automatische Generierung</ion-note>
            </ion-item>
            
            <ion-item>
              <ion-label>Ablaufdatum (optional)</ion-label>
              <ion-datetime v-model="newLink.expiresAt" presentation="date"></ion-datetime>
            </ion-item>
            
            <ion-button type="submit" expand="block" class="ion-margin-top">
              Link erstellen
            </ion-button>
          </form>
        </ion-content>
      </ion-modal>

      <!-- Link Details Modal -->
      <ion-modal :is-open="detailsModalOpen" @did-dismiss="detailsModalOpen = false">
        <ion-header>
          <ion-toolbar>
            <ion-title>Link Details: {{ selectedLink?.title }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="detailsModalOpen = false">
                <ion-icon name="close"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding" v-if="selectedLink">
          <div class="link-details">
            <div class="detail-section">
              <h3>Link Information</h3>
              <div class="detail-item">
                <strong>Kurz-URL:</strong> {{ selectedLink.shortUrl }}
                <ion-button size="small" fill="clear" @click="copyLink(selectedLink.shortUrl)">
                  <ion-icon name="copy"></ion-icon>
                </ion-button>
              </div>
              <div class="detail-item">
                <strong>Ziel-URL:</strong> {{ selectedLink.targetUrl }}
              </div>
              <div class="detail-item">
                <strong>Erstellt:</strong> {{ formatDate(selectedLink.created) }}
              </div>
              <div class="detail-item">
                <strong>Status:</strong> 
                <ion-badge :color="getStatusColor(selectedLink.status)">
                  {{ getStatusText(selectedLink.status) }}
                </ion-badge>
              </div>
            </div>
            
            <div class="detail-section">
              <h3>Statistiken</h3>
              <div class="stats-grid">
                <div class="stat-card">
                  <div class="stat-value">{{ formatNumber(selectedLink.clicks) }}</div>
                  <div class="stat-label">Gesamt Klicks</div>
                </div>
                <div class="stat-card">
                  <div class="stat-value">{{ formatNumber(selectedLink.uniqueVisitors) }}</div>
                  <div class="stat-label">Eindeutige Besucher</div>
                </div>
                <div class="stat-card">
                  <div class="stat-value">{{ formatNumber(selectedLink.clicksToday) }}</div>
                  <div class="stat-label">Heute</div>
                </div>
                <div class="stat-card">
                  <div class="stat-value">{{ formatNumber(selectedLink.clicksThisWeek) }}</div>
                  <div class="stat-label">Diese Woche</div>
                </div>
              </div>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { Chart, LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, BarController, BarElement, DoughnutController, ArcElement, Legend, Tooltip } from 'chart.js';
Chart.register(LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, BarController, BarElement, DoughnutController, ArcElement, Legend, Tooltip);
import countryService from '@/services/countryService';

export default {
  name: 'LinkTrackerDashboard',
  data() {
    return {
      analytics: [],
      links: [],
      filteredLinks: [],
      stats: {},
      loading: true,
      error: null,
      charts: {},
      selectedPeriod: '30',
      searchTerm: '',
      filterStatus: '',
      sortField: 'created',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 20,
      
      // Modals
      createModalOpen: false,
      detailsModalOpen: false,
      selectedLink: null,
      
      // New Link Form
      newLink: {
        title: '',
        description: '',
        targetUrl: '',
        slug: '',
        expiresAt: null
      }
    };
  },
  
  computed: {
    topLinks() {
      return this.links
        .sort((a, b) => b.clicks - a.clicks)
        .slice(0, 5);
    },
    
    topCountries() {
      const countryData = {};
      this.analytics.forEach(item => {
        if (!countryData[item.country]) {
          countryData[item.country] = 0;
        }
        countryData[item.country] += 1;
      });
      
      const total = Object.values(countryData).reduce((sum, count) => sum + count, 0);
      
      return Object.entries(countryData)
        .map(([code, clicks]) => ({
          code,
          clicks,
          percentage: Math.round((clicks / total) * 100)
        }))
        .sort((a, b) => b.clicks - a.clicks)
        .slice(0, 10);
    },
    
    paginatedLinks() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredLinks.slice(start, end);
    },
    
    totalPages() {
      return Math.ceil(this.filteredLinks.length / this.itemsPerPage);
    }
  },
  
  mounted() {
    this.loadAnalytics();
    this.loadLinks();
  },
  
  methods: {
    async loadAnalytics() {
      this.loading = true;
      this.error = null;
      
      try {
        const res = await this.$axios.get(`link_tracker_api.php?action=analytics&period=${this.selectedPeriod}&project=` + this.$route.params.project);
        this.analytics = res.data.analytics || [];
        this.calculateStats();
        
        this.$nextTick(() => {
          this.renderAllCharts();
        });
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },
    
    async loadLinks() {
      try {
        const res = await this.$axios.get(`link_tracker_api.php?action=links&project=` + this.$route.params.project);
        this.links = res.data.links || [];
        this.filteredLinks = [...this.links];
        this.sortLinks();
      } catch (e) {
        console.error('Error loading links:', e);
      }
    },
    
    calculateStats() {
      if (!this.analytics.length) return;
      
      const totalClicks = this.analytics.length;
      const uniqueVisitors = new Set(this.analytics.map(item => item.ip_address)).size;
      const activeLinks = this.links.filter(link => link.status === 'active').length;
      const totalLinks = this.links.length;
      
      const daysInPeriod = parseInt(this.selectedPeriod);
      const avgClicksPerDay = Math.round(totalClicks / daysInPeriod);
      
      // Calculate trends (mock for now)
      const clicksTrend = Math.floor(Math.random() * 30) - 15;
      const visitorsTrend = Math.floor(Math.random() * 20) - 10;
      
      // Calculate click rate
      const impressions = totalClicks * 2; // Mock data
      const clickRate = impressions > 0 ? ((totalClicks / impressions) * 100) : 0;
      
      this.stats = {
        totalClicks,
        uniqueVisitors,
        activeLinks,
        totalLinks,
        avgClicksPerDay,
        clickRate: Math.round(clickRate * 100) / 100,
        clicksTrend,
        visitorsTrend
      };
    },
    
    renderAllCharts() {
      this.destroyAllCharts();
      this.renderTimelineChart();
      this.renderCountryChart();
      this.renderDeviceChart();
      this.renderBrowserChart();
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
      
      // Group clicks by date
      const dateData = {};
      this.analytics.forEach(item => {
        const date = item.clicked_at.split(' ')[0];
        dateData[date] = (dateData[date] || 0) + 1;
      });
      
      const sortedDates = Object.keys(dateData).sort();
      const data = sortedDates.map(date => dateData[date]);
      
      this.charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
          labels: sortedDates.map(date => this.formatDate(date)),
          datasets: [{
            label: 'Klicks',
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
            data: topCountriesData.map(c => c.clicks),
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
    
    renderDeviceChart() {
      const ctx = this.$refs.deviceChart;
      if (!ctx) return;
      
      const deviceData = {};
      this.analytics.forEach(item => {
        const device = this.getDeviceType(item.user_agent);
        deviceData[device] = (deviceData[device] || 0) + 1;
      });
      
      this.charts.device = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: Object.keys(deviceData),
          datasets: [{
            data: Object.values(deviceData),
            backgroundColor: ['#3880ff', '#2fdf75', '#ffce00', '#ff6b6b']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    },
    
    renderBrowserChart() {
      const ctx = this.$refs.browserChart;
      if (!ctx) return;
      
      const browserData = {};
      this.analytics.forEach(item => {
        const browser = this.getBrowserName(item.user_agent);
        browserData[browser] = (browserData[browser] || 0) + 1;
      });
      
      this.charts.browser = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: Object.keys(browserData),
          datasets: [{
            label: 'Klicks',
            data: Object.values(browserData),
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
    
    async createLink() {
      if (!this.newLink.title || !this.newLink.targetUrl) {
        return;
      }
      
      try {
        const response = await this.$axios.post('link_tracker_api.php?project=' + this.$route.params.project, {
          action: 'create_link',
          ...this.newLink
        });
        
        if (response.data.success) {
          this.createModalOpen = false;
          this.resetNewLinkForm();
          this.loadLinks();
          // Show success toast
        }
      } catch (e) {
        console.error('Error creating link:', e);
      }
    },
    
    resetNewLinkForm() {
      this.newLink = {
        title: '',
        description: '',
        targetUrl: '',
        slug: '',
        expiresAt: null
      };
    },
    
    openCreateLinkModal() {
      this.createModalOpen = true;
    },
    
    async viewLinkDetails(linkId) {
      try {
        const response = await this.$axios.get(`link_tracker_api.php?action=link_details&id=${linkId}&project=` + this.$route.params.project);
        this.selectedLink = response.data.link;
        this.detailsModalOpen = true;
      } catch (e) {
        console.error('Error loading link details:', e);
      }
    },
    
    editLink(linkId) {
      // Implementation for edit functionality
      console.log('Edit link:', linkId);
    },
    
    async deleteLink(linkId) {
      if (confirm('Möchten Sie diesen Link wirklich löschen?')) {
        try {
          await this.$axios.post('link_tracker_api.php?project=' + this.$route.params.project, {
            action: 'delete_link',
            id: linkId
          });
          this.loadLinks();
        } catch (e) {
          console.error('Error deleting link:', e);
        }
      }
    },
    
    copyLink(url) {
      navigator.clipboard.writeText(url).then(() => {
        // Show success toast
      });
    },
    
    filterLinks() {
      let filtered = [...this.links];
      
      if (this.searchTerm) {
        filtered = filtered.filter(link => 
          link.title.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
          link.shortUrl.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
          link.targetUrl.toLowerCase().includes(this.searchTerm.toLowerCase())
        );
      }
      
      if (this.filterStatus) {
        filtered = filtered.filter(link => link.status === this.filterStatus);
      }
      
      this.filteredLinks = filtered;
      this.sortLinks();
      this.currentPage = 1;
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.sortLinks();
    },
    
    sortLinks() {
      this.filteredLinks.sort((a, b) => {
        let aVal = a[this.sortField];
        let bVal = b[this.sortField];
        
        if (this.sortField === 'clicks') {
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
        ['Titel', 'Kurz-URL', 'Ziel-URL', 'Klicks', 'Erstellt', 'Status'],
        ...this.filteredLinks.map(link => [
          link.title, link.shortUrl, link.targetUrl, link.clicks, link.created, link.status
        ])
      ].map(row => row.join(',')).join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `link_tracker_${new Date().toISOString().split('T')[0]}.csv`;
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
      return countryService.getCountryName(code);
    },

    getCountryFlag(code) {
      return countryService.getCountryFlag(code);
    },
    
    truncateUrl(url) {
      return url.length > 50 ? url.substring(0, 50) + '...' : url;
    },
    
    getStatusColor(status) {
      const colors = {
        'active': 'success',
        'paused': 'warning',
        'expired': 'danger'
      };
      return colors[status] || 'medium';
    },
    
    getStatusText(status) {
      const texts = {
        'active': 'Aktiv',
        'paused': 'Pausiert',
        'expired': 'Abgelaufen'
      };
      return texts[status] || status;
    },
    
    getDeviceType(userAgent) {
      if (/Mobile|Android|iPhone|iPad/.test(userAgent)) return 'Mobile';
      if (/Tablet/.test(userAgent)) return 'Tablet';
      return 'Desktop';
    },
    
    getBrowserName(userAgent) {
      if (userAgent.includes('Chrome')) return 'Chrome';
      if (userAgent.includes('Firefox')) return 'Firefox';
      if (userAgent.includes('Safari')) return 'Safari';
      if (userAgent.includes('Edge')) return 'Edge';
      return 'Other';
    }
  }
};
</script>

<style scoped>
/* Reuse most styles from the AppStore module but add specific styles for link tracker */
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

.quick-actions {
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

.links-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.link-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border: 1px solid var(--ion-color-light);
  border-radius: 8px;
  transition: all 0.2s ease;
}

.link-item:hover {
  background: var(--ion-color-step-50);
}

.link-info {
  flex: 1;
}

.link-title {
  font-weight: 600;
  margin-bottom: 5px;
}

.link-url {
  color: var(--ion-color-medium);
  font-size: 0.9em;
}

.link-stats {
  display: flex;
  gap: 20px;
  margin: 0 20px;
}

.stat {
  text-align: center;
}

.stat-value {
  display: block;
  font-weight: bold;
  color: var(--ion-color-primary);
}

.stat-label {
  font-size: 0.8em;
  color: var(--ion-color-medium);
}

.link-actions {
  display: flex;
  gap: 5px;
}

.chart-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
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

.country-clicks {
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

.link-title-cell strong {
  display: block;
}

.link-meta {
  font-size: 0.8em;
  color: var(--ion-color-medium);
}

.short-url-cell {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--ion-color-primary);
}

.short-url-cell ion-icon {
  margin-right: 5px;
}

.short-url-cell:hover {
  text-decoration: underline;
}

.target-url {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.number-cell {
  text-align: right;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
}

.loading-container,
.error-container {
  text-align: center;
  padding: 40px;
}

.link-details {
  padding: 20px 0;
}

.detail-section {
  margin-bottom: 30px;
}

.detail-section h3 {
  margin-bottom: 15px;
  color: var(--ion-color-primary);
}

.detail-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.detail-item strong {
  min-width: 120px;
  margin-right: 10px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 15px;
}

.stat-card {
  text-align: center;
  padding: 15px;
  background: var(--ion-color-step-50);
  border-radius: 8px;
}

.stat-card .stat-value {
  font-size: 1.5em;
  font-weight: bold;
  color: var(--ion-color-primary);
}

.stat-card .stat-label {
  font-size: 0.9em;
  color: var(--ion-color-medium);
  margin-top: 5px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-row {
    grid-template-columns: 1fr;
  }
  
  .table-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .link-item {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .link-stats {
    justify-content: space-around;
    margin: 10px 0;
  }
  
  .link-actions {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>