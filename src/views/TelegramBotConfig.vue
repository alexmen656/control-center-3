<template>
  <div class="ion-padding">
    <ion-input
      placeholder="Bot Token"
      v-model="token"
      :value="token"
      @ionInput="token = $event.target.value"
    />
    <ion-input
      placeholder="Chat ID"
      v-model="chat"
      :value="chat"
      @ionInput="chat = $event.target.value"
    />
    <ion-button
      v-if="originalToken || originalChat"
      @click="change(token, chat)"
      >Change</ion-button
    >
    <ion-button v-else @click="submit(token, chat)">Submit</ion-button>
  </div>
</template>

<script>
import { IonButton, IonInput } from "@ionic/vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";

export default {
  components: {
    IonButton,
    IonInput,
  },
  data() {
    return {
      token: "",
      chat: "",
      content: "<h1>Some initial content</h1>",
    };
  },
  setup() {
    const route = useRoute();
    function submit(token, chat) {
      if (token && chat) {
        console.log(chat);
        axios
          .post(
            "/control-center/telegram_bot.php",
            qs.stringify({
              newConfig: "newConfig",
              token: token,
              chatID: chat,
              project: route.params.project,
            })
          )
          .then((res) => {
            alert("Data successful submitted!!!");
            //reload();
          });
      } else {
        alert("Bot Token or ChatID empty!");
      }
    }

    function change(token, chat) {
      if (token && chat) {
        axios
          .post(
            "/control-center/telegram_bot.php",
            qs.stringify({
              changeConfig: "changeConfig",
              token: token,
              chatID: chat,
              project: route.params.project,
            })
          )
          .then((res) => {
            alert("Data successful changed!!!");
            //reload();
          });
      } else {
        alert("Bot Token or ChatID empty!");
      }
    }

    return {
      submit,
      change,
    };
  },
  async mounted() {
    const route = useRoute();
    await axios
      .post(
        "/control-center/telegram_bot.php",
        qs.stringify({ getConfig: "getConfig", project: route.params.project })
      )
      .then((res) => {
        this.token = res.data.token;
        this.chat = res.data.chatID;
        this.originalToken = res.data.token;
        this.originalChat = res.data.chatID;
      });
  },
  methods: {
    async reload() {
      const route = useRoute();
      await axios
        .post(
          "/control-center/telegram_bot.php",
          qs.stringify({
            getConfig: "getConfig",
            project: route.params.project,
          })
        )
        .then((res) => {
          this.token = res.data.token;
          this.chat = res.data.chatID;
          this.originalToken = res.data.token;
          this.originalChat = res.data.chatID;
        });
    },
  },
};
</script>
