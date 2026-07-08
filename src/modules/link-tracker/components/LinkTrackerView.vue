<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="link-outline" title="Link Tracker" bg="transparent"/>

      <div class="page-container">
        <div class="action-bar">
          <div class="action-group-left">
            <ActionButton variant="primary" icon="add-circle" @click="showCreateForm = !showCreateForm">
              Neuer Link
            </ActionButton>
            <ActionButton icon="refresh" @click="loadLinks">
              Aktualisieren
            </ActionButton>
          </div>

          <div class="action-group-right">
            <div class="period-selector">
              <button v-for="period in periods" :key="period.value"
                :class="['period-btn', { active: selectedPeriod === period.value }]"
                @click="selectedPeriod = period.value; loadAnalytics()">
                {{ period.label }}
              </button>
            </div>
            <div class="toggle-container">
              <label class="toggle-label">
                <input type="checkbox" v-model="excludeBots" @change="loadLinks" class="toggle-input">
                <span class="toggle-slider"></span>
                Bots ausfiltern
              </label>
            </div>
          </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">{{ totalClicks }}</div>
            <div class="stat-label">Gesamt Klicks</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ uniqueVisitors }}</div>
            <div class="stat-label">Unique Besucher</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ links.length }}</div>
            <div class="stat-label">Aktive Links</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ avgClicksPerLink }}</div>
            <div class="stat-label">Ø Klicks/Link</div>
          </div>
        </div>

        <div class="analytics-section" v-if="analyticsLoaded">
          <div class="data-card">
            <div class="card-header">
              <h3>Klicks Timeline</h3>
            </div>
            <div class="chart-container">
              <canvas ref="timelineChart"></canvas>
            </div>
          </div>

          <div class="analytics-grid">
            <div class="data-card">
              <div class="card-header">
                <h3>Länder</h3>
              </div>
              <div class="analytics-list">
                <div v-for="country in analytics.countries" :key="country.country" class="analytics-item">
                  <span class="country-flag">{{ getCountryFlag(country.country) }}</span>
                  <span class="analytics-name">{{ getCountryName(country.country) }}</span>
                  <span class="analytics-count">{{ country.count }}</span>
                </div>
              </div>
            </div>

            <div class="data-card">
              <div class="card-header">
                <h3>Geräte</h3>
              </div>
              <div class="chart-container">
                <canvas ref="deviceChart"></canvas>
              </div>
            </div>

            <div class="data-card">
              <div class="card-header">
                <h3>Browser</h3>
              </div>
              <div class="analytics-list">
                <div v-for="browser in analytics.browsers" :key="browser.browser" class="analytics-item">
                  <ion-icon :name="getBrowserIcon(browser.browser)"></ion-icon>
                  <span class="analytics-name">{{ browser.browser }}</span>
                  <span class="analytics-count">{{ browser.count }}</span>
                </div>
              </div>
            </div>

            <div class="data-card">
              <div class="card-header">
                <h3>Betriebssysteme</h3>
              </div>
              <div class="analytics-list">
                <div v-for="platform in analytics.platforms" :key="platform.platform" class="analytics-item">
                  <ion-icon :name="getPlatformIcon(platform.platform)"></ion-icon>
                  <span class="analytics-name">{{ platform.platform }}</span>
                  <span class="analytics-count">{{ platform.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Deine Links</h3>
              <p class="entry-count">{{ filteredLinks.length }} Links gefunden</p>
            </div>
            <div class="header-right">
              <div class="search-box">
                <ion-icon name="search"></ion-icon>
                <input type="text" placeholder="Links durchsuchen..." v-model="searchTerm" @input="filterLinks" />
              </div>
            </div>
          </div>

          <div class="table-wrapper">
            <div class="modern-table">
              <div class="table-header">
                <div class="header-cell" @click="sortBy(0)">
                  <span class="header-text">Titel</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon(0)"
                      :class="sortColumn === 0 ? 'sort-active' : 'sort-default'"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy(1)">
                  <span class="header-text">Kurz-URL</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon(1)"
                      :class="sortColumn === 1 ? 'sort-active' : 'sort-default'"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy(2)">
                  <span class="header-text">Ziel-URL</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon(2)"
                      :class="sortColumn === 2 ? 'sort-active' : 'sort-default'"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy(3)">
                  <span class="header-text">Besuche</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon(3)"
                      :class="sortColumn === 3 ? 'sort-active' : 'sort-default'"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy(4)">
                  <span class="header-text">Erstellt</span>
                  <div class="sort-indicator">
                    <ion-icon :name="getSortIcon(4)"
                      :class="sortColumn === 4 ? 'sort-active' : 'sort-default'"></ion-icon>
                  </div>
                </div>
                <div class="actions-header">Aktionen</div>
              </div>

              <div class="table-body" v-if="filteredLinks.length > 0">
                <div class="table-row" v-for="link in paginatedLinks" :key="link.id">
                  <div class="table-cell">
                    <div class="cell-content">{{ link.title }}</div>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content" @click="copyToClipboard(link.short_url)"
                      style="cursor: pointer; color: var(--primary-color);">
                      {{ link.short_url }}
                    </div>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content">{{ truncateUrl(link.target_url) }}</div>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content">
                      {{ link.visits || 0 }}
                      <small v-if="link.unique_visitors">({{ link.unique_visitors }} unique)</small>
                    </div>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content">{{ formatDate(link.created_at) }}</div>
                  </div>
                  <div class="actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn analytics-btn" @click="viewAnalytics(link.id)" title="Analytics">
                        <ion-icon name="analytics"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteLink(link.id)" title="Löschen">
                        <ion-icon name="trash"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <EmptyState v-if="filteredLinks.length === 0" icon="link" title="Keine Links gefunden"
              description="Erstelle deinen ersten Link mit dem Button oben." />
          </div>
        </div>

        <div class="form-section" :class="{ 'form-visible': showCreateForm }">
          <div class="form-card">
            <div class="form-header">
              <h3>Neuen Link erstellen</h3>
              <button class="close-form-btn" @click="showCreateForm = false">
                <ion-icon name="close"></ion-icon>
              </button>
            </div>
            <div class="form-content">
              <form @submit.prevent="createLink" class="modern-form">
                <div class="form-group">
                  <label class="form-label">Titel *</label>
                  <input type="text" class="modern-input" v-model="newLink.title" required
                    placeholder="z.B. Meine Webseite" />
                </div>

                <div class="form-group">
                  <label class="form-label">Ziel-URL *</label>
                  <input type="url" class="modern-input" v-model="newLink.target_url" required
                    placeholder="https://example.com" />
                </div>

                <div class="form-group">
                  <label class="form-label">Custom Slug (optional)</label>
                  <input type="text" class="modern-input" v-model="newLink.custom_slug" placeholder="mein-link" />
                </div>

                <div class="form-actions">
                  <ActionButton type="button" @click="showCreateForm = false">
                    Abbrechen
                  </ActionButton>
                  <ActionButton type="submit" variant="primary" :disabled="!newLink.title || !newLink.target_url">
                    Link erstellen
                  </ActionButton>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import ActionButton from "@/components/ActionButton.vue";
import EmptyState from "@/components/EmptyState.vue";
import CountryService from "@/services/countryService.js"

import {
  Chart, registerables
} from 'chart.js';

Chart.register(...registerables);

export default {
  name: "LinkTrackerView",
  components: {
    SiteTitle,
    ActionButton,
    EmptyState
  },
  data() {
    return {
      links: [],
      filteredLinks: [],
      analytics: {
        countries: [],
        devices: [],
        browsers: [],
        platforms: [],
        timeline: []
      },
      analyticsLoaded: false,
      selectedPeriod: 30,
      periods: [
        { value: 7, label: '7 Tage' },
        { value: 30, label: '30 Tage' },
        { value: 90, label: '90 Tage' }
      ],
      excludeBots: true,
      searchTerm: '',
      showCreateForm: false,
      sortColumn: null,
      sortDirection: 'asc',
      currentPage: 1,
      itemsPerPage: 20,
      newLink: {
        title: '',
        target_url: '',
        custom_slug: ''
      },
      charts: {}
    };
  },

  computed: {
    totalClicks() {
      return this.links.reduce((sum, link) => sum + (parseInt(link.visits) || 0), 0);
    },

    uniqueVisitors() {
      return this.links.reduce((sum, link) => sum + (parseInt(link.unique_visitors) || 0), 0);
    },

    avgClicksPerLink() {
      if (this.links.length === 0) return 0;
      return Math.round(this.totalClicks / this.links.length);
    },

    paginatedLinks() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredLinks.slice(start, end);
    }
  },

  mounted() {
    this.loadLinks();
    this.loadAnalytics();
  },

  methods: {
    async loadLinks() {
      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          getLinks: true,
          project: this.$route.params.project,
          exclude_bots: this.excludeBots
        }));

        if (response.data.success) {
          this.links = response.data.links;
          this.filteredLinks = [...this.links];
        }
      } catch (error) {
        console.error('Error loading links:', error);
      }
    },

    async createLink() {
      if (!this.newLink.title || !this.newLink.target_url) return;

      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          createLink: true,
          project: this.$route.params.project,
          title: this.newLink.title,
          target_url: this.newLink.target_url,
          custom_slug: this.newLink.custom_slug
        }));

        if (response.data.success) {
          this.showCreateForm = false;
          this.resetForm();
          this.loadLinks();
        } else {
          alert(response.data.message || 'Fehler beim Erstellen des Links');
        }
      } catch (error) {
        console.error('Error creating link:', error);
        alert('Fehler beim Erstellen des Links');
      }
    },

    async deleteLink(linkId) {
      if (!confirm('Link wirklich löschen?')) return;

      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          deleteLink: true,
          project: this.$route.params.project,
          link_id: linkId
        }));

        if (response.data.success) {
          this.loadLinks();
        } else {
          alert(response.data.message || 'Fehler beim Löschen');
        }
      } catch (error) {
        console.error('Error deleting link:', error);
        alert('Fehler beim Löschen des Links');
      }
    },

    filterLinks() {
      if (!this.searchTerm.trim()) {
        this.filteredLinks = [...this.links];
        return;
      }

      const searchLower = this.searchTerm.toLowerCase();
      this.filteredLinks = this.links.filter(link =>
        link.title.toLowerCase().includes(searchLower) ||
        link.short_url.toLowerCase().includes(searchLower) ||
        link.target_url.toLowerCase().includes(searchLower)
      );
    },

    sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }

      this.filteredLinks.sort((a, b) => {
        let aVal, bVal;

        switch (columnIndex) {
          case 0: aVal = a.title; bVal = b.title; break;
          case 1: aVal = a.short_url; bVal = b.short_url; break;
          case 2: aVal = a.target_url; bVal = b.target_url; break;
          case 3: aVal = parseInt(a.visits) || 0; bVal = parseInt(b.visits) || 0; break;
          case 4: aVal = new Date(a.created_at); bVal = new Date(b.created_at); break;
          default: return 0;
        }

        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1;
        } else {
          return aVal < bVal ? 1 : -1;
        }
      });
    },

    getSortIcon(columnIndex) {
      if (this.sortColumn !== columnIndex) return 'swap-vertical';
      return this.sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
    },

    resetForm() {
      this.newLink = {
        title: '',
        target_url: '',
        custom_slug: ''
      };
    },

    copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        console.log('Link copied to clipboard');
      }).catch(err => {
        console.error('Failed to copy: ', err);
      });
    },

    truncateUrl(url) {
      return url.length > 50 ? url.substring(0, 50) + '...' : url;
    },

    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('de-DE');
    },

    viewAnalytics(linkId) {
      this.$router.push({
        name: 'LinkAnalytics',
        params: {
          project: this.$route.params.project,
          linkId: linkId
        }
      });
    },

    async loadAnalytics() {
      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          getDetailedAnalytics: true,
          project: this.$route.params.project,
          period: this.selectedPeriod,
          exclude_bots: this.excludeBots
        }));

        if (response.data.success) {
          this.analytics = response.data;
          this.analyticsLoaded = true;
          this.$nextTick(() => {
            this.renderCharts();
          });
        }
      } catch (error) {
        console.error('Error loading analytics:', error);
      }
    },

    renderCharts() {
      this.renderTimelineChart();
      this.renderDeviceChart();
    },

    renderTimelineChart() {
      const ctx = this.$refs.timelineChart;
      if (!ctx || !this.analytics.timeline) return;

      if (this.charts.timeline) {
        this.charts.timeline.destroy();
      }

      this.charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
          labels: this.analytics.timeline.map(item => this.formatDate(item.date)),
          datasets: [{
            label: 'Klicks',
            data: this.analytics.timeline.map(item => item.clicks),
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.1)',
            tension: 0.4,
            fill: true
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

    renderDeviceChart() {
      const ctx = this.$refs.deviceChart;
      if (!ctx || !this.analytics.devices) return;

      if (this.charts.device) {
        this.charts.device.destroy();
      }

      this.charts.device = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: this.analytics.devices.map(item => item.device_type),
          datasets: [{
            data: this.analytics.devices.map(item => item.count),
            backgroundColor: ['#f97316', '#059669', '#d97706', '#dc2626']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    },

    getCountryName(code) {
      return CountryService.getCountryName(code)
    },

    getCountryFlag(code) {
      return CountryService.getCountryFlag(code) || '🌍'
    },

    getBrowserIcon(browser) {
      const icons = {
        'Chrome': 'logo-chrome',
        'Firefox': 'logo-firefox',
        'Safari': 'logo-safari',
        'Edge': 'logo-edge',
        'Opera': 'logo-opera',
        'Other': 'globe'
      };
      return icons[browser] || 'globe';
    },

    getPlatformIcon(platform) {
      const icons = {
        'Windows': 'logo-windows',
        'macOS': 'logo-apple',
        'Linux': 'logo-tux',
        'Android': 'logo-android',
        'iOS': 'logo-apple',
        'Other': 'desktop'
      };
      return icons[platform] || 'desktop';
    }
  }
};
</script>

<style scoped>
ion-content.modern-content {
  --background: var(--background);
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  text-align: center;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: var(--primary-color);
  margin-bottom: 8px;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

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
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

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

.actions-header {
  flex: 0 0 120px;
  justify-content: center;
  cursor: default;
  padding: 16px;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.actions-header:hover {
  background: var(--background);
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

.actions-cell {
  flex: 0 0 120px;
  justify-content: center;
  padding: 12px 16px;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

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
  font-size: 14px;
}

.analytics-btn {
  background: #f0f9ff;
  color: var(--primary-color);
}

.analytics-btn:hover {
  background: #fff7ed;
  transform: scale(1.05);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
  transform: scale(1.05);
}

.form-section {
  position: fixed;
  top: 0;
  right: -600px;
  width: 600px;
  height: 100vh;
  background: var(--surface);
  box-shadow: var(--shadow-lg);
  transition: right 0.3s ease;
  z-index: 1000;
  border-left: 1px solid var(--border);
}

.form-section.form-visible {
  right: 0;
}

.form-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.form-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.close-form-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: var(--border);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-form-btn:hover {
  background: var(--text-muted);
  color: var(--surface);
}

.form-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

.modern-form {
  width: 100%;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

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
}

.period-btn:hover {
  background: var(--background);
}

.period-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.toggle-container {
  display: flex;
  align-items: center;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 500;
}

.toggle-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.toggle-slider {
  position: relative;
  width: 44px;
  height: 24px;
  background: var(--border);
  border-radius: 12px;
  transition: all 0.2s ease;
}

.toggle-slider:before {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-input:checked + .toggle-slider {
  background: var(--primary-color);
}

.toggle-input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.analytics-section {
  margin-top: 32px;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-top: 24px;
}

.chart-container {
  padding: 20px;
  height: 300px;
  position: relative;
}

.analytics-list {
  padding: 20px;
  max-height: 300px;
  overflow-y: auto;
}

.analytics-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
  gap: 12px;
}

.analytics-item:last-child {
  border-bottom: none;
}

.analytics-name {
  flex: 1;
  font-weight: 500;
  color: var(--text-primary);
}

.analytics-count {
  font-weight: 600;
  color: var(--primary-color);
  background: var(--background);
  padding: 4px 8px;
  border-radius: var(--radius);
  font-size: 12px;
}

.country-flag {
  font-size: 18px;
  width: 24px;
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
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
  }

  .period-selector {
    flex-wrap: wrap;
    justify-content: center;
  }

  .toggle-container {
    justify-content: center;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .search-box input {
    min-width: 100%;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .form-section {
    width: 100%;
    right: -100%;
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .modern-table {
    min-width: 600px;
  }

  .cell-content {
    max-width: 80px;
  }
}
</style>