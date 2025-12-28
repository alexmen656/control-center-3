/**
 * QR Code Scanner Module Routes
 */

import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: 'qr-code-scanner',
    name: 'qr-code-scanner',
    component: () => import('./components/QrCodeScannerView.vue'),
    meta: {
      title: 'QR Code Scanner',
      icon: 'qr-code-outline',
      description: 'Scan QR codes and barcodes',
      requiresAuth: true
    }
  },
  {
    path: 'qr-code-scanner/config',
    name: 'qr-code-scanner-config',
    component: () => import('./components/ConfigView.vue'),
    meta: {
      title: 'QR Code Scanner Configuration',
      icon: 'settings-outline',
      description: 'Configure QR code scanner settings',
      requiresAuth: true
    }
  }
];

export default routes;
