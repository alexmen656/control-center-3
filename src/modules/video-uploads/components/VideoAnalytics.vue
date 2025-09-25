<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle 
        icon="analytics-outline" 
        :title="`Video Analytics - ${video?.title || 'Loading...'}`" 
      />

      <div class="page-container" v-if="video">
        <!-- Header Actions -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn secondary" @click="goBack">
              <ion-icon name="arrow-back-outline"></ion-icon>
              Zurück zu Details
            </button>
          </div>
          <div class="action-group-right">
            <div class="time-range-selector">
              <select v-model="selectedTimeRange" @change="updateAnalytics" class="modern-select">
                <option value="7d">Letzte 7 Tage</option>
                <option value="30d">Letzte 30 Tage</option>
                <option value="90d">Letzte 3 Monate</option>
                <option value="1y">Letztes Jahr</option>
              </select>
            </div>
            <button class="action-btn secondary" @click="exportAnalytics">
              <ion-icon name="download-outline"></ion-icon>
              Export
            </button>
            <button class="action-btn secondary" @click="refreshAnalytics">
              <ion-icon name="refresh-outline"></ion-icon>
              Aktualisieren
            </button>
          </div>
        </div>

        <!-- Overview KPIs -->
        <div class="kpi-section">
          <div class="kpi-grid">
            <div class="kpi-card">
              <div class="kpi-icon success">
                <ion-icon name="eye-outline"></ion-icon>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ formatNumber(getTotalViews()) }}</div>
                <div class="kpi-label">Gesamte Views</div>
                <div class="kpi-subtitle">
                  <span class="kpi-trend positive">
                    <ion-icon name="trending-up-outline"></ion-icon>
                    +{{ getViewsGrowth() }}%
                  </span>
                  vs. vorherige Periode
                </div>
              </div>
            </div>

            <div class="kpi-card">
              <div class="kpi-icon warning">
                <ion-icon name="heart-outline"></ion-icon>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ formatNumber(getTotalEngagements()) }}</div>
                <div class="kpi-label">Engagements</div>
                <div class="kpi-subtitle">
                  <span class="kpi-trend positive">
                    <ion-icon name="trending-up-outline"></ion-icon>
                    +{{ getEngagementsGrowth() }}%
                  </span>
                  vs. vorherige Periode
                </div>
              </div>
            </div>

            <div class="kpi-card">
              <div class="kpi-icon active">
                <ion-icon name="stats-chart-outline"></ion-icon>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ getOverallEngagementRate() }}%</div>
                <div class="kpi-label">Engagement Rate</div>
                <div class="kpi-subtitle">
                  <span class="kpi-trend positive">
                    <ion-icon name="trending-up-outline"></ion-icon>
                    +{{ getEngagementRateGrowth() }}%
                  </span>
                  vs. vorherige Periode
                </div>
              </div>
            </div>

            <div class="kpi-card">
              <div class="kpi-icon">
                <ion-icon name="people-outline"></ion-icon>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ formatNumber(getReachEstimate()) }}</div>
                <div class="kpi-label">Geschätzte Reichweite</div>
                <div class="kpi-subtitle">
                  <span class="kpi-trend positive">
                    <ion-icon name="trending-up-outline"></ion-icon>
                    +{{ getReachGrowth() }}%
                  </span>
                  vs. vorherige Periode
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Platform Distribution Charts -->
        <div class="charts-section">
          <div class="chart-row">
            <!-- Views Distribution -->
            <div class="chart-card">
              <div class="chart-header">
                <h3>Views nach Plattform</h3>
                <div class="chart-total">
                  Total: {{ formatNumber(getTotalViews()) }}
                </div>
              </div>
              <div class="chart-content">
                <div class="pie-chart-container">
                  <canvas ref="viewsChart" width="300" height="300"></canvas>
                </div>
                <div class="chart-legend">
                  <div 
                    v-for="platform in getPlatformData()"
                    :key="platform.name"
                    class="legend-item"
                  >
                    <div 
                      class="legend-color"
                      :style="{ backgroundColor: platform.color }"
                    ></div>
                    <span class="legend-label">{{ platform.name }}</span>
                    <span class="legend-value">{{ formatNumber(platform.views) }}</span>
                    <span class="legend-percentage">({{ platform.viewsPercentage }}%)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Engagement Distribution -->
            <div class="chart-card">
              <div class="chart-header">
                <h3>Engagements nach Plattform</h3>
                <div class="chart-total">
                  Total: {{ formatNumber(getTotalEngagements()) }}
                </div>
              </div>
              <div class="chart-content">
                <div class="pie-chart-container">
                  <canvas ref="engagementChart" width="300" height="300"></canvas>
                </div>
                <div class="chart-legend">
                  <div 
                    v-for="platform in getPlatformData()"
                    :key="platform.name"
                    class="legend-item"
                  >
                    <div 
                      class="legend-color"
                      :style="{ backgroundColor: platform.color }"
                    ></div>
                    <span class="legend-label">{{ platform.name }}</span>
                    <span class="legend-value">{{ formatNumber(platform.engagements) }}</span>
                    <span class="legend-percentage">({{ platform.engagementsPercentage }}%)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Comparison -->
          <div class="chart-card full-width">
            <div class="chart-header">
              <h3>Performance Vergleich nach Plattform</h3>
              <div class="chart-controls">
                <select v-model="selectedMetric" @change="updatePerformanceChart" class="modern-select">
                  <option value="views">Views</option>
                  <option value="likes">Likes</option>
                  <option value="comments">Kommentare</option>
                  <option value="shares">Shares</option>
                  <option value="engagement_rate">Engagement Rate</option>
                </select>
              </div>
            </div>
            <div class="chart-content">
              <div class="bar-chart-container">
                <canvas ref="performanceChart" width="800" height="400"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Platform Analytics -->
        <div class="platform-details-section">
          <div class="data-card">
            <div class="card-header">
              <h3>Detaillierte Platform Analytics</h3>
            </div>
            <div class="platform-analytics">
              <div 
                v-for="platform in getPlatformData()"
                :key="platform.name"
                class="platform-analytics-card"
              >
                <div class="platform-header">
                  <div :class="['platform-icon', platform.key]">
                    <ion-icon :name="platform.icon"></ion-icon>
                  </div>
                  <div class="platform-info">
                    <h4>{{ platform.name }}</h4>
                    <p class="platform-status">{{ getPlatformStatus(platform.key) }}</p>
                  </div>
                  <div class="platform-engagement-rate">
                    <span class="engagement-rate-value">{{ platform.engagementRate }}%</span>
                    <span class="engagement-rate-label">Engagement Rate</span>
                  </div>
                </div>

                <div class="platform-metrics-grid">
                  <div class="metric-box">
                    <div class="metric-icon views">
                      <ion-icon name="eye-outline"></ion-icon>
                    </div>
                    <div class="metric-data">
                      <div class="metric-value">{{ formatNumber(platform.views) }}</div>
                      <div class="metric-label">Views</div>
                      <div class="metric-change positive">+12%</div>
                    </div>
                  </div>

                  <div class="metric-box">
                    <div class="metric-icon likes">
                      <ion-icon name="heart-outline"></ion-icon>
                    </div>
                    <div class="metric-data">
                      <div class="metric-value">{{ formatNumber(platform.likes) }}</div>
                      <div class="metric-label">Likes</div>
                      <div class="metric-change positive">+8%</div>
                    </div>
                  </div>

                  <div class="metric-box">
                    <div class="metric-icon comments">
                      <ion-icon name="chatbubble-outline"></ion-icon>
                    </div>
                    <div class="metric-data">
                      <div class="metric-value">{{ formatNumber(platform.comments) }}</div>
                      <div class="metric-label">Kommentare</div>
                      <div class="metric-change positive">+15%</div>
                    </div>
                  </div>

                  <div class="metric-box">
                    <div class="metric-icon shares">
                      <ion-icon name="share-outline"></ion-icon>
                    </div>
                    <div class="metric-data">
                      <div class="metric-value">{{ formatNumber(platform.shares) }}</div>
                      <div class="metric-label">Shares</div>
                      <div class="metric-change positive">+22%</div>
                    </div>
                  </div>
                </div>

                <!-- Time-based performance for each platform -->
                <div class="platform-timeline">
                  <h5>Performance über Zeit</h5>
                  <div class="timeline-chart">
                    <canvas :ref="`timeline-${platform.key}`" width="400" height="200"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Audience Demographics -->
        <div class="demographics-section">
          <div class="chart-row">
            <div class="chart-card">
              <div class="chart-header">
                <h3>Altersverteilung</h3>
              </div>
              <div class="chart-content">
                <div class="demographics-chart">
                  <canvas ref="ageChart" width="400" height="300"></canvas>
                </div>
              </div>
            </div>

            <div class="chart-card">
              <div class="chart-header">
                <h3>Geografische Verteilung</h3>
              </div>
              <div class="chart-content">
                <div class="geography-list">
                  <div 
                    v-for="country in getTopCountries()"
                    :key="country.name"
                    class="geography-item"
                  >
                    <span class="country-flag">{{ country.flag }}</span>
                    <span class="country-name">{{ country.name }}</span>
                    <span class="country-percentage">{{ country.percentage }}%</span>
                    <div class="country-bar">
                      <div 
                        class="country-bar-fill"
                        :style="{ width: country.percentage + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recommendations -->
        <div class="recommendations-section">
          <div class="data-card">
            <div class="card-header">
              <h3>
                <ion-icon name="bulb-outline"></ion-icon>
                Empfehlungen zur Optimierung
              </h3>
            </div>
            <div class="recommendations-content">
              <div class="recommendation-grid">
                <div 
                  v-for="recommendation in getRecommendations()"
                  :key="recommendation.id"
                  :class="['recommendation-card', recommendation.priority]"
                >
                  <div class="recommendation-header">
                    <ion-icon :name="recommendation.icon"></ion-icon>
                    <span class="recommendation-title">{{ recommendation.title }}</span>
                    <span :class="['priority-badge', recommendation.priority]">
                      {{ recommendation.priorityText }}
                    </span>
                  </div>
                  <p class="recommendation-description">{{ recommendation.description }}</p>
                  <div class="recommendation-impact">
                    <span class="impact-label">Erwartete Verbesserung:</span>
                    <span class="impact-value">{{ recommendation.expectedImprovement }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="loading-container">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Analytics werden geladen...</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";

export default {
  name: "VideoAnalytics",
  components: {
    SiteTitle,
  },
  data() {
    return {
      video: null,
      loading: true,
      error: null,
      selectedTimeRange: '30d',
      selectedMetric: 'views',
      charts: {}
    };
  },

  mounted() {
    this.loadVideo();
  },

  methods: {
    async loadVideo() {
      this.loading = true;
      this.error = null;

      try {
        const videoId = this.$route.params.videoId;
        const projectId = this.$route.params.project;

        const response = await this.$axios.get(`get_video.php?action=get_video&project_id=${projectId}&video_id=${videoId}`);

        /*if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }*/ 

        const data = await response.data;
        
        if (data.success) {
          this.video = data.video;
          
          // Enhanced mock analytics data
          this.video.analytics = {
            youtube: { 
              views: 25400, likes: 1250, comments: 189, shares: 45,
              timeline: [2100, 3200, 4800, 6400, 8100, 9800, 11600, 13200, 14900, 16500, 18200, 19800, 21400, 23100, 25400]
            },
            instagram: { 
              views: 18200, likes: 820, comments: 95, shares: 32,
              timeline: [1800, 2600, 3900, 5200, 6800, 8100, 9700, 11200, 12800, 14100, 15600, 16800, 17900, 18100, 18200]
            },
            tiktok: { 
              views: 45600, likes: 2890, comments: 456, shares: 187,
              timeline: [3200, 5800, 8900, 12400, 16200, 20100, 24600, 28900, 33200, 37100, 40800, 42900, 44200, 45100, 45600]
            },
            facebook: { 
              views: 12800, likes: 410, comments: 78, shares: 28,
              timeline: [980, 1600, 2400, 3200, 4100, 5200, 6400, 7600, 8900, 10200, 11100, 11800, 12300, 12600, 12800]
            },
            linkedin: { 
              views: 5600, likes: 185, comments: 32, shares: 18,
              timeline: [420, 680, 980, 1300, 1680, 2100, 2600, 3200, 3800, 4400, 4900, 5200, 5400, 5500, 5600]
            }
          };

          this.$nextTick(() => {
            this.initializeCharts();
          });
        } else {
          this.error = data.message || 'Fehler beim Laden der Analytics';
        }
      } catch (error) {
        console.error('Error loading video analytics:', error);
        this.error = 'Fehler beim Laden der Analytics';
      } finally {
        this.loading = false;
      }
    },

    initializeCharts() {
      this.createViewsChart();
      this.createEngagementChart();
      this.createPerformanceChart();
      this.createAgeChart();
      this.createTimelineCharts();
    },

    createViewsChart() {
      const canvas = this.$refs.viewsChart;
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const data = this.getPlatformData();
      
      this.drawPieChart(ctx, data, 'views', canvas.width, canvas.height);
    },

    createEngagementChart() {
      const canvas = this.$refs.engagementChart;
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const data = this.getPlatformData();
      
      this.drawPieChart(ctx, data, 'engagements', canvas.width, canvas.height);
    },

    createPerformanceChart() {
      const canvas = this.$refs.performanceChart;
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      this.drawBarChart(ctx, this.getPlatformData(), this.selectedMetric, canvas.width, canvas.height);
    },

    createAgeChart() {
      const canvas = this.$refs.ageChart;
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const ageData = [
        { label: '18-24', value: 28, color: '#3b82f6' },
        { label: '25-34', value: 35, color: '#10b981' },
        { label: '35-44', value: 22, color: '#f59e0b' },
        { label: '45-54', value: 12, color: '#ef4444' },
        { label: '55+', value: 3, color: '#8b5cf6' }
      ];
      
      this.drawBarChart(ctx, ageData, 'age', canvas.width, canvas.height);
    },

    createTimelineCharts() {
      const platforms = this.getPlatformData();
      platforms.forEach(platform => {
        const canvas = this.$refs[`timeline-${platform.key}`];
        if (canvas && canvas[0]) {
          const ctx = canvas[0].getContext('2d');
          this.drawLineChart(ctx, platform.timeline, platform.color, canvas[0].width, canvas[0].height);
        }
      });
    },

    drawPieChart(ctx, data, field, width, height) {
      const centerX = width / 2;
      const centerY = height / 2;
      const radius = Math.min(width, height) / 2 - 20;

      let total;
      if (field === 'views') {
        total = data.reduce((sum, item) => sum + item.views, 0);
      } else {
        total = data.reduce((sum, item) => sum + item.engagements, 0);
      }

      let currentAngle = -Math.PI / 2;

      data.forEach(item => {
        const value = field === 'views' ? item.views : item.engagements;
        const sliceAngle = (value / total) * 2 * Math.PI;

        // Draw slice
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = item.color;
        ctx.fill();

        // Draw border
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();

        currentAngle += sliceAngle;
      });
    },

    drawBarChart(ctx, data, metric, width, height) {
      const padding = 60;
      const chartWidth = width - padding * 2;
      const chartHeight = height - padding * 2;

      // Clear canvas
      ctx.clearRect(0, 0, width, height);

      let values, maxValue;
      
      if (metric === 'age') {
        values = data.map(item => item.value);
        maxValue = Math.max(...values);
      } else {
        const platforms = data;
        if (metric === 'views') {
          values = platforms.map(p => p.views);
        } else if (metric === 'likes') {
          values = platforms.map(p => p.likes);
        } else if (metric === 'comments') {
          values = platforms.map(p => p.comments);
        } else if (metric === 'shares') {
          values = platforms.map(p => p.shares);
        } else if (metric === 'engagement_rate') {
          values = platforms.map(p => parseFloat(p.engagementRate));
        }
        maxValue = Math.max(...values);
      }

      const barWidth = chartWidth / values.length - 20;

      values.forEach((value, index) => {
        const barHeight = (value / maxValue) * chartHeight;
        const x = padding + index * (barWidth + 20);
        const y = height - padding - barHeight;

        // Draw bar
        ctx.fillStyle = data[index].color || '#3b82f6';
        ctx.fillRect(x, y, barWidth, barHeight);

        // Draw value on top
        ctx.fillStyle = '#1e293b';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(this.formatNumber(value), x + barWidth / 2, y - 5);

        // Draw label
        ctx.fillText(data[index].label || data[index].name, x + barWidth / 2, height - padding + 20);
      });
    },

    drawLineChart(ctx, timeline, color, width, height) {
      if (!timeline || timeline.length === 0) return;

      const padding = 20;
      const chartWidth = width - padding * 2;
      const chartHeight = height - padding * 2;

      // Clear canvas
      ctx.clearRect(0, 0, width, height);

      const maxValue = Math.max(...timeline);
      const step = chartWidth / (timeline.length - 1);

      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.beginPath();

      timeline.forEach((value, index) => {
        const x = padding + index * step;
        const y = height - padding - (value / maxValue) * chartHeight;

        if (index === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      });

      ctx.stroke();

      // Draw points
      ctx.fillStyle = color;
      timeline.forEach((value, index) => {
        const x = padding + index * step;
        const y = height - padding - (value / maxValue) * chartHeight;
        
        ctx.beginPath();
        ctx.arc(x, y, 3, 0, 2 * Math.PI);
        ctx.fill();
      });
    },

    updateAnalytics() {
      // Reload analytics data based on selected time range
      this.loadVideo();
    },

    updatePerformanceChart() {
      this.createPerformanceChart();
    },

    refreshAnalytics() {
      this.loadVideo();
    },

    exportAnalytics() {
      // Implementation for exporting analytics data
      console.log('Exporting analytics...');
    },

    goBack() {
      this.$router.push(`/project/${this.$route.params.project}/video-uploads/${this.$route.params.videoId}/details`);
    },

    getPlatformData() {
      if (!this.video?.analytics) return [];

      const platforms = [
        { key: 'youtube', name: 'YouTube', color: '#ff0000', icon: 'logo-youtube' },
        { key: 'instagram', name: 'Instagram', color: '#e1306c', icon: 'logo-instagram' },
        { key: 'tiktok', name: 'TikTok', color: '#000000', icon: 'logo-tiktok' },
        { key: 'facebook', name: 'Facebook', color: '#1877f2', icon: 'logo-facebook' },
        { key: 'linkedin', name: 'LinkedIn', color: '#0077b5', icon: 'logo-linkedin' }
      ];

      const totalViews = this.getTotalViews();
      const totalEngagements = this.getTotalEngagements();

      return platforms.map(platform => {
        const analytics = this.video.analytics[platform.key] || { views: 0, likes: 0, comments: 0, shares: 0, timeline: [] };
        const views = analytics.views || 0;
        const likes = analytics.likes || 0;
        const comments = analytics.comments || 0;
        const shares = analytics.shares || 0;
        const engagements = likes + comments + shares;
        const engagementRate = views > 0 ? ((engagements / views) * 100).toFixed(1) : '0.0';

        return {
          ...platform,
          views,
          likes,
          comments,
          shares,
          engagements,
          engagementRate,
          viewsPercentage: totalViews > 0 ? ((views / totalViews) * 100).toFixed(1) : '0',
          engagementsPercentage: totalEngagements > 0 ? ((engagements / totalEngagements) * 100).toFixed(1) : '0',
          timeline: analytics.timeline || []
        };
      }).filter(platform => platform.views > 0);
    },

    getTotalViews() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => sum + (platform.views || 0), 0);
    },

    getTotalEngagements() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => {
        return sum + (platform.likes || 0) + (platform.comments || 0) + (platform.shares || 0);
      }, 0);
    },

    getOverallEngagementRate() {
      const totalViews = this.getTotalViews();
      const totalEngagements = this.getTotalEngagements();
      return totalViews > 0 ? ((totalEngagements / totalViews) * 100).toFixed(1) : '0.0';
    },

    getReachEstimate() {
      // Estimated reach is typically 2-3x the views
      return Math.floor(this.getTotalViews() * 2.3);
    },

    getViewsGrowth() {
      return Math.floor(Math.random() * 20) + 5; // Mock growth 5-25%
    },

    getEngagementsGrowth() {
      return Math.floor(Math.random() * 15) + 8; // Mock growth 8-23%
    },

    getEngagementRateGrowth() {
      return Math.floor(Math.random() * 10) + 2; // Mock growth 2-12%
    },

    getReachGrowth() {
      return Math.floor(Math.random() * 18) + 7; // Mock growth 7-25%
    },

    getPlatformStatus() {
      const statuses = ['Aktiv', 'Sehr gut', 'Überdurchschnittlich', 'Durchschnittlich'];
      return statuses[Math.floor(Math.random() * statuses.length)];
    },

    getTopCountries() {
      return [
        { name: 'Deutschland', flag: '🇩🇪', percentage: 45 },
        { name: 'Österreich', flag: '🇦🇹', percentage: 22 },
        { name: 'Schweiz', flag: '🇨🇭', percentage: 15 },
        { name: 'USA', flag: '🇺🇸', percentage: 8 },
        { name: 'Niederlande', flag: '🇳🇱', percentage: 6 },
        { name: 'Frankreich', flag: '🇫🇷', percentage: 4 }
      ];
    },

    getRecommendations() {
      return [
        {
          id: 1,
          title: 'TikTok Performance optimieren',
          description: 'Ihre TikTok-Videos zeigen die beste Engagement Rate. Erwägen Sie, mehr Content speziell für TikTok zu erstellen.',
          priority: 'high',
          priorityText: 'Hoch',
          icon: 'trending-up-outline',
          expectedImprovement: '+25% Engagement'
        },
        {
          id: 2,
          title: 'YouTube Thumbnails verbessern',
          description: 'YouTube hat gute Views, aber niedrige Click-Through-Rate. Bessere Thumbnails könnten die Performance steigern.',
          priority: 'medium',
          priorityText: 'Mittel',
          icon: 'image-outline',
          expectedImprovement: '+15% Views'
        },
        {
          id: 3,
          title: 'Instagram Stories nutzen',
          description: 'Nutzen Sie Instagram Stories für zusätzliche Reichweite und um auf Ihre Videos aufmerksam zu machen.',
          priority: 'medium',
          priorityText: 'Mittel',
          icon: 'layers-outline',
          expectedImprovement: '+20% Reichweite'
        },
        {
          id: 4,
          title: 'Posting-Zeiten optimieren',
          description: 'Analysieren Sie die besten Posting-Zeiten für jede Plattform, um maximale Sichtbarkeit zu erreichen.',
          priority: 'low',
          priorityText: 'Niedrig',
          icon: 'time-outline',
          expectedImprovement: '+10% Engagement'
        }
      ];
    },

    formatNumber(num) {
      return new Intl.NumberFormat('de-DE').format(num || 0);
    }
  }
};
</script>

<style scoped>
/* Inherit base styles */
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

.action-group-left,
.action-group-right {
  display: flex;
  gap: 12px;
  align-items: center;
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

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover {
  background: var(--background);
  color: var(--text-primary);
}

.modern-select {
  padding: 10px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  cursor: pointer;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

/* KPI Section */
.kpi-section {
  margin-bottom: 32px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.kpi-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid var(--border);
}

.kpi-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  font-size: 24px;
}

.kpi-icon.success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.kpi-icon.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.kpi-icon.active {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.kpi-content {
  flex: 1;
}

.kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 2px;
  line-height: 1.2;
}

.kpi-label {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: 6px;
}

.kpi-subtitle {
  font-size: 13px;
  color: var(--text-muted);
}

.kpi-trend {
  display: inline-flex;
  align-items: center;
  font-size: 13px;
  font-weight: 600;
}

.kpi-trend.positive {
  color: var(--success-color);
}

/* Charts Section */
.charts-section {
  margin-bottom: 32px;
}

.chart-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 24px;
}

.chart-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.chart-card.full-width {
  grid-column: 1 / -1;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.chart-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.chart-total {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 500;
}

.chart-controls {
  display: flex;
  gap: 12px;
}

.chart-content {
  padding: 24px;
}

.pie-chart-container,
.bar-chart-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 20px;
}

.chart-legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-label {
  flex: 1;
  color: var(--text-primary);
  font-weight: 500;
}

.legend-value {
  color: var(--text-primary);
  font-weight: 600;
}

.legend-percentage {
  color: var(--text-secondary);
  font-size: 13px;
}

/* Platform Details */
.platform-details-section {
  margin-bottom: 32px;
}

.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.platform-analytics {
  padding: 24px;
}

.platform-analytics-card {
  background: var(--background);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid var(--border);
}

.platform-analytics-card:last-child {
  margin-bottom: 0;
}

.platform-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.platform-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.platform-icon.youtube {
  background: rgba(255, 0, 0, 0.1);
  color: #ff0000;
}

.platform-icon.instagram {
  background: rgba(225, 48, 108, 0.1);
  color: #e1306c;
}

.platform-icon.tiktok {
  background: rgba(0, 0, 0, 0.1);
  color: #000000;
}

.platform-icon.facebook {
  background: rgba(24, 119, 242, 0.1);
  color: #1877f2;
}

.platform-icon.linkedin {
  background: rgba(0, 119, 181, 0.1);
  color: #0077b5;
}

.platform-info {
  flex: 1;
}

.platform-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.platform-status {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.platform-engagement-rate {
  text-align: right;
}

.engagement-rate-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: var(--primary-color);
  line-height: 1.2;
}

.engagement-rate-label {
  display: block;
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.platform-metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.metric-box {
  background: var(--surface);
  border-radius: 8px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--border);
}

.metric-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.metric-icon.views {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.metric-icon.likes {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.metric-icon.comments {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.metric-icon.shares {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.metric-data {
  flex: 1;
}

.metric-value {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  margin-bottom: 2px;
}

.metric-label {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.metric-change {
  font-size: 11px;
  font-weight: 600;
}

.metric-change.positive {
  color: var(--success-color);
}

.platform-timeline h5 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.timeline-chart {
  height: 200px;
}

/* Demographics */
.demographics-section {
  margin-bottom: 32px;
}

.demographics-chart {
  display: flex;
  justify-content: center;
}

.geography-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.geography-item {
  display: grid;
  grid-template-columns: 30px 1fr auto 100px;
  align-items: center;
  gap: 12px;
}

.country-flag {
  font-size: 18px;
}

.country-name {
  color: var(--text-primary);
  font-weight: 500;
}

.country-percentage {
  color: var(--text-primary);
  font-weight: 600;
  text-align: right;
}

.country-bar {
  height: 8px;
  background: var(--border);
  border-radius: 4px;
  overflow: hidden;
}

.country-bar-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.3s ease;
}

/* Recommendations */
.recommendations-section {
  margin-bottom: 32px;
}

.recommendations-content {
  padding: 24px;
}

.recommendation-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 20px;
}

.recommendation-card {
  background: var(--background);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--border);
  position: relative;
}

.recommendation-card.high {
  border-left: 4px solid var(--danger-color);
}

.recommendation-card.medium {
  border-left: 4px solid var(--warning-color);
}

.recommendation-card.low {
  border-left: 4px solid var(--success-color);
}

.recommendation-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.recommendation-header ion-icon {
  font-size: 20px;
  color: var(--primary-color);
}

.recommendation-title {
  flex: 1;
  font-weight: 600;
  color: var(--text-primary);
}

.priority-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-badge.high {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.priority-badge.medium {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.priority-badge.low {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.recommendation-description {
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 16px;
}

.recommendation-impact {
  display: flex;
  align-items: center;
  gap: 8px;
}

.impact-label {
  font-size: 13px;
  color: var(--text-secondary);
}

.impact-value {
  font-size: 13px;
  font-weight: 600;
  color: var(--success-color);
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-container ion-spinner {
  margin-bottom: 16px;
}

.loading-container p {
  margin: 0;
  font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .chart-row {
    grid-template-columns: 1fr;
  }

  .platform-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .recommendation-grid {
    grid-template-columns: 1fr;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    justify-content: center;
  }
}
</style>