import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

async function fetchUsers(): Promise<any[]> {
  const response = await axios.get('v2/users');
  const payload = response.data || {};
  const labels: string[] = payload.labels || [];
  const rows: any[][] = payload.data || [];
  return rows.map((row) => {
    const obj: Record<string, any> = {};
    labels.forEach((label, index) => {
      obj[label] = row[index];
    });
    return obj;
  });
}

export const appUsersDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'app-users',
  moduleName: 'App User Management',
  moduleIcon: 'people-outline',
  
  widgets: [
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
          const users = await fetchUsers();
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
          const users = await fetchUsers();
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
          const users = await fetchUsers();
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
          const response = await axios.get('v2/users/assignments');
          const assignments = response.data?.assignments || [];
          const filtered = params?.project
            ? assignments.filter((a: any) => a.project_link === params.project)
            : assignments;
          return {
            value: filtered.length || 0,
            label: 'Zugewiesene Benutzer'
          };
        } catch (error) {
          console.error('Error fetching assigned users:', error);
          return { value: 0, label: 'Zugewiesene Benutzer' };
        }
      }
    },
    
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
          const users = await fetchUsers();

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
            'pending': '#f97316'
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
        color: '#f97316'
      },
      getData: async (params?: { period?: number }) => {
        try {
          const users = await fetchUsers();

          const dateCounts: { [key: string]: number } = {};
          users.forEach((u: any) => {
            const date = u.created_at?.split(' ')[0] || u.registration_date?.split(' ')[0] || new Date().toISOString().split('T')[0];
            dateCounts[date] = (dateCounts[date] || 0) + 1;
          });
          
          const sortedDates = Object.keys(dateCounts).sort();
          const labels = sortedDates;
          const data = sortedDates.map(d => dateCounts[d]);
          
          return {
            labels,
            datasets: [{
              label: 'Registrierungen',
              data,
              backgroundColor: 'rgba(249, 115, 22, 0.1)',
              borderColor: '#f97316',
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
          const response = await axios.get('v2/users/assignments');
          const assignments = response.data?.assignments || [];

          const counts: { [key: string]: number } = {};
          assignments.forEach((a: any) => {
            const name = a.project_name || a.project_link || 'Unknown';
            counts[name] = (counts[name] || 0) + 1;
          });

          const stats = Object.keys(counts).map((name) => ({
            project_name: name,
            user_count: counts[name]
          }));

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
              backgroundColor: '#f97316',
              borderColor: '#ea580c',
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
          const roles: any[] = [];

          const roleCounts: { [key: string]: number } = {};
          roles.forEach((r: any) => {
            const role = r.role_name || 'Unknown';
            roleCounts[role] = (roleCounts[role] || 0) + 1;
          });
          
          const labels = Object.keys(roleCounts);
          const data = Object.values(roleCounts);
          
          const colors = ['#f97316', '#059669', '#d97706', '#dc2626', '#8b5cf6'];
          
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

export default appUsersDashboardProvider;
