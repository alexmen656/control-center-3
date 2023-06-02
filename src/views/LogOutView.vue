<template>
    <ion-page>
      <ion-content>
        <p>Do you really want to log out?</p>
        <button @click="goBack">Go Back</button>
        <button @click="performLogOut">Logout</button>
      </ion-content>
    </ion-page>
  </template>
  
  <script>  
  import { IonPage, IonContent } from '@ionic/vue';


  export default {
    name: "LogOut",
    components: {IonPage, IonContent},
    methods: {
      performLogOut() {
        localStorage.removeItem("token");
        // Redirect to the login page or homepage after logging out
        window.location.href = "/login";
      },
      goBack() {
        // Navigate back to the previous page in the user's browsing history
        window.history.back();
      }
    }
  };
  </script>
  