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
          <ion-col size-md="8" size="12">
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
              </ion-row>
            </ion-grid>
          </ion-col>
          <ion-col size-md="2" size="0"></ion-col>

          <ion-col size=""></ion-col>

          <ion-col size-md="5" size="12">
            <ion-button
              size="medium"
              expand="full"
              color="primary"
              type="submit"
              @click="validateAndSignUp"
              >Sign Up</ion-button
            >
            <div class="line-between">
              <br />
              <hr />
              already have an account ?
              <hr />
              <br />
            </div>
            <router-link to="/login"
              ><ion-button expand="full">Log In</ion-button></router-link
            >
            <ion-item v-if="passwordsDoNotMatch" lines="none" color="danger">
              Passwords do not match
            </ion-item>
          </ion-col>
          <ion-col size=""></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonGrid,
  IonRow,
  IonCol,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
} from "@ionic/vue";
import { defineComponent } from "vue";
import axios from "axios";
import qs from "qs";

export default defineComponent({
  components: {
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonItem,
    IonLabel,
    IonInput,
    IonButton,
  },
  data() {
    return {
      firstName: "",
      lastName: "",
      email: "",
      password: "",
      confirmPassword: "",
      passwordsDoNotMatch: false,
    };
  },
  methods: {
    async validateAndSignUp() {
      if (
        this.firstName.trim() === "" ||
        this.email.trim() === "" ||
        this.password.trim() === ""
      ) {
        alert("Please fill in all required fields.");
        return;
      }

      if (this.password !== this.confirmPassword) {
        this.passwordsDoNotMatch = true;
        return;
      }

      const emailExists = await this.emailAlreadyExists();

      if (emailExists == true) {
        alert("This email has already been used.");
        return;
      }

      this.signUp();
    },
    async emailAlreadyExists() {
      try {
        const response = await axios.post(
          "https://alex.polan.sk/control-center/user.php",
          qs.stringify({
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
      axios
        .post(
          "https://alex.polan.sk/control-center/sign_up.php",
          qs.stringify({
            first_name: this.firstName,
            last_name: this.lastName,
            email_adress: this.email,
            password: this.password,
          })
        )
        .then((res) => {
          if (res.data.token) {
            localStorage.setItem("token", res.data.token);
            location.href = "/pending-verification";
          } else {
            alert("Sorry, an error occurred. Please try again later.");
          }
        });
    },
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

hr {
  background-color: #fff;
  color: #fff;
  width: 30%;
}

.line-between {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin: 0.5rem 0;
}
</style>
