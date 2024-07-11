<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1"></ion-col>
      <ion-col size="10">
        <h2>Login Options</h2>
        <ion-list>
          <ion-item>
            <ion-toggle
              slot="start"
              color="success"
              :checked="login_with_google"
              labelPlacement="start"
              aria-label="LogIn with Google"
              @ionChange="update($event)"
              >LogIn with Google</ion-toggle
            >
          </ion-item>
          <!-- Coming Soon-->
          <!--
            <ion-item>
            <ion-label>LogIn with Facebook</ion-label>
            <ion-toggle
              slot="end"
              name="kiwi"
              color="success"
              checked
            ></ion-toggle>
          </ion-item>
          <ion-item>
            <ion-label>LogIn with Apple</ion-label>
            <ion-toggle
              slot="end"
              name="kiwi"
              color="success"
              checked
            ></ion-toggle>
          </ion-item>
          <ion-item>
            <ion-label>LogIn with Github</ion-label>
            <ion-toggle
              slot="end"
              name="kiwi"
              color="success"
              checked
            ></ion-toggle>
          </ion-item>
          -->
        </ion-list>
      </ion-col>
      <ion-col size="1"></ion-col>
    </ion-row>
  </ion-grid>
</template>

<script>
import { defineComponent } from "vue";
import { getUserData } from "@/userData";

export default defineComponent({
  data() {
    return {
      user: {},
      login_with_google: false,
    };
  },
  async created() {
    this.user = await getUserData();
    this.login_with_google = this.user.login_with_google;
  },
  methods: {
    update(event) {
      this.$axios.post(
        "user.php",
        this.$qs.stringify({
          updateLoginWithGoogle: "updateLoginWithGoogle",
          newValue: event.detail.checked,
        })
      );
    },
  },
});
</script>
