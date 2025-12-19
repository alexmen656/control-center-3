/**
 * Dashboard Provider für Newsletter Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import qs from 'qs';
import type { ModuleDashboardProvider } from '@/types/dashboard.types';

/**
 * Newsletter Dashboard Provider
 */
export const newsletterDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'newsletter',
  moduleName: 'Newsletter',
  moduleIcon: 'mail-outline',
  moduleColor: '#2563eb',
  
  widgets: [
    // Stat Widgets
    {
      id: 'newsletter-total-sent',
      type: 'stat',
      title: 'Gesendete Newsletter',
      icon: 'mail-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_stats',
              project: params?.project || ''
            })
          );
          
          return {
            value: response.data.total_sent || 0,
            label: 'Gesendete Newsletter'
          };
        } catch (error) {
          console.error('Error fetching newsletter stats:', error);
          return { value: 0, label: 'Gesendete Newsletter' };
        }
      }
    },
    
    {
      id: 'newsletter-total-subscribers',
      type: 'stat',
      title: 'Abonnenten',
      icon: 'people-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_stats',
              project: params?.project || ''
            })
          );
          
          return {
            value: response.data.total_subscribers || 0,
            label: 'Abonnenten'
          };
        } catch (error) {
          console.error('Error fetching subscriber count:', error);
          return { value: 0, label: 'Abonnenten' };
        }
      }
    },
    
    {
      id: 'newsletter-open-rate',
      type: 'stat',
      title: 'Öffnungsrate',
      icon: 'eye-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'percentage'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_stats',
              project: params?.project || ''
            })
          );
          
          return {
            value: response.data.open_rate || 0,
            label: 'Öffnungsrate'
          };
        } catch (error) {
          console.error('Error fetching open rate:', error);
          return { value: 0, label: 'Öffnungsrate' };
        }
      }
    },
    
    {
      id: 'newsletter-click-rate',
      type: 'stat',
      title: 'Klickrate',
      icon: 'hand-left-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'percentage'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_stats',
              project: params?.project || ''
            })
          );
          
          return {
            value: response.data.click_rate || 0,
            label: 'Klickrate'
          };
        } catch (error) {
          console.error('Error fetching click rate:', error);
          return { value: 0, label: 'Klickrate' };
        }
      }
    },
    
    // Table Widget
    {
      id: 'newsletter-recent-campaigns',
      type: 'table',
      title: 'Letzte Newsletter',
      icon: 'list-outline',
      category: 'data',
      config: {
        pageSize: 10
      },
      getData: async (params?: { project?: string; limit?: number; offset?: number }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_recent',
              project: params?.project || '',
              limit: params?.limit || 10,
              offset: params?.offset || 0
            })
          );
          
          const newsletters = response.data.newsletters || [];
          
          return {
            headers: ['Betreff', 'Empfänger', 'Status', 'Gesendet am'],
            rows: newsletters.map((n: any) => [
              n.subject || '-',
              n.recipients || 0,
              n.status || 'unknown',
              n.sent_at || '-'
            ]),
            total: response.data.total || newsletters.length
          };
        } catch (error) {
          console.error('Error fetching recent newsletters:', error);
          return {
            headers: ['Betreff', 'Empfänger', 'Status', 'Gesendet am'],
            rows: [],
            total: 0
          };
        }
      }
    },
    
    // Chart Widget
    {
      id: 'newsletter-performance-chart',
      type: 'chart',
      title: 'Newsletter Performance',
      icon: 'bar-chart-outline',
      category: 'analytics',
      config: {
        chartType: 'line'
      },
      getData: async (params?: { project?: string; period?: string }) => {
        try {
          const response = await axios.post(
            'newsletter.php',
            qs.stringify({
              action: 'get_performance',
              project: params?.project || '',
              period: params?.period || '30d'
            })
          );
          
          const data = response.data.performance || [];
          
          return {
            labels: data.map((d: any) => d.date),
            datasets: [
              {
                label: 'Gesendete',
                data: data.map((d: any) => d.sent),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)'
              },
              {
                label: 'Geöffnet',
                data: data.map((d: any) => d.opened),
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)'
              },
              {
                label: 'Geklickt',
                data: data.map((d: any) => d.clicked),
                borderColor: '#d97706',
                backgroundColor: 'rgba(217, 119, 6, 0.1)'
              }
            ]
          };
        } catch (error) {
          console.error('Error fetching newsletter performance:', error);
          return {
            labels: [],
            datasets: []
          };
        }
      }
    }
  ],
  
  // Helper method to get widget by ID
  getWidget(widgetId: string) {
    return this.widgets.find(w => w.id === widgetId);
  }
};

export default newsletterDashboardProvider;
