<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="settings-outline" title="Newsletter - Einstellungen" bg="transparent"/>
      
      <div class="page-container">
        <!-- Back Button -->
        <div class="action-bar">
          <button class="action-btn" @click="goBack">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Zurück
          </button>
        </div>

        <!-- Info Card -->
        <div class="info-card">
          <div class="info-icon">
            <ion-icon name="information-circle-outline"></ion-icon>
          </div>
          <div class="info-content">
            <h3>Newsletter Einstellungen</h3>
            <p>Konfiguriere die Einstellungen für das Newsletter-Modul.</p>
          </div>
        </div>

        <!-- Settings Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Allgemeine Einstellungen</h3>
            </div>
          </div>

          <div class="form-container">
            <form @submit.prevent="saveSettings">
              <!-- Sender Name -->
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

              <!-- Sender Email -->
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

              <!-- Reply-To Email -->
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

              <!-- Email Template -->
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

              <!-- Tracking -->
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

              <!-- Unsubscribe -->
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

              <!-- Rate Limiting -->
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

              <!-- Action Buttons -->
              <div class="form-actions">
                <button 
                  type="button" 
                  class="action-btn secondary" 
                  @click="resetSettings"
                  :disabled="saving"
                >
                  <ion-icon name="refresh-outline"></ion-icon>
                  Zurücksetzen
                </button>
                <button 
                  type="submit" 
                  class="action-btn primary" 
                  :disabled="saving"
                >
                  <ion-icon v-if="!saving" name="save-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ saving ? 'Wird gespeichert...' : 'Einstellungen speichern' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- SMTP Settings Card -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>SMTP Konfiguration</h3>
              <p class="entry-count">Konfiguriere deinen E-Mail-Server</p>
            </div>
          </div>

          <div class="form-container">
            <form @submit.prevent="saveSmtpSettings">
              <!-- SMTP Host -->
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

              <!-- SMTP Port -->
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

              <!-- SMTP Username -->
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

              <!-- SMTP Password -->
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

              <!-- Encryption -->
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

              <!-- Test Connection -->
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

              <!-- Action Buttons -->
              <div class="form-actions">
                <button 
                  type="submit" 
                  class="action-btn primary" 
                  :disabled="savingSmtp"
                >
                  <ion-icon v-if="!savingSmtp" name="save-outline"></ion-icon>
                  <ion-icon v-else name="hourglass-outline" class="spinning"></ion-icon>
                  {{ savingSmtp ? 'Wird gespeichert...' : 'SMTP speichern' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import SiteTitle from '@/components/SiteTitle.vue';
import { IonPage, IonContent, IonIcon, toastController } from '@ionic/vue';

export default defineComponent({
  name: 'NewsletterConfigView',
  components: {
    IonPage,
    IonContent,
    IonIcon,
    SiteTitle
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
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'get_settings',
            project: this.$route.params.project
          })
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
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'get_smtp',
            project: this.$route.params.project
          })
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
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'save_settings',
            project: this.$route.params.project,
            settings: JSON.stringify(this.settings)
          })
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
        const response = await this.$axios.post(
          'newsletter.php',
          this.$qs.stringify({
            action: 'save_smtp',
            project: this.$route.params.project,
            smtp: JSON.stringify(this.smtp)
          })
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
          'newsletter.php',
          this.$qs.stringify({
            action: 'test_smtp',
            project: this.$route.params.project,
            smtp: JSON.stringify(this.smtp)
          })
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
          message: error.message || 'Verbindung fehlgeschlagen',
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
/* Modern Design System */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Action Bar */
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

/* Info Card */
.info-card {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
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

/* Animations */
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

/* Responsive */
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

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
  
  .info-card {
    background: #1e3a8a;
    border-color: #1e40af;
  }
}
</style>
