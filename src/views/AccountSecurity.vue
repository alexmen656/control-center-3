<template>
  <ion-page>
    <ion-content class="modern-content">
      <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          <div class="header-left">
            <button class="back-btn" @click="$router.go(-1)">
              <ion-icon name="arrow-back-outline"></ion-icon>
            </button>
            <div>
              <h1>Account Security</h1>
              <p>Manage your security settings and login methods</p>
            </div>
          </div>
        </div>

        <!-- Security Settings -->
        <div class="security-sections">
          <!-- Login Methods -->
          <div class="security-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="log-in-outline"></ion-icon>
              </div>
              <div>
                <h3>Login Methods</h3>
                <p>Configure your preferred login options</p>
              </div>
            </div>

            <div class="card-content">
              <div class="login-methods">
                <!-- Google Login -->
                <div class="login-method">
                  <div class="method-info">
                    <div class="method-icon google">
                      <ion-icon name="logo-google"></ion-icon>
                    </div>
                    <div class="method-details">
                      <h4>Google Account</h4>
                      <p>Sign in with your Google account</p>
                    </div>
                  </div>
                  <div class="method-toggle">
                    <ion-toggle :checked="login_with_google" @ionChange="update($event)" color="success"></ion-toggle>
                  </div>
                </div>

                <!-- Microsoft Login -->
                <div class="login-method">
                  <div class="method-info">
                    <div class="method-icon microsoft">
                      <ion-icon name="logo-microsoft"></ion-icon>
                    </div>
                    <div class="method-details">
                      <h4>Microsoft Account</h4>
                      <p>Sign in with your Microsoft account</p>
                    </div>
                  </div>
                  <div class="method-toggle">
                    <ion-toggle :checked="login_with_microsoft" @ionChange="update2($event)"
                      color="success"></ion-toggle>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Connected Services -->
          <div class="security-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="link-outline"></ion-icon>
              </div>
              <div>
                <h3>Connected Services</h3>
                <p>External services linked to your account</p>
              </div>
            </div>

            <div class="card-content">
              <div class="connected-services">
                <!-- GitHub Connection -->
                <div class="service-item">
                  <div class="service-info">
                    <div class="service-icon github">
                      <ion-icon name="logo-github"></ion-icon>
                    </div>
                    <div class="service-details">
                      <h4>GitHub</h4>
                      <p v-if="githubAccount">Connected as {{ githubAccount.login }}</p>
                      <p v-else>Connect your GitHub account</p>
                    </div>
                  </div>
                  <div class="service-status">
                    <span v-if="githubAccount" class="status-badge connected">Connected</span>
                    <button v-else class="connect-btn" @click="connectGithub({ detail: { checked: true } })">
                      <ion-icon name="add-outline"></ion-icon>
                      Connect
                    </button>
                  </div>
                </div>

                <!-- Vercel Connection -->
                <div class="service-item">
                  <div class="service-info">
                    <div class="service-icon vercel">
                      <ion-icon name="logo-vercel"></ion-icon>
                    </div>
                    <div class="service-details">
                      <h4>Vercel</h4>
                      <p>Deploy and manage your projects</p>
                    </div>
                  </div>
                  <div class="service-status">
                    <span v-if="vercelConnected" class="status-badge connected">Connected</span>
                    <button v-else class="connect-btn" @click="connectVercel()">
                      <ion-icon name="add-outline"></ion-icon>
                      Connect
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Security Actions -->
          <div class="security-card">
            <div class="card-header">
              <div class="header-icon">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
              </div>
              <div>
                <h3>Security Actions</h3>
                <p>Additional security measures</p>
              </div>
            </div>

            <div class="card-content">
              <div class="security-actions">
                <button class="action-btn primary">
                  <ion-icon name="key-outline"></ion-icon>
                  <span>Change Password</span>
                </button>
                <button class="action-btn secondary">
                  <ion-icon name="finger-print-outline"></ion-icon>
                  <span>Two-Factor Authentication</span>
                </button>
                <button class="action-btn secondary">
                  <ion-icon name="download-outline"></ion-icon>
                  <span>Download Account Data</span>
                </button>
                <button class="action-btn danger">
                  <ion-icon name="trash-outline"></ion-icon>
                  <span>Delete Account</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { getUserData } from "@/userData";

export default defineComponent({
  data() {
    return {
      user: {},
      login_with_google: false,
      login_with_microsoft: false,
      login_with_github: false,
      githubAccount: null,
      vercelConnected: false,
    };
  },
  async created() {
    this.user = await getUserData();
    const login_wg = this.user.login_with_google;
    if (typeof login_wg === 'string' && login_wg.toLowerCase() == "microsoft") {
      this.login_with_microsoft = true;
      console.log("login with microsoft");
    } else if (login_wg == true) {
      console.log("login with google");
      this.login_with_google = true;
    }
    // GitHub Login Status & Info prüfen
    if (this.user.userID) {
      try {
        const res = await this.$axios.get(`github_token_status.php?userID=${this.user.userID}`);
        this.login_with_github = !!(res.data && res.data.connected);
        if (this.login_with_github) {
          const infoRes = await this.$axios.get(`github_token_info.php?userID=${this.user.userID}`);
          if (infoRes.data && infoRes.data.login) {
            this.githubAccount = infoRes.data;
          } else {
            this.githubAccount = null;
          }
        } else {
          this.githubAccount = null;
        }
        // Vercel Status
        const vercelRes = await this.$axios.get(`vercel_token_status.php?userID=${this.user.userID}`);
        this.vercelConnected = !!(vercelRes.data && vercelRes.data.connected);
      } catch (e) {
        this.login_with_github = false;
        this.githubAccount = null;
        this.vercelConnected = false;
      }
    }
  },
  methods: {
    connectGithub(event) {
    },
    connectVercel() {
      const clientSlug = 'control-center';
      const userId = this.user && this.user.userID ? this.user.userID : '';
      const state = userId ? `user_${userId}` : '';
      const vercelAuthUrl = `https://vercel.com/integrations/${clientSlug}/new?state=${state}`;
      window.location.href = vercelAuthUrl;
      this.login_with_github = event.detail.checked;
      if (event.detail.checked) {
        // OAuth2-URL für GitHub (Client-ID und Redirect-URL anpassen!)
        const clientId = 'Ov23liwAe9al1YhVcwrK';
        const redirectUri = encodeURIComponent('https://alex.polan.sk/control-center/github_oauth_callback.php');//control-center.eu
        const scope = 'repo user';
        // User-ID für state-Parameter
        const userId = this.user && this.user.userID ? this.user.userID : '';
        const state = userId ? `user_${userId}` : '';
        const githubAuthUrl = `https://github.com/login/oauth/authorize?client_id=${clientId}&redirect_uri=${redirectUri}&scope=${scope}&state=${state}`;
        window.location.href = githubAuthUrl;
      } else {
        // Optional: Logout/Token entfernen
        // this.$axios.post('user.php', this.$qs.stringify({ updateLoginWithGithub: false }));
      }
    },
    update(event) {
      this.login_with_google = event.detail.checked;

      if (event.detail.checked) {
        this.login_with_microsoft = false;
      }

      this.$axios.post(
        "user.php",
        this.$qs.stringify({
          updateLoginWithGoogle: "updateLoginWithGoogle",
          newValue: event.detail.checked.toString(),
        })
      ).then((response) => {
        console.log(response.data);
      });
    },
    update2(event) {
      this.login_with_microsoft = event.detail.checked;

      if (event.detail.checked) {
        this.login_with_google = false;
        this.$axios.post(
          "user.php",
          this.$qs.stringify({
            updateLoginWithGoogle: "updateLoginWithGoogle",
            newValue: "Microsoft",
          })
        );
      } else {
        this.$axios.post(
          "user.php",
          this.$qs.stringify({
            updateLoginWithGoogle: "updateLoginWithGoogle",
            newValue: false,
          })
        );
      }
    },
  },
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
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: var(--radius);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.back-btn:hover {
  background: var(--background);
  color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.back-btn ion-icon {
  font-size: 20px;
}

.header-left h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-left p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
}

/* Security Sections */
.security-sections {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.security-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.header-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: var(--radius);
}

.header-icon ion-icon {
  font-size: 24px;
}

.card-header h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.card-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.card-content {
  padding: 24px;
}

/* Login Methods */
.login-methods {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.login-method {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.login-method:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow);
}

.method-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.method-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius);
  color: white;
}

.method-icon.google {
  background: #4285f4;
}

.method-icon.microsoft {
  background: #00a1f1;
}

.method-icon ion-icon {
  font-size: 20px;
}

.method-details h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.method-details p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.method-toggle {
  flex-shrink: 0;
}

/* Connected Services */
.connected-services {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.service-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.service-item:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow);
}

.service-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.service-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius);
  color: white;
}

.service-icon.github {
  background: #333;
}

.service-icon.vercel {
  background: #000;
}

.service-icon ion-icon {
  font-size: 20px;
}

.service-details h4 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.service-details p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.service-status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.connected {
  background: #dcfce7;
  color: var(--success-color);
}

.connect-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: none;
  border-radius: var(--radius);
  background: var(--primary-color);
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.connect-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.connect-btn ion-icon {
  font-size: 16px;
}

/* Security Actions */
.security-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  justify-content: center;
  text-align: center;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border: 1px solid var(--primary-color);
  box-shadow: var(--shadow);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn.secondary:hover {
  background: var(--background);
  border-color: var(--secondary-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.danger {
  background: #fef2f2;
  color: var(--danger-color);
  border: 1px solid #fecaca;
  box-shadow: var(--shadow);
}

.action-btn.danger:hover {
  background: #fee2e2;
  border-color: var(--danger-color);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn ion-icon {
  font-size: 16px;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }

  .status-badge.connected {
    background: #065f46;
    color: #10b981;
  }

  .action-btn.danger {
    background: #7f1d1d;
    color: #f87171;
    border-color: #991b1b;
  }

  .action-btn.danger:hover {
    background: #991b1b;
    border-color: #dc2626;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .card-header,
  .card-content {
    padding: 20px;
  }

  .login-method,
  .service-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
  }

  .method-toggle,
  .service-status {
    align-self: stretch;
    display: flex;
    justify-content: center;
  }

  .security-actions {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .header-left h1 {
    font-size: 24px;
  }

  .card-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
  }
}
</style>
