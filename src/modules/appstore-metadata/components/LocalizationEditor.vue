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
              <h1>Lokalisierungen</h1>
              <p>Mehrsprachige App-Metadaten verwalten</p>
            </div>
          </div>
          <button class="action-btn primary" @click="showAddModal = true">
            <ion-icon name="add-outline"></ion-icon>
            Sprache hinzufügen
          </button>
        </div>

        <!-- Localizations Table -->
        <div class="data-card">
          <div class="card-header">
            <h3>Verfügbare Sprachen</h3>
            <span class="badge">{{ localizations.length }} Sprachen</span>
          </div>

          <div class="modern-table">
            <!-- Table Header -->
            <div class="table-header">
              <div class="table-cell flex-2">Sprache</div>
              <div class="table-cell flex-2">App Name</div>
              <div class="table-cell flex-2">Untertitel</div>
              <div class="table-cell flex-1">Datenschutz</div>
              <div class="table-cell actions-cell">Aktionen</div>
            </div>
            
            <!-- Table Body -->
            <div class="table-body">
              <div class="table-row" v-for="loc in localizations" :key="loc.id">
                <div class="table-cell flex-2">
                  <div class="locale-cell">
                    <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
                    <div class="locale-info">
                      <span class="locale-name">{{ loc.locale_name || loc.locale }}</span>
                      <span class="locale-code">{{ loc.locale }}</span>
                    </div>
                  </div>
                </div>
                <div class="table-cell flex-2">
                  <span :class="{ 'text-muted': !loc.name }">{{ loc.name || 'Nicht gesetzt' }}</span>
                </div>
                <div class="table-cell flex-2">
                  <span :class="{ 'text-muted': !loc.subtitle }">{{ loc.subtitle || 'Nicht gesetzt' }}</span>
                </div>
                <div class="table-cell flex-1">
                  <a v-if="loc.privacy_policy_url" :href="loc.privacy_policy_url" target="_blank" class="url-link">
                    <ion-icon name="link-outline"></ion-icon>
                    Link
                  </a>
                  <span v-else class="text-muted">—</span>
                </div>
                <div class="table-cell actions-cell">
                  <div class="row-actions">
                    <button class="icon-btn" @click="editLocale(loc)" title="Bearbeiten">
                      <ion-icon name="create-outline"></ion-icon>
                    </button>
                    <button class="icon-btn danger" @click="confirmDelete(loc)" title="Löschen">
                      <ion-icon name="trash-outline"></ion-icon>
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-if="localizations.length === 0" class="empty-state-row">
                <div class="empty-icon">
                  <ion-icon name="language-outline"></ion-icon>
                </div>
                <h4>Noch keine Lokalisierungen</h4>
                <p>Füge deine erste Sprache hinzu, um loszulegen.</p>
                <button class="action-btn primary" @click="showAddModal = true">
                  <ion-icon name="add-outline"></ion-icon>
                  Sprache hinzufügen
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="custom-modal-overlay" @click.self="closeModal">
        <div class="custom-modal-content modal-lg">
          <div class="custom-modal-header">
            <h3>{{ showEditModal ? 'Lokalisierung bearbeiten' : 'Sprache hinzufügen' }}</h3>
            <button class="modal-close-btn" @click="closeModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div class="form-group" v-if="!showEditModal">
              <label class="form-label">Sprache <span class="required">*</span></label>
              <select v-model="form.locale" class="modern-select">
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
              <label class="form-label">App Name</label>
              <input 
                v-model="form.name" 
                type="text" 
                class="modern-input"
                placeholder="Lokalisierter App Name"
              />
              <span class="form-hint">Leer lassen, um den Standardnamen zu verwenden</span>
            </div>

            <div class="form-group">
              <label class="form-label">Untertitel</label>
              <input 
                v-model="form.subtitle" 
                type="text" 
                class="modern-input"
                placeholder="Kurze Beschreibung (max. 30 Zeichen)"
                maxlength="30"
              />
              <div class="char-count">{{ (form.subtitle || '').length }}/30</div>
            </div>

            <div class="form-group">
              <label class="form-label">Datenschutzrichtlinie URL</label>
              <input 
                v-model="form.privacy_policy_url" 
                type="url" 
                class="modern-input"
                placeholder="https://example.com/privacy"
              />
            </div>

            <div class="form-group">
              <label class="form-label">Datenschutzrichtlinie Text</label>
              <textarea 
                v-model="form.privacy_policy_text" 
                class="modern-input"
                rows="4"
                placeholder="Optionaler Text zur Datenschutzrichtlinie..."
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Privacy Choices URL</label>
              <input 
                v-model="form.privacy_choices_url" 
                type="url" 
                class="modern-input"
                placeholder="https://example.com/privacy-choices"
              />
              <span class="form-hint">Für Apps mit ATT (App Tracking Transparency)</span>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="closeModal">Abbrechen</button>
              <button 
                class="action-btn primary" 
                @click="saveLocalization" 
                :disabled="!showEditModal && !form.locale"
              >
                <ion-icon name="save-outline"></ion-icon>
                Speichern
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="custom-modal-overlay" @click.self="showDeleteModal = false">
        <div class="custom-modal-content">
          <div class="custom-modal-header danger">
            <h3>Sprache entfernen</h3>
            <button class="modal-close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <p>Bist du sicher, dass du die Lokalisierung für <strong>{{ localeToDelete?.locale }}</strong> entfernen möchtest?</p>
            
            <div class="form-actions">
              <button class="action-btn secondary" @click="showDeleteModal = false">Abbrechen</button>
              <button class="action-btn danger" @click="deleteLocalization">
                <ion-icon name="trash-outline"></ion-icon>
                Entfernen
              </button>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { getLocaleFlag } from '../config';

export default {
  name: 'LocalizationEditor',
  components: {},
  props: {
    appId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      localizations: [],
      allLocales: [],
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      localeToDelete: null,
      form: {
        locale: '',
        name: '',
        subtitle: '',
        privacy_policy_url: '',
        privacy_policy_text: '',
        privacy_choices_url: ''
      }
    };
  },
  
  computed: {
    projectId() {
      return this.$route.params.project;
    },
    availableLocales() {
      const usedLocales = this.localizations.map(l => l.locale);
      return this.allLocales.filter(l => !usedLocales.includes(l.code));
    }
  },
  
  mounted() {
    this.loadLocalizations();
    this.loadLocales();
  },
  
  methods: {
    async loadLocalizations() {
      try {
        const res = await this.$axios.get(`appstore_metadata.php?action=app_localizations&app_id=${this.appId}&project=${this.projectId}`);
        if (res.data.success) {
          this.localizations = res.data.localizations || [];
        }
      } catch (e) {
        console.error('Error loading localizations:', e);
        this.$toast?.error('Fehler beim Laden');
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
    
    editLocale(loc) {
      this.form = { ...loc };
      this.showEditModal = true;
    },
    
    async saveLocalization() {
      if (!this.showEditModal && !this.form.locale) return;
      
      try {
        const res = await this.$axios.post(`appstore_metadata.php?action=app_localizations&app_id=${this.appId}&project=${this.projectId}`, this.form);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gespeichert');
          this.closeModal();
          this.loadLocalizations();
        }
      } catch (e) {
        console.error('Error saving localization:', e);
        this.$toast?.error('Fehler beim Speichern');
      }
    },
    
    confirmDelete(loc) {
      this.localeToDelete = loc;
      this.showDeleteModal = true;
    },
    
    async deleteLocalization() {
      if (!this.localeToDelete) return;
      
      try {
        const res = await this.$axios.delete(`appstore_metadata.php?action=app_localizations&app_id=${this.appId}&locale=${this.localeToDelete.locale}&project=${this.projectId}`);
        if (res.data.success) {
          this.$toast?.success('Lokalisierung gelöscht');
          this.showDeleteModal = false;
          this.localeToDelete = null;
          this.loadLocalizations();
        }
      } catch (e) {
        console.error('Error deleting localization:', e);
        this.$toast?.error('Fehler beim Löschen');
      }
    },
    
    closeModal() {
      this.showAddModal = false;
      this.showEditModal = false;
      this.form = {
        locale: '',
        name: '',
        subtitle: '',
        privacy_policy_url: '',
        privacy_policy_text: '',
        privacy_choices_url: ''
      };
    },
    
    goBack() {
      this.$router.push(`/project/${this.projectId}/appstore-metadata/app/${this.appId}`);
    },
    
    getLocaleFlag(locale) {
      return getLocaleFlag(locale);
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
  max-width: 1100px;
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

/* Modern Table */
.modern-table {
  width: 100%;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 1px solid var(--border);
  padding: 12px 24px;
}

.table-header .table-cell {
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
}

.table-body {
  min-height: 100px;
}

.table-row {
  display: flex;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  transition: background 0.15s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-row:last-child {
  border-bottom: none;
}

.table-cell {
  padding: 0 8px;
  color: var(--text-primary);
  font-size: 14px;
}

.table-cell:first-child {
  padding-left: 0;
}

.table-cell:last-child {
  padding-right: 0;
}

.flex-1 { flex: 1; }
.flex-2 { flex: 2; }

.actions-cell {
  flex: 0 0 100px;
  display: flex;
  justify-content: flex-end;
}

/* Locale Cell */
.locale-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.locale-cell .flag {
  font-size: 24px;
}

.locale-info {
  display: flex;
  flex-direction: column;
}

.locale-name {
  font-weight: 500;
  color: var(--text-primary);
}

.locale-code {
  font-size: 12px;
  color: var(--text-muted);
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Monaco, Consolas, monospace;
}

.text-muted {
  color: var(--text-muted);
}

.url-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--primary-color);
  text-decoration: none;
  font-size: 13px;
}

.url-link:hover {
  text-decoration: underline;
}

/* Row Actions */
.row-actions {
  display: flex;
  gap: 8px;
}

/* Icon Button */
.icon-btn {
  width: 32px;
  height: 32px;
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
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.icon-btn.danger:hover {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

/* Empty State */
.empty-state-row {
  padding: 60px 24px;
  text-align: center;
}

.empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.empty-icon ion-icon {
  font-size: 32px;
  color: white;
}

.empty-state-row h4 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.empty-state-row p {
  margin: 0 0 20px 0;
  color: var(--text-secondary);
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
  max-width: 480px;
  max-height: 90vh;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.custom-modal-content.modal-lg {
  max-width: 560px;
}

.custom-modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
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
  overflow-y: auto;
  flex: 1;
}

.custom-modal-body p {
  margin: 0 0 16px 0;
  color: var(--text-primary);
}

/* Form Elements */
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
  font-family: inherit;
  transition: all 0.2s ease;
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

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .table-header {
    display: none;
  }

  .table-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .table-cell {
    padding: 0;
    width: 100%;
  }

  .actions-cell {
    flex: none;
    width: 100%;
    justify-content: flex-start;
    padding-top: 8px;
    border-top: 1px solid var(--border);
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
