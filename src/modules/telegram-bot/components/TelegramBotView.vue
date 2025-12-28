<template>
  <ion-page>
    <ion-content>
      <div class="ion-padding">
        <h1>Telegram-Bot Nachricht senden</h1>
        <ion-input
          placeholder="Nachricht"
          v-model="message"
          :value="message"
          @ionInput="message = $event.target.value"
        ></ion-input>
        <ion-button @click="sendMessage()">Senden</ion-button>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import { IonPage, IonContent, IonButton, IonInput } from "@ionic/vue";
import { useRoute } from "vue-router";
import qs from "qs";

export default defineComponent({
  name: "TelegramBotView",
  components: {
    IonPage,
    IonContent,
    IonButton,
    IonInput,
  },
  data() {
    return {
      message: "",
      token: "",
      chatID: "",
    };
  },
  mounted() {
    const route = useRoute();
    this.$axios
      .post(
        "telegram_bot.php",
        qs.stringify({ getConfig: "getConfig", project: route.params.project })
      )
      .then((res) => {
        this.token = res.data.token;
        this.chatID = res.data.chatID;
        const baseUrl = `https://api.telegram.org/bot${this.token}`;
        const repliedMessages = {};

        const getUpdates = () => {
          this.$axios
            .post(`${baseUrl}/getUpdates`)
            .then((response) => {
              const updates = response.data.result;
              updates.forEach((update) => {
                const message = update.message;
                const chatId = message.chat.id;
                const messageId = message.message_id;
                if (!repliedMessages[messageId]) {
                  repliedMessages[messageId] = true;
                  this.$axios.post(`${baseUrl}/sendMessage`, {
                    chat_id: chatId,
                    text: "Hallo",
                  });
                }
              });
              setTimeout(getUpdates, 5000);
            })
            .catch((error) => {
              console.log(error);
              setTimeout(getUpdates, 5000);
            });
        };
        getUpdates();
      });
  },
  methods: {
    sendMessage() {
      const url = "https://api.telegram.org/bot" + this.token + "/sendMessage";
      const fullUrl = url + "?chat_id=" + this.chatID + "&text=" + this.message;
      this.$axios.post(fullUrl);
    },
  },
});
</script>

<style scoped>
h1 {
  margin-bottom: 1rem;
}
</style>
