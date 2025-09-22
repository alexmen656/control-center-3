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
];

export default routes;
