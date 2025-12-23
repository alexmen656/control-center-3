<template>
  <ion-page>
    <ion-content class="modern-content">
     

      <div class="page-container" v-if="video">
        <!-- Header Actions -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn secondary" @click="goBack">
              <ion-icon name="arrow-back-outline"></ion-icon>
              Zurück
            </button>
          </div>
          <div class="action-group-right">
            <button class="action-btn primary" @click="editVideo">
              <ion-icon name="pencil-outline"></ion-icon>
              Bearbeiten
            </button>
            <button class="action-btn secondary" @click="viewAnalytics">
              <ion-icon name="analytics-outline"></ion-icon>
              Analytics
            </button>
          </div>
        </div>

        <!-- Video Overview Card -->
        <div class="data-card">
          <div class="card-header">
            <h3>Video Übersicht</h3>
          </div>
          <div class="video-overview">
            <div class="video-preview-section">
              <div class="video-player">
                <div class="thumbnail-large" v-if="video.thumbnail_url">
                  <img :src="video.thumbnail_url" :alt="video.title" />
                  <div class="play-overlay">
                    <ion-icon name="play-outline"></ion-icon>
                  </div>
                </div>
                <div class="default-thumbnail-large" v-else>
                  <ion-icon name="videocam-outline"></ion-icon>
                </div>
              </div>
            </div>
            
            <div class="video-info-section">
              <h2 class="video-title">{{ video.title }}</h2>
              <p class="video-description">{{ video.description }}</p>
              
              <div class="video-meta">
                <div class="meta-item">
                  <span class="meta-label">Status:</span>
                  <span :class="['status-badge', video.status]">
                    {{ getStatusText(video.status) }}
                  </span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Erstellt:</span>
                  <span>{{ formatDateTime(video.created_at) }}</span>
                </div>
                <div class="meta-item" v-if="video.scheduled_at">
                  <span class="meta-label">Geplant für:</span>
                  <span>{{ formatDateTime(video.scheduled_at) }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Dateigröße:</span>
                  <span>{{ formatFileSize(video.file_size) }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Dauer:</span>
                  <span>{{ video.duration || 'Unbekannt' }}</span>
                </div>
              </div>

              <div class="platforms-section">
                <h4>Zielplattformen</h4>
                <div class="platform-badges">
                  <div 
                    v-for="platform in getPlatformsArray(video.platforms)"
                    :key="platform"
                    :class="['platform-badge', platform]"
                  >
                    <ion-icon :name="getPlatformIcon(platform)"></ion-icon>
                    {{ getPlatformText(platform) }}
                  </div>
                </div>
              </div>

              <div class="tags-section" v-if="getTagsArray(video.tags).length > 0">
                <h4>Tags</h4>
                <div class="tags-container">
                  <span 
                    v-for="tag in getTagsArray(video.tags)"
                    :key="tag"
                    class="tag-badge"
                  >
                    {{ tag }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Overview -->
        <div class="data-card">
          <div class="card-header">
            <h3>Performance Übersicht</h3>
          </div>
          <div class="performance-overview">
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
                      +12%
                    </span>
                    seit letztem Monat
                  </div>
                </div>
              </div>

              <div class="kpi-card">
                <div class="kpi-icon warning">
                  <ion-icon name="heart-outline"></ion-icon>
                </div>
                <div class="kpi-content">
                  <div class="kpi-value">{{ formatNumber(getTotalLikes()) }}</div>
                  <div class="kpi-label">Gesamte Likes</div>
                  <div class="kpi-subtitle">
                    <span class="kpi-trend positive">
                      <ion-icon name="trending-up-outline"></ion-icon>
                      +8%
                    </span>
                    seit letztem Monat
                  </div>
                </div>
              </div>

              <div class="kpi-card">
                <div class="kpi-icon active">
                  <ion-icon name="chatbubble-outline"></ion-icon>
                </div>
                <div class="kpi-content">
                  <div class="kpi-value">{{ formatNumber(getTotalComments()) }}</div>
                  <div class="kpi-label">Kommentare</div>
                  <div class="kpi-subtitle">
                    <span class="kpi-trend positive">
                      <ion-icon name="trending-up-outline"></ion-icon>
                      +15%
                    </span>
                    seit letztem Monat
                  </div>
                </div>
              </div>

              <div class="kpi-card">
                <div class="kpi-icon">
                  <ion-icon name="share-outline"></ion-icon>
                </div>
                <div class="kpi-content">
                  <div class="kpi-value">{{ formatNumber(getTotalShares()) }}</div>
                  <div class="kpi-label">Shares</div>
                  <div class="kpi-subtitle">
                    <span class="kpi-trend positive">
                      <ion-icon name="trending-up-outline"></ion-icon>
                      +22%
                    </span>
                    seit letztem Monat
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Platform Performance -->
        <div class="data-card">
          <div class="card-header">
            <h3>Platform Performance</h3>
          </div>
          <div class="platform-performance">
            <div class="performance-grid">
              <div 
                v-for="platform in getPlatformsArray(video.platforms)"
                :key="platform"
                class="platform-performance-card"
              >
                <div class="platform-header">
                  <div :class="['platform-icon', platform]">
                    <ion-icon :name="getPlatformIcon(platform)"></ion-icon>
                  </div>
                  <h4>{{ getPlatformText(platform) }}</h4>
                </div>
                
                <div class="platform-metrics">
                  <div class="metric-item">
                    <span class="metric-label">Views</span>
                    <span class="metric-value">{{ formatNumber(getPlatformViews(platform)) }}</span>
                  </div>
                  <div class="metric-item">
                    <span class="metric-label">Likes</span>
                    <span class="metric-value">{{ formatNumber(getPlatformLikes(platform)) }}</span>
                  </div>
                  <div class="metric-item">
                    <span class="metric-label">Kommentare</span>
                    <span class="metric-value">{{ formatNumber(getPlatformComments(platform)) }}</span>
                  </div>
                  <div class="metric-item">
                    <span class="metric-label">Engagement Rate</span>
                    <span class="metric-value">{{ getPlatformEngagementRate(platform) }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload History -->
        <div class="data-card">
          <div class="card-header">
            <h3>Upload Verlauf</h3>
          </div>
          <div class="upload-history">
            <div 
              v-for="(upload, index) in video.upload_history || []"
              :key="index"
              class="history-item"
            >
              <div class="history-icon">
                <ion-icon :name="getHistoryIcon(upload.status)"></ion-icon>
              </div>
              <div class="history-content">
                <div class="history-title">
                  Upload zu {{ getPlatformText(upload.platform) }}
                </div>
                <div class="history-details">
                  <span :class="['history-status', upload.status]">
                    {{ getStatusText(upload.status) }}
                  </span>
                  <span class="history-date">
                    {{ formatDateTime(upload.uploaded_at) }}
                  </span>
                </div>
                <div class="history-message" v-if="upload.message">
                  {{ upload.message }}
                </div>
              </div>
            </div>
            
            <div v-if="!video.upload_history || video.upload_history.length === 0" class="no-history">
              <ion-icon name="time-outline"></ion-icon>
              <p>Noch keine Uploads</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="loading-container">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Video wird geladen...</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>

export default {
  name: "VideoDetails",
  data() {
    return {
      video: null,
      loading: true,
      error: null,
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

       /* if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }*/

        const data = await response.data;
        
        if (data.success) {
          this.video = data.video;
          
          // Add mock analytics data for demonstration
          this.video.analytics = {
            youtube: { views: 12500, likes: 450, comments: 89, shares: 23 },
            instagram: { views: 8200, likes: 320, comments: 45, shares: 12 },
            tiktok: { views: 25000, likes: 890, comments: 156, shares: 67 },
            facebook: { views: 6800, likes: 210, comments: 34, shares: 18 },
            linkedin: { views: 2100, likes: 85, comments: 12, shares: 8 }
          };

          // Add mock upload history
          this.video.upload_history = [
            {
              platform: 'youtube',
              status: 'published',
              uploaded_at: '2024-09-20 14:30:00',
              message: 'Erfolgreich hochgeladen und veröffentlicht'
            },
            {
              platform: 'instagram',
              status: 'published',
              uploaded_at: '2024-09-20 15:45:00',
              message: 'Erfolgreich hochgeladen und veröffentlicht'
            },
            {
              platform: 'tiktok',
              status: 'processing',
              uploaded_at: '2024-09-20 16:20:00',
              message: 'Upload in Bearbeitung...'
            }
          ];
        } else {
          this.error = data.message || 'Fehler beim Laden des Videos';
        }
      } catch (error) {
        console.error('Error loading video:', error);
        this.error = 'Fehler beim Laden des Videos';
      } finally {
        this.loading = false;
      }
    },

    goBack() {
      this.$router.push(`/project/${this.$route.params.project}/video-uploads`);
    },

    editVideo() {
      this.$router.push(`/project/${this.$route.params.project}/video-uploads?edit=${this.video.id}`);
    },

    viewAnalytics() {
      this.$router.push(`/project/${this.$route.params.project}/video-uploads/${this.video.id}/analytics`);
    },

    getTotalViews() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => sum + (platform.views || 0), 0);
    },

    getTotalLikes() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => sum + (platform.likes || 0), 0);
    },

    getTotalComments() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => sum + (platform.comments || 0), 0);
    },

    getTotalShares() {
      if (!this.video?.analytics) return 0;
      return Object.values(this.video.analytics).reduce((sum, platform) => sum + (platform.shares || 0), 0);
    },

    getPlatformViews(platform) {
      return this.video?.analytics?.[platform]?.views || 0;
    },

    getPlatformLikes(platform) {
      return this.video?.analytics?.[platform]?.likes || 0;
    },

    getPlatformComments(platform) {
      return this.video?.analytics?.[platform]?.comments || 0;
    },

    getPlatformEngagementRate(platform) {
      const analytics = this.video?.analytics?.[platform];
      if (!analytics || !analytics.views) return '0.0';
      
      const engagements = (analytics.likes || 0) + (analytics.comments || 0) + (analytics.shares || 0);
      const rate = (engagements / analytics.views) * 100;
      return rate.toFixed(1);
    },

    getPlatformsArray(platforms) {
      if (Array.isArray(platforms)) return platforms;
      if (typeof platforms === 'string') {
        try {
          return JSON.parse(platforms);
        } catch {
          return platforms.split(',').map(p => p.trim());
        }
      }
      return [];
    },

    getTagsArray(tags) {
      if (!tags) return [];
      if (Array.isArray(tags)) return tags;
      return tags.split(',').map(tag => tag.trim()).filter(tag => tag);
    },

    getHistoryIcon(status) {
      const iconMap = {
        published: 'checkmark-circle-outline',
        processing: 'time-outline',
        failed: 'close-circle-outline',
        scheduled: 'calendar-outline'
      };
      return iconMap[status] || 'help-circle-outline';
    },

    formatDate(dateStr) {
      if (!dateStr) return 'Unbekannt';
      return new Date(dateStr).toLocaleDateString('de-DE');
    },

    formatDateTime(dateTimeStr) {
      if (!dateTimeStr) return 'Unbekannt';
      return new Date(dateTimeStr).toLocaleString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatFileSize(bytes) {
      if (!bytes) return 'Unbekannt';
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(1024));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    },

    formatNumber(num) {
      return new Intl.NumberFormat('de-DE').format(num || 0);
    },

    getStatusText(status) {
      const statusMap = {
        draft: 'Entwurf',
        scheduled: 'Geplant',
        published: 'Veröffentlicht',
        processing: 'In Bearbeitung',
        failed: 'Fehlgeschlagen'
      };
      return statusMap[status] || status;
    },

    getPlatformText(platform) {
      const platformMap = {
        youtube: 'YouTube',
        instagram: 'Instagram',
        tiktok: 'TikTok',
        facebook: 'Facebook',
        linkedin: 'LinkedIn'
      };
      return platformMap[platform] || platform;
    },

    getPlatformIcon(platform) {
      const iconMap = {
        youtube: 'logo-youtube',
        instagram: 'logo-instagram',
        tiktok: 'logo-tiktok',
        facebook: 'logo-facebook',
        linkedin: 'logo-linkedin'
      };
      return iconMap[platform] || 'videocam-outline';
    }
  }
};
</script>

<style scoped>
/* Inherit all styles from VideoUploads.vue */
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

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
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
  background: var(--background);
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

/* Video Overview */
.video-overview {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 32px;
  padding: 24px;
}

.video-preview-section {
  display: flex;
  flex-direction: column;
}

.video-player {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.thumbnail-large {
  position: relative;
  width: 100%;
  height: 225px;
  border-radius: 12px;
  overflow: hidden;
  background: var(--background);
  cursor: pointer;
}

.thumbnail-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.default-thumbnail-large {
  width: 100%;
  height: 225px;
  border-radius: 12px;
  background: var(--background);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 48px;
  border: 2px dashed var(--border);
}

.play-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 64px;
  height: 64px;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  transition: all 0.2s ease;
}

.play-overlay:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: translate(-50%, -50%) scale(1.1);
}

.video-info-section {
  flex: 1;
}

.video-title {
  margin: 0 0 16px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
  line-height: 1.3;
}

.video-description {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.6;
}

.video-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.meta-label {
  font-weight: 600;
  color: var(--text-secondary);
  min-width: 80px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.draft {
  background: #f3f4f6;
  color: #374151;
}

.status-badge.scheduled {
  background: #eff6ff;
  color: #1e40af;
}

.status-badge.published {
  background: #ecfdf5;
  color: #047857;
}

.status-badge.processing {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.failed {
  background: #fee2e2;
  color: #b91c1c;
}

.platforms-section,
.tags-section {
  margin-bottom: 24px;
}

.platforms-section h4,
.tags-section h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.platform-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.platform-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
  background: var(--background);
  border: 1px solid var(--border);
}

.platform-badge.youtube {
  color: #ff0000;
  background: rgba(255, 0, 0, 0.1);
  border-color: rgba(255, 0, 0, 0.2);
}

.platform-badge.instagram {
  color: #e1306c;
  background: rgba(225, 48, 108, 0.1);
  border-color: rgba(225, 48, 108, 0.2);
}

.platform-badge.tiktok {
  color: #000000;
  background: rgba(0, 0, 0, 0.1);
  border-color: rgba(0, 0, 0, 0.2);
}

.platform-badge.facebook {
  color: #1877f2;
  background: rgba(24, 119, 242, 0.1);
  border-color: rgba(24, 119, 242, 0.2);
}

.platform-badge.linkedin {
  color: #0077b5;
  background: rgba(0, 119, 181, 0.1);
  border-color: rgba(0, 119, 181, 0.2);
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag-badge {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
}

/* Performance Overview */
.performance-overview {
  padding: 24px;
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

/* Platform Performance */
.platform-performance {
  padding: 24px;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.platform-performance-card {
  background: var(--background);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--border);
}

.platform-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.platform-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
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

.platform-header h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.platform-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.metric-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.metric-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.metric-value {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

/* Upload History */
.upload-history {
  padding: 24px;
  max-height: 400px;
  overflow-y: auto;
}

.history-item {
  display: flex;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid var(--border);
}

.history-item:last-child {
  border-bottom: none;
}

.history-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--background);
  font-size: 20px;
  color: var(--text-secondary);
  flex-shrink: 0;
}

.history-content {
  flex: 1;
}

.history-title {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.history-details {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-bottom: 4px;
}

.history-status {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.history-status.published {
  color: var(--success-color);
}

.history-status.processing {
  color: var(--warning-color);
}

.history-status.failed {
  color: var(--danger-color);
}

.history-date {
  font-size: 12px;
  color: var(--text-secondary);
}

.history-message {
  font-size: 13px;
  color: var(--text-secondary);
  font-style: italic;
}

.no-history {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-muted);
}

.no-history ion-icon {
  font-size: 48px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.no-history p {
  margin: 0;
  font-size: 14px;
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

  .video-overview {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .video-meta {
    grid-template-columns: 1fr;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .performance-grid {
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