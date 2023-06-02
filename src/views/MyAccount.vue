<template>
  <ion-page>
     <ion-content class="profile-container" v-if="token"><!--ion-padding-->
        <ion-avatar>
          <img v-if="userData.profileImg != 'avater'" :src="userData.profileImg" />
        </ion-avatar>
        <div class="user-info">
          <h2>{{ userData.firstName }} {{ userData.lastName }}</h2>
          <p>{{ userData.email }}</p>
        </div>
    <!--<ion-list>
      <ion-item>
        <ion-icon name="phone-portrait" slot="start"></ion-icon>
        <ion-label>Phone</ion-label>
        <ion-text>{{ user.phone }}</ion-text>
      </ion-item>
      <ion-item>
        <ion-icon name="pin" slot="start"></ion-icon>
        <ion-label>Address</ion-label>
        <ion-text>{{ user.address }}</ion-text>
      </ion-item>
      <ion-item>
        <ion-icon name="calendar" slot="start"></ion-icon>
        <ion-label>Birthday</ion-label>
        <ion-text>{{ user.birthday }}</ion-text>
      </ion-item>
    </ion-list>-->
        <ion-grid>
          <ion-row>
            <ion-col v-for="card in cards" :key="card" size="12" size-lg="4" size-xl="3">
              <ion-card class="tall-card">
                <ion-card-header>
                  <ion-card-title>
                    <ion-icon name="log-out-outline" slot="start"></ion-icon>
                    <router-link :to="'/my-account/'+ (card.replaceAll(` `, `-`).toLowerCase())">{{card}}</router-link>
                  </ion-card-title>
                </ion-card-header>
              </ion-card>
            </ion-col>
            <ion-button>Edit Profile</ion-button><!-- @click="goToEditPage"-->
          </ion-row>
        </ion-grid>
     </ion-content> 
  </ion-page>
</template>
      
      <script lang="ts">
      import { IonContent, IonPage, IonIcon, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonButton, IonAvatar } from '@ionic/vue';
      import { defineComponent, ref } from 'vue';
      import axios from 'axios';
      
      export default defineComponent({
        name: 'MyAccount',
        data() {
          return {
            token: localStorage.getItem("token"),
            useSystemDefault: true,
            selectedTheme: 'light',
            user: {
              name: 'John Doe',
              email: 'john@example.com',
              phone: '123-456-7890',
              address: '123 Main Street, Anytown, USA',
              birthday: '01/01/1970'
            },
            cards: [
              "Logout",
              "Personal Information",
              "Settings",
              "Preferences",
              "Account Security",
              "App Theme",
              "My Team",
              "My Projects",
              "photo"
            ]
          }
        },
        components: {
          IonContent,
          IonPage,
          IonGrid,
          IonIcon,
          IonRow,
          IonCol,
          IonCard,
          IonCardHeader,
          IonCardTitle,
          IonButton,
          IonAvatar
        },
        setup(){
    const user = ref({});
    const response = axios.post("https://alex.polan.sk/control-center/user.php").then(responseee => {
      user.value = responseee.data;
     }); //, {token: localStorage.getItem('token')}
    

    return {
      userData: user
    }
  }
      });
      </script>

<style scoped>
  ion-avatar {
    display: block;
    margin: 0 auto;
  }

  .profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
  }

  ion-card-title {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
  }

  ion-card {
    border-radius: 10px;
    padding-top: 30px;
    padding-bottom: 30px;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  ion-avatar  {      
    width: 164px !important; 
    height: 164px !important; 
  }

  .user-info {
    display: flex;
    align-items: center;
    flex-direction: column;
  }

  h2{
    margin-bottom: 0 !important;
  }

  p {
    margin-top: 5px;
  }

  ion-avatar {
    margin-top: 20px;
  }

  ion-col {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }

  ion-row {
    justify-content: center;
  }

  ion-button {
    margin-top: 1rem;
  }
</style>
      
      