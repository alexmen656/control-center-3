<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="key-outline" title="Video API Konfiguration" />

      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <h2 class="section-title">API-Verbindungen für Video-Plattformen</h2>
          </div>
          
          <div class="action-group-right">
            <button class="action-btn secondary" @click="testAllConnections()">
              <ion-icon name="refresh-outline"></ion-icon>
              <span>Verbindungen testen</span>
            </button>
          </div>
        </div>

        <!-- Platform Connection Cards -->
        <div class="platform-grid">
          <!-- YouTube -->
          <div class="platform-card" :class="{'connected': connections.youtube.connected}">
            <div class="platform-header">
              <div class="platform-icon youtube">
                <ion-icon name="logo-youtube"></ion-icon>
              </div>
              <div class="platform-title">
                <h3>YouTube</h3>
                <div class="connection-status" :class="{'connected': connections.youtube.connected}">
                  <span>{{ connections.youtube.connected ? 'Verbunden' : 'Nicht verbunden' }}</span>
                  <ion-icon :name="connections.youtube.connected ? 'checkmark-circle' : 'alert-circle-outline'"></ion-icon>
                </div>
              </div>
            </div>

            <div class="platform-content">
              <p class="platform-description">
                Veröffentlichen Sie Videos direkt auf YouTube. Die Verbindung verwendet OAuth 2.0 zur sicheren Authentifizierung.
              </p>

              <div v-if="connections.youtube.connected" class="connected-info">
                <div class="account-info">
                  <span class="info-label">Verbunden mit:</span>
                  <span class="info-value">{{ connections.youtube.username }}</span>
                </div>
                <div class="account-info">
                  <span class="info-label">Kanal:</span>
                  <span class="info-value">{{ connections.youtube.channel }}</span>
                </div>
                <div class="account-info">
                  <span class="info-label">Letzte Aktualisierung:</span>
                  <span class="info-value">{{ formatDate(connections.youtube.lastUpdated) }}</span>
                </div>

                <div class="token-info">
                  <div class="token-expiry" :class="{'expiring': isTokenExpiring(connections.youtube.expiresAt)}">
                    <ion-icon name="time-outline"></ion-icon>
                    <span>{{ getTokenExpiryText(connections.youtube.expiresAt) }}</span>
                  </div>
                </div>

                <div class="platform-actions">
                  <button class="action-btn secondary" @click="refreshToken('youtube')">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Token erneuern</span>
                  </button>
                  <button class="action-btn warning" @click="disconnectPlatform('youtube')">
                    <ion-icon name="unlink-outline"></ion-icon>
                    <span>Verbindung trennen</span>
                  </button>
                </div>
              </div>

              <div v-else class="connection-form">
                <div class="form-group">
                  <label class="form-label">Client ID</label>
                  <input 
                    v-model="youtube.clientId" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihre YouTube API Client ID"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Client Secret</label>
                  <input 
                    v-model="youtube.clientSecret" 
                    type="password" 
                    class="modern-input" 
                    placeholder="Ihr YouTube API Client Secret"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Redirect URI</label>
                  <input 
                    v-model="youtube.redirectUri" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihr OAuth Redirect URI"
                    disabled
                  />
                  <div class="helper-text">Diese URL in Ihrer Google API Console als autorisierte Redirect URI hinterlegen.</div>
                </div>
                <div class="platform-actions">
                  <button class="action-btn primary" @click="connectPlatform('youtube')">
                    <ion-icon name="link-outline"></ion-icon>
                    <span>Mit YouTube verbinden</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Instagram -->
          <div class="platform-card" :class="{'connected': connections.instagram.connected}">
            <div class="platform-header">
              <div class="platform-icon instagram">
                <ion-icon name="logo-instagram"></ion-icon>
              </div>
              <div class="platform-title">
                <h3>Instagram</h3>
                <div class="connection-status" :class="{'connected': connections.instagram.connected}">
                  <span>{{ connections.instagram.connected ? 'Verbunden' : 'Nicht verbunden' }}</span>
                  <ion-icon :name="connections.instagram.connected ? 'checkmark-circle' : 'alert-circle-outline'"></ion-icon>
                </div>
              </div>
            </div>

            <div class="platform-content">
              <p class="platform-description">
                Teilen Sie Videos und Reels direkt auf Instagram. Erfordert eine Facebook Developer App und Instagram Business-Konto.
              </p>

              <div v-if="connections.instagram.connected" class="connected-info">
                <div class="account-info">
                  <span class="info-label">Verbunden mit:</span>
                  <span class="info-value">{{ connections.instagram.username }}</span>
                </div>
                <div class="account-info">
                  <span class="info-label">Konto-Typ:</span>
                  <span class="info-value">{{ connections.instagram.accountType }}</span>
                </div>
                <div class="account-info">
                  <span class="info-label">Letzte Aktualisierung:</span>
                  <span class="info-value">{{ formatDate(connections.instagram.lastUpdated) }}</span>
                </div>

                <div class="token-info">
                  <div class="token-expiry" :class="{'expiring': isTokenExpiring(connections.instagram.expiresAt)}">
                    <ion-icon name="time-outline"></ion-icon>
                    <span>{{ getTokenExpiryText(connections.instagram.expiresAt) }}</span>
                  </div>
                </div>

                <div class="platform-actions">
                  <button class="action-btn secondary" @click="refreshToken('instagram')">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Token erneuern</span>
                  </button>
                  <button class="action-btn warning" @click="disconnectPlatform('instagram')">
                    <ion-icon name="unlink-outline"></ion-icon>
                    <span>Verbindung trennen</span>
                  </button>
                </div>
              </div>

              <div v-else class="connection-form">
                <div class="form-group">
                  <label class="form-label">App ID</label>
                  <input 
                    v-model="instagram.appId" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihre Facebook App ID"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">App Secret</label>
                  <input 
                    v-model="instagram.appSecret" 
                    type="password" 
                    class="modern-input" 
                    placeholder="Ihr Facebook App Secret"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Redirect URI</label>
                  <input 
                    v-model="instagram.redirectUri" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihr OAuth Redirect URI"
                    disabled
                  />
                  <div class="helper-text">Diese URL in Ihrer Facebook Developer App als OAuth Redirect URI hinterlegen.</div>
                </div>
                <div class="platform-actions">
                  <button class="action-btn primary" @click="connectPlatform('instagram')">
                    <ion-icon name="link-outline"></ion-icon>
                    <span>Mit Instagram verbinden</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TikTok -->
          <div class="platform-card" :class="{'connected': connections.tiktok.connected}">
            <div class="platform-header">
              <div class="platform-icon tiktok">
                <ion-icon name="logo-tiktok"></ion-icon>
              </div>
              <div class="platform-title">
                <h3>TikTok</h3>
                <div class="connection-status" :class="{'connected': connections.tiktok.connected}">
                  <span>{{ connections.tiktok.connected ? 'Verbunden' : 'Nicht verbunden' }}</span>
                  <ion-icon :name="connections.tiktok.connected ? 'checkmark-circle' : 'alert-circle-outline'"></ion-icon>
                </div>
              </div>
            </div>

            <div class="platform-content">
              <p class="platform-description">
                Laden Sie Videos direkt auf TikTok hoch und planen Sie Ihre Veröffentlichungen. Erfordert ein TikTok For Business-Konto.
              </p>

              <div v-if="connections.tiktok.connected" class="connected-info">
                <div class="account-info">
                  <span class="info-label">Verbunden mit:</span>
                  <span class="info-value">{{ connections.tiktok.username }}</span>
                </div>
                <div class="account-info">
                  <span class="info-label">Letzte Aktualisierung:</span>
                  <span class="info-value">{{ formatDate(connections.tiktok.lastUpdated) }}</span>
                </div>

                <div class="token-info">
                  <div class="token-expiry" :class="{'expiring': isTokenExpiring(connections.tiktok.expiresAt)}">
                    <ion-icon name="time-outline"></ion-icon>
                    <span>{{ getTokenExpiryText(connections.tiktok.expiresAt) }}</span>
                  </div>
                </div>

                <div class="platform-actions">
                  <button class="action-btn secondary" @click="refreshToken('tiktok')">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Token erneuern</span>
                  </button>
                  <button class="action-btn warning" @click="disconnectPlatform('tiktok')">
                    <ion-icon name="unlink-outline"></ion-icon>
                    <span>Verbindung trennen</span>
                  </button>
                </div>
              </div>

              <div v-else class="connection-form">
                <div class="form-group">
                  <label class="form-label">Client Key</label>
                  <input 
                    v-model="tiktok.clientKey" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihr TikTok API Client Key"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Client Secret</label>
                  <input 
                    v-model="tiktok.clientSecret" 
                    type="password" 
                    class="modern-input" 
                    placeholder="Ihr TikTok API Client Secret"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Redirect URI</label>
                  <input 
                    v-model="tiktok.redirectUri" 
                    type="text" 
                    class="modern-input" 
                    placeholder="Ihr OAuth Redirect URI"
                    disabled
                  />
                  <div class="helper-text">Diese URL in Ihrem TikTok Developer Portal als Callback URL hinterlegen.</div>
                </div>
                <div class="platform-actions">
                  <button class="action-btn primary" @click="connectPlatform('tiktok')">
                    <ion-icon name="link-outline"></ion-icon>
                    <span>Mit TikTok verbinden</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Settings -->
        <div class="settings-section">
          <h2 class="section-title">Erweiterte Einstellungen</h2>
          
          <div class="settings-card">
            <div class="settings-group">
              <h3>API-Anfrage Einstellungen</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">API Timeout (Sekunden)</label>
                  <input 
                    v-model="settings.apiTimeout" 
                    type="number" 
                    class="modern-input" 
                    min="5"
                    max="300"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Max. Upload-Größe (MB)</label>
                  <input 
                    v-model="settings.maxUploadSize" 
                    type="number" 
                    class="modern-input" 
                    min="10"
                    max="2000"
                  />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">API Rate Limiting</label>
                  <div class="toggle-switch">
                    <input type="checkbox" id="rateLimiting" v-model="settings.enableRateLimiting">
                    <label for="rateLimiting"></label>
                    <span>{{ settings.enableRateLimiting ? 'Aktiviert' : 'Deaktiviert' }}</span>
                  </div>
                </div>
                <div class="form-group" v-if="settings.enableRateLimiting">
                  <label class="form-label">Anfragen pro Minute</label>
                  <input 
                    v-model="settings.requestsPerMinute" 
                    type="number" 
                    class="modern-input" 
                    min="1"
                    max="100"
                  />
                </div>
              </div>
            </div>
            
            <div class="settings-group">
              <h3>Proxy-Einstellungen</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Proxy verwenden</label>
                  <div class="toggle-switch">
                    <input type="checkbox" id="useProxy" v-model="settings.useProxy">
                    <label for="useProxy"></label>
                    <span>{{ settings.useProxy ? 'Aktiviert' : 'Deaktiviert' }}</span>
                  </div>
                </div>
              </div>
              
              <div v-if="settings.useProxy">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Proxy Host</label>
                    <input 
                      v-model="settings.proxyHost" 
                      type="text" 
                      class="modern-input" 
                      placeholder="z.B. proxy.example.com"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Proxy Port</label>
                    <input 
                      v-model="settings.proxyPort" 
                      type="number" 
                      class="modern-input" 
                      placeholder="z.B. 8080"
                    />
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Proxy Benutzername (optional)</label>
                    <input 
                      v-model="settings.proxyUsername" 
                      type="text" 
                      class="modern-input" 
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Proxy Passwort (optional)</label>
                    <input 
                      v-model="settings.proxyPassword" 
                      type="password" 
                      class="modern-input" 
                    />
                  </div>
                </div>
              </div>
            </div>
            
            <div class="settings-actions">
              <button class="action-btn secondary" @click="resetSettings()">
                <ion-icon name="refresh-outline"></ion-icon>
                <span>Zurücksetzen</span>
              </button>
              <button class="action-btn primary" @click="saveSettings()">
                <ion-icon name="save-outline"></ion-icon>
                <span>Einstellungen speichern</span>
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

export default {
  name: "VideoAPIConfig",
  components: {
    SiteTitle,
  },
  data() {
    return {
      // Platform Connections Status
      connections: {
        youtube: {
          connected: false,
          username: '',
          channel: '',
          lastUpdated: null,
          expiresAt: null
        },
        instagram: {
          connected: false,
          username: '',
          accountType: '',
          lastUpdated: null,
          expiresAt: null
        },
        tiktok: {
          connected: false,
          username: '',
          lastUpdated: null,
          expiresAt: null
        }
      },
      
      // Platform Connection Forms
      youtube: {
        clientId: '',
        clientSecret: '',
        redirectUri: 'https://alex.polan.sk/control-center/youtube_callback.php'
      },
      instagram: {
        appId: '',
        appSecret: '',
        redirectUri: window.location.origin + '/instagram_callback.php'
      },
      tiktok: {
        clientKey: '',
        clientSecret: '',
        redirectUri: window.location.origin + '/tiktok_callback.php'
      },
      
      // Advanced Settings
      settings: {
        apiTimeout: 60,
        maxUploadSize: 500,
        enableRateLimiting: true,
        requestsPerMinute: 30,
        useProxy: false,
        proxyHost: '',
        proxyPort: '',
        proxyUsername: '',
        proxyPassword: ''
      },
      
      loading: false,
      error: null,
      success: null
    };
  },
  
  mounted() {
    this.loadConnections();
    this.loadSettings();
  },
  
  methods: {
    async loadConnections() {
      this.loading = true;
      
      try {
        const response = await this.$axios.get('video_uploads_config.php', {
          params: {
            action: 'get_connections',
            project: this.$route.params.project
          }
        });
        
        if (response.data.connections) {
          this.connections = response.data.connections;
        }
      } catch (error) {
        console.error('Error loading connections:', error);
        this.error = 'Fehler beim Laden der API-Verbindungen';
      } finally {
        this.loading = false;
      }
    },
    
    async loadSettings() {
      try {
        const response = await this.$axios.get('video_uploads_config.php', {
          params: {
            action: 'get_settings',
            project: this.$route.params.project
          }
        });
        
        if (response.data.settings) {
          this.settings = response.data.settings;
        }
      } catch (error) {
        console.error('Error loading settings:', error);
      }
    },
    
    async connectPlatform(platform) {
      this.loading = true;
      this.error = null;
      this.success = null;
      
      try {
        let platformData;
        let platformName;
        
        switch (platform) {
          case 'youtube':
            platformData = this.youtube;
            platformName = 'YouTube';
            break;
          case 'instagram':
            platformData = this.instagram;
            platformName = 'Instagram';
            break;
          case 'tiktok':
            platformData = this.tiktok;
            platformName = 'TikTok';
            break;
          default:
            throw new Error('Ungültige Plattform');
        }
        
        // Validate inputs
        if (!this.validatePlatformInputs(platform)) {
          return;
        }
        
        // Start OAuth process
        const response = await this.$axios.post('video_uploads_config.php', this.$qs.stringify({
          action: 'init_oauth',
          platform: platform,
          project: this.$route.params.project,
          ...platformData
        }));
        
        if (response.data.auth_url) {
          // Open OAuth popup
          const width = 600;
          const height = 700;
          const left = window.screen.width / 2 - width / 2;
          const top = window.screen.height / 2 - height / 2;
          
          window.open(
            response.data.auth_url,
            `${platformName} Authorization`,
            `width=${width},height=${height},left=${left},top=${top}`
          );
          
          // Poll for auth completion
          this.pollAuthStatus(platform);
        } else {
          this.error = `Fehler beim Initialisieren der ${platformName}-Authentifizierung`;
        }
      } catch (error) {
        console.error(`Error connecting to ${platform}:`, error);
        this.error = `Verbindungsfehler zu ${this.getPlatformName(platform)}`;
      } finally {
        this.loading = false;
      }
    },
    
    validatePlatformInputs(platform) {
  let isValid = true;
  const missingFields = [];
      
      switch (platform) {
        case 'youtube':
          if (!this.youtube.clientId) missingFields.push('Client ID');
          if (!this.youtube.clientSecret) missingFields.push('Client Secret');
          break;
        case 'instagram':
          if (!this.instagram.appId) missingFields.push('App ID');
          if (!this.instagram.appSecret) missingFields.push('App Secret');
          break;
        case 'tiktok':
          if (!this.tiktok.clientKey) missingFields.push('Client Key');
          if (!this.tiktok.clientSecret) missingFields.push('Client Secret');
          break;
      }
      
      if (missingFields.length > 0) {
        this.error = `Bitte füllen Sie alle erforderlichen Felder aus: ${missingFields.join(', ')}`;
        isValid = false;
      }
      
      return isValid;
    },
    
    async pollAuthStatus(platform) {
      const pollInterval = setInterval(async () => {
        try {
          const response = await this.$axios.get('video_uploads_config.php', {
            params: {
              action: 'check_oauth',
              platform: platform,
              project: this.$route.params.project
            }
          });
          
          if (response.data.status === 'completed') {
            clearInterval(pollInterval);
            this.success = `Erfolgreich mit ${this.getPlatformName(platform)} verbunden!`;
            this.loadConnections();
          } else if (response.data.status === 'failed') {
            clearInterval(pollInterval);
            this.error = `Authentifizierung mit ${this.getPlatformName(platform)} fehlgeschlagen: ${response.data.error || 'Unbekannter Fehler'}`;
          }
        } catch (error) {
          console.error('Error checking auth status:', error);
        }
      }, 2000);
      
      // Stop polling after 2 minutes
      setTimeout(() => {
        clearInterval(pollInterval);
      }, 120000);
    },
    
    async disconnectPlatform(platform) {
      if (!confirm(`Sind Sie sicher, dass Sie die Verbindung zu ${this.getPlatformName(platform)} trennen möchten?`)) {
        return;
      }
      
      this.loading = true;
      
      try {
        await this.$axios.post('video_uploads_config.php', this.$qs.stringify({
          action: 'disconnect',
          platform: platform,
          project: this.$route.params.project
        }));
        
        this.success = `Verbindung zu ${this.getPlatformName(platform)} wurde getrennt`;
        this.loadConnections();
      } catch (error) {
        console.error('Error disconnecting platform:', error);
        this.error = `Fehler beim Trennen der Verbindung zu ${this.getPlatformName(platform)}`;
      } finally {
        this.loading = false;
      }
    },
    
    async refreshToken(platform) {
      this.loading = true;
      
      try {
        await this.$axios.post('video_uploads_config.php', this.$qs.stringify({
          action: 'refresh_token',
          platform: platform,
          project: this.$route.params.project
        }));
        
        this.success = `Token für ${this.getPlatformName(platform)} wurde erneuert`;
        this.loadConnections();
      } catch (error) {
        console.error('Error refreshing token:', error);
        this.error = `Fehler beim Erneuern des Tokens für ${this.getPlatformName(platform)}`;
      } finally {
        this.loading = false;
      }
    },
    
    async testAllConnections() {
      this.loading = true;
      this.error = null;
      this.success = null;
      
      try {
        const response = await this.$axios.post('video_uploads_config.php', this.$qs.stringify({
          action: 'test_connections',
          project: this.$route.params.project
        }));
        
        if (response.data.results) {
          const results = response.data.results;
          let successCount = 0;
          let failCount = 0;
          
          Object.keys(results).forEach(platform => {
            if (results[platform].success) {
              successCount++;
            } else {
              failCount++;
            }
          });
          
          if (failCount === 0 && successCount > 0) {
            this.success = 'Alle Verbindungen sind funktionsfähig';
          } else if (successCount === 0) {
            this.error = 'Keine aktiven Verbindungen gefunden';
          } else {
            this.success = `${successCount} Verbindungen funktionsfähig, ${failCount} fehlgeschlagen`;
            this.error = 'Einige Verbindungen konnten nicht hergestellt werden';
          }
          
          this.loadConnections();
        }
      } catch (error) {
        console.error('Error testing connections:', error);
        this.error = 'Fehler beim Testen der Verbindungen';
      } finally {
        this.loading = false;
      }
    },
    
    async saveSettings() {
      this.loading = true;
      this.error = null;
      this.success = null;
      
      try {
        await this.$axios.post('video_uploads_config.php', this.$qs.stringify({
          action: 'save_settings',
          project: this.$route.params.project,
          settings: JSON.stringify(this.settings)
        }));
        
        this.success = 'Einstellungen wurden gespeichert';
      } catch (error) {
        console.error('Error saving settings:', error);
        this.error = 'Fehler beim Speichern der Einstellungen';
      } finally {
        this.loading = false;
      }
    },
    
    resetSettings() {
      this.settings = {
        apiTimeout: 60,
        maxUploadSize: 500,
        enableRateLimiting: true,
        requestsPerMinute: 30,
        useProxy: false,
        proxyHost: '',
        proxyPort: '',
        proxyUsername: '',
        proxyPassword: ''
      };
    },
    
    getPlatformName(platform) {
      const names = {
        youtube: 'YouTube',
        instagram: 'Instagram',
        tiktok: 'TikTok'
      };
      return names[platform] || platform;
    },
    
    formatDate(timestamp) {
      if (!timestamp) return 'Nie';
      
      const date = new Date(timestamp * 1000);
      return date.toLocaleString('de-DE');
    },
    
    isTokenExpiring(expiresAt) {
      if (!expiresAt) return false;
      
      const now = Math.floor(Date.now() / 1000);
      const oneWeek = 7 * 24 * 60 * 60;
      
      return expiresAt - now < oneWeek;
    },
    
    getTokenExpiryText(expiresAt) {
      if (!expiresAt) return 'Token-Ablaufdatum unbekannt';
      
      const now = Math.floor(Date.now() / 1000);
      const secondsRemaining = expiresAt - now;
      
      if (secondsRemaining <= 0) {
        return 'Token abgelaufen';
      }
      
      const days = Math.floor(secondsRemaining / (24 * 60 * 60));
      const hours = Math.floor((secondsRemaining % (24 * 60 * 60)) / (60 * 60));
      
      if (days > 0) {
        return `Token läuft in ${days} Tagen ab`;
      } else {
        return `Token läuft in ${hours} Stunden ab`;
      }
    }
  }
};
</script>

<style scoped>
/* Modern Design System - inherit colors from Video Uploads component */
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

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.section-title {
  margin: 0 0 16px 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: none;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn.primary {
  background-color: var(--primary-color);
  color: white;
}

.action-btn.primary:hover {
  background-color: var(--primary-hover);
}

.action-btn.secondary {
  background-color: var(--surface);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.action-btn.secondary:hover {
  background-color: var(--background);
  color: var(--text-primary);
}

.action-btn.warning {
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.action-btn.warning:hover {
  background-color: rgba(220, 38, 38, 0.2);
}

/* Platform Grid */
.platform-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.platform-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  transition: all 0.2s ease;
}

.platform-card.connected {
  border-color: var(--primary-color);
  border-width: 2px;
}

.platform-header {
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  background: var(--background);
  border-bottom: 1px solid var(--border);
}

.platform-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  font-size: 24px;
}

.platform-icon.youtube {
  background: rgba(255, 0, 0, 0.1);
  color: #ff0000;
}

.platform-icon.instagram {
  background: rgba(225, 48, 108, 0.1);
  color: #e1306c;
}

.platform-icon.tiktok {
  background: rgba(0, 0, 0, 0.1);
  color: #000000;
}

.platform-title {
  flex: 1;
}

.platform-title h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
}

.connection-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: var(--danger-color);
}

.connection-status.connected {
  color: var(--success-color);
}

.platform-content {
  padding: 20px;
}

.platform-description {
  margin: 0 0 20px 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

/* Connected Info */
.connected-info {
  border-top: 1px solid var(--border);
  padding-top: 16px;
}

.account-info {
  margin-bottom: 8px;
  font-size: 14px;
}

.info-label {
  color: var(--text-secondary);
  margin-right: 8px;
}

.info-value {
  color: var(--text-primary);
  font-weight: 500;
}

.token-info {
  margin: 16px 0;
  padding: 10px;
  background: var(--background);
  border-radius: var(--radius);
}

.token-expiry {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: var(--text-secondary);
}

.token-expiry.expiring {
  color: var(--warning-color);
}

.platform-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

/* Connection Form */
.connection-form {
  border-top: 1px solid var(--border);
  padding-top: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 8px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
}

.modern-input {
  width: 100%;
  padding: 10px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
}

.modern-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.modern-input:disabled {
  background: var(--background);
  color: var(--text-secondary);
  cursor: not-allowed;
}

.helper-text {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
}

/* Advanced Settings */
.settings-section {
  margin-top: 40px;
}

.settings-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  box-shadow: var(--shadow);
}

.settings-group {
  margin-bottom: 32px;
}

.settings-group h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: var(--text-primary);
}

.settings-actions {
  display: flex;
  justify-content: flex-end;
  gap: 16px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}

/* Toggle Switch */
.toggle-switch {
  display: flex;
  align-items: center;
  gap: 8px;
}

.toggle-switch input {
  height: 0;
  width: 0;
  visibility: hidden;
  position: absolute;
}

.toggle-switch label {
  cursor: pointer;
  width: 50px;
  height: 24px;
  background: var(--border);
  display: block;
  border-radius: 24px;
  position: relative;
  transition: background-color 0.2s ease;
}

.toggle-switch label:after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: var(--surface);
  border-radius: 20px;
  transition: transform 0.2s ease;
}

.toggle-switch input:checked + label {
  background: var(--primary-color);
}

.toggle-switch input:checked + label:after {
  transform: translateX(26px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .platform-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>