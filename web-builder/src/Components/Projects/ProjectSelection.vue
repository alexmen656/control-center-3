<template>
  <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Projekt auswählen</h1>
        <p class="text-gray-600">Wählen Sie ein Projekt zum Bearbeiten aus oder erstellen Sie ein neues Projekt.</p>
      </div>
      <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div v-if="!isLoading && projects.length > 0" class="divide-y divide-gray-200">
          <div v-for="project in projects" :key="project.id"
            class="p-6 hover:bg-gray-50 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ project.name }}</h2>
                <p class="text-gray-500 text-sm mt-1">Zuletzt bearbeitet: {{ formatDate(project.updated_at) }}</p>
                <p class="text-gray-600 text-sm mt-2" v-if="project.description">{{ project.description }}</p>
              </div>
              <div>
                <button @click="selectProject(project)" class="myPrimaryButton inline-flex items-center px-4 py-2">
                  <span class="material-symbols-outlined mr-2">edit</span>
                  Bearbeiten
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="!isLoading && projects.length === 0" class="p-12 text-center">
          <div class="inline-block p-4 rounded-full bg-gray-100 mb-4">
            <span class="material-symbols-outlined text-4xl text-gray-500">folder_open</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Projekte vorhanden</h3>
          <p class="text-gray-500 mb-6">Erstellen Sie Ihr erstes Projekt, um mit dem Page Builder zu beginnen.</p>
        </div>
        <div v-else class="p-12 text-center">
          <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
              viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
              </path>
            </svg>
            <span>Projekte werden geladen...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useProjectStore } from '@/stores/project';

const projectStore = useProjectStore();
const isLoading = ref(true);
const projects = ref([]);

const emit = defineEmits(['selectProject']);

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

const selectProject = (project) => {
  projectStore.setCurrentProject(project);
  emit('selectProject', project);
};

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