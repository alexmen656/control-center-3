<template>
<ion-header :translucent="true">
    <ion-toolbar class="header">
      <ion-buttons slot="start">
        <ion-menu-button></ion-menu-button>
      </ion-buttons>
      <ion-title @click="goToStart()">{{title}}</ion-title><!--routerLink="/"-->
      <router-link style="height: 36px; margin-right: 5px;" slot="end" to="/my-account/"><Avatar :profileImg="user.profileImg" :firstName="user.firstName" :lastName="user.lastName" avatarColor="green"></Avatar></router-link>
    </ion-toolbar>
</ion-header>
</template>

<script>
import { IonApp, IonContent, IonIcon, IonItem, IonLabel, IonList, IonListHeader, IonMenu, IonMenuToggle, IonNote, IonRouterOutlet, IonSplitPane, IonSearchbar, IonButton, IonTitle, IonFooter, IonMenuButton, IonToolbar, IonHeader, IonButtons} from '@ionic/vue';
import Avatar from '@/components/AvatarComponent.vue'
import axios from 'axios';
import { defineComponent, ref } from 'vue';
import {getUserData} from '@/userData'

export default defineComponent({
  name: "SiteHeader",
  components: {
    IonTitle,
    IonMenuButton, 
    IonToolbar, 
    IonHeader,
    IonButtons,
    Avatar,
  },
  async mounted(){
        const data = await getUserData();
        this.user = data;
    },
  setup(){
    const user = ref({});
    const width = document.body.clientWidth;
    const response = axios.post("https://alex.polan.sk/control-center/user.php").then(responseee => {
      user.value = responseee.data;
     }); //, {token: localStorage.getItem('token')

      const title = (width>380) ? "Control Center" : "CCenter";
    

    return {
      user: user,
      title: title
    }
  },
  methods: {
    goToStart(){
      window.location.href = '/';
    }
  }
});
</script>

<style scoped>
  ion-title {
    font-family: Chalkduster;
    font-size: 28px;
    color: #FF0000;
    text-align: left;
    cursor: pointer !important;
  }

  @media only screen and (max-width: 600px){
    ion-title {
      text-align: center;
    }
  }
</style>