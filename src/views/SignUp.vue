<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="logo">
        <picture>
          <source
            media="(min-width:465px)"
            srcset="assets/logo_inline_large.png"
          />
          <source
            media="(max-width:465px)"
            srcset="assets/logo_block_large.png"
          />
          <img
            src="assets/logo_inline_large.png"
            alt="Control Center Logo"
            style="width: auto"
          />
        </picture>
      </div>
      <ion-grid>
        <ion-row>
          <ion-col size-md="2" size="0"></ion-col>
          <ion-col size-md="8" size="12" v-if="createPasswordView">
            <alert-message
              v-if="errorMessage"
              color="danger"
              :message="{ title: 'Error!', content: errorMessage }"
            ></alert-message>
            <ion-item>
              <ion-label position="floating">
                Password<span style="color: red">*</span>
              </ion-label>
              <ion-input
                type="password"
                v-model="g_password"
                :value="g_password"
                @ionInput="g_password = $event.target.value"
                placeholder="Enter your password"
                fill="outline"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="floating"
                >Confirm Password<span style="color: red">*</span></ion-label
              >
              <ion-input
                type="password"
                v-model="g_confirmPassword"
                :value="g_confirmPassword"
                @ionInput="g_confirmPassword = $event.target.value"
                placeholder="Confirm your password"
                fill="outline"
              ></ion-input>
            </ion-item>
            <ion-button @click="onSignUp()" type="submit" expand="block">
              Continue</ion-button
            >
          </ion-col>

          <ion-col size-md="8" size="12" v-else>
            <alert-message
              v-if="errorMessage"
              color="danger"
              :message="{ title: 'Error!', content: errorMessage }"
            ></alert-message>
            <ion-grid>
              <ion-row>
                <ion-col size-md="6" size="12">
                  <ion-item>
                    <ion-label position="floating">
                      First Name<span style="color: red">*</span>
                    </ion-label>
                    <ion-input
                      type="text"
                      v-model="firstName"
                      :value="firstName"
                      @ionInput="firstName = $event.target.value"
                      placeholder="Enter your first name"
                      fill="outline"
                    ></ion-input>
                  </ion-item>
                </ion-col>
                <ion-col size-md="6" size="12">
                  <ion-item>
                    <ion-label position="floating">Last Name</ion-label>
                    <ion-input
                      type="text"
                      v-model="lastName"
                      :value="lastName"
                      @ionInput="lastName = $event.target.value"
                      placeholder="Enter your last name"
                      fill="outline"
                    ></ion-input>
                  </ion-item>
                </ion-col>

                <ion-col size="12">
                  <ion-item>
                    <ion-label position="floating">
                      Email<span style="color: red">*</span>
                    </ion-label>
                    <ion-input
                      type="email"
                      v-model="email"
                      :value="email"
                      @ionInput="email = $event.target.value"
                      placeholder="Enter your email"
                      fill="outline"
                    ></ion-input>
                  </ion-item>
                </ion-col>
                <ion-col size="12">
                  <ion-item>
                    <ion-label position="floating">
                      Password<span style="color: red">*</span>
                    </ion-label>
                    <ion-input
                      type="password"
                      v-model="password"
                      :value="password"
                      @ionInput="password = $event.target.value"
                      placeholder="Enter your password"
                      fill="outline"
                    ></ion-input>
                  </ion-item>
                </ion-col>
                <ion-col size="12">
                  <ion-item>
                    <ion-label position="floating"
                      >Confirm Password<span style="color: red"
                        >*</span
                      ></ion-label
                    >
                    <ion-input
                      type="password"
                      v-model="confirmPassword"
                      :value="confirmPassword"
                      @ionInput="confirmPassword = $event.target.value"
                      placeholder="Confirm your password"
                      fill="outline"
                    ></ion-input>
                  </ion-item>
                </ion-col>
                <ion-col size-md="3" size="0"></ion-col>
                <ion-col size-md="6" size="12">
                  <ion-button
                    size="medium"
                    expand="full"
                    color="primary"
                    type="submit"
                    @click="validateAndSignUp"
                    >Sign Up</ion-button
                  >

                  <router-link to="/login"
                    ><ion-button expand="full">Log In</ion-button></router-link
                  >
                  <ion-button
                    color="light"
                    type="button"
                    expand="block"
                    @click="continueWithGoogle()"
                    ><img
                      height="24"
                      src="/assets/g-logo3.png"
                      alt=""
                    />Continue with Google</ion-button
                  >
                  <ion-button
                    class="lomi"
                    type="button"
                    expand="block"
                    @click="loginWM"
                    ><svg
                      height="20"
                      aria-hidden="true"
                      viewBox="0 0 25 25"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                      itemprop="logo"
                      itemscope="itemscope"
                    >
                      <path
                        d="M11.5216 0.5H0V11.9067H11.5216V0.5Z"
                        fill="#f25022"
                      ></path>
                      <path
                        d="M24.2418 0.5H12.7202V11.9067H24.2418V0.5Z"
                        fill="#7fba00"
                      ></path>
                      <path
                        d="M11.5216 13.0933H0V24.5H11.5216V13.0933Z"
                        fill="#00a4ef"
                      ></path>
                      <path
                        d="M24.2418 13.0933H12.7202V24.5H24.2418V13.0933Z"
                        fill="#ffb900"
                      ></path>
                    </svg>
                    <pre></pre>
                    Continue with Microsoft</ion-button
                  >
                </ion-col>
                <ion-col size-md="3" size="0"></ion-col>
              </ion-row>
            </ion-grid>
          </ion-col>
          <ion-col size-md="2" size="0"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { GoogleAuth } from "@codetrix-studio/capacitor-google-auth";
import AlertMessage from "@/components/AlertMessage.vue";
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
  components: {
    AlertMessage,
  },
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
          scopes: ["user.read"], // Berechtigungen, die du benötigst
        };
        await msalInstance.loginPopup(loginRequest); //const loginResponse =
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
    await msalInstance.initialize(); // Initialisiere hier

    // Stelle sicher, dass die MSAL-Instanz initialisiert wird
    if (!msalInstance.getAllAccounts().length) {
      await msalInstance.initialize(); // Initialisiere hier
    }
  },
});
</script>
<style scoped>
ion-content {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
}

.logo {
  padding: 60px 0;
  text-align: center;
  width: 100%;
  display: flex;
  justify-content: center;
}

.logo > picture {
  width: 40%;
  height: 40%;
}

@media only screen and (max-width: 600px) {
  .logo {
    padding-bottom: 0;
  }

  .logo > picture {
    width: 60%;
    height: 60%;
  }
}

ion-button.lomi {
  --background: black;
  color: white;
}
</style>
