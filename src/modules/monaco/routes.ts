import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'monaco', component: () => import('./components/Modul1View.vue') },
    { path: 'monaco-editor', component: () => import('./components/Modul1View.vue') },
    { path: 'monaco/:codespace', component: () => import('./components/Modul1View.vue') },
];

export default routes;
