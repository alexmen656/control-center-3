# Universelles Modul-Dashboard System

## 🎯 Konzept

Jedes Modul kann seine Daten standardisiert für das Dashboard bereitstellen. Ähnlich wie `routes.ts` für Routes gibt es jetzt `dashboard.provider.ts` für Dashboard-Widgets.

## 📁 Struktur pro Modul

```
src/modules/mein-modul/
├── index.ts                    # Modul-Initialisierung
├── routes.ts                   # Routes (wie bisher)
├── dashboard.provider.ts       # NEU: Dashboard Widgets
├── components/
│   └── MeinModulView.vue
└── config.ts                   # Optional: Modul-Config
```

## 🚀 Quick Start

### 1. Dashboard Provider erstellen

```typescript
// src/modules/mein-modul/dashboard.provider.ts
import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const dashboardProvider: ModuleDashboardProvider = {
  moduleId: 'mein-modul',
  moduleName: 'Mein Modul',
  moduleIcon: 'cube-outline',
  
  widgets: [
    {
      id: 'mein-modul-stat',
      type: 'stat',
      title: 'Meine Statistik',
      icon: 'bar-chart-outline',
      getData: async () => ({
        value: 42,
        label: 'Beschreibung'
      })
    }
  ],
  
  getWidget(id) {
    return this.widgets.find(w => w.id === id);
  }
};

export default dashboardProvider;
```

### 2. Modul registrieren

```typescript
// src/modules/mein-modul/index.ts
import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import dashboardProvider from './dashboard.provider';

dashboardRegistry.register(dashboardProvider);

export default {
  name: 'mein-modul',
  dashboardProvider
};
```

### 3. Fertig! 🎉

Das Widget erscheint automatisch im Dashboard unter "Module Widget".

## 📊 Widget-Typen

| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| **stat** | Einzelne Zahl/Statistik | Gesamtanzahl, Durchschnitt |
| **chart** | Diagramme | Pie, Bar, Line Charts |
| **table** | Tabellen | Liste von Datensätzen |
| **card** | Benutzerdefiniert | Beliebiger Content |

## 💡 Beispiel: AppStore Connect

Das AppStore-Modul ist vollständig implementiert und zeigt:
- ✅ 4 Stat-Widgets (Downloads, Geräte, Länder, Plattformen)
- ✅ 4 Chart-Widgets (Timeline, Länder, Plattformen, Versionen)
- ✅ Automatische Datenladung aus API
- ✅ Fehlerbehandlung

## 🔧 Verwendung im Dashboard

1. Dashboard öffnen: `/project/:project/dashboard/:dashboard`
2. **+** Button klicken
3. **"Module Widget"** wählen
4. Modul auswählen (z.B. "App Store Analytics")
5. Widget auswählen (z.B. "Gesamte Downloads")
6. Widget wird angezeigt ✨

## 📚 Weitere Dokumentation

Siehe [MODULE_DASHBOARD_INTEGRATION.md](./MODULE_DASHBOARD_INTEGRATION.md) für:
- Detaillierte Widget-Konfiguration
- API-Referenz
- Best Practices
- Troubleshooting
- Erweiterte Beispiele

## 🎨 Features

- ✅ **Standardisiert**: Einheitliche Schnittstelle für alle Module
- ✅ **Typsicher**: Vollständige TypeScript-Unterstützung
- ✅ **Automatisch**: Module werden beim App-Start geladen
- ✅ **Flexibel**: Unterstützt verschiedene Widget-Typen
- ✅ **Erweiterbar**: Neue Widget-Typen einfach hinzufügbar
- ✅ **Composable**: Vue 3 Composable für einfachen Zugriff

## 🔄 System-Komponenten

| Komponente | Zweck |
|------------|-------|
| `DashboardRegistry` | Zentrale Verwaltung aller Dashboard-Provider |
| `dashboard.types.ts` | TypeScript Type Definitionen |
| `useDashboardData` | Vue Composable für Dashboard-Zugriff |
| `DashboardView.vue` | Haupt-Dashboard mit Widget-Auswahl |
| `dashboard.provider.ts` | Pro-Modul Provider-Definition |

## 🎯 Vorteile

1. **Universell**: Funktioniert für alle Module gleich
2. **Wartbar**: Zentrale Type-Definitionen
3. **Skalierbar**: Beliebig viele Module und Widgets
4. **Wiederverwendbar**: Widgets können in mehreren Dashboards verwendet werden
5. **Testbar**: Provider isoliert testbar
