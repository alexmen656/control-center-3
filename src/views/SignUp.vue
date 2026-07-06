<template>
  <ion-page>
    <ion-content :fullscreen="true" class="signup-content">
      <div class="background-pattern"></div>

      <div class="signup-container">
        <div class="signup-card">

          <div class="logo-section">
            <span class="logo">
              Fringelo
            </span>
            <h1 class="welcome-title" v-if="!createPasswordView">Create Account</h1>
            <h1 class="welcome-title" v-else>Complete Setup</h1>
            <p class="welcome-subtitle" v-if="!createPasswordView">Join Fringelo today</p>
            <p class="welcome-subtitle" v-else>Create a password for your account</p>
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

          <div v-else class="form-section">
            <div class="input-group">
              <div class="name-row">
                <div class="custom-input-wrapper">
                  <label class="input-label">First Name *</label>
                  <div class="input-container">
                    <ion-icon name="person-outline" class="input-icon"></ion-icon>
                    <input
                      type="text"
                      v-model="firstName"
                      placeholder="First name"
                      class="custom-input"
                    />
                  </div>
                </div>

                <div class="custom-input-wrapper">
                  <label class="input-label">Last Name</label>
                  <div class="input-container">
                    <ion-icon name="person-outline" class="input-icon"></ion-icon>
                    <input
                      type="text"
                      v-model="lastName"
                      placeholder="Last name"
                      class="custom-input"
                    />
                  </div>
                </div>
              </div>

              <div class="custom-input-wrapper">
                <label class="input-label">Email *</label>
                <div class="input-container">
                  <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                  <input
                    type="email"
                    v-model="email"
                    placeholder="Enter your email"
                    class="custom-input"
                  />
                </div>
              </div>

              <div class="custom-input-wrapper">
                <label class="input-label">Password *</label>
                <div class="input-container">
                  <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                  <input
                    type="password"
                    v-model="password"
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
                    v-model="confirmPassword"
                    placeholder="Confirm your password"
                    class="custom-input"
                  />
                </div>
              </div>
            </div>

            <div class="button-group">
              <button
                @click="validateAndSignUp"
                type="submit"
                class="primary-button"
              >
                <ion-icon name="person-add" class="button-icon"></ion-icon>
                Create Account
              </button>

              <router-link to="/login" class="login-link">
                <button class="secondary-button">
                  <ion-icon name="log-in" class="button-icon"></ion-icon>
                  Already have an account?
                </button>
              </router-link>
            </div>

            <div class="divider">
              <span class="divider-text">or sign up with</span>
            </div>

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
          </div>
        </div>

        <div class="footer-section">
          <p class="footer-text">
            By creating an account, you agree to our Terms of Service and Privacy Policy
          </p>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { GoogleAuth } from "@codetrix-studio/capacitor-google-auth";
import { msalInstance } from "@/msalConfig";
import axios from "axios";
import qs from "qs";


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

export default defineComponent({
  components: {},
  data() {
    return {
      firstName: "",
      lastName: "",
      email: "",
      password: "",
      confirmPassword: "",
      passwordsDoNotMatch: false,
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
    };
  },
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
                    location.href = "/";
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
    async validateAndSignUp() {
      if (
        this.firstName.trim() === "" ||
        this.email.trim() === "" ||
        this.password.trim() === ""
      ) {
        this.errorMessage = "Please fill in all required fields.";
        return;
      }

      if (this.password !== this.confirmPassword) {
        this.errorMessage = "Passwords do not match";
        return;
      }

      const emailExists = await this.emailAlreadyExists();

      if (emailExists == true) {
        this.errorMessage = "This email has already been used.";
        return;
      }

      this.signUp();
    },
    async emailAlreadyExists() {
      try {
        const response = await this.$axios.post(
          "user.php",
          this.$qs.stringify({
            checkEmailExists: "checkEmailExists",
            email: this.email,
          })
        );
        return response.data.value;
      } catch (error) {
        console.error(error);
        return "";
      }
    },
    signUp() {
      this.$axios
        .post(
          "sign_up.php",
          this.$qs.stringify({
            first_name: this.firstName,
            last_name: this.lastName,
            email_adress: this.email,
            password: this.password,
            login_with_google: false,
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
                      location.href = "/";
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
    verifyIP(data) {
      localStorage.setItem("verification_email", data.verification_email);
      localStorage.setItem("verification_name", data.verification_name);
      localStorage.setItem("verification_token", data.verification_token);
      location.href = "/login/verification/";
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
    await msalInstance.initialize();

    if (!msalInstance.getAllAccounts().length) {
      await msalInstance.initialize();
    }
  },
});
</script>
<style scoped>
:root {
  --brand-orange: #f97316;
  --brand-orange-light: #fb923c;
  --brand-orange-dark: #ea580c;
  --brand-gray: #f7fafc;
  --brand-gray-dark: #2d3748;
  --brand-text: #2d3748;
  --brand-text-light: #718096;
}

.signup-content {
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

.signup-container {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 2rem 1rem;
  max-width: 600px;
  margin: 0 auto;
}

.signup-card {
  width: 100%;
  max-width: 500px;
  background: #ffffff;
  border-radius: 16px;
  padding: 3rem 2.5rem;
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
  max-width: 200px;
  height: auto;
  margin-bottom: 1.5rem;
}

.logo {
  display: inline-block;
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

.name-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.custom-input-wrapper {
  width: 100%;
  margin-bottom: 1.5rem;
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

.login-link {
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
  margin-top: 2.5rem;
  text-align: center;
}

.footer-text {
  font-size: 0.85rem;
  color: var(--brand-text-light);
  margin: 0;
  line-height: 1.5;
}

@media (max-width: 600px) {
  .signup-container {
    padding: 1.5rem 1rem;
    max-width: 100%;
  }

  .signup-card {
    padding: 2.5rem 2rem;
    border-radius: 14px;
    max-width: 100%;
  }

  .logo {
    font-size: 36px;
  }

  .welcome-title {
    font-size: 1.7rem;
  }

  .name-row {
    flex-direction: column;
    gap: 0;
  }
}

@media (max-height: 800px) {
  .signup-container {
    justify-content: flex-start;
    padding-top: 2rem;
    padding-bottom: 2rem;
  }

  .logo-section {
    margin-bottom: 2rem;
  }

  .logo {
    font-size: 36px;
    margin-bottom: 1rem;
  }

  .welcome-title {
    font-size: 1.6rem;
  }
}

.custom-input:focus + .input-icon,
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

.logo {
  display: block;
  font-weight: 700;
  font-size: 42px;
  color: var(--brand-orange);
  letter-spacing: -0.8px;
  line-height: 1.2;
  margin-bottom: 2rem;
  transition: color 0.2s ease;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
