import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'push-messages', component: () => import('./components/Modul1View.vue') },
];

export default routes;
