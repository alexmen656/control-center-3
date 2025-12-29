<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="language-outline" title="Lokalisierungen" bg="transparent"/>
      
      <div class="page-container">
        <!-- Back Button -->
        <button class="back-btn" @click="goBack">
          <ion-icon name="arrow-back-outline"></ion-icon>
          Zurück zur App
        </button>

        <!-- Info -->
        <div class="info-card">
          <div class="info-icon">
            <ion-icon name="globe-outline"></ion-icon>
          </div>
          <div class="info-content">
            <h3>Mehrsprachige App-Metadaten</h3>
            <p>Verwalte hier die App-spezifischen Texte für verschiedene Sprachen. Diese Texte gelten für alle Versionen deiner App.</p>
          </div>
        </div>

        <!-- Localizations Table -->
        <div class="data-card">
          <div class="card-header">
            <h3>Verfügbare Sprachen</h3>
            <button class="action-btn primary" @click="showAddModal = true">
              <ion-icon name="add-outline"></ion-icon>
              Sprache hinzufügen
            </button>
          </div>

          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Sprache</th>
                  <th>App Name</th>
                  <th>Untertitel</th>
                  <th>Datenschutz-URL</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="loc in localizations" :key="loc.id">
                  <td>
                    <div class="locale-cell">
                      <span class="flag">{{ getLocaleFlag(loc.locale) }}</span>
                      <div class="locale-info">
                        <span class="locale-name">{{ loc.locale_name || loc.locale }}</span>
                        <span class="locale-code">{{ loc.locale }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="{ 'text-muted': !loc.name }">{{ loc.name || 'Nicht gesetzt' }}</span>
                  </td>
                  <td>
                    <span :class="{ 'text-muted': !loc.subtitle }">{{ loc.subtitle || 'Nicht gesetzt' }}</span>
                  </td>
                  <td>
                    <a v-if="loc.privacy_policy_url" :href="loc.privacy_policy_url" target="_blank" class="url-link">
                      <ion-icon name="link-outline"></ion-icon>
                      Link
                    </a>
                    <span v-else class="text-muted">Nicht gesetzt</span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="icon-btn" @click="editLocale(loc)" title="Bearbeiten">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="icon-btn danger" @click="confirmDelete(loc)" title="Löschen">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="localizations.length === 0">
                  <td colspan="5" class="empty-cell">
                    <div class="empty-content">
                      <ion-icon name="language-outline"></ion-icon>
                      <p>Noch keine Lokalisierungen vorhanden</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content modal-lg">
          <div class="modal-header">
            <h3>{{ showEditModal ? 'Lokalisierung bearbeiten' : 'Sprache hinzufügen' }}</h3>
            <button class="close-btn" @click="closeModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="form-group" v-if="!showEditModal">
              <label>Sprache <span class="required">*</span></label>
              <select v-model="form.locale" class="form-select">
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
              <input 
                v-model="form.name" 
                type="text" 
                class="form-input"
                placeholder="Lokalisierter App Name"
              />
              <span class="form-hint">Leer lassen, um den Standardnamen zu verwenden</span>
            </div>

            <div class="form-group">
              <label>Untertitel</label>
              <input 
                v-model="form.subtitle" 
                type="text" 
                class="form-input"
                placeholder="Kurze Beschreibung (max. 30 Zeichen)"
                maxlength="30"
              />
              <div class="char-count">{{ (form.subtitle || '').length }}/30</div>
            </div>

            <div class="form-group">
              <label>Datenschutzrichtlinie URL</label>
              <input 
                v-model="form.privacy_policy_url" 
                type="url" 
                class="form-input"
                placeholder="https://example.com/privacy"
              />
            </div>

            <div class="form-group">
              <label>Datenschutzrichtlinie Text</label>
              <textarea 
                v-model="form.privacy_policy_text" 
                class="form-textarea"
                rows="4"
                placeholder="Optionaler Text zur Datenschutzrichtlinie..."
              ></textarea>
            </div>

            <div class="form-group">
              <label>Privacy Choices URL</label>
              <input 
                v-model="form.privacy_choices_url" 
                type="url" 
                class="form-input"
                placeholder="https://example.com/privacy-choices"
              />
              <span class="form-hint">Für Apps mit ATT (App Tracking Transparency)</span>
            </div>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="closeModal">Abbrechen</button>
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

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-content modal-sm">
          <div class="modal-header danger">
            <h3>Sprache entfernen</h3>
            <button class="close-btn" @click="showDeleteModal = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="modal-body">
            <p>Bist du sicher, dass du die Lokalisierung für <strong>{{ localeToDelete?.locale }}</strong> entfernen möchtest?</p>
          </div>
          
          <div class="modal-footer">
            <button class="action-btn" @click="showDeleteModal = false">Abbrechen</button>
            <button class="action-btn danger" @click="deleteLocalization">
              <ion-icon name="trash-outline"></ion-icon>
              Entfernen
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
  name: 'LocalizationEditor',
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
/* Reuse styles from other components */
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
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

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
  color: var(--text-primary);
}

.info-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Data Card & Table */
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
  color: var(--text-primary);
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 16px 20px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

.data-table th {
  background: var(--background);
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  color: var(--text-primary);
}

.data-table tr:last-child td {
  border-bottom: none;
}

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
  font-family: monospace;
}

.text-muted {
  color: var(--text-muted);
  font-style: italic;
}

.url-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--primary-color);
  text-decoration: none;
}

.url-link:hover {
  text-decoration: underline;
}

.action-btns {
  display: flex;
  gap: 8px;
}

.icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
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

.empty-cell {
  text-align: center;
  padding: 40px !important;
}

.empty-content {
  color: var(--text-muted);
}

.empty-content ion-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

/* Action Button */
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
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  transition: all 0.2s ease;
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

.modal-content.modal-lg {
  max-width: 600px;
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
  font-family: inherit;
}

.form-textarea {
  resize: vertical;
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

.form-hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-muted);
}
</style>
