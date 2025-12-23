import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import vueFetch from '@/composables/vueFetch';

export const useProjectStore = defineStore('project', () => {
  const projects = ref([]);
  const currentProject = ref(null);
  const isLoading = ref(false);
  const error = ref(null);

  // Getter
  const getProjects = computed(() => projects.value);
  const getCurrentProject = computed(() => currentProject.value);
  const getIsLoading = computed(() => isLoading.value);
  const getError = computed(() => error.value);

  // Projekte vom Backend laden
  const loadProjects = async () => {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await vueFetch('https://alex.polan.sk/backend/api/projects.php', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.status === 'success') {
        projects.value = response.data || [];
      } else {
        throw new Error(response.message || 'Fehler beim Laden der Projekte');
      }
    } catch (err) {
      console.error('Fehler beim Laden der Projekte:', err);
      error.value = err.message;
    } finally {
      isLoading.value = false;
    }
  };

  // Neues Projekt erstellen
  const createProject = async (projectData) => {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await vueFetch('https://alex.polan.sk/backend/api/projects.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(projectData)
      });

      if (response.status === 'success') {
        // Füge das neue Projekt zur Liste hinzu
        const newProject = response.data;
        projects.value.push(newProject);
        currentProject.value = newProject;
        return newProject;
      } else {
        throw new Error(response.message || 'Fehler beim Erstellen des Projekts');
      }
    } catch (err) {
      console.error('Fehler beim Erstellen des Projekts:', err);
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  // Aktuelles Projekt setzen
  const setCurrentProject = (project) => {
    currentProject.value = project;
    // Aktuellen Projektnamen im Session Storage speichern für Persistenz
    if (project) {
      sessionStorage.setItem('currentProjectId', project.id);
    } else {
      sessionStorage.removeItem('currentProjectId');
    }
  };

  // Projekt aktualisieren
  const updateProject = async (projectId, projectData) => {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await vueFetch(`https://alex.polan.sk/backend/api/projects.php?id=${projectId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(projectData)
      });

      if (response.status === 'success') {
        // Aktualisiere das Projekt in der Liste
        const index = projects.value.findIndex(p => p.id === projectId);
        if (index !== -1) {
          projects.value[index] = { ...projects.value[index], ...projectData };
          
          // Aktualisiere auch das aktuelle Projekt, wenn es das gleiche ist
          if (currentProject.value?.id === projectId) {
            currentProject.value = projects.value[index];
          }
        }
        return projects.value[index];
      } else {
        throw new Error(response.message || 'Fehler beim Aktualisieren des Projekts');
      }
    } catch (err) {
      console.error('Fehler beim Aktualisieren des Projekts:', err);
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  // Projekt löschen
  const deleteProject = async (projectId) => {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await vueFetch(`https://alex.polan.sk/backend/api/projects.php?id=${projectId}`, {
        method: 'DELETE'
      });

      if (response.status === 'success') {
        // Entferne das Projekt aus der Liste
        projects.value = projects.value.filter(p => p.id !== projectId);
        
        // Wenn das gelöschte Projekt das aktuelle war, setze currentProject zurück
        if (currentProject.value?.id === projectId) {
          currentProject.value = null;
          sessionStorage.removeItem('currentProjectId');
        }
        
        return true;
      } else {
        throw new Error(response.message || 'Fehler beim Löschen des Projekts');
      }
    } catch (err) {
      console.error('Fehler beim Löschen des Projekts:', err);
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  // Projekt aus Session Storage wiederherstellen (für Persistenz zwischen Seiten-Refreshes)
  const restoreCurrentProject = async () => {
    const storedProjectId = sessionStorage.getItem('currentProjectId');
    if (storedProjectId && (!currentProject.value || currentProject.value.id !== parseInt(storedProjectId))) {
      // Wenn Projekte noch nicht geladen sind, lade sie zuerst
      if (projects.value.length === 0) {
        await loadProjects();
      }
      
      // Finde das gespeicherte Projekt in der Liste
      const project = projects.value.find(p => p.id === parseInt(storedProjectId));
      if (project) {
        setCurrentProject(project);
      }
    }
  };

  return {
    projects,
    currentProject,
    isLoading,
    error,
    getProjects,
    getCurrentProject,
    getIsLoading,
    getError,
    loadProjects,
    createProject,
    setCurrentProject,
    updateProject,
    deleteProject,
    restoreCurrentProject
  };
});