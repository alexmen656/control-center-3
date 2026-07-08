<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="mail-outline" title="Newsletter" bg="transparent"/>
      
      <div class="page-container">
        <div class="action-bar">
          <div class="action-group-left">
            <PageTitle icon="mail-outline" title="Newsletter versenden" />
          </div>

          <div class="action-group-right">
            <ActionButton icon="settings-outline" @click="goToConfig">Einstellungen</ActionButton>
            <ActionButton icon="time-outline" @click="viewHistory">Verlauf</ActionButton>
          </div>
        </div>

        <div class="stats-grid">
          <StatCard icon="mail-outline" color="primary" :value="stats.totalSent || 0" label="Gesendete Newsletter" />
          <StatCard icon="people-outline" color="success" :value="stats.totalSubscribers || 0" label="Abonnenten" />
          <StatCard icon="eye-outline" color="warning" :value="(stats.openRate || 0) + '%'" label="Öffnungsrate" />
          <StatCard icon="hand-left-outline" color="info" :value="(stats.clickRate || 0) + '%'" label="Klickrate" />
        </div>

        <DataCard title="Newsletter erstellen" subtitle="Sende personalisierte Newsletter an deine Abonnenten" noPadding>
          <div class="form-container">
            <form @submit.prevent="sendNewsletter">
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

              <div class="form-actions">
                <ActionButton
                  variant="secondary"
                  icon="close-outline"
                  type="button"
                  @click="clearForm"
                  :disabled="sending"
                >Zurücksetzen</ActionButton>
                <ActionButton
                  icon="eye-outline"
                  type="button"
                  @click="previewNewsletter"
                  :disabled="sending"
                >Vorschau</ActionButton>
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
        </DataCard>

        <DataCard v-if="recentNewsletters.length > 0" title="Letzte Newsletter" :subtitle="recentNewsletters.length + ' kürzlich gesendet'" noPadding>
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
        </DataCard>
      </div>

      <AppModal v-model="showPreview" title="Newsletter Vorschau" size="large">
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
        <template #footer>
          <ActionButton variant="secondary" icon="close-outline" @click="showPreview = false">Schließen</ActionButton>
          <ActionButton variant="primary" icon="send-outline" @click="sendNewsletter(); showPreview = false;">Jetzt senden</ActionButton>
        </template>
      </AppModal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import SiteTitle from '@/components/SiteTitle.vue';
import PageTitle from '@/components/PageTitle.vue';
import StatCard from '@/components/StatCard.vue';
import ActionButton from '@/components/ActionButton.vue';
import AppModal from '@/components/AppModal.vue';
import DataCard from '@/components/DataCard.vue';
import { IonPage, IonContent, IonIcon, toastController, alertController } from '@ionic/vue';

export default defineComponent({
  name: 'NewsletterView',
  components: {
    IonPage,
    IonContent,
    IonIcon,
    SiteTitle,
    PageTitle,
    StatCard,
    ActionButton,
    AppModal,
    DataCard
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
        const response = await this.$axios.get(
          'v2/newsletter/stats',
          { params: { project: this.$route.params.project } }
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
        const response = await this.$axios.get(
          'v2/newsletter/recent',
          {
            params: {
              project: this.$route.params.project,
              limit: 5
            }
          }
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
          'v2/newsletter/send',
          {
            project: this.$route.params.project,
            subject: this.subject,
            email: this.email,
            recipients: this.recipients,
            test_mode: this.sendTestEmail
          }
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
          message: error.response?.data?.error || error.message || 'Fehler beim Senden des Newsletters',
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
                const response = await this.$axios.delete(
                  'v2/newsletter/' + id,
                  { params: { project: this.$route.params.project } }
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
.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-title {
  margin: 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

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
  background: rgba(249, 115, 22, 0.12);
  color: var(--primary-color);
}

.view-btn:hover {
  background: rgba(249, 115, 22, 0.22);
  transform: scale(1.05);
}

.delete-btn {
  background: rgba(235, 68, 90, 0.12);
  color: var(--danger-color);
}

.delete-btn:hover {
  background: rgba(235, 68, 90, 0.22);
  transform: scale(1.05);
}

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
}
</style>
