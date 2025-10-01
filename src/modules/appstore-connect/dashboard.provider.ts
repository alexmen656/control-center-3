/**
 * Dashboard Provider für AppStore Connect Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';

export interface DashboardWidgetData {
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
  }>;
}

export interface DashboardWidget {
  id: string;
  type: 'stat' | 'chart' | 'table' | 'card';
  title: string;
  icon?: string;
  category?: string;
  getData: (params?: any) => Promise<any>;
  config?: {
    chartType?: 'pie' | 'donut' | 'bar' | 'line' | 'date_bar';
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
 * AppStore Connect Dashboard Provider
 */
export const appstoreDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'appstore-connect',
  moduleName: 'App Store Analytics',
  moduleIcon: 'logo-apple-appstore',
  
  widgets: [
    // Stat Widgets
    {
      id: 'appstore-total-downloads',
      type: 'stat',
      title: 'Gesamte Downloads',
      icon: 'download-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { period?: number; app?: string }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          
          return {
            value: response.data.stats?.total_downloads || 0,
            trend: response.data.stats?.trend || 0,
            label: 'Gesamte Downloads'
          };
        } catch (error) {
          console.error('Error fetching total downloads:', error);
          return { value: 0, trend: 0, label: 'Gesamte Downloads' };
        }
      }
    },
    
    {
      id: 'appstore-unique-devices',
      type: 'stat',
      title: 'Einzigartige Geräte',
      icon: 'phone-portrait-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { period?: number; app?: string }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          
          return {
            value: response.data.stats?.unique_devices || 0,
            label: 'Einzigartige Geräte'
          };
        } catch (error) {
          console.error('Error fetching unique devices:', error);
          return { value: 0, label: 'Einzigartige Geräte' };
        }
      }
    },
    
    {
      id: 'appstore-countries',
      type: 'stat',
      title: 'Länder',
      icon: 'earth-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { period?: number; app?: string }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          
          return {
            value: response.data.stats?.countries || 0,
            label: 'Länder'
          };
        } catch (error) {
          console.error('Error fetching countries:', error);
          return { value: 0, label: 'Länder' };
        }
      }
    },
    
    {
      id: 'appstore-platforms',
      type: 'stat',
      title: 'Plattformen',
      icon: 'apps-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async (params?: { period?: number; app?: string }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          
          return {
            value: response.data.stats?.platforms || 0,
            label: 'Plattformen'
          };
        } catch (error) {
          console.error('Error fetching platforms:', error);
          return { value: 0, label: 'Plattformen' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'appstore-downloads-timeline',
      type: 'chart',
      title: 'Downloads im Zeitverlauf',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'line',
        color: '#2563eb'
      },
      getData: async (params?: { period?: number; app?: string; dateStamp?: 'hours' | 'days' | 'weeks' | 'months' }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          const downloads = response.data.downloads || [];
          
          // Gruppiere nach Datum
          const groupedData: { [key: string]: number } = {};
          const dateStamp = params?.dateStamp || 'days';
          
          downloads.forEach((item: any) => {
            let dateKey: string;
            
            if (dateStamp === 'hours') {
              const dateTime = item.date.split(' ');
              const date = dateTime[0];
              const hour = dateTime[1]?.split(':')[0] || '00';
              dateKey = `${date} ${hour}:00:00`;
            } else {
              dateKey = item.date.split(' ')[0];
            }
            
            if (!groupedData[dateKey]) {
              groupedData[dateKey] = 0;
            }
            groupedData[dateKey] += item.count;
          });
          
          // Erstelle Labels und Daten
          const labels: string[] = [];
          const data: number[] = [];
          
          if (dateStamp === 'hours') {
            const now = new Date();
            for (let i = 23; i >= 0; i--) {
              const date = new Date(now);
              date.setHours(now.getHours() - i);
              const dateString = date.toISOString().split(':')[0] + ':00:00';
              const hours = date.getHours().toString().padStart(2, '0');
              labels.push(`${hours}:00`);
              data.push(groupedData[dateString] || 0);
            }
          } else {
            const today = new Date();
            const days = params?.period || 7;
            for (let i = days - 1; i >= 0; i--) {
              const date = new Date(today);
              date.setDate(today.getDate() - i);
              const dateString = date.toISOString().split('T')[0];
              labels.push(dateString);
              data.push(groupedData[dateString] || 0);
            }
          }
          
          return {
            labels,
            datasets: [{
              label: 'Downloads',
              data,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,
              fill: true
            }]
          };
        } catch (error) {
          console.error('Error fetching timeline data:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'appstore-countries-chart',
      type: 'chart',
      title: 'Top Länder',
      icon: 'earth-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { period?: number; app?: string; limit?: number }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          const downloads = response.data.downloads || [];
          
          // Gruppiere nach Land
          const countryData: { [key: string]: number } = {};
          downloads.forEach((item: any) => {
            const country = item.country || 'Unknown';
            if (!countryData[country]) {
              countryData[country] = 0;
            }
            countryData[country] += item.count;
          });
          
          // Sortiere und limitiere
          const sorted = Object.entries(countryData)
            .sort(([, a], [, b]) => b - a)
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map(([country]) => country);
          const data = sorted.map(([, count]) => count);
          
          // Farben für Pie Chart
          const colors = [
            '#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6',
            '#0891b2', '#f59e0b', '#10b981', '#ef4444', '#6366f1'
          ];
          
          return {
            labels,
            datasets: [{
              label: 'Downloads',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching countries chart data:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'appstore-platforms-chart',
      type: 'chart',
      title: 'Plattformen Verteilung',
      icon: 'apps-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { period?: number; app?: string }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          const downloads = response.data.downloads || [];
          
          // Gruppiere nach Plattform
          const platformData: { [key: string]: number } = {};
          downloads.forEach((item: any) => {
            const platform = item.platform || 'Unknown';
            if (!platformData[platform]) {
              platformData[platform] = 0;
            }
            platformData[platform] += item.count;
          });
          
          const labels = Object.keys(platformData);
          const data = Object.values(platformData);
          
          const colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6'];
          
          return {
            labels,
            datasets: [{
              label: 'Downloads',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching platforms chart data:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'appstore-versions-chart',
      type: 'chart',
      title: 'App Versionen',
      icon: 'code-working-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { period?: number; app?: string; limit?: number }) => {
        try {
          const queryParams = new URLSearchParams({
            period: String(params?.period || 30)
          });
          
          if (params?.app) {
            queryParams.append('app', params.app);
          }
          
          const response = await axios.get(`appstore_downloads.php?${queryParams.toString()}`);
          const downloads = response.data.downloads || [];
          
          // Gruppiere nach Version
          const versionData: { [key: string]: number } = {};
          downloads.forEach((item: any) => {
            const version = item.version || 'Unknown';
            if (!versionData[version]) {
              versionData[version] = 0;
            }
            versionData[version] += item.count;
          });
          
          // Sortiere nach Anzahl
          const sorted = Object.entries(versionData)
            .sort(([, a], [, b]) => b - a)
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map(([version]) => version);
          const data = sorted.map(([, count]) => count);
          
          return {
            labels,
            datasets: [{
              label: 'Downloads',
              data,
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching versions chart data:', error);
          return { labels: [], datasets: [] };
        }
      }
    }
  ],
  
  getWidget(widgetId: string): DashboardWidget | undefined {
    return this.widgets.find(w => w.id === widgetId);
  }
};

// Export für einfachen Zugriff
export default appstoreDashboardProvider;
