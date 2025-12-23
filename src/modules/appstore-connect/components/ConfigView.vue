<template>
  <ion-page>
    <ion-content class="modern-content">
      
      <div class="page-container">
        <!-- Info Card -->
        <div class="info-card">
          <div class="info-icon">
            <ion-icon name="information-circle-outline"></ion-icon>
          </div>
          <div class="info-content">
            <h3>App-Auswahl konfigurieren</h3>
            <p>Wähle die App aus, die im Dashboard angezeigt werden soll. Die Einstellung wird automatisch gespeichert.</p>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-icon">
            <ion-icon name="hourglass-outline"></ion-icon>
          </div>
          <p>Lade verfügbare Apps...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <ion-icon name="warning-outline"></ion-icon>
          <h4>Fehler beim Laden</h4>
          <p>{{ error }}</p>
          <button class="action-btn primary" @click="loadApps">
            <ion-icon name="refresh-outline"></ion-icon>
            Erneut versuchen
          </button>
        </div>

        <!-- App Selection -->
        <div v-else class="config-card">
          <div class="card-header">
            <h3>Verfügbare Apps</h3>
            <span class="app-count">{{ apps.length }} Apps gefunden</span>
          </div>

          <div class="apps-list">
            <div 
              v-for="app in apps" 
              :key="app.sku"
              class="app-item"
              :class="{ selected: selectedApp === app.sku }"
              @click="selectApp(app.sku)"
            >
              <div class="app-icon">
                <ion-icon name="logo-apple-appstore"></ion-icon>
              </div>
              <div class="app-info">
                <h4>{{ app.title }}</h4>
                <p class="app-sku">SKU: {{ app.sku }}</p>
              </div>
              <div class="app-selected" v-if="selectedApp === app.sku">
                <ion-icon name="checkmark-circle"></ion-icon>
              </div>
            </div>

            <div v-if="apps.length === 0" class="no-apps">
              <ion-icon name="apps-outline"></ion-icon>
              <p>Keine Apps verfügbar</p>
            </div>
          </div>

          <div class="card-footer">
            <button class="action-btn" @click="clearSelection">
              <ion-icon name="close-circle-outline"></ion-icon>
              Auswahl zurücksetzen
            </button>
            <button class="action-btn primary" @click="saveAndReturn">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
              Speichern & Zurück
            </button>
          </div>
        </div>

        <!-- Additional Info -->
        <div class="help-card">
          <h4>
            <ion-icon name="help-circle-outline"></ion-icon>
            Hilfe
          </h4>
          <ul>
            <li>Die ausgewählte App wird im Dashboard angezeigt</li>
            <li>Wenn keine App ausgewählt ist, werden alle Apps angezeigt</li>
            <li>Die Einstellung wird lokal im Browser gespeichert</li>
            <li>Apps werden automatisch aus deinem App Store Connect Account geladen</li>
          </ul>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>

export default {
  name: 'AppStoreConfig',
  data() {
    return {
      apps: [],
      selectedApp: '',
      loading: true,
      error: null
    };
  },
  
  mounted() {
    this.loadSavedConfig();
    this.loadApps();
  },
  
  methods: {
    async loadSavedConfig() {
      // Try to load from database first
      try {
        const res = await this.$axios.get('appstore_connections.php');
        if (res.data.connection) {
          this.selectedApp = res.data.connection.app_sku;
        }
      } catch (e) {
        console.warn('Could not load connection from database:', e);
        // Fallback to localStorage
        const saved = localStorage.getItem('appstore_selected_app');
        if (saved) {
          this.selectedApp = saved;
        }
      }
    },

    async loadApps() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load apps list with optimized API call
        const res = await this.$axios.get('appstore_downloads.php?get_apps=true');
        
        if (res.data.error) {
          this.error = res.data.error;
          return;
        }

        this.apps = res.data.apps || [];
        
        // Show cache info if available
        if (res.data.from_cache) {
          console.log('Apps loaded from cache, cached at:', res.data.cached_at);
        }
      } catch (e) {
        console.error('Error loading apps:', e);
        this.error = e.message || 'Fehler beim Laden der Apps';
      } finally {
        this.loading = false;
      }
    },

    selectApp(sku) {
      this.selectedApp = sku;
      this.saveConfig();
    },

    clearSelection() {
      this.selectedApp = '';
      this.saveConfig();
    },

    async saveConfig() {
      try {
        if (this.selectedApp) {
          // Save to database
          const selectedAppData = this.apps.find(app => app.sku === this.selectedApp);
          await this.$axios.post('appstore_connections.php', {
            app_sku: this.selectedApp,
            app_title: selectedAppData?.title || 'Unknown'
          });
          
          // Also save to localStorage as backup
          localStorage.setItem('appstore_selected_app', this.selectedApp);
        } else {
          // Remove from database
          await this.$axios.delete('appstore_connections.php');
          
          // Remove from localStorage
          localStorage.removeItem('appstore_selected_app');
        }
        
        // Show success message
        if (this.$toast) {
          this.$toast.success('Einstellung gespeichert');
        }
      } catch (e) {
        console.error('Error saving config:', e);
        if (this.$toast) {
          this.$toast.error('Fehler beim Speichern');
        }
      }
    },

    saveAndReturn() {
      this.saveConfig();
      setTimeout(() => {
        this.$router.back();
      }, 500);
    }
  }
};
</script>

<style scoped>
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
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
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
}

/* Info Card */
.info-card {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: rgba(37, 99, 235, 0.05);
  border: 1px solid rgba(37, 99, 235, 0.2);
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
}

.info-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
}

.info-icon ion-icon {
  font-size: 24px;
}

.info-content h3 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.info-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.loading-icon {
  display: inline-flex;
  font-size: 64px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 2s linear infinite;
}

.error-state ion-icon {
  font-size: 64px;
  color: var(--danger-color);
  margin-bottom: 16px;
}

.error-state h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 20px;
}

.error-state p {
  margin: 0 0 20px 0;
  color: var(--text-secondary);
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Config Card */
.config-card {
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

.app-count {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

/* Apps List */
.apps-list {
  max-height: 500px;
  overflow-y: auto;
}

.app-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-item:last-child {
  border-bottom: none;
}

.app-item:hover {
  background: var(--background);
}

.app-item.selected {
  background: rgba(37, 99, 235, 0.05);
  border-left: 3px solid var(--primary-color);
}

.app-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border-radius: var(--radius);
}

.app-icon ion-icon {
  font-size: 24px;
}

.app-info {
  flex: 1;
  min-width: 0;
}

.app-info h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.app-sku {
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
  font-family: monospace;
}

.app-selected {
  flex-shrink: 0;
  color: var(--success-color);
  font-size: 28px;
}

.no-apps {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
}

.no-apps ion-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-apps p {
  margin: 0;
  font-size: 16px;
}

/* Card Footer */
.card-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

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

.action-btn ion-icon {
  font-size: 18px;
}

/* Help Card */
.help-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.help-card h4 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 16px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.help-card h4 ion-icon {
  font-size: 20px;
  color: var(--info-color);
}

.help-card ul {
  margin: 0;
  padding-left: 24px;
  color: var(--text-secondary);
  line-height: 1.8;
}

.help-card li {
  font-size: 14px;
}

/* Scrollbar */
.apps-list::-webkit-scrollbar {
  width: 8px;
}

.apps-list::-webkit-scrollbar-track {
  background: var(--background);
}

.apps-list::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 4px;
}

.apps-list::-webkit-scrollbar-thumb:hover {
  background: var(--text-muted);
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .card-footer {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
