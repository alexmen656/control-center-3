/**
 * QR Code Generator Dashboard Provider
 */

import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const qrCodeGeneratorDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'qr-code-generator',
  moduleName: 'QR Code Generator',
  moduleIcon: 'qr-code-outline',
  moduleColor: '#8b5cf6',
  
  widgets: [
    {
      id: 'qr-generator-stat',
      type: 'stat',
      title: 'Generated Codes',
      icon: 'qr-code-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async () => {
        return {
          value: 0,
          trend: 0,
          label: 'Total generated'
        };
      }
    }
  ],

  getNavigationItems: () => [
    {
      id: 'qr-code-generator',
      title: 'QR Code Generator',
      icon: 'qr-code-outline',
      path: '/qr-code-generator',
      order: 11
    }
  ]
};

export default qrCodeGeneratorDashboardProvider;
