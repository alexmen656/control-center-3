<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle :icon="'analytics-outline'" :title="`Analytics: ${linkData.title}`" bg="transparent"/>

      <div class="page-container">
        <!-- Back Button & Link Info -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn" @click="$router.go(-1)">
              <ion-icon name="arrow-back"></ion-icon>
              Zurück
            </button>
            <div class="link-info">
              <h3>{{ linkData.title }}</h3>
              <p class="link-url">{{ linkData.short_url }}</p>
            </div>
          </div>

          <div class="action-group-right">
            <div class="filter-group">
              <div class="bot-filter">
                <ion-toggle v-model="excludeBots" @ionChange="loadAnalytics()"></ion-toggle>
                <label>Bots ausfiltern</label>
              </div>
              <div class="period-selector">
                <button v-for="period in periods" :key="period.value"
                  :class="['period-btn', { active: selectedPeriod === period.value }]"
                  @click="selectedPeriod = period.value; loadAnalytics()">
                  {{ period.label }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-value">{{ linkStats.totalClicks }}</div>
            <div class="stat-label">Gesamt Klicks</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ linkStats.uniqueVisitors }}</div>
            <div class="stat-label">Unique Besucher</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ linkStats.clicksToday }}</div>
            <div class="stat-label">Heute</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ linkStats.clicksThisWeek }}</div>
            <div class="stat-label">Diese Woche</div>
          </div>
          <div v-if="botStats.total_visits > 0" class="stat-card bot-stats">
            <div class="stat-value">{{ botStats.bot_visits }}</div>
            <div class="stat-label">Bot Besuche</div>
          </div>
          <div v-if="botStats.total_visits > 0" class="stat-card bot-stats">
            <div class="stat-value">{{ Math.round((botStats.bot_visits / botStats.total_visits) * 100) }}%</div>
            <div class="stat-label">Bot Anteil</div>
          </div>
        </div>

        <!-- Timeline Chart -->
        <div class="data-card">
          <div class="card-header">
            <h3>Klicks Timeline</h3>
          </div>
          <div class="chart-container">
            <canvas ref="timelineChart"></canvas>
          </div>
        </div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
          <!-- Countries -->
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
              <div v-if="!analytics.countries.length" class="no-data">
                Keine Daten verfügbar
              </div>
            </div>
          </div>

          <!-- Devices -->
          <div class="data-card">
            <div class="card-header">
              <h3>Geräte</h3>
            </div>
            <div class="analytics-list">
              <div v-for="device in analytics.devices" :key="device.device_type" class="analytics-item">
                <ion-icon :name="getDeviceIcon(device.device_type)"></ion-icon>
                <span class="analytics-name">{{ device.device_type }}</span>
                <span class="analytics-count">{{ device.count }}</span>
              </div>
              <div v-if="!analytics.devices.length" class="no-data">
                Keine Daten verfügbar
              </div>
            </div>
          </div>

          <!-- Browsers -->
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
              <div v-if="!analytics.browsers.length" class="no-data">
                Keine Daten verfügbar
              </div>
            </div>
          </div>

          <!-- Platforms -->
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
              <div v-if="!analytics.platforms.length" class="no-data">
                Keine Daten verfügbar
              </div>
            </div>
          </div>
        </div>

        <!-- Custom Domains -->
        <div class="data-card">
          <div class="card-header">
            <h3>Custom Domains</h3>
            <button class="action-btn primary" @click="showDomainForm = !showDomainForm">
              <ion-icon name="add"></ion-icon>
              Domain hinzufügen
            </button>
          </div>

          <!-- Domain Form -->
          <div v-if="showDomainForm" class="domain-form">
            <form @submit.prevent="createCustomDomain">
              <div class="form-row">
                <input v-model="newDomain.subdomain" type="text" placeholder="subdomain" class="domain-input"
                  required />
                <span class="domain-separator">.</span>
                <span class="base-domain">{{ baseDomain }}</span>
                <button type="submit" class="action-btn primary">Erstellen</button>
              </div>
            </form>
          </div>

          <!-- Domain List -->
          <div class="domain-list">
            <div v-for="domain in customDomains" :key="domain.id" class="domain-item">
              <div class="domain-url">
                <ion-icon name="link"></ion-icon>
                <a :href="`https://${domain.full_domain}`" target="_blank">{{ domain.full_domain }}</a>
              </div>
              <div class="domain-actions">
                <button class="icon-btn delete-btn" @click="deleteCustomDomain(domain.id)">
                  <ion-icon name="trash"></ion-icon>
                </button>
              </div>
            </div>
            <div v-if="!customDomains.length" class="no-data">
              Keine custom Domains erstellt
            </div>
          </div>
        </div>

        <!-- Recent Visits Table -->
        <div class="data-card">
          <div class="card-header">
            <h3>Letzte Besuche</h3>
          </div>
          <div class="table-wrapper">
            <div class="modern-table">
              <div class="table-header">
                <div class="header-cell">Zeit</div>
                <div class="header-cell">Land</div>
                <div class="header-cell">Gerät</div>
                <div class="header-cell">Browser</div>
                <div class="header-cell">Referer</div>
                <div class="header-cell">Bot</div>
                <!--<div class="header-cell">Angefragt</div>-->
              </div>
              <div class="table-body">
                                <div class="table-row" v-for="visit in recentVisits" :key="visit.id">
                  <div class="table-cell">{{ formatDateTime(visit.visited_at) }}</div>
                  <div class="table-cell">
                    <span class="country-flag">{{ getCountryFlag(visit.country) }}</span>
                    {{ getCountryName(visit.country) }}
                  </div>
                  <div class="table-cell">{{ visit.device_type }}</div>
                  <div class="table-cell">{{ visit.browser }}</div>
                  <div class="table-cell">
                    <span v-if="visit.referer" class="referer-link" @click="openReferer(visit.referer)">
                      {{ formatReferer(visit.referer) }}
                    </span>
                    <span v-else class="no-referer">Kein Referer</span>
                  </div>
                  <div class="table-cell">
                    <span :class="['bot-badge', { 'is-bot': Number(visit.is_bot) == 1 }]">
                      {{ Number(visit.is_bot) == 1 ? 'Bot' : 'Human' }}
                    </span>
                  </div>
                <!-- <div class="table-cell">
                    <span class="requested-url">{{ visit.requested_url || '/' }}</span>
                  </div>--> 
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import CountryService from "@/services/countryService.js"
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

export default {
  name: "LinkAnalyticsView",
  components: {
    SiteTitle
  },
  data() {
    return {
      linkData: {},
      linkStats: {
        totalClicks: 0,
        uniqueVisitors: 0,
        clicksToday: 0,
        clicksThisWeek: 0
      },
      analytics: {
        countries: [],
        devices: [],
        browsers: [],
        platforms: [],
        timeline: []
      },
      botStats: {
        bot_visits: 0,
        human_visits: 0,
        total_visits: 0
      },
      recentVisits: [],
      customDomains: [],
      showDomainForm: false,
      baseDomain: '',
      newDomain: {
        subdomain: ''
      },
      selectedPeriod: 30,
      excludeBots: false,
      periods: [
        { value: 7, label: '7 Tage' },
        { value: 30, label: '30 Tage' },
        { value: 90, label: '90 Tage' }
      ],
      charts: {}
    };
  },

  mounted() {
    this.loadLinkData();
    this.loadAnalytics();
    this.loadCustomDomains();
    this.getBaseDomain();
  },

  methods: {
    async loadLinkData() {
      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          getLinkDetails: true,
          project: this.$route.params.project,
          link_id: this.$route.params.linkId
        }));

        if (response.data.success) {
          this.linkData = response.data.link;
          this.linkStats = {
            totalClicks: response.data.link.visits || 0,
            uniqueVisitors: response.data.link.unique_visitors || 0,
            clicksToday: response.data.link.clicks_today || 0,
            clicksThisWeek: response.data.link.clicks_this_week || 0
          };
        }
      } catch (error) {
        console.error('Error loading link data:', error);
      }
    },

    async loadAnalytics() {
      try {
        const response = await this.$axios.post('link_tracker_api.php', this.$qs.stringify({
          getLinkAnalytics: true,
          project: this.$route.params.project,
          link_id: this.$route.params.linkId,
          period: this.selectedPeriod,
          exclude_bots: this.excludeBots
        }));

        if (response.data.success) {
          this.analytics = response.data;
          this.recentVisits = response.data.recent_visits || [];
          this.botStats = response.data.bot_stats || { bot_visits: 0, human_visits: 0, total_visits: 0 };
          this.$nextTick(() => {
            this.renderTimelineChart();
          });
        }
      } catch (error) {
        console.error('Error loading analytics:', error);
      }
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
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
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

    // Helper methods
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('de-DE');
    },

    formatDateTime(dateTimeStr) {
      return new Date(dateTimeStr).toLocaleString('de-DE');
    },

    formatReferer(referer) {
      try {
        const url = new URL(referer);
        return url.hostname;
      } catch (e) {
        return referer.substring(0, 30) + '...';
      }
    },

    getCountryName(code) {
      /*const countries = {
        'DE': 'Deutschland', 'US': 'USA', 'GB': 'Großbritannien',
        'FR': 'Frankreich', 'AT': 'Österreich', 'CH': 'Schweiz',
        'IT': 'Italien', 'ES': 'Spanien', 'NL': 'Niederlande',
        'BE': 'Belgien', 'XX': 'Unbekannt'
      };
      return countries[code] || code;*/
      return CountryService.getCountryName(code)
    },

    getCountryFlag(code) {
      /*  const flags = {
          'DE': '🇩🇪', 'US': '🇺🇸', 'GB': '🇬🇧', 'FR': '🇫🇷',
          'AT': '🇦🇹', 'CH': '🇨🇭', 'IT': '🇮🇹', 'ES': '🇪🇸',
          'NL': '🇳🇱', 'BE': '🇧🇪', 'XX': '🌍'
        };
        return flags[code] || '🌍';*/
      return CountryService.getCountryFlag(code) || '🌍'
    },

    getBrowserIcon(browser) {
      const icons = {
        'Chrome': 'logo-chrome', 'Firefox': 'logo-firefox',
        'Safari': 'logo-safari', 'Edge': 'logo-edge',
        'Opera': 'logo-opera', 'Other': 'globe'
      };
      return icons[browser] || 'globe';
    },

    getPlatformIcon(platform) {
      const icons = {
        'Windows': 'logo-windows', 'macOS': 'logo-apple',
        'Linux': 'logo-tux', 'Android': 'logo-android',
        'iOS': 'logo-apple', 'Other': 'desktop'
      };
      return icons[platform] || 'desktop';
    },

    getDeviceIcon(device) {
      const icons = {
        'Desktop': 'desktop', 'Mobile': 'phone-portrait',
        'Tablet': 'tablet-portrait'
      };
      return icons[device] || 'desktop';
    },

    async loadCustomDomains() {
      try {
        const response = await this.$axios.post('link_tracker_domains.php', this.$qs.stringify({
          getCustomDomains: true,
          project: this.$route.params.project,
          link_id: this.$route.params.linkId
        }));

        if (response.data.success) {
          this.customDomains = response.data.domains;
        }
      } catch (error) {
        console.error('Error loading custom domains:', error);
      }
    },

    async getBaseDomain() {
      try {
        const response = await this.$axios.post('projects.php', this.$qs.stringify({
          getProjectInfo: true,
          project: this.$route.params.project
        }));

        if (response.data && response.data.domain) {
          this.baseDomain = response.data.domain;
        } else {
          this.baseDomain = this.$route.params.project + '.links.control-center.eu';
        }
      } catch (error) {
        this.baseDomain = this.$route.params.project + '.links.control-center.eu';
      }
    },

    async createCustomDomain() {
      if (!this.newDomain.subdomain) return;

      try {
        const response = await this.$axios.post('link_tracker_domains.php', this.$qs.stringify({
          createCustomDomain: true,
          project: this.$route.params.project,
          link_id: this.$route.params.linkId,
          custom_subdomain: this.newDomain.subdomain
        }));

        if (response.data.success) {
          this.showDomainForm = false;
          this.newDomain.subdomain = '';
          this.loadCustomDomains();
          // Success message
        } else {
          alert(response.data.message || 'Fehler beim Erstellen der Domain');
        }
      } catch (error) {
        console.error('Error creating domain:', error);
        alert('Fehler beim Erstellen der Domain');
      }
    },

    async deleteCustomDomain(domainId) {
      if (!confirm('Domain wirklich löschen?')) return;

      try {
        const response = await this.$axios.post('link_tracker_domains.php', this.$qs.stringify({
          deleteCustomDomain: true,
          project: this.$route.params.project,
          domain_id: domainId
        }));

        if (response.data.success) {
          this.loadCustomDomains();
        } else {
          alert(response.data.message || 'Fehler beim Löschen');
        }
      } catch (error) {
        console.error('Error deleting domain:', error);
        alert('Fehler beim Löschen der Domain');
      }
    },

    openReferer(referer) {
      if (referer) {
        window.open(referer, '_blank');
      }
    }
  }
};
</script>

<style scoped>
/* Same modern styling as FormDisplay */
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

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #1e1e1e;
    --surface: #2a2a2a;
    --border: #404040;
    --text-primary: #e2e8f0;
    --text-secondary: #94a3b8;
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

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.action-group-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
  text-decoration: none;
}

.action-btn:hover {
  background: var(--background);
}

.link-info h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  color: var(--text-primary);
}

.link-url {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
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

.data-card {
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

.chart-container {
  padding: 20px;
  height: 300px;
  position: relative;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
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

.referer-link {
  color: var(--primary-color);
  cursor: pointer;
}

.no-referer {
  color: var(--text-muted);
  font-style: italic;
}

.no-data {
  text-align: center;
  color: var(--text-muted);
  padding: 40px;
  font-style: italic;
}

/* Domain Management */
.domain-form {
  padding: 20px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.form-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.domain-input {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  min-width: 150px;
}

.domain-separator {
  font-weight: bold;
  color: var(--text-secondary);
}

.base-domain {
  color: var(--text-secondary);
  font-weight: 500;
}

.domain-list {
  padding: 20px;
}

.domain-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.domain-item:last-child {
  border-bottom: none;
}

.domain-url {
  display: flex;
  align-items: center;
  gap: 8px;
}

.domain-url a {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 500;
}

.domain-url a:hover {
  text-decoration: underline;
}

.domain-actions {
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

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .analytics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .period-selector {
    flex-wrap: wrap;
  }
}
</style>