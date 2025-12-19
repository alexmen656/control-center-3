<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="mail-outline" title="Newsletter" bg="transparent"/>
      
      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <h2 class="page-title">Newsletter versenden</h2>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn" @click="goToConfig">
              <ion-icon name="settings-outline"></ion-icon>
              Einstellungen
            </button>
            <button class="action-btn" @click="viewHistory">
              <ion-icon name="time-outline"></ion-icon>
              Verlauf
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
              <ion-icon name="mail-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.totalSent || 0 }}</h3>
              <p>Gesendete Newsletter</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon" style="background: rgba(5, 150, 105, 0.1); color: #059669;">
              <ion-icon name="people-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.totalSubscribers || 0 }}</h3>
              <p>Abonnenten</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
              <ion-icon name="eye-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.openRate || 0 }}%</h3>
              <p>Öffnungsrate</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
              <ion-icon name="hand-left-outline"></ion-icon>
            </div>
            <div class="stat-content">
              <h3>{{ stats.clickRate || 0 }}%</h3>
              <p>Klickrate</p>
            </div>
          </div>
        </div>

        <!-- Newsletter Form Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Newsletter erstellen</h3>
              <p class="entry-count">Sende personalisierte Newsletter an deine Abonnenten</p>
            </div>
          </div>

          <div class="form-container">
            <form @submit.prevent="sendNewsletter">
              <!-- Subject Field -->
              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="text-outline"></ion-icon>
                  Betreff
                </label>
                <input 
                  v-model="subject" 
                  type="text" 
                  class="modern-input" 
                  placeholder="Newsletter Betreff eingeben..."
                  required
                />
              </div>

              <!-- Email Content Field -->
              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="document-text-outline"></ion-icon>
                  Nachricht
                </label>
                <textarea 
                  v-model="email" 
                  class="modern-textarea" 
                  rows="15"
                  placeholder="Newsletter Inhalt (HTML unterstützt)..."
                  required
                ></textarea>
                <div class="field-hint">
                  <ion-icon name="information-circle-outline"></ion-icon>
                  HTML wird unterstützt. Nutze <code>&lt;b&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;a&gt;</code> für Formatierung.
                </div>
              </div>

              <!-- Recipients Field -->
              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="people-outline"></ion-icon>
                  Empfänger
                </label>
                <textarea 
                  v-model="recipients" 
                  class="modern-textarea" 
                  rows="3"
                  placeholder="E-Mail-Adressen (eine pro Zeile oder mit Komma getrennt)"
                  required
                ></textarea>
                <div class="field-hint">
                  <ion-icon name="information-circle-outline"></ion-icon>
                  {{ recipientCount }} Empfänger gefunden
                </div>
              </div>

              <!-- Send Options -->
              <div class="form-group">
                <label class="checkbox-container">
                  <input 
                    v-model="sendTestEmail" 
                    type="checkbox" 
                    class="modern-checkbox"
                  />
                  <span>Test-E-Mail an mich selbst senden</span>
                </label>
              </div>

              <!-- Action Buttons -->
              <div class="form-actions">
                <button 
                  type="button" 
                  class="action-btn secondary" 
                  @click="clearForm"
                  :disabled="sending"
                >
                  <ion-icon name="close-outline"></ion-icon>
                  Zurücksetzen
                </button>
                <button 
                  type="button" 
                  class="action-btn" 
                  @click="previewNewsletter"
                  :disabled="sending"
                >
                  <ion-icon name="eye-outline"></ion-icon>
                  Vorschau
                </button>
                <button 
                  type="submit" 
                  class="action-btn primary" 
                  :disabled="sending || !canSend"
                >
                  <ion-icon v-if="!sending" name="send-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ sending ? 'Wird gesendet...' : 'Newsletter senden' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Recent Newsletters -->
        <div v-if="recentNewsletters.length > 0" class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Letzte Newsletter</h3>
              <p class="entry-count">{{ recentNewsletters.length }} kürzlich gesendet</p>
            </div>
          </div>

          <div class="table-wrapper">
            <div class="modern-table">
              <div class="table-header">
                <div class="header-cell"><span class="header-text">Betreff</span></div>
                <div class="header-cell"><span class="header-text">Empfänger</span></div>
                <div class="header-cell"><span class="header-text">Status</span></div>
                <div class="header-cell"><span class="header-text">Gesendet am</span></div>
                <div class="header-cell actions-header"><span class="header-text">Aktionen</span></div>
              </div>
              
              <div class="table-body">
                <div v-for="newsletter in recentNewsletters" :key="newsletter.id" class="table-row">
                  <div class="table-cell">
                    <div class="cell-content">{{ newsletter.subject }}</div>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content">{{ newsletter.recipients }}</div>
                  </div>
                  <div class="table-cell">
                    <span :class="['status-badge', newsletter.status]">
                      {{ formatStatus(newsletter.status) }}
                    </span>
                  </div>
                  <div class="table-cell">
                    <div class="cell-content">{{ formatDate(newsletter.sent_at) }}</div>
                  </div>
                  <div class="table-cell actions-cell">
                    <div class="action-buttons">
                      <button class="icon-btn view-btn" @click="viewNewsletter(newsletter.id)" title="Ansehen">
                        <ion-icon name="eye-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteNewsletter(newsletter.id)" title="Löschen">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview Modal -->
      <div v-if="showPreview" class="custom-modal-overlay" @click="showPreview = false">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>
              <ion-icon name="eye-outline"></ion-icon>
              Newsletter Vorschau
            </h3>
            <button class="modal-close-btn" @click="showPreview = false">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div class="preview-container">
              <div class="preview-meta">
                <div class="preview-field">
                  <strong>Betreff:</strong>
                  <span>{{ subject }}</span>
                </div>
                <div class="preview-field">
                  <strong>Empfänger:</strong>
                  <span>{{ recipientCount }} Empfänger</span>
                </div>
              </div>
              
              <div class="preview-divider"></div>
              
              <div class="preview-content" v-html="email"></div>
            </div>
          </div>
          
          <div class="custom-modal-footer">
            <button class="action-btn secondary" @click="showPreview = false">
              <ion-icon name="close-outline"></ion-icon>
              Schließen
            </button>
            <button class="action-btn primary" @click="sendNewsletter(); showPreview = false;">
              <ion-icon name="send-outline"></ion-icon>
              Jetzt senden
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import SiteTitle from '@/components/SiteTitle.vue';
import { IonPage, IonContent, IonIcon, toastController, alertController } from '@ionic/vue';

export default defineComponent({
  name: 'NewsletterView',
  components: {
    IonPage,
    IonContent,
    IonIcon,
    SiteTitle
  },
  data() {
    return {
      subject: '',
      email: '',
      recipients: '',
      sendTestEmail: false,
      sending: false,
      showPreview: false,
      stats: {
        totalSent: 0,
        totalSubscribers: 0,
        openRate: 0,
        clickRate: 0
      },
      recentNewsletters: []
    };
  },
  computed: {
    recipientCount() {
      if (!this.recipients.trim()) return 0;
      const emails = this.recipients
        .split(/[\n,]/)
        .map(e => e.trim())
        .filter(e => e && this.isValidEmail(e));
      return emails.length;
    },
    canSend() {
      return this.subject.trim() && this.email.trim() && this.recipientCount > 0;
    }
  },
  mounted() {
    this.loadStats();
    this.loadRecentNewsletters();
  },
  methods: {
    async loadStats() {
      try {
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'get_stats',
            project: this.$route.params.project
          })
        );
        
        if (response.data.success) {
          this.stats = response.data.stats;
        }
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },
    
    async loadRecentNewsletters() {
      try {
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'get_recent',
            project: this.$route.params.project,
            limit: 5
          })
        );
        
        if (response.data.success) {
          this.recentNewsletters = response.data.newsletters || [];
        }
      } catch (error) {
        console.error('Error loading recent newsletters:', error);
      }
    },
    
    async sendNewsletter() {
      if (!this.canSend) return;
      
      this.sending = true;
      
      try {
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'send',
            project: this.$route.params.project,
            subject: this.subject,
            email: this.email,
            recipients: this.recipients,
            test_mode: this.sendTestEmail
          })
        );
        
        if (response.data.success) {
          const toast = await toastController.create({
            message: response.data.message || 'Newsletter erfolgreich gesendet!',
            duration: 3000,
            color: 'success',
            position: 'top'
          });
          await toast.present();
          
          this.clearForm();
          this.loadStats();
          this.loadRecentNewsletters();
        } else {
          throw new Error(response.data.message || 'Fehler beim Senden');
        }
      } catch (error) {
        console.error('Error sending newsletter:', error);
        
        const toast = await toastController.create({
          message: error.message || 'Fehler beim Senden des Newsletters',
          duration: 5000,
          color: 'danger',
          position: 'top'
        });
        await toast.present();
      } finally {
        this.sending = false;
      }
    },
    
    previewNewsletter() {
      if (!this.subject.trim() || !this.email.trim()) {
        return;
      }
      this.showPreview = true;
    },
    
    clearForm() {
      this.subject = '';
      this.email = '';
      this.recipients = '';
      this.sendTestEmail = false;
    },
    
    goToConfig() {
      this.$router.push({
        path: `/project/${this.$route.params.project}/newsletter/config`
      });
    },
    
    viewHistory() {
      // Navigate to history view if implemented
      console.log('View history');
    },
    
    async viewNewsletter(id) {
      // Implement view newsletter details
      console.log('View newsletter:', id);
    },
    
    async deleteNewsletter(id) {
      const alert = await alertController.create({
        header: 'Newsletter löschen',
        message: 'Möchtest du diesen Newsletter wirklich löschen?',
        buttons: [
          {
            text: 'Abbrechen',
            role: 'cancel'
          },
          {
            text: 'Löschen',
            role: 'destructive',
            handler: async () => {
              try {
                const response = await this.$axios.post(
                  'newsletter.php',
                  this.$qs.stringify({
                    action: 'delete',
                    project: this.$route.params.project,
                    id: id
                  })
                );
                
                if (response.data.success) {
                  const toast = await toastController.create({
                    message: 'Newsletter gelöscht',
                    duration: 2000,
                    color: 'success',
                    position: 'top'
                  });
                  await toast.present();
                  
                  this.loadRecentNewsletters();
                }
              } catch (error) {
                console.error('Error deleting newsletter:', error);
              }
            }
          }
        ]
      });
      
      await alert.present();
    },
    
    isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    
    formatStatus(status) {
      const statusMap = {
        'sent': 'Gesendet',
        'pending': 'Ausstehend',
        'failed': 'Fehlgeschlagen',
        'draft': 'Entwurf'
      };
      return statusMap[status] || status;
    },
    
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
});
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
  --info-color: #6366f1;
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

/* Page Title */
.page-title {
  margin: 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
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

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn ion-icon {
  font-size: 18px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
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
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
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
  margin: 0;
}

/* Form Container */
.form-container {
  padding: 32px;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.form-label ion-icon {
  font-size: 18px;
  color: var(--text-secondary);
}

.modern-input,
.modern-textarea,
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

.modern-input:focus,
.modern-textarea:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-textarea {
  resize: vertical;
  min-height: 100px;
}

.field-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 12px;
}

.field-hint ion-icon {
  font-size: 14px;
}

.field-hint code {
  background: var(--background);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 11px;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--text-primary);
}

.modern-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

/* Table Styles */
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
  flex: 0 0 120px;
  justify-content: center;
}

.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
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
  flex: 0 0 120px;
  justify-content: center;
}

.cell-content {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}

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

.view-btn {
  background: #eff6ff;
  color: var(--primary-color);
}

.view-btn:hover {
  background: #dbeafe;
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

/* Status Badge */
.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.status-badge.sent {
  background: #dcfce7;
  color: #059669;
}

.status-badge.pending {
  background: #fef3c7;
  color: #d97706;
}

.status-badge.failed {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.draft {
  background: #f1f5f9;
  color: #64748b;
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
  width: 800px;
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
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
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

.custom-modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

/* Preview Styles */
.preview-container {
  background: var(--surface);
}

.preview-meta {
  padding: 16px;
  background: var(--background);
  border-radius: var(--radius);
  margin-bottom: 20px;
}

.preview-field {
  display: flex;
  gap: 12px;
  margin-bottom: 8px;
  font-size: 14px;
}

.preview-field:last-child {
  margin-bottom: 0;
}

.preview-field strong {
  color: var(--text-secondary);
  min-width: 100px;
}

.preview-field span {
  color: var(--text-primary);
}

.preview-divider {
  height: 1px;
  background: var(--border);
  margin: 20px 0;
}

.preview-content {
  padding: 20px;
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  min-height: 200px;
  color: #000;
  font-size: 14px;
  line-height: 1.6;
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

.spinning {
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

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }
  
  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .form-container {
    padding: 20px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .action-btn {
    width: 100%;
    justify-content: center;
  }
  
  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
  }
}

/* Dark Mode Support */
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
</style>
