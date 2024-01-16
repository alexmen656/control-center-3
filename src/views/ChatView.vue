<template>
  <ion-page>
    <ion-header class="seperate">
      <ion-toolbar>
        <Avatar
          slot="start"
          :profileImg="'https://alex.polan.sk/control-center/' + chat.image"
          avatarColor="blue"
        />
        <ion-title slot="start">{{ chat.name }}</ion-title>
      </ion-toolbar>
    </ion-header>
    <div style="height: 100%">
      <ion-grid style="overflow-y: scroll; height: 100%">
        <ion-row style="height: 88%">
          <ion-col size="12" v-for="message in this.messages" :key="message"
            ><ion-row>
              <ion-col v-if="Number(message.from) != userID" size="9">
                <div style="display: flex; align-items: end">
                  <Avatar
                    v-if="message.type == 2"
                    :profileImg="message.user.profileImg"
                    :firstName="message.user.firstname"
                    :lastName="message.user.lastname"
                    avatarColor="blue"
                    style="margin-right: 5px"
                  />
                  <div class="message message-user2">{{ message.body }}</div>
                </div>
              </ion-col>
              <ion-col v-if="Number(message.from) != userID" size="3"/>

              <ion-col v-if="message.from == userID" size="3"/>
              <ion-col v-if="message.from == userID" size="9">
                <div style="display: flex; justify-content: right">
                  <div class="message message-user1">{{ message.body }}</div>
                </div>
              </ion-col>
            </ion-row></ion-col
          >
        </ion-row>

        <!-- </ion-grid>
      <ion-grid>-->
        <ion-row class="bottom">
          <ion-col class="bottom">
            <ion-card
              style="padding-top: 0 !important; padding-bottom: 0 !important"
            >
              <ion-item
                ><ion-input/>
                <ion-icon @click="spechToText()" name="mic-outline"/>
                <label style="height: 24px; width: 22.9667px" for="fileInput">
                  <ion-icon
                    style="
                      height: 100%;
                      width: 100%;
                      color: rgba(var(--ion-text-color-rgb, 0, 0, 0), 0.54);
                    "
                    name="attach-outline"
                  />
                </label> </ion-item
            ></ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </div>

    <input
      ref="fileInput"
      type="file"
      id="fileInput"
      style="display: none"
      @input="handleFileUpload()"
    />

    <ion-modal
      :is-open="isOpenRef"
      css-class="my-custom-class"
      @didDismiss="closeModal(false)"
    >
      <img v-if="imageUrl" :src="imageUrl" style="max-width: 100%" />
      <ion-button>Send <ion-icon slot="end" name="send-outline" /></ion-button>
    </ion-modal>
  </ion-page>
</template>

<script>
import Avatar from "@/components/AvatarComponent.vue";
import axios from "axios";
import qs from "qs";
//import { ref, onMounted } from "vue";
//import { useRoute } from "vue-router";
import { getUserData } from "@/userData";
import { defineComponent, ref } from "vue";

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
  IonModal,
  IonItem,
} from "@ionic/vue";

export default defineComponent({
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
    IonModal,
    IonItem,
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
      imageUrl: "",
    };
  },
  setup() {
    const isOpenRef = ref(false);
    const edit_id = ref("");
    const edit = (id) => {
      isOpenRef.value = true;
      edit_id.value = id;
    }; //: number
    const closeModal = (state) => {
      isOpenRef.value = state;
    }; //: number
    return { isOpenRef, edit, closeModal, edit_id };
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
    spechToText() {
      //spech to text
    },
    handleFileUpload(event) {
      this.edit();
      const fileInput = this.$refs.fileInput;
      const selectedFile = fileInput.files[0];

      if (selectedFile) {
        const reader = new FileReader();

        reader.onload = (e) => {
          this.imageUrl = e.target.result;
        };

        reader.readAsDataURL(selectedFile);
      }
      console.log(this.imageUrl);
    },

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
});
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

.seperate {
  border-bottom: solid 1px #dedede;
}

@media (prefers-color-scheme: dark) {
  .seperate {
    border-bottom: solid 1px #77797f;
  }
}

ion-toolbar {
  padding-left: 0.5rem;
}

ion-card {
  width: 100%;
  height: 80%;
}

ion-row.bottom {
  height: 12%;
}

ion-col.bottom {
  height: 100%;
}
.bottom {
  width: 100%;
  display: flex;
  align-items: end;
}
</style>
