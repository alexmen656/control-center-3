<template>
  <div class="chat-menu">
    <ion-avatar
      v-for="chat in chats"
      :key="chat.chatId"
      @click="goToChat(chat.chatId)"
      class="avatar"
    >
      <div
        v-if="profileImg === 'avatar'"
        :style="{ 'background-color': 'blue' }"
        class="avatar"
      >
        {{ initials(chat.users[0].firstname, chat.users[0].lastname) }}
      </div>
      <img v-else :src="(chat.type == '2' ?'https://alex.polan.sk/control-center/' : '') + chat.image" />
   
    </ion-avatar>
      <router-link class="avatar" to="/messages/new/group"><ion-icon class="add" name="add-circle-outline" /></router-link>
  </div>
</template>

<script>
//import Avatar from "@/components/AvatarComponent.vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  data() {
    return {
      chats: [],
    };
  },
  mounted() {
    this.fetchChats();
  },

  setup() {
    const initials = (fn, ln) => {
      return fn.charAt(0) + ln.charAt(0);
    }

    return {
      initials
    }
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

.avatar {
  margin-top: .75rem;
}

.chat-menu {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.add {
  height: 64px;
  width: 64px;
}
</style>
