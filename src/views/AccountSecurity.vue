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
         <ion-item>
           <ion-toggle
             slot="start"
             color="success"
             :checked="login_with_github"
             labelPlacement="start"
             aria-label="LogIn with GitHub"
             @ionChange="connectGithub($event)"
           >LogIn with GitHub</ion-toggle>
         </ion-item>
          <!-- Coming Soon-->
          <!--
            <ion-item>
            <ion-label>LogIn with Facebook</ion-label>
            <ion-toggle
              slot="end"
              name="kiwi"
              color="success"
    },
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
     login_with_github: false,
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
    // GitHub Login Status (optional, falls im Userobjekt vorhanden)
    if (this.user.login_with_github) {
      this.login_with_github = true;
    }
  },
  methods: {
        connectGithub(event) {
      this.login_with_github = event.detail.checked;
      if (event.detail.checked) {
        // OAuth2-URL für GitHub (Client-ID und Redirect-URL anpassen!)
        const clientId = 'Ov23liwAe9al1YhVcwrK';
        const redirectUri = encodeURIComponent('https://alex.polan.sk/control-center/github_oauth_callback.php');//control-center.eu
        const scope = 'repo user';
        // User-ID für state-Parameter
        const userId = this.user && this.user.userID ? this.user.userID : '';
        const state = userId ? `user_${userId}` : '';
        const githubAuthUrl = `https://github.com/login/oauth/authorize?client_id=${clientId}&redirect_uri=${redirectUri}&scope=${scope}&state=${state}`;
        window.location.href = githubAuthUrl;
      } else {
        // Optional: Logout/Token entfernen
        // this.$axios.post('user.php', this.$qs.stringify({ updateLoginWithGithub: false }));
      }
    },
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
