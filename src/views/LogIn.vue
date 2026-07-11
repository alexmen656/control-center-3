<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-content">
      <div class="background-pattern"></div>
      <div class="login-container">
        <div class="login-card">
          <div class="logo-section">
            <template v-if="isCustomLogin && customLoginConfig?.logo_url">
              <img :src="customLoginConfig.logo_url" :alt="companyName + ' Logo'" class="logo-image custom-logo" />
            </template>
            <template v-else>
              <img src="/assets/brand/fringelo-wordmark.svg" alt="Fringelo" class="logo-wordmark" />
            </template>
            <p class="welcome-subtitle" v-if="!createPasswordView">
              {{ isCustomLogin ? 'Sign in to ' + companyName : 'Sign in to your account' }}
            </p>
          </div>

          <div v-if="errorMessage" class="error-container">
            <div class="error-card">
              <div class="error-content">
                <ion-icon name="alert-circle" class="error-icon"></ion-icon>
                <div>
                  <h3>Error</h3>
                  <p>{{ errorMessage }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-if="createPasswordView" class="form-section">
            <div class="input-group">
              <div class="custom-input-wrapper">
                <label class="input-label">Password *</label>
                <div class="input-container">
                  <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                  <input type="password" v-model="g_password" placeholder="Enter your password" class="custom-input" />
                </div>
              </div>

              <div class="custom-input-wrapper">
                <label class="input-label">Confirm Password *</label>
                <div class="input-container">
                  <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                  <input type="password" v-model="g_confirmPassword" placeholder="Confirm your password"
                    class="custom-input" />
                </div>
              </div>
            </div>

            <button @click="onSignUp()" type="submit" class="primary-button">
              <ion-icon name="checkmark-circle" class="button-icon"></ion-icon>
              Complete Setup
            </button>
          </div>

          <div v-else class="form-section">
            <form @submit.prevent="login">
              <div class="input-group">
                <div class="custom-input-wrapper">
                  <label class="input-label">Email</label>
                  <div class="input-container">
                    <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                    <input v-model="username" name="username" type="email" spellcheck="false" autocapitalize="off"
                      @input="
                        username = $event.target.value;
                      showUsernameError = false;
                      usernameError = '';
                      " required placeholder="Enter your email" class="custom-input" />
                  </div>
                </div>

                <div class="custom-input-wrapper">
                  <label class="input-label">Password</label>
                  <div class="input-container">
                    <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                    <input v-model="password" name="password" type="password" @input="
                      password = $event.target.value;
                    showPasswordError = false;
                    passwordError = '';
                    " required placeholder="Enter your password" class="custom-input" />
                  </div>
                </div>
              </div>

              <div class="button-group">
                <button @click="onLogin()" type="submit" class="primary-button">
                  <ion-icon name="log-in" class="button-icon"></ion-icon>
                  Sign In
                </button>

                <router-link to="/signup" class="signup-link">
                  <button class="secondary-button">
                    <ion-icon name="person-add" class="button-icon"></ion-icon>
                    Create Account
                  </button>
                </router-link>
              </div>

              <div class="divider">
                <span class="divider-text">or continue with</span>
              </div>

              <div class="social-buttons">
                <button @click="continueWithGoogle()" class="social-button google-button">
                  <img height="24" src="/assets/g-logo3.png" alt="Google" class="social-icon" />
                  Google
                </button>

                <button @click="loginWM" class="social-button microsoft-button">
                  <svg height="18" aria-hidden="true" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="social-icon">
                    <path d="M11.5216 0.5H0V11.9067H11.5216V0.5Z" fill="#f25022"></path>
                    <path d="M24.2418 0.5H12.7202V11.9067H24.2418V0.5Z" fill="#7fba00"></path>
                    <path d="M11.5216 13.0933H0V24.5H11.5216V13.0933Z" fill="#00a4ef"></path>
                    <path d="M24.2418 13.0933H12.7202V24.5H24.2418V13.0933Z" fill="#ffb900"></path>
                  </svg>
                  Microsoft
                </button>
              </div>
            </form>
            <div class="footer-section">
              <p class="footer-text">
                © 2026 {{ isCustomLogin ? companyName : 'Fringelo' }}. All rights reserved.
              </p>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import axios from "axios";
import qs from "qs";
import { defineComponent } from "vue";
import { GoogleAuth } from "@codetrix-studio/capacitor-google-auth";
import { msalInstance } from "@/msalConfig";

try {
  GoogleAuth.initialize({
    clientId:
      "706582238302-k3e6bqv81en6u97gf8l5pq883p773236.apps.googleusercontent.com",
    scopes: ["profile", "email"],
    grantOfflineAccess: true,
  });
} catch {
  console.log("error");
}

if (localStorage.getItem("token")) {
  //location.href = "/projects";
}

interface VerificationData {
  verification_email: string;
  verification_name: string;
  verification_token: string;
}

interface CustomLoginConfig {
  domain: string;
  primary_color: string;
  logo_url: string;
  company_name: string;
  project_name: string;
}

export default defineComponent({
  data() {
    return {
      username: "",
      password: "",
      errorMessage: "",
      createPasswordView: false,
      user: {
        email: "",
        givenName: "",
        familyName: "",
        imageUrl: "",
      },
      g_password: "",
      g_confirmPassword: "",
      loginWith: "",
      customLoginConfig: null as CustomLoginConfig | null,
      isCustomLogin: false,
    };
  },
  computed: {
    primaryColor(): string {
      return this.customLoginConfig?.primary_color || '#f97316';
    },
    logoUrl(): string {
      return this.customLoginConfig?.logo_url || '/assets/logo_inline_large.png';
    },
    companyName(): string {
      return this.customLoginConfig?.company_name || 'Fringelo';
    }
  },
  components: {},
  methods: {
    async checkCustomLoginDomain() {
      const currentDomain = window.location.hostname;

      if (currentDomain === 'localhost') {
        return;
      }

      try {
        const res = await this.$axios.get(`v2/custom-login-config?domain=${currentDomain}`);
        if (res.data.success && res.data.config) {
          this.customLoginConfig = res.data.config;
          this.isCustomLogin = true;
          this.applyCustomStyles();
        }
      } catch (e) {
        console.log('No custom login config found for domain:', currentDomain);
      }
    },
    applyCustomStyles() {
      if (!this.customLoginConfig) return;

      const root = document.documentElement;
      root.style.setProperty('--brand-orange', this.primaryColor);
      root.style.setProperty('--brand-orange-light', this.lightenColor(this.primaryColor, 30));
      root.style.setProperty('--brand-orange-dark', this.darkenColor(this.primaryColor, 20));
    },
    lightenColor(hex: string, percent: number): string {
      const num = parseInt(hex.replace('#', ''), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.min(255, (num >> 16) + amt);
      const G = Math.min(255, ((num >> 8) & 0x00FF) + amt);
      const B = Math.min(255, (num & 0x0000FF) + amt);
      return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    },
    darkenColor(hex: string, percent: number): string {
      const num = parseInt(hex.replace('#', ''), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.max(0, (num >> 16) - amt);
      const G = Math.max(0, ((num >> 8) & 0x00FF) - amt);
      const B = Math.max(0, (num & 0x0000FF) - amt);
      return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    },
    async continueWithGoogle() {
      try {
        this.user = await GoogleAuth.signIn();
      } catch {
        console.log("error");
      }

      await axios
        .post(
          "user.php",
          qs.stringify({
            checkEmailExists: "checkEmailExists",
            email: this.user.email,
          })
        )
        .then((res) => {
          if (res.data.value == true) {
            axios
              .post(
                "v2/auth/login",
                {
                  email: this.user.email,
                  loginWithGoogle: "loginWithGoogle",
                }
              )
              .then(
                (res) => {
                  console.log(res.data);
                  if (res.data.token) {
                    localStorage.setItem("token", res.data.token);
                    if (res.data.assigned_project) {
                      location.href = `/project/${res.data.assigned_project}`;
                    } else {
                      location.href = "/";
                    }
                  } else if (res.data.errorMessage) {
                    this.errorMessage = res.data.errorMessage;
                  } else if (res.data.command) {
                    if (res.data.command == "verify-ip") {
                      this.verifyIP(res.data);
                    }
                    this.errorMessage = "send";
                  }
                },
                (err) => {
                  console.log(err);
                  this.errorMessage = "Cannot connect to server";
                }
              );
          } else if (res.data.value == false) {
            this.loginWith = "google";
            this.createPasswordView = true;
          }
        });
    },
    async onSignUp() {
      if (
        this.g_password.trim() === "" ||
        this.g_confirmPassword.trim() === ""
      ) {
        this.errorMessage = "Please fill in all required fields.";
        return;
      }

      if (this.g_password !== this.g_confirmPassword) {
        this.errorMessage = "Passwords do not match.";
        return;
      }

      let lwg = "false";
      if (this.loginWith == "google") {
        lwg = "true";
      } else if (this.loginWith == "microsoft") {
        lwg = "Microsoft";
      }

      axios
        .post(
          "v2/auth/sign-up",
          {
            first_name: this.user.givenName,
            last_name: this.user.familyName,
            profile_img: this.user.imageUrl.replace("s96", "s512"),
            email_adress: this.user.email,
            password: this.g_password,
            login_with_google: lwg,
          }
        )
        .then((res) => {
          if (res.data.token) {
            localStorage.setItem("token", res.data.token);
            location.href = "/pending-verification";
          } else {
            this.errorMessage =
              "Sorry, an error occurred. Please try again later.";
          }
        });
    },
    async onLogin() {
      if (this.username == "") {
        this.errorMessage = "Field Username mustn't be empty.";
      } else if (this.username.length < 3 && this.username != "") {
        this.errorMessage = "Username to short";
      } else {
        await axios
          .post(
            "v2/auth/login",
            {
              email: this.username,
              password: this.password,
            }
          )
          .then(
            (res) => {
              if (res.data.token) {
                localStorage.setItem("token", res.data.token);
                console.log(localStorage.getItem("token"));
                if (res.data.assigned_project) {
                  location.href = `/project/${res.data.assigned_project}`;
                } else {
                  location.href = "/";
                }
              } else if (res.data.errorMessage) {
                this.errorMessage = res.data.errorMessage;
              } else if (res.data.command) {
                if (res.data.command == "verify-ip") {
                  this.verifyIP(res.data);
                }
                this.errorMessage = "send";
              }
            },
            (err) => {
              console.log(err);
              this.errorMessage = "Cannot connect to server";
            }
          );
      }
    },
    verifyIP(data: VerificationData) {
      localStorage.setItem("verification_email", data.verification_email);
      localStorage.setItem("verification_name", data.verification_name);
      localStorage.setItem("verification_token", data.verification_token);
      location.href = "/login/verification/";
    },
    async loginWM() {
      if (this.msalInteractionInProgress) {
        console.log("Eine Interaktion ist bereits im Gange.");
        return;
      }

      this.msalInteractionInProgress = true;

      try {
        const loginRequest = {
          scopes: ["user.read"],
        };
        await msalInstance.loginPopup(loginRequest);

        const tokenResponse = await msalInstance.acquireTokenSilent({
          scopes: ["User.Read"],
          account: msalInstance.getAllAccounts()[0],
        });

        const userData = await this.fetchUserData(tokenResponse.accessToken);

        this.user.email = userData.mail || userData.userPrincipalName;
        this.user.givenName = userData.givenName;
        this.user.familyName = userData.surname;
        this.user.imageUrl = userData.photo || "";

        await axios
          .post(
            "user.php",
            qs.stringify({
              checkEmailExists: "checkEmailExists",
              email: this.user.email,
            })
          )
          .then((res) => {
            if (res.data.value == true) {
              axios
                .post(
                  "v2/auth/login",
                  {
                    email: this.user.email,
                    loginWithMicrosoft: "microsoft",
                  }
                )
                .then(
                  (res) => {
                    console.log(res.data);
                    if (res.data.token) {
                      localStorage.setItem("token", res.data.token);
                      if (res.data.assigned_project) {
                        location.href = `/project/${res.data.assigned_project}`;
                      } else {
                        location.href = "/";
                      }
                    } else if (res.data.errorMessage) {
                      this.errorMessage = res.data.errorMessage;
                    } else if (res.data.command) {
                      if (res.data.command == "verify-ip") {
                        this.verifyIP(res.data);
                      }
                      this.errorMessage = "send";
                    }
                  },
                  (err) => {
                    console.log(err);
                    this.errorMessage = "Cannot connect to server";
                  }
                );
            } else if (res.data.value == false) {
              this.loginWith = "microsoft";
              this.createPasswordView = true;
            }
          });
      } catch (error) {
        console.error("Fehler beim Login:", error);
      } finally {
        this.msalInteractionInProgress = false;
      }
    },
    async fetchUserData(accessToken) {
      try {
        const response = await fetch("https://graph.microsoft.com/v1.0/me", {
          headers: {
            Authorization: `Bearer ${accessToken}`,
          },
        });

        if (!response.ok)
          throw new Error("Fehler beim Abrufen der Benutzerinformationen");

        const userData = await response.json();
        return userData;
      } catch (error) {
        console.error("Fehler beim Abrufen der Benutzerdaten:", error);
      }
    },
  },
  async mounted() {
    await this.checkCustomLoginDomain();

    await msalInstance.initialize();

    if (!msalInstance.getAllAccounts().length) {
      await msalInstance.initialize();
    }
  },
});
</script>

<style scoped>
:global(:root) {
  --brand-orange: #f97316;
  --brand-orange-light: #fb923c;
  --brand-orange-dark: #ea580c;
  --brand-gray: #f7fafc;
  --brand-gray-dark: #2d3748;
  --brand-text: #2d3748;
  --brand-text-light: #718096;
}

.login-content {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.background-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image:
    radial-gradient(circle at 20% 50%, rgba(249, 115, 22, 0.06) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(249, 115, 22, 0.04) 0%, transparent 50%),
    radial-gradient(circle at 40% 80%, rgba(249, 115, 22, 0.03) 0%, transparent 50%);
  z-index: 0;
}

.login-container {
  position: relative;
  z-index: 1;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 2rem 1rem;
  max-width: 600px;
  margin: 0 auto;
}

.login-card {
  width: 100%;
  max-width: 450px;
  background: #ffffff;
  border-radius: 16px;
  padding: 3rem 2.5rem 1.5rem 2.5rem;
  box-shadow:
    0 1px 3px rgba(15, 23, 42, 0.06),
    0 10px 30px -12px rgba(15, 23, 42, 0.12);
  border: 1px solid #eef2f7;
}

.logo-section {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo-image {
  max-width: 220px;
  height: auto;
  margin-bottom: 1.5rem;
}

.welcome-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.025em;
}

.welcome-subtitle {
  font-size: 1.05rem;
  color: #64748b;
  margin: 0;
  font-weight: 400;
  margin-top: 0.5rem;
  margin-bottom: 2rem;
}

.error-container {
  margin-bottom: 1.5rem;
}

.error-card {
  margin: 0;
  background: rgba(249, 115, 22, 0.08);
  border: 1px solid rgba(249, 115, 22, 0.2);
  border-radius: 10px;
  padding: 1rem;
}

.error-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.error-icon {
  color: var(--brand-orange-dark);
  font-size: 1.25rem;
  flex-shrink: 0;
}

.error-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--brand-orange-dark);
}

.error-content p {
  margin: 0;
  font-size: 0.85rem;
  color: var(--brand-orange-dark);
  opacity: 0.9;
}

.form-section {
  width: 100%;
}

.input-group {
  margin-bottom: 2rem;
}

.custom-input-wrapper {
  width: 100%;
  margin-bottom: 1rem;
}

.input-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #334155;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  color: var(--brand-text-light);
  font-size: 1.1rem;
  z-index: 2;
  transition: color 0.2s ease;
}

.custom-input {
  width: 100%;
  height: 52px;
  padding: 0 1rem 0 3rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  background: #ffffff;
  color: #1a202c;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  outline: none;
}

.custom-input::placeholder {
  color: #a0aec0;
  opacity: 0.8;
}

.custom-input:focus {
  border-color: var(--brand-orange);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
  background: #ffffff;
}

.custom-input:hover {
  border-color: #cbd5e0;
}

.button-group {
  margin-bottom: 2rem;
}

.primary-button {
  width: 100%;
  height: 52px;
  background: var(--brand-orange);
  border: none;
  border-radius: 10px;
  color: #ffffff;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  margin-bottom: 1rem;
}

.primary-button:hover {
  background: var(--brand-orange-dark);
}

.primary-button:active {
  background: var(--brand-orange-dark);
}

.secondary-button {
  width: 100%;
  height: 52px;
  background: #ffffff;
  border: 1.5px solid var(--brand-orange);
  border-radius: 10px;
  color: var(--brand-orange-dark);
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.secondary-button:hover {
  background: var(--brand-orange);
  color: #ffffff;
}

.signup-link {
  text-decoration: none;
}

.button-icon {
  font-size: 1.1rem;
}

.divider {
  display: flex;
  align-items: center;
  margin: 2rem 0;
  color: var(--brand-text-light);
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider-text {
  padding: 0 1.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  white-space: nowrap;
}

.social-buttons {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.social-button {
  width: 100%;
  height: 52px;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  color: #334155;
  font-size: 1rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.social-button:hover {
  border-color: #cbd5e0;
  background: #f8fafc;
}

.social-icon {
  flex-shrink: 0;
}

.google-button:hover {
  border-color: #4285f4;
  background: rgba(66, 133, 244, 0.06);
}

.microsoft-button:hover {
  border-color: #00a4ef;
  background: rgba(0, 164, 239, 0.06);
}

.footer-section {
  margin-top: 1.5rem;
  text-align: center;
}

.footer-text {
  font-size: 0.85rem;
  color: var(--brand-text-light);
  margin: 0;
  line-height: 1.5;
}

@media (max-width: 600px) {
  .login-container {
    padding: 1.5rem 1rem;
    max-width: 100%;
  }

  .login-card {
    padding: 2.5rem 2rem;
    border-radius: 14px;
    max-width: 100%;
  }

  .logo-image {
    max-width: 180px;
  }

  .welcome-title {
    font-size: 1.7rem;
  }
}

@media (max-height: 700px) {
  .login-container {
    justify-content: flex-start;
    padding-top: 2rem;
  }

  .logo-section {
    margin-bottom: 2rem;
  }

  .logo-image {
    max-width: 160px;
    margin-bottom: 1rem;
  }

  .welcome-title {
    font-size: 1.6rem;
  }
}

.custom-input:focus+.input-icon,
.input-container:focus-within .input-icon {
  color: var(--brand-orange);
}

.custom-input {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}

@media screen and (max-width: 767px) {
  .custom-input {
    font-size: 16px;
  }
}

.logo-wordmark {
  display: inline-block;
  height: 50px;
  width: auto;
  margin-bottom: 0;
}

@media (prefers-color-scheme: dark) {
  .login-content {
    background: linear-gradient(135deg, #0f0f0f 0%, #161616 100%);
  }

  .background-pattern {
    background-image:
      radial-gradient(circle at 20% 50%, rgba(249, 115, 22, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(249, 115, 22, 0.07) 0%, transparent 50%),
      radial-gradient(circle at 40% 80%, rgba(249, 115, 22, 0.05) 0%, transparent 50%);
  }

  .login-card {
    background: #1c1c1e;
    border: 1px solid #2c2c2e;
    box-shadow:
      0 1px 3px rgba(0, 0, 0, 0.4),
      0 10px 30px -12px rgba(0, 0, 0, 0.6);
  }

  .welcome-title {
    color: #f4f5f8;
  }

  .welcome-subtitle {
    color: #98999f;
  }

  .input-label {
    color: #d1d5db;
  }

  .custom-input {
    background: #2c2c2e;
    border-color: #3a3a3c;
    color: #f4f5f8;
  }

  .custom-input::placeholder {
    color: #7c7c82;
  }

  .custom-input:hover {
    border-color: #48484a;
  }

  .custom-input:focus {
    background: #2c2c2e;
  }

  .secondary-button {
    background: #1c1c1e;
  }

  .social-button {
    background: #2c2c2e;
    border-color: #3a3a3c;
    color: #f4f5f8;
  }

  .social-button:hover {
    border-color: #48484a;
    background: #333335;
  }

  .divider::before,
  .divider::after {
    background: #3a3a3c;
  }
}
</style>
