<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button
            :defaultHref="'/project/' + $route.params.project + '/chat-app'"
          ></ion-back-button>
        </ion-buttons>
        <ion-title>Chat</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <ion-grid class="full-height">
        <ion-row v-for="message in messages" :key="message.id">
          <ion-col :class="message.author === 'Carlos' ? 'justify-end' : 'justify-start'" size="12">
            <div
              :class="{
                message: true,
                'my-message': message.author === 'Carlos',
                'other-message': message.author !== 'Carlos',
              }"
            >
              <h2>{{ capitalizeFirstLetter(message.message) }}</h2>
            </div>
          </ion-col>
        </ion-row>
      </ion-grid>
      <!-- Floating input container -->
      <div class="floating-input">
        <ion-input
          v-model="newMessage"
          placeholder="Type a message"
          @keyup.enter="sendMessage"
        ></ion-input>
        <ion-button @click="sendMessage">Send</ion-button>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
 //  IonFooter,
  IonInput,
  IonButton,
} from "@ionic/vue";

export default {
  name: "ChatDetail",
  components: {
    IonPage,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonBackButton,
   // IonFooter,
    IonInput,
    IonButton,
    IonButtons,
  },
  data() {
    return {
      messages: [],
      old_messages: [],
      newMessage: "",
      ticker1: 1,
      ticker2: 1,
      newMessages: false,
      activeChat: 1,
    };
  },
  created() {
    this.fetchMessages();
  },
  methods: {
    fetchMessages(scroll = true) {
      const roomId = this.$route.params.id;
      this.activeChat = roomId;
      this.$axios
        .get(
          "api/chat-app/livechat.php?room_id=" +
            roomId +
            "&verification_id=d329ef42ae229740"
        )
        .then((response) => {
          this.old_messages = this.messages;
          this.messages = response.data.messages;
          this.onlineCount = response.data.online_count;
        })
        .then(() => {
        //  const container = this.$refs.messagesContainer;

          if (this.ticker2 == 1 || !scroll) {
            this.ticker2++;
          } /*else if (
            this.old_messages.length != this.messages.length &&
            scroll &&
            container.scrollHeight > container.clientHeight
          ) {
          }*/
        })
        .catch((error) => {
          console.error("Error fetching messages:", error);
        });
    },
    sendMessage() {
      if (this.newMessage.trim() !== "") {
        const roomId = this.$route.params.id;
        const message = {
          author: "Carlos",
          message: this.newMessage,
          type: "user",
          room_id: roomId,
          verification_id: "d329ef42ae229740",
        };
        this.$axios
          .post("api/chat-app/livechat.php", message)
          .then((response) => {
            //alert(2);
            //console.log("Response: ", response.data.status);
            if (response.data.status === "success") {
              this.fetchMessages(false);
              //const container = this.$refs.messagesContainer;
            //  container.scrollTop = container.scrollHeight;
              //alert(1);
              this.newMessage = "";
            } else if (response.data.status === "error") {
              alert(
                "Message could not be send!\nError: " + response.data.message
              );
            }
          })
          .catch((error) => {
            console.error("Error sending message:", error);
          });
      }
    },
    capitalizeFirstLetter(text) {
      if (!text) return "";
      return text.charAt(0).toUpperCase() + text.slice(1);
    },
  },
};
</script>

<style scoped>
ion-item.sent {
  --background: #dcf8c6;
  --border-radius: 20px;
  --margin-start: auto;
  --margin-end: 10px;
  --padding-start: 10px;
  --padding-end: 10px;
  --width: auto;
  max-width: 70%;
  margin-bottom: 10px;
  text-align: end;
}

ion-item.received {
  --background: #e0e0e0;
  --border-radius: 20px;
  --margin-start: 10px;
  --margin-end: auto;
  --padding-start: 10px;
  --padding-end: 10px;
  --width: auto;
  max-width: 70%;
  margin-bottom: 10px;
  text-align: start;
}

ion-footer {
  --padding-bottom: 10px;
}

.message {
  color: #444;
  padding: 10px 15px;
  line-height: 1.4;
  font-size: 16px;
  border-radius: 15px;
  display: inline-block;
  max-width: 70%;
  word-wrap: break-word;
}

.message h2 {
  margin: 0;
  font-size: 1.25rem;
}

.my-message {
  background: #e8f1f3;
  text-align: left;
  border-radius: 15px 15px 0 15px !important;
  align-self: flex-end;
}

.other-message {
  background: #efefef;
  text-align: left;
  border-radius: 15px 15px 15px 0 !important;
  align-self: flex-start;
}

.justify-start {
  display: flex;
  justify-content: flex-start;
}

.justify-end {
  display: flex;
  justify-content: flex-end;
}

.d-flex {
  display: flex;
}

.floating-input {
  position: fixed /*sticky*/;
  bottom: 15px;
  left: 0;
  right: 0;
  display: flex;
  gap: 10px;
  background: white;
  padding: 10px 15px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  z-index: 10;
  margin: 0 10px;
}

.full-height {
  height: calc(100% - 60px); /* Adjust height to leave space for the input */
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
</style>
