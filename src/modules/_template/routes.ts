/**
 * Template Module Routes
 */

import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/template-module',
    name: 'template-module',
    component: () => import('./components/TemplateView.vue'),
    meta: {
      title: 'Template Module',
      requiresAuth: true
    }
  }
];

export default routes;
