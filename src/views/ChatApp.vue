<template>
  <ion-page>
    <!--  <ion-header>
      <ion-toolbar>
        <ion-title>ChatApp</ion-title>
      </ion-toolbar>
    </ion-header>-->
    <ion-content>
      <ion-list>
        <ion-item
          v-for="chat in chats"
          :key="chat.id"
          @click="openChat(chat.verification_id)"
          class="chat"
        >
          <img
            class="avatar"
            :src="
              chat.avatar
                ? chat.avatar
                : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48ZyBpZD0iaWNvbiI+PHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjI0IiBmaWxsPSIjZTllZWY4Ii8+PGcgaWQ9InByb2ZpbGVfcGljIj48cGF0aCBkPSJNMTIsMTcuNzloMGMtMy43MSwwLTcsMi05LDYuMjFIMjFDMTksMTkuODEsMTUuNzIsMTcuNzksMTIsMTcuNzlaIiBmaWxsPSIjYWFiNGM3Ii8+PHBhdGggaWQ9InByb2ZpbGVfcGljMiIgZD0iTTE2LjY5LDEwLjdsLS4yNCwwQTQuNDksNC40OSwwLDAsMCwxMiw3LjFoMGE0LjUzLDQuNTMsMCwwLDAtMy43NywyLDQuNDcsNC40NywwLDAsMC0uNjgsMS42NS44NS44NSwwLDAsMC0uMjMsMGgwYTEuMTMsMS4xMywwLDEsMCwwLDIuMjZoLjE2VjEzYTQuNDQsNC40NCwwLDAsMCwzLjM5LDQuMjh2MS4zNUExLjE0LDEuMTQsMCwwLDAsMTIsMTkuNzZoMGExLjEzLDEuMTMsMCwwLDAsMS4xMy0xLjEzVjE3LjI4QTQuNDUsNC40NSwwLDAsMCwxNi41MywxM1YxM2guMTZhMS4xMywxLjEzLDAsMCwwLDAtMi4yNloiIGZpbGw9IiNmZmYiLz48ZyBpZD0icHJvZmlsZV9waWMxIj48cGF0aCBkPSJNMTUuMTMsNC43YTIsMiwwLDAsMCwuMjctLjQzLjE2LjE2LDAsMCwwLS4xOS0uMjJsLS4zOC4xMkEuMTYuMTYsMCwwLDEsMTQuNjIsNFYzLjgyYS4xNi4xNiwwLDAsMC0uMi0uMTYsMi40NywyLjQ3LDAsMCwwLS42OS4yN0E1LDUsMCwwLDAsMTIsMy42LDUuNDksNS40OSwwLDAsMCw2LjY5LDkuMzlhNi42Niw2LjY2LDAsMCwwLC40MywyLjM4LjE1LjE1LDAsMCwwLC4yOCwwLDQsNCwwLDAsMSwuNC0xLjQyQTUuODEsNS44MSwwLDAsMSw5LjIsOC41OWEuMTQuMTQsMCwwLDEsLjIsMCw1Ljc0LDUuNzQsMCwwLDAsMy4xOSwyYzIuMjQuNTEsMi44My0uMTIsMy4yNy0uNHMuNjUuOTQuNzEsMS42NGEuMTQuMTQsMCwwLDAsLjI3LDAsNi41OSw2LjU5LDAsMCwwLC40Ni0yLjQ1QTUuODgsNS44OCwwLDAsMCwxNS4xMyw0LjdaIiBmaWxsPSIjOGU5OWFjIi8+PC9nPjwvZz48L2c+PC9zdmc+'
            "
            alt="Avatar"
          />
          <ion-label>
            <h2>{{ chat.username }}</h2>
            <p>{{ chat.lastMessage }}</p>
          </ion-label>
        </ion-item>
      </ion-list>
    </ion-content>
  </ion-page>
</template>

<script>
import {
  IonPage,
  //IonHeader,
  //IonToolbar,
  //IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
} from "@ionic/vue";
import { useRouter } from "vue-router";

export default {
  name: "ChatApp",
  components: {
    IonPage,
    //IonHeader,
    //IonToolbar,
    //IonTitle,
    IonContent,
    IonList,
    IonItem,
    IonLabel,
  },
  data() {
    return {
      chats: [
        { id: 1, username: "Chat 1", lastMessage: "Hello there!" },
        { id: 2, username: "Chat 2", lastMessage: "How are you?" },
        { id: 3, username: "Chat 3", lastMessage: "Good morning!" },
      ],
    };
  },
  created() {
    this.$axios
      .get(
        "api/chat-app/livechat.php?action=getUsers&verification_id=d329ef42ae229740"
      )
      .then((response) => {
        console.log("Reponse: ", response.data);
        this.chats = response.data;
        this.setCurrentChat();
      });
  },
  setup() {
    const router = useRouter();
    const openChat = (chatId) => {
      router.push({ name: "ChatDetail", params: { id: chatId } });
    };
    return { openChat };
  },
};
</script>

<style scoped>
ion-item {
  --padding-start: 10px;
  --inner-padding-end: 10px;
}

img.avatar {
  width: 36px;
  border-radius: 50%;
  aspect-ratio: 1/1;
  object-fit: cover;
  margin: 5px;
}

.chat {
  cursor: pointer;
}
</style>
