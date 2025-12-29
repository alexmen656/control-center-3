/**
 * Dashboard Provider für App Store Metadata Manager Modul
 */

import axios from 'axios';

export interface DashboardWidget {
  id: string;
  type: 'stat' | 'chart' | 'table' | 'card';
  title: string;
  icon?: string;
  category?: string;
  getData: (params?: any) => Promise<any>;
  config?: {
    chartType?: 'pie' | 'donut' | 'bar' | 'line';
    color?: string;
    format?: 'number' | 'currency' | 'percentage';
    [key: string]: any;
  };
}

export interface ModuleDashboardProvider {
  moduleId: string;
  moduleName: string;
  moduleIcon?: string;
  widgets: DashboardWidget[];
  getWidget: (widgetId: string) => DashboardWidget | undefined;
}

/**
 * App Store Metadata Manager Dashboard Provider
 */
export const metadataManagerDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'appstore-metadata',
  moduleName: 'App Store Metadata',
  moduleIcon: 'apps-outline',
  
  widgets: [
    {
      id: 'metadata-total-apps',
      type: 'stat',
      title: 'Verwaltete Apps',
      icon: 'apps-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async () => {
        try {
          const response = await axios.get('appstore_metadata.php?action=dashboard');
          return {
            value: response.data.stats?.total_apps || 0,
            label: 'Verwaltete Apps'
          };
        } catch (error) {
          console.error('Error fetching app count:', error);
          return { value: 0, label: 'Verwaltete Apps' };
        }
      }
    },
    {
      id: 'metadata-total-versions',
      type: 'stat',
      title: 'App Versionen',
      icon: 'git-branch-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async () => {
        try {
          const response = await axios.get('appstore_metadata.php?action=dashboard');
          return {
            value: response.data.stats?.total_versions || 0,
            label: 'App Versionen'
          };
        } catch (error) {
          console.error('Error fetching version count:', error);
          return { value: 0, label: 'App Versionen' };
        }
      }
    },
    {
      id: 'metadata-total-locales',
      type: 'stat',
      title: 'Sprachen',
      icon: 'language-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async () => {
        try {
          const response = await axios.get('appstore_metadata.php?action=dashboard');
          return {
            value: response.data.stats?.total_locales || 0,
            label: 'Sprachen'
          };
        } catch (error) {
          console.error('Error fetching locale count:', error);
          return { value: 0, label: 'Sprachen' };
        }
      }
    },
    {
      id: 'metadata-api-status',
      type: 'stat',
      title: 'API Status',
      icon: 'cloud-outline',
      category: 'stats',
      config: {
        color: 'info'
      },
      getData: async () => {
        try {
          const response = await axios.get('appstore_metadata.php?action=dashboard');
          return {
            value: response.data.stats?.has_credentials ? 'Verbunden' : 'Nicht verbunden',
            label: 'API Status',
            connected: response.data.stats?.has_credentials || false
          };
        } catch (error) {
          console.error('Error fetching API status:', error);
          return { value: 'Fehler', label: 'API Status', connected: false };
        }
      }
    }
  ],
  
  getWidget(widgetId: string): DashboardWidget | undefined {
    return this.widgets.find(w => w.id === widgetId);
  }
};

export default metadataManagerDashboardProvider;
