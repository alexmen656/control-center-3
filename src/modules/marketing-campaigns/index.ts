import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import dashboardProvider from './dashboard.provider';

// Dashboard Provider registrieren
dashboardRegistry.register(dashboardProvider);

console.log('📦 Marketing Campaigns Module initialized with Dashboard Provider');

export { default as routes } from './routes';

export default {
  name: 'marketing-campaigns',
  version: '1.0.0',
  dashboardProvider
};
