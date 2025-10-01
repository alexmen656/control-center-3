/**
 * AppStore Connect Module
 * 
 * Entry point for the AppStore Connect module
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import appstoreDashboardProvider from './dashboard.provider';

// Register the dashboard provider
dashboardRegistry.register(appstoreDashboardProvider);

console.log('📦 AppStore Connect Module initialized');

export default {
  name: 'appstore-connect',
  version: '1.0.0',
  dashboardProvider: appstoreDashboardProvider
};
