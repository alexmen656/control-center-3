<template>
  <ion-list>
    <ion-item
      v-for="chat in chats"
      :key="chat.chatId"
      @click="goToChat(chat.chatId)"
    >
      <ion-avatar slot="start">
        <Avatar
          :profileImg="chat.image"
          :firstName="chat.users[0].firstname"
          :lastName="chat.users[0].lastname"
          avatarColor="blue"
        /><!-- v-if="chat.type == 1"-->
      </ion-avatar>
      <ion-label>
        <h2>
          <!-- v-if="chat.type == 1"-->
          <!--   {{ chat.users[0].firstname }} {{ chat.users[0].lastname }}-->
          {{ chat.name }}
        </h2>
        <!-- <h2 v-if="chat.type == 2">Group</h2>-->
        <!--   <p>{{ lastMessage(chat.id) }}</p>-->
      </ion-label>
    </ion-item>
    <ion-item router-link="/messages/new/group">
      <ion-icon name="add-outline" />
      <h2>New Group</h2>
    </ion-item>
  </ion-list>
</template>

<script>
import Avatar from "@/components/AvatarComponent.vue";
import { defineComponent, ref } from "vue";
import {
  //IonPage,
  //IonHeader,
  //IonContent,
  IonList,
  IonItem,
  //IonCol,
  //IonRow,
  //IonGrid,
  IonLabel,
  IonAvatar,
  IonIcon,
} from "@ionic/vue";

export default defineComponent({
  components: {
    Avatar,
    //IonPage,
    //IonHeader,
    //IonContent,
    IonList,
    IonItem,
    //IonCol,
    //IonRow,
    //IonGrid,
    IonLabel,
    IonAvatar,
    IonIcon,
  },
  data() {
    return {
      chats: [],
    };
  },
  mounted() {
    this.fetchChats();
  },
  methods: {
    async fetchChats() {
      const isOnline = ref(navigator.onLine);
      const userId = 79;
      if (isOnline.value) {
        await fetch(
          `https://alex.polan.sk/control-center/chats.php?userId=${userId}`
        )
          .then((response) => response.json())
          .then((data) => {
            this.chats = data;
          })
          .catch((error) => {
            console.error("Fehler beim Abrufen der Chats:", error);
          });
      } else {
        this.chats = JSON.parse(localStorage.getItem("chats"));
      }
    },
    goToChat(chatId) {
      console.log("Navigating to chat:", chatId);
      this.$router.push("/messages/" + chatId);
    },
  },
});
</script>

<style scoped>
ion-col {
  height: 100%;
}

ion-list {
  /* background: #000;*/
  height: 100% !important;
  border-radius: 25px;
  padding: 1rem;
  border: none;
  overflow-y: scroll;
  box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 1px -2px,
    rgba(0, 0, 0, 0.14) 0px 2px 2px 0px, rgba(0, 0, 0, 0.12) 0px 1px 5px 0px;
}

ion-item {
  margin-bottom: 10px;
  border-radius: 20px;
  box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 1px -2px,
    rgba(0, 0, 0, 0.14) 0px 2px 2px 0px, rgba(0, 0, 0, 0.12) 0px 1px 5px 0px;
}
</style>
