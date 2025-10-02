/**
 * Dashboard Provider für App Users Modul
 * 
 * Standardisierte Schnittstelle zum Bereitstellen von Daten für das Dashboard
 */

import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

/**
 * App Users Dashboard Provider
 */
export const appUsersDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'app-users',
  moduleName: 'App User Management',
  moduleIcon: 'people-outline',
  
  widgets: [
    // Stat Widgets
    {
      id: 'app-users-total',
      type: 'stat',
      title: 'Gesamte Benutzer',
      icon: 'people-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getUsers: true
          });
          
          const users = response.data || [];
          return {
            value: users.length || 0,
            label: 'Gesamte Benutzer'
          };
        } catch (error) {
          console.error('Error fetching total users:', error);
          return { value: 0, label: 'Gesamte Benutzer' };
        }
      }
    },
    
    {
      id: 'app-users-active',
      type: 'stat',
      title: 'Aktive Benutzer',
      icon: 'checkmark-circle-outline',
      category: 'stats',
      config: {
        color: 'success',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getUsers: true
          });
          
          const users = response.data || [];
          const activeUsers = users.filter((u: any) => u.account_status === 'active');
          
          return {
            value: activeUsers.length || 0,
            label: 'Aktive Benutzer'
          };
        } catch (error) {
          console.error('Error fetching active users:', error);
          return { value: 0, label: 'Aktive Benutzer' };
        }
      }
    },
    
    {
      id: 'app-users-inactive',
      type: 'stat',
      title: 'Inaktive Benutzer',
      icon: 'close-circle-outline',
      category: 'stats',
      config: {
        color: 'warning',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getUsers: true
          });
          
          const users = response.data || [];
          const inactiveUsers = users.filter((u: any) => u.account_status === 'inactive');
          
          return {
            value: inactiveUsers.length || 0,
            label: 'Inaktive Benutzer'
          };
        } catch (error) {
          console.error('Error fetching inactive users:', error);
          return { value: 0, label: 'Inaktive Benutzer' };
        }
      }
    },
    
    {
      id: 'app-users-project-assigned',
      type: 'stat',
      title: 'Zugewiesene Benutzer',
      icon: 'link-outline',
      category: 'stats',
      config: {
        color: 'info',
        format: 'number'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getProjectUsers: true,
            project: params?.project
          });
          
          const users = response.data || [];
          return {
            value: users.length || 0,
            label: 'Zugewiesene Benutzer'
          };
        } catch (error) {
          console.error('Error fetching assigned users:', error);
          return { value: 0, label: 'Zugewiesene Benutzer' };
        }
      }
    },
    
    // Chart Widgets
    {
      id: 'app-users-status-distribution',
      type: 'chart',
      title: 'Benutzer Status',
      icon: 'pie-chart-outline',
      category: 'charts',
      config: {
        chartType: 'pie'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getUsers: true
          });
          
          const users = response.data || [];
          
          // Count by status
          const statusCounts: { [key: string]: number } = {};
          users.forEach((u: any) => {
            const status = u.account_status || 'unknown';
            statusCounts[status] = (statusCounts[status] || 0) + 1;
          });
          
          const labels = Object.keys(statusCounts);
          const data = Object.values(statusCounts);
          
          const colors = {
            'active': '#10b981',
            'inactive': '#f59e0b',
            'suspended': '#ef4444',
            'pending': '#3b82f6'
          };
          
          const backgroundColor = labels.map(l => colors[l as keyof typeof colors] || '#6b7280');
          
          return {
            labels,
            datasets: [{
              label: 'Benutzer',
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
      id: 'app-users-registration-timeline',
      type: 'chart',
      title: 'Registrierungen im Zeitverlauf',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'line',
        color: '#2563eb'
      },
      getData: async (params?: { period?: number }) => {
        try {
          const response = await axios.post('users.php', {
            getUsers: true
          });
          
          const users = response.data || [];
          
          // Group by date (assuming there's a created_at or similar field)
          const dateCounts: { [key: string]: number } = {};
          users.forEach((u: any) => {
            // If no date field exists, we'll use a dummy date
            const date = u.created_at?.split(' ')[0] || u.registration_date?.split(' ')[0] || new Date().toISOString().split('T')[0];
            dateCounts[date] = (dateCounts[date] || 0) + 1;
          });
          
          // Sort by date
          const sortedDates = Object.keys(dateCounts).sort();
          const labels = sortedDates;
          const data = sortedDates.map(d => dateCounts[d]);
          
          return {
            labels,
            datasets: [{
              label: 'Registrierungen',
              data,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,
              fill: true
            }]
          };
        } catch (error) {
          console.error('Error fetching registration timeline:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'app-users-by-project',
      type: 'chart',
      title: 'Benutzer pro Projekt',
      icon: 'folder-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: { limit?: number }) => {
        try {
          const response = await axios.post('users.php', {
            getUserProjectStats: true
          });
          
          const stats = response.data || [];
          
          // Sort by user count and take top N
          const sorted = stats
            .sort((a: any, b: any) => (b.user_count || 0) - (a.user_count || 0))
            .slice(0, params?.limit || 10);
          
          const labels = sorted.map((s: any) => s.project_name || 'Unknown');
          const data = sorted.map((s: any) => s.user_count || 0);
          
          return {
            labels,
            datasets: [{
              label: 'Benutzer',
              data,
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching users by project:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    {
      id: 'app-users-role-distribution',
      type: 'chart',
      title: 'Rollen Verteilung',
      icon: 'shield-outline',
      category: 'charts',
      config: {
        chartType: 'donut'
      },
      getData: async (params?: { project?: string }) => {
        try {
          const response = await axios.post('users.php', {
            getUserRoles: true,
            project: params?.project
          });
          
          const roles = response.data || [];
          
          // Count by role
          const roleCounts: { [key: string]: number } = {};
          roles.forEach((r: any) => {
            const role = r.role_name || 'Unknown';
            roleCounts[role] = (roleCounts[role] || 0) + 1;
          });
          
          const labels = Object.keys(roleCounts);
          const data = Object.values(roleCounts);
          
          const colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6'];
          
          return {
            labels,
            datasets: [{
              label: 'Benutzer',
              data,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching role distribution:', error);
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
export default appUsersDashboardProvider;
