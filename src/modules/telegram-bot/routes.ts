/**
 * Telegram Bot Module Routes
 */

import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: 'telegram-bot',
    name: 'telegram-bot',
    component: () => import('./components/TelegramBotView.vue'),
    meta: {
      title: 'Telegram Bot',
      icon: 'paper-plane-outline',
      description: 'Send messages via Telegram bot',
      requiresAuth: true
    }
  },
  {
    path: 'telegram-bot/config',
    name: 'telegram-bot-config',
    component: () => import('./components/ConfigView.vue'),
    meta: {
      title: 'Telegram Bot Configuration',
      icon: 'settings-outline',
      description: 'Configure Telegram bot settings',
      requiresAuth: true
    }
  }
];

export default routes;
