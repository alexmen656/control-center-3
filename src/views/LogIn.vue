<template>
  <div class="ion-page">
    <ion-content>
      <div class="login-logo">
        <ion-title>Control Center</ion-title>
      </div>
      <div v-if="!continueWithGoogleScreen">
        <ion-grid>
          <ion-row>
            <ion-col size="4"></ion-col>
            <ion-col size="4">
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

                  <ion-text color="danger">
                    <p v-show="showUsernameError" padding-left>
                      {{ usernameError }}
                    </p>
                  </ion-text>

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

                  <ion-text color="danger">
                    <p v-show="showPasswordError" padding-left>
                      {{ passwordError }}
                    </p>
                  </ion-text>
                </ion-list>
                <ion-button @click="onLogin()" type="submit" expand="block"
                  >Log In</ion-button
                >
                <router-link to="/signup"
                  ><ion-button @click="onLogin()" type="button" expand="block"
                    >Sign Up</ion-button
                  ></router-link
                >
              </form>
            </ion-col>
            <ion-col size="4"></ion-col>
          </ion-row>
        </ion-grid>
        <!-- <ion-row responsive-sm>
          <ion-col></ion-col>
          <ion-col>
            <ion-button routerLink="/signup" type="button" color="light" expand="block">Signup</ion-button>
          </ion-col>
          <ion-col></ion-col>
        </ion-row>-->
      </div>
    </ion-content>
  </div>
</template>

<style scoped>
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

@media (prefers-color-scheme: dark) {
  ion-list {
    background-color: #121212;
  }
}

ion-list {
  border: none !important;
}

:root {
  --ion-color-secondary: #006600;
  --ion-color-secondary-rgb: 0, 102, 0;
  --ion-color-secondary-contrast: #ffffff;
  --ion-color-secondary-contrast-rgb: 255, 255, 255;
  --ion-color-secondary-shade: #005a00;
  --ion-color-secondary-tint: #1a751a;
}
</style>

<script lang="ts">
import axios from "axios";
import qs from "qs";
import {
  IonButton,
  IonLabel,
  IonItem,
  IonRow,
  IonCol,
  IonText,
  IonContent,
  IonList,
  IonInput,
} from "@ionic/vue";
import { defineComponent } from "vue";

if (localStorage.getItem("token")) {
  //  location.href = '/home';
}

export default defineComponent({
  data() {
    return {
      username: "",
      password: "",
      submitted: false,
      showUsernameError: false,
      showPasswordError: false,
      usernameError: "",
      passwordError: "",
      continueWithGoogleScreen: false,
      hasError: false,
      errorMessage: "",
    };
  },
  components: {
    IonButton,
    IonLabel,
    IonItem,
    IonRow,
    IonCol,
    IonText,
    IonContent,
    IonList,
    IonInput,
  },
  methods: {
    async onLogin() {
      if (this.username == "") {
        this.showUsernameError = true;
        this.usernameError = "Field Username mustn't be empty.";
      } else if (this.username.length < 3 && this.username != "") {
        this.showUsernameError = true;
        this.usernameError = "Username to short";
      } else {
        const options = {
          method: "POST",
          data: qs.stringify({ email: this.username, password: this.password }),
          url: "/control-center/login.php",
        };
        await axios(options).then(
          (res) => {
            console.log(res.data);
            if (res.data.token) {
              localStorage.setItem("token", res.data.token);
              console.log(localStorage.getItem("token"));
              location.href = "/";
            } else if (res.data.errorMessage) {
              this.hasError = true;
              this.errorMessage = res.data.errorMessage;
              this.showUsernameError = true;
              this.usernameError = "Username or Password Incorrect";
              this.showPasswordError = true;
              this.passwordError = "Username or Password Incorrect";
            } else if (res.data.command) {
              if (res.data.command == "verify-ip") {
                localStorage.setItem(
                  "verification_email",
                  res.data.verification_email
                );
                localStorage.setItem(
                  "verification_name",
                  res.data.verification_name
                );
                localStorage.setItem(
                  "verification_token",
                  res.data.verification_token
                );
                location.href = "/login/verification/";
              }
              this.hasError = true;
              this.errorMessage = "send";
            }
          },
          (err) => {
            console.log(err);
            this.hasError = true;
            this.errorMessage = "Cannot connect to server";
          }
        );
      }
    },
  },
});
</script>
