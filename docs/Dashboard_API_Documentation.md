# AI Dashboard Generator - Kompakte Referenz

## Verfügbare Formulare und Felder
**Endpoint:** `form.php`
- `get_forms`: Gibt alle Formulare eines Projekts zurück
- Jedes Formular hat: `title`, `inputs[]` mit `name`, `type`, `label`

## Chart-Typen
- `pie_chart`: Kategorien als Kreisdiagramm
- `donut_chart`: Kategorien als Donut
- `bar_chart`: Vergleich von Kategorien
- `date_bar_chart`: Zeitbasierte Analyse (Stunden/Tage/Wochen/Monate/Jahre)

## Chart-Konfiguration
```json
{
  "chart_type": "pie_chart|donut_chart|bar_chart|date_bar_chart",
  "form": "formular_name",
  "label": "feld_für_beschriftung",
  "data": "feld_für_werte",
  "date_stamps": "hours|days|weeks|months|years", // nur date_bar_chart
  "method": "count" // nur date_bar_chart
}
```

## Dashboard API
**Endpoint:** `dashboard.php`
- `new_chart`: Fügt Chart hinzu
- Parameter: `dashboard`, `project`, `json` (Chart-Config als JSON-String)

## AI Schema für Dashboard-Generierung
```json
{
  "dashboard_title": "string",
  "charts": [
    {
      "chart_type": "pie_chart|donut_chart|bar_chart|date_bar_chart",
      "form": "formular_name",
      "label": "feld_name",
      "data": "feld_name",
      "date_stamps": "days", // optional für date_bar_chart
      "method": "count" // optional für date_bar_chart
    }
  ]
}
```

## Intelligente Chart-Auswahl Regeln
- **Kategorien/Text-Felder** → `pie_chart` oder `donut_chart`
- **Vergleiche** → `bar_chart`
- **Datum-Felder vorhanden** → `date_bar_chart`
- **Numerische Felder** → Datenfeld für Charts
- **Text/Select-Felder** → Label-Feld für Charts
