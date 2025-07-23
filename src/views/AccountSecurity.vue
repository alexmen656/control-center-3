<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1"></ion-col>
      <ion-col size="10">
        <h2>Login Options</h2>
        <ion-list>
          <ion-item>
            <ion-toggle slot="start" color="success" :checked="login_with_google" labelPlacement="start"
              aria-label="LogIn with Google" @ionChange="update($event)">LogIn with Google</ion-toggle>
          </ion-item>
          <ion-item>
            <ion-toggle slot="start" color="success" :checked="login_with_microsoft" labelPlacement="start"
              aria-label="LogIn with Google" @ionChange="update2($event)">LogIn with Microsoft</ion-toggle>
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
      <ion-col size="1"></ion-col>
      <ion-col size="10">
        <h2>Connected Accounts</h2>
        <ion-list>
          <!-- GitHub -->
          <ion-item button v-if="!login_with_github || !githubAccount" @click="connectGithub({ detail: { checked: true } })">
            <ion-icon name="logo-github" style="font-size:1.5em;vertical-align:middle;margin-right:0.5em;"></ion-icon>
            <span style="vertical-align:middle;color:#888;">Connect your GitHub account now</span>
          </ion-item>
          <ion-item v-else>
            <ion-icon name="logo-github" style="font-size:1.5em;vertical-align:middle;margin-right:0.5em;"></ion-icon>
            <ion-label>GitHub</ion-label>
            <span style="vertical-align:middle;">{{ githubAccount.login }}<span v-if="githubAccount.name"> ({{ githubAccount.name }})</span></span>
          </ion-item>
          <!-- Vercel -->
          <ion-item button v-if="!vercelConnected" @click="connectVercel()">
            <ion-icon name="logo-vercel" style="font-size:1.5em;vertical-align:middle;margin-right:0.5em;"></ion-icon><!--color:#38d996;-->
            <span style="vertical-align:middle;color:#888;">Connect your Vercel account now</span>
          </ion-item>
          <ion-item v-else>
            <ion-icon name="logo-vercel" style="font-size:1.5em;vertical-align:middle;margin-right:0.5em;"></ion-icon><!--color:#38d996;-->
            <ion-label>Vercel</ion-label>
            <span style="vertical-align:middle;">Verbunden</span>
          </ion-item>
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
      githubAccount: null,
      vercelConnected: false,
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
    // GitHub Login Status & Info prüfen
    if (this.user.userID) {
      try {
        const res = await this.$axios.get(`github_token_status.php?userID=${this.user.userID}`);
        this.login_with_github = !!(res.data && res.data.connected);
        if (this.login_with_github) {
          const infoRes = await this.$axios.get(`github_token_info.php?userID=${this.user.userID}`);
          if (infoRes.data && infoRes.data.login) {
            this.githubAccount = infoRes.data;
          } else {
            this.githubAccount = null;
          }
        } else {
          this.githubAccount = null;
        }
        // Vercel Status
        const vercelRes = await this.$axios.get(`vercel_token_status.php?userID=${this.user.userID}`);
        this.vercelConnected = !!(vercelRes.data && vercelRes.data.connected);
      } catch (e) {
        this.login_with_github = false;
        this.githubAccount = null;
        this.vercelConnected = false;
      }
    }
  },
  methods: {
    connectGithub(event) {
    },
    connectVercel() {
      const clientSlug = 'control-center';
      const userId = this.user && this.user.userID ? this.user.userID : '';
      const state = userId ? `user_${userId}` : '';
      const vercelAuthUrl = `https://vercel.com/integrations/${clientSlug}/new?state=${state}`;
      window.location.href = vercelAuthUrl;
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

      if (event.detail.checked) {
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
