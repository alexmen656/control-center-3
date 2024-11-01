<template>
  <LoadingSpinner v-if="loading" />
  <div v-if="!loading" :class="store.theme">
    <ion-app>
      <ion-content v-if="showContent">
        <SiteHeader v-if="showHeader"></SiteHeader>
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
                v-if="showSideBar"
              ></SideBar>
              <ProjectSideBar v-if="showProjectSideBar"></ProjectSideBar>
            </ion-content>
          </ion-menu>
          <div id="main-content">
            <SiteTitle
              v-if="showSiteTitle"
              :icon="page.icon"
              :title="page.title"
              @updateSidebar="updateSidebar()"
            />
            <ion-router-outlet
              v-if="page.title"
              @updateSidebar="updateSidebar()"
              :class="{
                showTitle: showTitle,
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
import { defineComponent, ref, onMounted } from "vue";
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
import axios from "axios";
import qs from "qs";
import { saveLocal, loadFromLocalStorage } from "@/utils/localStorageHelpers";
import {
  checkPendingVerification,
  checkLoginStatus,
} from "@/utils/authHelpers";

const { FaceId } = Plugins;

const fmg = FirebaseMessaging;

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
      faceIDAvailble: false,
      authenticated: false,
      userData: {},
      theme: localStorage.getItem("themeSet"),
      store,
      // account_active: false
    };
  },
  computed: {
    showContent() {
      return (
        !this.faceIDAvailble ||
        this.authenticated ||
        this.$route.path === "/pin" ||
        this.$route.path === "/pin/"
      );
    },
    showHeader() {
      return (
        this.account_active &&
        this.token &&
        this.$route.path !== "/pin" &&
        this.$route.path !== "/pin/"
      );
    },
    showSideBar() {
      return (
        !this.$route.params.project &&
        this.$route.path !== "/pin" &&
        this.$route.path !== "/pin/"
      );
    },
    showProjectSideBar() {
      return (
        this.$route.params.project &&
        this.$route.path !== "/pin" &&
        this.$route.path !== "/pin/"
      );
    },
    showSiteTitle() {
      return (
        this.token &&
        this.account_active &&
        this.page.title &&
        (this.page.showTitle === true || this.page.showTitle === "true") &&
        this.$route.path !== "/pin" &&
        this.$route.path !== "/pin/"
      );
    },
    showTitle() {
      return this.page.showTitle === true || this.page.showTitle === "true";
    },
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
            projects.value = sidebarResponse.data.projects;
            bookmarks.value = bookmarksResponse.data;

            saveLocal("tools", tools.value);
            saveLocal("projects", projects.value);
            saveLocal("bookmarks", bookmarks.value);
          })
          .catch((error) => {
            console.error("Error loading data:", error);
          });
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
  created() {
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
  },
  async mounted() {
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
  },
  methods: {
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
    },
  },
});
</script>
