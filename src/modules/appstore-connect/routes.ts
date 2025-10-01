import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
      path: 'appstore-connect', 
      component: () => import('./components/Modul1View.vue') 
    },
    { 
      path: 'appstore-connect/config', 
      component: () => import('./components/ConfigView.vue') 
    }
];

export default routes;
