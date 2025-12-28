/**
 * QR Code Scanner Dashboard Provider
 */

import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const qrCodeScannerDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'qr-code-scanner',
  moduleName: 'QR Code Scanner',
  moduleIcon: 'qr-code-outline',
  moduleColor: '#10b981',
  
  widgets: [
    {
      id: 'qr-scanner-stat',
      type: 'stat',
      title: 'Scanned Codes',
      icon: 'qr-code-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async () => {
        return {
          value: 0,
          trend: 0,
          label: 'Total scanned'
        };
      }
    }
  ],

  getNavigationItems: () => [
    {
      id: 'qr-code-scanner',
      title: 'QR Code Scanner',
      icon: 'qr-code-outline',
      path: '/qr-code-scanner',
      order: 10
    }
  ]
};

export default qrCodeScannerDashboardProvider;
