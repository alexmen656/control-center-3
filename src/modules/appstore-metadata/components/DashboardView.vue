<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="apps-outline" title="App Store Metadata Manager" bg="transparent"/>
      
      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <h2 class="page-subtitle">Verwalte deine App Store Metadaten</h2>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn" @click="goToConfig">
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
            <div class="stat-icon primary">
              <ion-icon name="apps-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_apps }}</div>
              <div class="stat-label">Verwaltete Apps</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon success">
              <ion-icon name="git-branch-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_versions }}</div>
              <div class="stat-label">App Versionen</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon warning">
              <ion-icon name="language-outline"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_locales }}</div>
              <div class="stat-label">Sprachen</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon" :class="stats.has_credentials ? 'success' : 'danger'">
              <ion-icon :name="stats.has_credentials ? 'cloud-done-outline' : 'cloud-offline-outline'"></ion-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.has_credentials ? 'Verbunden' : 'Nicht verbunden' }}</div>
              <div class="stat-label">API Status</div>
              <div class="stat-subtitle" v-if="!stats.has_credentials">
                <a href="#" @click.prevent="goToConfig">API einrichten</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-icon">
            <ion-icon name="hourglass-outline"></ion-icon>
          </div>
          <p>Lade Apps...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="apps.length === 0" class="empty-state">
          <div class="empty-icon">
            <ion-icon name="apps-outline"></ion-icon>
          </div>
          <h3>Keine Apps verbunden</h3>
          <p>Füge deine erste App hinzu, um die Metadaten zu verwalten.</p>
          <button class="action-btn primary" @click="openAddAppModal">
            <ion-icon name="add-outline"></ion-icon>
            Erste App hinzufügen
          </button>
        </div>

        <!-- Apps Grid -->
        <div v-else class="apps-grid">
          <div 
            v-for="app in apps" 
            :key="app.id" 
            class="app-card"
            @click="openApp(app.id)"
          >
            <div class="app-card-header">
              <div class="app-icon-large">
                <ion-icon name="logo-apple-appstore"></ion-icon>
              </div>
              <div class="app-status" :class="app.status">
                {{ getStatusLabel(app.status) }}
              </div>
            </div>
            
            <div class="app-card-body">
              <h3 class="app-name">{{ app.name }}</h3>
              <p class="app-bundle">{{ app.bundle_id }}</p>
              
              <div class="app-meta">
                <div class="meta-item">
                  <ion-icon name="git-branch-outline"></ion-icon>
                  <span>{{ app.version_count || 0 }} Versionen</span>
                </div>
                <div class="meta-item">
                  <ion-icon name="language-outline"></ion-icon>
                  <span>{{ app.locale_count || 0 }} Sprachen</span>
                </div>
              </div>
              
              <div class="app-version" v-if="app.latest_version">
                <span class="version-badge">v{{ app.latest_version }}</span>
              </div>
            </div>
            
            <div class="app-card-footer">
              <button class="card-action" @click.stop="openApp(app.id)">
                <ion-icon name="create-outline"></ion-icon>
                Bearbeiten
              </button>
              <button class="card-action" @click.stop="syncApp(app.id)">
                <ion-icon name="sync-outline"></ion-icon>
                Sync
              </button>
              <button class="card-action danger" @click.stop="confirmDeleteApp(app)">
                <ion-icon name="trash-outline"></ion-icon>
              </button>
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
      <div v-if="showAddAppModal" class="modal-overlay" @click.self="showAddAppModal = false">
        <div class="modal-content modal-lg">
          <div class="modal-header">
            <h3>App hinzufügen</h3>
            <button class="close-btn" @click="showAddAppModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
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
                <label>Primäre Sprache</label>
                <select v-model="newApp.primary_locale" class="form-select">
                  <option v-for="locale in locales" :key="locale.code" :value="locale.code">
                    {{ locale.name }} ({{ locale.code }})
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showAddAppModal = false">Abbrechen</button>
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
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-content modal-sm">
          <div class="modal-header danger">
            <h3>App löschen</h3>
            <button class="close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Bist du sicher, dass du <strong>{{ appToDelete?.name }}</strong> löschen möchtest?</p>
            <p class="warning-text">Diese Aktion kann nicht rückgängig gemacht werden. Alle Versionen und Lokalisierungen werden ebenfalls gelöscht.</p>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showDeleteModal = false">Abbrechen</button>
            <button class="action-btn danger" @click="deleteApp">
              <ion-icon name="trash-outline"></ion-icon>
              Endgültig löschen
            </button>
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
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
  font-weight: 500;
  margin: 0;
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
  align-items: center;
  gap: 12px;
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

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  gap: 16px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon ion-icon {
  font-size: 28px;
}

.stat-icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--primary-color); }
.stat-icon.success { background: rgba(5, 150, 105, 0.1); color: var(--success-color); }
.stat-icon.warning { background: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
.stat-icon.danger { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }
.stat-icon.info { background: rgba(8, 145, 178, 0.1); color: var(--info-color); }

.stat-info { flex: 1; }

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.stat-subtitle {
  margin-top: 8px;
  font-size: 13px;
}

.stat-subtitle a {
  color: var(--primary-color);
  text-decoration: none;
}

/* Loading & Empty States */
.loading-state,
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
}

.loading-icon,
.empty-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.loading-state p,
.empty-state h3,
.empty-state p {
  color: var(--text-primary);
  margin: 8px 0;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-icon ion-icon {
  animation: spin 2s linear infinite;
}

/* Apps Grid */
.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.app-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
}

.app-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-color);
}

.app-card-header {
  padding: 20px;
  background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.app-icon-large {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-icon-large ion-icon {
  font-size: 32px;
  color: white;
}

.app-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.app-status.live { background: var(--success-color); }
.app-status.draft { background: var(--secondary-color); }
.app-status.in_review { background: var(--info-color); }
.app-status.rejected { background: var(--danger-color); }

.app-card-body {
  padding: 20px;
}

.app-name {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 4px 0;
}

.app-bundle {
  font-size: 13px;
  color: var(--text-secondary);
  font-family: monospace;
  margin: 0 0 16px 0;
}

.app-meta {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
}

.meta-item ion-icon {
  font-size: 16px;
}

.version-badge {
  display: inline-block;
  padding: 4px 10px;
  background: var(--background);
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  color: var(--primary-color);
}

.app-card-footer {
  padding: 16px 20px;
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
  padding: 8px 12px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-secondary);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.card-action:hover {
  background: var(--surface);
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.card-action.danger:hover {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

/* Data Card */
.data-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
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

.activity-info {
  flex: 1;
}

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
  box-shadow: var(--shadow-lg);
}

.modal-content.modal-sm {
  max-width: 400px;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header.danger {
  background: rgba(220, 38, 38, 0.1);
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: var(--text-primary);
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 4px;
  display: flex;
}

.close-btn:hover {
  color: var(--text-primary);
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

/* Form Elements */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
}

.form-group .required {
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

.form-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: var(--text-muted);
}

.warning-text {
  color: var(--danger-color);
  font-size: 14px;
  margin-top: 12px;
}

/* Responsive */
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
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .apps-grid {
    grid-template-columns: 1fr;
  }
  
  .app-card-footer {
    flex-wrap: wrap;
  }
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
  background: linear-gradient(135deg, #667eea, #764ba2);
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

.status-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.connected {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-badge.selected {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
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

/* Small Variants */
.loading-state.small,
.empty-state.small {
  padding: 40px 20px;
}

.loading-state.small .loading-icon,
.empty-state.small ion-icon {
  font-size: 32px;
}

.action-btn.small {
  padding: 6px 12px;
  font-size: 12px;
}

/* Modal Large */
.modal-content.modal-lg {
  max-width: 700px;
}
</style>
