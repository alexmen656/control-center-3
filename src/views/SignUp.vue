<template>
    <ion-page>
      <ion-header>
        <ion-toolbar color="primary">
          <ion-title>Sign Up</ion-title>
        </ion-toolbar>
      </ion-header>
  
      <ion-content class="ion-padding">
        <ion-grid>
          <ion-row>
            <ion-col>
              <ion-item>
                <ion-label position="floating">
                  First Name<span style="color: red;">*</span>
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
            <ion-col>
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
          </ion-row>
        </ion-grid>
  
        <ion-item>
          <ion-label position="floating">
            Email<span style="color: red;">*</span>
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
  
        <ion-item>
          <ion-label position="floating">
            Password<span style="color: red;">*</span>
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
  
        <ion-item>
          <ion-label position="floating">Confirm Password<span style="color: red;">*</span></ion-label>
          <ion-input
            type="password"
            v-model="confirmPassword"
            :value="confirmPassword"
            @ionInput="confirmPassword = $event.target.value"
            placeholder="Confirm your password"
            fill="outline"
          ></ion-input>
        </ion-item>
  
        <ion-button expand="full" color="primary" @click="validateAndSignUp">Sign Up</ion-button>
        <br>already have an account ?<br>
        <router-link to="/login"><ion-button>Log In</ion-button></router-link>
        <ion-item v-if="passwordsDoNotMatch" lines="none" color="danger">
          Passwords do not match
        </ion-item>
      </ion-content>
    </ion-page>
  </template>
  
  
  <script>
  import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, IonItem, IonLabel, IonInput, IonButton } from '@ionic/vue';
  import { defineComponent } from 'vue';
  
  export default defineComponent({
    components: {
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
      IonButton
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
      validateAndSignUp() {
        if (this.firstName.trim() === "" || this.email.trim() === "" || this.password.trim() === "") {
          alert("Please fill in all required fields.");
          return;
        }
  
        if (this.password !== this.confirmPassword) {
          this.passwordsDoNotMatch = true;
          return;
        }
  
        if (this.emailAlreadyExists()) {
          alert("This email has already been used.");
          return;
        }
  
        this.signUp();
      },
      emailAlreadyExists() {
        return false;
      },
      signUp() {
        alert("Sign up successful!");
      },
    },
  });
  </script>
  