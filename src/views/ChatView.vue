<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <Avatar slot="start" :profileImg="chat.image" avatarColor="blue" />
        <ion-title slot="start">{{ chat.name }}</ion-title>
      </ion-toolbar>
    </ion-header>
    <div style="height: 100%">
      <ion-grid style="overflow-y: scroll; height: 100%">
        <ion-row v-for="message in this.messages" :key="message">
          <ion-col
            v-if="Number(message.from) != userID"
            size="9"
            style="display: flex; align-items: end"
          >
            <Avatar
              v-if="message.type == 2"
              :profileImg="message.user.profileImg"
              :firstName="message.user.firstname"
              :lastName="message.user.lastname"
              avatarColor="blue"
              style="margin-right: 5px"
            />
            <div class="message message-user2">{{ message.body }}</div>
          </ion-col>
          <ion-col v-if="Number(message.from) != userID" size="3"></ion-col>

          <ion-col v-if="message.from == userID" size="3"></ion-col>
          <ion-col
            v-if="message.from == userID"
            size="9"
            style="display: flex; justify-content: right"
          >
            <div class="message message-user1">{{ message.body }}</div>
          </ion-col>
        </ion-row>
      </ion-grid>
      <ion-grid>
        <ion-row>
          <ion-col>
            <ion-card> <ion-input></ion-input></ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </div>
    <ion-input></ion-input>
  </ion-page>
</template>

<script>
import Avatar from "@/components/AvatarComponent.vue";
import axios from "axios";
import qs from "qs";
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { getUserData } from "@/userData";
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  //IonContent,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonInput,
} from "@ionic/vue";

export default {
  name: "ChatView",
  components: {
    Avatar,
    IonPage,
    IonHeader,
    //IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonCard,
    IonInput,
    IonToolbar,
    IonTitle,
  },
  data() {
    return {
      chatId: "",
      creationTime: "",
      type: "",
      users: [],
      userID: "",
      messages: [],
      chat: {},
    };
  },
  async mounted() {
    const chatId = this.$route.params.id;
    const data = await getUserData();
    console.log(data.email);
    this.userID = data.userID;
    await this.fetchChat(chatId);
    await this.fetchMessages(chatId);
  },
  methods: {
    async fetchChat(chatId) {
      try {
        const response = await axios.post(
          "/control-center/chats.php",
          qs.stringify({
            getChatByChatID: "getChatByChatID",
            chatID: chatId,
            userID: this.userID,
          })
        );

        this.chat = response.data;
      } catch (error) {
        console.error("Fehler beim Abrufen der Nachrichten:", error);
      }
    },
    async fetchMessages(chatId) {
      try {
        const response = await axios.post(
          "/control-center/messages.php",
          qs.stringify({
            getMessagesByChatID: "getMessagesByChatID",
            chatID: chatId,
          })
        );

        this.messages = response.data;
      } catch (error) {
        console.error("Fehler beim Abrufen der Nachrichten:", error);
      }
    },
  },
};
</script>
<style scoped>
.message-user1 {
  background-color: red;
  border-radius: 1rem 1rem 0 1rem;
}

.message-user2 {
  background-color: black;
  border-radius: 1rem 1rem 1rem 0;
}

.message {
  max-width: 100%;
  width: fit-content;
  min-width: 30%;
  padding: 0.8rem;
  color: white;
}
</style>
