<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="settings-outline" title="Newsletter - Einstellungen" bg="transparent"/>
      
      <div class="page-container">
        <div class="action-bar">
          <ActionButton icon="arrow-back-outline" @click="goBack">Zurück</ActionButton>
        </div>

        <div class="info-card">
          <div class="info-icon">
            <ion-icon name="information-circle-outline"></ion-icon>
          </div>
          <div class="info-content">
            <h3>Newsletter Einstellungen</h3>
            <p>Konfiguriere die Einstellungen für das Newsletter-Modul.</p>
          </div>
        </div>

        <DataCard title="Allgemeine Einstellungen" :no-padding="true">
          <div class="form-container">
            <form @submit.prevent="saveSettings">
              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="person-outline"></ion-icon>
                  Absender Name
                </label>
                <input 
                  v-model="settings.senderName" 
                  type="text" 
                  class="modern-input" 
                  placeholder="Dein Name oder Firmenname"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="mail-outline"></ion-icon>
                  Absender E-Mail
                </label>
                <input 
                  v-model="settings.senderEmail" 
                  type="email" 
                  class="modern-input" 
                  placeholder="absender@example.com"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="arrow-undo-outline"></ion-icon>
                  Antwort-E-Mail
                </label>
                <input 
                  v-model="settings.replyTo" 
                  type="email" 
                  class="modern-input" 
                  placeholder="antwort@example.com"
                />
                <div class="field-hint">
                  <ion-icon name="information-circle-outline"></ion-icon>
                  E-Mail-Adresse für Antworten (optional)
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="document-text-outline"></ion-icon>
                  E-Mail Vorlage
                </label>
                <select v-model="settings.template" class="modern-select">
                  <option value="default">Standard</option>
                  <option value="minimal">Minimal</option>
                  <option value="modern">Modern</option>
                  <option value="classic">Klassisch</option>
                </select>
              </div>

              <div class="form-group">
                <h4 class="subsection-title">Tracking & Analytics</h4>
                
                <label class="checkbox-container">
                  <input 
                    v-model="settings.trackOpens" 
                    type="checkbox" 
                    class="modern-checkbox"
                  />
                  <span>Öffnungen verfolgen</span>
                </label>
                
                <label class="checkbox-container">
                  <input 
                    v-model="settings.trackClicks" 
                    type="checkbox" 
                    class="modern-checkbox"
                  />
                  <span>Klicks verfolgen</span>
                </label>
              </div>

              <div class="form-group">
                <h4 class="subsection-title">Abmeldung</h4>
                
                <label class="checkbox-container">
                  <input 
                    v-model="settings.includeUnsubscribe" 
                    type="checkbox" 
                    class="modern-checkbox"
                  />
                  <span>Abmelde-Link einfügen</span>
                </label>
                
                <div v-if="settings.includeUnsubscribe" class="form-group sub-group">
                  <label class="form-label">Abmelde-Text</label>
                  <input 
                    v-model="settings.unsubscribeText" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Vom Newsletter abmelden"
                  />
                </div>
              </div>

              <div class="form-group">
                <h4 class="subsection-title">Versandlimit</h4>
                
                <label class="form-label">
                  <ion-icon name="speedometer-outline"></ion-icon>
                  E-Mails pro Minute
                </label>
                <input 
                  v-model.number="settings.rateLimit" 
                  type="number" 
                  class="modern-input" 
                  min="1"
                  max="100"
                  placeholder="30"
                />
                <div class="field-hint">
                  <ion-icon name="information-circle-outline"></ion-icon>
                  Verhindert Spam-Filter und Server-Überlastung
                </div>
              </div>

              <div class="form-actions">
                <ActionButton
                  variant="secondary"
                  icon="refresh-outline"
                  type="button"
                  @click="resetSettings"
                  :disabled="saving"
                >
                  Zurücksetzen
                </ActionButton>
                <ActionButton
                  variant="primary"
                  type="submit"
                  :disabled="saving"
                >
                  <ion-icon v-if="!saving" name="save-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ saving ? 'Wird gespeichert...' : 'Einstellungen speichern' }}
                </ActionButton>
              </div>
            </form>
          </div>
        </DataCard>

        <DataCard title="SMTP Konfiguration" subtitle="Konfiguriere deinen E-Mail-Server" :no-padding="true">
          <div class="form-container">
            <form @submit.prevent="saveSmtpSettings">
              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="server-outline"></ion-icon>
                  SMTP Host
                </label>
                <input 
                  v-model="smtp.host" 
                  type="text" 
                  class="modern-input" 
                  placeholder="smtp.example.com"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="link-outline"></ion-icon>
                  SMTP Port
                </label>
                <input 
                  v-model.number="smtp.port" 
                  type="number" 
                  class="modern-input" 
                  placeholder="587"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="person-outline"></ion-icon>
                  Benutzername
                </label>
                <input 
                  v-model="smtp.username" 
                  type="text" 
                  class="modern-input" 
                  placeholder="username@example.com"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="lock-closed-outline"></ion-icon>
                  Passwort
                </label>
                <input 
                  v-model="smtp.password" 
                  type="password" 
                  class="modern-input" 
                  placeholder="••••••••"
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <ion-icon name="shield-outline"></ion-icon>
                  Verschlüsselung
                </label>
                <select v-model="smtp.encryption" class="modern-select">
                  <option value="">Keine</option>
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                </select>
              </div>

              <div class="form-group">
                <button
                  type="button"
                  class="action-btn secondary full-width"
                  @click="testConnection"
                  :disabled="testing"
                >
                  <ion-icon v-if="!testing" name="pulse-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ testing ? 'Teste Verbindung...' : 'Verbindung testen' }}
                </button>
              </div>

              <div class="form-actions">
                <ActionButton
                  variant="primary"
                  type="submit"
                  :disabled="savingSmtp"
                >
                  <ion-icon v-if="!savingSmtp" name="save-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ savingSmtp ? 'Wird gespeichert...' : 'SMTP speichern' }}
                </ActionButton>
              </div>
            </form>
          </div>
        </DataCard>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import SiteTitle from '@/components/SiteTitle.vue';
import ActionButton from '@/components/ActionButton.vue';
import DataCard from '@/components/DataCard.vue';
import { IonPage, IonContent, IonIcon, toastController } from '@ionic/vue';

export default defineComponent({
  name: 'NewsletterConfigView',
  components: {
    IonPage,
    IonContent,
    IonIcon,
    SiteTitle,
    ActionButton,
    DataCard
  },
  data() {
    return {
      saving: false,
      savingSmtp: false,
      testing: false,
      settings: {
        senderName: '',
        senderEmail: '',
        replyTo: '',
        template: 'default',
        trackOpens: true,
        trackClicks: true,
        includeUnsubscribe: true,
        unsubscribeText: 'Vom Newsletter abmelden',
        rateLimit: 30
      },
      smtp: {
        host: '',
        port: 587,
        username: '',
        password: '',
        encryption: 'tls'
      }
    };
  },
  mounted() {
    this.loadSettings();
    this.loadSmtpSettings();
  },
  methods: {
    async loadSettings() {
      try {
        const response = await this.$axios.get(
          'v2/newsletter/settings',
          { params: { project: this.$route.params.project } }
        );

        if (response.data.success && response.data.settings) {
          this.settings = { ...this.settings, ...response.data.settings };
        }
      } catch (error) {
        console.error('Error loading settings:', error);
      }
    },
    
    async loadSmtpSettings() {
      try {
        const response = await this.$axios.get(
          'v2/newsletter/smtp',
          { params: { project: this.$route.params.project } }
        );

        if (response.data.success && response.data.smtp) {
          this.smtp = { ...this.smtp, ...response.data.smtp };
        }
      } catch (error) {
        console.error('Error loading SMTP settings:', error);
      }
    },
    
    async saveSettings() {
      this.saving = true;
      
      try {
        const response = await this.$axios.put(
          'v2/newsletter/settings',
          {
            project: this.$route.params.project,
            settings: JSON.stringify(this.settings)
          }
        );

        if (response.data.success) {
          const toast = await toastController.create({
            message: 'Einstellungen gespeichert',
            duration: 2000,
            color: 'success',
            position: 'top'
          });
          await toast.present();
        }
      } catch (error) {
        console.error('Error saving settings:', error);
        
        const toast = await toastController.create({
          message: 'Fehler beim Speichern',
          duration: 3000,
          color: 'danger',
          position: 'top'
        });
        await toast.present();
      } finally {
        this.saving = false;
      }
    },
    
    async saveSmtpSettings() {
      this.savingSmtp = true;
      
      try {
        const response = await this.$axios.put(
          'v2/newsletter/smtp',
          {
            project: this.$route.params.project,
            smtp: JSON.stringify(this.smtp)
          }
        );

        if (response.data.success) {
          const toast = await toastController.create({
            message: 'SMTP Einstellungen gespeichert',
            duration: 2000,
            color: 'success',
            position: 'top'
          });
          await toast.present();
        }
      } catch (error) {
        console.error('Error saving SMTP settings:', error);
        
        const toast = await toastController.create({
          message: 'Fehler beim Speichern',
          duration: 3000,
          color: 'danger',
          position: 'top'
        });
        await toast.present();
      } finally {
        this.savingSmtp = false;
      }
    },
    
    async testConnection() {
      this.testing = true;
      
      try {
        const response = await this.$axios.post(
          'v2/newsletter/smtp/test',
          {
            project: this.$route.params.project,
            smtp: JSON.stringify(this.smtp)
          }
        );

        if (response.data.success) {
          const toast = await toastController.create({
            message: 'Verbindung erfolgreich! ✓',
            duration: 3000,
            color: 'success',
            position: 'top'
          });
          await toast.present();
        } else {
          throw new Error(response.data.message || 'Verbindungsfehler');
        }
      } catch (error) {
        console.error('Error testing connection:', error);

        const toast = await toastController.create({
          message: error.response?.data?.error || error.message || 'Verbindung fehlgeschlagen',
          duration: 5000,
          color: 'danger',
          position: 'top'
        });
        await toast.present();
      } finally {
        this.testing = false;
      }
    },
    
    resetSettings() {
      this.settings = {
        senderName: '',
        senderEmail: '',
        replyTo: '',
        template: 'default',
        trackOpens: true,
        trackClicks: true,
        includeUnsubscribe: true,
        unsubscribeText: 'Vom Newsletter abmelden',
        rateLimit: 30
      };
    },
    
    goBack() {
      this.$router.push({
        path: `/project/${this.$route.params.project}/newsletter`
      });
    }
  }
});
</script>

<style scoped>
.page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.action-bar {
  margin-bottom: 24px;
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
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
}

.action-btn.full-width {
  width: 100%;
  justify-content: center;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn ion-icon {
  font-size: 18px;
}

.info-card {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
}

.info-icon {
  font-size: 32px;
  color: var(--primary-color);
}

.info-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.info-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.form-container {
  padding: 32px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group.sub-group {
  margin-top: 12px;
  margin-left: 26px;
}

.subsection-title {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
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

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 12px;
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

@media (prefers-color-scheme: dark) {
  .info-card {
    background: #7c2d12;
    border-color: #9a3412;
  }
}
</style>
