/**
 * Template Dashboard Provider
 * 
 * Kopiere diese Datei und passe sie an dein Modul an.
 */

import axios from 'axios';
import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const templateDashboardProvider: ModuleDashboardProvider = {
  // Eindeutige Modul-ID (lowercase, kebab-case)
  moduleId: 'template-module',
  
  // Anzeigename des Moduls
  moduleName: 'Template Modul',
  
  // Ionicon Name (optional)
  moduleIcon: 'cube-outline',
  
  // Modul-Farbe (optional)
  moduleColor: '#2563eb',
  
  widgets: [
    // ===== BEISPIEL: Stat Widget =====
    {
      id: 'template-stat-example',
      type: 'stat',
      title: 'Beispiel Statistik',
      icon: 'bar-chart-outline',
      category: 'stats',
      config: {
        color: 'primary', // primary, success, warning, danger, info
        format: 'number'  // number, currency, percentage
      },
      getData: async (params?: { period?: number; project?: string }) => {
        try {
          // API-Aufruf
          const response = await axios.get('your_api.php', {
            params: {
              period: params?.period || 30,
              project: params?.project
            }
          });
          
          return {
            value: response.data.total || 0,
            trend: response.data.trend || 0,  // Optional: Prozent-Änderung
            label: 'Beschreibung'
          };
        } catch (error) {
          console.error('Error fetching stat:', error);
          return { value: 0, label: 'Beschreibung' };
        }
      }
    },
    
    // ===== BEISPIEL: Pie/Donut Chart =====
    {
      id: 'template-pie-chart',
      type: 'chart',
      title: 'Verteilung',
      icon: 'pie-chart-outline',
      category: 'charts',
      config: {
        chartType: 'pie' // oder 'donut'
      },
      getData: async (params?: any) => {
        try {
          const response = await axios.get('your_api.php', { params });
          
          // Farben für Pie/Donut Charts
          const colors = [
            '#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6',
            '#0891b2', '#f59e0b', '#10b981', '#ef4444', '#6366f1'
          ];
          
          return {
            labels: ['Kategorie 1', 'Kategorie 2', 'Kategorie 3'],
            datasets: [{
              label: 'Datensatz',
              data: [30, 50, 20],
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          };
        } catch (error) {
          console.error('Error fetching pie chart:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    // ===== BEISPIEL: Bar Chart =====
    {
      id: 'template-bar-chart',
      type: 'chart',
      title: 'Balkendiagramm',
      icon: 'bar-chart-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: any) => {
        try {
          const response = await axios.get('your_api.php', { params });
          
          return {
            labels: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai'],
            datasets: [{
              label: 'Datensatz',
              data: [10, 20, 15, 30, 25],
              backgroundColor: '#2563eb',
              borderColor: '#1d4ed8',
              borderWidth: 1
            }]
          };
        } catch (error) {
          console.error('Error fetching bar chart:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    // ===== BEISPIEL: Line Chart (Timeline) =====
    {
      id: 'template-line-chart',
      type: 'chart',
      title: 'Zeitverlauf',
      icon: 'trending-up-outline',
      category: 'charts',
      config: {
        chartType: 'line'
      },
      getData: async (params?: { period?: number; dateStamp?: 'hours' | 'days' | 'weeks' | 'months' }) => {
        try {
          const response = await axios.get('your_api.php', { params });
          const data = response.data || [];
          
          // Gruppiere nach Datum
          const groupedData: { [key: string]: number } = {};
          const dateStamp = params?.dateStamp || 'days';
          
          data.forEach((item: any) => {
            let dateKey: string;
            
            if (dateStamp === 'hours') {
              const [date, time] = item.date.split(' ');
              const hour = time?.split(':')[0] || '00';
              dateKey = `${date} ${hour}:00:00`;
            } else {
              dateKey = item.date.split(' ')[0];
            }
            
            groupedData[dateKey] = (groupedData[dateKey] || 0) + item.value;
          });
          
          // Erstelle Labels und Werte für die letzten X Tage
          const labels: string[] = [];
          const values: number[] = [];
          const days = params?.period || 7;
          const today = new Date();
          
          for (let i = days - 1; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(today.getDate() - i);
            const dateString = date.toISOString().split('T')[0];
            labels.push(dateString);
            values.push(groupedData[dateString] || 0);
          }
          
          return {
            labels,
            datasets: [{
              label: 'Werte',
              data: values,
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              borderColor: '#2563eb',
              borderWidth: 2,
              tension: 0.4,  // Kurven-Glättung
              fill: true     // Fläche füllen
            }]
          };
        } catch (error) {
          console.error('Error fetching line chart:', error);
          return { labels: [], datasets: [] };
        }
      }
    },
    
    // ===== BEISPIEL: Table Widget =====
    {
      id: 'template-table',
      type: 'table',
      title: 'Datentabelle',
      icon: 'list-outline',
      category: 'tables',
      getData: async (params?: { page?: number; pageSize?: number }) => {
        try {
          const response = await axios.get('your_api.php', { params });
          
          return {
            columns: [
              { key: 'id', label: 'ID', sortable: true },
              { key: 'name', label: 'Name', sortable: true },
              { key: 'value', label: 'Wert', sortable: true, format: (v: number) => v.toFixed(2) },
              { key: 'date', label: 'Datum', sortable: true }
            ],
            rows: response.data.items || [],
            pagination: {
              page: params?.page || 1,
              pageSize: params?.pageSize || 10,
              total: response.data.total || 0
            }
          };
        } catch (error) {
          console.error('Error fetching table:', error);
          return { columns: [], rows: [], pagination: { page: 1, pageSize: 10, total: 0 } };
        }
      }
    }
  ],
  
  getWidget(widgetId: string) {
    return this.widgets.find(w => w.id === widgetId);
  }
};

export default templateDashboardProvider;
