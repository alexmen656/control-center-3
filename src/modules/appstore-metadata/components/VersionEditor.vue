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
              <h1 v-if="version">Version {{ version.version_string }}</h1>
              <h1 v-else>Version Metadaten</h1>
              <p v-if="version">
                <span class="status-badge">{{ version.platform }}</span>
                <span class="status-badge" :class="version.status">{{ getStatusLabel(version.status) }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Lade Version...</p>
        </div>

        <!-- Locale Tabs -->
        <div class="tab-navigation" v-if="version && !loading">
          <button 
            v-for="loc in localizations" 
            :key="loc.locale"
            class="tab-btn"
            :class="{ active: activeLocale === loc.locale }"
            @click="activeLocale = loc.locale"
          >
            <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
            <span>{{ loc.locale_name || loc.locale }}</span>
          </button>
          
          <button class="tab-btn add-tab" @click="showAddLocaleModal = true">
            <ion-icon name="add-outline"></ion-icon>
            <span>Hinzufügen</span>
          </button>
        </div>

        <!-- Localization Form -->
        <div v-if="activeLocalization" class="data-card">
          <div class="card-header">
            <h3>
              <span class="flag-lg">{{ getLocaleFlag(activeLocalization.locale) }}</span>
              {{ activeLocalization.locale_name || activeLocalization.locale }}
            </h3>
            <button class="icon-btn danger" @click="deleteLocalization" v-if="localizations.length > 1" title="Entfernen">
              <ion-icon name="trash-outline"></ion-icon>
            </button>
          </div>
          
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Beschreibung <span class="required">*</span></label>
              <textarea 
                v-model="activeLocalization.description" 
                class="modern-input"
                rows="8"
                placeholder="App Beschreibung (max. 4000 Zeichen)"
                maxlength="4000"
              ></textarea>
              <div class="char-count" :class="{ warning: (activeLocalization.description || '').length > 3800 }">
                {{ (activeLocalization.description || '').length }}/4000
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Keywords</label>
              <input 
                v-model="activeLocalization.keywords" 
                type="text" 
                class="modern-input"
                placeholder="keyword1, keyword2, keyword3 (max. 100 Zeichen)"
                maxlength="100"
              />
              <div class="char-count">{{ (activeLocalization.keywords || '').length }}/100</div>
              <span class="form-hint">Trenne Keywords mit Kommas</span>
            </div>

            <div class="form-group">
              <label class="form-label">Was ist neu (Release Notes)</label>
              <textarea 
                v-model="activeLocalization.whats_new" 
                class="modern-input"
                rows="5"
                placeholder="Neue Funktionen und Verbesserungen..."
                maxlength="4000"
              ></textarea>
              <div class="char-count">{{ (activeLocalization.whats_new || '').length }}/4000</div>
            </div>

            <div class="form-group">
              <label class="form-label">Promotionstext</label>
              <input 
                v-model="activeLocalization.promotional_text" 
                type="text" 
                class="modern-input"
                placeholder="Kurzer Promotionstext (max. 170 Zeichen)"
                maxlength="170"
              />
              <div class="char-count">{{ (activeLocalization.promotional_text || '').length }}/170</div>
              <span class="form-hint">Wird über der Beschreibung angezeigt, kann jederzeit aktualisiert werden</span>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Marketing URL</label>
                <input 
                  v-model="activeLocalization.marketing_url" 
                  type="url" 
                  class="modern-input"
                  placeholder="https://..."
                />
              </div>
              
              <div class="form-group">
                <label class="form-label">Support URL</label>
                <input 
                  v-model="activeLocalization.support_url" 
                  type="url" 
                  class="modern-input"
                  placeholder="https://..."
                />
              </div>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="copyFromLocale">
                <ion-icon name="copy-outline"></ion-icon>
                Von anderer Sprache kopieren
              </button>
              <button class="action-btn primary" @click="saveLocalization">
                <ion-icon name="save-outline"></ion-icon>
                Speichern
              </button>
            </div>
          </div>
        </div>

        <!-- No Localizations -->
        <div v-else-if="!loading && localizations.length === 0" class="empty-state">
          <div class="empty-icon">
            <ion-icon name="language-outline"></ion-icon>
          </div>
          <h3>Keine Lokalisierungen</h3>
          <p>Füge eine Sprache hinzu, um die Metadaten zu bearbeiten.</p>
          <button class="action-btn primary" @click="showAddLocaleModal = true">
            <ion-icon name="add-outline"></ion-icon>
            Sprache hinzufügen
          </button>
        </div>

        <!-- Version Settings -->
        <div v-if="version && !loading" class="data-card">
          <div class="card-header">
            <h3>Versions-Einstellungen</h3>
          </div>
          
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Versionsnummer</label>
                <input v-model="versionForm.version_string" type="text" class="modern-input" />
              </div>
              
              <div class="form-group">
                <label class="form-label">Build-Nummer</label>
                <input v-model="versionForm.build_number" type="text" class="modern-input" />
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Veröffentlichungsart</label>
                <select v-model="versionForm.release_type" class="modern-select">
                  <option value="manual">Manuell freigeben</option>
                  <option value="afterApproval">Automatisch nach Genehmigung</option>
                  <option value="scheduled">Geplante Veröffentlichung</option>
                </select>
              </div>
              
              <div class="form-group" v-if="versionForm.release_type === 'scheduled'">
                <label class="form-label">Veröffentlichungsdatum</label>
                <input v-model="versionForm.earliest_release_date" type="datetime-local" class="modern-input" />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Copyright</label>
              <input v-model="versionForm.copyright" type="text" class="modern-input" placeholder="© 2024 Dein Unternehmen" />
            </div>

            <div class="form-group">
              <label class="form-label">Hinweise für App-Reviewer</label>
              <textarea 
                v-model="versionForm.review_notes" 
                class="modern-input"
                rows="4"
                placeholder="Anmeldedaten, Test-Accounts, spezielle Anweisungen..."
              ></textarea>
              <span class="form-hint">Diese Informationen sind nur für das Review-Team sichtbar</span>
            </div>

            <div class="form-actions">
              <button class="action-btn primary" @click="saveVersionSettings">
                <ion-icon name="save-outline"></ion-icon>
                Einstellungen speichern
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Locale Modal -->
      <div v-if="showAddLocaleModal" class="custom-modal-overlay" @click.self="showAddLocaleModal = false">
        <div class="custom-modal-content">
          <div class="custom-modal-header">
            <h3>Sprache hinzufügen</h3>
            <button class="modal-close-btn" @click="showAddLocaleModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Sprache <span class="required">*</span></label>
              <select v-model="newLocale" class="modern-select">
                <option value="">Sprache wählen</option>
                <option 
                  v-for="locale in availableLocales" 
                  :key="locale.code" 
                  :value="locale.code"
                >
                  {{ locale.name }} ({{ locale.code }})
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="showAddLocaleModal = false">Abbrechen</button>
              <button class="action-btn primary" @click="addLocalization" :disabled="!newLocale">
                <ion-icon name="add-outline"></ion-icon>
                Hinzufügen
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Copy From Locale Modal -->
      <div v-if="showCopyModal" class="custom-modal-overlay" @click.self="showCopyModal = false">
        <div class="custom-modal-content">
          <div class="custom-modal-header">
            <h3>Von Sprache kopieren</h3>
            <button class="modal-close-btn" @click="showCopyModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <p class="modal-description">Wähle die Sprache, von der du die Texte kopieren möchtest:</p>
            <div class="locale-list">
              <button 
                v-for="loc in otherLocalizations" 
                :key="loc.locale"
                class="locale-option"
                @click="copyFromSelectedLocale(loc)"
              >
                <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
                <span class="name">{{ loc.locale_name || loc.locale }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { APP_STATUSES } from '../config';

export default {
  name: 'VersionEditor',
  components: {},
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
      loading: true,
      version: null,
      localizations: [],
      allLocales: [],
      activeLocale: null,
      versionForm: {},
      showAddLocaleModal: false,
      showCopyModal: false,
      newLocale: ''
    };
  },
  
  computed: {
    projectId() {
      return this.$route.params.project;
    },
    activeLocalization() {
      return this.localizations.find(l => l.locale === this.activeLocale) || null;
    },
    availableLocales() {
      const usedLocales = this.localizations.map(l => l.locale);
      return this.allLocales.filter(l => !usedLocales.includes(l.code));
    },
    otherLocalizations() {
      return this.localizations.filter(l => l.locale !== this.activeLocale);
    }
  },
  
  mounted() {
    this.loadVersion();
    this.loadLocales();
  },
  
  methods: {
    async loadVersion() {
      this.loading = true;
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=version&version_id=${this.versionId}&project=${this.projectId}`);
        if (res.data.success) {
          this.version = res.data.version;
          this.localizations = res.data.localizations || [];
          this.versionForm = { ...this.version };
          
          if (this.localizations.length > 0) {
            this.activeLocale = this.localizations[0].locale;
          }
        }
      } catch (e) {
        console.error('Error loading version:', e);
        this.$toast?.error('Fehler beim Laden');
      } finally {
        this.loading = false;
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
    
    async saveLocalization() {
      if (!this.activeLocalization) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=version_localizations&version_id=${this.versionId}&project=${this.projectId}`, this.activeLocalization);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gespeichert');
        }
      } catch (e) {
        console.error('Error saving localization:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    async addLocalization() {
      if (!this.newLocale) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=version_localizations&version_id=${this.versionId}&project=${this.projectId}`, {
          locale: this.newLocale
        });
        if (res.data.success) {
          this.$toast?.success('Sprache hinzugefügt');
          this.showAddLocaleModal = false;
          this.newLocale = '';
          this.loadVersion();
        }
      } catch (e) {
        console.error('Error adding localization:', e);
        this.$toast?.error('Fehler beim Hinzufügen');
      }
    },
    
    async deleteLocalization() {
      if (!this.activeLocalization) return;
      if (!confirm(`Lokalisierung "${this.activeLocale}" wirklich löschen?`)) return;
      
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=version_localizations&version_id=${this.versionId}&locale=${this.activeLocale}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gelöscht');
          this.loadVersion();
        }
      } catch (e) {
        console.error('Error deleting localization:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },
    
    async saveVersionSettings() {
      try {
        const res = await this.$axios.put(`appstore_metadata.php?action=version&version_id=${this.versionId}&project=${this.projectId}`, this.versionForm);
        if (res.data.success) {
          this.$toast?.success('Einstellungen gespeichert');
        }
      } catch (e) {
        console.error('Error saving version:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    copyFromLocale() {
      if (this.otherLocalizations.length === 0) {
        this.$toast?.warning('Keine anderen Sprachen verfügbar');
        return;
      }
      this.showCopyModal = true;
    },
    
    copyFromSelectedLocale(loc) {
      if (this.activeLocalization) {
        this.activeLocalization.description = loc.description;
        this.activeLocalization.keywords = loc.keywords;
        this.activeLocalization.whats_new = loc.whats_new;
        this.activeLocalization.promotional_text = loc.promotional_text;
        this.activeLocalization.marketing_url = loc.marketing_url;
        this.activeLocalization.support_url = loc.support_url;
      }
      this.showCopyModal = false;
      this.$toast?.success(`Texte von ${loc.locale} kopiert`);
    },
    
    goBack() {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${this.appId}`);
    },
    
    getStatusLabel(status) {
      const found = APP_STATUSES.find(s => s.value === status);
      return found?.label || status;
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
  --info-color: #0891b2;
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
  max-width: 900px;
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
  display: flex;
  gap: 8px;
  align-items: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
}

.status-badge.draft { background: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }
.status-badge.ready_for_submission { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
.status-badge.in_review { background: rgba(8, 145, 178, 0.1); color: var(--info-color); }
.status-badge.approved { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }

/* Loading State */
.loading-state {
  text-align: center;
  padding: 80px 20px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: var(--text-secondary);
  margin: 0;
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  overflow-x: auto;
  padding-bottom: 8px;
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
  white-space: nowrap;
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

.tab-btn.add-tab:hover {
  color: var(--primary-color);
}

.tab-btn .flag {
  font-size: 18px;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  overflow: hidden;
  margin-bottom: 24px;
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
  display: flex;
  align-items: center;
  gap: 12px;
}

.flag-lg {
  font-size: 28px;
}

.card-body {
  padding: 24px;
}

/* Form Elements */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.form-group {
  margin-bottom: 20px;
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

.required {
  color: var(--danger-color);
}

.modern-input,
.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  font-family: inherit;
}

textarea.modern-input {
  resize: vertical;
  min-height: 100px;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.char-count {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
  text-align: right;
}

.char-count.warning {
  color: var(--warning-color);
}

.form-hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
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

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.empty-icon ion-icon {
  font-size: 40px;
  color: white;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state p {
  color: var(--text-secondary);
  margin: 0 0 24px 0;
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
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Icon Button */
.icon-btn {
  width: 36px;
  height: 36px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  box-shadow: var(--shadow);
}

.icon-btn.danger {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.icon-btn.danger:hover {
  background: var(--danger-color);
  color: white;
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
  max-width: 480px;
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

.modal-description {
  margin: 0 0 16px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Locale List for Copy Modal */
.locale-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.locale-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.locale-option:hover {
  border-color: var(--primary-color);
  background: var(--surface);
}

.locale-option .flag {
  font-size: 24px;
}

.locale-option .name {
  font-weight: 500;
  color: var(--text-primary);
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .action-btn {
    width: 100%;
    justify-content: center;
  }

  .header-content h1 {
    font-size: 20px;
  }
}
</style>
