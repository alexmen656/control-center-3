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

  pageBuilderPrimaryHandler.value = async function () {
    userStore.setIsLoading(true);

    if (formType.value === 'update') {
      await nextTick();
      pageBuilder.saveComponentsLocalStorageUpdate();
    }

    openPageBuilder.value = false;
    router.push({ name: 'home' });
    userStore.setIsLoading(false);
  };

  pageBuilderSecondaryHandler.value = async function () {
    userStore.setIsLoading(true);

    if (formType.value === 'create') {
      await nextTick();
      pageBuilder.saveComponentsLocalStorage();
      await nextTick();
    }

    if (formType.value === 'update') {
      await nextTick();
      pageBuilder.synchronizeDOMAndComponents();
      await nextTick();
    }

    openPageBuilder.value = false;
    router.push({ name: 'home' });
    userStore.setIsLoading(false);
  };

  userStore.setIsLoading(false);
};

const handleLogout = async () => {
  userStore.setIsLoading(true);
  await userStore.logout();
  openPageBuilder.value = false;
  projectStore.setCurrentProject(null);
  router.push({ name: 'login' });
  userStore.setIsLoading(false);
};

const handleSelectProject = (project) => {
  projectStore.setCurrentProject(project);
  router.push({ name: 'project', params: { id: project.id } });
};

onMounted(async () => {
  userStore.setIsLoading(true);
  await userStore.fetchCurrentUser();
  userStore.setIsLoading(false);
});

watch(
  () => [currentProject.value, route.name],
  ([project, routeName]) => {
    if (project && routeName === 'project') {
      openPageBuilder.value = true;
      pageBuilderStateStore.setProjectId(project.id);
    } else {
      openPageBuilder.value = false;
    }
  },
  { immediate: true }
);

onBeforeMount(() => {
  pageBuilderStateStore.setLocalStorageItemName(pathPageBuilderStorageCreate);
  pageBuilderStateStore.setLocalStorageItemNameUpdate(
    pathPageBuilderStorageUpdate
  );
});
</script>

<template>
  <teleport to="body">
    <FullScreenSpinner v-if="getIsLoading"></FullScreenSpinner>
  </teleport>
  <router-view v-slot="{ Component }">
    <template v-if="route.name === 'login'">
      <LoginForm />
    </template>
    <template v-else-if="route.name === 'home' && isAuthenticated">
      <ProjectSelection @selectProject="handleSelectProject" />
    </template>
    <template v-else-if="route.name === 'project' && isAuthenticated && currentProject">
      <PageBuilderModal :show="openPageBuilder" updateOrCreate="create"
        @pageBuilderPrimaryHandler="pageBuilderPrimaryHandler"
        @pageBuilderSecondaryHandler="pageBuilderSecondaryHandler">
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
