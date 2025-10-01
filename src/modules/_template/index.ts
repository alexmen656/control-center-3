/**
 * Template Module
 * 
 * Kopiere dieses Modul und passe es an.
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import templateDashboardProvider from './dashboard.provider';

// Registriere Dashboard Provider
dashboardRegistry.register(templateDashboardProvider);

console.log('📦 Template Module initialized');

export default {
  name: 'template-module',
  version: '1.0.0',
  dashboardProvider: templateDashboardProvider
};
