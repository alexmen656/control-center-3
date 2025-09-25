import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
    { 
        path: 'video-uploads', 
        component: () => import('./components/VideoUploads.vue'),
        meta: {
            title: 'Video Uploads',
            icon: 'videocam-outline',
            description: 'Manage and schedule video uploads to social platforms'
        }
    },
    {
        path: 'video-uploads/config',
        component: () => import('./components/VideoAPIConfig.vue'),
        meta: {
            title: 'Video API Konfiguration',
            icon: 'key-outline',
            description: 'API-Verbindungen zu Video-Plattformen konfigurieren'
        }
    },
    {
        path: 'video-uploads/:videoId/details',
        component: () => import('./components/VideoDetails.vue'),
        meta: {
            title: 'Video Details',
            icon: 'information-circle-outline',
            description: 'Detaillierte Video-Informationen und Verwaltung'
        }
    },
    {
        path: 'video-uploads/:videoId/analytics',
        component: () => import('./components/VideoAnalytics.vue'),
        meta: {
            title: 'Video Analytics',
            icon: 'analytics-outline',
            description: 'Detaillierte Analytics und Performance-Metriken'
        }
    },
];

export default routes;
