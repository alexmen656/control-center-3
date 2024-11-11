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
          <ion-item>
            <ion-toggle
              slot="start"
              color="success"
              :checked="login_with_microsoft"
              labelPlacement="start"
              aria-label="LogIn with Google"
              @ionChange="update2($event)"
              >LogIn with Microsoft</ion-toggle
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
      login_with_microsoft: false,
    };
  },
  async created() {
    this.user = await getUserData();
    const login_wg = this.user.login_with_google;
    if (typeof login_wg === 'string' && login_wg.toLowerCase() == "microsoft") {
      this.login_with_microsoft = true;
      console.log("login with microsoft");
    } else if (login_wg == true) {
      console.log("login with google");
      this.login_with_google = true;
    }
  },
  methods: {
    update(event) {
      this.login_with_google = event.detail.checked;

      if(event.detail.checked){
        this.login_with_microsoft = false;
      }

      this.$axios.post(
        "user.php",
        this.$qs.stringify({
          updateLoginWithGoogle: "updateLoginWithGoogle",
          newValue: event.detail.checked.toString(),
        })
      ).then((response) => {
        console.log(response.data);
      });
    },
    update2(event) {
      this.login_with_microsoft = event.detail.checked;

      if (event.detail.checked) {
        this.login_with_google = false;
        this.$axios.post(
          "user.php",
          this.$qs.stringify({
            updateLoginWithGoogle: "updateLoginWithGoogle",
            newValue: "Microsoft",
          })
        );
      } else {
        this.$axios.post(
          "user.php",
          this.$qs.stringify({
            updateLoginWithGoogle: "updateLoginWithGoogle",
            newValue: false,
          })
        );
      }
    },
  },
});
</script>
