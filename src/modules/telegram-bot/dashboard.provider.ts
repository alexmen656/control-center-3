/**
 * Telegram Bot Dashboard Provider
 */

import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const telegramBotDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'telegram-bot',
  moduleName: 'Telegram Bot',
  moduleIcon: 'paper-plane-outline',
  moduleColor: '#0088cc',
  
  widgets: [
    {
      id: 'telegram-bot-stat',
      type: 'stat',
      title: 'Messages Sent',
      icon: 'paper-plane-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async () => {
        return {
          value: 0,
          trend: 0,
          label: 'Total messages'
        };
      }
    }
  ],

  getNavigationItems: () => [
    {
      id: 'telegram-bot',
      title: 'Telegram Bot',
      icon: 'paper-plane-outline',
      path: '/telegram-bot',
      order: 12
    }
  ]
};

export default telegramBotDashboardProvider;
