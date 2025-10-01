# 📊 Dashboard Widget Rendering Guide

## Übersicht: DashboardDisplay.vue

Die `DashboardDisplay.vue` Komponente unterstützt jetzt **4 Widget-Layouts**:

---

## 1️⃣ Stat Widgets (Kleine Karten)

### Layout
```
┌──────────────────────────────────────────────────────┐
│  📊 Gesamte Downloads                                 │
│                                                        │
│  1,234                                                 │
│  Total Downloads                                       │
│  📈 +15%                                               │
└──────────────────────────────────────────────────────┘
```

### Verwendung
```typescript
{
  chart_type: 'stat',
  title: 'Gesamte Downloads',
  icon: 'download-outline',
  data: {
    value: 1234,
    label: 'Total Downloads',
    trend: 15  // Optional: +15% Änderung
  }
}
```

### Responsive Grid
- **Mobile (12 cols)**: 1 pro Zeile
- **Tablet (6 cols)**: 2 pro Zeile  
- **Desktop (4 cols)**: 3 pro Zeile
- **Large (3 cols)**: 4 pro Zeile

### Styling
- ✅ Icon mit Primary Color
- ✅ Große Zahl (2.5rem)
- ✅ Trend-Badge (Grün für positiv, Rot für negativ)
- ✅ Dark Mode Support

---

## 2️⃣ Small Charts (Pie, Donut, Cards)

### Layout
```
┌────────────────────────────────────────────────────┐
│  Top Länder                                         │
│  ┌────────────────────────────────────────────┐   │
│  │         [Pie Chart]                         │   │
│  │                                             │   │
│  └────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────┘
```

### Verwendung
```typescript
{
  chart_type: 'pie_chart', // oder 'donut_chart'
  title: 'Top Länder',
  data: {
    labels: ['Germany', 'USA', 'UK'],
    datasets: [{
      data: [50, 30, 20],
      backgroundColor: ['#2563eb', '#059669', '#d97706']
    }]
  }
}
```

### Responsive Grid
- **Mobile (12 cols)**: 1 pro Zeile
- **Desktop (6 cols)**: 2 pro Zeile
- **Large (4 cols)**: 3 pro Zeile

---

## 3️⃣ Large Charts (Bar, Line/Timeline)

### Layout
```
┌──────────────────────────────────────────────────────────────────┐
│  Downloads im Zeitverlauf                                         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                                                            │   │
│  │         [Bar/Line Chart - Volle Breite]                   │   │
│  │                                                            │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

### Verwendung
```typescript
{
  chart_type: 'date_bar_chart', // oder 'bar_chart'
  title: 'Downloads im Zeitverlauf',
  data: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    datasets: [{
      label: 'Downloads',
      data: [120, 190, 150, 220, 180],
      backgroundColor: '#2563eb'
    }]
  }
}
```

### Responsive Grid
- **Mobile (12 cols)**: Volle Breite
- **Desktop (12 cols)**: Volle Breite
- **Large (8 cols)**: 2/3 Breite

---

## 4️⃣ Card Widgets (Shortcuts)

### Layout
```
┌────────────────────────────────┐
│  Quick Link Card                │
│  [Icon/Image]                   │
│  Navigation zu View             │
└────────────────────────────────┘
```

### Verwendung
```typescript
{
  chart_type: 'card',
  name: 'My View',
  url: 'path/to/view'
}
```

---

## 🎨 CSS Styling

### Stat Card Styles
```css
.stat-card {
  height: 100%;
  /* Automatische Höhenanpassung */
}

.stat-icon {
  font-size: 28px;
  color: var(--ion-color-primary);
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
}

.stat-trend {
  display: inline-flex;
  padding: 4px 12px;
  border-radius: 12px;
}

.stat-trend.positive {
  background: rgba(16, 185, 129, 0.1);
  color: var(--ion-color-success);
}

.stat-trend.negative {
  background: rgba(239, 68, 68, 0.1);
  color: var(--ion-color-danger);
}
```

---

## 📱 Responsive Breakpoints

| Breakpoint | Stat Widgets | Small Charts | Large Charts |
|------------|--------------|--------------|--------------|
| Mobile (<768px) | 1 pro Zeile | 1 pro Zeile | Volle Breite |
| Tablet (768-992px) | 2 pro Zeile | 2 pro Zeile | Volle Breite |
| Desktop (992-1200px) | 3 pro Zeile | 2 pro Zeile | Volle Breite |
| Large (>1200px) | 4 pro Zeile | 3 pro Zeile | 2/3 Breite |

---

## 🔧 Computed Properties

Die Komponente verwendet computed properties für bessere Performance:

```javascript
computed: {
  // Filtert nur Stat-Widgets
  statCharts() {
    return this.charts.filter(chart => chart.chart_type === 'stat');
  },
  
  // Filtert kleine Charts (Pie, Donut, Cards)
  smallCharts() {
    return this.charts.filter(chart => 
      chart.chart_type !== 'date_bar_chart' && 
      chart.chart_type !== 'bar_chart' &&
      chart.chart_type !== 'stat'
    );
  },
  
  // Filtert große Charts (Bar, Line)
  largeCharts() {
    return this.charts.filter(chart => 
      chart.chart_type === 'date_bar_chart' || 
      chart.chart_type === 'bar_chart'
    );
  }
}
```

---

## 🎯 Beispiel: Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────┐
│  [Stat 1]  [Stat 2]  [Stat 3]  [Stat 4]                         │
│                                                                   │
│  [Pie Chart      ]  [Donut Chart    ]  [Card        ]           │
│                                                                   │
│  [Bar Chart - Timeline ────────────────────────────────]         │
│                                                                   │
│  [Line Chart - Downloads ──────────────────────────────]         │
└─────────────────────────────────────────────────────────────────┘
```

---

## ✅ Features

### Stat Widgets
- ✅ Responsive Sizing (1-4 pro Zeile)
- ✅ Icon Support (Ionicons)
- ✅ Trend Indicator (+/- mit Farbe)
- ✅ Formatierung (1,234 statt 1234)
- ✅ Dark Mode Support

### Chart Widgets
- ✅ Title Header
- ✅ Card Wrapping
- ✅ Chart.js Integration
- ✅ Responsive Charts

### Edit Mode
- ✅ Delete Button für jedes Widget
- ✅ Icon-Only Trash Button
- ✅ Danger Color

---

## 🚀 Verwendung

```vue
<DashboardDisplay
  :charts="myCharts"
  :editView="isEditMode"
  @deleteChart="handleDelete"
/>
```

### Props
- `charts` (Array, required) - Array von Chart-Objekten
- `editView` (Boolean, required) - Edit-Modus aktivieren
- `options` (Object, optional) - Chart.js Optionen

### Events
- `@deleteChart(index)` - Widget löschen

---

## 📊 Chart Format

Alle Charts folgen dem Chart.js Format:

```javascript
{
  labels: ['Label 1', 'Label 2', 'Label 3'],
  datasets: [{
    label: 'Dataset Name',
    data: [10, 20, 30],
    backgroundColor: '#2563eb',
    borderColor: '#1d4ed8',
    borderWidth: 1
  }]
}
```

---

**DashboardDisplay.vue ist jetzt bereit für alle Widget-Typen!** 🎉
