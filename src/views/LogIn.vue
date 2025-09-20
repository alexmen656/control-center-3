<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-content">
      <!-- Background Pattern -->
      <div class="background-pattern"></div>
      
      <!-- Main Container -->
      <div class="login-container">
        <div class="login-card">
          
          <!-- Logo Section -->
          <div class="logo-section">
            <picture>
              <source
                media="(min-width:465px)"
                srcset="/assets/logo_inline_large.png"
              />
              <source
                media="(max-width:465px)"
                srcset="/assets/logo_block_large.png"
              />
              <img
                src="/assets/logo_inline_large.png"
                alt="Control Center Logo"
                class="logo-image"
              />
            </picture>
            <h1 class="welcome-title" v-if="!createPasswordView">Welcome Back</h1>
            <h1 class="welcome-title" v-else>Complete Setup</h1>
            <p class="welcome-subtitle" v-if="!createPasswordView">Sign in to your account</p>
            <p class="welcome-subtitle" v-else>Create a password for your account</p>
          </div>

          <!-- Error Message -->
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

          <!-- Password Creation Form -->
          <div v-if="createPasswordView" class="form-section">
            <div class="input-group">
              <div class="custom-input-wrapper">
                <label class="input-label">Password *</label>
                <div class="input-container">
                  <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                  <input
                    type="password"
                    v-model="g_password"
                    placeholder="Enter your password"
                    class="custom-input"
                  />
                </div>
              </div>

              <div class="custom-input-wrapper">
                <label class="input-label">Confirm Password *</label>
                <div class="input-container">
                  <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                  <input
                    type="password"
                    v-model="g_confirmPassword"
                    placeholder="Confirm your password"
                    class="custom-input"
                  />
                </div>
              </div>
            </div>

            <button 
              @click="onSignUp()" 
              type="submit" 
              class="primary-button"
            >
              <ion-icon name="checkmark-circle" class="button-icon"></ion-icon>
              Complete Setup
            </button>
          </div>

          <!-- Login Form -->
          <div v-else class="form-section">
            <form @submit.prevent="login">
              <div class="input-group">
                <div class="custom-input-wrapper">
                  <label class="input-label">Username</label>
                  <div class="input-container">
                    <ion-icon name="person-outline" class="input-icon"></ion-icon>
                    <input
                      v-model="username"
                      name="username"
                      type="text"
                      spellcheck="false"
                      autocapitalize="off"
                      @input="
                        username = $event.target.value;
                        showUsernameError = false;
                        usernameError = '';
                      "
                      required
                      placeholder="Enter your username"
                      class="custom-input"
                    />
                  </div>
                </div>

                <div class="custom-input-wrapper">
                  <label class="input-label">Password</label>
                  <div class="input-container">
                    <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                    <input
                      v-model="password"
                      name="password"
                      type="password"
                      @input="
                        password = $event.target.value;
                        showPasswordError = false;
                        passwordError = '';
                      "
                      required
                      placeholder="Enter your password"
                      class="custom-input"
                    />
                  </div>
                </div>
              </div>

              <!-- Main Actions -->
              <div class="button-group">
                <button 
                  @click="onLogin()" 
                  type="submit" 
                  class="primary-button"
                >
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

              <!-- Divider -->
              <div class="divider">
                <span class="divider-text">or continue with</span>
              </div>

              <!-- Social Login -->
              <div class="social-buttons">
                <button
                  @click="continueWithGoogle()"
                  class="social-button google-button"
                >
                  <img height="20" src="/assets/g-logo3.png" alt="Google" class="social-icon" />
                  Google
                </button>

                <button
                  @click="loginWM"
                  class="social-button microsoft-button"
                >
                  <svg
                    height="18"
                    aria-hidden="true"
                    viewBox="0 0 25 25"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    class="social-icon"
                  >
                    <path d="M11.5216 0.5H0V11.9067H11.5216V0.5Z" fill="#f25022"></path>
                    <path d="M24.2418 0.5H12.7202V11.9067H24.2418V0.5Z" fill="#7fba00"></path>
                    <path d="M11.5216 13.0933H0V24.5H11.5216V13.0933Z" fill="#00a4ef"></path>
                    <path d="M24.2418 13.0933H12.7202V24.5H24.2418V13.0933Z" fill="#ffb900"></path>
                  </svg>
                  Microsoft
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
          <p class="footer-text">
            © 2025 Control Center. All rights reserved.
          </p>
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
  //location.href = "/home";
}

interface VerificationData {
  verification_email: string;
  verification_name: string;
  verification_token: string;
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
    };
  },
  components: {},
  methods: {
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
                "login.php",
                qs.stringify({
                  email: this.user.email,
                  loginWithGoogle: "loginWithGoogle",
                })
              )
              .then(
                (res) => {
                  console.log(res.data);
                  if (res.data.token) {
                    localStorage.setItem("token", res.data.token);
                    // Check for assigned project and redirect accordingly
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
      if(this.loginWith == "google") {
        lwg = "true";
      } else if(this.loginWith == "microsoft") {
        lwg = "Microsoft";
      }

      axios
        .post(
          "sign_up.php",
          qs.stringify({
            first_name: this.user.givenName,
            last_name: this.user.familyName,
            profile_img: this.user.imageUrl.replace("s96", "s512"),
            email_adress: this.user.email,
            password: this.g_password,
            login_with_google: lwg,
          })
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
            "login.php",
            this.$qs.stringify({
              email: this.username,
              password: this.password,
            })
          )
          .then(
            (res) => {
              if (res.data.token) {
                localStorage.setItem("token", res.data.token);
                console.log(localStorage.getItem("token"));
                // Check for assigned project and redirect accordingly
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
          scopes: ["user.read"], // Berechtigungen, die du benötigst
        };
        await msalInstance.loginPopup(loginRequest);//const loginResponse = 
        //console.log("Login erfolgreich:", loginResponse);

        // Rufe das Access-Token ab
        const tokenResponse = await msalInstance.acquireTokenSilent({
          scopes: ["User.Read"],
          account: msalInstance.getAllAccounts()[0], // Verwende das erste Konto, das angemeldet ist
        });

        //console.log("Access-Token erhalten:", tokenResponse.accessToken);

        // Rufe automatisch Benutzerinformationen ab
        const userData = await this.fetchUserData(tokenResponse.accessToken);
        //console.log("Benutzerdaten:", userData);

        // Setze die Benutzerdaten
        this.user.email = userData.mail || userData.userPrincipalName;
        this.user.givenName = userData.givenName;
        this.user.familyName = userData.surname;
        this.user.imageUrl = userData.photo || "";

        // Überprüfe, ob die E-Mail existiert
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
                  "login.php",
                  qs.stringify({
                    email: this.user.email,
                    loginWithMicrosoft: "microsoft",
                  })
                )
                .then(
                  (res) => {
                    console.log(res.data);
                    if (res.data.token) {
                      localStorage.setItem("token", res.data.token);
                      // Check for assigned project and redirect accordingly
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
    await msalInstance.initialize(); // Initialisiere hier

    // Stelle sicher, dass die MSAL-Instanz initialisiert wird
    if (!msalInstance.getAllAccounts().length) {
      await msalInstance.initialize(); // Initialisiere hier
    }
  },
});
</script>

<style scoped>
/* Brand Colors */
:root {
  --brand-red: #e53e3e;
  --brand-red-light: #fc8181;
  --brand-red-dark: #c53030;
  --brand-gray: #f7fafc;
  --brand-gray-dark: #2d3748;
  --brand-text: #2d3748;
  --brand-text-light: #718096;
}

/* Main Container Styles */
.login-content {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
}

.background-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    radial-gradient(circle at 20% 50%, rgba(229, 62, 62, 0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(229, 62, 62, 0.05) 0%, transparent 50%),
    radial-gradient(circle at 40% 80%, rgba(229, 62, 62, 0.03) 0%, transparent 50%);
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
  max-width: 600px; /* Increased width */
  margin: 0 auto;
}

.login-card {
  width: 100%;
  max-width: 450px; /* Increased width */
  background: white;
  border-radius: 24px;
  padding: 3rem 2.5rem; /* Increased padding */
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.1),
    0 10px 20px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Logo Section */
.logo-section {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo-image {
  max-width: 220px; /* Increased logo size */
  height: auto;
  margin-bottom: 1.5rem;
}

.welcome-title {
  font-size: 2rem; /* Increased font size */
  font-weight: 700;
  color: var(--brand-text);
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.025em;
}

.welcome-subtitle {
  font-size: 1.1rem; /* Increased font size */
  color: var(--brand-text-light);
  margin: 0;
  font-weight: 400;
}

/* Error Styles */
.error-container {
  margin-bottom: 1.5rem;
}

.error-card {
  margin: 0;
  background: rgba(229, 62, 62, 0.1);
  border: 1px solid rgba(229, 62, 62, 0.2);
  border-radius: 12px;
  padding: 1rem;
}

.error-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.error-icon {
  color: var(--brand-red);
  font-size: 1.25rem;
  flex-shrink: 0;
}

.error-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--brand-red);
}

.error-content p {
  margin: 0;
  font-size: 0.85rem;
  color: var(--brand-red);
  opacity: 0.9;
}

/* Form Styles */
.form-section {
  width: 100%;
}

.input-group {
  margin-bottom: 2rem; /* Increased spacing */
}

.custom-input-wrapper {
  width: 100%;
  margin-bottom: 1.5rem; /* Increased spacing */
}

.input-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--brand-text);
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
}

.custom-input {
  width: 100%;
  height: 52px; /* Increased height */
  padding: 0 1rem 0 3rem; /* More padding */
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  color: var(--brand-text);
  transition: all 0.3s ease;
  outline: none;
}

.custom-input::placeholder {
  color: var(--brand-text-light);
  opacity: 0.7;
}

.custom-input:focus {
  border-color: var(--brand-red);
  box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.custom-input:hover {
  border-color: #cbd5e0;
}

/* Button Styles */
.button-group {
  margin-bottom: 2rem; /* Increased spacing */
}

.primary-button {
  width: 100%;
  height: 52px; /* Increased height */
  background: linear-gradient(135deg, var(--brand-red), var(--brand-red-dark));
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 1rem;
  box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
}

.primary-button:hover {
  background: linear-gradient(135deg, var(--brand-red-dark), #b91c1c);
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(229, 62, 62, 0.4);
}

.primary-button:active {
  transform: translateY(0);
}

.secondary-button {
  width: 100%;
  height: 52px; /* Increased height */
  background: white;
  border: 2px solid var(--brand-red);
  border-radius: 12px;
  color: var(--brand-red);
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.secondary-button:hover {
  background: var(--brand-red);
  color: white;
  transform: translateY(-1px);
}

.signup-link {
  text-decoration: none;
}

.button-icon {
  font-size: 1.1rem;
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  margin: 2rem 0; /* Increased spacing */
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
  padding: 0 1.5rem; /* Increased padding */
  font-size: 0.9rem;
  font-weight: 500;
  white-space: nowrap;
}

/* Social Buttons */
.social-buttons {
  display: flex;
  flex-direction: column;
  gap: 1rem; /* Increased gap */
}

.social-button {
  width: 100%;
  height: 52px; /* Increased height */
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  color: var(--brand-text);
  font-size: 1rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem; /* Increased gap */
  cursor: pointer;
  transition: all 0.3s ease;
}

.social-button:hover {
  border-color: #cbd5e0;
  background: #f7fafc;
  transform: translateY(-1px);
}

.social-icon {
  flex-shrink: 0;
}

.google-button:hover {
  border-color: #4285f4;
  background: rgba(66, 133, 244, 0.05);
}

.microsoft-button:hover {
  border-color: #00a4ef;
  background: rgba(0, 164, 239, 0.05);
}

/* Footer */
.footer-section {
  margin-top: 2.5rem; /* Increased spacing */
  text-align: center;
}

.footer-text {
  font-size: 0.85rem; /* Slightly larger */
  color: var(--brand-text-light);
  margin: 0;
  line-height: 1.5; /* Better line height */
}

/* Responsive Design */
@media (max-width: 600px) {
  .login-container {
    padding: 1.5rem 1rem;
    max-width: 100%;
  }
  
  .login-card {
    padding: 2.5rem 2rem;
    border-radius: 20px;
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

/* Animation */
.login-card {
  animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Focus and Accessibility */
.custom-input:focus + .input-icon,
.input-container:focus-within .input-icon {
  color: var(--brand-red);
}

/* Remove iOS input styling */
.custom-input {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}

/* Prevent zoom on iOS */
@media screen and (max-width: 767px) {
  .custom-input {
    font-size: 16px;
  }
}
</style>
