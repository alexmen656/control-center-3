<template>
  <h1>Send Newsletter Message</h1>
  <!--  <HtmlInput></HtmlInput>-->
  <ion-content>
    <ion-list>
      <ion-item>
        <ion-label position="stacked">Subject</ion-label>
        <ion-input
          type="text"
          v-model="subject"
          :value="subject"
          @ionInput="subject = $event.target.value"
        />
      </ion-item>
      <ion-item>
        <ion-label position="stacked">Email</ion-label>
        <ion-textarea
          v-model="email"
          :value="email"
          @ionInput="email = $event.target.value"
        />
      </ion-item>
      <!--<ion-item>
            <ion-label position="stacked">Examples</ion-label>
            <ion-textarea v-model="examples" :value="examples" @ionInput="examples = $event.target.value;"></ion-textarea>
          </ion-item>-->
    </ion-list>
    <ion-button expand="block" color="primary" @click="send()"
      >Send Newsletter</ion-button
    >
  </ion-content>
</template>

<script>
//import HtmlInput from "@/components/HtmlInput.vue";
import axios from "axios";
import qs from "qs";

export default {
  name: "NewsletterView",
  components: {
    //  HtmlInput
  },
  data() {
    return {
      subject: "",
      email: "",
    };
  },
  methods: {
    send() {
      axios.post(
        "/control-center/newsletter.php",
        qs.stringify({ subject: this.subject, email: this.email })
      );
    },
  },
};
</script>
