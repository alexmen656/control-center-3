import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
//import PageView from '../views/PageViev.vue'
import LogIn from '../views/LogIn.vue'
import LogInVerification from '../views/LogInVerification.vue'
//import MessagesView from '../views/MesagesView.vue'
import ChatsView from '../views/ChatsView.vue'
import DatabasesView from '../views/Databases.vue'
import TableDetail from '../views/TableDetail.vue'
import MyAccount from '../views/MyAccount.vue'
import PhotoView from '../views/PhotoView.vue'
import PinVerification from '../views/PinVerification.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    component: LogIn,
  },
  {
    path: '/login/verification',
    component: LogInVerification,
  },
  {
    path: '/messages',
    component: ChatsView,//MessagesVeiw
  },
  {
    path: '/messages/:id',
    component: ChatsView,//MessagesVeiw
  },
  {
    path: '/messages/new/group',
    component: () => import('../views/NewGroup.vue'),
  },
  {
    path: '/users',
    component: () => import('../views/ManageUsers.vue'),
  },
  {
    path: '/databases',
    component: DatabasesView,
  },
  {
    path: '/databases/table/:name',
    component: TableDetail,
  },
  {
    path: '/chat/:id',
    component: () => import('../views/ChatView.vue'),
  },
  {
    path: '/my-account',
    component: MyAccount,
  },
  {
    path: '/manage/bookmarks',
    component: () => import('../views/ManageView.vue'),
  },
  {
    path: '/manage/projects',
    component: () => import('../views/ManageView.vue'),
  },
  {
    path: '/my-account/personal-information',
    component: () => import('../views/PersonalInformationView.vue'),
  },
  {
    path: '/my-account/logout',
    component: () => import('../views/LogOutView.vue'),
  },
  {
    path: '/photo',
    component: PhotoView,
  },
  {
    path: '/pin',
    component: PinVerification,
  },
  {
    path: '/new/site/',
    component: () => import('../views/NewProject.vue'),
  },
  {
    path: '/new/doc/',
    component: () => import('../views/CreatePage.vue'),
  },
  {
    path: '/new/project/',
    component: () => import('../views/NewProject.vue'),
  },
  {
    path: '/telegram/bot',
    component: () => import('../views/TelegramBot.vue'),
  },
  {
    path: '/filesystem/',
    component: () => import('../views/FileSystem.vue'),
  },
  {
    path: '/pages/',
    component: () => import('../views/ManagePages.vue'),
  },
  {
    path: '/notepad/',
    component: () => import('../views/NotePad.vue'),
  },
  {
    path: '/info/icons/',
    component: () => import('../views/AllIcons.vue'),
  },
  {
    path: '/info/:function/',
    component: () => import('../views/InfoView.vue'),
  },
  /*{
    path: '/project/:project/',
    component: () => import('../views/ProjectView.vue'),
  },*/
  {
    path: '/project/:project/new-tool/',
    component: () => import('../views/NewTool.vue'),
  },
  {
    path: '/project/:project/filesystem',
    component: () => import('../views/ProjectFileSystem.vue'),
  },
  /*{
    path: '/project/:project/databases',
    component: () => import('../views/ProjectView.vue'),
  },*/
  {
    path: '/project/:project/manage/tools',
    component: () => import('../views/ManageTools.vue'),
  },
  /*
  {
    path: '/project/:project/whatsapp-bot',
    component: () => import('../views/ProjectView.vue'),
  },
  */
  {
    path: '/project/:project/info',
    component: () => import('../views/ProjectInfo.vue'),
  },
  {
    path: '/project/:project/telegram-bot',
    component: () => import('../views/TelegramBot.vue'),
  },
  {
    path: '/project/:project/telegram-bot/config',
    component: () => import('../views/TelegramBotConfig.vue'),
  },
  {
    path: '/project/:project/newsletter/config',
    component: () => import('../views/NewsletterViewConfig.vue'),
  },
  {
    path: '/project/:project/newsletter',
    component: () => import('../views/NewsletterView.vue'),
  },
  {
    path: '/project/:project/ai-dashboard-generator',
    component: () => import('../views/AIDashboardGenerator.vue'),
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
    path: '/project/:project/manage/pages',
    component: () => import('../views/PagesView.vue'),
  },
  {
    path: '/project/:project/new/page',
    component: () => import('../views/NewComponent.vue'),
  },
  {
    path: '/project/:project/page/:page',
    component: () => import('../views/PageView.vue'),
  },
  {
    path: '/project/:project/page/:page/config',
    component: () => import('../views/ComponentSettings.vue'),
  },
  {
    path: '/project/:project/page/:page/:component',
    component: () => import('../views/PageComponent.vue'),
  },
  {
    path: '/project/:project',
    component: () => import('../views/ProjectView.vue'),
  },
  {
    path: '/project/:project/package-manager',
    component: () => import('../views/PackageManager.vue'),
  },
  {
    path: '/project/:project/info',
    component: () => import('../views/ProjectInfo.vue'),
  },
  /*  {
      path: '/drop',
      component: () => import('../views/DropZone.vue'),
    },*/
  {
    path: '/signup',
    component: () => import('../views/SignUp.vue'),
  },
  {
    path: '/pending_verification',
    component: () => import('../views/PendingVerification.vue'),
  },
  {
    path: '/my-account/account-security',
    component: () => import('../views/AccountSecurity.vue'),
  },
  {
    path: '/project/:project/dashboard/:dashboard',
    component: () => import('../views/DashboardView.vue'),
  },
  {
    path: '/project/:project/module-store',
    component: () => import('../views/StoreOverview.vue'),
  },
  {
    path: '/project/:project/new/service',
    component: () => import('../views/NewService.vue'),
  },
  {
    path: '/project/:project/manage/services',
    component: () => import('../views/ManageServices.vue'),
  },
  {
    path: '/project/:project/services/:service',
    component: () => import('../views/ServiceView.vue'),
  },
  {
    path: '/project/:project/services/:service/config',
    component: () => import('../views/ServiceConfigView.vue'),
  },
  // Codespace Routes
  {
    path: '/project/:project/manage/codespaces',
    component: () => import('../views/ManageCodespaces.vue'),
  },
  {
    path: '/project/:project/new/codespace',
    component: () => import('../views/ManageCodespaces.vue'),
  },
  // API Routes
  {
    path: '/project/:project/new/api',
    component: () => import('../apis/ManageApis.vue'),
  },
  {
    path: '/project/:project/manage/apis',
    component: () => import('../apis/ManageApis.vue'),
  },
  {
    path: '/project/:project/apis/:apiSlug',
    component: () => import('../apis/ApiDocumentation.vue'),
  },
  {
    path: '/project/:project/apis/:apiSlug/settings',
    component: () => import('../apis/ApiSettings.vue'),
  },
  {
    path: '/project/:project/qr-code-generator',///:qr-code-generator
    component: () => import('../views/QrCodeGenerator.vue'),
  },
  {
    path: '/project/:project/qr-code-generator/config',///:qr-code-generator
    component: () => import('../views/QrCodeGeneratorConfig.vue'),
  },
  {
    path: '/project/:project/qr-code-scanner',///:qr-code-scanner
    component: () => import('../views/QrCodeScanner.vue'),
  },
  {
    path: '/project/:project/qr-code-scanner/config',///:qr-code-scanner
    component: () => import('../views/QrCodeScannerConfig.vue'),
  },
  {
    path: '/project/:project/chat-app',
    component: () => import('../views/ChatApp.vue'),
  },
  {
    path: '/project/:project/chat-app/chat/:id',
    name: 'ChatDetail',
    component: () => import('../views/ChatDetail.vue'),
  },
  {
    path: '/project/:project/chat-app/config',
    component: () => import('../views/ChatAppConfig.vue'),
  },
  {
    path: '/project/:project/my-tasks',
    component: () => import('../views/MyTasks.vue'),
  },
  {
    path: '/project/:project/my-tasks/config',
    component: () => import('../views/ConfigView.vue'),
  },
  {
    path: '/project/:project/nfc',
    component: () => import('../views/NFCView.vue'),
  },
  {
    path: '/project/:project/nfc/config',
    component: () => import('../views/NFCConfigView.vue'),
  },
  {
    path: '/project/:project/web-builder',
    component: () => import('../views/WebBuilderView.vue'),
  },
  {
    path: '/ai-website-generator',
    component: () => import('../views/AIWebsiteGenerator.vue'),
  },
  {
    path: '/no-permission',
    component: () => import('../views/NoPermission.vue'),
  },
];

const modules = import.meta.glob('@/modules/*/routes.ts', { eager: true });

for (const path in modules) {
  const moduleRoutes = (modules[path] as { default: RouteRecordRaw[] }).default;

  // Extract the module name from the folder name instead of the file name
  const moduleName = path.split('/').slice(-2, -1)[0]?.replace(/[^a-zA-Z0-9]/g, '-')?.toLowerCase() || 'default-module';

  // Modify each route to prepend `/project/:project/`
  const transformedRoutes = moduleRoutes.map(route => ({
    ...route,
    path: `/project/:project${route.path.startsWith('/') ? '' : '/'}${route.path}`,
  }));

  // Add the `/config` route for each module
  const configRoute: RouteRecordRaw = {
    path: `/project/:project/${moduleName}/config`,
    name: `${moduleName}-config`,
    component: () => import(`@/views/ConfigView.vue`),
  };

  routes.push(...transformedRoutes, configRoute);
}

const services = import.meta.glob('@/user_services/*/routes.ts', { eager: true });

for (const path in services) {
  const serviceRoutes = (services[path] as { default: RouteRecordRaw[] }).default;

  // Modify each route to prepend `/project/:project/`
  const transformedRoutes = serviceRoutes.map(route => ({
    ...route,
    path: `/project/:project${route.path.startsWith('/') ? '/services' : '/services/'}${route.path}`,
  }));

  routes.push(...transformedRoutes);
}

// Add the specified routes
routes.push(
  {
    path: '/project/:project/:form/config',
    component: () => import('../views/FormConfig.vue'),
  },
  {
    path: '/project/:project/:form',
    component: () => import('../views/FormDisplay.vue'),
  },
  {
    path: '/my-account/preferences',
    component: () => import('../views/settingsModal.vue'),
  },
  {
    path: '/:url(.*)',
    component: () => import('../views/PageViev.vue'),
  }
);

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
