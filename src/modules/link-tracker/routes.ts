import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'link-tracker', component: () => import('./components/LinkTrackerView.vue') },
    { name: 'LinkAnalytics', path: 'link-analytics/:linkId', component: () => import('./components/LinkAnalyticsView.vue') },
];

export default routes;