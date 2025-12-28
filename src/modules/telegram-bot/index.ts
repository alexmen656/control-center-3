/**
 * Telegram Bot Module
 * 
 * Module for Telegram bot integration
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import telegramBotDashboardProvider from './dashboard.provider';

// Register Dashboard Provider
dashboardRegistry.register(telegramBotDashboardProvider);

console.log('📦 Telegram Bot Module initialized');

export default {
  name: 'telegram-bot',
  version: '1.0.0',
  dashboardProvider: telegramBotDashboardProvider
};
