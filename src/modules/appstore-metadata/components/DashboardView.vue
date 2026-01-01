<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="apps-outline" title="App Store Metadata Manager" bg="transparent"/>
      
      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>App Store Metadata</h1>
            <p>Verwalte deine App Store Metadaten</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="goToConfig">
              <ion-icon name="key-outline"></ion-icon>
              API Einstellungen
            </button>
            <button class="action-btn primary" @click="openAddAppModal">
              <ion-icon name="add-outline"></ion-icon>
              App hinzufügen
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="apps-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_apps }}</h3>
              <p>Verwaltete Apps</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="git-branch-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_versions }}</h3>
              <p>App Versionen</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <ion-icon name="language-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.total_locales }}</h3>
              <p>Sprachen</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon" :class="stats.has_credentials ? 'connected' : 'disconnected'">
              <ion-icon :name="stats.has_credentials ? 'cloud-done-outline' : 'cloud-offline-outline'"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.has_credentials ? 'Verbunden' : 'Nicht verbunden' }}</h3>
              <p>API Status</p>
              <a v-if="!stats.has_credentials" href="#" @click.prevent="goToConfig" class="stat-link">API einrichten</a>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Lade Apps...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="apps.length === 0" class="no-data-state">
          <div class="no-data-content">
            <ion-icon name="apps-outline" class="no-data-icon"></ion-icon>
            <h4>Keine Apps verbunden</h4>
            <p>Füge deine erste App hinzu, um die Metadaten zu verwalten.</p>
            <button class="action-btn primary" @click="openAddAppModal">
              <ion-icon name="add-outline"></ion-icon>
              Erste App hinzufügen
            </button>
          </div>
        </div>

        <!-- Apps Table -->
        <div v-else class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Alle Apps</h3>
              <span class="entry-count">{{ apps.length }} App{{ apps.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>

          <div class="table-wrapper">
            <div class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="header-cell">App</div>
                <div class="header-cell">Bundle ID</div>
                <div class="header-cell">Status</div>
                <div class="header-cell">Versionen</div>
                <div class="header-cell">Sprachen</div>
                <div class="header-cell actions-header">Aktionen</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <div v-for="app in apps" :key="app.id" class="table-row" @click="openApp(app.id)">
                  <div class="table-cell cell-app">
                    <div class="app-info">
                      <div class="app-icon">
                        <ion-icon name="logo-apple-appstore"></ion-icon>
                      </div>
                      <div class="app-details">
                        <span class="app-name">{{ app.name }}</span>
                        <span class="app-version" v-if="app.latest_version">v{{ app.latest_version }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="table-cell cell-bundle">
                    <code class="bundle-id">{{ app.bundle_id }}</code>
                  </div>

                  <div class="table-cell cell-status">
                    <span class="status-badge" :class="app.status">
                      {{ getStatusLabel(app.status) }}
                    </span>
                  </div>

                  <div class="table-cell">
                    <span class="meta-value">{{ app.version_count || 0 }}</span>
                  </div>

                  <div class="table-cell">
                    <span class="meta-value">{{ app.locale_count || 0 }}</span>
                  </div>

                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn edit-btn" @click.stop="openApp(app.id)" title="Bearbeiten">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn assign-btn" @click.stop="syncApp(app.id)" title="Synchronisieren">
                        <ion-icon name="sync-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click.stop="confirmDeleteApp(app)" title="Löschen">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="data-card" v-if="recentActivity.length > 0">
          <div class="card-header">
            <h3>Letzte Aktivitäten</h3>
          </div>
          <div class="activity-list">
            <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
              <div class="activity-icon" :class="activity.status">
                <ion-icon :name="getActivityIcon(activity.operation)"></ion-icon>
              </div>
              <div class="activity-info">
                <div class="activity-title">{{ getActivityTitle(activity.operation) }}</div>
                <div class="activity-time">{{ formatDate(activity.created_at) }}</div>
              </div>
              <div class="activity-status" :class="activity.status">
                {{ activity.status }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add App Modal -->
      <div v-if="showAddAppModal" class="custom-modal-overlay" @click.self="showAddAppModal = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>App hinzufügen</h3>
            <button class="modal-close-btn" @click="showAddAppModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <!-- Tab Navigation -->
            <div class="tab-navigation">
              <button 
                class="tab-btn" 
                :class="{ active: addAppTab === 'browse' }"
                @click="addAppTab = 'browse'"
              >
                <ion-icon name="cloud-download-outline"></ion-icon>
                Aus App Store Connect
              </button>
              <button 
                class="tab-btn" 
                :class="{ active: addAppTab === 'manual' }"
                @click="addAppTab = 'manual'"
              >
                <ion-icon name="create-outline"></ion-icon>
                Manuell eingeben
              </button>
            </div>

            <!-- Browse Apps Tab -->
            <div v-if="addAppTab === 'browse'" class="tab-content">
              <div v-if="!stats.has_credentials" class="info-box warning">
                <ion-icon name="warning-outline"></ion-icon>
                <div>
                  <strong>API nicht konfiguriert</strong>
                  <p>Bitte füge zuerst deine App Store Connect API Credentials hinzu.</p>
                  <button class="action-btn small" @click="goToConfig">API einrichten</button>
                </div>
              </div>
              
              <div v-else>
                <div class="browse-header">
                  <p>Wähle eine App aus deinem App Store Connect Account:</p>
                  <button class="action-btn small" @click="loadBrowseApps" :disabled="loadingBrowseApps">
                    <ion-icon :name="loadingBrowseApps ? 'hourglass-outline' : 'refresh-outline'"></ion-icon>
                    {{ loadingBrowseApps ? 'Lädt...' : 'Aktualisieren' }}
                  </button>
                </div>
                
                <div v-if="loadingBrowseApps" class="loading-state small">
                  <div class="loading-icon">
                    <ion-icon name="hourglass-outline"></ion-icon>
                  </div>
                  <p>Lade Apps aus App Store Connect...</p>
                </div>
                
                <div v-else-if="browseAppsError" class="info-box error">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  <div>{{ browseAppsError }}</div>
                </div>
                
                <div v-else-if="browseApps.length === 0" class="empty-state small">
                  <ion-icon name="apps-outline"></ion-icon>
                  <p>Keine Apps gefunden. Klicke auf "Aktualisieren" um die Apps zu laden.</p>
                </div>
                
                <div v-else class="browse-apps-list">
                  <div 
                    v-for="app in browseApps" 
                    :key="app.id" 
                    class="browse-app-item"
                    :class="{ connected: app.is_connected, selected: selectedBrowseApp?.id === app.id }"
                    @click="selectBrowseApp(app)"
                  >
                    <div class="browse-app-icon">
                      <ion-icon name="logo-apple-appstore"></ion-icon>
                    </div>
                    <div class="browse-app-info">
                      <div class="browse-app-name">{{ app.name }}</div>
                      <div class="browse-app-bundle">{{ app.bundle_id }}</div>
                    </div>
                    <div class="browse-app-status">
                      <span v-if="app.is_connected" class="status-badge connected">
                        <ion-icon name="checkmark-circle"></ion-icon>
                        Verbunden
                      </span>
                      <span v-else-if="selectedBrowseApp?.id === app.id" class="status-badge selected">
                        <ion-icon name="radio-button-on"></ion-icon>
                        Ausgewählt
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Manual Input Tab -->
            <div v-if="addAppTab === 'manual'" class="tab-content">
              <div class="info-box info">
                <ion-icon name="information-circle-outline"></ion-icon>
                <div>
                  <strong>Manuelle Eingabe</strong>
                  <p>Erstelle einen lokalen Eintrag für deine App. Die Metadaten werden nicht automatisch mit App Store Connect synchronisiert.</p>
                </div>
              </div>
              
              <div class="form-group">
                <label>App ID <span class="required">*</span></label>
                <input 
                  v-model="newApp.app_id" 
                  type="text" 
                  placeholder="z.B. 123456789"
                  class="form-input"
                />
                <span class="form-hint">Die App Store ID aus App Store Connect (nur zur Referenz)</span>
              </div>
              
              <div class="form-group">
                <label>Bundle ID <span class="required">*</span></label>
                <input 
                  v-model="newApp.bundle_id" 
                  type="text" 
                  placeholder="z.B. com.example.myapp"
                  class="form-input"
                />
              </div>
              
              <div class="form-group">
                <label>App Name <span class="required">*</span></label>
                <input 
                  v-model="newApp.name" 
                  type="text" 
                  placeholder="Meine App"
                  class="form-input"
                />
              </div>
              
              <div class="form-group">
                <label>SKU</label>
                <input 
                  v-model="newApp.sku" 
                  type="text" 
                  placeholder="Optional"
                  class="form-input"
                />
              </div>
              
                <div class="form-group">
                  <label class="form-label">Sprache wählen</label>
                  <select v-model="newLocale" class="modern-select">
                    <option value="">Wählen...</option>
                    <option v-for="loc in availableLocales" :key="loc.code" :value="loc.code">
                      {{ loc.name }} ({{ loc.code }})
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Manual Input Tab -->
            <div v-if="addAppTab === 'manual'" class="tab-content">
              <div class="info-box info">
                <ion-icon name="information-circle-outline"></ion-icon>
                <div>
                  <strong>Manuelle Eingabe</strong>
                  <p>Erstelle einen lokalen Eintrag für deine App. Die Metadaten werden nicht automatisch mit App Store Connect synchronisiert.</p>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">App ID <span class="required">*</span></label>
                <input 
                  v-model="newApp.app_id" 
                  type="text" 
                  placeholder="z.B. 123456789"
                  class="modern-input"
                />
                <span class="form-help">Die App Store ID aus App Store Connect (nur zur Referenz)</span>
              </div>
              
              <div class="form-group">
                <label class="form-label">Bundle ID <span class="required">*</span></label>
                <input 
                  v-model="newApp.bundle_id" 
                  type="text" 
                  placeholder="z.B. com.example.myapp"
                  class="modern-input"
                />
              </div>
              
              <div class="form-group">
                <label class="form-label">App Name <span class="required">*</span></label>
                <input 
                  v-model="newApp.name" 
                  type="text" 
                  placeholder="Meine App"
                  class="modern-input"
                />
              </div>
              
              <div class="form-group">
                <label class="form-label">SKU</label>
                <input 
                  v-model="newApp.sku" 
                  type="text" 
                  placeholder="Optional"
                  class="modern-input"
                />
              </div>
              
              <div class="form-group">
                <label class="form-label">Primäre Sprache</label>
                <select v-model="newApp.primary_locale" class="modern-select">
                  <option v-for="locale in locales" :key="locale.code" :value="locale.code">
                    {{ locale.name }} ({{ locale.code }})
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <button class="action-btn secondary" @click="showAddAppModal = false">Abbrechen</button>
            <button 
              v-if="addAppTab === 'browse'" 
              class="action-btn primary" 
              @click="connectSelectedApp" 
              :disabled="!selectedBrowseApp || selectedBrowseApp.is_connected || connectingApp"
            >
              <ion-icon :name="connectingApp ? 'hourglass-outline' : 'link-outline'"></ion-icon>
              {{ connectingApp ? 'Verbinde...' : 'App verbinden' }}
            </button>
            <button 
              v-else 
              class="action-btn primary" 
              @click="createApp" 
              :disabled="!canCreateApp"
            >
              <ion-icon name="add-outline"></ion-icon>
              App erstellen
            </button>
          </div>
        </div>
      <!--</div>-->

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="custom-modal-overlay" @click.self="showDeleteModal = false">
        <div class="custom-modal-content modal-sm" @click.stop>
          <div class="custom-modal-header danger">
            <h3>App löschen</h3>
            <button class="modal-close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <p>Bist du sicher, dass du <strong>{{ appToDelete?.name }}</strong> löschen möchtest?</p>
            <p class="warning-text">Diese Aktion kann nicht rückgängig gemacht werden. Alle Versionen und Lokalisierungen werden ebenfalls gelöscht.</p>
            
            <div class="form-actions">
              <button class="action-btn secondary" @click="showDeleteModal = false">Abbrechen</button>
              <button class="action-btn danger" @click="deleteApp">
                <ion-icon name="trash-outline"></ion-icon>
                Endgültig löschen
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
  name: 'MetadataDashboard',
  components: {
    SiteTitle
  },
  data() {
    return {
      loading: true,
      apps: [],
      stats: {
        total_apps: 0,
        total_versions: 0,
        total_locales: 0,
        has_credentials: false
      },
      recentActivity: [],
      locales: [],
      showAddAppModal: false,
      showDeleteModal: false,
      appToDelete: null,
      addAppTab: 'browse',
      browseApps: [],
      loadingBrowseApps: false,
      browseAppsError: null,
      selectedBrowseApp: null,
      connectingApp: false,
      newApp: {
        app_id: '',
        bundle_id: '',
        name: '',
        sku: '',
        primary_locale: 'en-US'
      }
    };
  },
  
  computed: {
    projectId() {
      return this.$route.params.project;
    },
    canCreateApp() {
      return this.newApp.app_id && this.newApp.bundle_id && this.newApp.name;
    }
  },
  
  mounted() {
    this.loadDashboard();
    this.loadLocales();
  },
  
  methods: {
    openAddAppModal() {
      this.showAddAppModal = true;
      this.addAppTab = 'browse';
      this.selectedBrowseApp = null;
      this.browseAppsError = null;
      
      // Auto-load apps if credentials exist
      if (this.stats.has_credentials && this.browseApps.length === 0) {
        this.loadBrowseApps();
      }
    },
    
    async loadDashboard() {
      this.loading = true;
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=dashboard&project=${this.projectId}`);
        if (res.data.success) {
          this.stats = res.data.stats || this.stats;
          this.apps = res.data.recent_apps || [];
          this.recentActivity = res.data.recent_activity || [];
        }
        
        // Load all apps
        const appsRes = await this.$axios.get(`appstore_metadata.php?action=apps&project=${this.projectId}`);
        if (appsRes.data.success) {
          this.apps = appsRes.data.apps || [];
        }
      } catch (e) {
        console.error('Error loading dashboard:', e);
        this.$toast?.error('Fehler beim Laden des Dashboards');
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
    
    async createApp() {
      if (!this.canCreateApp) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=apps&project=${this.projectId}`, this.newApp);
        if (res.data.success) {
          this.$toast?.success('App erfolgreich erstellt');
          this.showAddAppModal = false;
          this.resetNewApp();
          this.loadDashboard();
        } else {
          throw new Error(res.data.error || 'Fehler beim Erstellen');
        }
      } catch (e) {
        console.error('Error creating app:', e);
        const errorMsg = e.response?.data?.error || e.message || 'Fehler beim Erstellen der App';
        this.$toast?.error(errorMsg);
      }
    },
    
    resetNewApp() {
      this.newApp = {
        app_id: '',
        bundle_id: '',
        name: '',
        sku: '',
        primary_locale: 'en-US'
      };
      this.selectedBrowseApp = null;
    },
    
    async loadBrowseApps() {
      this.loadingBrowseApps = true;
      this.browseAppsError = null;
      
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=browse_apps&project=${this.projectId}`);
        if (res.data.success) {
          this.browseApps = res.data.apps || [];
        } else {
          this.browseAppsError = res.data.error || 'Fehler beim Laden der Apps';
        }
      } catch (e) {
        console.error('Error loading browse apps:', e);
        this.browseAppsError = e.response?.data?.error || 'Fehler beim Laden der Apps aus App Store Connect';
      } finally {
        this.loadingBrowseApps = false;
      }
    },
    
    selectBrowseApp(app) {
      if (app.is_connected) return;
      this.selectedBrowseApp = app;
    },
    
    async connectSelectedApp() {
      if (!this.selectedBrowseApp || this.selectedBrowseApp.is_connected) return;
      
      this.connectingApp = true;
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=connect_app&project=${this.projectId}`, {
          app_store_id: this.selectedBrowseApp.id
        });
        
        if (res.data.success) {
          this.$toast?.success(`App "${this.selectedBrowseApp.name}" erfolgreich verbunden`);
          this.showAddAppModal = false;
          this.selectedBrowseApp = null;
          this.browseApps = [];
          this.loadDashboard();
        } else {
          throw new Error(res.data.error || 'Fehler beim Verbinden');
        }
      } catch (e) {
        console.error('Error connecting app:', e);
        this.$toast?.error(e.response?.data?.error || e.message || 'Fehler beim Verbinden der App');
      } finally {
        this.connectingApp = false;
      }
    },

    openApp(appId) {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${appId}`);
    },
    
    goToConfig() {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/config`);
    },
    
    async syncApp(appId) {
      try {
        this.$toast?.info('Synchronisierung gestartet...');
        const res = await this.$axios.get(`appstore_metadata.php?action=sync_pull&app_id=${appId}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Synchronisierung abgeschlossen');
          this.loadDashboard();
        }
      } catch (e) {
        console.error('Error syncing app:', e);
        this.$toast?.error('Fehler bei der Synchronisierung');
      }
    },
    
    confirmDeleteApp(app) {
      this.appToDelete = app;
      this.showDeleteModal = true;
    },
    
    async deleteApp() {
      if (!this.appToDelete) return;
      
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=app&app_id=${this.appToDelete.id}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('App erfolgreich gelöscht');
          this.showDeleteModal = false;
          this.appToDelete = null;
          this.loadDashboard();
        }
      } catch (e) {
        console.error('Error deleting app:', e);
        this.$toast?.error('Fehler beim Löschen der App');
      }
    },
    
    getStatusLabel(status) {
      const found = APP_STATUSES.find(s => s.value === status);
      return found?.label || status;
    },
    
    getActivityIcon(operation) {
      const icons = {
        'pull': 'cloud-download-outline',
        'push': 'cloud-upload-outline',
        'create_app': 'add-circle-outline',
        'create_version': 'git-branch-outline',
        'upload_screenshot': 'image-outline',
        'submit_review': 'paper-plane-outline'
      };
      return icons[operation] || 'time-outline';
    },
    
    getActivityTitle(operation) {
      const titles = {
        'pull': 'Daten synchronisiert',
        'push': 'Änderungen hochgeladen',
        'create_app': 'App erstellt',
        'create_version': 'Version erstellt',
        'upload_screenshot': 'Screenshot hochgeladen',
        'submit_review': 'Zur Prüfung eingereicht'
      };
      return titles[operation] || operation;
    },
    
    formatDate(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleString('de-DE');
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
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  flex-shrink: 0;
}

.stat-icon.connected {
  background: linear-gradient(135deg, var(--success-color) 0%, #047857 100%);
}

.stat-icon.disconnected {
  background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
}

.stat-content h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.stat-link {
  display: inline-block;
  margin-top: 8px;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 13px;
}

.stat-link:hover {
  text-decoration: underline;
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
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn ion-icon {
  font-size: 16px;
}

.action-btn.small {
  padding: 6px 12px;
  font-size: 12px;
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

.header-cell {
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
  flex: 0 0 140px;
  justify-content: center;
}

.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
  cursor: pointer;
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
  flex: 0 0 140px;
  justify-content: center;
  padding: 12px 16px;
}

/* App Info in Table */
.app-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.app-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}

.app-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.app-name {
  font-weight: 600;
  color: var(--text-primary);
}

.app-version {
  font-size: 12px;
  color: var(--text-muted);
}

.bundle-id {
  font-size: 12px;
  color: var(--text-secondary);
  background: var(--background);
  padding: 4px 8px;
  border-radius: 4px;
}

.meta-value {
  font-weight: 500;
  color: var(--text-primary);
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
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

.status-badge.rejected {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

/* Action Buttons in Table */
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

.edit-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.edit-btn:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.assign-btn {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.assign-btn:hover {
  background: rgba(37, 99, 235, 0.2);
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

/* Loading & Empty States */
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
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
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

/* Activity List */
.activity-list {
  padding: 16px 24px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--background);
}

.activity-icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
.activity-icon.failed { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }
.activity-icon.started { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }

.activity-info { flex: 1; }

.activity-title {
  font-weight: 500;
  color: var(--text-primary);
}

.activity-time {
  font-size: 12px;
  color: var(--text-muted);
}

.activity-status {
  padding: 4px 10px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.activity-status.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
.activity-status.failed { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }
.activity-status.started { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }

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
  width: 700px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-content.modal-sm {
  width: 450px;
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

.custom-modal-header.danger {
  background: rgba(220, 38, 38, 0.1);
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

/* Form Styles */
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

.form-help {
  margin-top: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.warning-text {
  color: var(--danger-color);
  font-size: 14px;
  margin-top: 12px;
}

/* Tab Navigation */
.tab-navigation {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 12px;
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
}

.tab-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.tab-btn.active {
  background: var(--primary-color);
  color: white;
}

.tab-content {
  min-height: 300px;
}

/* Browse Apps */
.browse-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.browse-header p {
  color: var(--text-secondary);
  margin: 0;
}

.browse-apps-list {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.browse-app-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.browse-app-item:last-child {
  border-bottom: none;
}

.browse-app-item:hover:not(.connected) {
  background: var(--background);
}

.browse-app-item.connected {
  opacity: 0.6;
  cursor: not-allowed;
}

.browse-app-item.selected {
  background: rgba(37, 99, 235, 0.1);
  border-left: 3px solid var(--primary-color);
}

.browse-app-icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: 10px;
  color: white;
  font-size: 22px;
  flex-shrink: 0;
}

.browse-app-info {
  flex: 1;
  min-width: 0;
}

.browse-app-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.browse-app-bundle {
  font-size: 12px;
  color: var(--text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.browse-app-status {
  flex-shrink: 0;
}

/* Info Box */
.info-box {
  display: flex;
  gap: 12px;
  padding: 16px;
  border-radius: var(--radius);
  margin-bottom: 16px;
}

.info-box ion-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.info-box.warning {
  background: rgba(217, 119, 6, 0.1);
  border: 1px solid rgba(217, 119, 6, 0.3);
}

.info-box.warning ion-icon {
  color: var(--warning-color);
}

.info-box.error {
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.3);
  color: var(--danger-color);
}

.info-box.info {
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.3);
}

.info-box.info ion-icon {
  color: var(--primary-color);
}

.info-box strong {
  display: block;
  margin-bottom: 4px;
}

.info-box p {
  margin: 0 0 12px 0;
  font-size: 14px;
}

/* Animations */
@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
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
