<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="apps-outline" :title="app?.name || 'App Details'" bg="transparent"/>
      
      <div class="page-container">
        <!-- Back Button -->
        <button class="back-btn" @click="$router.push('appstore-metadata')">
          <ion-icon name="arrow-back-outline"></ion-icon>
          Zurück zum Dashboard
        </button>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="loading-icon">
            <ion-icon name="hourglass-outline"></ion-icon>
          </div>
          <p>Lade App Details...</p>
        </div>

        <!-- App Header -->
        <div v-else-if="app" class="app-header-card">
          <div class="app-header-content">
            <div class="app-icon-wrapper">
              <ion-icon name="logo-apple-appstore"></ion-icon>
            </div>
            <div class="app-header-info">
              <h1>{{ app.name }}</h1>
              <p class="bundle-id">{{ app.bundle_id }}</p>
              <div class="app-badges">
                <span class="badge" :class="app.status">{{ getStatusLabel(app.status) }}</span>
                <span class="badge secondary">{{ app.primary_locale }}</span>
              </div>
            </div>
            <div class="app-header-actions">
              <button class="action-btn" @click="syncFromAppStore">
                <ion-icon name="cloud-download-outline"></ion-icon>
                Von App Store laden
              </button>
              <button class="action-btn primary" @click="pushToAppStore">
                <ion-icon name="cloud-upload-outline"></ion-icon>
                Zu App Store pushen
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container" v-if="app">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            class="tab-btn"
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            <ion-icon :name="tab.icon"></ion-icon>
            {{ tab.label }}
          </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" v-if="app">
          <!-- General Info Tab -->
          <div v-if="activeTab === 'general'" class="content-section">
            <div class="data-card">
              <div class="card-header">
                <h3>Allgemeine Informationen</h3>
              </div>
              <div class="card-body">
                <div class="form-row">
                  <div class="form-group">
                    <label>App ID</label>
                    <input v-model="app.app_id" type="text" class="form-input" readonly />
                  </div>
                  <div class="form-group">
                    <label>Bundle ID</label>
                    <input v-model="app.bundle_id" type="text" class="form-input" readonly />
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>App Name</label>
                    <input v-model="editForm.name" type="text" class="form-input" />
                  </div>
                  <div class="form-group">
                    <label>SKU</label>
                    <input v-model="editForm.sku" type="text" class="form-input" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Primäre Sprache</label>
                    <select v-model="editForm.primary_locale" class="form-select">
                      <option v-for="locale in locales" :key="locale.code" :value="locale.code">
                        {{ locale.name }}
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select v-model="editForm.status" class="form-select">
                      <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label>Inhaltsrechte</label>
                  <select v-model="editForm.content_rights_declaration" class="form-select">
                    <option value="">Nicht angegeben</option>
                    <option value="doesNotUseThirdPartyContent">Verwendet keine Drittanbieter-Inhalte</option>
                    <option value="usesThirdPartyContent">Verwendet Drittanbieter-Inhalte</option>
                  </select>
                </div>
              </div>
              <div class="card-footer">
                <button class="action-btn primary" @click="saveAppDetails">
                  <ion-icon name="save-outline"></ion-icon>
                  Änderungen speichern
                </button>
              </div>
            </div>
          </div>

          <!-- Localizations Tab -->
          <div v-if="activeTab === 'localizations'" class="content-section">
            <div class="section-header">
              <h3>App Lokalisierungen</h3>
              <button class="action-btn primary" @click="showAddLocaleModal = true">
                <ion-icon name="add-outline"></ion-icon>
                Sprache hinzufügen
              </button>
            </div>

            <div class="localizations-grid">
              <div 
                v-for="loc in localizations" 
                :key="loc.id" 
                class="locale-card"
                @click="editLocalization(loc)"
              >
                <div class="locale-header">
                  <span class="locale-flag">{{ getLocaleFlag(loc.locale) }}</span>
                  <span class="locale-name">{{ loc.locale_name || loc.locale }}</span>
                  <span class="locale-code">{{ loc.locale }}</span>
                </div>
                <div class="locale-body">
                  <div class="locale-field">
                    <label>Name</label>
                    <span>{{ loc.name || '-' }}</span>
                  </div>
                  <div class="locale-field">
                    <label>Untertitel</label>
                    <span>{{ loc.subtitle || '-' }}</span>
                  </div>
                </div>
                <div class="locale-footer">
                  <button class="card-action" @click.stop="editLocalization(loc)">
                    <ion-icon name="create-outline"></ion-icon>
                    Bearbeiten
                  </button>
                  <button class="card-action danger" @click.stop="deleteLocalization(loc)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>

              <div class="locale-card add-card" @click="showAddLocaleModal = true">
                <ion-icon name="add-circle-outline"></ion-icon>
                <span>Neue Sprache</span>
              </div>
            </div>
          </div>

          <!-- Versions Tab -->
          <div v-if="activeTab === 'versions'" class="content-section">
            <div class="section-header">
              <h3>App Versionen</h3>
              <button class="action-btn primary" @click="showAddVersionModal = true">
                <ion-icon name="add-outline"></ion-icon>
                Version erstellen
              </button>
            </div>

            <div class="versions-list">
              <div 
                v-for="version in versions" 
                :key="version.id" 
                class="version-card"
              >
                <div class="version-header">
                  <div class="version-number">
                    <h4>v{{ version.version_string }}</h4>
                    <span class="build-badge">Build {{ version.build_number || 'N/A' }}</span>
                  </div>
                  <span class="version-status" :class="version.status">
                    {{ getStatusLabel(version.status) }}
                  </span>
                </div>
                
                <div class="version-meta">
                  <div class="meta-item">
                    <ion-icon name="phone-portrait-outline"></ion-icon>
                    {{ version.platform }}
                  </div>
                  <div class="meta-item">
                    <ion-icon name="language-outline"></ion-icon>
                    {{ version.locale_count || 0 }} Sprachen
                  </div>
                  <div class="meta-item">
                    <ion-icon name="images-outline"></ion-icon>
                    {{ version.screenshot_count || 0 }} Screenshots
                  </div>
                </div>

                <div class="version-actions">
                  <button class="action-btn" @click="openVersionEditor(version.id)">
                    <ion-icon name="create-outline"></ion-icon>
                    Metadaten bearbeiten
                  </button>
                  <button class="action-btn" @click="openScreenshotManager(version.id)">
                    <ion-icon name="images-outline"></ion-icon>
                    Screenshots
                  </button>
                  <button class="action-btn danger" @click="deleteVersion(version)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>
              </div>

              <div v-if="versions.length === 0" class="empty-state-sm">
                <ion-icon name="git-branch-outline"></ion-icon>
                <p>Noch keine Versionen erstellt</p>
                <button class="action-btn primary" @click="showAddVersionModal = true">
                  <ion-icon name="add-outline"></ion-icon>
                  Erste Version erstellen
                </button>
              </div>
            </div>
          </div>

          <!-- Categories Tab -->
          <div v-if="activeTab === 'categories'" class="content-section">
            <div class="data-card">
              <div class="card-header">
                <h3>App Kategorien</h3>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label>Primäre Kategorie</label>
                  <select v-model="categoryForm.primary" class="form-select">
                    <option value="">Kategorie wählen</option>
                    <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Sekundäre Kategorie (optional)</label>
                  <select v-model="categoryForm.secondary" class="form-select">
                    <option value="">Keine</option>
                    <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="card-footer">
                <button class="action-btn primary" @click="saveCategories">
                  <ion-icon name="save-outline"></ion-icon>
                  Kategorien speichern
                </button>
              </div>
            </div>
          </div>

          <!-- Age Rating Tab -->
          <div v-if="activeTab === 'age_rating'" class="content-section">
            <div class="data-card">
              <div class="card-header">
                <h3>Altersfreigabe</h3>
              </div>
              <div class="card-body">
                <p class="info-text">Beantworte diese Fragen, um die Altersfreigabe für deine App zu bestimmen.</p>

                <div class="rating-questions">
                  <div class="rating-question" v-for="question in ageRatingQuestions" :key="question.key">
                    <label>{{ question.label }}</label>
                    <select v-model="ageRatingForm[question.key]" class="form-select">
                      <option v-for="opt in question.options" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <button class="action-btn primary" @click="saveAgeRating">
                  <ion-icon name="save-outline"></ion-icon>
                  Altersfreigabe speichern
                </button>
              </div>
            </div>
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
              <select v-model="newLocale.locale" class="form-select">
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

            <div class="form-group">
              <label>App Name</label>
              <input v-model="newLocale.name" type="text" class="form-input" placeholder="Lokalisierter App Name" />
            </div>

            <div class="form-group">
              <label>Untertitel</label>
              <input v-model="newLocale.subtitle" type="text" class="form-input" placeholder="Max. 30 Zeichen" maxlength="30" />
              <span class="char-count">{{ (newLocale.subtitle || '').length }}/30</span>
            </div>

            <div class="form-group">
              <label>Datenschutzrichtlinie URL</label>
              <input v-model="newLocale.privacy_policy_url" type="url" class="form-input" placeholder="https://..." />
            </div>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showAddLocaleModal = false">Abbrechen</button>
            <button class="action-btn primary" @click="saveLocalization" :disabled="!newLocale.locale">
              <ion-icon name="save-outline"></ion-icon>
              Speichern
            </button>
          </div>
        </div>
      </div>

      <!-- Add Version Modal -->
      <div v-if="showAddVersionModal" class="modal-overlay" @click.self="showAddVersionModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Neue Version erstellen</h3>
            <button class="close-btn" @click="showAddVersionModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="form-group">
              <label>Versionsnummer <span class="required">*</span></label>
              <input v-model="newVersion.version_string" type="text" class="form-input" placeholder="z.B. 1.0.0" />
            </div>

            <div class="form-group">
              <label>Build-Nummer</label>
              <input v-model="newVersion.build_number" type="text" class="form-input" placeholder="z.B. 1" />
            </div>

            <div class="form-group">
              <label>Plattform</label>
              <select v-model="newVersion.platform" class="form-select">
                <option value="iOS">iOS</option>
                <option value="macOS">macOS</option>
                <option value="tvOS">tvOS</option>
                <option value="watchOS">watchOS</option>
                <option value="visionOS">visionOS</option>
              </select>
            </div>

            <div class="form-group">
              <label>Veröffentlichungsart</label>
              <select v-model="newVersion.release_type" class="form-select">
                <option value="manual">Manuell freigeben</option>
                <option value="afterApproval">Automatisch nach Genehmigung</option>
                <option value="scheduled">Geplante Veröffentlichung</option>
              </select>
            </div>

            <div class="form-group">
              <label>Copyright</label>
              <input v-model="newVersion.copyright" type="text" class="form-input" placeholder="z.B. © 2024 Dein Unternehmen" />
            </div>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showAddVersionModal = false">Abbrechen</button>
            <button class="action-btn primary" @click="createVersion" :disabled="!newVersion.version_string">
              <ion-icon name="add-outline"></ion-icon>
              Version erstellen
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";
import { APP_STATUSES, AGE_RATING_OPTIONS } from '../config';

export default {
  name: 'AppDetailView',
  components: {
    SiteTitle
  },
  props: {
    appId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: true,
      app: null,
      localizations: [],
      versions: [],
      categories: [],
      ageRating: null,
      locales: [],
      availableCategories: [],
      activeTab: 'general',
      tabs: [
        { id: 'general', label: 'Allgemein', icon: 'information-circle-outline' },
        { id: 'localizations', label: 'Sprachen', icon: 'language-outline' },
        { id: 'versions', label: 'Versionen', icon: 'git-branch-outline' },
        { id: 'categories', label: 'Kategorien', icon: 'folder-outline' },
        { id: 'age_rating', label: 'Altersfreigabe', icon: 'shield-checkmark-outline' }
      ],
      statuses: APP_STATUSES,
      editForm: {},
      categoryForm: {
        primary: '',
        secondary: ''
      },
      ageRatingForm: {},
      showAddLocaleModal: false,
      showAddVersionModal: false,
      newLocale: {
        locale: '',
        name: '',
        subtitle: '',
        privacy_policy_url: ''
      },
      newVersion: {
        version_string: '',
        build_number: '',
        platform: 'iOS',
        release_type: 'afterApproval',
        copyright: ''
      },
      ageRatingQuestions: [
        { key: 'violence_cartoon_or_fantasy', label: 'Cartoon- oder Fantasy-Gewalt', options: AGE_RATING_OPTIONS },
        { key: 'violence_realistic', label: 'Realistische Gewalt', options: AGE_RATING_OPTIONS },
        { key: 'sexual_content_or_nudity', label: 'Sexuelle Inhalte/Nacktheit', options: AGE_RATING_OPTIONS },
        { key: 'profanity_or_crude_humor', label: 'Kraftausdrücke/Anzüglicher Humor', options: AGE_RATING_OPTIONS },
        { key: 'alcohol_tobacco_or_drug_use_or_references', label: 'Alkohol/Tabak/Drogen', options: AGE_RATING_OPTIONS },
        { key: 'horror_fear_themes', label: 'Horror/Angst-Themen', options: AGE_RATING_OPTIONS },
        { key: 'mature_suggestive_themes', label: 'Anzügliche Themen für Erwachsene', options: AGE_RATING_OPTIONS },
        { key: 'gambling_simulated', label: 'Simuliertes Glücksspiel', options: AGE_RATING_OPTIONS }
      ]
    };
  },
  
  computed: {
    projectId() {
      return this.$route.params.project;
    },
    availableLocales() {
      const usedLocales = this.localizations.map(l => l.locale);
      return this.locales.filter(l => !usedLocales.includes(l.code));
    }
  },
  
  mounted() {
    this.loadApp();
    this.loadLocales();
    this.loadCategories();
  },
  
  methods: {
    async loadApp() {
      this.loading = true;
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=app&app_id=${this.appId}&project=${this.projectId}`);
        if (res.data.success) {
          this.app = res.data.app;
          this.localizations = res.data.localizations || [];
          this.versions = res.data.versions || [];
          this.categories = res.data.categories || [];
          this.ageRating = res.data.age_rating;
          
          this.editForm = { ...this.app };
          
          // Set category form
          const primary = this.categories.find(c => c.category_type === 'primary');
          const secondary = this.categories.find(c => c.category_type === 'secondary');
          this.categoryForm.primary = primary?.category_id || '';
          this.categoryForm.secondary = secondary?.category_id || '';
          
          // Set age rating form
          if (this.ageRating) {
            this.ageRatingForm = { ...this.ageRating };
          }
        }
      } catch (e) {
        console.error('Error loading app:', e);
        this.$toast?.error('Fehler beim Laden der App');
      } finally {
        this.loading = false;
      }
    },
    
    async loadLocales() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=locales&project=${this.projectId}`);
        if (res.data.success) {
          this.locales = res.data.locales || [];
        }
      } catch (e) {
        console.error('Error loading locales:', e);
      }
    },
    
    async loadCategories() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=categories&project=${this.projectId}`);
        if (res.data.success) {
          this.availableCategories = res.data.categories || [];
        }
      } catch (e) {
        console.error('Error loading categories:', e);
      }
    },
    
    async saveAppDetails() {
      try {
        const res = await this.$axios.put(`appstore_metadata.php?action=app&app_id=${this.appId}&project=${this.projectId}`, this.editForm);
        if (res.data.success) {
          this.$toast?.success('App gespeichert');
          this.loadApp();
        }
      } catch (e) {
        console.error('Error saving app:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    async saveLocalization() {
      if (!this.newLocale.locale) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=app_localizations&app_id=${this.appId}&project=${this.projectId}`, this.newLocale);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gespeichert');
          this.showAddLocaleModal = false;
          this.newLocale = { locale: '', name: '', subtitle: '', privacy_policy_url: '' };
          this.loadApp();
        }
      } catch (e) {
        console.error('Error saving localization:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    editLocalization(loc) {
      this.newLocale = { ...loc };
      this.showAddLocaleModal = true;
    },
    
    async deleteLocalization(loc) {
      if (!confirm(`Lokalisierung "${loc.locale}" wirklich löschen?`)) return;
      
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=app_localizations&app_id=${this.appId}&locale=${loc.locale}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gelöscht');
          this.loadApp();
        }
      } catch (e) {
        console.error('Error deleting localization:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },
    
    async createVersion() {
      if (!this.newVersion.version_string) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=versions&app_id=${this.appId}&project=${this.projectId}`, this.newVersion);
        if (res.data.success) {
          this.$toast?.success('Version erstellt');
          this.showAddVersionModal = false;
          this.newVersion = { version_string: '', build_number: '', platform: 'iOS', release_type: 'afterApproval', copyright: '' };
          this.loadApp();
        }
      } catch (e) {
        console.error('Error creating version:', e);
        this.$toast?.error('Fehler beim Erstellen');
      }
    },
    
    async deleteVersion(version) {
      if (!confirm(`Version ${version.version_string} wirklich löschen?`)) return;
      
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=version&version_id=${version.id}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Version gelöscht');
          this.loadApp();
        }
      } catch (e) {
        console.error('Error deleting version:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },
    
    openVersionEditor(versionId) {
      this.$router.push(`appstore-metadata/app/${this.appId}/version/${versionId}`);
    },
    
    openScreenshotManager(versionId) {
      this.$router.push(`appstore-metadata/app/${this.appId}/screenshots/${versionId}`);
    },
    
    async saveCategories() {
      try {
        if (this.categoryForm.primary) {
          const cat = this.availableCategories.find(c => c.id === this.categoryForm.primary);
          await this.$axios.post(`appstore_metadata.php?action=categories&app_id=${this.appId}&project=${this.projectId}`, {
            category_type: 'primary',
            category_id: this.categoryForm.primary,
            category_name: cat?.name || ''
          });
        }
        
        if (this.categoryForm.secondary) {
          const cat = this.availableCategories.find(c => c.id === this.categoryForm.secondary);
          await this.$axios.post(`appstore_metadata.php?action=categories&app_id=${this.appId}&project=${this.projectId}`, {
            category_type: 'secondary',
            category_id: this.categoryForm.secondary,
            category_name: cat?.name || ''
          });
        }
        
        this.$toast?.success('Kategorien gespeichert');
      } catch (e) {
        console.error('Error saving categories:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    async saveAgeRating() {
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=age_ratings&app_id=${this.appId}&project=${this.projectId}`, this.ageRatingForm);
        if (res.data.success) {
          this.$toast?.success('Altersfreigabe gespeichert');
        }
      } catch (e) {
        console.error('Error saving age rating:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    async syncFromAppStore() {
      this.$toast?.info('Synchronisierung gestartet...');
      try {
        await this.$axios.get(`appstore_metadata.php?action=sync_pull&app_id=${this.appId}&project=${this.projectId}`);
        this.$toast?.success('Synchronisierung abgeschlossen');
        this.loadApp();
      } catch (e) {
        this.$toast?.error('Fehler bei der Synchronisierung');
      }
    },
    
    async pushToAppStore() {
      this.$toast?.info('Push gestartet...');
      try {
        await this.$axios.get(`appstore_metadata.php?action=sync_push&app_id=${this.appId}&project=${this.projectId}`);
        this.$toast?.success('Push abgeschlossen');
      } catch (e) {
        this.$toast?.error('Fehler beim Push');
      }
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
  max-width: 1200px;
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

/* App Header */
.app-header-card {
  background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
  border-radius: var(--radius-lg);
  padding: 32px;
  margin-bottom: 24px;
  color: white;
}

.app-header-content {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.app-icon-wrapper {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-icon-wrapper ion-icon {
  font-size: 48px;
}

.app-header-info {
  flex: 1;
  min-width: 200px;
}

.app-header-info h1 {
  margin: 0 0 4px 0;
  font-size: 28px;
}

.bundle-id {
  margin: 0 0 12px 0;
  opacity: 0.8;
  font-family: monospace;
}

.app-badges {
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

.badge.live { background: var(--success-color); }
.badge.draft { background: var(--secondary-color); }

.app-header-actions {
  display: flex;
  gap: 12px;
}

/* Tabs */
.tabs-container {
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
  padding: 12px 20px;
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

/* Content Sections */
.content-section {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h3 {
  margin: 0;
  color: var(--text-primary);
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
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
}

.card-body {
  padding: 24px;
}

.card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
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
.form-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input[readonly] {
  background: var(--background);
  color: var(--text-muted);
}

.char-count {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
  text-align: right;
}

/* Localizations Grid */
.localizations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.locale-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
}

.locale-card:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.locale-card.add-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  border-style: dashed;
  color: var(--text-muted);
}

.locale-card.add-card ion-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.locale-card.add-card:hover {
  color: var(--primary-color);
}

.locale-header {
  padding: 16px;
  background: var(--background);
  display: flex;
  align-items: center;
  gap: 12px;
}

.locale-flag {
  font-size: 24px;
}

.locale-name {
  flex: 1;
  font-weight: 600;
  color: var(--text-primary);
}

.locale-code {
  font-size: 12px;
  color: var(--text-muted);
  font-family: monospace;
}

.locale-body {
  padding: 16px;
}

.locale-field {
  margin-bottom: 12px;
}

.locale-field:last-child {
  margin-bottom: 0;
}

.locale-field label {
  display: block;
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 2px;
}

.locale-field span {
  color: var(--text-primary);
}

.locale-footer {
  padding: 12px 16px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 8px;
}

.card-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 12px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.card-action:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.card-action.danger:hover {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

/* Versions List */
.versions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.version-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
}

.version-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.version-number h4 {
  margin: 0;
  font-size: 20px;
  color: var(--text-primary);
}

.build-badge {
  font-size: 12px;
  color: var(--text-muted);
}

.version-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.version-status.draft { background: rgba(100, 116, 139, 0.1); color: var(--secondary-color); }
.version-status.ready_for_submission { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
.version-status.in_review { background: rgba(8, 145, 178, 0.1); color: var(--info-color); }
.version-status.approved, .version-status.ready_for_sale { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
.version-status.rejected { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }

.version-meta {
  display: flex;
  gap: 20px;
  margin-bottom: 16px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.version-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* Rating Questions */
.rating-questions {
  display: grid;
  gap: 16px;
}

.rating-question {
  display: grid;
  grid-template-columns: 1fr 200px;
  align-items: center;
  gap: 16px;
}

.rating-question label {
  color: var(--text-primary);
}

.info-text {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

/* Empty State */
.empty-state-sm {
  text-align: center;
  padding: 40px 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.empty-state-sm ion-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.empty-state-sm p {
  color: var(--text-secondary);
  margin-bottom: 16px;
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
  transform: translateY(-1px);
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

.action-btn.danger:hover {
  background: var(--danger-color);
  color: white;
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
  max-width: 500px;
  max-height: 90vh;
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
  overflow-y: auto;
  max-height: 60vh;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .app-header-content {
    flex-direction: column;
    text-align: center;
  }
  
  .app-header-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .app-header-actions .action-btn {
    width: 100%;
    justify-content: center;
  }
  
  .tabs-container {
    flex-wrap: nowrap;
  }
  
  .tab-btn {
    padding: 10px 16px;
    font-size: 13px;
  }
  
  .rating-question {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .version-actions {
    flex-direction: column;
  }
}
</style>
