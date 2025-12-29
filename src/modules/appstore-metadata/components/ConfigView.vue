<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="key-outline" title="API Einstellungen" bg="transparent"/>
      
      <div class="page-container">
        <!-- Back Button -->
        <button class="back-btn" @click="$router.push(`/project/${this.projectId}/appstore-metadata`)">
          <ion-icon name="arrow-back-outline"></ion-icon>
          Zurück zum Dashboard
        </button>

        <!-- Info Card -->
        <div class="info-card">
          <div class="info-icon">
            <ion-icon name="information-circle-outline"></ion-icon>
          </div>
          <div class="info-content">
            <h3>App Store Connect API Einstellungen</h3>
            <p>Verbinde dein App Store Connect Konto, um Apps zu synchronisieren und Metadaten direkt zu aktualisieren.</p>
          </div>
        </div>

        <!-- Connection Status -->
        <div class="status-card" :class="hasCredentials ? 'connected' : 'disconnected'">
          <div class="status-icon">
            <ion-icon :name="hasCredentials ? 'checkmark-circle' : 'close-circle'"></ion-icon>
          </div>
          <div class="status-info">
            <h4>{{ hasCredentials ? 'API verbunden' : 'API nicht verbunden' }}</h4>
            <p v-if="hasCredentials">Letzte Nutzung: {{ formatDate(credentials.last_used_at) || 'Noch nicht verwendet' }}</p>
            <p v-else>Füge deine API Zugangsdaten hinzu, um loszulegen.</p>
          </div>
        </div>

        <!-- Credentials Form -->
        <div class="config-card">
          <div class="card-header">
            <h3>API Zugangsdaten</h3>
            <a href="https://appstoreconnect.apple.com/access/api" target="_blank" class="help-link">
              <ion-icon name="help-circle-outline"></ion-icon>
              API-Schlüssel erstellen
            </a>
          </div>

          <div class="card-body">
            <div class="form-group">
              <label>Issuer ID <span class="required">*</span></label>
              <input 
                v-model="form.issuer_id" 
                type="text" 
                placeholder="z.B. 69a6de7e-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                class="form-input"
              />
              <span class="form-hint">Die Issuer ID findest du unter "Users and Access" → "Keys"</span>
            </div>

            <div class="form-group">
              <label>Key ID <span class="required">*</span></label>
              <input 
                v-model="form.key_id" 
                type="text" 
                placeholder="z.B. ABC123DEF4"
                class="form-input"
              />
              <span class="form-hint">Die Key ID des API-Schlüssels (10 Zeichen)</span>
            </div>

            <div class="form-group">
              <label>Private Key (.p8) <span class="required">*</span></label>
              <div class="file-upload-area" @click="$refs.fileInput.click()" @dragover.prevent @drop.prevent="handleFileDrop">
                <input 
                  ref="fileInput" 
                  type="file" 
                  accept=".p8" 
                  @change="handleFileSelect"
                  style="display: none"
                />
                <div v-if="!form.private_key" class="upload-placeholder">
                  <ion-icon name="cloud-upload-outline"></ion-icon>
                  <p>Klicke oder ziehe deine .p8 Datei hierher</p>
                </div>
                <div v-else class="upload-success">
                  <ion-icon name="checkmark-circle"></ion-icon>
                  <p>Private Key geladen</p>
                  <button class="clear-btn" @click.stop="clearPrivateKey">
                    <ion-icon name="close-outline"></ion-icon>
                  </button>
                </div>
              </div>
              <span class="form-hint">Lade die .p8 Datei hoch, die du beim Erstellen des API-Keys erhalten hast</span>
            </div>

            <div class="form-group">
              <label>Vendor Number (optional)</label>
              <input 
                v-model="form.vendor_number" 
                type="text" 
                placeholder="z.B. 12345678"
                class="form-input"
              />
              <span class="form-hint">Für den Zugriff auf Verkaufsberichte</span>
            </div>
          </div>

          <div class="card-footer">
            <button class="action-btn danger" @click="confirmDeleteCredentials" v-if="hasCredentials">
              <ion-icon name="trash-outline"></ion-icon>
              Zugangsdaten löschen
            </button>
            <div class="spacer"></div>
            <button class="action-btn" @click="testConnection" :disabled="!canSave || testing">
              <ion-icon :name="testing ? 'hourglass-outline' : 'flash-outline'"></ion-icon>
              {{ testing ? 'Teste...' : 'Verbindung testen' }}
            </button>
            <button class="action-btn primary" @click="saveCredentials" :disabled="!canSave || saving">
              <ion-icon :name="saving ? 'hourglass-outline' : 'save-outline'"></ion-icon>
              {{ saving ? 'Speichert...' : 'Speichern' }}
            </button>
          </div>
        </div>

        <!-- Help Section -->
        <div class="help-card">
          <h4>
            <ion-icon name="help-circle-outline"></ion-icon>
            So erstellst du einen API-Schlüssel
          </h4>
          <ol>
            <li>Gehe zu <a href="https://appstoreconnect.apple.com/access/api" target="_blank">App Store Connect → Users and Access → Keys</a></li>
            <li>Klicke auf das + Symbol, um einen neuen API-Schlüssel zu erstellen</li>
            <li>Gib dem Schlüssel einen Namen (z.B. "Control Center")</li>
            <li>Wähle "Admin" oder "App Manager" als Zugriffsrecht</li>
            <li>Lade die .p8 Datei herunter (nur einmal möglich!)</li>
            <li>Kopiere die Issuer ID und Key ID hierher</li>
          </ol>
          
          <div class="security-note">
            <ion-icon name="shield-checkmark-outline"></ion-icon>
            <p>Deine Zugangsdaten werden sicher verschlüsselt gespeichert und niemals an Dritte weitergegeben.</p>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-content modal-sm">
          <div class="modal-header danger">
            <h3>Zugangsdaten löschen</h3>
            <button class="close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Bist du sicher, dass du die API Zugangsdaten löschen möchtest?</p>
            <p class="warning-text">Die Verbindung zu App Store Connect wird getrennt.</p>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showDeleteModal = false">Abbrechen</button>
            <button class="action-btn danger" @click="deleteCredentials">
              <ion-icon name="trash-outline"></ion-icon>
              Löschen
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import SiteTitle from "@/components/SiteTitle.vue";

export default {
  name: 'MetadataConfig',
  components: {
    SiteTitle
  },
  data() {
    return {
      hasCredentials: false,
      credentials: null,
      form: {
        issuer_id: '',
        key_id: '',
        private_key: '',
        vendor_number: ''
      },
      saving: false,
      testing: false,
      showDeleteModal: false
    };
  },
  
  computed: {
    projectId() {
      return this.$route.params.project;
    },
    canSave() {
      return this.form.issuer_id && this.form.key_id && this.form.private_key;
    }
  },
  
  mounted() {
    this.loadCredentials();
  },
  
  methods: {
    async loadCredentials() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=credentials&project=${this.projectId}`);
        if (res.data.success) {
          this.hasCredentials = res.data.has_credentials;
          this.credentials = res.data.credentials;
          
          if (this.credentials) {
            this.form.issuer_id = this.credentials.issuer_id || '';
            this.form.key_id = this.credentials.key_id || '';
            this.form.vendor_number = this.credentials.vendor_number || '';
            // Don't load private key for security
          }
        }
      } catch (e) {
        console.error('Error loading credentials:', e);
      }
    },
    
    handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        this.readPrivateKey(file);
      }
    },
    
    handleFileDrop(event) {
      const file = event.dataTransfer.files[0];
      if (file && file.name.endsWith('.p8')) {
        this.readPrivateKey(file);
      }
    },
    
    readPrivateKey(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.form.private_key = e.target.result;
      };
      reader.readAsText(file);
    },
    
    clearPrivateKey() {
      this.form.private_key = '';
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = '';
      }
    },
    
    async saveCredentials() {
      if (!this.canSave) return;
      
      this.saving = true;
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=credentials&project=${this.projectId}`, this.form);
        if (res.data.success) {
          this.$toast?.success('Zugangsdaten gespeichert');
          this.hasCredentials = true;
          this.loadCredentials();
        } else {
          throw new Error(res.data.error || 'Fehler beim Speichern');
        }
      } catch (e) {
        console.error('Error saving credentials:', e);
        this.$toast?.error(e.message || 'Fehler beim Speichern');
      } finally {
        this.saving = false;
      }
    },
    
    async testConnection() {
      if (!this.canSave) return;
      
      this.testing = true;
      try {
        // For now, just validate the format
        // In production, this would make a test API call
        if (this.form.issuer_id.length < 10) {
          throw new Error('Issuer ID scheint ungültig zu sein');
        }
        if (this.form.key_id.length !== 10) {
          throw new Error('Key ID muss 10 Zeichen lang sein');
        }
        if (!this.form.private_key.includes('BEGIN PRIVATE KEY')) {
          throw new Error('Private Key Format ungültig');
        }
        
        this.$toast?.success('Zugangsdaten sehen gültig aus!');
      } catch (e) {
        this.$toast?.error(e.message || 'Verbindungstest fehlgeschlagen');
      } finally {
        this.testing = false;
      }
    },
    
    confirmDeleteCredentials() {
      this.showDeleteModal = true;
    },
    
    async deleteCredentials() {
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=credentials&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Zugangsdaten gelöscht');
          this.hasCredentials = false;
          this.credentials = null;
          this.form = {
            issuer_id: '',
            key_id: '',
            private_key: '',
            vendor_number: ''
          };
          this.showDeleteModal = false;
        }
      } catch (e) {
        console.error('Error deleting credentials:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },
    
    formatDate(dateStr) {
      if (!dateStr) return null;
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
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
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
  max-width: 800px;
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
  transition: all 0.2s ease;
}

.back-btn:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
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
  font-size: 32px;
  color: var(--primary-color);
}

.info-content h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  color: var(--text-primary);
}

.info-content p {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

/* Status Card */
.status-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
}

.status-card.connected {
  background: rgba(5, 150, 105, 0.1);
  border: 1px solid rgba(5, 150, 105, 0.3);
}

.status-card.disconnected {
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.status-icon {
  font-size: 40px;
}

.status-card.connected .status-icon { color: var(--success-color); }
.status-card.disconnected .status-icon { color: var(--danger-color); }

.status-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  color: var(--text-primary);
}

.status-info p {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

/* Config Card */
.config-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  margin-bottom: 24px;
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
  font-size: 18px;
  color: var(--text-primary);
}

.help-link {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 14px;
}

.help-link:hover {
  text-decoration: underline;
}

.card-body {
  padding: 24px;
}

.card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 12px;
  align-items: center;
}

.spacer {
  flex: 1;
}

/* Form Elements */
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

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  font-family: monospace;
  transition: all 0.2s ease;
}

.form-input:focus {
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

/* File Upload */
.file-upload-area {
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  padding: 32px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-upload-area:hover {
  border-color: var(--primary-color);
  background: rgba(37, 99, 235, 0.05);
}

.upload-placeholder ion-icon {
  font-size: 48px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.upload-placeholder p {
  margin: 0;
  color: var(--text-secondary);
}

.upload-success {
  position: relative;
}

.upload-success ion-icon {
  font-size: 48px;
  color: var(--success-color);
  margin-bottom: 12px;
}

.upload-success p {
  margin: 0;
  color: var(--success-color);
  font-weight: 500;
}

.clear-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: var(--danger-color);
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
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
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Help Card */
.help-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  padding: 24px;
}

.help-card h4 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 16px 0;
  color: var(--text-primary);
}

.help-card ol {
  margin: 0 0 20px 0;
  padding-left: 20px;
  color: var(--text-secondary);
}

.help-card li {
  margin-bottom: 8px;
  line-height: 1.5;
}

.help-card a {
  color: var(--primary-color);
}

.security-note {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: rgba(5, 150, 105, 0.1);
  border-radius: var(--radius);
}

.security-note ion-icon {
  font-size: 24px;
  color: var(--success-color);
  flex-shrink: 0;
}

.security-note p {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
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
  max-width: 400px;
  overflow: hidden;
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
}

.modal-body {
  padding: 24px;
}

.modal-body p {
  margin: 0;
  color: var(--text-primary);
}

.warning-text {
  color: var(--danger-color);
  font-size: 14px;
  margin-top: 12px !important;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

@media (max-width: 768px) {
  .card-footer {
    flex-direction: column;
  }
  
  .spacer {
    display: none;
  }
  
  .card-footer .action-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
