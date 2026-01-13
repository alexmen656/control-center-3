import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import { ideaDevelopmentDashboardProvider } from './dashboard.provider';

// Registriere Dashboard Provider
dashboardRegistry.register(ideaDevelopmentDashboardProvider);

console.log('📦 Idea Development Module initialized');

export { default as routes } from './routes';

export default {
  name: 'idea-development',
  version: '1.0.0',
  dashboardProvider: ideaDevelopmentDashboardProvider
};
