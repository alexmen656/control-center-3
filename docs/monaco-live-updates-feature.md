# Monaco IDE Live Updates & Diff Viewer Features

## Übersicht

Zwei neue Features wurden zur Monaco IDE hinzugefügt:

1. **Live Updates** - Automatische Aktualisierung der Git Changes Section
2. **Diff Viewer** - Visueller Diff-Viewer mit Syntax-Highlighting

## 1. Live Updates Feature

### Funktionalität
- **Auto-Refresh**: Git Changes werden automatisch alle 2 Sekunden aktualisiert
- **Event-basiert**: Sofortige Updates bei Datei-Änderungen oder -Speicherungen
- **Toggle-Button**: Ein/Aus-Schalter für Auto-Refresh in der Source Control Section

### Technische Details

#### Events
- `monaco-file-saved`: Wird ausgelöst wenn eine Datei gespeichert wird
- `monaco-file-changed`: Wird ausgelöst wenn Datei-Inhalt geändert wird

#### Implementierung
```javascript
// In MonacoSidebar.vue
const startLiveGitUpdates = () => {
  if (isAutoRefreshEnabled.value) {
    gitRefreshInterval.value = setInterval(() => {
      loadGitData()
    }, 2000)
  }
}

// In Modul1View.vue - Auto-save mit Event
const saveFile = async (filename, content) => {
  // ... save logic ...
  window.dispatchEvent(new CustomEvent('monaco-file-saved', { 
    detail: { filename, content, projectName } 
  }))
}
```

#### UI Elements
- **Sync Button**: Zeigt den Status der Auto-Refresh-Funktion
  - `sync` Icon: Auto-Refresh aktiviert (blau hinterlegt)
  - `sync-outline` Icon: Auto-Refresh deaktiviert
- **Tooltip**: Zeigt aktuellen Status beim Hover

## 2. Diff Viewer Feature

### Funktionalität
- **Klickbare Datei-Items**: Klick auf Dateien in der Changes-Liste öffnet Diff-Viewer
- **VSCode-ähnliches Design**: Grüne/rote Highlighting wie in VSCode
- **Modal Window**: Overlay-Fenster mit vollständiger Diff-Anzeige
- **Keyboard Support**: Escape-Taste zum Schließen

### Technische Details

#### Backend Enhancement (monaco_git_api.php)
```php
function getFileDiff($projectPath, $file, $project, $userID) {
    // Lädt Original- und aktuelle Datei-Inhalte
    // Generiert Line-by-Line Diff
    // Unterstützt GitHub Integration für Original-Content
}

function generateLineDiff($originalLines, $currentLines) {
    // Erstellt strukturierten Diff mit:
    // - type: 'added', 'deleted', 'unchanged'
    // - lineNumber: Zeilennummer
    // - content: Zeilen-Inhalt
}
```

#### Frontend Implementation
```vue
<!-- Klickbare File Items -->
<div class="file-item" @click="viewFileDiff(file.path)">
  <!-- File content with click.stop for buttons -->
  <ion-button @click.stop="stageFile(file.path)">
</div>
```

#### Diff Viewer Modal
- **Styling**: VSCode-Themes kompatibel
- **Line Numbers**: Zeigt Zeilennummern links
- **Color Coding**: 
  - Grün: Hinzugefügte Zeilen
  - Rot: Gelöschte Zeilen
  - Normal: Unveränderte Zeilen

### CSS Styling
```css
.diff-line.added {
  background: var(--vscode-diffEditor-insertedTextBackground, rgba(155, 185, 85, 0.2));
}

.diff-line.deleted {
  background: var(--vscode-diffEditor-removedTextBackground, rgba(255, 0, 0, 0.2));
}
```

## API Endpoints

### Neue/Erweiterte Endpoints

#### GET Diff
```
GET /backend/monaco_git_api.php?project={PROJECT}&action=diff&file={FILEPATH}
```

**Response:**
```json
{
  "success": true,
  "file": "main.js",
  "diff": [
    {
      "type": "unchanged",
      "lineNumber": 1,
      "content": "// Original code"
    },
    {
      "type": "deleted", 
      "lineNumber": 2,
      "content": "console.log('old');"
    },
    {
      "type": "added",
      "lineNumber": 2,
      "content": "console.log('new');"
    }
  ],
  "original_content": "...",
  "current_content": "..."
}
```

## Benutzung

### Live Updates
1. **Automatisch**: Startet automatisch beim Laden der Monaco IDE
2. **Manual Toggle**: Klick auf den Sync-Button im Source Control Header
3. **Events**: Funktioniert automatisch bei Datei-Änderungen

### Diff Viewer
1. **Öffnen**: Klick auf eine Datei in der "Changes" Liste
2. **Navigation**: Scroll durch die Änderungen
3. **Schließen**: 
   - Escape-Taste
   - Klick auf X-Button
   - Klick außerhalb des Modals

## Performance Considerations

- **Intervall**: 2-Sekunden-Refresh-Rate (konfigurierbar)
- **Event-basiert**: Sofortige Updates nur bei tatsächlichen Änderungen
- **Cleanup**: Automatisches Stoppen der Intervalle beim Verlassen der Seite
- **Lazy Loading**: Diff wird nur geladen wenn tatsächlich angezeigt

## Browser Compatibility

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **CSS Variables**: Verwendet VSCode CSS Custom Properties
- **ES6+ Features**: Verwendung von Refs, Computed, etc.

## Future Enhancements

1. **Split View**: Side-by-side Diff Anzeige
2. **Inline Edit**: Direkte Bearbeitung im Diff Viewer
3. **Syntax Highlighting**: Code-spezifische Hervorhebung
4. **File History**: Diff zwischen verschiedenen Commits
5. **Configurable Refresh Rate**: Benutzer-definierte Refresh-Intervalle
