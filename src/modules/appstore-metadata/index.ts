/**
 * App Store Metadata Manager Module
 * 
 * Entry point for the App Store Metadata Manager module
 */

import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import metadataManagerDashboardProvider from './dashboard.provider';

// Register the dashboard provider
dashboardRegistry.register(metadataManagerDashboardProvider);

console.log('📱 App Store Metadata Manager Module initialized');

export default {
  name: 'appstore-metadata',
  version: '1.0.0',
  dashboardProvider: metadataManagerDashboardProvider
};
