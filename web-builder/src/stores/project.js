import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import vueFetch from '@/composables/vueFetch';

export const useProjectStore = defineStore('project', () => {
  const projects = ref([]);
  const currentProject = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const getProjects = computed(() => projects.value);
  const getCurrentProject = computed(() => currentProject.value);
  const getIsLoading = computed(() => isLoading.value);
  const getError = computed(() => error.value);

  const loadProjects = async () => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await vueFetch('https://alex.polan.sk/control-center/web-builder/projects.php', {
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

  const setCurrentProject = (project) => {
    currentProject.value = project;
    if (project) {
      sessionStorage.setItem('currentProjectId', project.id);
    } else {
      sessionStorage.removeItem('currentProjectId');
    }
  };

  const updateProject = async (projectId, projectData) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await vueFetch(`https://alex.polan.sk/control-center/web-builder/projects.php?id=${projectId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(projectData)
      });

      if (response.status === 'success') {
        const index = projects.value.findIndex(p => p.id === projectId);
        if (index !== -1) {
          projects.value[index] = { ...projects.value[index], ...projectData };

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

  const deleteProject = async (projectId) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await vueFetch(`https://alex.polan.sk/control-center/web-builder/projects.php?id=${projectId}`, {
        method: 'DELETE'
      });

      if (response.status === 'success') {
        projects.value = projects.value.filter(p => p.id !== projectId);

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

  const restoreCurrentProject = async () => {
    const storedProjectId = sessionStorage.getItem('currentProjectId');
    if (storedProjectId && (!currentProject.value || currentProject.value.id !== parseInt(storedProjectId))) {
      if (projects.value.length === 0) {
        await loadProjects();
      }

      const project = projects.value.find(p => p.id === parseInt(storedProjectId));

      if (project) {
        setCurrentProject(project);
      }
    }
  };

  const loadProjectById = async (projectId) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await vueFetch(`https://alex.polan.sk/control-center/web-builder/projects.php?id=${projectId}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.error) {
        throw new Error(response.message || 'Projekt nicht gefunden');
      }

      const project = response;
      project.id = parseInt(project.id);

      const index = projects.value.findIndex(p => parseInt(p.id) === project.id);
      if (index !== -1) {
        projects.value[index] = project;
      } else {
        projects.value.push(project);
      }

      setCurrentProject(project);
      return project;
    } catch (err) {
      console.error('Fehler beim Laden des Projekts:', err);
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
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
    setCurrentProject,
    updateProject,
    deleteProject,
    restoreCurrentProject,
    loadProjectById
  };
});