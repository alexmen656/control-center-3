import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  { path: 'modul2', component: () => import('./components/Modul2View.vue') },
];

export default routes;
