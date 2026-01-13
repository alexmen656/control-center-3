import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/ideas',
    name: 'idea-list',
    component: () => import('./components/IdeaList.vue'),
    meta: {
      title: 'Ideen Entwicklung',
      requiresAuth: true
    }
  },
  {
    path: '/ideas/:id',
    name: 'idea-detail',
    component: () => import('./components/IdeaDetail.vue'),
    meta: {
        title: 'Idee Details',
        requiresAuth: true
    }
  }
];

export default routes;
