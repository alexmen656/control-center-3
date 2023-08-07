<template>
  <ion-app>
    <ion-content>
      <SiteHeader v-if="account_active && token"></SiteHeader>
      <ion-split-pane content-id="main-content">
        <SideBar
          :projects="projects"
          :tools="tools"
          :bookmarks="bookmarks"
          v-if="token && account_active && !$route.params.project"
        ></SideBar>
        <ProjectSideBar
          v-if="token && account_active && $route.params.project"
        ></ProjectSideBar>
        <div id="main-content">
          <SiteTitle
            v-if="
              token &&
              account_active &&
              page.title &&
              (page.showTitle == true || page.showTitle == 'true')
            "
            :icon="page.icon"
            :title="page.title"
            @updateSidebar="updateSidebar()"
          />
          <ion-router-outlet
            v-if="page.title"
            @updateSidebar="updateSidebar()"
            :class="{
              showTitle: page.showTitle == true || page.showTitle == 'true',
            }"
          ></ion-router-outlet>
          <div v-else class="error404">
            <h1>Error 404, Site not found.</h1>
          </div>
        </div>
      </ion-split-pane>
    </ion-content>
    <!--<div class="offline" v-if="!isOnline"><h6>You are offline!</h6></div>-->
    <ion-footer v-if="!isOnline" class="offline" collapse="fade">
      <ion-toolbar>
        <ion-title><h6 class="offline-h6">You are offline!</h6></ion-title>
      </ion-toolbar>
    </ion-footer>
  </ion-app>
</template>

<script>
import {
  IonApp,
  IonRouterOutlet,
  IonSplitPane,
  useIonRouter,
  IonTitle,
  IonFooter,
  IonToolbar,
  IonContent,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";
import SiteHeader from "@/components/Header.vue";
import SideBar from "@/components/SideBar.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import ProjectSideBar from "@/components/ProjectSideBar.vue";
import axios from "axios";
import { initializeApp } from "firebase/app";
import { useRoute } from "vue-router";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import qs from "qs";
import { loadUserData, getUserData } from "@/userData";
import offlineTools from "@/offline/tools.json";
import offlinePages from "@/offline/pages.json";
import { SplashScreen } from "@capacitor/splash-screen";

const firebaseConfig = {
  apiKey: "AIzaSyAF53AYFblvyoeCHvXqUT--C5lnYf095VY",
  authDomain: "alexs-blog-371818.firebaseapp.com",
  projectId: "alexs-blog-371818",
  storageBucket: "alexs-blog-371818.appspot.com",
  messagingSenderId: "993142363546",
  appId: "1:993142363546:web:af5db6e6c5ff5c57105623",
  measurementId: "G-Y452FKG122",
};

const app = initializeApp(firebaseConfig);
let messaging;
try {
  messaging = getMessaging();
  onMessage(messaging, (payload) => {
    console.log("Message received. ", payload);
  });
} catch (err) {
  console.error("Failed to initialize Firebase Messaging", err);
}

getToken(messaging, {
  vapidKey:
    "BLHOWBaM9Ej23udRuBcV5-hUMw-jVCA6czu7wCOtqtyv54u-0NyeughCPUomwSVdhJGUsC54VGdjb2czi2HeTr4",
})
  .then((currentToken) => {
    if (currentToken) {
      axios.post(
        "/control-center/push_notifications_token.php",
        qs.stringify({
          newToken: "newToken",
          token: currentToken,
          platform: window.navigator.userAgent,
          userID: "79",
        })
      );
    } else {
      console.log(
        "No registration token available. Request permission to generate one."
      );
    }
  })
  .catch((err) => {
    console.log("An error occurred while retrieving token. ", err);
  });

export default defineComponent({
  name: "App",
  components: {
    IonApp,
    IonRouterOutlet,
    IonSplitPane,
    SiteHeader,
    SideBar,
    ProjectSideBar,
    SiteTitle,
    IonTitle,
    IonContent,
    IonFooter,
    IonToolbar,
    //IonHeader
  },
  data() {
    return {
      token: localStorage.getItem("token"),
      // account_active: false
    };
  },
  async mounted() {
    await SplashScreen.hide();
    this.$watch(
      () => this.$route.params,
      () => {
        //console.log('URL-Parameter wurden geändert');
        this.page = {};
        this.loadPageData();
      }
    );

    this.$watch(
      () => window.location.pathname.replace("/", ""),
      () => {
        //console.log('URL-Parameter wurden geändert');
        this.page = {};
        this.loadPageData();
      }
    );

    this.loadPageData();
  },
  async created() {
    await loadUserData();
  },
  setup() {
    const pages = ref([]);
    const page = ref({});
    const tools = ref([]);
    const projects = ref([]);
    const bookmarks = ref([]);
    const account_active = ref({});
    const route = useRoute();
    const ionRouter = useIonRouter();
    let paramUrl = "";

    const isOnline = ref(navigator.onLine);

    const loadPageData = async () => {
      //alert("changed");

      if (
        !localStorage.getItem("token") &&
        location.pathname != "/login" &&
        location.pathname != "/login/verification/" &&
        location.pathname != "/login/" &&
        location.pathname != "/login/verification" &&
        location.pathname != "/signup" &&
        location.pathname != "/signup/"
      ) {
        ionRouter.push("/login");
      }
      const userData = getUserData();

      if (
        userData.accountStatus == "pending_verification" &&
        location.pathname != "/pending_verification" &&
        location.pathname != "/pending_verification/"
      ) {
        location.href = "/pending_verification";
      } else if (
        userData.accountStatus != "pending_verification" &&
        (location.pathname == "/pending_verification" ||
          location.pathname == "/pending_verification/")
      ) {
        location.href = "/home";
      }

      if (userData.accountStatus == "pending_verification") {
        account_active.value = false;
      } else {
        account_active.value = true;
      }

      if (!route.params || !route.params.url) {
        console.error("Route params or URL not defined.");
        paramUrl = window.location.pathname.replace("/", "");
        if (paramUrl.charAt(paramUrl.length - 1) == "/") {
          paramUrl = paramUrl.slice(0, -1);
        }
      } else {
        paramUrl = route.params.url;
      }

      if (isOnline.value) {
        //   alert("changed2");

        axios.post("/control-center/pages.php").then((response) => {
          pages.value = response.data;
          const foundPage = pages.value.find((p) => p["url"] === paramUrl);

          if (!foundPage) {
            return;
          }

          page.value = foundPage;
          document.title = page.value.title;
        });

        axios.get("/control-center/sidebar.php").then((response) => {
          tools.value = response.data.tools;
          projects.value = response.data.projects;
          localStorage.setItem("tools", JSON.stringify(tools.value));
          localStorage.setItem("projects", JSON.stringify(projects.value));
        });

        axios
          .get("/control-center/bookmarks.php?getBookmarks=getBookmarks")
          .then((response) => {
            bookmarks.value = response.data;
            localStorage.setItem("bookmarks", JSON.stringify(bookmarks.value));
          });
      } else {
        pages.value = offlinePages;
        const foundPage = pages.value.find((p) => p["url"] === paramUrl);

        if (!foundPage) {
          return;
        } else {
          page.value = foundPage;
          document.title = page.value.title;
        }

        if (localStorage.getItem("tools")) {
          tools.value = JSON.parse(localStorage.getItem("tools"));
        } else {
          tools.value = offlineTools.tools;
        }

        if (localStorage.getItem("projects")) {
          projects.value = JSON.parse(localStorage.getItem("projects"));
        } else {
          projects.value = offlineTools.tools;
        }

        if (localStorage.getItem("bookmarks")) {
          bookmarks.value = JSON.parse(localStorage.getItem("bookmarks"));
        } else {
          bookmarks.value = offlineTools.tools;
        }
      }
    };

    window.addEventListener("online", () => {
      isOnline.value = true;
      loadPageData();
    });

    window.addEventListener("offline", () => {
      isOnline.value = false;
      loadPageData();
    });

    return {
      page: page,
      tools: tools,
      projects: projects,
      bookmarks: bookmarks,
      loadPageData: loadPageData,
      isOnline: isOnline,
      account_active: account_active,
    };
  },

  methods: {
    updateSidebar() {
      const bookmarks = ref([]);
      axios
        .get("/control-center/bookmarks.php?getBookmarks=getBookmarks")
        .then((response) => {
          this.bookmarks = response.data;
          localStorage.setItem("bookmarks", this.bookmarks);
        });
    },
    subscribe() {
      getToken(messaging, {
        vapidKey:
          "BLHOWBaM9Ej23udRuBcV5-hUMw-jVCA6czu7wCOtqtyv54u-0NyeughCPUomwSVdhJGUsC54VGdjb2czi2HeTr4",
      })
        .then((currentToken) => {
          if (currentToken) {
            alert(currentToken);
            console.log(currentToken);
          } else {
            console.log(
              "No registration token available. Request permission to generate one."
            );
          }
        })
        .catch((err) => {
          console.log("An error occurred while retrieving token. ", err);
        });
    },
  },
});
</script>

<style>
.error404 {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.error404 > h1 {
  font-size: 3.2rem;
  padding-bottom: 4rem;
  color: red;
}

.btn-red {
  --background: red !important;
}
.ion-menu {
  width: 300px;
}

ion-menu ion-content {
  --background: var(--ion-item-background, var(--ion-background-color, #fff));
}

ion-menu.md ion-content {
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 46px;
  --padding-bottom: 20px;
}

ion-menu.md ion-list {
  padding: 20px 0;
}

ion-menu.md ion-note {
  margin-bottom: 30px;
}

ion-menu.md ion-list-header,
ion-menu.md ion-note {
  padding-left: 10px;
}

ion-menu.md ion-list#inbox-list {
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
}

ion-menu.md ion-list#inbox-list ion-list-header {
  font-size: 22px;
  font-weight: 600;

  min-height: 20px;
}

ion-menu.md ion-list#labels-list ion-list-header {
  font-size: 16px;

  margin-bottom: 18px;

  color: #757575;

  min-height: 26px;
}

ion-menu.md ion-item {
  --padding-start: 10px;
  --padding-end: 10px;
  border-radius: 4px;
}

ion-menu.md ion-item.selected {
  --background: rgba(var(--ion-color-primary-rgb), 0.14);
}

ion-menu.md ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.md ion-item ion-icon {
  color: #616e7e;
}

ion-menu.md ion-item ion-label {
  font-weight: 500;
}

ion-menu.ios ion-content {
  --padding-bottom: 20px;
}

ion-menu.ios ion-list {
  padding: 20px 0 0 0;
}

ion-menu.ios ion-note {
  line-height: 24px;
  margin-bottom: 20px;
}

ion-menu.ios ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 50px;
}

ion-menu.ios ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.ios ion-item ion-icon {
  font-size: 24px;
  color: #73849a;
}

ion-menu.ios ion-list#labels-list ion-list-header {
  margin-bottom: 8px;
}

ion-menu.ios ion-list-header,
ion-menu.ios ion-note {
  padding-left: 16px;
  padding-right: 16px;
}

ion-menu.ios ion-note {
  margin-bottom: 8px;
}

ion-note {
  display: inline-block;
  font-size: 16px;

  color: var(--ion-color-medium-shade);
}

ion-item.selected {
  --color: var(--ion-color-primary);
}

a {
  text-decoration: none;
  color: red;
}

.mobile-only {
  display: none;
}

.desktop-only {
  display: block;
}

@media only screen and (max-width: 600px) {
  .only-web {
    display: none;
  }

  .mobile-only {
    display: block;
  }

  .desktop-only {
    display: none;
  }
}

router-link,
a {
  color: red;
}

ion-menu-button {
  color: red !important;
}

a {
  color: red !important;
}

.link-container {
  display: flex;
  justify-content: center;
}

.link {
  text-decoration: none;
}

ion-menu.md ion-item.selected {
  --background: rgba(255, 0, 0, 0.14) !important;
}

ion-item.selected {
  --color: red !important;
}

ion-menu ion-item.selected ion-icon {
  color: red !important;
}

.list-md.articles {
  background: var(--ion-background-color);
}

#main-content {
  /*padding-top: 100px !important;*/
  margin-top: 56px !important;
  width: 100%;
}
h1 {
  z-index: 6666;
}

ion-router-outlet.showTitle {
  margin-top: 45px;
}

ion-router-outlet {
  width: 100%;
}

@media (prefers-color-scheme: dark) {
  ion-card {
    --background: black;
  }
}

@media only screen and (min-width: 600px) {
  ion-card {
    padding: 1rem;
    border-radius: 20px;
  }
}

.offline {
  background-color: #212121 !important;
  z-index: 100000;
}

.offline-h6 {
  text-align: center !important;
  width: 100%;
  color: #fff;
  margin: 0.25rem;
}

ion-list {
  background-color: var(--ion-background-color);
}

ion-list {
  border: none !important;
}
</style>
