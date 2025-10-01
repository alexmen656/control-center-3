/**
 * useDashboardData Composable
 * 
 * Provides easy access to dashboard data from modules
 */

import { ref, computed } from 'vue';
import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import type { DashboardWidget, ModuleDashboardProvider } from '@/types/dashboard.types';

export function useDashboardData() {
  const loading = ref(false);
  const error = ref<string | null>(null);
  
  /**
   * Get all available modules with dashboard providers
   */
  const availableModules = computed(() => {
    return dashboardRegistry.getAllProviders();
  });
  
  /**
   * Get all available widgets
   */
  const availableWidgets = computed(() => {
    return dashboardRegistry.getAllWidgets();
  });
  
  /**
   * Get a specific provider
   */
  const getProvider = (moduleId: string): ModuleDashboardProvider | undefined => {
    return dashboardRegistry.getProvider(moduleId);
  };
  
  /**
   * Get a specific widget
   */
  const getWidget = (moduleId: string, widgetId: string): DashboardWidget | undefined => {
    return dashboardRegistry.getWidget(moduleId, widgetId);
  };
  
  /**
   * Fetch data from a widget
   */
  const fetchWidgetData = async (moduleId: string, widgetId: string, params?: any) => {
    loading.value = true;
    error.value = null;
    
    try {
      const widget = dashboardRegistry.getWidget(moduleId, widgetId);
      
      if (!widget) {
        throw new Error(`Widget ${widgetId} not found in module ${moduleId}`);
      }
      
      const data = await widget.getData(params);
      loading.value = false;
      return data;
    } catch (e: any) {
      error.value = e.message || 'Error fetching widget data';
      loading.value = false;
      throw e;
    }
  };
  
  /**
   * Get widgets by category
   */
  const getWidgetsByCategory = (category: string) => {
    return dashboardRegistry.getWidgetsByCategory(category);
  };
  
  /**
   * Get widgets by type
   */
  const getWidgetsByType = (type: DashboardWidget['type']) => {
    return dashboardRegistry.getWidgetsByType(type);
  };
  
  /**
   * Get stat widgets (for quick stats display)
   */
  const statWidgets = computed(() => {
    return getWidgetsByType('stat');
  });
  
  /**
   * Get chart widgets
   */
  const chartWidgets = computed(() => {
    return getWidgetsByType('chart');
  });
  
  return {
    loading,
    error,
    availableModules,
    availableWidgets,
    statWidgets,
    chartWidgets,
    getProvider,
    getWidget,
    fetchWidgetData,
    getWidgetsByCategory,
    getWidgetsByType
  };
}
