import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'github', component: () => import('./components/Modul1View.vue') },
];

export default routes;
