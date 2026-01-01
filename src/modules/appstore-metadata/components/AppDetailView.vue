<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="apps-outline" :title="app?.name || 'App Details'" bg="transparent" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <button class="back-btn" @click="$router.push(`/project/${this.projectId}/appstore-metadata`)">
              <ion-icon name="arrow-back-outline"></ion-icon>
              Zurück
            </button>
            <h1 v-if="app">{{ app.name }}</h1>
            <p v-if="app" class="bundle-id">{{ app.bundle_id }}</p>
          </div>
          <div class="header-actions" v-if="app">
            <span class="status-badge" :class="app.status">{{ getStatusLabel(app.status) }}</span>
            <button class="action-btn secondary" @click="syncFromAppStore">
              <ion-icon name="cloud-download-outline"></ion-icon>
              Von App Store laden
            </button>
            <button class="action-btn primary" @click="pushToAppStore">
              <ion-icon name="cloud-upload-outline"></ion-icon>
              Zu App Store pushen
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Lade App Details...</p>
        </div>

        <!-- Tabs -->
        <div class="tab-navigation" v-if="app && !loading">
          <button v-for="tab in tabs" :key="tab.id" class="tab-btn" :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id">
            <ion-icon :name="tab.icon"></ion-icon>
            {{ tab.label }}
          </button>
        </div>

        <!-- Push Results Modal -->
        <div v-if="showPushResultsModal" class="modal-overlay" @click="showPushResultsModal = false">
          <div class="modal-content push-results-modal" @click.stop>
            <div class="modal-header">
              <h2>
                <ion-icon
                  :name="pushResults.hasErrors ? 'alert-circle-outline' : 'checkmark-circle-outline'"></ion-icon>
                Push Ergebnisse
              </h2>
              <button class="close-btn" @click="showPushResultsModal = false">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>
            <div class="modal-body">
              <!-- Summary -->
              <div class="results-summary">
                <div class="summary-card success">
                  <ion-icon name="checkmark-circle-outline"></ion-icon>
                  <div>
                    <div class="count">{{ pushResults.succeeded.length }}</div>
                    <div class="label">Erfolgreich</div>
                  </div>
                </div>
                <div class="summary-card error">
                  <ion-icon name="close-circle-outline"></ion-icon>
                  <div>
                    <div class="count">{{ pushResults.failed.length }}</div>
                    <div class="label">Fehlgeschlagen</div>
                  </div>
                </div>
              </div>

              <!-- Failed Items -->
              <div v-if="pushResults.failed.length > 0" class="results-section">
                <h3>Fehlgeschlagene Lokalisierungen</h3>
                <div class="result-item error" v-for="(item, idx) in pushResults.failed" :key="'error-' + idx">
                  <div class="item-header">
                    <span class="locale-badge">{{ item.locale }}</span>
                    <span class="type-badge">{{ item.type === 'app_localization' ? 'App' : 'Version ' + item.version
                      }}</span>
                  </div>
                  <div class="error-message">{{ item.error }}</div>
                </div>
              </div>

              <!-- Successful Items -->
              <div v-if="pushResults.succeeded.length > 0" class="results-section">
                <h3>Erfolgreich aktualisiert</h3>
                <div class="result-item-grid">
                  <div class="result-item success" v-for="(item, idx) in pushResults.succeeded" :key="'success-' + idx">
                    <span class="locale-badge">{{ item.locale }}</span>
                    <span class="type-badge">{{ item.type === 'app_localization' ? 'App' : 'Version' }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <div class="export-buttons">
                <button class="btn-secondary" @click="exportResultsAsJson">
                  <ion-icon name="code-outline"></ion-icon>
                  Als JSON exportieren
                </button>
                <button class="btn-secondary" @click="exportResultsAsText">
                  <ion-icon name="document-text-outline"></ion-icon>
                  Als Text exportieren
                </button>
              </div>
              <button class="btn-primary" @click="showPushResultsModal = false">Schließen</button>
            </div>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" v-if="app && !loading">
          <!-- General Info Tab -->
          <div v-if="activeTab === 'general'" class="content-section">
            <div class="data-card">
              <div class="card-header">
                <div class="header-left">
                  <h3>Allgemeine Informationen</h3>
                </div>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">App ID</label>
                    <input v-model="app.app_id" type="text" class="modern-input" readonly />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Bundle ID</label>
                    <input v-model="app.bundle_id" type="text" class="modern-input" readonly />
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">App Name</label>
                    <input v-model="editForm.name" type="text" class="modern-input" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input v-model="editForm.sku" type="text" class="modern-input" />
                  </div>
                </div>

                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">Primäre Sprache</label>
                    <select v-model="editForm.primary_locale" class="modern-select">
                      <option v-for="locale in locales" :key="locale.code" :value="locale.code">
                        {{ locale.name }}
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Status</label>
                    <select v-model="editForm.status" class="modern-select">
                      <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Inhaltsrechte</label>
                  <select v-model="editForm.content_rights_declaration" class="modern-select">
                    <option value="">Nicht angegeben</option>
                    <option value="doesNotUseThirdPartyContent">Verwendet keine Drittanbieter-Inhalte</option>
                    <option value="usesThirdPartyContent">Verwendet Drittanbieter-Inhalte</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
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
              <div v-for="loc in localizations" :key="loc.id" class="locale-card" @click="editLocalization(loc)">
                <div class="locale-header">
                  <span class="locale-flag">{{ getLocaleFlag(loc.locale) }}</span>
                  <span class="locale-name">{{ getJustLocaleName(loc.locale_name || loc.locale) }}</span>
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
              <div v-for="version in versions" :key="version.id" class="version-card">
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
                <p class="card-subtitle">Wähle die Kategorien für deine App im App Store</p>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">Primäre Kategorie <span class="required">*</span></label>
                    <select v-model="categoryForm.primary" class="modern-select">
                      <option value="">Kategorie wählen</option>
                      <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                    <span class="form-hint">Die Hauptkategorie, unter der deine App erscheint</span>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Sekundäre Kategorie</label>
                    <select v-model="categoryForm.secondary" class="modern-select">
                      <option value="">Keine</option>
                      <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                    <span class="form-hint">Optional: Eine zusätzliche Kategorie</span>
                  </div>
                </div>

                <div class="form-actions">
                  <button class="action-btn primary" @click="saveCategories">
                    <ion-icon name="save-outline"></ion-icon>
                    Kategorien speichern
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Age Rating Tab -->
          <div v-if="activeTab === 'age_rating'" class="content-section">
            <div class="data-card">
              <div class="card-header">
                <h3>Altersfreigabe</h3>
                <p class="card-subtitle">Beantworte diese Fragen, um die Altersfreigabe für deine App zu bestimmen</p>
              </div>
              <div class="card-body">
                <div class="rating-questions">
                  <div class="rating-question" v-for="question in ageRatingQuestions" :key="question.key">
                    <label class="form-label">{{ question.label }}</label>
                    <select v-model="ageRatingForm[question.key]" class="modern-select">
                      <option v-for="opt in question.options" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="form-actions">
                  <button class="action-btn primary" @click="saveAgeRating">
                    <ion-icon name="save-outline"></ion-icon>
                    Altersfreigabe speichern
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add/Edit Locale Modal -->
      <div v-if="showAddLocaleModal" class="custom-modal-overlay" @click.self="closeLocaleModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>{{ newLocale.id ? 'Sprache bearbeiten' : 'Sprache hinzufügen' }}</h3>
            <button class="modal-close-btn" @click="closeLocaleModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>

          <div class="custom-modal-body">
            <div class="form-group" v-if="!newLocale.id">
              <label class="form-label">Sprache <span class="required">*</span></label>
              <select v-model="newLocale.locale" class="modern-select">
                <option value="">Sprache wählen</option>
                <option v-for="locale in availableLocales" :key="locale.code" :value="locale.code">
                  {{ locale.name }} ({{ locale.code }})
                </option>
              </select>
            </div>

            <div class="form-group" v-else>
              <label class="form-label">Sprache</label>
              <div class="locale-display">
                <span class="locale-flag">{{ getLocaleFlag(newLocale.locale) }}</span>
                <span class="locale-name">{{ getLocaleName(newLocale.locale) }}</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">App Name</label>
              <input v-model="newLocale.name" type="text" class="modern-input" placeholder="Lokalisierter App Name" />
            </div>

            <div class="form-group">
              <label class="form-label">Untertitel</label>
              <input v-model="newLocale.subtitle" type="text" class="modern-input" placeholder="Max. 30 Zeichen"
                maxlength="30" />
              <span class="char-count">{{ (newLocale.subtitle || '').length }}/30</span>
            </div>

            <div class="form-group">
              <label class="form-label">Datenschutzrichtlinie URL</label>
              <input v-model="newLocale.privacy_policy_url" type="url" class="modern-input" placeholder="https://..." />
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="closeLocaleModal">Abbrechen</button>
              <button class="action-btn primary" @click="saveLocalization" :disabled="!newLocale.locale">
                <ion-icon name="save-outline"></ion-icon>
                Speichern
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Version Modal -->
      <div v-if="showAddVersionModal" class="custom-modal-overlay" @click.self="showAddVersionModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Neue Version erstellen</h3>
            <button class="modal-close-btn" @click="showAddVersionModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>

          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Versionsnummer <span class="required">*</span></label>
              <input v-model="newVersion.version_string" type="text" class="modern-input" placeholder="z.B. 1.0.0" />
            </div>

            <div class="form-group">
              <label class="form-label">Build-Nummer</label>
              <input v-model="newVersion.build_number" type="text" class="modern-input" placeholder="z.B. 1" />
            </div>

            <div class="form-group">
              <label class="form-label">Plattform</label>
              <select v-model="newVersion.platform" class="modern-select">
                <option value="iOS">iOS</option>
                <option value="macOS">macOS</option>
                <option value="tvOS">tvOS</option>
                <option value="watchOS">watchOS</option>
                <option value="visionOS">visionOS</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Veröffentlichungsart</label>
              <select v-model="newVersion.release_type" class="modern-select">
                <option value="manual">Manuell freigeben</option>
                <option value="afterApproval">Automatisch nach Genehmigung</option>
                <option value="scheduled">Geplante Veröffentlichung</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Copyright</label>
              <input v-model="newVersion.copyright" type="text" class="modern-input"
                placeholder="z.B. © 2024 Dein Unternehmen" />
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="showAddVersionModal = false">Abbrechen</button>
              <button class="action-btn primary" @click="createVersion" :disabled="!newVersion.version_string">
                <ion-icon name="add-outline"></ion-icon>
                Version erstellen
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
import { APP_STATUSES, AGE_RATING_OPTIONS, getLocaleFlag } from '../config';

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
      ],
      showPushResultsModal: false,
      pushResults: {
        succeeded: [],
        failed: [],
        hasErrors: false
      }
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
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${this.appId}/version/${versionId}`);
    },

    openScreenshotManager(versionId) {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${this.appId}/screenshots/${versionId}`);
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
        await this.$axios.get(`appstore_metadata.php?action=sync_pull&app_id=${this.appId}&project=${this.projectId}`, {
          timeout: 300000 // 5 minutes timeout for sync
        });
        this.$toast?.success('Synchronisierung abgeschlossen');
        this.loadApp();
      } catch (e) {
        this.$toast?.error('Fehler bei der Synchronisierung');
      }
    },

    async pushToAppStore() {
      this.$toast?.info('Push gestartet...');
      try {
        const response = await this.$axios.get(`appstore_metadata.php?action=sync_push&app_id=${this.appId}&project=${this.projectId}`, {
          timeout: 60000 // 1 minute - now fast because we only push dirty entries
        });

        // Check if no changes
        if (response.data?.skipped_reason === 'no_changes') {
          this.$toast?.info('✓ Keine Änderungen - alles bereits synchronisiert');
          return;
        }

        // Analyze results
        const results = response.data?.results || [];
        const succeeded = results.filter(r => ['updated', 'created', 'recreated', 'synced_and_updated'].includes(r.status));
        const failed = results.filter(r => r.status === 'failed');
        const stats = response.data?.stats || {};

        // Store results for modal
        this.pushResults = {
          succeeded,
          failed,
          hasErrors: failed.length > 0
        };

        if (failed.length === 0 && succeeded.length > 0) {
          this.$toast?.success(`✓ Push erfolgreich! ${succeeded.length} Lokalisierung(en) aktualisiert.`);
        } else if (failed.length === 0 && succeeded.length === 0) {
          this.$toast?.info('✓ Keine Änderungen zu pushen');
        } else {
          // Show summary toast with click to open modal
          const summary = `${succeeded.length} erfolgreich, ${failed.length} fehlgeschlagen`;
          this.$toast?.warning(`Push abgeschlossen: ${summary} - Details anzeigen`, {
            duration: 5000,
            onClick: () => {
              this.showPushResultsModal = true;
            }
          });
          // Auto-show modal for errors
          this.showPushResultsModal = true;
        }

        // Reload app data to reflect changes
        await this.loadApp();
      } catch (e) {
        this.$toast?.error('Fehler beim Push: ' + (e.response?.data?.message || e.message));
      }
    },

    getStatusLabel(status) {
      const found = APP_STATUSES.find(s => s.value === status);
      return found?.label || status;
    },

    getLocaleFlag(locale) {
      return getLocaleFlag(locale);
    },

    getLocaleName(locale) {
      const found = this.locales.find(l => l.code === locale);
      return found ? `${found.name} (${locale})` : locale;
    },

    getJustLocaleName(locale) {
      const found = this.locales.find(l => l.code === locale);
      return found ? `${found.name}` : locale;
    },

    closeLocaleModal() {
      this.showAddLocaleModal = false;
      this.newLocale = { locale: '', name: '', subtitle: '', privacy_policy_url: '' };
    },

    exportResultsAsJson() {
      const data = {
        timestamp: new Date().toISOString(),
        appId: this.appId,
        appName: this.app?.name,
        summary: {
          total: this.pushResults.succeeded.length + this.pushResults.failed.length,
          succeeded: this.pushResults.succeeded.length,
          failed: this.pushResults.failed.length
        },
        results: {
          succeeded: this.pushResults.succeeded,
          failed: this.pushResults.failed
        }
      };

      const jsonStr = JSON.stringify(data, null, 2);
      const blob = new Blob([jsonStr], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `appstore-push-results-${this.appId}-${Date.now()}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      this.$toast?.success('JSON-Export heruntergeladen');
    },

    exportResultsAsText() {
      const timestamp = new Date().toLocaleString('de-DE');
      let text = `App Store Push Ergebnisse\n`;
      text += `=================================\n`;
      text += `App: ${this.app?.name || 'N/A'}\n`;
      text += `App ID: ${this.appId}\n`;
      text += `Zeitpunkt: ${timestamp}\n\n`;

      text += `Zusammenfassung:\n`;
      text += `---------------\n`;
      text += `Gesamt: ${this.pushResults.succeeded.length + this.pushResults.failed.length}\n`;
      text += `✓ Erfolgreich: ${this.pushResults.succeeded.length}\n`;
      text += `✗ Fehlgeschlagen: ${this.pushResults.failed.length}\n\n`;

      if (this.pushResults.failed.length > 0) {
        text += `Fehlgeschlagene Lokalisierungen:\n`;
        text += `================================\n\n`;
        this.pushResults.failed.forEach((item, idx) => {
          text += `${idx + 1}. ${item.locale} (${item.type === 'app_localization' ? 'App' : 'Version ' + item.version})\n`;
          text += `   Fehler: ${item.error}\n\n`;
        });
      }

      if (this.pushResults.succeeded.length > 0) {
        text += `Erfolgreich aktualisierte Lokalisierungen:\n`;
        text += `=========================================\n\n`;
        this.pushResults.succeeded.forEach((item, idx) => {
          text += `${idx + 1}. ${item.locale} (${item.type === 'app_localization' ? 'App' : 'Version ' + (item.version || '')})\n`;
        });
      }

      const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `appstore-push-results-${this.appId}-${Date.now()}.txt`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      this.$toast?.success('Text-Export heruntergeladen');
    }
  }
};
</script>

<style scoped>
/* Modern Design System - Same as ManageUsers */
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
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.bundle-id {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-family: monospace;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Back Button */
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-size: 14px;
  cursor: pointer;
  transition: color 0.2s ease;
}

.back-btn:hover {
  color: var(--primary-color);
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.live {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

.status-badge.draft {
  background: rgba(100, 116, 139, 0.1);
  color: var(--secondary-color);
  border: 1px solid rgba(100, 116, 139, 0.2);
}

.status-badge.in_review {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(217, 119, 6, 0.2);
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
  color: var(--text-primary);
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

/* Tab Navigation */
.tab-navigation {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 12px;
  overflow-x: auto;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.tab-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.tab-btn.active {
  background: var(--primary-color);
  color: white;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
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
}

.header-left h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.card-body {
  padding: 24px;
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
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

.required {
  color: var(--danger-color);
}

.locale-display {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.locale-display .locale-flag {
  font-size: 24px;
}

.locale-display .locale-name {
  font-weight: 500;
  color: var(--text-primary);
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
  box-sizing: border-box;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-input[readonly] {
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

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
}

/* Section Header */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
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
  border-color: var(--primary-color);
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
  transition: all 0.2s ease;
}

.version-card:hover {
  box-shadow: var(--shadow-md);
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

.version-status.draft {
  background: rgba(100, 116, 139, 0.1);
  color: var(--secondary-color);
}

.version-status.ready_for_submission {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color);
}

.version-status.in_review {
  background: rgba(8, 145, 178, 0.1);
  color: var(--info-color);
}

.version-status.approved,
.version-status.ready_for_sale {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.version-status.rejected {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

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
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.rating-question {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  transition: all 0.2s ease;
}

.rating-question:hover {
  border-color: var(--primary-color);
  background: var(--surface);
}

.rating-question label {
  color: var(--text-primary);
  font-weight: 500;
}

.card-subtitle {
  margin: 4px 0 0 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: normal;
}

.form-hint {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-secondary);
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

/* Modal Styles */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 500px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.custom-modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  min-height: 0;
}

/* Push Results Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  animation: modalFadeIn 0.2s ease;
}

.modal-content {
  background: var(--surface);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
  overflow: hidden;
}

.push-results-modal {
  width: 800px;
  max-width: 95vw;
  max-height: 85vh;
}

.push-results-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  border-bottom: 1px solid var(--border);
}

.push-results-modal .modal-header h2 {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.push-results-modal .modal-header ion-icon {
  font-size: 28px;
}

.close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.push-results-modal .modal-body {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
}

.push-results-modal .modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.export-buttons {
  display: flex;
  gap: 8px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.btn-secondary ion-icon {
  font-size: 16px;
}

.results-summary {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: var(--radius-lg);
  background: var(--surface);
  border: 2px solid var(--border);
}

.summary-card.success {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.05);
}

.summary-card.success ion-icon {
  font-size: 40px;
  color: #10b981;
}

.summary-card.error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.summary-card.error ion-icon {
  font-size: 40px;
  color: #ef4444;
}

.summary-card .count {
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
}

.summary-card .label {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 500;
}

.results-section {
  margin-top: 32px;
}

.results-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 16px 0;
}

.result-item {
  padding: 16px;
  border-radius: var(--radius);
  background: var(--surface);
  border: 1px solid var(--border);
  margin-bottom: 12px;
}

.result-item.error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.result-item.success {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.05);
}

.result-item-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.result-item-grid .result-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  margin-bottom: 0;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.locale-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  background: var(--primary-color);
  color: white;
}

.type-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  background: var(--border);
  color: var(--text-secondary);
}

.error-message {
  font-size: 13px;
  color: #dc2626;
  line-height: 1.5;
  font-family: 'SF Mono', Monaco, monospace;
}

.btn-primary {
  padding: 10px 20px;
  border: none;
  border-radius: var(--radius);
  background: var(--primary-color);
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

/* Animations */
@keyframes modalFadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: center;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .version-actions {
    flex-direction: column;
  }

  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}
</style>
