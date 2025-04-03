import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'modul1', component: () => import('./components/Modul1View.vue') },
];

export default routes;
