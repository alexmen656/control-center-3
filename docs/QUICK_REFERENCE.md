# 🎯 Dashboard System - Quick Reference

## ✅ System Status
- ✅ **Core System**: Implementiert
- ✅ **AppStore Module**: 8 Widgets fertig
- ✅ **Template**: Vorlage verfügbar
- ✅ **Dokumentation**: Komplett
- ✅ **Fehler**: Behoben

---

## 🚀 Für Entwickler: Neues Widget erstellen (3 Schritte)

### 1️⃣ `dashboard.provider.ts` erstellen
```typescript
// src/modules/dein-modul/dashboard.provider.ts
import type { ModuleDashboardProvider } from '@/types/dashboard.types';

export const provider: ModuleDashboardProvider = {
  moduleId: 'dein-modul',
  moduleName: 'Dein Modul Name',
  
  widgets: [
    {
      id: 'dein-widget-id',
      type: 'stat',  // oder 'chart', 'table'
      title: 'Widget Titel',
      getData: async (params) => ({
        value: 123,
        label: 'Beschreibung'
      })
    }
  ],
  
  getWidget(id) {
    return this.widgets.find(w => w.id === id);
  }
};

export default provider;
```

### 2️⃣ `index.ts` registrieren
```typescript
// src/modules/dein-modul/index.ts
import { dashboardRegistry } from '@/core/registry/DashboardRegistry';
import provider from './dashboard.provider';

dashboardRegistry.register(provider);
```

### 3️⃣ Fertig! ✅
Widget erscheint automatisch im Dashboard.

---

## 📊 Widget-Typen Cheat Sheet

### Stat Widget (Einzelwert)
```typescript
{
  type: 'stat',
  getData: async () => ({
    value: 1234,
    trend: 15,        // Optional: % Änderung
    label: 'Text'
  })
}
```

### Chart Widget (Diagramm)
```typescript
{
  type: 'chart',
  config: {
    chartType: 'pie' | 'donut' | 'bar' | 'line'
  },
  getData: async () => ({
    labels: ['A', 'B', 'C'],
    datasets: [{
      label: 'Name',
      data: [10, 20, 30],
      backgroundColor: '#2563eb'
    }]
  })
}
```

### Table Widget (Tabelle)
```typescript
{
  type: 'table',
  getData: async () => ({
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'name', label: 'Name' }
    ],
    rows: [
      { id: 1, name: 'Item 1' }
    ]
  })
}
```

---

## 🎨 Verwendung im Dashboard

1. Öffne Dashboard: `/project/:project/dashboard/:dashboard`
2. Klick **+** Button (unten rechts)
3. Wähle **"Module Widget"**
4. Wähle **Modul** (z.B. "App Store Analytics")
5. Wähle **Widget** (z.B. "Gesamte Downloads")
6. Klick **Confirm**
7. ✨ Widget erscheint!

---

## 📁 Dateistruktur

```
src/modules/dein-modul/
├── index.ts                    # Registrierung
├── dashboard.provider.ts       # Widget-Definitionen
├── routes.ts                   # Routes
└── components/
    └── DeinView.vue
```

---

## 🔧 Wichtige Dateien

| Datei | Zweck |
|-------|-------|
| `src/core/registry/DashboardRegistry.ts` | Zentrale Verwaltung |
| `src/types/dashboard.types.ts` | Type Definitionen |
| `src/modules/_template/` | Vorlage für neue Module |
| `docs/MODULE_DASHBOARD_INTEGRATION.md` | Vollständige Doku |

---

## 💡 Farben

```typescript
config: {
  color: 'primary'   // Blau
       | 'success'   // Grün
       | 'warning'   // Orange
       | 'danger'    // Rot
       | 'info'      // Cyan
}
```

---

## 🎯 Beispiel: AppStore Module

**8 fertige Widgets:**
- ✅ Gesamte Downloads (Stat)
- ✅ Einzigartige Geräte (Stat)
- ✅ Länder (Stat)
- ✅ Plattformen (Stat)
- ✅ Downloads Timeline (Line Chart)
- ✅ Top Länder (Pie Chart)
- ✅ Plattformen Verteilung (Donut Chart)
- ✅ App Versionen (Bar Chart)

**Siehe:** `src/modules/appstore-connect/dashboard.provider.ts`

---

## 🐛 Troubleshooting

### Widget erscheint nicht?
1. Prüfe Console: `dashboardRegistry.getAllProviders()`
2. Prüfe ob Modul geladen: Siehe Console-Log beim App-Start
3. Prüfe `index.ts` - ist Provider registriert?

### Keine Daten?
1. Prüfe API-Endpoint in `getData()`
2. Prüfe Browser Console für Fehler
3. Teste `getData()` isoliert

### TypeScript Fehler?
1. Importiere Types: `import type { ... } from '@/types/dashboard.types'`
2. Prüfe `tsconfig.json`

---

## 📚 Dokumentation

- **Quick Start**: [DASHBOARD_SYSTEM_README.md](./DASHBOARD_SYSTEM_README.md)
- **Vollständig**: [MODULE_DASHBOARD_INTEGRATION.md](./MODULE_DASHBOARD_INTEGRATION.md)
- **Zusammenfassung**: [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md)

---

## ✨ Features

✅ Standardisiert - Einheitliche API
✅ Typsicher - Full TypeScript
✅ Automatisch - Auto-Loading
✅ Flexibel - Mehrere Widget-Typen
✅ Erweiterbar - Neue Typen einfach
✅ Template - Fertige Vorlage

---

**Das System ist einsatzbereit!** 🚀
