// Simuliert den Zugriff auf Projekt-Daten (könnte eine API-Abfrage oder eine Datenbankabfrage sein)
export const getProjectData = async (projectId: string) => {
    // Hier kommt die Logik zum Abrufen der Projekt-Daten
    const projectData = {
        id: projectId,
        name: "Projekt 1",
        description: "Dies ist ein Beispiel-Projekt",
        tables: {
            users: [{ id: 1, name: 'Max Mustermann' }, { id: 2, name: 'Anna Müller' }],
            settings: { theme: 'dark', language: 'de' },
        },
    };

    return projectData;
};
