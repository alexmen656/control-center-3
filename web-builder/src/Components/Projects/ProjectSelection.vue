<template>
  <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Projekt auswählen</h1>
        <p class="text-gray-600">Wählen Sie ein Projekt zum Bearbeiten aus oder erstellen Sie ein neues Projekt.</p>
      </div>
      
      <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Projekte Liste -->
        <div v-if="!isLoading && projects.length > 0" class="divide-y divide-gray-200">
          <div v-for="project in projects" :key="project.id" class="p-6 hover:bg-gray-50 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ project.name }}</h2>
                <p class="text-gray-500 text-sm mt-1">Zuletzt bearbeitet: {{ formatDate(project.updated_at) }}</p>
                <p class="text-gray-600 text-sm mt-2" v-if="project.description">{{ project.description }}</p>
              </div>
              <div>
                <button 
                  @click="selectProject(project)"
                  class="myPrimaryButton inline-flex items-center px-4 py-2"
                >
                  <span class="material-symbols-outlined mr-2">edit</span>
                  Bearbeiten
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Leerer Zustand -->
        <div v-else-if="!isLoading && projects.length === 0" class="p-12 text-center">
          <div class="inline-block p-4 rounded-full bg-gray-100 mb-4">
            <span class="material-symbols-outlined text-4xl text-gray-500">folder_open</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Projekte vorhanden</h3>
          <p class="text-gray-500 mb-6">Erstellen Sie Ihr erstes Projekt, um mit dem Page Builder zu beginnen.</p>
        </div>
        
        <!-- Ladezustand -->
        <div v-else class="p-12 text-center">
          <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Projekte werden geladen...</span>
          </div>
        </div>
      </div>
      
      <!-- Neues Projekt Button -->
      <div class="mt-6 text-center">
        <button 
          @click="createNewProject"
          class="inline-flex items-center px-5 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <span class="material-symbols-outlined mr-2">add</span>
          Neues Projekt erstellen
        </button>
      </div>
      
      <!-- Neues Projekt Dialog -->
      <div v-if="showNewProjectModal" class="fixed inset-0 overflow-y-auto z-50">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showNewProjectModal = false"></div>
          
          <div class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <div class="bg-white p-6">
              <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Neues Projekt erstellen</h3>
              
              <div class="space-y-4">
                <div>
                  <label for="projectName" class="block text-sm font-medium text-gray-700">Projektname</label>
                  <input 
                    type="text" 
                    id="projectName" 
                    v-model="newProject.name" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="z.B. Meine Website"
                    @keyup.enter="handleCreateProject"
                  />
                </div>
                <div>
                  <label for="projectDescription" class="block text-sm font-medium text-gray-700">Beschreibung (optional)</label>
                  <textarea 
                    id="projectDescription" 
                    v-model="newProject.description" 
                    rows="3" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Kurze Beschreibung des Projekts"
                  ></textarea>
                </div>
              </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button 
                type="button" 
                @click="handleCreateProject" 
                :disabled="!newProject.name" 
                class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                Erstellen
              </button>
              <button 
                type="button" 
                @click="showNewProjectModal = false" 
                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm"
              >
                Abbrechen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useProjectStore } from '@/stores/project';
import { useUserStore } from '@/stores/user';

const projectStore = useProjectStore();
const userStore = useUserStore();

const isLoading = ref(true);
const projects = ref([]);
const showNewProjectModal = ref(false);
const newProject = ref({
  name: '',
  description: ''
});

const emit = defineEmits(['selectProject', 'createNewProject']);

// Projekte laden
onMounted(async () => {
  isLoading.value = true;
  try {
    await projectStore.loadProjects();
    projects.value = projectStore.getProjects;
  } catch (error) {
    console.error('Fehler beim Laden der Projekte:', error);
  } finally {
    isLoading.value = false;
  }
});

// Projekt auswählen
const selectProject = (project) => {
  projectStore.setCurrentProject(project);
  emit('selectProject', project);
};

// Neues Projekt Modal anzeigen
const createNewProject = () => {
  newProject.value = {
    name: '',
    description: ''
  };
  showNewProjectModal.value = true;
};

// Neues Projekt erstellen
const handleCreateProject = async () => {
  if (!newProject.value.name) return;
  
  try {
    const project = await projectStore.createProject({
      name: newProject.value.name,
      description: newProject.value.description
    });
    
    showNewProjectModal.value = false;
    projectStore.setCurrentProject(project);
    emit('selectProject', project);
  } catch (error) {
    console.error('Fehler beim Erstellen des Projekts:', error);
  }
};

// Datum formatieren
const formatDate = (dateString) => {
  if (!dateString) return 'Unbekannt';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};
</script>