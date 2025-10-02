/**
 * Dashboard Provider für Marketing Campaigns Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

/**
 * Marketing Campaigns Dashboard Provider
 */
export const marketingCampaignsDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'marketing-campaigns',
  moduleName: 'Marketing Campaigns',
  moduleIcon: 'megaphone-outline',
  
  widgets: [
    // Stat Widgets
    {
      id: 'marketing-total-campaigns',
      type: 'stat',
      title: 'Gesamte Kampagnen',
      icon: 'megaphone-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          return {
            value: campaigns.length || 0,
            label: 'Gesamte Kampagnen'
          };
        } catch (error) {
          console.error('Error fetching total campaigns:', error);
          return { value: 0, label: 'Gesamte Kampagnen' };
        }
      }
    },
    
    {
      id: 'marketing-active-campaigns',
      type: 'stat',
      title: 'Aktive Kampagnen',
      icon: 'play-circle-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          const activeCampaigns = campaigns.filter((c: any) => c.status === 'active');
          
          return {
            value: activeCampaigns.length || 0,
            label: 'Aktive Kampagnen'
          };
        } catch (error) {
          console.error('Error fetching active campaigns:', error);
          return { value: 0, label: 'Aktive Kampagnen' };
        }
      }
    },
    
    {
      id: 'marketing-total-budget',
      type: 'stat',
      title: 'Gesamt Budget',
      icon: 'cash-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'currency'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          const totalBudget = campaigns.reduce((sum: number, c: any) => sum + (parseFloat(c.budget) || 0), 0);
          
          return {
            value: totalBudget.toFixed(2),
            label: 'Gesamt Budget'
          };
        } catch (error) {
          console.error('Error fetching total budget:', error);
          return { value: '0.00', label: 'Gesamt Budget' };
        }
      }
    },
    
    {
      id: 'marketing-total-spent',
      type: 'stat',
      title: 'Gesamt Ausgaben',
      icon: 'wallet-outline',
      category: 'stats',
      config: {
        color: 'danger',
        format: 'currency'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          const totalSpent = campaigns.reduce((sum: number, c: any) => sum + (parseFloat(c.spent) || 0), 0);
          
          return {
            value: totalSpent.toFixed(2),
            label: 'Gesamt Ausgaben'
          };
        } catch (error) {
          console.error('Error fetching total spent:', error);
          return { value: '0.00', label: 'Gesamt Ausgaben' };
        }
      }
    },
    
    {
      id: 'marketing-total-conversions',
      type: 'stat',
      title: 'Gesamte Konversionen',
      icon: 'checkmark-circle-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          const totalConversions = campaigns.reduce((sum: number, c: any) => sum + (parseInt(c.conversions) || 0), 0);
          
          return {
            value: totalConversions,
            label: 'Gesamte Konversionen'
          };
        } catch (error) {
          console.error('Error fetching total conversions:', error);
          return { value: 0, label: 'Gesamte Konversionen' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'marketing-status-distribution',
      type: 'chart',
      title: 'Kampagnen Status',
      icon: 'pie-chart-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          
          // Count by status
          const statusCounts: { [key: string]: number } = {};
          campaigns.forEach((c: any) => {
            const status = c.status || 'unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
          });
          
          const labels = Object.keys(statusCounts);
          const data = Object.values(statusCounts);
          
          const colors = {
            'draft': '#6b7280',
            'scheduled': '#3b82f6',
            'active': '#10b981',
            'paused': '#f59e0b',
            'completed': '#8b5cf6'
          };
          
          const backgroundColor = labels.map(l => colors[l as keyof typeof colors] || '#6b7280');
          
          return {
            labels,
            datasets: [{
              label: 'Kampagnen',
              data,
              backgroundColor,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching status distribution:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'marketing-channel-distribution',
      type: 'chart',
      title: 'Kanal Verteilung',
      icon: 'apps-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          
          // Count by channel
          const channelCounts: { [key: string]: number } = {};
          campaigns.forEach((c: any) => {
            const channel = c.channel || 'unknown';
            channelCounts[channel] = (channelCounts[channel] || 0) + 1;
          });
          
          const labels = Object.keys(channelCounts);
          const data = Object.values(channelCounts);
          
          const colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6'];
          
          return {
            labels,
            datasets: [{
              label: 'Kampagnen',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching channel distribution:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'marketing-budget-vs-spent',
      type: 'chart',
      title: 'Budget vs. Ausgaben',
      icon: 'stats-chart-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          
          // Sort by budget and take top N
          const sorted = campaigns
            .sort((a: any, b: any) => (parseFloat(b.budget) || 0) - (parseFloat(a.budget) || 0))
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((c: any) => c.name || 'Unknown');
          const budgetData = sorted.map((c: any) => parseFloat(c.budget) || 0);
          const spentData = sorted.map((c: any) => parseFloat(c.spent) || 0);
          
          return {
            labels,
            datasets: [
              {
                label: 'Budget',
                data: budgetData,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1
              },
              {
                label: 'Ausgegeben',
                data: spentData,
                backgroundColor: '#ef4444',
                borderColor: '#dc2626',
                borderWidth: 1
              }
            ]
          };
        } catch (error) {
          console.error('Error fetching budget vs spent:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'marketing-performance-metrics',
      type: 'chart',
      title: 'Performance Metriken',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { project?: string; limit?: number }) => {
        try {
          const formData = new FormData();
          formData.append('action', 'getCampaigns');
          formData.append('project', params?.project || '');
          
          const response = await axios.post('marketing_campaigns.php', formData);
          
          const campaigns = response.data.campaigns || [];
          
          // Filter active campaigns and sort by conversions
          const sorted = campaigns
            .filter((c: any) => c.status === 'active')
            .sort((a: any, b: any) => (parseInt(b.conversions) || 0) - (parseInt(a.conversions) || 0))
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((c: any) => c.name || 'Unknown');
          const impressions = sorted.map((c: any) => parseInt(c.impressions) || 0);
          const clicks = sorted.map((c: any) => parseInt(c.clicks) || 0);
          const conversions = sorted.map((c: any) => parseInt(c.conversions) || 0);
          
          return {
            labels,
            datasets: [
              {
                label: 'Impressions',
                data: impressions,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1
              },
              {
                label: 'Clicks',
                data: clicks,
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 1
              },
              {
                label: 'Conversions',
                data: conversions,
                backgroundColor: '#8b5cf6',
                borderColor: '#7c3aed',
                borderWidth: 1
              }
            ]
          };
        } catch (error) {
          console.error('Error fetching performance metrics:', error);
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
export default marketingCampaignsDashboardProvider;
