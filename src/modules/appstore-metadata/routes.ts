import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
      path: 'appstore-metadata', 
      component: () => import('./components/DashboardView.vue') 
    },
    { 
      path: 'appstore-metadata/config', 
      component: () => import('./components/ConfigView.vue') 
    },
    { 
      path: 'appstore-metadata/app/:appId', 
      component: () => import('./components/AppDetailView.vue'),
      props: true
    },
    { 
      path: 'appstore-metadata/app/:appId/localization', 
      component: () => import('./components/LocalizationEditor.vue'),
      props: true
    },
    { 
      path: 'appstore-metadata/app/:appId/version/:versionId', 
      component: () => import('./components/VersionEditor.vue'),
      props: true
    },
    { 
      path: 'appstore-metadata/app/:appId/screenshots/:versionId', 
      component: () => import('./components/ScreenshotManager.vue'),
      props: true
    }
];

export default routes;
