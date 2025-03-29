<template>
  <ion-page class="ion-padding">
    <ion-content>
      <!--   v-model="style['par2']"-->
      <FloatingInput
        label="Url"
        placeholder="url"
        type="text"
        defaultVal="https://alex.polan.sk/control-center/api/chat-app/"
        disabled="true"
      />
      <FloatingInput
        label="API key"
        placeholder="API key"
        type="text"
        :defaultVal="api_key"
        disabled="true"
      />
    </ion-content>
  </ion-page>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";

export default {
  name: "ChatApp",
  data() {
    return {
      api_key: "",
    };
  },
  components: {
    FloatingInput,
  },
  created() {
    this.$axios
      .post(
        "chat_app.php",
        this.$qs.stringify({
          getAPIKey: "getAPIKey",
          project: this.$route.params.project,
          tool: "chat-app",
        })
      )
      .then((res) => {
        this.api_key = res.data.api_key;
        //console.log(res.data.api_key);
      });
  },
};
</script>
