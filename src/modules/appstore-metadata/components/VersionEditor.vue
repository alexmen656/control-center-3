<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="create-outline" title="Version Metadaten" bg="transparent"/>
      
      <div class="page-container">
        <!-- Back Button -->
        <button class="back-btn" @click="goBack">
          <ion-icon name="arrow-back-outline"></ion-icon>
          Zurück zur App
        </button>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="loading-icon">
            <ion-icon name="hourglass-outline"></ion-icon>
          </div>
          <p>Lade Version...</p>
        </div>

        <!-- Version Header -->
        <div v-else-if="version" class="version-header-card">
          <div class="version-info">
            <h1>Version {{ version.version_string }}</h1>
            <div class="version-meta">
              <span class="badge">{{ version.platform }}</span>
              <span class="badge" :class="version.status">{{ getStatusLabel(version.status) }}</span>
            </div>
          </div>
        </div>

        <!-- Locale Tabs -->
        <div class="locale-tabs" v-if="version">
          <button 
            v-for="loc in localizations" 
            :key="loc.locale"
            class="locale-tab"
            :class="{ active: activeLocale === loc.locale }"
            @click="activeLocale = loc.locale"
          >
            <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
            <span class="name">{{ loc.locale_name || loc.locale }}</span>
          </button>
          
          <button class="locale-tab add" @click="showAddLocaleModal = true">
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
            <button class="action-btn danger" @click="deleteLocalization" v-if="localizations.length > 1">
              <ion-icon name="trash-outline"></ion-icon>
              Entfernen
            </button>
          </div>
          
          <div class="card-body">
            <div class="form-group">
              <label>Beschreibung <span class="required">*</span></label>
              <textarea 
                v-model="activeLocalization.description" 
                class="form-textarea"
                rows="8"
                placeholder="App Beschreibung (max. 4000 Zeichen)"
                maxlength="4000"
              ></textarea>
              <div class="char-count" :class="{ warning: (activeLocalization.description || '').length > 3800 }">
                {{ (activeLocalization.description || '').length }}/4000
              </div>
            </div>

            <div class="form-group">
              <label>Keywords</label>
              <input 
                v-model="activeLocalization.keywords" 
                type="text" 
                class="form-input"
                placeholder="keyword1, keyword2, keyword3 (max. 100 Zeichen)"
                maxlength="100"
              />
              <div class="char-count">{{ (activeLocalization.keywords || '').length }}/100</div>
              <span class="form-hint">Trenne Keywords mit Kommas</span>
            </div>

            <div class="form-group">
              <label>Was ist neu (Release Notes)</label>
              <textarea 
                v-model="activeLocalization.whats_new" 
                class="form-textarea"
                rows="5"
                placeholder="Neue Funktionen und Verbesserungen..."
                maxlength="4000"
              ></textarea>
              <div class="char-count">{{ (activeLocalization.whats_new || '').length }}/4000</div>
            </div>

            <div class="form-group">
              <label>Promotionstext</label>
              <input 
                v-model="activeLocalization.promotional_text" 
                type="text" 
                class="form-input"
                placeholder="Kurzer Promotionstext (max. 170 Zeichen)"
                maxlength="170"
              />
              <div class="char-count">{{ (activeLocalization.promotional_text || '').length }}/170</div>
              <span class="form-hint">Wird über der Beschreibung angezeigt, kann jederzeit aktualisiert werden</span>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Marketing URL</label>
                <input 
                  v-model="activeLocalization.marketing_url" 
                  type="url" 
                  class="form-input"
                  placeholder="https://..."
                />
              </div>
              
              <div class="form-group">
                <label>Support URL</label>
                <input 
                  v-model="activeLocalization.support_url" 
                  type="url" 
                  class="form-input"
                  placeholder="https://..."
                />
              </div>
            </div>
          </div>
          
          <div class="card-footer">
            <button class="action-btn" @click="copyFromLocale">
              <ion-icon name="copy-outline"></ion-icon>
              Von anderer Sprache kopieren
            </button>
            <button class="action-btn primary" @click="saveLocalization">
              <ion-icon name="save-outline"></ion-icon>
              Speichern
            </button>
          </div>
        </div>

        <!-- No Localizations -->
        <div v-else-if="!loading && localizations.length === 0" class="empty-state">
          <ion-icon name="language-outline"></ion-icon>
          <h3>Keine Lokalisierungen</h3>
          <p>Füge eine Sprache hinzu, um die Metadaten zu bearbeiten.</p>
          <button class="action-btn primary" @click="showAddLocaleModal = true">
            <ion-icon name="add-outline"></ion-icon>
            Sprache hinzufügen
          </button>
        </div>

        <!-- Version Settings -->
        <div v-if="version" class="data-card">
          <div class="card-header">
            <h3>Versions-Einstellungen</h3>
          </div>
          
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label>Versionsnummer</label>
                <input v-model="versionForm.version_string" type="text" class="form-input" />
              </div>
              
              <div class="form-group">
                <label>Build-Nummer</label>
                <input v-model="versionForm.build_number" type="text" class="form-input" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Veröffentlichungsart</label>
                <select v-model="versionForm.release_type" class="form-select">
                  <option value="manual">Manuell freigeben</option>
                  <option value="afterApproval">Automatisch nach Genehmigung</option>
                  <option value="scheduled">Geplante Veröffentlichung</option>
                </select>
              </div>
              
              <div class="form-group" v-if="versionForm.release_type === 'scheduled'">
                <label>Veröffentlichungsdatum</label>
                <input v-model="versionForm.earliest_release_date" type="datetime-local" class="form-input" />
              </div>
            </div>

            <div class="form-group">
              <label>Copyright</label>
              <input v-model="versionForm.copyright" type="text" class="form-input" placeholder="© 2024 Dein Unternehmen" />
            </div>

            <div class="form-group">
              <label>Hinweise für App-Reviewer</label>
              <textarea 
                v-model="versionForm.review_notes" 
                class="form-textarea"
                rows="4"
                placeholder="Anmeldedaten, Test-Accounts, spezielle Anweisungen..."
              ></textarea>
              <span class="form-hint">Diese Informationen sind nur für das Review-Team sichtbar</span>
            </div>
          </div>
          
          <div class="card-footer">
            <button class="action-btn primary" @click="saveVersionSettings">
              <ion-icon name="save-outline"></ion-icon>
              Einstellungen speichern
            </button>
          </div>
        </div>
      </div>

      <!-- Add Locale Modal -->
      <div v-if="showAddLocaleModal" class="modal-overlay" @click.self="showAddLocaleModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Sprache hinzufügen</h3>
            <button class="close-btn" @click="showAddLocaleModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="form-group">
              <label>Sprache <span class="required">*</span></label>
              <select v-model="newLocale" class="form-select">
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
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showAddLocaleModal = false">Abbrechen</button>
            <button class="action-btn primary" @click="addLocalization" :disabled="!newLocale">
              <ion-icon name="add-outline"></ion-icon>
              Hinzufügen
            </button>
          </div>
        </div>
      </div>

      <!-- Copy From Locale Modal -->
      <div v-if="showCopyModal" class="modal-overlay" @click.self="showCopyModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Von Sprache kopieren</h3>
            <button class="close-btn" @click="showCopyModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Wähle die Sprache, von der du die Texte kopieren möchtest:</p>
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
import SiteTitle from "@/components/SiteTitle.vue";
import { APP_STATUSES } from '../config';

export default {
  name: 'VersionEditor',
  components: {
    SiteTitle
  },
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
      this.$router.push(`appstore-metadata/app/${this.appId}`);
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
/* Modern Design System */
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
  --radius: 8px;
  --radius-lg: 12px;
}

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
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

/* Back Button */
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-size: 14px;
  cursor: pointer;
  margin-bottom: 24px;
}

.back-btn:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

/* Version Header */
.version-header-card {
  background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 24px;
  color: white;
}

.version-info h1 {
  margin: 0 0 12px 0;
  font-size: 24px;
}

.version-meta {
  display: flex;
  gap: 8px;
}

.badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.2);
}

.badge.draft { background: var(--secondary-color); }
.badge.ready_for_submission { background: var(--warning-color); }

/* Locale Tabs */
.locale-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.locale-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.locale-tab:hover {
  border-color: var(--primary-color);
}

.locale-tab.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.locale-tab.add {
  border-style: dashed;
  color: var(--text-muted);
}

.locale-tab.add:hover {
  color: var(--primary-color);
}

.locale-tab .flag {
  font-size: 20px;
}

.locale-tab .name {
  font-size: 14px;
  font-weight: 500;
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

.card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

/* Form Elements */
.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
}

.required {
  color: var(--danger-color);
}

.form-input,
.form-select,
.form-textarea {
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

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
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

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.empty-state ion-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.loading-icon ion-icon {
  font-size: 48px;
  color: var(--primary-color);
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Action Buttons */
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
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.action-btn:hover:not(:disabled) {
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.danger {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
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
}

.modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 450px;
  overflow: hidden;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: var(--text-primary);
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: var(--text-secondary);
  cursor: pointer;
}

.modal-body {
  padding: 24px;
}

.modal-body p {
  margin: 0 0 16px 0;
  color: var(--text-secondary);
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
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
  .card-footer {
    flex-direction: column;
  }
  
  .card-footer .action-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
