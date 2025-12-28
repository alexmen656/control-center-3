<script setup>
import { ref, computed, nextTick, onBeforeMount, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
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

const router = useRouter();
const route = useRoute();
const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const userStore = useUserStore();
const projectStore = useProjectStore();

const openPageBuilder = ref(false);

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

// Computed: Zeige Projektauswahl wenn kein Projekt geladen ist
const showProjectSelection = computed(() => {
  return isAuthenticated.value && !currentProject.value && route.name === 'home';
});

// Computed: Zeige PageBuilder wenn Projekt geladen ist
const showPageBuilder = computed(() => {
  return isAuthenticated.value && currentProject.value && route.name === 'project';
});

const pathPageBuilderStorageCreate = `page-builder-create-post`;
const pathPageBuilderStorageUpdate = `page-builder-update-post-id-1`;

const handlePageBuilder = async function () {
  userStore.setIsLoading(true);

  await nextTick();
  openPageBuilder.value = true;

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
    // Navigiere zurück zur Home-Seite (Projektauswahl)
    router.push({ name: 'home' });
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
    // Navigiere zurück zur Home-Seite (Projektauswahl)
    router.push({ name: 'home' });

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
  projectStore.setCurrentProject(null);
  router.push({ name: 'login' });
  userStore.setIsLoading(false);
};

// Projekt-Handler
const handleSelectProject = (project) => {
  // Projekt wurde ausgewählt - navigiere zur Projekt-Route
  projectStore.setCurrentProject(project);
  router.push({ name: 'project', params: { id: project.id } });
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
    
    // Navigiere zum neuen Projekt
    router.push({ name: 'project', params: { id: newProject.id } });
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
  userStore.setIsLoading(false);
  
  // Lasse den Router automatisch zur richtigen Route navigieren
  // basierend auf dem Authentifizierungsstatus und der aktuellen URL
});

// Watcher: Wenn ein Projekt geladen wird, öffne den PageBuilder
watch(
  () => [currentProject.value, route.name],
  ([project, routeName]) => {
    if (project && routeName === 'project') {
      openPageBuilder.value = true;
    } else {
      openPageBuilder.value = false;
    }
  },
  { immediate: true }
);

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

  <!-- Router View für die verschiedenen Routen -->
  <router-view v-slot="{ Component }">
    <!-- Login-Route -->
    <template v-if="route.name === 'login'">
      <LoginForm />
    </template>

    <!-- Home-Route (Projektauswahl) -->
    <template v-else-if="route.name === 'home' && isAuthenticated">
      <ProjectSelection 
        @selectProject="handleSelectProject"
        @createNewProject="handleCreateNewProject"
      />
    </template>

    <!-- Projekt-Route (PageBuilder) -->
    <template v-else-if="route.name === 'project' && isAuthenticated && currentProject">
      <PageBuilderModal
        :show="openPageBuilder"
        updateOrCreate="create"
        @pageBuilderPrimaryHandler="pageBuilderPrimaryHandler"
        @pageBuilderSecondaryHandler="pageBuilderSecondaryHandler"
        @handleDraftForUpdate="handleDraftForUpdate"
      >
        <PageBuilderView></PageBuilderView>
      </PageBuilderModal>

      <template v-if="!openPageBuilder">
        <Navbar @handleButton="handlePageBuilder" :user="currentUser" @logout="handleLogout"></Navbar>
        <HomeSection @handleButton="handlePageBuilder"></HomeSection>
        <Footer></Footer>
      </template>
    </template>
  </router-view>
</template>
