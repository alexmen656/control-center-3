/**
 * Dashboard Provider für Link Tracker Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

/**
 * Link Tracker Dashboard Provider
 */
export const linkTrackerDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'link-tracker',
  moduleName: 'Link Tracker Analytics',
  moduleIcon: 'link-outline',
  
  widgets: [
    // Stat Widgets
    {
      id: 'link-tracker-total-links',
      type: 'stat',
      title: 'Gesamte Links',
      icon: 'link-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getLinks', 'true');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          const links = data.links || [];
          return {
            value: links.length || 0,
            label: 'Gesamte Links'
          };
        } catch (error) {
          console.error('Error fetching total links:', error);
          return { value: 0, label: 'Gesamte Links' };
        }
      }
    },
    
    {
      id: 'link-tracker-total-clicks',
      type: 'stat',
      title: 'Gesamte Klicks',
      icon: 'hand-left-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', String(params?.period || 30));
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { value: 0, label: 'Gesamte Klicks' };
          }
          
          const totalClicks = data.bot_stats?.total_visits || 0;
          return {
            value: totalClicks,
            label: 'Gesamte Klicks'
          };
        } catch (error) {
          console.error('Error fetching total clicks:', error);
          return { value: 0, label: 'Gesamte Klicks' };
        }
      }
    },
    
    {
      id: 'link-tracker-unique-visitors',
      type: 'stat',
      title: 'Einzigartige Besucher',
      icon: 'people-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getLinks', 'true');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          const links = data.links || [];
          
          // Calculate unique visitors from all links
          const uniqueVisitors = links.reduce((sum: number, link: any) => 
            sum + (parseInt(link.unique_visitors) || 0), 0
          );
          
          return {
            value: uniqueVisitors,
            label: 'Einzigartige Besucher'
          };
        } catch (error) {
          console.error('Error fetching unique visitors:', error);
          return { value: 0, label: 'Einzigartige Besucher' };
        }
      }
    },
    
    {
      id: 'link-tracker-countries',
      type: 'stat',
      title: 'Länder',
      icon: 'earth-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async (params?: { project?: string; period?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', String(params?.period || 30));
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { value: 0, label: 'Länder' };
          }
          
          const countries = data.countries || [];
          return {
            value: countries.length || 0,
            label: 'Länder'
          };
        } catch (error) {
          console.error('Error fetching countries:', error);
          return { value: 0, label: 'Länder' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'link-tracker-clicks-timeline',
      type: 'chart',
      title: 'Klicks im Zeitverlauf',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'line',
        color: '#2563eb'
      },
      getData: async (params?: { project?: string; period?: number; linkId?: string }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', String(params?.period || 7));
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { labels: [], datasets: [] };
          }
          
          const timeline = data.timeline || [];
          const labels = timeline.map((item: any) => item.date);
          const clicksData = timeline.map((item: any) => item.clicks);
          
          return {
            labels,
            datasets: [{
              label: 'Klicks',
              data: clicksData,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,
              fill: true
            }]
          };
        } catch (error) {
          console.error('Error fetching clicks timeline:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'link-tracker-countries-chart',
      type: 'chart',
      title: 'Top Länder',
      icon: 'earth-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', '30');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { labels: [], datasets: [] };
          }
          
          const countries = (data.countries || []).slice(0, params?.limit || 10);
          const labels = countries.map((c: any) => c.country || 'Unknown');
          const countsData = countries.map((c: any) => c.count || 0);
          
          const colors = [
            '#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6',
            '#0891b2', '#f59e0b', '#10b981', '#ef4444', '#6366f1'
          ];
          
          return {
            labels,
            datasets: [{
              label: 'Klicks',
              data: countsData,
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
      id: 'link-tracker-devices-chart',
      type: 'chart',
      title: 'Geräte Verteilung',
      icon: 'phone-portrait-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', '30');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { labels: [], datasets: [] };
          }
          
          const devices = data.devices || [];
          const labels = devices.map((d: any) => d.device_type || 'Unknown');
          const countsData = devices.map((d: any) => d.count || 0);
          
          const colors = ['#2563eb', '#059669', '#d97706', '#dc2626'];
          
          return {
            labels,
            datasets: [{
              label: 'Klicks',
              data: countsData,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching devices chart data:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'link-tracker-browsers-chart',
      type: 'chart',
      title: 'Browser Verteilung',
      icon: 'globe-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getDetailedAnalytics', 'true');
          formData.append('project', params?.project || '');
          formData.append('period', '30');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { labels: [], datasets: [] };
          }
          
          const browsers = (data.browsers || []).slice(0, params?.limit || 10);
          const labels = browsers.map((b: any) => b.browser || 'Unknown');
          const countsData = browsers.map((b: any) => b.count || 0);
          
          return {
            labels,
            datasets: [{
              label: 'Klicks',
              data: countsData,
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching browsers chart data:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'link-tracker-top-links',
      type: 'chart',
      title: 'Top Links',
      icon: 'trophy-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('getLinks', 'true');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('link_tracker_api.php', formData);
          
          const data = response.data;
          if (!data.success) {
            return { labels: [], datasets: [] };
          }
          
          const links = data.links || [];
          
          // Sort by visits and take top N
          const sorted = links
            .sort((a: any, b: any) => (b.visits || 0) - (a.visits || 0))
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((l: any) => l.title || l.slug || 'Unknown');
          const countsData = sorted.map((l: any) => l.visits || 0);
          
          return {
            labels,
            datasets: [{
              label: 'Klicks',
              data: countsData,
              backgroundColor: '#059669',
              borderColor: '#047857',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching top links chart data:', error);
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
export default linkTrackerDashboardProvider;
