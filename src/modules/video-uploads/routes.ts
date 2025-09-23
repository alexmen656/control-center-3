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
];

export default routes;
