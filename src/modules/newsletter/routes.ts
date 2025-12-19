import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
        path: 'newsletter', 
        component: () => import('./components/NewsletterView.vue'),
        meta: {
            title: 'Newsletter',
            icon: 'mail-outline',
            description: 'Send newsletters to your subscribers'
        }
    },
    { 
        path: 'newsletter/config', 
        component: () => import('./components/ConfigView.vue'),
        meta: {
            title: 'Newsletter Configuration',
            icon: 'settings-outline',
            description: 'Configure newsletter settings'
        }
    }
];

export default routes;