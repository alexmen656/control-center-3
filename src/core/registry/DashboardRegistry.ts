/**
 * Dashboard Registry
 * 
 * Zentrale Verwaltung aller Module-Dashboard-Provider
 */

import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

class DashboardRegistry {
  private providers: Map<string, ModuleDashboardProvider> = new Map();
  
  /**
   * Registriert einen neuen Dashboard Provider
   */
  register(provider: ModuleDashboardProvider): void {
    if (this.providers.has(provider.moduleId)) {
      console.warn(`Dashboard Provider for module "${provider.moduleId}" is already registered. Overwriting...`);
    }
    
    this.providers.set(provider.moduleId, provider);
    console.log(`✅ Dashboard Provider registered: ${provider.moduleName} (${provider.moduleId})`);
  }
  
  /**
   * Gibt einen Provider anhand der Module-ID zurück
   */
  getProvider(moduleId: string): ModuleDashboardProvider | undefined {
    return this.providers.get(moduleId);
  }
  
  /**
   * Gibt alle registrierten Provider zurück
   */
  getAllProviders(): ModuleDashboardProvider[] {
    return Array.from(this.providers.values());
  }
  
  /**
   * Gibt alle verfügbaren Widgets zurück
   */
  getAllWidgets(): Array<DashboardWidget & { moduleId: string; moduleName: string }> {
    const widgets: Array<DashboardWidget & { moduleId: string; moduleName: string }> = [];
    
    this.providers.forEach((provider) => {
      provider.widgets.forEach((widget) => {
        widgets.push({
          ...widget,
          moduleId: provider.moduleId,
          moduleName: provider.moduleName
        });
      });
    });
    
    return widgets;
  }
  
  /**
   * Gibt ein bestimmtes Widget zurück
   */
  getWidget(moduleId: string, widgetId: string): DashboardWidget | undefined {
    const provider = this.providers.get(moduleId);
    return provider?.getWidget(widgetId);
  }
  
  /**
   * Sucht Widgets nach Kategorie
   */
  getWidgetsByCategory(category: string): Array<DashboardWidget & { moduleId: string; moduleName: string }> {
    return this.getAllWidgets().filter(widget => widget.category === category);
  }
  
  /**
   * Sucht Widgets nach Typ
   */
  getWidgetsByType(type: DashboardWidget['type']): Array<DashboardWidget & { moduleId: string; moduleName: string }> {
    return this.getAllWidgets().filter(widget => widget.type === type);
  }
  
  /**
   * Entfernt einen Provider
   */
  unregister(moduleId: string): boolean {
    return this.providers.delete(moduleId);
  }
  
  /**
   * Löscht alle Provider
   */
  clear(): void {
    this.providers.clear();
  }
  
  /**
   * Gibt die Anzahl der registrierten Provider zurück
   */
  get size(): number {
    return this.providers.size;
  }
}

// Singleton Instance
export const dashboardRegistry = new DashboardRegistry();

export default dashboardRegistry;
