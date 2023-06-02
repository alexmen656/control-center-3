<template>
    <ion-page>
      <!--<ion-header>
        <ion-toolbar color="primary">
          <ion-title>
            Chat
          </ion-title>
        </ion-toolbar>
      </ion-header>-->
      <ion-content size-lg class="ion-padding">

            <ion-row>
                <ion-col size-lg="3" size="12"><ChatsMenu/></ion-col>
                
                <ion-col size-lg="9" class="desktop" size="0">
                    <div class="" v-if="$route.params.id">
        <!--<ion-card>
            Hello how are you ? {{ $route.params.id }}
            </ion-card>-->
            <ion-list v-if="$route.params.id" >
        <ion-item v-for="message in getMessagesByChatId($route.params.id)" :key="message.id" class="chat-item">
            <ion-label>
                <p>{{ message.text }}</p>
            </ion-label>
        </ion-item>
    </ion-list>
            <ion-card><ion-input></ion-input></ion-card>
        </div>
        <div class="select-chat-screen" v-else>
        <h2>Select a chat to show messages.</h2>
    </div>
</ion-col>
            </ion-row>


            
        

      </ion-content>


    </ion-page>
  </template>
  
  <script>
  import axios from 'axios'
  import ChatsMenu from '@/components/ChatsMenu.vue'
  import { isPlatform } from '@ionic/vue';
import { IonPage, IonContent, IonCol, IonList, IonCard, IonInput, IonLabel, IonRow, IonItem } from '@ionic/vue';//IonToolbar, IonTitle, IonHeader,

  export default {
    data() {
      return {
        chats: [],
        messages: [],
        ios: isPlatform('ios'),
      }
    },
    components: {
        ChatsMenu,
        IonPage, 
        IonContent, 
        IonCol, 
        IonList, 
        IonCard, 
        IonInput,
        IonLabel, 
        IonRow,
        IonItem,
        //IonToolbar, IonTitle, IonHeader,
    },
    mounted() {
      axios.get('/data.json')
        .then(response => {
          this.chats = response.data.chats
          this.messages = response.data.messages
        })
        .catch(error => {
          console.log(error)
        })
    },
    methods: {
        getMessagesByChatId(chatIdd) {
            const chatId = Number(chatIdd);
            console.log(chatId);
            console.log(this.messages);

            console.log(this.messages.filter(message => message.chatId === chatId).length);

      return this.messages.filter(message => message.chatId === chatId);
    },
      sendMessage() {
        // Add code to send the message
      }
    }
  }
  </script>
  
  <style scoped>
    ion-row {
        height: 100%;
    }

    ion-card {
        padding: 5px;
    }

    .select-chat-screen {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: red;
    }

    .desktop {
        display: block;
    }

    .mobile {
        display: none;
    }

    @media only screen and (max-width: 600px) {

        .desktop {
        display: none;
    }

    .mobile {
        display: block;
    }
    }
</style>