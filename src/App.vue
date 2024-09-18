<template>
  <div :class="store.theme">
    <ion-app>
      <ion-content
        v-if="
          !faceIDAvaible ||
          authenticated ||
          $route.path == '/pin' ||
          $route.path == '/pin/'
        "
      >
        <SiteHeader
          v-if="
            account_active &&
            token &&
            $route.path != '/pin' &&
            $route.path != '/pin/'
          "
        ></SiteHeader>
        <ion-split-pane content-id="main-content">
          <ion-menu
            v-if="token && account_active"
            content-id="main-content"
            class="ion-menu"
            type="overlay"
          >
            <ion-content>
              <SideBar
                :projects="projects"
                :tools="tools"
                :bookmarks="bookmarks"
                v-if="
                  !$route.params.project &&
                  $route.path != '/pin' &&
                  $route.path != '/pin/'
                "
              ></SideBar>
              <ProjectSideBar
                v-if="
                  $route.params.project &&
                  $route.path != '/pin' &&
                  $route.path != '/pin/'
                "
              ></ProjectSideBar>
            </ion-content>
          </ion-menu>
          <div id="main-content">
            <SiteTitle
              v-if="
                token &&
                account_active &&
                page.title &&
                (page.showTitle == true || page.showTitle == 'true') &&
                $route.path != '/pin' &&
                $route.path != '/pin/'
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
  </div>
</template>

<script>
import { useIonRouter } from "@ionic/vue";
import { defineComponent, ref } from "vue";
import SiteHeader from "@/components/Header.vue";
import SideBar from "@/components/SideBar.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import ProjectSideBar from "@/components/ProjectSideBar.vue";
import { initializeApp } from "firebase/app";
import { useRoute } from "vue-router";
//import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { loadUserData, getUserData } from "@/userData";
import offlineTools from "@/offline/tools.json";
import offlinePages from "@/offline/pages.json";
import { SplashScreen } from "@capacitor/splash-screen";
import { Plugins } from "@capacitor/core";
import { isPlatform } from "@ionic/vue";
import { FirebaseMessaging } from "@capacitor-firebase/messaging";
import { firebase_config } from "@/firebase_config";
import { store } from "./theme/theme";

const { FaceId } = Plugins;

export default defineComponent({
  name: "App",
  components: {
    SiteHeader,
    SideBar,
    ProjectSideBar,
    SiteTitle,
  },
  data() {
    return {
      token: localStorage.getItem("token"),
      faceIDAvaible: false,
      authenticated: false,
      userData: {},
      theme: localStorage.getItem("themeSet"),
      store,
      // account_active: false
    };
  },
  async mounted() {
    await loadUserData();
    this.userData = await getUserData();

    const checkPermissions = async () => {
      const result = await FirebaseMessaging.checkPermissions();
      return result.receive;
    };

    const requestPermissions = async () => {
      const result = await FirebaseMessaging.requestPermissions();
      return result.receive;
    };

    const getToken = async () => {
      const result = await FirebaseMessaging.getToken({
        vapidKey: firebase_config.vapidKey,
      });
      return result.token;
    };
    if (this.userData) {
      if (checkPermissions()) {
        getToken().then((token) => {
          this.$axios.post(
            "push_notifications_token.php",
            this.$qs.stringify({
              newToken: "newToken",
              token: token,
              platform: window.navigator.userAgent,
              userID: this.userData.userID,
            })
          );
        });
      } else {
        requestPermissions();
      }
    }

    await SplashScreen.hide();
    this.$watch(
      () => this.$route.params,
      () => {
        //alert(location.pathname);
        //alert(location.pathname);
        if (location.pathname.includes("project") && location.pathname != "/new/project/" && location.pathname != "/new/project") {
          const project = this.$route.params.project;
          this.$axios
            .post(
              "projects.php",
              this.$qs.stringify({
                checkUserPermissions: "checkUserPermissions",
                project: project,
              })
            )
            .then((res) => {
              if (!res.data.success) {
                this.$router.push("/no-permission");
              }
            });
        }
        this.page = {};
        this.loadPageData();
      }
    );

    this.$watch(
      () => window.location.pathname.replace("/", ""),
      () => {
        //if (this.authenticated == true) {
        if (window.location.pathname.includes("projects")) {
          alert(1);
          const project = this.$route.params.project;
          this.$axios
            .post(
              "projects.php",
              this.$qs.stringify({
                checkUserPermissions: "checkUserPermissions",
                project: project,
              })
            )
            .then((res) => {
              if (!res.data.success) {
                this.$router.push("/no-permission");
              }
            });
        }
        //}
        this.page = {};
        this.loadPageData();
      }
    );

    this.loadPageData();
  },
  async created() {
    initializeApp(firebase_config);
    this.emitter.on("authenticated", () => {
      this.authenticated = true;
    });
    if (isPlatform("ios")) {
      FaceId.isAvailable().then((checkResult) => {
        this.faceIDAvaible = true;

        if (checkResult.value) {
          FaceId.auth()
            .then(() => {
              this.authenticated = true;
            })
            .catch(() => {
              //error
              this.$router.push("/pin");
            });
        } else {
          this.$router.push("/pin");
        }
      });
    }

    if (location.pathname.includes("project")) {
      const project = this.$route.params.project;
      this.$axios
        .post(
          "projects.php",
          this.$qs.stringify({
            checkUserPermissions: "checkUserPermissions",
            project: project,
          })
        )
        .then((res) => {
          if (!res.data.success) {
            this.$router.push("/no-permission");
          }
        });
    }
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

    const loadPageData = async function () {
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
        this.$axios.post("pages.php").then((response) => {
          pages.value = response.data;
          const foundPage = pages.value.find((p) => p["url"] === paramUrl);

          if (!foundPage) {
            return;
          }

          page.value = foundPage;
          document.title = page.value.title;
        });

        this.$axios.get("sidebar.php").then((response) => {
          tools.value = response.data.tools;
          projects.value = response.data.projects;
          localStorage.setItem("tools", JSON.stringify(tools.value));
          localStorage.setItem("projects", JSON.stringify(projects.value));
        });

        this.$axios
          .get("bookmarks.php?getBookmarks=getBookmarks")
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
    setTheme(value) {
      // @t s-ignore: Object is possibly 'null'.
      localStorage.setItem("themeSet", value);
      store.setItem();
    },
    updateSidebar: function () {
      // const bookmarks = ref([]);
      this.$axios
        .get("bookmarks.php?getBookmarks=getBookmarks")
        .then((response) => {
          this.bookmarks = response.data;
          localStorage.setItem("bookmarks", this.bookmarks);
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
  color: var(--ion-color-primary);
}

.btn-red {
  --background: var(--ion-color-primary) !important;
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
  --background: rgba(
    var(--ion-color-primary-rgb),
    0.14
  ); /*var(--ion-color-primary-rgb)*/
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
  color: var(--ion-color-primary);
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
  color: var(--ion-color-primary);
}

ion-menu-button {
  color: var(--ion-color-primary) !important;
}

a {
  color: var(--ion-color-primary) !important;
}

.link-container {
  display: flex;
  justify-content: center;
}

.link {
  text-decoration: none;
}

/*ion-menu.md ion-item.selected {
  --background: rgba(255, 0, 0, 0.14) !important;
}*/

ion-item.selected {
  --color: var(--ion-color-primary) !important;
}

ion-menu ion-item.selected ion-icon {
  color: var(--ion-color-primary) !important;
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
