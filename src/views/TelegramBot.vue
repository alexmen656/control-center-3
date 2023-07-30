<template>
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
</template>

<script>
import { IonButton, IonInput } from "@ionic/vue";
import { useRoute } from "vue-router";
import axios from "axios";
import qs from "qs";

export default {
  components: {
    IonButton,
    IonInput,
  },
  data() {
    return {
      message: "",
    };
  },
  mounted() {
    const route = useRoute();
    axios
      .post(
        "/control-center/telegram_bot.php",
        qs.stringify({ getConfig: "getConfig", project: route.params.project })
      )
      .then((res) => {
        this.token = res.data.token;
        this.chatID = res.data.chatID;
        const baseUrl = `https://api.telegram.org/bot${this.token}`;
        const repliedMessages = {};

        function getUpdates() {
          axios
            .post(`${baseUrl}/getUpdates`)
            .then((response) => {
              const updates = response.data.result;
              updates.forEach((update) => {
                const message = update.message;
                const chatId = message.chat.id;
                const messageId = message.message_id;
                if (!repliedMessages[messageId]) {
                  repliedMessages[messageId] = true;
                  axios.post(`${baseUrl}/sendMessage`, {
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
        }
        getUpdates();
      });
  },
  methods: {
    sendMessage() {
      const url = "https://api.telegram.org/bot" + this.token + "/sendMessage";
      const fullUrl = url + "?chat_id=" + this.chatID + "&text=" + this.message;
      axios.post(fullUrl);

      //send image
      //const url2 = "https://api.telegram.org/bot" + token + "/sendPhoto";
      //const fullUrl2 = url2 + '?chat_id=796382938&photo=https://www.simplilearn.com/ice9/free_resources_article_thumb/what_is_image_Processing.jpg&caption=Ty mas mega velky penis hahahahahah';
      //axios.post(fullUrl2);
    },
  },
};
</script>
