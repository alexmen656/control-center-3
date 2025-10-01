# Module Dashboard Integration

## Übersicht

Dieses System ermöglicht es jedem Modul, seine Daten standardisiert für das Dashboard bereitzustellen. Module können Widgets (Stats, Charts, Tabellen) definieren, die dann im Dashboard angezeigt werden können.

## Architektur

```
src/
├── core/
│   └── registry/
│       └── DashboardRegistry.ts          # Zentrale Registry für alle Module
├── types/
│   └── dashboard.types.ts                 # Type Definitionen
├── composables/
│   └── useDashboardData.ts               # Vue Composable für Dashboard-Zugriff
├── modules/
│   └── appstore-connect/
│       ├── index.ts                       # Modul-Initialisierung
│       ├── dashboard.provider.ts          # Dashboard Provider mit Widgets
│       ├── routes.ts                      # Modul-spezifische Routes
│       └── components/
└── views/
    └── DashboardView.vue                  # Haupt-Dashboard View
```

## Für jedes Modul: Dashboard Provider erstellen

### 1. Dashboard Provider erstellen (`dashboard.provider.ts`)

```typescript
import axios from 'axios';
import type { ModuleDashboardProvider, DashboardWidget } from '@/types/dashboard.types';

export const myModuleDashboardProvider: ModuleDashboardProvider = {
  moduleId: 'my-module',
  moduleName: 'Mein Modul',
  moduleIcon: 'cube-outline',
  
  widgets: [
    // Stat Widget
    {
      id: 'my-module-total-count',
      type: 'stat',
      title: 'Gesamt Anzahl',
      icon: 'bar-chart-outline',
      category: 'stats',
      config: {
        color: 'primary',
        format: 'number'
      },
      getData: async (params?: any) => {
        const response = await axios.get('my_api.php', { params });
        return {
          value: response.data.total,
          trend: response.data.trend,
          label: 'Gesamt Anzahl'
        };
      }
    },
    
    // Chart Widget
    {
      id: 'my-module-chart',
      type: 'chart',
      title: 'Mein Chart',
      icon: 'stats-chart-outline',
      category: 'charts',
      config: {
        chartType: 'bar'
      },
      getData: async (params?: any) => {
        const response = await axios.get('my_api.php', { params });
        return {
          labels: response.data.labels,
          datasets: [{
            label: 'Daten',
            data: response.data.values,
            backgroundColor: '#2563eb'
          }]
        };
      }
    }
  ],
  
  getWidget(widgetId: string) {
    return this.widgets.find(w => w.id === widgetId);
  }
};

export default myModuleDashboardProvider;
```

### 2. Modul-Index aktualisieren (`index.ts`)

```typescript
import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import myModuleDashboardProvider from './dashboard.provider';

// Dashboard Provider registrieren
dashboardRegistry.register(myModuleDashboardProvider);

console.log('📦 Mein Modul initialized');

export default {
  name: 'my-module',
  version: '1.0.0',
  dashboardProvider: myModuleDashboardProvider
};
```

### 3. Routes definieren (`routes.ts`)

```typescript
import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/my-module',
    name: 'my-module',
    component: () => import('./components/MyModuleView.vue')
  }
];

export default routes;
```

## Widget-Typen

### Stat Widget
Zeigt eine einzelne Statistik an (z.B. Gesamtanzahl, Durchschnitt).

```typescript
{
  id: 'unique-id',
  type: 'stat',
  title: 'Widget Titel',
  icon: 'ionicon-name',
  category: 'stats',
  config: {
    color: 'primary' | 'success' | 'warning' | 'danger' | 'info',
    format: 'number' | 'currency' | 'percentage'
  },
  getData: async (params) => ({
    value: 1234,
    trend: 15, // Optional: Prozentuale Änderung
    label: 'Beschreibung'
  })
}
```

### Chart Widget
Zeigt ein Diagramm an (Pie, Bar, Line, etc.).

```typescript
{
  id: 'unique-id',
  type: 'chart',
  title: 'Chart Titel',
  icon: 'ionicon-name',
  category: 'charts',
  config: {
    chartType: 'pie' | 'donut' | 'bar' | 'line' | 'date_bar'
  },
  getData: async (params) => ({
    labels: ['Label 1', 'Label 2', 'Label 3'],
    datasets: [{
      label: 'Dataset Name',
      data: [10, 20, 30],
      backgroundColor: '#2563eb',
      borderColor: '#1d4ed8',
      borderWidth: 1
    }]
  })
}
```

### Table Widget
Zeigt eine Tabelle an.

```typescript
{
  id: 'unique-id',
  type: 'table',
  title: 'Tabelle Titel',
  icon: 'ionicon-name',
  category: 'tables',
  getData: async (params) => ({
    columns: [
      { key: 'id', label: 'ID', sortable: true },
      { key: 'name', label: 'Name', sortable: true }
    ],
    rows: [
      { id: 1, name: 'Item 1' },
      { id: 2, name: 'Item 2' }
    ],
    pagination: {
      page: 1,
      pageSize: 10,
      total: 100
    }
  })
}
```

## Dashboard View Verwendung

Die Widgets werden automatisch im Dashboard View verfügbar gemacht. Benutzer können:

1. Im Dashboard den **"+"** Button klicken
2. **"Module Widget"** auswählen
3. Das gewünschte **Modul** auswählen (z.B. "App Store Analytics")
4. Das gewünschte **Widget** auswählen (z.B. "Gesamte Downloads")
5. Das Widget wird zum Dashboard hinzugefügt

## Composable Verwendung

In Vue-Komponenten können Sie den `useDashboardData` Composable verwenden:

```typescript
import { useDashboardData } from '@/composables/useDashboardData';

export default {
  setup() {
    const {
      availableModules,
      availableWidgets,
      statWidgets,
      chartWidgets,
      getWidget,
      fetchWidgetData
    } = useDashboardData();
    
    // Alle Module anzeigen
    console.log(availableModules.value);
    
    // Widget-Daten laden
    const loadData = async () => {
      const data = await fetchWidgetData('appstore-connect', 'appstore-total-downloads', {
        period: 30
      });
      console.log(data);
    };
    
    return {
      availableModules,
      loadData
    };
  }
};
```

## Beispiel: AppStore Connect Modul

Das AppStore Connect Modul ist vollständig implementiert und bietet:

### Stat Widgets:
- **Gesamte Downloads** - Gesamtanzahl der Downloads
- **Einzigartige Geräte** - Anzahl einzigartiger Geräte
- **Länder** - Anzahl der Länder
- **Plattformen** - Anzahl der Plattformen

### Chart Widgets:
- **Downloads im Zeitverlauf** - Line Chart
- **Top Länder** - Pie Chart
- **Plattformen Verteilung** - Donut Chart
- **App Versionen** - Bar Chart

## Best Practices

1. **Eindeutige IDs**: Verwende eindeutige IDs für Widgets (z.B. `modulename-widget-name`)
2. **Error Handling**: Implementiere Error Handling in `getData()` Funktionen
3. **Caching**: Überlege Caching für häufig abgerufene Daten
4. **Performance**: Limitiere Datenmengen (z.B. Top 10)
5. **Parameter**: Unterstütze Standard-Parameter wie `period`, `project`
6. **Icons**: Verwende Ionicons für konsistentes Design
7. **Colors**: Nutze die vordefinierten Farben (primary, success, warning, etc.)

## Erweiterung

Um weitere Widget-Typen hinzuzufügen:

1. Erweitere `DashboardWidget['type']` in `dashboard.types.ts`
2. Implementiere Rendering in `DashboardDisplay.vue`
3. Füge entsprechende Logik in `DashboardView.vue` hinzu

## Troubleshooting

### Widget erscheint nicht
- Prüfe, ob das Modul in `main.ts` geladen wird
- Prüfe Console-Logs für Registrierung
- Prüfe, ob `dashboard.provider.ts` exportiert wird

### Daten laden nicht
- Prüfe API-Endpoints und Axios-Konfiguration
- Prüfe Browser Console für Fehler
- Teste `getData()` Funktion isoliert

### TypeScript Fehler
- Stelle sicher, dass alle Types aus `@/types/dashboard.types` importiert sind
- Prüfe `tsconfig.json` Pfad-Aliase
