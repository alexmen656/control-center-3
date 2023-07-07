<template>
  <ion-page>
    <ion-content size-lg class="ion-padding">
      <ion-grid>
        <ion-row>
          <ion-col size-lg="3" size="12">
            <ChatsMenu />
          </ion-col>
          <ion-col size-lg="9" size="0">
            <div style="height: 100%" v-if="$route.params.id">
              <ChatView />
            </div>
            <div v-else class="select-chat-screen">
              <!--v-else-->
              <h2>Select a chat to show messages.</h2>
            </div>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import ChatsMenu from "@/components/ChatsMenu.vue";
import ChatView from "@/views/ChatView.vue";
import { defineComponent, ref } from "vue";
import {
  IonPage,
  // IonHeader,
  IonContent,
  //IonList,
  //IonItem,
  IonCol,
  IonRow,
  IonGrid,
  //IonLabel,
} from "@ionic/vue";

export default defineComponent({
  data() {
    return {
      chats: [],
    };
  },
  components: {
    ChatsMenu,
    ChatView,
    IonPage,
    //IonHeader,
    IonContent,
    //IonList,
    //IonItem,
    IonCol,
    IonRow,
    IonGrid,
    //IonLabel,
  },
  mounted() {
    this.fetchChats();
  },
  methods: {
    fetchChats() {
      const userId = 79; // Beispielwert, entsprechend anpassen
      const isOnline = ref(navigator.onLine);
      //alert(isOnline.value);
      if (isOnline.value) {
        fetch(`https://alex.polan.sk/control-center/chats.php?userId=${userId}`)
          .then((response) => response.json())
          .then((data) => {
            this.chats = data;
            localStorage.setItem("chats", JSON.stringify(this.chats));
          })
          .catch((error) => {
            console.error("Fehler beim Abrufen der Chats:", error);
          });
      } else {
        // alert("1" + JSON.parse(localStorage.getItem("chats")));
        // alert("2" + localStorage.getItem("chats"));

        this.chats = localStorage.getItem("chats");
      }
    },
    goToChat(chatId) {
      // Hier kannst du die Navigation zur Chatseite implementieren
      // Verwende Vue Router oder Ionic Navigation nach Bedarf
      console.log("Navigating to chat:", chatId);
      this.$router.push({ path: "/chat/" + chatId });
    },
  },
});
</script>
<style scoped>
ion-grid,
ion-row,
ion-col {
  height: 100%;
}

.select-chat-screen {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  color: red;
  font-size: 1.5rem;
}
</style>
