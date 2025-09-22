import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
        path: 'marketing-campaigns', 
        component: () => import('./components/MarketingCampaigns.vue'),
        meta: {
            title: 'Marketing Campaigns',
            icon: 'megaphone-outline',
            description: 'Plan and manage marketing campaigns'
        }
    },
];

export default routes;
