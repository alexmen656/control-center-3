/**
 * QR Code Generator Module Routes
 */

import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: 'qr-code-generator',
    name: 'qr-code-generator',
    component: () => import('./components/QrCodeGeneratorView.vue'),
    meta: {
      title: 'QR Code Generator',
      icon: 'qr-code-outline',
      description: 'Generate QR codes and barcodes',
      requiresAuth: true
    }
  },
  {
    path: 'qr-code-generator/config',
    name: 'qr-code-generator-config',
    component: () => import('./components/ConfigView.vue'),
    meta: {
      title: 'QR Code Generator Configuration',
      icon: 'settings-outline',
      description: 'Configure QR code generator settings',
      requiresAuth: true
    }
  }
];

export default routes;
