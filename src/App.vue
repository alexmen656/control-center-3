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
          <div id="main-content" :class="{ 'has-touch-bar': showTouchBar, 'isNotLogin': isLoginPage }">
            <!--  <SiteTitle v-if="showSiteTitle" :icon="page.icon" :title="page.title" @updateSidebar="updateSidebar()" />-->
            <ion-router-outlet v-if="page.title" @updateSidebar="updateSidebar()" :class="{
              showTitle: showTitle,
            }"></ion-router-outlet>
            <div v-else class="error404">
              <div class="error404-content">
                <div class="error404-animation">
                  <div class="glitch" data-text="404">404</div>
                  <div class="error404-subtitle">Oops! Diese Seite existiert nicht.</div>
                </div>
                <p class="error404-text">
                  Die Seite, die du suchst, wurde verschoben, gelöscht oder hat nie existiert.
                </p>
                <div class="error404-actions">
                  <ion-button @click="$router.push('/dashboard')" color="primary" size="large">
                    <ion-icon slot="start" name="home"></ion-icon>
                    Zur Startseite
                  </ion-button>
                  <ion-button @click="$router.back()" fill="outline" color="medium" size="large">
                    <ion-icon slot="start" name="arrow-back"></ion-icon>
                    Zurück
                  </ion-button>
                </div>
                <div class="error404-decoration">
                  <div class="floating-shape shape-1"></div>
                  <div class="floating-shape shape-2"></div>
                  <div class="floating-shape shape-3"></div>
                </div>
              </div>
            </div>


          </div>
        </ion-split-pane>
        <!-- TouchBar for mobile devices -->
        <TouchBar v-if="showTouchBar" />

        
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
//import SiteTitle from "@/components/SiteTitle.vue";
import ProjectSideBar from "@/components/ProjectSideBar.vue";
import TouchBar from "@/components/TouchBar.vue";
import LoadingSpinner from "@/components/LoadingSpinner.vue";
import { initializeApp } from "firebase/app";
import { useRoute } from "vue-router";
//import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { loadUserData, getUserData } from "@/userData";
import offlineTools from "@/offline/tools.json";
import offlinePages from "@/offline/pages.json";
import { SplashScreen } from "@capacitor/splash-screen";
import { StatusBar, Style } from "@capacitor/status-bar";
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
  checkProjectAccess,
} from "@/utils/authHelpers";

//const { FaceId } = Plugins;

const fmg = FirebaseMessaging;

export default defineComponent({
  name: "App",
  components: {
    SiteHeader,
    SideBar,
    ProjectSideBar,
    TouchBar,
    //SiteTitle,
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
    showTouchBar() {
      // Show TouchBar on mobile platforms when user is logged in and account is active
      return (
        (isPlatform('ios') || isPlatform('android') ||
          (isPlatform('mobile') && window.innerWidth <= 768)) &&
        this.token &&
        this.account_active &&
        this.isJwtValid &&
        this.$route.path !== "/pin" &&
        this.$route.path !== "/pin/"
      );
    },
    isLoginPage() {
      return !(
        this.$route.path === "/login" || this.$route.path === "/login/" ||
        this.$route.path === "/login/verification" || this.$route.path === "/login/verification/" ||
        this.$route.path === "/signup" || this.$route.path === "/signup/"
      );
    }
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
      await checkProjectAccess(); // Check project access restrictions
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
        //console.log("eferfv", route.path);
        if (route.path !== "/login" && route.path !== "/login/verification" && route.path !== "/login/verification/" && route.path !== "/signup" && route.path !== "/signup/") {


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
          } else if (route.path === "/signup" || route.path === "/signup/") {
            page.value = {
              "id": "9",
              "url": "signup",
              "showTitle": "true",
              "icon": "",
              "title": "Sign Up",
              "html": "",
              "pageID": "0"
            };
          } else {
            page.value = {
              "id": "10",
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

    try {
      await SplashScreen.hide();
      
      // Configure iOS Status Bar
      if (isPlatform('ios')) {
        // Use Dark style (black text) for light mode header (#eff3f6)
        await StatusBar.setStyle({ style: Style.Dark });
        await StatusBar.setOverlaysWebView({ overlay: false });
        await StatusBar.setBackgroundColor({ color: '#eff3f6' });
      }
    } catch (error) {
      console.error('Error configuring status bar:', error);
    }

    this.$watch(() => this.$route.params, this.handleRouteChange);

    this.$watch(
      () => window.location.pathname.replace("/", ""),
      this.handleRouteChange
    );

    this.loadPageData("Mounted");
    this.checkMonacoRoute();

    // Watch for theme changes to update status bar
    if (isPlatform('ios')) {
      const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
      const updateStatusBar = async (e) => {
        try {
          if (e.matches) {
            // Dark mode
            await StatusBar.setStyle({ style: Style.Light });
            await StatusBar.setBackgroundColor({ color: '#1e1e1e' });
          } else {
            // Light mode
            await StatusBar.setStyle({ style: Style.Dark });
            await StatusBar.setBackgroundColor({ color: '#eff3f6' });
          }
        } catch (error) {
          console.error('Error updating status bar:', error);
        }
      };
      darkModeQuery.addEventListener('change', updateStatusBar);
      // Set initial state
      updateStatusBar(darkModeQuery);
    }
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
      if (this.$route.path.includes('/codespace') && !this.$route.path.includes('new/codespace') && !this.$route.path.includes('manage/codespaces') && !this.$route.path.includes('table')) {
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

.error404 {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: var(--ion-background-color, #ffffff);
  position: relative;
  overflow: hidden;
}

.error404::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 20% 50%, rgba(234, 14, 43, 0.1) 0%, transparent 50%),
              radial-gradient(circle at 80% 80%, rgba(234, 14, 43, 0.08) 0%, transparent 50%);
  pointer-events: none;
}
.glitch {
  font-size: 120px;
  font-weight: 900;
  color: var(--ion-color-primary, #ea0e2b);
  text-transform: uppercase;
  position: relative;
  text-shadow: 0.05em 0 0 rgba(234, 14, 43, 0.75),
    -0.025em -0.05em 0 rgba(234, 14, 43, 0.5),
    0.025em 0.05em 0 rgba(234, 14, 43, 0.3);
  animation: glitch 500ms infinite;
  margin: 0;
  line-height: 1;
}glitch {
  font-size: 120px;
  font-weight: 900;
  color: #fff;
  text-transform: uppercase;
  position: relative;
  text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75),
    -0.025em -0.05em 0 rgba(0, 255, 0, 0.75),
    0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
  animation: glitch 500ms infinite;
  margin: 0;
  line-height: 1;
}

.glitch::before,
.glitch::after {
  content: attr(data-text);
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.glitch::before {
  left: 2px;
  text-shadow: -2px 0 var(--ion-color-primary-shade, #cf3c4f);
  clip: rect(44px, 450px, 56px, 0);
  animation: glitch-anim 5s infinite linear alternate-reverse;
}

.glitch::after {
  left: -2px;
  text-shadow: -2px 0 var(--ion-color-primary-tint, #ed576b), 2px 2px var(--ion-color-primary-shade, #cf3c4f);
  animation: glitch-anim2 1s infinite linear alternate-reverse;
}

@keyframes glitch {
  0% {
    text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75),
      -0.05em -0.025em 0 rgba(0, 255, 0, 0.75),
      -0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
  }
  14% {
    text-shadow: 0.05em 0 0 rgba(255, 0, 0, 0.75),
      -0.05em -0.025em 0 rgba(0, 255, 0, 0.75),
      -0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
  }
  15% {
    text-shadow: -0.05em -0.025em 0 rgba(255, 0, 0, 0.75),
      0.025em 0.025em 0 rgba(0, 255, 0, 0.75),
      -0.05em -0.05em 0 rgba(0, 0, 255, 0.75);
  }
  49% {
    text-shadow: -0.05em -0.025em 0 rgba(255, 0, 0, 0.75),
      0.025em 0.025em 0 rgba(0, 255, 0, 0.75),
      -0.05em -0.05em 0 rgba(0, 0, 255, 0.75);
  }
  50% {
    text-shadow: 0.025em 0.05em 0 rgba(255, 0, 0, 0.75),
      0.05em 0 0 rgba(0, 255, 0, 0.75), 0 -0.05em 0 rgba(0, 0, 255, 0.75);
  }
  99% {
    text-shadow: 0.025em 0.05em 0 rgba(255, 0, 0, 0.75),
      0.05em 0 0 rgba(0, 255, 0, 0.75), 0 -0.05em 0 rgba(0, 0, 255, 0.75);
  }
  100% {
    text-shadow: -0.025em 0 0 rgba(255, 0, 0, 0.75),
      -0.025em -0.025em 0 rgba(0, 255, 0, 0.75),
      -0.025em -0.05em 0 rgba(0, 0, 255, 0.75);
  }
}

@keyframes glitch-anim {
  0% {
    clip: rect(61px, 9999px, 78px, 0);
  }
  5% {
    clip: rect(33px, 9999px, 144px, 0);
  }
  10% {
    clip: rect(17px, 9999px, 99px, 0);
  }
  15% {
    clip: rect(85px, 9999px, 85px, 0);
  }
  20% {
    clip: rect(2px, 9999px, 125px, 0);
  }
  25% {
    clip: rect(120px, 9999px, 38px, 0);
  }
  30% {
    clip: rect(44px, 9999px, 134px, 0);
  }
  35% {
    clip: rect(96px, 9999px, 71px, 0);
  }
  40% {
    clip: rect(23px, 9999px, 108px, 0);
  }
  45% {
    clip: rect(55px, 9999px, 15px, 0);
  }
  50% {
    clip: rect(89px, 9999px, 133px, 0);
  }
  55% {
    clip: rect(77px, 9999px, 92px, 0);
  }
  60% {
    clip: rect(140px, 9999px, 19px, 0);
  }
  65% {
    clip: rect(11px, 9999px, 66px, 0);
  }
  70% {
    clip: rect(105px, 9999px, 49px, 0);
  }
  75% {
    clip: rect(31px, 9999px, 117px, 0);
  }
  80% {
    clip: rect(68px, 9999px, 82px, 0);
  }
  85% {
    clip: rect(128px, 9999px, 5px, 0);
  }
  90% {
    clip: rect(42px, 9999px, 101px, 0);
  }
  95% {
    clip: rect(14px, 9999px, 146px, 0);
  }
  100% {
    clip: rect(73px, 9999px, 27px, 0);
  }
}

@keyframes glitch-anim2 {
  0% {
    clip: rect(129px, 9999px, 36px, 0);
  }
  5% {
    clip: rect(36px, 9999px, 4px, 0);
  }
  10% {
    clip: rect(85px, 9999px, 66px, 0);
  }
  15% {
    clip: rect(91px, 9999px, 91px, 0);
  }
  20% {
    clip: rect(148px, 9999px, 138px, 0);
  }
  25% {
    clip: rect(38px, 9999px, 122px, 0);
  }
  30% {
    clip: rect(69px, 9999px, 54px, 0);
  }
  35% {
    clip: rect(98px, 9999px, 71px, 0);
  }
  40% {
    clip: rect(146px, 9999px, 34px, 0);
  }
  45% {
    clip: rect(134px, 9999px, 43px, 0);
  }
  50% {
    clip: rect(102px, 9999px, 80px, 0);
  }
  55% {
    clip: rect(119px, 9999px, 44px, 0);
  }
  60% {
    clip: rect(106px, 9999px, 99px, 0);
  }
  65% {
    clip: rect(141px, 9999px, 74px, 0);
  }
  70% {
    clip: rect(20px, 9999px, 78px, 0);
  }
  75% {
    clip: rect(133px, 9999px, 79px, 0);
  }
  80% {
    clip: rect(78px, 9999px, 52px, 0);
  }
  85% {
    clip: rect(35px, 9999px, 39px, 0);
  }
  90% {
    clip: rect(67px, 9999px, 70px, 0);
  }
  95% {
    clip: rect(71px, 9999px, 103px, 0);
  }
  100% {
    clip: rect(83px, 9999px, 40px, 0);
  }
}

.error404-subtitle {
  font-size: 24px;
  color: var(--ion-text-color, #000);
  margin-top: 1rem;
  font-weight: 600;
}

.error404-text {
  font-size: 18px;
  color: var(--ion-color-medium, #92949c);
  margin-bottom: 2rem;
  line-height: 1.6;
}

.error404-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.error404-actions ion-button {
  --border-radius: 12px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: 0.5px;
}

.floating-shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0.1;
  animation: float 6s ease-in-out infinite;
}

.shape-1 {
  width: 80px;
  height: 80px;
  background: #fff;
  top: 20%;
  left: 10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 120px;
  height: 120px;
  background: #fff;
  top: 60%;
  right: 10%;
  animation-delay: 2s;
}

.shape-3 {
  width: 60px;
  height: 60px;
  background: #fff;
  bottom: 20%;
  left: 20%;
  animation-delay: 4s;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .glitch {
    font-size: 80px;
  }
  
  .error404-subtitle {
    font-size: 20px;
  }
  
  .error404-text {
    font-size: 16px;
  }
  
  .error404-actions {
    flex-direction: column;
  }
  
  .error404-actions ion-button {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .glitch {
    font-size: 60px;
  }
  
  .error404-subtitle {
    font-size: 18px;
  }
  
  .error404-text {
    font-size: 14px;
  }
}

.error404-content {
  text-align: center;
  z-index: 10;
  padding: 2rem;
  max-width: 600px;
}

.error404-animation {
  margin-bottom: 2rem;
}
</style>
