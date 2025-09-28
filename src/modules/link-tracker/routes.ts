import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { path: 'link-tracker', component: () => import('./components/LinkTrackerView.vue') },
];

export default routes;