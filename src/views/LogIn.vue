<template>
  <div class="ion-page">
    <ion-content style="display: flex; justify-content: center; align-items: center;" :fullscreen="true">
      <div id="container">
      <ion-grid>
        <ion-row>
          <ion-col size-md="4" size="3"></ion-col>

          <ion-col size-md="4" size="6">
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
          </ion-col>
          <ion-col size-md="4" size="3"></ion-col>

          <ion-col size-md="4" size="auto"></ion-col>

          <ion-col size-md="4" size="11" v-if="createPasswordView">
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

          <ion-col size-md="4" size="11" v-else>
            <alert-message
              v-if="errorMessage"
              color="danger"
              :message="{ title: 'Error!', content: errorMessage }"
            ></alert-message>
            <form @submit.prevent="login">
              <ion-list>
                <ion-item>
                  <ion-label position="stacked" color="primary"
                    >Username</ion-label
                  >
                  <ion-input
                    v-model="username"
                    name="username"
                    type="text"
                    spellcheck="false"
                    autocapitalize="off"
                    :value="username"
                    @ionInput="
                      username = $event.target.value;
                      showUsernameError = false;
                      usernameError = '';
                    "
                    required
                    placeholder="John Due"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-label position="stacked" color="primary"
                    >Password</ion-label
                  >
                  <ion-input
                    v-model="password"
                    name="password"
                    type="password"
                    :value="password"
                    @ionInput="
                      password = $event.target.value;
                      showPasswordError = false;
                      passwordError = '';
                    "
                    required
                    placeholder="****"
                  ></ion-input>
                </ion-item>
              </ion-list>
              <ion-button @click="onLogin()" type="submit" expand="block"
                >Log In</ion-button
              >
              <router-link to="/signup"
                ><ion-button type="button" expand="block"
                  >Sign Up</ion-button
                ></router-link
              >
              <ion-button
                color="light"
                type="button"
                expand="block"
                @click="continueWithGoogle()"
                ><img height="24" src="/assets/g-logo3.png" alt="" />Continue
                with Google</ion-button
              >
            </form>
          </ion-col>
          <ion-col size-md="4" size="auto"></ion-col>
        </ion-row>
      </ion-grid>
      </div>
    </ion-content>
  </div>
</template>

<script lang="ts">
import axios from "axios";
import qs from "qs";
import { defineComponent } from "vue";
import { GoogleAuth } from "@codetrix-studio/capacitor-google-auth";
import AlertMessage from "@/components/AlertMessage.vue";

GoogleAuth.initialize({
  clientId:
    "706582238302-k3e6bqv81en6u97gf8l5pq883p773236.apps.googleusercontent.com",
  scopes: ["profile", "email"],
  grantOfflineAccess: true,
});

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
    };
  },
  components: {
    AlertMessage,
  },
  methods: {
    async continueWithGoogle() {
      this.user = await GoogleAuth.signIn();

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

      axios
        .post(
          "sign_up.php",
          qs.stringify({
            first_name: this.user.givenName,
            last_name: this.user.familyName,
            profile_img: this.user.imageUrl.replace("s96", "s512"),
            email_adress: this.user.email,
            password: this.g_password,
            login_with_google: true,
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
            this.$qs.stringify({ email: this.username, password: this.password })
          )
          .then(
            (res) => {
              if (res.data.token) {
                localStorage.setItem("token", res.data.token);
                console.log(localStorage.getItem("token"));
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
      }
    },
    verifyIP(data: VerificationData) {
      localStorage.setItem("verification_email", data.verification_email);
      localStorage.setItem("verification_name", data.verification_name);
      localStorage.setItem("verification_token", data.verification_token);
      location.href = "/login/verification/";
    },
  },
});
</script>

<style scoped>
ion-list {
  background: var(--ion-background-color);
}
#container {
  text-align: center;
  position: absolute;
  left: 0;
  right: 0;
  top: 45%;
  transform: translateY(-50%);
}
.login-logo {
  padding: 20px 0;
  min-height: 200px;
  text-align: center;
  width: 100%;
  display: flex;
  justify-content: center;
}
.login-logo img {
  max-width: 150px;
}
.list {
  margin-bottom: 0;
}

ion-title {
  font-family: Chalkduster;
  font-size: 28px;
  color: #ff0000;
  text-align: left;
  cursor: pointer !important;
  text-align: center;
}

ion-item {
  margin-bottom: 0.5rem; /* 1rem */
}

.logo {
  margin-bottom: 2rem;
}
</style>
