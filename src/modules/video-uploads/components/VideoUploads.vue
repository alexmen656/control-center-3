<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="videocam-outline" title="Video Uploads" />

      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <button class="action-btn primary" @click="toggleVideoForm">
              <ion-icon name="add-outline"></ion-icon>
              <span>Neues Video</span>
            </button>
          </div>

          <div class="action-group-right">
            <button class="action-btn secondary" @click="exportVideos()">
              <ion-icon name="download-outline"></ion-icon>
              <span>Export CSV</span>
            </button>
            <button class="action-btn secondary" @click="refreshVideos()">
              <ion-icon name="refresh-outline"></ion-icon>
              <span>Aktualisieren</span>
            </button>
            <div class="dropdown">
              <button class="action-btn secondary dropdown-toggle" @click="toggleDropdown">
                <ion-icon name="ellipsis-vertical-outline"></ion-icon>
              </button>
              <div class="dropdown-menu" :class="{ active: dropdownOpen }">
                <a @click="openAnalyticsModal()" class="dropdown-item">
                  <ion-icon name="analytics-outline"></ion-icon>
                  Analytics Dashboard
                </a>
                <a @click="openTemplatesModal()" class="dropdown-item">
                  <ion-icon name="library-outline"></ion-icon>
                  Video Vorlagen
                </a>
                <a @click="openBulkUploadModal()" class="dropdown-item">
                  <ion-icon name="cloud-upload-outline"></ion-icon>
                  Bulk Upload
                </a>
                <a @click="openApiConfig()" class="dropdown-item">
                  <ion-icon name="key-outline"></ion-icon>
                  API Konfiguration
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-icon">
              <ion-icon name="videocam-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ totalVideos }}</div>
              <div class="kpi-label">Gesamt Videos</div>
              <div class="kpi-trend positive">
                <ion-icon name="trending-up"></ion-icon>
                +{{ Math.abs(videoGrowth) }}%
              </div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon active">
              <ion-icon name="play-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ publishedVideos }}</div>
              <div class="kpi-label">Veröffentlichte Videos</div>
              <div class="kpi-subtitle">{{ scheduledVideos }} geplant</div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon success">
              <ion-icon name="eye-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ formatNumber(totalViews) }}</div>
              <div class="kpi-label">Gesamtaufrufe</div>
              <div class="kpi-trend positive">
                <ion-icon name="trending-up"></ion-icon>
                +{{ viewsGrowth }}%
              </div>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-icon warning">
              <ion-icon name="thumbs-up-outline"></ion-icon>
            </div>
            <div class="kpi-content">
              <div class="kpi-value">{{ avgEngagementRate }}%</div>
              <div class="kpi-label">Engagement Rate</div>
              <div class="kpi-subtitle">{{ formatNumber(totalEngagements) }} insgesamt</div>
            </div>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="search-box">
            <ion-icon name="search-outline"></ion-icon>
            <input type="text" v-model="searchTerm" @input="filterVideos" placeholder="Videos durchsuchen..."
              class="search-input" />
          </div>

          <div class="filter-controls">
            <select v-model="statusFilter" @change="filterVideos" class="modern-select">
              <option value="">Alle Status</option>
              <option value="draft">Entwurf</option>
              <option value="scheduled">Geplant</option>
              <option value="published">Veröffentlicht</option>
              <option value="processing">Verarbeitung</option>
              <option value="failed">Fehlgeschlagen</option>
            </select>

            <select v-model="platformFilter" @change="filterVideos" class="modern-select">
              <option value="">Alle Plattformen</option>
              <option value="youtube">YouTube</option>
              <option value="instagram">Instagram</option>
              <option value="tiktok">TikTok</option>
              <option value="facebook">Facebook</option>
              <option value="linkedin">LinkedIn</option>
            </select>
          </div>
        </div>

        <!-- Videos Data Table -->
        <div class="data-card">
          <div class="card-header">
            <h3>Video Liste</h3>
            <div class="header-actions">
              <div class="sort-controls">
                <span>Sortieren nach:</span>
                <button class="sort-button" :class="getSortClass('created_at')" @click="setSorting('created_at')">
                  Datum
                  <ion-icon :name="getSortIcon('created_at')"></ion-icon>
                </button>
                <button class="sort-button" :class="getSortClass('title')" @click="setSorting('title')">
                  Titel
                  <ion-icon :name="getSortIcon('title')"></ion-icon>
                </button>
                <button class="sort-button" :class="getSortClass('views')" @click="setSorting('views')">
                  Aufrufe
                  <ion-icon :name="getSortIcon('views')"></ion-icon>
                </button>
              </div>
            </div>
          </div>

          <div class="table-wrapper">
            <div class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="table-cell">Video</div>
                <div class="table-cell">Status</div>
                <div class="table-cell">Plattform</div>
                <div class="table-cell">Datum</div>
                <div class="table-cell">Performance</div>
                <div class="table-cell actions-header">Aktionen</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <!-- No Data State -->
                <div v-if="filteredVideos.length === 0" class="empty-state">
                  <div class="no-data-content">
                    <ion-icon name="videocam-outline" size="large"></ion-icon>
                    <h4>Keine Videos gefunden</h4>
                    <p>Erstellen Sie Ihr erstes Video, um zu beginnen.</p>
                    <button class="action-btn primary" @click="toggleVideoForm">
                      <ion-icon name="add-outline"></ion-icon>
                      Erstes Video erstellen
                    </button>
                  </div>
                </div>

                <!-- Video Rows -->
                <div v-for="video in paginatedVideos" :key="video.id" class="table-row" :class="{ 'row-hover': true }">

                  <div class="table-cell">
                    <div class="video-info">
                      <div class="video-thumbnail">
                        <img v-if="video.thumbnail_url" :src="video.thumbnail_url" alt="Video Thumbnail">
                        <div v-else class="default-thumbnail">
                          <ion-icon name="play-circle-outline"></ion-icon>
                        </div>
                      </div>
                      <div>
                        <div class="video-title">{{ video.title }}</div>
                        <div class="video-description">{{ video.description }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="table-cell">
                    <span class="status-badge" :class="video.status">
                      {{ getStatusText(video.status) }}
                    </span>
                  </div>

                  <div class="table-cell">
                    <div class="platform-badge" :class="video.platform">
                      <ion-icon :name="getPlatformIcon(video.platform)"></ion-icon>
                      {{ getPlatformText(video.platform) }}
                    </div>
                  </div>

                  <div class="table-cell">
                    <div class="date-info">
                      <div class="date-primary">{{ formatDate(video.publish_date) }}</div>
                      <div class="date-secondary">
                        {{ video.publish_time || 'Keine Zeit' }}
                      </div>
                    </div>
                  </div>

                  <div class="table-cell">
                    <div class="performance-metrics">
                      <div class="metric">
                        <span class="metric-value">{{ formatNumber(video.views) }}</span>
                        <span class="metric-label">Views</span>
                      </div>
                      <div class="metric">
                        <span class="metric-value">{{ video.like_rate }}%</span>
                        <span class="metric-label">Like Rate</span>
                      </div>
                    </div>
                  </div>

                  <div class="actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click="editVideo(video)" title="Bearbeiten">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn view-btn" @click="viewVideoDetails(video)" title="Details">
                        <ion-icon name="eye-outline"></ion-icon>
                      </button>

                      <!-- Upload Buttons für verfügbare Plattformen -->
                      <div class="upload-dropdown" v-if="video.status === 'draft' || video.status === 'scheduled'">
                        <button class="icon-btn upload-btn" @click="toggleUploadDropdown(video.id)" title="Hochladen">
                          <ion-icon name="cloud-upload-outline"></ion-icon>
                        </button>
                        <div class="upload-dropdown-menu" :class="{ 'active': video.uploadDropdownOpen }">
                          <button v-for="platform in availablePlatforms.filter(p => p.connected)" :key="platform.value"
                            class="upload-dropdown-item" @click="uploadToPlatform(video.id, platform.value)">
                            <ion-icon :name="platform.icon"></ion-icon>
                            {{ platform.name }}
                          </button>
                          <button class="upload-dropdown-item bulk-upload"
                            @click="bulkUploadToPlatforms(video.id, availablePlatforms.filter(p => p.connected).map(p => p.value))">
                            <ion-icon name="layers-outline"></ion-icon>
                            Alle Plattformen
                          </button>
                        </div>
                      </div>

                      <button v-if="video.status === 'draft'" class="icon-btn play-btn" @click="scheduleVideo(video.id)"
                        title="Planen">
                        <ion-icon name="calendar-outline"></ion-icon>
                      </button>
                      <button v-if="video.status === 'scheduled'" class="icon-btn pause-btn"
                        @click="unscheduleVideo(video.id)" title="Planung aufheben">
                        <ion-icon name="pause-outline"></ion-icon>
                      </button>
                      <button v-if="video.status === 'processing'" class="icon-btn processing-btn"
                        @click="checkUploadProgress(video.id)" title="Upload-Status prüfen">
                        <ion-icon name="sync-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteVideo(video.id)" title="Löschen">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="pagination" v-if="totalPages > 1">
            <button class="action-btn secondary" :disabled="currentPage === 1" @click="currentPage--">
              <ion-icon name="chevron-back"></ion-icon>
            </button>
            <span>Seite {{ currentPage }} von {{ totalPages }}</span>
            <button class="action-btn secondary" :disabled="currentPage === totalPages" @click="currentPage++">
              <ion-icon name="chevron-forward"></ion-icon>
            </button>
          </div>
        </div>

        <!-- Video Form Section -->
        <div class="form-section" :class="{ 'form-visible': showVideoForm }">
          <div class="form-card">
            <div class="form-header">
              <h3>{{ editingVideo ? 'Video bearbeiten' : 'Neues Video' }}</h3>
              <button class="close-form-btn" @click="closeVideoForm">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <div class="form-content">
              <form @submit.prevent="saveVideo" class="video-form">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Video Titel</label>
                    <input v-model="videoForm.title" type="text" class="modern-input" required
                      placeholder="Video Titel eingeben..." />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Status</label>
                    <select v-model="videoForm.status" class="modern-select" required>
                      <option value="draft">Entwurf</option>
                      <option value="scheduled">Geplant</option>
                      <option value="published">Veröffentlicht</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Beschreibung</label>
                  <textarea v-model="videoForm.description" class="modern-textarea" rows="3"
                    placeholder="Kurze Beschreibung des Videos..."></textarea>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Ziel-Plattformen</label>
                    <div class="platform-checkbox-grid">
                      <div v-for="platform in availablePlatforms" :key="platform.value" class="platform-checkbox">
                        <input type="checkbox" :id="'platform-' + platform.value" :value="platform.value"
                          v-model="videoForm.platforms" class="platform-checkbox-input">
                        <label :for="'platform-' + platform.value" class="platform-checkbox-label">
                          <div class="platform-checkbox-icon">
                            <ion-icon :name="platform.icon"></ion-icon>
                          </div>
                          <span class="platform-checkbox-text">{{ platform.name }}</span>
                        </label>
                      </div>
                    </div>
                    <div class="form-helper-text">
                      Wählen Sie alle Plattformen aus, auf die das Video hochgeladen werden soll.
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Kategorie</label>
                    <input v-model="videoForm.category" type="text" class="modern-input"
                      placeholder="z.B. Unterhaltung, Bildung, Musik" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Veröffentlichungsdatum</label>
                    <input v-model="videoForm.publish_date" type="date" class="modern-input" required />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Veröffentlichungszeit</label>
                    <input v-model="videoForm.publish_time" type="time" class="modern-input" />
                  </div>
                </div>

                <div class="form-group" v-if="!editingVideo || !videoForm.video_file">
                  <label class="form-label">Video Datei</label>
                  <div class="file-upload-container">
                    <div class="file-upload-box" @click="triggerFileInput" @dragover.prevent
                      @drop.prevent="handleFileDrop">
                      <input type="file" ref="fileInput" @change="handleFileSelect" style="display: none;"
                        accept="video/*">
                      <ion-icon name="cloud-upload-outline" size="large"></ion-icon>
                      <div class="upload-text" v-if="!videoFile">
                        <p>Klicken oder Datei hierher ziehen</p>
                        <span>MP4, MOV, oder WEBM Dateien bis 1GB</span>
                      </div>
                      <div class="file-info" v-else>
                        <span class="file-name">{{ videoFile.name }}</span>
                        <span class="file-size">{{ formatFileSize(videoFile.size) }}</span>
                      </div>
                    </div>

                    <!-- Upload Progress Bar -->
                    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="upload-progress-container">
                      <div class="upload-progress-text">
                        <span>Hochladen: {{ uploadProgress }}%</span>
                        <span>{{ formatFileSize(uploadedBytes) }} von {{ formatFileSize(totalBytes) }}</span>
                      </div>
                      <div class="upload-progress-bar-container">
                        <div class="upload-progress-bar" :style="{ width: uploadProgress + '%' }"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-group" v-if="!editingVideo || !videoForm.thumbnail_url">
                  <label class="form-label">Video Thumbnail (optional)</label>
                  <div class="file-upload-container">
                    <div class="file-upload-box" @click="triggerThumbnailInput" @dragover.prevent
                      @drop.prevent="handleThumbnailDrop">
                      <input type="file" ref="thumbnailInput" @change="handleThumbnailSelect" style="display: none;"
                        accept="image/*">
                      <ion-icon name="image-outline" size="large"></ion-icon>
                      <div class="upload-text" v-if="!thumbnailFile">
                        <p>Klicken oder Thumbnail hierher ziehen</p>
                        <span>JPG, PNG oder GIF Dateien</span>
                      </div>
                      <div class="file-info" v-else>
                        <span class="file-name">{{ thumbnailFile.name }}</span>
                        <span class="file-size">{{ formatFileSize(thumbnailFile.size) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Tags</label>
                  <input v-model="videoForm.tags" type="text" class="modern-input"
                    placeholder="Tags mit Kommas trennen (z.B. sport,fitness,tutorial)" />
                </div>

                <div class="form-group">
                  <label class="form-label">Video Ziele</label>
                  <textarea v-model="videoForm.goals" class="modern-textarea" rows="2"
                    placeholder="z.B. 10k Aufrufe erreichen, Community aufbauen..."></textarea>
                </div>

                <div class="form-actions">
                  <button type="button" class="action-btn secondary" @click="closeVideoForm">
                    Abbrechen
                  </button>
                  <button type="submit" class="action-btn primary" :disabled="saving">
                    {{ saving ? 'Speichern...' : (editingVideo ? 'Aktualisieren' : 'Erstellen') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Video Detail View -->
        <div class="detail-section" :class="{ 'detail-visible': showDetailModal }">
          <div class="detail-card">
            <div class="detail-header">
              <h3>Video Details</h3>
              <button class="close-detail-btn" @click="closeDetailModal">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>

            <div class="detail-content" v-if="selectedVideo">
              <div class="detail-grid">
                <!-- Video Info Section -->
                <div class="detail-info-section">
                  <h4>Video Informationen</h4>
                  <div class="detail-item">
                    <span class="detail-label">Titel:</span>
                    <span class="detail-value">{{ selectedVideo.title }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Beschreibung:</span>
                    <span class="detail-value">{{ selectedVideo.description || 'Keine Beschreibung' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge" :class="selectedVideo.status">
                      {{ getStatusText(selectedVideo.status) }}
                    </span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Plattform:</span>
                    <div class="platform-badge" :class="selectedVideo.platform">
                      <ion-icon :name="getPlatformIcon(selectedVideo.platform)"></ion-icon>
                      {{ getPlatformText(selectedVideo.platform) }}
                    </div>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Kategorie:</span>
                    <span class="detail-value">{{ selectedVideo.category || 'Keine Kategorie' }}</span>
                  </div>
                </div>

                <!-- Publishing Info Section -->
                <div class="detail-info-section">
                  <h4>Veröffentlichung</h4>
                  <div class="detail-item">
                    <span class="detail-label">Datum:</span>
                    <span class="detail-value">{{ formatDate(selectedVideo.publish_date) }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Zeit:</span>
                    <span class="detail-value">{{ selectedVideo.publish_time || 'Keine Zeit angegeben' }}</span>
                  </div>
                  <div class="detail-item" v-if="selectedVideo.scheduled_time">
                    <span class="detail-label">Geplant für:</span>
                    <span class="detail-value">{{ formatDateTime(selectedVideo.scheduled_time) }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Erstellt am:</span>
                    <span class="detail-value">{{ formatDateTime(selectedVideo.created_at) }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Zuletzt bearbeitet:</span>
                    <span class="detail-value">{{ formatDateTime(selectedVideo.updated_at) }}</span>
                  </div>
                </div>

                <!-- Performance Section -->
                <div class="detail-info-section">
                  <h4>Performance</h4>
                  <div class="performance-grid">
                    <div class="performance-metric">
                      <div class="metric-icon">
                        <ion-icon name="eye-outline"></ion-icon>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(selectedVideo.views) }}</div>
                        <div class="metric-label">Aufrufe</div>
                      </div>
                    </div>
                    <div class="performance-metric">
                      <div class="metric-icon">
                        <ion-icon name="thumbs-up-outline"></ion-icon>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(selectedVideo.likes) }}</div>
                        <div class="metric-label">Likes</div>
                      </div>
                    </div>
                    <div class="performance-metric">
                      <div class="metric-icon">
                        <ion-icon name="chatbubble-outline"></ion-icon>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(selectedVideo.comments) }}</div>
                        <div class="metric-label">Kommentare</div>
                      </div>
                    </div>
                    <div class="performance-metric">
                      <div class="metric-icon">
                        <ion-icon name="share-outline"></ion-icon>
                      </div>
                      <div class="metric-info">
                        <div class="metric-value">{{ formatNumber(selectedVideo.shares) }}</div>
                        <div class="metric-label">Shares</div>
                      </div>
                    </div>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Like Rate:</span>
                    <span class="detail-value">{{ selectedVideo.like_rate }}%</span>
                  </div>
                </div>

                <!-- Media Section -->
                <div class="detail-info-section">
                  <h4>Medien</h4>
                  <div class="media-preview">
                    <div class="thumbnail-preview" v-if="selectedVideo.thumbnail_url">
                      <img :src="selectedVideo.thumbnail_url" alt="Video Thumbnail">
                    </div>
                    <div class="media-info">
                      <div class="detail-item" v-if="selectedVideo.video_file">
                        <span class="detail-label">Video Datei:</span>
                        <span class="detail-value">{{ selectedVideo.video_file }}</span>
                      </div>
                      <div class="detail-item" v-if="selectedVideo.platform_video_id">
                        <span class="detail-label">Plattform Video ID:</span>
                        <span class="detail-value">{{ selectedVideo.platform_video_id }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tags and Goals Section -->
                <div class="detail-info-section full-width">
                  <h4>Tags & Ziele</h4>
                  <div class="detail-item" v-if="selectedVideo.tags">
                    <span class="detail-label">Tags:</span>
                    <div class="tags-container">
                      <span v-for="tag in getTagsArray(selectedVideo.tags)" :key="tag" class="tag-badge">
                        {{ tag }}
                      </span>
                    </div>
                  </div>
                  <div class="detail-item" v-if="selectedVideo.goals">
                    <span class="detail-label">Ziele:</span>
                    <span class="detail-value">{{ selectedVideo.goals }}</span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="detail-actions">
                <button class="action-btn secondary" @click="editVideo(selectedVideo)">
                  <ion-icon name="create-outline"></ion-icon>
                  Bearbeiten
                </button>
                <button v-if="selectedVideo.status === 'draft' || selectedVideo.status === 'scheduled'"
                  class="action-btn primary" @click="uploadToPlatform(selectedVideo.id, selectedVideo.platform)">
                  <ion-icon name="cloud-upload-outline"></ion-icon>
                  Hochladen
                </button>
                <button class="action-btn danger" @click="deleteVideo(selectedVideo.id)">
                  <ion-icon name="trash-outline"></ion-icon>
                  Löschen
                </button>
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

export default {
  name: "VideoUploads",
  components: {
    SiteTitle,
  },
  data() {
    return {
      videos: [],
      filteredVideos: [],
      loading: true,
      error: null,
      searchTerm: '',
      statusFilter: '',
      platformFilter: '',
      sortField: 'created_at',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 20,
      showVideoForm: false,
      editingVideo: null,
      saving: false,
      dropdownOpen: false,
      videoFile: null,
      thumbnailFile: null,
      showDetailModal: false,
      selectedVideo: null,
      uploadProgress: 0,
      uploadedBytes: 0,
      totalBytes: 0,
      uploading: false,
      availablePlatforms: [
        { value: 'youtube', name: 'YouTube', icon: 'logo-youtube' },
        { value: 'instagram', name: 'Instagram', icon: 'logo-instagram' },
        { value: 'tiktok', name: 'TikTok', icon: 'logo-tiktok' },
        { value: 'facebook', name: 'Facebook', icon: 'logo-facebook' },
        { value: 'linkedin', name: 'LinkedIn', icon: 'logo-linkedin' }
      ],
      connectedPlatforms: {
        youtube: false,
        instagram: false,
        tiktok: false,
        facebook: false,
        linkedin: false
      },
      videoForm: {
        title: '',
        description: '',
        status: 'draft',
        platforms: [],
        category: '',
        publish_date: '',
        publish_time: '',
        video_file: '',
        thumbnail_url: '',
        tags: '',
        goals: ''
      },
      totalVideos: 0,
      publishedVideos: 0,
      scheduledVideos: 0,
      totalViews: 0,
      totalEngagements: 0,
      avgEngagementRate: 0,
      videoGrowth: 12,
      viewsGrowth: 18
    };
  },

  computed: {
    paginatedVideos() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredVideos.slice(start, end);
    },

    totalPages() {
      return Math.ceil(this.filteredVideos.length / this.itemsPerPage);
    }
  },

  mounted() {
    this.loadVideos();
    this.loadConnectedPlatforms();
  },

  methods: {
    async loadVideos() {
      this.loading = true;
      this.error = null;

      try {
        const response = await this.$axios.get('video_uploads.php', {
          params: {
            action: 'list',
            project: this.$route.params.project
          }
        });

        this.videos = (response.data.videos || []).map(video => ({
          ...video,
          uploadDropdownOpen: false,
          platforms: video.platforms_array || (video.platform ? [video.platform] : [])
        }));
        this.filteredVideos = [...this.videos];

        this.totalVideos = this.videos.length;
        this.publishedVideos = this.videos.filter(v => v.status === 'published').length;
        this.scheduledVideos = this.videos.filter(v => v.status === 'scheduled').length;

        this.totalViews = this.videos.reduce((sum, video) => sum + (video.views || 0), 0);
        const totalLikes = this.videos.reduce((sum, video) => sum + (video.likes || 0), 0);
        const totalComments = this.videos.reduce((sum, video) => sum + (video.comments || 0), 0);
        const totalShares = this.videos.reduce((sum, video) => sum + (video.shares || 0), 0);
        this.totalEngagements = totalLikes + totalComments + totalShares;

        if (this.totalViews > 0) {
          this.avgEngagementRate = Math.round((this.totalEngagements / this.totalViews) * 100);
        } else {
          this.avgEngagementRate = 0;
        }

      } catch (error) {
        console.error('Error loading videos:', error);
        this.error = 'Fehler beim Laden der Videos';
      } finally {
        this.loading = false;
      }
    },

    filterVideos() {
      let filtered = [...this.videos];

      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        filtered = filtered.filter(video =>
          video.title.toLowerCase().includes(term) ||
          video.description.toLowerCase().includes(term) ||
          (video.tags && video.tags.toLowerCase().includes(term))
        );
      }

      if (this.statusFilter) {
        filtered = filtered.filter(video => video.status === this.statusFilter);
      }

      if (this.platformFilter) {
        filtered = filtered.filter(video => video.platform === this.platformFilter);
      }

      filtered.sort((a, b) => {
        let valueA = a[this.sortField];
        let valueB = b[this.sortField];

        if (this.sortField === 'views' || this.sortField === 'likes') {
          valueA = Number(valueA) || 0;
          valueB = Number(valueB) || 0;
        }

        if (valueA < valueB) return this.sortDirection === 'asc' ? -1 : 1;
        if (valueA > valueB) return this.sortDirection === 'asc' ? 1 : -1;
        return 0;
      });

      this.filteredVideos = filtered;
      this.currentPage = 1;
    },

    setSorting(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }

      this.filterVideos();
    },

    getSortIcon(field) {
      if (this.sortField !== field) return 'chevron-down';
      return this.sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
    },

    getSortClass(field) {
      if (this.sortField !== field) return 'sort-default';
      return 'sort-active';
    },

    toggleVideoForm() {
      this.showVideoForm = !this.showVideoForm;
      if (!this.showVideoForm) {
        this.resetForm();
      }
    },

    closeVideoForm() {
      this.showVideoForm = false;
      this.resetForm();
    },

    resetForm() {
      this.editingVideo = null;
      this.videoFile = null;
      this.thumbnailFile = null;
      this.uploadProgress = 0;
      this.uploadedBytes = 0;
      this.totalBytes = 0;
      this.uploading = false;
      this.videoForm = {
        title: '',
        description: '',
        status: 'draft',
        platforms: [],
        category: '',
        publish_date: '',
        publish_time: '',
        video_file: '',
        thumbnail_url: '',
        tags: '',
        goals: ''
      };
    },

    openApiConfig() {
      this.$router.push(`/project/${this.$route.params.project}/video-uploads/config`);
    },

    editVideo(video) {
      this.editingVideo = video;
      this.videoForm = { ...video };

      if (video.platforms && Array.isArray(video.platforms)) {
        this.videoForm.platforms = [...video.platforms];
      } else if (video.platforms_array && Array.isArray(video.platforms_array)) {
        this.videoForm.platforms = [...video.platforms_array];
      } else if (video.platform) {
        this.videoForm.platforms = [video.platform];
      } else {
        this.videoForm.platforms = [];
      }

      this.closeDetailModal();
      this.showVideoForm = true;
    },

    async saveVideo() {
      this.saving = true;

      try {
        if (!this.videoForm.platforms || this.videoForm.platforms.length === 0) {
          this.error = 'Bitte wählen Sie mindestens eine Plattform aus';
          this.saving = false;
          return;
        }

        const formData = new FormData();

        Object.keys(this.videoForm).forEach(key => {
          if (key === 'platforms') {
            this.videoForm.platforms.forEach(platform => {
              formData.append('platforms[]', platform);
            });
          } else {
            formData.append(key, this.videoForm[key]);
          }
        });

        formData.append('project', this.$route.params.project);
        formData.append('action', this.editingVideo ? 'update' : 'create');

        if (this.editingVideo) {
          formData.append('id', this.editingVideo.id);
        }

        if (this.videoFile) {
          formData.append('video_file', this.videoFile);

          return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
              if (e.lengthComputable) {
                this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                this.uploadedBytes = e.loaded;
                this.totalBytes = e.total;
              }
            });

            xhr.onreadystatechange = () => {
              if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                  this.closeVideoForm();
                  this.loadVideos();
                  resolve();
                } else {
                  this.error = 'Fehler beim Speichern des Videos';
                  reject(new Error('Upload failed'));
                }
                this.saving = false;
              }
            };

            xhr.open('POST', 'https://alex.polan.sk/control-center/video_uploads.php', true);
            const token = localStorage.getItem('token');

            if (token) {
              xhr.setRequestHeader('Authorization', token);
            }

            xhr.send(formData);

          });
        }

        if (this.thumbnailFile) {
          formData.append('thumbnail_file', this.thumbnailFile);
        }

        await this.$axios.post('video_uploads.php', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.closeVideoForm();
        this.loadVideos();
      } catch (error) {
        console.error('Error saving video:', error);
        this.error = 'Fehler beim Speichern des Videos';
      } finally {
        this.saving = false;
      }
    },

    async deleteVideo(videoId) {
      if (!confirm('Sind Sie sicher, dass Sie dieses Video löschen möchten?')) {
        return;
      }

      try {
        await this.$axios.post('video_uploads.php', this.$qs.stringify({
          action: 'delete',
          id: videoId,
          project: this.$route.params.project
        }));

        this.loadVideos();
      } catch (error) {
        console.error('Error deleting video:', error);
        this.error = 'Fehler beim Löschen des Videos';
      }
    },

    async scheduleVideo(videoId) {
      try {
        await this.$axios.post('video_uploads.php', this.$qs.stringify({
          action: 'update_status',
          id: videoId,
          status: 'scheduled',
          project: this.$route.params.project
        }));

        this.loadVideos();
      } catch (error) {
        console.error('Error scheduling video:', error);
      }
    },

    async unscheduleVideo(videoId) {
      try {
        await this.$axios.post('video_uploads.php', this.$qs.stringify({
          action: 'update_status',
          id: videoId,
          status: 'draft',
          project: this.$route.params.project
        }));

        this.loadVideos();
      } catch (error) {
        console.error('Error unscheduling video:', error);
      }
    },

    async uploadToPlatform(videoId, platform) {
      if (!confirm(`Möchten Sie das Video zu ${platform} hochladen?`)) {
        return;
      }

      try {
        const response = await this.$axios.post('video_uploads.php', this.$qs.stringify({
          action: 'upload_to_platform',
          video_id: videoId,
          platform: platform,
          project: this.$route.params.project
        }));

        if (response.data.success) {
          this.showSuccessMessage(`Video erfolgreich zu ${platform} hochgeladen!`);
          this.loadVideos();
        } else {
          this.showErrorMessage(`Fehler beim Upload zu ${platform}: ${response.data.error}`);
        }
      } catch (error) {
        console.error('Error uploading to platform:', error);
        this.showErrorMessage(`Fehler beim Upload zu ${platform}`);
      }
    },

    async scheduleUpload(videoId, platform, scheduledTime) {
      try {
        const response = await this.$axios.post('video_uploads.php', this.$qs.stringify({
          action: 'schedule_upload',
          video_id: videoId,
          platform: platform,
          scheduled_time: scheduledTime,
          project: this.$route.params.project
        }));

        if (response.data.success) {
          this.showSuccessMessage(`Upload für ${platform} geplant!`);
          this.loadVideos();
        } else {
          this.showErrorMessage(`Fehler beim Planen: ${response.data.error}`);
        }
      } catch (error) {
        console.error('Error scheduling upload:', error);
        this.showErrorMessage('Fehler beim Planen des Uploads');
      }
    },

    async checkUploadProgress(videoId) {
      try {
        const response = await this.$axios.get('video_uploads.php', {
          params: {
            action: 'get_upload_progress',
            video_id: videoId,
            project: this.$route.params.project
          }
        });

        return response.data.progress;
      } catch (error) {
        console.error('Error checking upload progress:', error);
        return null;
      }
    },

    async loadConnectedPlatforms() {
      try {
        const response = await this.$axios.get('video_uploads_config.php', {
          params: {
            action: 'get_connections',
            project: this.$route.params.project
          }
        });

        if (response.data.connections) {
          this.connectedPlatforms = response.data.connections;

          this.availablePlatforms.forEach(platform => {
            platform.connected = this.connectedPlatforms[platform.value]?.connected || false;
          });
        }
      } catch (error) {
        console.error('Error loading connected platforms:', error);
      }
    },

    async bulkUploadToPlatforms(videoId, platforms) {
      const promises = platforms.map(platform =>
        this.uploadToPlatform(videoId, platform)
      );

      try {
        await Promise.all(promises);
        this.showSuccessMessage('Alle Uploads erfolgreich gestartet!');
      } catch (error) {
        console.error('Error in bulk upload:', error);
        this.showErrorMessage('Fehler beim Bulk-Upload');
      }
    },

    toggleUploadDropdown(videoId) {
      this.videos.forEach(video => {
        if (video.id !== videoId) {
          video.uploadDropdownOpen = false;
        }
      });

      const video = this.videos.find(v => v.id === videoId);
      if (video) {
        video.uploadDropdownOpen = !video.uploadDropdownOpen;
      }
    },

    showSuccessMessage(message) {
      console.log('✅ Success:', message);
      // You can integrate a toast library like vue-toastification here
    },

    showErrorMessage(message) {
      console.error('❌ Error:', message);
      // You can integrate a toast library like vue-toastification here
    },

    viewVideoDetails(video) {
      this.selectedVideo = video;
      this.showDetailModal = true;
    },

    closeDetailModal() {
      this.showDetailModal = false;
      this.selectedVideo = null;
    },

    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
    },

    openAnalyticsModal() {
      console.log('Open analytics modal');
      this.dropdownOpen = false;
    },

    openTemplatesModal() {
      console.log('Open templates modal');
      this.dropdownOpen = false;
    },

    openBulkUploadModal() {
      console.log('Open bulk upload modal');
      this.dropdownOpen = false;
    },

    async exportVideos() {
      try {
        const csvContent = [
          ['Titel', 'Status', 'Plattform', 'Veröffentlichungsdatum', 'Kategorie', 'Aufrufe', 'Likes', 'Kommentare'],
          ...this.filteredVideos.map(video => [
            video.title,
            this.getStatusText(video.status),
            this.getPlatformText(video.platform),
            video.publish_date,
            video.category || '',
            video.views || 0,
            video.likes || 0,
            video.comments || 0
          ])
        ].map(row => row.join(',')).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `video_uploads_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error exporting videos:', error);
      }
    },

    refreshVideos() {
      this.loadVideos();
    },

    triggerFileInput() {
      this.$refs.fileInput.click();
    },

    triggerThumbnailInput() {
      this.$refs.thumbnailInput.click();
    },

    handleFileSelect(e) {
      const file = e.target.files[0];
      if (file) {
        this.videoFile = file;
        this.totalBytes = file.size;
      }
    },

    handleThumbnailSelect(e) {
      const file = e.target.files[0];
      if (file) {
        this.thumbnailFile = file;
      }
    },   

    handleFileDrop(e) {
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('video/')) {
        this.videoFile = file;
      }
    },

    handleThumbnailDrop(e) {
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        this.thumbnailFile = file;
      }
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString('de-DE');
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';

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
        processing: 'Verarbeitung',
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
    },

    formatDateTime(dateTimeStr) {
      if (!dateTimeStr) return '';
      const date = new Date(dateTimeStr);
      return date.toLocaleDateString('de-DE') + ' ' + date.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    getTagsArray(tagsStr) {
      if (!tagsStr) return [];
      return tagsStr.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
    }
  }
};
</script>

<style scoped>
/* Modern Design System - inheriting from MarketingCampaigns.vue */
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

.action-btn.danger {
  background: #fef2f2;
  color: var(--danger-color);
  border: 1px solid var(--danger-color);
}

.action-btn.danger:hover {
  background: #fee2e2;
}

.action-btn ion-icon {
  font-size: 16px;
}

/* Dropdown */
.dropdown {
  position: relative;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 220px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
  z-index: 100;
  display: none;
  overflow: hidden;
}

.dropdown-menu.active {
  display: block;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  color: var(--text-secondary);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: var(--background);
  color: var(--text-primary);
}

/* KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.kpi-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 16px;
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

.kpi-icon.active {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.kpi-icon.success {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
}

.kpi-icon.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
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

.kpi-trend.negative {
  color: var(--danger-color);
}

/* Filter Bar */
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
}

.search-box {
  position: relative;
  flex: 1;
}

.search-box ion-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
}

.search-input {
  width: 100%;
  padding: 10px 16px 10px 42px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.filter-controls {
  display: flex;
  gap: 12px;
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
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.sort-controls {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  color: var(--text-secondary);
}

.sort-button {
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--text-secondary);
  font-size: 14px;
  transition: all 0.2s ease;
}

.sort-button:hover {
  color: var(--text-primary);
}

.sort-button.sort-active {
  color: var(--primary-color);
  font-weight: 500;
}

/* Modern Table */
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

.table-header .table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.actions-header {
  flex: 0 0 180px;
  justify-content: center;
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
  flex: 0 0 180px;
  justify-content: center;
  padding: 12px 16px;
}

/* Video specific cells */
.video-info {
  display: flex;
  gap: 16px;
  align-items: center;
  max-width: 300px;
}

.video-thumbnail {
  width: 72px;
  height: 40px;
  border-radius: 4px;
  overflow: hidden;
  background-color: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.default-thumbnail {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: 24px;
}

.video-title {
  font-weight: 600;
  margin-bottom: 4px;
}

.video-description {
  font-size: 12px;
  color: var(--text-secondary);
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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

.platform-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 500;
}

.platform-badge.youtube {
  color: #ff0000;
}

.platform-badge.instagram {
  color: #e1306c;
}

.platform-badge.tiktok {
  color: #000000;
}

.platform-badge.facebook {
  color: #1877f2;
}

.platform-badge.linkedin {
  color: #0077b5;
}

.date-info {
  flex-direction: column;
  align-items: flex-start;
}

.date-primary {
  font-weight: 500;
  margin-bottom: 2px;
}

.date-secondary {
  font-size: 12px;
  color: var(--text-secondary);
}

.performance-metrics {
  display: flex;
  gap: 16px;
}

.metric {
  text-align: center;
}

.metric-value {
  display: block;
  font-weight: 600;
  font-size: 14px;
}

.metric-label {
  display: block;
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
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

.edit-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

.view-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.view-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.play-btn {
  background: #ecfdf5;
  color: var(--success-color);
}

.play-btn:hover {
  background: #d1fae5;
  transform: scale(1.05);
}

.pause-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.pause-btn:hover {
  background: #fde68a;
  transform: scale(1.05);
}

.upload-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.upload-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.processing-btn {
  background: #fef3c7;
  color: var(--warning-color);
}

.processing-btn:hover {
  background: #fde68a;
  transform: scale(1.05);
}

/* Upload Dropdown */
.upload-dropdown {
  position: relative;
}

.upload-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 180px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
  z-index: 100;
  display: none;
  overflow: hidden;
}

.upload-dropdown-menu.active {
  display: block;
}

.upload-dropdown-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 10px 16px;
  color: var(--text-secondary);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  background: none;
  text-align: left;
}

.upload-dropdown-item:hover {
  background-color: var(--background);
  color: var(--text-primary);
}

.upload-dropdown-item.bulk-upload {
  border-top: 1px solid var(--border);
  font-weight: 500;
}

.upload-dropdown-item.bulk-upload:hover {
  background-color: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

/* No Data State */
.empty-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-content ion-icon {
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
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  border-top: 1px solid var(--border);
}

.pagination button[disabled] {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Form Section */
.form-section {
  position: fixed;
  top: 0;
  right: -700px;
  width: 700px;
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
  font-size: 20px;
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

/* Video Form */
.video-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-textarea {
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  font-family: inherit;
}

.modern-input:focus,
.modern-textarea:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-helper-text {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
  line-height: 1.4;
}

/* File Upload */
.file-upload-container {
  margin-top: 4px;
}

.file-upload-box {
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: var(--background);
}

.file-upload-box:hover {
  border-color: var(--primary-color);
  background-color: rgba(37, 99, 235, 0.03);
}

.file-upload-box ion-icon {
  color: var(--text-secondary);
  margin-bottom: 12px;
}

.upload-text {
  text-align: center;
}

.upload-text p {
  margin: 0 0 4px 0;
  font-size: 15px;
  font-weight: 500;
  color: var(--text-primary);
}

.upload-text span {
  font-size: 13px;
  color: var(--text-secondary);
}

.file-info {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.file-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.file-size {
  font-size: 12px;
  color: var(--text-secondary);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
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

  .action-group-left,
  .action-group-right {
    flex-wrap: wrap;
    justify-content: center;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-controls {
    flex-wrap: wrap;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .modern-table {
    min-width: 800px;
  }

  .form-section {
    width: 100%;
    right: -100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Platform Checkbox Grid */
.platform-checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-top: 8px;
}

.platform-checkbox {
  position: relative;
}

.platform-checkbox-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.platform-checkbox-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 12px;
  border: 2px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  min-height: 80px;
  justify-content: center;
}

.platform-checkbox-label:hover {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.platform-checkbox-input:checked+.platform-checkbox-label {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.platform-checkbox-icon {
  font-size: 24px;
  margin-bottom: 8px;
}

.platform-checkbox-text {
  font-size: 14px;
  font-weight: 500;
}

/* Detail View */
.detail-section {
  position: fixed;
  top: 0;
  right: -800px;
  width: 800px;
  height: 100vh;
  background: var(--surface);
  box-shadow: var(--shadow-lg);
  transition: right 0.3s ease;
  z-index: 1000;
  border-left: 1px solid var(--border);
}

.detail-section.detail-visible {
  right: 0;
}

.detail-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.detail-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.close-detail-btn {
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

.close-detail-btn:hover {
  background: var(--text-muted);
  color: var(--surface);
}

.detail-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.detail-info-section {
  background: var(--background);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--border);
}

.detail-info-section.full-width {
  grid-column: 1 / -1;
}

.detail-info-section h4 {
  margin: 0 0 16px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  border-bottom: 1px solid var(--border);
  padding-bottom: 8px;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-label {
  font-weight: 500;
  color: var(--text-secondary);
  min-width: 120px;
  margin-right: 12px;
}

.detail-value {
  color: var(--text-primary);
  flex: 1;
  word-break: break-word;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.performance-metric {
  display: flex;
  align-items: center;
  background: var(--surface);
  border-radius: 8px;
  padding: 12px;
  border: 1px solid var(--border);
}

.metric-icon {
  background: rgba(37, 99, 235, 0.1);
  border-radius: 8px;
  padding: 8px;
  margin-right: 12px;
  color: var(--primary-color);
  font-size: 20px;
}

.metric-info {
  flex: 1;
}

.metric-value {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.metric-label {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.media-preview {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.thumbnail-preview {
  flex-shrink: 0;
}

.thumbnail-preview img {
  width: 120px;
  height: 68px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--border);
}

.media-info {
  flex: 1;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
}

.tag-badge {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.detail-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

/* Responsive Design */
@media (max-width: 768px) {
  .detail-section {
    width: 100%;
    right: -100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .performance-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .media-preview {
    flex-direction: column;
  }

  .platform-checkbox-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .detail-actions {
    flex-direction: column;
    gap: 8px;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
