<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-left">
            <button class="back-button" @click="goBack">
              <ion-icon name="arrow-back-outline"></ion-icon>
            </button>
            <div class="header-content">
              <h1>Screenshots verwalten</h1>
              <p v-if="version">Version {{ version.version_string }} • <span class="status-badge"
                  :class="version.status">{{ version.status }}</span></p>
            </div>
          </div>
          <button class="action-btn primary" @click="triggerFileInput">
            <ion-icon name="cloud-upload-outline"></ion-icon>
            Hochladen
          </button>
        </div>

        <!-- Device Type Selector -->
        <div class="tab-navigation device-tabs">
          <button v-for="device in deviceTypes" :key="device.type" class="tab-btn"
            :class="{ active: activeDevice === device.type }" @click="activeDevice = device.type">
            <ion-icon :name="device.icon"></ion-icon>
            {{ device.label }}
          </button>
        </div>

        <!-- Locale Tabs -->
        <div class="tab-navigation locale-tabs">
          <button v-for="loc in localizations" :key="loc.locale" class="tab-btn"
            :class="{ active: activeLocale === loc.locale }" @click="activeLocale = loc.locale">
            <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
            {{ loc.locale }}
          </button>
          <button class="tab-btn add-tab" @click="showAddLocaleModal = true" v-if="availableLocales.length > 0">
            <ion-icon name="add-outline"></ion-icon>
          </button>
        </div>

        <!-- Screenshots Section -->
        <div class="data-card">
          <div class="card-header">
            <h3>Screenshots für {{ activeDevice }} - {{ activeLocale }}</h3>
            <span class="badge">{{ currentScreenshots.length }}/10</span>
          </div>

          <div class="screenshots-drop-zone" @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
            @drop.prevent="handleDrop" :class="{ dragging: isDragging }">
            <input ref="fileInput" type="file" accept="image/png,image/jpeg" multiple hidden
              @change="handleFileSelect" />

            <div v-if="currentScreenshots.length === 0" class="empty-state" @click="triggerFileInput">
              <div class="empty-icon">
                <ion-icon name="images-outline"></ion-icon>
              </div>
              <h4>Keine Screenshots</h4>
              <p>Ziehe Screenshots hierhin oder klicke zum Hochladen</p>
              <span class="hint">PNG oder JPEG, max. 10 Screenshots</span>
            </div>

            <div v-else class="screenshots-grid">
              <div v-for="(element, index) in currentScreenshots" :key="element.id" class="screenshot-item"
                draggable="true" @dragstart="dragStart(index)" @dragover.prevent="dragOver(index)" @drop="drop(index)"
                @dragend="dragEnd">
                <div class="screenshot-preview">
                  <img :src="screenshotUrl(element)" :alt="`Screenshot ${index + 1}`" />
                  <div class="screenshot-overlay">
                    <button class="overlay-btn" @click="viewScreenshot(element)">
                      <ion-icon name="eye-outline"></ion-icon>
                    </button>
                    <button class="overlay-btn danger" @click="confirmDeleteScreenshot(element)">
                      <ion-icon name="trash-outline"></ion-icon>
                    </button>
                  </div>
                </div>
                <div class="screenshot-info">
                  <span class="order-badge">{{ index + 1 }}</span>
                  <span class="display-type">{{ element.display_type || 'Standard' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Guidelines -->
          <div class="guidelines">
            <h4>Screenshot-Richtlinien</h4>
            <div class="guidelines-grid">
              <div class="guideline-item" v-for="(size, key) in screenshotSizes[activeDevice]" :key="key">
                <span class="size-label">{{ key }}</span>
                <span class="size-value">{{ size }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploading" class="upload-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
          </div>
          <span>{{ uploadProgress }}% hochgeladen</span>
        </div>
      </div>

      <!-- Screenshot Preview Modal -->
      <div v-if="previewScreenshot" class="custom-modal-overlay" @click.self="previewScreenshot = null">
        <div class="preview-modal">
          <button class="close-preview" @click="previewScreenshot = null">
            <ion-icon name="close-outline"></ion-icon>
          </button>
          <img :src="screenshotUrl(previewScreenshot)" :alt="'Preview'" />
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="custom-modal-overlay" @click.self="showDeleteModal = false">
        <div class="custom-modal-content">
          <div class="custom-modal-header danger">
            <h3>Screenshot löschen</h3>
            <button class="modal-close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <p>Bist du sicher, dass du diesen Screenshot löschen möchtest?</p>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showDeleteModal = false">Abbrechen</button>
              <button class="action-btn danger" @click="deleteScreenshot">
                <ion-icon name="trash-outline"></ion-icon>
                Löschen
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Locale Modal -->
      <div v-if="showAddLocaleModal" class="custom-modal-overlay" @click.self="showAddLocaleModal = false">
        <div class="custom-modal-content">
          <div class="custom-modal-header">
            <h3>Sprache für Screenshots</h3>
            <button class="modal-close-btn" @click="showAddLocaleModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Sprache wählen</label>
              <select v-model="newLocale" class="modern-select">
                <option value="">Wählen...</option>
                <option v-for="loc in availableLocales" :key="loc.code" :value="loc.code">
                  {{ loc.name }} ({{ loc.code }})
                </option>
              </select>
            </div>
            <div class="form-actions">
              <button class="action-btn secondary" @click="showAddLocaleModal = false">Abbrechen</button>
              <button class="action-btn primary" @click="addLocale" :disabled="!newLocale">
                Hinzufügen
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: 'ScreenshotManager',
  props: {
    appId: {
      type: [String, Number],
      required: true
    },
    versionId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      version: null,
      screenshots: [],
      localizations: [],
      allLocales: [],
      activeDevice: 'iphone_65',
      activeLocale: 'de-DE',
      isDragging: false,
      uploading: false,
      uploadProgress: 0,
      previewScreenshot: null,
      showDeleteModal: false,
      screenshotToDelete: null,
      showAddLocaleModal: false,
      newLocale: '',
      deviceTypes: [
        { type: 'iphone_65', label: 'iPhone 6.5"', icon: 'phone-portrait-outline' },
        { type: 'iphone_55', label: 'iPhone 5.5"', icon: 'phone-portrait-outline' },
        { type: 'ipad_129', label: 'iPad 12.9"', icon: 'tablet-portrait-outline' },
        { type: 'ipad_pro_3gen', label: 'iPad Pro', icon: 'tablet-portrait-outline' },
        { type: 'mac', label: 'Mac', icon: 'desktop-outline' }
      ],
      screenshotSizes: {
        iphone_65: {
          'Porträt': '1284 x 2778 px',
          'Landschaft': '2778 x 1284 px'
        },
        iphone_55: {
          'Porträt': '1242 x 2208 px',
          'Landschaft': '2208 x 1242 px'
        },
        ipad_129: {
          'Porträt': '2048 x 2732 px',
          'Landschaft': '2732 x 2048 px'
        },
        ipad_pro_3gen: {
          'Porträt': '2048 x 2732 px',
          'Landschaft': '2732 x 2048 px'
        },
        mac: {
          'Standard': '1280 x 800 px',
          'Retina': '2560 x 1600 px'
        }
      },
      draggedIndex: null
    };
  },

  computed: {
    projectId() {
      return this.$route.params.project;
    },
    currentScreenshots() {
      return this.screenshots.filter(
        s => (s.display_type === this.activeDevice || s.display_type === '') && s.locale === this.activeLocale
      ).sort((a, b) => (a.position || a.display_order || 0) - (b.position || b.display_order || 0));
    },
    screenshotUrl() {
      /*return (screenshot) => {
        if (!screenshot.file_path) return '';
        // Remove leading ../ and prepend API base URL
        const path = screenshot.file_path.replace(/^\.\.\//, '');
        return `${this.$axios.defaults.baseURL || '/backend/'}${path}`;
      };*/
      return (screenshot) => {
        console.log('screenshotUrl called', screenshot.file_path);
        return screenshot.file_path;
      };
    },
    availableLocales() {
      const usedLocales = [...new Set(this.screenshots.map(s => s.locale))];
      return this.allLocales.filter(l => !usedLocales.includes(l.code));
    }
  },

  mounted() {
    this.loadVersion();
    this.loadScreenshots();
    this.loadLocales();
    this.loadLocalizations();
  },

  methods: {
    async loadVersion() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=version&version_id=${this.versionId}&project=${this.projectId}`);
        if (res.data.success) {
          this.version = res.data.version;
        }
      } catch (e) {
        console.error('Error loading version:', e);
      }
    },

    async loadScreenshots() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=screenshots&version_id=${this.versionId}&project=${this.projectId}`);
        if (res.data.success) {
          this.screenshots = res.data.screenshots || [];
          // Set first locale as active if available
          if (this.screenshots.length > 0 && !this.localizations.find(l => l.locale === this.activeLocale)) {
            this.activeLocale = this.screenshots[0].locale;
          }
        }
      } catch (e) {
        console.error('Error loading screenshots:', e);
      }
    },

    async loadLocalizations() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=version_localizations&version_id=${this.versionId}&project=${this.projectId}`);
        if (res.data.success) {
          this.localizations = res.data.localizations || [];
          if (this.localizations.length > 0) {
            this.activeLocale = this.localizations[0].locale;
          }
        }
      } catch (e) {
        console.error('Error loading localizations:', e);
      }
    },

    async loadLocales() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=locales&project=${this.projectId}`);
        if (res.data.success) {
          this.allLocales = res.data.locales || [];
        }
      } catch (e) {
        console.error('Error loading locales:', e);
      }
    },

    triggerFileInput() {
      this.$refs.fileInput.click();
    },

    handleFileSelect(event) {
      const files = Array.from(event.target.files);
      this.uploadFiles(files);
    },

    handleDrop(event) {
      this.isDragging = false;
      const files = Array.from(event.dataTransfer.files).filter(
        f => f.type === 'image/png' || f.type === 'image/jpeg'
      );
      if (files.length > 0) {
        this.uploadFiles(files);
      }
    },

    async uploadFiles(files) {
      const maxScreenshots = 10 - this.currentScreenshots.length;
      const toUpload = files.slice(0, maxScreenshots);

      if (toUpload.length === 0) {
        this.$toast?.warning('Maximale Anzahl an Screenshots erreicht');
        return;
      }

      this.uploading = true;
      this.uploadProgress = 0;

      for (let i = 0; i < toUpload.length; i++) {
        const file = toUpload[i];
        const formData = new FormData();
        formData.append('file', file);
        //formData.append('version_id', this.versionId);
        formData.append('locale', this.activeLocale);
        formData.append('display_type', this.activeDevice);
        formData.append('position', this.currentScreenshots.length + i + 1);

        try {
          await this.$axios.post(`appstore_metadata.php?action=screenshots&project=${this.projectId}&version_id=${this.versionId}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          this.uploadProgress = Math.round(((i + 1) / toUpload.length) * 100);
        } catch (e) {
          console.error('Error uploading screenshot:', e);
          this.$toast?.error(`Fehler beim Hochladen von ${file.name}`);
        }
      }

      this.uploading = false;
      this.uploadProgress = 0;
      this.loadScreenshots();
      this.$toast?.success(`${toUpload.length} Screenshot(s) hochgeladen`);
    },

    dragStart(index) {
      this.draggedIndex = index;
    },

    dragOver(index) {
      if (this.draggedIndex === null || this.draggedIndex === index) return;

      const items = [...this.screenshots];
      const draggedItem = items.find((s, i) =>
        (s.display_type === this.activeDevice || s.display_type === '') &&
        s.locale === this.activeLocale &&
        this.currentScreenshots[this.draggedIndex]?.id === s.id
      );
      const targetItem = items.find((s, i) =>
        (s.display_type === this.activeDevice || s.display_type === '') &&
        s.locale === this.activeLocale &&
        this.currentScreenshots[index]?.id === s.id
      );

      if (!draggedItem || !targetItem) return;

      const draggedItemIndex = items.indexOf(draggedItem);
      const targetItemIndex = items.indexOf(targetItem);

      items.splice(draggedItemIndex, 1);
      items.splice(targetItemIndex, 0, draggedItem);

      this.screenshots = items;
      this.draggedIndex = index;
    },

    drop(index) {
      this.draggedIndex = null;
      this.updateOrder();
    },

    dragEnd() {
      this.draggedIndex = null;
    },

    async updateOrder() {
      const updates = this.currentScreenshots.map((s, index) => ({
        id: s.id,
        position: index + 1
      }));

      try {
        await this.$axios.put(`appstore_metadata.php?action=screenshots&project=${this.projectId}`, {
          order_updates: updates
        });
      } catch (e) {
        console.error('Error updating order:', e);
        this.$toast?.error('Fehler beim Aktualisieren der Reihenfolge');
      }
    },

    viewScreenshot(screenshot) {
      this.previewScreenshot = screenshot;
    },

    confirmDeleteScreenshot(screenshot) {
      this.screenshotToDelete = screenshot;
      this.showDeleteModal = true;
    },

    async deleteScreenshot() {
      if (!this.screenshotToDelete) return;

      try {
        await this.$axios.delete(`appstore_metadata.php?action=screenshots&id=${this.screenshotToDelete.id}&project=${this.projectId}`);
        this.$toast?.success('Screenshot gelöscht');
        this.showDeleteModal = false;
        this.screenshotToDelete = null;
        this.loadScreenshots();
      } catch (e) {
        console.error('Error deleting screenshot:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },

    async addLocale() {
      if (!this.newLocale) return;

      // Add locale to localizations list
      this.localizations.push({ locale: this.newLocale });
      this.activeLocale = this.newLocale;
      this.showAddLocaleModal = false;
      this.newLocale = '';
    },

    goBack() {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${this.appId}`);
    },

    getLocaleFlag(locale) {
      const countryCode = locale.split('-')[1] || locale.toUpperCase();
      const codePoints = countryCode.split('').map(char => 127397 + char.charCodeAt(0));
      try {
        return String.fromCodePoint(...codePoints);
      } catch {
        return '🌍';
      }
    }
  }
};
</script>

<style scoped>
/* Modern Design System - ManageUsers Pattern */
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
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}

ion-content.modern-content {
  --background: var(--background);
}

.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.back-button {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.back-button:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.back-button ion-icon {
  font-size: 20px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
}

.header-content p {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.status-badge.draft {
  background: rgba(100, 116, 139, 0.1);
}

.status-badge.in_review {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.status-badge.approved {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-badge.released {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.tab-navigation.locale-tabs {
  margin-bottom: 24px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.tab-btn.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.tab-btn.add-tab {
  border-style: dashed;
  color: var(--text-muted);
}

.tab-btn .flag {
  font-size: 16px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  background: rgba(37, 99, 235, 0.1);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  color: var(--primary-color);
}

/* Drop Zone */
.screenshots-drop-zone {
  padding: 24px;
  min-height: 300px;
  transition: all 0.2s ease;
}

.screenshots-drop-zone.dragging {
  background: rgba(37, 99, 235, 0.05);
  border: 2px dashed var(--primary-color);
  border-radius: 0;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  cursor: pointer;
}

.empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.empty-icon ion-icon {
  font-size: 40px;
  color: white;
}

.empty-state h4 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state p {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0 0 8px 0;
}

.empty-state .hint {
  font-size: 13px;
  color: var(--text-muted);
}

/* Screenshots Grid */
.screenshots-grid,
.screenshots-grid-inner {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.screenshot-item {
  background: var(--background);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: grab;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid var(--border);
}

.screenshot-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.screenshot-item:active {
  cursor: grabbing;
}

.screenshot-preview {
  position: relative;
  aspect-ratio: 9/19.5;
  background: var(--border);
}

.screenshot-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.screenshot-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.screenshot-item:hover .screenshot-overlay {
  opacity: 1;
}

.overlay-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: white;
  border: none;
  border-radius: 50%;
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.overlay-btn:hover {
  transform: scale(1.1);
}

.overlay-btn.danger {
  color: var(--danger-color);
}

.screenshot-info {
  padding: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
}

.display-type {
  font-size: 12px;
  color: var(--text-muted);
}

/* Guidelines */
.guidelines {
  padding: 20px 24px;
  background: var(--background);
  border-top: 1px solid var(--border);
}

.guidelines h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-secondary);
}

.guidelines-grid {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}

.guideline-item {
  display: flex;
  flex-direction: column;
}

.size-label {
  font-size: 12px;
  color: var(--text-muted);
}

.size-value {
  font-size: 14px;
  color: var(--text-primary);
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Monaco, Consolas, monospace;
}

/* Upload Progress */
.upload-progress {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--surface);
  padding: 16px 24px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  display: flex;
  align-items: center;
  gap: 16px;
  z-index: 100;
  border: 1px solid var(--border);
}

.progress-bar {
  width: 200px;
  height: 6px;
  background: var(--border);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.2s ease;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn:hover:not(:disabled) {
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Custom Modal */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  backdrop-filter: blur(4px);
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 400px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.custom-modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.custom-modal-header.danger {
  background: rgba(220, 38, 38, 0.1);
}

.custom-modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.modal-close-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--radius);
  border: none;
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.modal-close-btn ion-icon {
  font-size: 20px;
}

.custom-modal-body {
  padding: 24px;
}

.custom-modal-body p {
  margin: 0 0 16px 0;
  color: var(--text-primary);
}

/* Preview Modal */
.preview-modal {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
}

.preview-modal img {
  max-width: 100%;
  max-height: 90vh;
  border-radius: var(--radius-lg);
}

.close-preview {
  position: absolute;
  top: -12px;
  right: -12px;
  width: 36px;
  height: 36px;
  background: white;
  border: none;
  border-radius: 50%;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: var(--shadow-md);
}

/* Form Elements */
.form-group {
  margin-bottom: 16px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 14px;
  color: var(--text-primary);
}

.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .screenshots-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
  }

  .header-content h1 {
    font-size: 20px;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions .action-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
