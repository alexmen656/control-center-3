<template>
  <LoadingSpinner v-if="loading" />
  <div v-if="!loading" :class="store.theme">
    <ion-app>
      <ion-content v-if="showContent">
        <SiteHeader v-if="showHeader" @toggleSidebar="toggleSidebar"></SiteHeader>
        <ion-split-pane content-id="main-content" :class="{ 'collapsed-sidebar': isMenuCollapsed }">
          <ion-menu v-if="token && account_active" content-id="main-content"
            :class="['ion-menu', { 'collapsed-menu': isMenuCollapsed, 'hasToBeDarkmode': hasToBeDarkmode }]"
            type="overlay">
            <ion-content :class="hasToBeDarkmode ? 'hasToBeDarkmode' : ''">
              <SideBar :projects="projects" :tools="tools" :bookmarks="bookmarks" :isCollapsed="isMenuCollapsed"
                v-if="showSideBar" @sidebarToggled="onSidebarToggled"></SideBar>
              <ProjectSideBar :isCollapsed="isMenuCollapsed" :hasToBeDarkmode="hasToBeDarkmode"
                v-if="showProjectSideBar" @sidebarToggled="onSidebarToggled"></ProjectSideBar>
            </ion-content>
          </ion-menu>
          <div id="main-content">
            <SiteTitle v-if="showSiteTitle" :icon="page.icon" :title="page.title" @updateSidebar="updateSidebar()" />
            <ion-router-outlet v-if="page.title" @updateSidebar="updateSidebar()" :class="{
              showTitle: showTitle,
            }"></ion-router-outlet>
            <div v-else class="error404">
              <h1>Error 404, Site not found.</h1>
            </div>
          </div>
        </ion-split-pane>
      </ion-content>
      <!--<div class="offline" v-if="!isOnline"><h6>You are offline!</h6></div>-->
      <ion-footer v-if="!isOnline" class="offline" collapse="fade">
        <ion-toolbar>
          <ion-title>
            <h6 class="offline-h6">You are offline!</h6>
          </ion-title>
        </ion-toolbar>
      </ion-footer>
    </ion-app>
  </div>
</template>

<script>
import { useIonRouter } from "@ionic/vue";
import { defineComponent, ref, onMounted } from "vue";
import SiteHeader from "@/components/Header.vue";
import SideBar from "@/components/SideBar.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import ProjectSideBar from "@/components/ProjectSideBar.vue";
import LoadingSpinner from "@/components/LoadingSpinner.vue";
import { initializeApp } from "firebase/app";
import { useRoute } from "vue-router";
//import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { loadUserData, getUserData } from "@/userData";
import offlineTools from "@/offline/tools.json";
import offlinePages from "@/offline/pages.json";
import { SplashScreen } from "@capacitor/splash-screen";
//import { Plugins } from "@capacitor/core";
import { isPlatform } from "@ionic/vue";
import { FirebaseMessaging } from "@capacitor-firebase/messaging";
import { firebase_config } from "@/firebase_config";
import { store } from "./theme/theme";
import axios from "axios";
import qs from "qs";
import { saveLocal, loadFromLocalStorage } from "@/utils/localStorageHelpers";
import {
  checkPendingVerification,
  checkLoginStatus,
} from "@/utils/authHelpers";

//const { FaceId } = Plugins;

const fmg = FirebaseMessaging;

export default defineComponent({
  name: "App",
  components: {
    SiteHeader,
    SideBar,
    ProjectSideBar,
    SiteTitle,
    LoadingSpinner,
  },
  data() {
    return {
      token: (() => {
        const t = localStorage.getItem("token");
        if (!t) return null;
        try {
          const payload = JSON.parse(atob(t.split(".")[1].replace(/-/g, "+").replace(/_/g, "/")));
          if (payload.exp && Date.now() / 1000 < payload.exp) {
            return t;
          } else {
            localStorage.removeItem("token");
            return null;
          }
        } catch (e) {
          localStorage.removeItem("token");
          return null;
        }
      })(),
      faceIDAvailble: false,
      authenticated: false,
      userData: {},
      theme: localStorage.getItem("themeSet"),
      store,
      isMenuCollapsed: false,
      hasToBeDarkmode: false,
      // account_active: false
    };
  },
  computed: {
    isJwtValid() {
      if (!this.token) return false;
      try {
        const payload = JSON.parse(atob(this.token.split(".")[1].replace(/-/g, "+").replace(/_/g, "/")));
        return payload.exp && Date.now() / 1000 < payload.exp;
      } catch (e) {
        return false;
      }
    },
    showContent() {
      return (
        (!this.faceIDAvailble || this.authenticated || this.$route.path === "/pin" || this.$route.path === "/pin/")
      );
    },
    showHeader() {
      return (
        this.account_active && this.token && this.isJwtValid && this.$route.path !== "/pin" && this.$route.path !== "/pin/"
      );
    },
    showSideBar() {
      return (
        !this.$route.params.project && this.isJwtValid && this.$route.path !== "/pin" && this.$route.path !== "/pin/"
      );
    },
    showProjectSideBar() {
      return (
        this.$route.params.project && this.isJwtValid && this.$route.path !== "/pin" && this.$route.path !== "/pin/"
      );
    },
    showSiteTitle() {
      return (
        this.token && this.isJwtValid && this.account_active && this.page.title && (this.page.showTitle === true || this.page.showTitle === "true") && this.$route.path !== "/pin" && this.$route.path !== "/pin/"
      );
    },
    showTitle() {
      return this.page.showTitle === true || this.page.showTitle === "true";
    },
  },
  watch: {
    '$route'() {
      this.checkMonacoRoute();
    }
  },
  setup() {
    const page = ref({});
    const tools = ref([]);
    const projects = ref([]);
    const bookmarks = ref([]);
    const account_active = ref({});
    const route = useRoute();
    const ionRouter = useIonRouter();
    const loading = ref(true);
    const isOnline = ref(navigator.onLine);
    const paths = ref([
      "/new/project/",
      "/new/project",
      "/info/projects/",
      "/info/projects",
      "/manage/projects/",
      "/manage/projects",
    ]);

    const updateDocumentTitle = (title) => {
      document.title = title;
    };

    const handleOnlineStatus = () => {
      isOnline.value = navigator.onLine;
      if (isOnline.value) {
        loadUserData().then(() => {
          loadPageData("Online");
        });
      }
    };

    const loadPageData = async function (launcher) {
      checkLoginStatus();
      const userData = getUserData();
      console.log(launcher);
      checkPendingVerification(userData);

      if (userData.accountStatus == "pending_verification") {
        account_active.value = false;
      } else {
        account_active.value = true;
      }

      const paramUrl =
        route.params?.url ||
        window.location.pathname.replace(/\/$/, "").replace(/^\//, "");

      if (isOnline.value) {
        if (route.path !== "/login" && route.path !== "/login/verification") {


          axios.post("pages.php").then((res) => {
            const foundPage = res.data.find((p) => p["url"] === paramUrl);
            page.value = foundPage || page.value;
            updateDocumentTitle(page.value.title);
            loading.value = false;
          });

          Promise.all([
            axios.get("sidebar.php"),
            axios.get("bookmarks.php?getBookmarks=getBookmarks"),
          ])
            .then(([sidebarResponse, bookmarksResponse]) => {
              tools.value = sidebarResponse.data.tools;
              projects.value = Object.values(sidebarResponse.data.projects);
              bookmarks.value = bookmarksResponse.data;

              saveLocal("tools", tools.value);
              saveLocal("projects", projects.value);
              saveLocal("bookmarks", bookmarks.value);
            })
            .catch((error) => {
              console.error("Error loading data:", error);
            });
        } else {
          loading.value = false;
          if (route.path === "/login/verification" || route.path === "/login/verification/") {
            page.value = {
              "id": "8",
              "url": "login/verification",
              "showTitle": "true",
              "icon": "",
              "title": "Login Verification",
              "html": "",
              "pageID": "0"
            };
          } else {
            page.value = {
              "id": "9",
              "url": "login",
              "showTitle": "true",
              "icon": "",
              "title": "Login",
              "html": "",
              "pageID": "0"
            };
          }
          updateDocumentTitle(page.value.title);
        }
      } else {
        const foundPage = offlinePages.find((p) => p["url"] === paramUrl);
        page.value = foundPage || page.value;
        updateDocumentTitle(page.value.title);
        tools.value = loadFromLocalStorage("tools", offlineTools.tools);
        projects.value = loadFromLocalStorage("projects", offlineTools.tools);
        bookmarks.value = loadFromLocalStorage("bookmarks", offlineTools.tools);
      }
    };

    const handleRouteChange = () => {
      if (
        !paths.value.includes(location.pathname) &&
        location.pathname.includes("project")
      ) {
        checkUserPermissions(route.params.project);
      }

      page.value = {};
      loadUserData().then(() => {
        loadPageData("Route");
      });
    };

    const checkUserPermissions = async (project) => {
      try {
        const res = await axios.post(
          "projects.php",
          qs.stringify({
            checkUserPermissions: "checkUserPermissions",
            project: project.replace("ü", "ue"),
          })
        );
        if (!res.data.success) {
          ionRouter.push("/no-permission");
        }
      } catch (error) {
        console.error("Error checking user permissions:", error);
      }
    };

    window.addEventListener("online", handleOnlineStatus);
    window.addEventListener("offline", handleOnlineStatus);

    onMounted(() => {
      handleOnlineStatus();
      handleRouteChange();
    });

    return {
      page,
      tools,
      projects,
      bookmarks,
      loadPageData,
      isOnline,
      account_active,
      loading,
      handleRouteChange,
      checkUserPermissions,
      paths,
    };
  },
  async created() {
    if (this.token && this.$route.path !== "/login" && this.$route.path !== "/login/verification" && this.$route.path !== "/login/verification/") {
      try {
        const res = await axios.post("token_verify.php", {}, {
          headers: { Authorization: this.token }
        });
        if (!res.data.valid) {
          localStorage.removeItem("token");
          this.token = null;
          this.$router.push("/login");

          initializeApp(firebase_config);
          this.emitter.on("authenticated", () => {
            this.authenticated = true;
          });

          if (isPlatform("ios")) {
            if (FaceId) {
              FaceId.isAvailable().then((checkResult) => {
                this.faceIDAvailble = true;
                if (checkResult.value) {
                  FaceId.auth()
                    .then(() => {
                      this.authenticated = true;
                    })
                    .catch(() => {
                      this.goTo("/pin");
                    });
                } else {
                  this.goTo("/pin");
                }
              });
            } else {
              console.log("FaceID Plugin could not be loaded.");
            }
          }

          if (
            !this.paths.includes(location.pathname) &&
            location.pathname.includes("project")
          ) {
            this.checkUserPermissions(this.$route.params.project);
          }

        }
      } catch (e) {
        localStorage.removeItem("token");
        this.token = null;
        this.$router.push("/login");
      }
    }
  },
  async mounted() {
    this.emitter.on("updateSidebar", async () => {
      await this.updateSidebar();
    });

    await loadUserData();
    this.userData = await getUserData();

    const checkSupported = () => {
      if ("Notification" in window) {
        return true;
      } else {
        return false;
      }
    };

    const checkPermissions = async () => {
      const result = await fmg.checkPermissions();
      return result.receive;
    };

    const requestPermissions = async () => {
      const result = await fmg.requestPermissions();
      return result.receive;
    };

    const getToken = async () => {
      const result = await fmg.getToken({
        vapidKey: firebase_config.vapidKey,
      });
      return result.token;
    };

    if (this.userData) {
      if (checkSupported() == true) {
        if (checkPermissions()) {
          getToken().then((token) => {
            axios.post(
              "push_notifications_token.php",
              qs.stringify({
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
    }

    await SplashScreen.hide();
    this.$watch(() => this.$route.params, this.handleRouteChange);

    this.$watch(
      () => window.location.pathname.replace("/", ""),
      this.handleRouteChange
    );

    this.loadPageData("Mounted");
    this.checkMonacoRoute();
  },
  methods: {
    onSidebarToggled(isCollapsed) {
      this.isMenuCollapsed = isCollapsed;
    },
    toggleSidebar() {
      this.isMenuCollapsed = !this.isMenuCollapsed;
    },
    checkMonacoRoute() {
      // Auto-collapse sidebar for Monaco editor
      if (this.$route.path.includes('/monaco')) {
        this.isMenuCollapsed = true;
        this.hasToBeDarkmode = true;
      } else {
        this.isMenuCollapsed = false;
        this.hasToBeDarkmode = false;
      }
    },
    goTo(location) {
      this.$router.push(location);
    },
    setTheme(value) {
      // @ts-ignore: Object is possibly 'null'.
      localStorage.setItem("themeSet", value);
      store.setItem();
    },
    async updateSidebar() {
      const res = await axios.get("bookmarks.php?getBookmarks=getBookmarks");
      this.bookmarks = res.data;
      localStorage.setItem("bookmarks", this.bookmarks);
      const res2 = await axios.get("sidebar.php");
      this.projects = Object.values(res2.data.projects);
      localStorage.setItem("projects", this.projects);
      console.log("Sidebar updated");
    },
  },
});
</script>
<style scoped>
ion-menu.hasToBeDarkmode,
ion-content.hasToBeDarkmode {
  --background: #1e1e1e;
  /*#121212;*/
  border-color: #1e1e1e;
}
</style>
