/**
 * Dashboard Types
 * 
 * Zentrale Type Definitionen für das Dashboard System
 */

export interface DashboardWidgetData {
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
    tension?: number;
    fill?: boolean;
    [key: string]: any;
  }>;
}

export interface StatWidgetData {
  value: number | string;
  label: string;
  trend?: number;
  trendLabel?: string;
  icon?: string;
  color?: string;
}

export interface TableWidgetData {
  columns: Array<{
    key: string;
    label: string;
    sortable?: boolean;
    format?: (value: any) => string;
  }>;
  rows: Array<{ [key: string]: any }>;
  pagination?: {
    page: number;
    pageSize: number;
    total: number;
  };
}

export interface DashboardWidget {
  id: string;
  type: 'stat' | 'chart' | 'table' | 'card';
  title: string;
  icon?: string;
  category?: string;
  getData: (params?: any) => Promise<DashboardWidgetData | StatWidgetData | TableWidgetData | any>;
  config?: {
    chartType?: 'pie' | 'donut' | 'bar' | 'line' | 'date_bar' | 'radar' | 'polarArea';
    color?: string;
    format?: 'number' | 'currency' | 'percentage' | 'date' | 'datetime';
    refreshInterval?: number; // in seconds
    height?: string;
    width?: string;
    [key: string]: any;
  };
}

export interface ModuleDashboardProvider {
  moduleId: string;
  moduleName: string;
  moduleIcon?: string;
  moduleColor?: string;
  widgets: DashboardWidget[];
  getWidget: (widgetId: string) => DashboardWidget | undefined;
}

export interface DashboardLayout {
  id: string;
  name: string;
  widgets: Array<{
    widgetId: string;
    moduleId: string;
    position: {
      x: number;
      y: number;
      w: number;
      h: number;
    };
    params?: any;
  }>;
}

export interface DashboardConfig {
  dashboardId: string;
  projectId: string;
  layouts: DashboardLayout[];
  defaultLayout?: string;
  refreshInterval?: number;
  theme?: 'light' | 'dark' | 'auto';
}
