# Codespace Connections Feature

Diese Erweiterung fügt die Möglichkeit hinzu, GitHub Repositories und Vercel Projekte mit Codespaces zu verbinden.

## Features

### 1. Codespace Settings Modal
- Neues Settings-Icon in jeder Codespace Card
- Verwaltung von GitHub und Vercel Verbindungen
- Übersichtliche Anzeige der aktuellen Verbindungen

### 2. GitHub Integration
- **Automatische Repository Erstellung**: Erstellt ein neues privates GitHub Repository
- **Vorhandenes Repository verbinden**: Auswahl aus bestehenden Repositories
- **Repository trennen**: Verbindung entfernen

### 3. Vercel Integration
- **Automatische Projekt Erstellung**: Erstellt ein neues Vercel Projekt (benötigt GitHub Repository)
- **Vorhandenes Projekt verbinden**: Auswahl aus bestehenden Vercel Projekten
- **Projekt trennen**: Verbindung entfernen

### 4. Auto-Create beim Codespace erstellen
- Checkbox "Automatisch GitHub Repository erstellen"
- Checkbox "Automatisch Vercel Projekt erstellen"
- Automatische Verknüpfung während der Erstellung

## Datenbankstruktur

### Neue Tabellen:
- `codespace_github_repos`: Verbindungen zwischen Codespaces und GitHub Repositories
- `codespace_vercel_projects`: Verbindungen zwischen Codespaces und Vercel Projekten

### Migration ausführen:
```bash
php backend/run_codespace_connections_migration.php
```

## API Endpoints

### codespace_connections.php

#### GitHub Aktionen:
- `action=connect_github`: Vorhandenes Repository verbinden
- `action=create_and_connect_github`: Neues Repository erstellen und verbinden
- `action=disconnect_github`: Repository Verbindung trennen
- `action=get_github`: GitHub Verbindung abrufen

#### Vercel Aktionen:
- `action=connect_vercel`: Vorhandenes Projekt verbinden
- `action=create_and_connect_vercel`: Neues Projekt erstellen und verbinden
- `action=disconnect_vercel`: Projekt Verbindung trennen
- `action=get_vercel`: Vercel Verbindung abrufen

#### Allgemein:
- `action=get_all_connections`: Alle Verbindungen eines Codespaces abrufen

## Erweiterte project_codespaces.php

Die bestehende API wurde erweitert um:
- `createGithubRepo=true`: Auto-create GitHub Repository
- `createVercelProject=true`: Auto-create Vercel Projekt

## Frontend Komponenten

### ManageCodespaces.vue Erweiterungen:
1. **Settings Button**: Öffnet das Verbindungs-Modal
2. **Settings Modal**: Zeigt aktuelle Verbindungen und Aktionen
3. **GitHub Repos Modal**: Auswahl vorhandener Repositories
4. **Vercel Projects Modal**: Auswahl vorhandener Projekte
5. **Auto-Create Checkboxes**: Im Create-Modal für neue Codespaces

## Verwendung

### Neue Codespace mit Auto-Create:
1. "Neuer Codespace" klicken
2. Codespace Details eingeben
3. "Automatisch GitHub Repository erstellen" aktivieren
4. Optional: "Automatisch Vercel Projekt erstellen" aktivieren
5. "Erstellen" klicken

### Nachträgliche Verbindung:
1. Settings-Icon in Codespace Card klicken
2. GitHub/Vercel Sektion nutzen
3. "Neues erstellen" oder "Vorhandenes verbinden" wählen

## Voraussetzungen

- GitHub Token muss für den User konfiguriert sein
- Vercel Token muss für den User konfiguriert sein (für Vercel Features)
- Entsprechende Berechtigungen in den APIs

## Test

Öffne `backend/test_codespace_connections.html` im Browser um die Funktionalität zu testen.
