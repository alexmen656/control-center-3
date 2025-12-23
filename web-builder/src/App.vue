<script setup>
import { ref, computed, nextTick, onBeforeMount, onMounted, watch } from 'vue';
import PageBuilderModal from '@/Components/Modals/PageBuilderModal.vue';
import HomeSection from '@/Components/Homepage/HomeSection.vue';
import Footer from '@/Components/Homepage/Footer.vue';
import Navbar from '@/Components/Homepage/Navbar.vue';
import PageBuilderView from '@/PageBuilder/PageBuilder.vue';
import PageBuilder from '@/composables/PageBuilder';
import FullScreenSpinner from '@/Components/Loaders/FullScreenSpinner.vue';
import LoginForm from '@/Components/Auth/LoginForm.vue';
import ProjectSelection from '@/Components/Projects/ProjectSelection.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useUserStore } from '@/stores/user';
import { useMediaLibraryStore } from '@/stores/media-library';
import { useProjectStore } from '@/stores/project';

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const userStore = useUserStore();
const projectStore = useProjectStore();

const openPageBuilder = ref(false);
const showProjectSelection = ref(false);

const pageBuilderPrimaryHandler = ref(null);
const pageBuilderSecondaryHandler = ref(null);
const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);
const formType = ref('create');

const getIsLoading = computed(() => {
  return userStore.getIsLoading;
});

const isAuthenticated = computed(() => {
  return userStore.getIsAuthenticated;
});

const currentUser = computed(() => {
  return userStore.getCurrentUser;
});

const currentProject = computed(() => {
  return projectStore.getCurrentProject;
});

const pathPageBuilderStorageCreate = `page-builder-create-post`;
const pathPageBuilderStorageUpdate = `page-builder-update-post-id-1`;

const handlePageBuilder = async function () {
  userStore.setIsLoading(true);

  await nextTick();
  openPageBuilder.value = true;
  showProjectSelection.value = false;

  if (formType.value === 'create') {
    pageBuilderStateStore.setComponents([]);
    pageBuilder.areComponentsStoredInLocalStorage();
  }

  // handle click
  pageBuilderPrimaryHandler.value = async function () {
    userStore.setIsLoading(true);

    if (formType.value === 'update') {
      await nextTick();
      pageBuilder.saveComponentsLocalStorageUpdate();
    }

    openPageBuilder.value = false;
    showProjectSelection.value = true;
    userStore.setIsLoading(false);
  };

  // handle click
  pageBuilderSecondaryHandler.value = async function () {
    userStore.setIsLoading(true);

    // save to local storage if new resource
    if (formType.value === 'create') {
      await nextTick();
      pageBuilder.saveComponentsLocalStorage();
      await nextTick();
    }
    // save to local storage if update
    if (formType.value === 'update') {
      await nextTick();
      pageBuilder.synchronizeDOMAndComponents();
      await nextTick();
    }

    openPageBuilder.value = false;
    showProjectSelection.value = true;

    userStore.setIsLoading(false);
  };

  userStore.setIsLoading(false);

  // end modal
};

// Builder # End
const handleDraftForUpdate = async function () {
  userStore.setIsLoading(true);

  if (formType.value === 'update') {
    await nextTick();
    pageBuilder.areComponentsStoredInLocalStorageUpdate();
    await nextTick();
    pageBuilder.setEventListenersForElements();

    userStore.setIsLoading(false);
  }
};

const handleLogout = async () => {
  userStore.setIsLoading(true);
  await userStore.logout();
  openPageBuilder.value = false;
  showProjectSelection.value = false;
  userStore.setIsLoading(false);
};

// Projekt-Handler
const handleSelectProject = (project) => {
  // Projekt wurde ausgewählt - starte PageBuilder mit diesem Projekt
  projectStore.setCurrentProject(project);
  
  // Starte den PageBuilder
  handlePageBuilder();
};

const handleCreateNewProject = async () => {
  userStore.setIsLoading(true);
  
  try {
    // Erstelle ein neues Projekt mit Standardwerten
    const newProject = await projectStore.createProject({
      name: `Neues Projekt ${new Date().toLocaleDateString()}`,
      description: 'Ein neues Webseiten-Projekt',
      pages: []
    });
    
    // Setze das neue Projekt als aktuelles Projekt
    projectStore.setCurrentProject(newProject);
    
    // Starte den PageBuilder mit dem neuen Projekt
    handlePageBuilder();
  } catch (error) {
    console.error('Fehler beim Erstellen eines neuen Projekts:', error);
  } finally {
    userStore.setIsLoading(false);
  }
};

// Überprüfen des Authentifizierungsstatus beim Start der App
onMounted(async () => {
  userStore.setIsLoading(true);
  await userStore.fetchCurrentUser();
  
  // Wenn der Benutzer angemeldet ist, zeigen wir die Projektauswahlseite an
  if (userStore.getIsAuthenticated) {
    showProjectSelection.value = true;
    
    // Versuche, ein zuvor ausgewähltes Projekt wiederherzustellen
    await projectStore.restoreCurrentProject();
    
    // Wenn ein aktuelles Projekt existiert und kein Projekt aus der Session wiederhergestellt wurde,
    // starten wir den PageBuilder direkt mit diesem Projekt
    if (projectStore.getCurrentProject) {
      openPageBuilder.value = true;
      showProjectSelection.value = false;
    }
  }
  
  userStore.setIsLoading(false);
});

// Watcher für Authentifizierungsstatus - zeige Projektauswahl nach Login
watch(isAuthenticated, (newValue) => {
  if (newValue === true) {
    // Wenn der Benutzer eingeloggt ist, zeige die Projektauswahl
    showProjectSelection.value = true;
  } else {
    showProjectSelection.value = false;
    openPageBuilder.value = false;
  }
});

onBeforeMount(() => {
  // Define local storage key name before on mount
  pageBuilderStateStore.setLocalStorageItemName(pathPageBuilderStorageCreate);

  // Define local storage key name before on mount
  pageBuilderStateStore.setLocalStorageItemNameUpdate(
    pathPageBuilderStorageUpdate
  );
});
</script>

<template>
  <teleport to="body">
    <FullScreenSpinner v-if="getIsLoading"></FullScreenSpinner>
  </teleport>

  <!-- Login-Formular anzeigen, wenn der Benutzer nicht authentifiziert ist -->
  <LoginForm v-if="!isAuthenticated" />

  <!-- Projektauswahl anzeigen, wenn der Benutzer authentifiziert ist aber kein PageBuilder geöffnet ist -->
  <ProjectSelection 
    v-if="isAuthenticated && showProjectSelection && !openPageBuilder" 
    @selectProject="handleSelectProject"
    @createNewProject="handleCreateNewProject"
  />

  <!-- Hauptanwendung anzeigen, wenn der PageBuilder geöffnet ist -->
  <template v-if="isAuthenticated">
    <PageBuilderModal
      :show="openPageBuilder"
      updateOrCreate="create"
      @pageBuilderPrimaryHandler="pageBuilderPrimaryHandler"
      @pageBuilderSecondaryHandler="pageBuilderSecondaryHandler"
      @handleDraftForUpdate="handleDraftForUpdate"
    >
      <PageBuilderView></PageBuilderView>
    </PageBuilderModal>

    <template v-if="!openPageBuilder && !showProjectSelection">
      <Navbar @handleButton="handlePageBuilder" :user="currentUser" @logout="handleLogout"></Navbar>
      <HomeSection @handleButton="handlePageBuilder"></HomeSection>
      <Footer></Footer>
    </template>
  </template>
</template>
