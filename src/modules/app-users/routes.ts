import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'app-users', redirect: 'app-users/dashboard' },
    { path: 'app-users/dashboard', component: () => import('./components/DashboardView.vue') },
    { path: 'app-users/register', component: () => import('./components/RegisterAppView.vue') },
    { path: 'app-users/statistics', component: () => import('./components/StatisticsView.vue') },
];

export default routes;
