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
  /*{
    path: '/project/:project/filemanager',
    component: () => import('../views/ProjectView.vue'),
  },
  {
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
  /*
  {
    path: '/project/:project/:url(.*)',
    component: () => import('../views/ProjectView.vue'),
  },*/
  {
    path: '/project/:project/components',
    component: () => import('../views/ComponentsView.vue'),
  },
  {
    path: '/project/:project/new/component',
    component: () => import('../views/NewComponent.vue'),
  },
  {
    path: '/project/:project/components/:component(.*)',
    component: () => import('../views/ComponentView.vue'),
  },
  {
    path: '/project/:project',
    component: () => import('../views/ProjectView.vue'),
  },
  {
    path: '/project/:project/info',
    component: () => import('../views/ProjectInfo.vue'),
  },
  {
    path: '/drop',
    component: () => import('../views/DropZone.vue'),
  },
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
    path: '/project/:project/nfc',
    component: () => import('../views/NFCView.vue'),
  },
  {
    path: '/project/:project/nfc/config',
    component: () => import('../views/NFCConfigView.vue'),
  },
  {
    path: '/project/:project/:form',
    component: () => import('../views/FormDisplay.vue'),
  },
  {
    path: '/:url(.*)',
    component: () => import('../views/PageViev.vue'),
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

export default router
