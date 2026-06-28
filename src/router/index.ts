import { createRouter, createWebHistory } from "@ionic/vue-router";
import { RouteRecordRaw } from "vue-router";
import LogIn from "../views/LogIn.vue";
import LogInVerification from "../views/LogInVerification.vue";
import ChatsView from "../views/ChatsView.vue";
import DatabasesView from "../views/Databases.vue";
import TableDetail from "../views/TableDetail.vue";
import MyAccount from "../views/MyAccount.vue";
import PhotoView from "../views/PhotoView.vue";
import PinVerification from "../views/PinVerification.vue";

const routes: Array<RouteRecordRaw> = [
  {
    path: "/",
    redirect: "/projects",
  },
  {
    path: "/projects",
    component: () => import("../views/PageViev.vue"),
  },
  {
    path: "/login",
    component: LogIn,
  },
  {
    path: "/login/verification",
    component: LogInVerification,
  },
  {
    path: "/messages",
    component: ChatsView,
  },
  {
    path: "/messages/:id",
    component: ChatsView,
  },
  {
    path: "/messages/new/group",
    component: () => import("../views/NewGroup.vue"),
  },
  {
    path: "/users",
    component: () => import("../views/ManageUsers.vue"),
  },
  {
    path: "/access-log",
    component: () => import("../views/AccessLog.vue"),
  },
  {
    path: "/databases",
    component: DatabasesView,
  },
  {
    path: "/databases/table/:name",
    component: TableDetail,
  },
  {
    path: "/chat/:id",
    component: () => import("../views/ChatView.vue"),
  },
  {
    path: "/my-account",
    component: MyAccount,
  },
  {
    path: "/manage/bookmarks",
    component: () => import("../views/ManageBookmarks.vue"),
  },
  {
    path: "/manage/projects",
    component: () => import("../views/ManageView.vue"),
  },
  {
    path: "/manage/domains",
    component: () => import("../views/ManageDomains.vue"),
  },
  {
    path: "/domains",
    component: () => import("../views/ManageDomains.vue"),
  },
  {
    path: "/my-account/personal-information",
    component: () => import("../views/PersonalInformationView.vue"),
  },
  {
    path: "/my-account/logout",
    component: () => import("../views/LogOutView.vue"),
  },
  {
    path: "/photo",
    component: PhotoView,
  },
  {
    path: "/pin",
    component: PinVerification,
  },
  {
    path: "/new/site/",
    component: () => import("../views/NewProject.vue"),
  },
  {
    path: "/new/doc/",
    component: () => import("../views/CreatePage.vue"),
  },
  {
    path: "/new/project/",
    component: () => import("../views/NewProject.vue"),
  },
  {
    path: "/filesystem/",
    component: () => import("../views/FileSystem.vue"),
  },
  {
    path: "/pages/",
    component: () => import("../views/ManagePages.vue"),
  },
  {
    path: "/manage-store/",
    component: () => import("../views/ManageStore.vue"),
  },
  {
    path: "/info/icons/",
    component: () => import("../views/AllIcons.vue"),
  },
  {
    path: "/info/:function/",
    component: () => import("../views/InfoView.vue"),
  },
  /*{
    path: '/project/:project/',
    component: () => import('../views/ProjectView.vue'),
  },*/
  {
    path: "/project/:project/new/tool/",
    component: () => import("../views/NewTool.vue"),
  },
  {
    path: "/project/:project/new/form",
    component: () => import("../views/NewForm.vue"),
  },
  {
    path: "/project/:project/filesystem",
    component: () => import("../views/ProjectFileSystem.vue"),
  },
  /*{
    path: '/project/:project/databases',
    component: () => import('../views/ProjectView.vue'),
  },*/
  {
    path: "/project/:project/manage/tools",
    component: () => import("../views/ManageTools.vue"),
  },
  /*
  {
    path: '/project/:project/whatsapp-bot',
    component: () => import('../views/ProjectView.vue'),
  },
  */
  {
    path: "/project/:project/info",
    component: () => import("../views/ProjectInfo.vue"),
  },
  {
    path: "/project/:project/info/sidebar-editor",
    component: () => import("../views/SidebarEditor.vue"),
    name: "sidebar-editor",
  },
  {
    path: "/project/:project/ai-dashboard-generator",
    component: () => import("../views/AIDashboardGenerator.vue"),
  },
  /*
  {
    path: '/project/:project/:url(.*)',
    component: () => import('../views/ProjectView.vue'),
  },*/
  /*{
    path: '/project/:project/pages',
    component: () => import('../views/PagesView.vue'),
  },*/
  {
    path: "/project/:project/manage/pages",
    component: () => import("../views/PagesView.vue"),
  },
  {
    path: "/project/:project/new/wb",
    component: () => import("../views/NewComponent.vue"),
  },
  {
    path: "/project/:project/page/:page",
    component: () => import("../views/PageView.vue"),
  },
  {
    path: "/project/:project/page/:page/config",
    component: () => import("../views/ComponentSettings.vue"),
  },
  {
    path: "/project/:project/page/:page/:component",
    component: () => import("../views/PageComponent.vue"),
  },
  {
    path: "/project/:project",
    component: () => import("../views/ProjectView_new.vue"),
  },
  {
    path: "/project/:project/package-manager",
    component: () => import("../views/PackageManager.vue"),
  },
  {
    path: "/project/:project/info",
    component: () => import("../views/ProjectInfo.vue"),
  },
  /*  {
      path: '/drop',
      component: () => import('../views/DropZone.vue'),
    },*/
  {
    path: "/signup",
    component: () => import("../views/SignUp.vue"),
  },
  {
    path: "/pending_verification",
    component: () => import("../views/PendingVerification.vue"),
  },
  {
    path: "/my-account/account-security",
    component: () => import("../views/AccountSecurity.vue"),
  },
  {
    path: "/project/:project/dashboard/:dashboard",
    component: () => import("../views/DashboardView.vue"),
  },
  {
    path: "/project/:project/module-store",
    component: () => import("../views/StoreOverview.vue"),
  },
  {
    path: "/project/:project/new/service",
    component: () => import("../views/NewService.vue"),
  },
  {
    path: "/project/:project/manage/services",
    component: () => import("../views/ManageServices.vue"),
  },
  {
    path: "/project/:project/services/:service",
    component: () => import("../views/ServiceView.vue"),
  },
  {
    path: "/project/:project/services/:service/config",
    component: () => import("../views/ServiceConfigView.vue"),
  },
  // Codespace Routes
  {
    path: "/project/:project/manage/codespaces",
    component: () => import("../views/ManageCodespaces.vue"),
  },
  {
    path: "/project/:project/new/codespace",
    component: () => import("../views/ManageCodespaces.vue"),
  },
  // API Routes
  {
    path: "/project/:project/new/api",
    component: () => import("../apis/ManageApis.vue"),
  },
  {
    path: "/project/:project/manage/apis",
    component: () => import("../apis/ManageApis.vue"),
  },
  {
    path: "/project/:project/apis/:apiSlug",
    component: () => import("../apis/ApiDocumentation.vue"),
  },
  {
    path: "/project/:project/apis/:apiSlug/settings",
    component: () => import("../apis/ApiSettings.vue"),
  },
  {
    path: "/project/:project/chat-app",
    component: () => import("../views/ChatApp.vue"),
  },
  {
    path: "/project/:project/chat-app/chat/:id",
    name: "ChatDetail",
    component: () => import("../views/ChatDetail.vue"),
  },
  {
    path: "/project/:project/chat-app/config",
    component: () => import("../views/ChatAppConfig.vue"),
  },
  /*{
    path: "/project/:project/web-builder",
    component: () => import("../views/WebBuilderView.vue"),
  },*/
  {
    path: "/project/:project/wb/:wb_project",
    component: () => import("../views/WebBuilderView.vue"),
  },
  {
    path: "/project/:project/wb/:wb_project/overview",
    component: () => import("../views/WebBuilderView.vue"),
  },
  {
    path: "/project/:project/wb/:wb_project/:page",
    component: () => import("../views/WebBuilderPageView.vue"),
  },
  {
    path: "/no-permission",
    component: () => import("../views/NoPermission.vue"),
  },
];

const modules = import.meta.glob("@/modules/*/routes.ts", { eager: true });

for (const path in modules) {
  const moduleRoutes = (modules[path] as { default: RouteRecordRaw[] }).default;

  const moduleName =
    path
      .split("/")
      .slice(-2, -1)[0]
      ?.replace(/[^a-zA-Z0-9]/g, "-")
      ?.toLowerCase() || "default-module";

  const transformedRoutes = moduleRoutes.map((route) => ({
    ...route,
    path: `/project/:project${route.path.startsWith("/") ? "" : "/"}${
      route.path
    }`,
  }));

  routes.push(...transformedRoutes);
}

const services = import.meta.glob("@/user_services/*/routes.ts", {
  eager: true,
});

for (const path in services) {
  const serviceRoutes = (services[path] as { default: RouteRecordRaw[] })
    .default;

  const transformedRoutes = serviceRoutes.map((route) => ({
    ...route,
    path: `/project/:project${
      route.path.startsWith("/") ? "/services" : "/services/"
    }${route.path}`,
  }));

  routes.push(...transformedRoutes);
}

routes.push(
  {
    path: "/project/:project/forms/:form/edit",
    component: () => import("../views/EditTool.vue"),
  },
  {
    path: "/project/:project/forms/:form/config",
    component: () => import("../views/FormConfig.vue"),
  },
  {
    path: "/project/:project/forms/:form",
    name: "FormDisplay",
    component: () => import("../views/FormDisplay.vue"),
  },
  {
    path: "/project/:project/manage/forms",
    name: "ManageTables",
    component: () => import("../views/ManageTables.vue"),
  },
  {
    path: "/my-account/preferences",
    component: () => import("../views/settingsModal.vue"),
  },
  {
    path: "/:url(.*)",
    component: () => import("../views/PageViev.vue"),
  },
);

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

export default router;
