import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
        path: 'mail', 
        component: () => import('./components/Modul1View.vue') 
    },
    {
        path: 'mail/email',
        name: 'email-view',
        component: () => import('./components/EmailView.vue')
    }
];

export default routes;
