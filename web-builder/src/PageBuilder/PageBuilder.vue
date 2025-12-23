<script setup>
import { onMounted, computed, ref, watch, nextTick } from 'vue';
import PageBuilder from '@/composables/PageBuilder';
import PageBuilderPreviewModal from '@/Components/Modals/PageBuilderPreviewModal.vue';
import Preview from '@/PageBuilder/Preview.vue';
import ComponentTopMenu from '@/Components/PageBuilder/EditorMenu/Editables/ComponentTopMenu.vue';
import EditGetElement from '@/Components/PageBuilder/EditorMenu/Editables/EditGetElement.vue';
import SearchComponents from '@/Components/Search/SearchComponents.vue';
import OptionsDropdown from '@/Components/PageBuilder/DropdownsPlusToggles/OptionsDropdown.vue';
import PageSelectDropdown from '@/Components/PageBuilder/DropdownsPlusToggles/PageSelectDropdown.vue';
import RightSidebarEditor from '@/Components/PageBuilder/EditorMenu/RightSidebarEditor.vue';
import SlideOverRight from '@/Components/PageBuilder/Slidebars/SlideOverRight.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useMediaLibraryStore } from '@/stores/media-library';
import { useProjectStore } from '@/stores/project';
import PageBuilderSettings from '@/Components/PageBuilder/Settings/PageBuilderSettings.vue';

const showSettingsSlideOverRight = ref(false);
const titleSettingsSlideOverRight = ref(null);
const isLoadingComponents = ref(false); // Zustand für das Laden von Komponenten

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const projectStore = useProjectStore();
const emit = defineEmits(['previewCurrentDesign']);
const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

const getMenuRight = computed(() => {
  return pageBuilderStateStore.getMenuRight;
});

const currentProject = computed(() => {
  return projectStore.getCurrentProject;
});

const previewCurrentDesign = function () {
  pageBuilder.previewCurrentDesign();
};
const openPageBuilderPreviewModal = ref(false);
const firstPageBuilderPreviewModalButton = ref(null);

const handlePageBuilderPreview = function () {
  previewCurrentDesign();

  openPageBuilderPreviewModal.value = true;
  // handle click
  firstPageBuilderPreviewModalButton.value = function () {
    openPageBuilderPreviewModal.value = false;
  };
  // end modal
};

const showModalAddComponent = ref(false);
const titleModalAddComponent = ref('');
const firstButtonTextSearchComponents = ref('');
const firstModalButtonSearchComponentsFunction = ref(null);

const handleAddComponent = function () {
  pageBuilderStateStore.setComponent(null);

  //
  titleModalAddComponent.value = 'Add Components to Page';
  firstButtonTextSearchComponents.value = 'Close';
  showModalAddComponent.value = true;

  firstModalButtonSearchComponentsFunction.value = function () {
    // handle show modal for unique content
    showModalAddComponent.value = false;
  };

  // end modal
};

const getComponents = computed(() => {
  return pageBuilderStateStore.getComponents;
});
const getComponent = computed(() => {
  return pageBuilderStateStore.getComponent;
});

const getElement = computed(() => {
  return pageBuilderStateStore.getElement;
});

const getElementAttributes = computed(() => {
  if (!getElement.value || !(getElement.value instanceof HTMLElement)) {
    return new Object();
  }

  // Extract the attributes you want to watch
  const attributesToWatch = {
    src: getElement.value.getAttribute('src'),
    href: getElement.value.getAttribute('href'),
    style: getElement.value.getAttribute('style'),
    class: getElement.value.getAttribute('class'),
    dataImage: getElement.value.getAttribute('data-image'),
  };

  return attributesToWatch;
});

watch(getElementAttributes, (newAttributes, oldAttributes) => {
  // Check if any of the specified attributes have changed
  if (
    newAttributes?.src !== oldAttributes?.src ||
    newAttributes?.href !== oldAttributes?.href ||
    newAttributes?.style !== oldAttributes?.style ||
    newAttributes?.class !== oldAttributes?.class ||
    newAttributes?.dataImage !== oldAttributes?.dataImage
  ) {
    // Trigger your method when any of the specified attributes change
    pageBuilder.handlePageBuilderMethods();
    pageBuilder.setEventListenersForElements();
  }
});

const handleSelectComponent = function (componentObject) {
  pageBuilderStateStore.setComponent(componentObject);
};

// handle slideover window
const handleSettingsSlideOver = function () {
  pageBuilderStateStore.setComponent(null);

  titleSettingsSlideOverRight.value = 'Settings';
  showSettingsSlideOverRight.value = true;
};
// handle slideover window
const settingsSlideOverButton = function () {
  pageBuilderStateStore.setComponent(null);

  showSettingsSlideOverRight.value = false;
};

const draggableZone = ref(null);

// Beobachte Änderungen am aktuellen Projekt und aktualisiere die Seite entsprechend
watch(currentProject, async (newProject) => {
  if (newProject) {
    console.log('Aktuelles Projekt geändert:', newProject.name);
    // Setze Titel für den PageBuilder basierend auf dem Projektnamen
    document.title = `Page Builder - ${newProject.name}`;
    
    // Aktualisiere die Seiten basierend auf dem neuen Projekt
    if (newProject.pages && newProject.pages.length > 0) {
      pageBuilderStateStore.setPages(newProject.pages);
      pageBuilderStateStore.setCurrentPageId(newProject.pages[0].id);
    } else {
      // Wenn das Projekt keine Seiten hat, erstelle eine neue Standardseite
      const defaultPage = {
        id: Date.now().toString(),
        name: 'Homepage',
        components: []
      };
      pageBuilderStateStore.setPages([defaultPage]);
      pageBuilderStateStore.setCurrentPageId(defaultPage.id);
    }
  }
});

// Beobachte Änderungen an der aktuellen Seite und aktualisiere den PageBuilder entsprechend
const currentPageId = computed(() => pageBuilderStateStore.getCurrentPageId);
watch(currentPageId, async (newPageId) => {
  console.log('Aktuelle Seiten-ID geändert:', newPageId);
  // Markiere als Ladezustand
  isLoadingComponents.value = true;
  
  // Stelle sicher, dass der PageBuilder die richtige Seiten-ID verwendet
  pageBuilder.setCurrentPageId(newPageId);
  
  try {
    // Lade Komponenten für die neue Seite
    await pageBuilder.loadComponentsFromBackend();
    console.log('Komponenten geladen:', pageBuilderStateStore.getComponents.length);
  } catch (error) {
    console.error('Fehler beim Laden der Komponenten:', error);
  } finally {
    // Ladezustand zurücksetzen
    isLoadingComponents.value = false;
  }
});

onMounted(async () => {
  console.log('PageBuilder wird initialisiert...');
  
  // Prüfe, ob ein aktuelles Projekt ausgewählt ist
  const currentProj = projectStore.getCurrentProject;
  console.log('Aktuelles Projekt beim PageBuilder-Start:', currentProj?.name || 'Keins');
  
  if (currentProj) {
    // Wenn ein Projekt ausgewählt ist, lade dessen Seiten
    if (currentProj.pages && currentProj.pages.length > 0) {
      // Wenn Seiten vom Projekt vorhanden sind, verwende diese
      pageBuilderStateStore.setPages(currentProj.pages);
      pageBuilderStateStore.setCurrentPageId(currentProj.pages[0].id);
    } else {
      // Erstelle eine neue Standardseite
      const defaultPage = {
        id: Date.now().toString(),
        name: 'Homepage',
        components: []
      };
      pageBuilderStateStore.setPages([defaultPage]);
      pageBuilderStateStore.setCurrentPageId(defaultPage.id);
    }
  } else {
    // Wenn kein Projekt ausgewählt ist, lade Seiten aus Backend oder initialisiere neue Seiten
    const pagesLoaded = await pageBuilderStateStore.loadFromBackend();
    console.log('Seiten geladen:', pageBuilderStateStore.pages);
    console.log('Aktuelle Seiten-ID:', pageBuilderStateStore.currentPageId);
  }
  
  // Setze die aktuelle Seiten-ID explizit im PageBuilder
  pageBuilder.setCurrentPageId(pageBuilderStateStore.currentPageId);
  console.log('PageBuilder aktuelle Seiten-ID gesetzt auf:', pageBuilder.currentPageId.value);
  
  // Wenn keine Seiten aus Backend geladen wurden, initialisiere Komponenten für die Standard-Seite
  if (await pageBuilderStateStore.areComponentsStoredInLocalStorage()) {
    // Wenn es gespeicherte Komponenten gibt, lade sie in die aktuelle Seite
    await pageBuilderStateStore.saveCurrentPageComponents();
  }
  
  // Warten auf das vollständige Rendern der DOM-Elemente, bevor Event-Listener hinzugefügt werden
  await nextTick();
  
  // Event Listener für alle Elemente einrichten
  pageBuilder.setEventListenersForElements();
  
  // Zusätzliches Timing, um sicherzustellen, dass alle DOM-Elemente vollständig gerendert wurden
  setTimeout(() => {
    pageBuilder.setEventListenersForElements();
  }, 500);
});
</script>

<template>
    <SlideOverRight
    :open="showSettingsSlideOverRight"
    :title="titleSettingsSlideOverRight"
    @slideOverButton="settingsSlideOverButton"
  >
    <PageBuilderSettings> </PageBuilderSettings>
  </SlideOverRight>
  <SearchComponents v-if="showModalAddComponent" :show="showModalAddComponent"
    :firstButtonText="firstButtonTextSearchComponents" :title="titleModalAddComponent"
    @firstModalButtonSearchComponentsFunction="
      firstModalButtonSearchComponentsFunction
    "></SearchComponents>
  <PageBuilderPreviewModal :show="openPageBuilderPreviewModal"
    @firstPageBuilderPreviewModalButton="firstPageBuilderPreviewModalButton">
    <Preview></Preview>
  </PageBuilderPreviewModal>

  <div class="w-full inset-x-0 h-[93vh] z-10 bg-white overflow-x-scroll lg:pt-2 pt-2">
    <div class="relative h-full flex">
      <div @click.self="pageBuilderStateStore.setComponent(null)"
        class="min-w-[3.5rem] pt-6 pb-2 mx-2 rounded-2xl bg-myPrimaryLightGrayColor shadow"><!--rounded-full my-2-->
        <div class="mx-2 flex flex-col myPrimaryGap">
          <div class="flex gap-2 items-center justify-center">
            <button type="button" @click="
              () => {
                pageBuilderStateStore.setComponentArrayAddMethod('unshift');
                handleAddComponent();
              }
            "
              class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
              <span class="myMediumIcon material-symbols-outlined"> add </span>
            </button>
          </div>

          <div @click.self="pageBuilderStateStore.setComponent(null)">
            <ComponentTopMenu v-if="getElement"></ComponentTopMenu>
          </div>

          <div class="flex gap-2 items-center justify-center">
            <button type="button" @click="handleSettingsSlideOver"
              class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
              <span class="myMediumIcon material-symbols-outlined">settings</span>
            </button>
          </div>
        </div>
      </div>
      <main class="flex flex-col h-full grow rounded-2xl duration-300 shadow-2xl mr-2">
        <div class="flex items-center justify-between primary-gap rounded-t-2xl bg-myPrimaryLightGrayColor">
          <div @click.self="pageBuilderStateStore.setComponent(null)"
            class="w-4/12 flex justify-start items-center py-2 pl-2 h-full">
            <!-- Projekt-Info anzeigen -->
            <div v-if="currentProject" class="text-sm font-medium text-gray-700 flex items-center">
              <span class="material-symbols-outlined text-base mr-1">folder</span>
              {{ currentProject.name }}
            </div>
            <OptionsDropdown @previewCurrentDesign="previewCurrentDesign"></OptionsDropdown>
          </div>

          <div @click.self="pageBuilderStateStore.setComponent(null)" class="w-4/12 flex justify-center py-2">
            <PageSelectDropdown></PageSelectDropdown>
          </div>

          <div @click.self="pageBuilderStateStore.setComponent(null)" class="w-4/12 flex justify-end py-2 pr-2">
            <div class="flex items-center justify-center gap-4">
              <button type="button" @click="
                () => {
                  pageBuilderStateStore.setMenuRight(false);
                  pageBuilderStateStore.setElement(null);
                  pageBuilderStateStore.setComponent(null);
                  handlePageBuilderPreview();
                }
              "
                class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
                <span class="material-symbols-outlined"> visibility </span>
              </button>

              <button type="button" v-if="getMenuRight === false" @click="pageBuilderStateStore.setMenuRight(true)"
                class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0">
                <span class="material-symbols-outlined"> gesture </span>
              </button>
            </div>
          </div>
        </div>

        <EditGetElement></EditGetElement>
        <div @click="pageBuilderStateStore.setComponent(null)" id="contains-pagebuilder"
          class="pl-4 pr-4 pb-4 overflow-y-auto h-screen pt-1">
          <div id="pagebuilder">
            <div ref="draggableZone">
              <!-- Added Components to DOM # start -->
              <template v-if="Array.isArray(getComponents) && getComponents.length > 0">
                <div v-for="component in getComponents" :key="component.id" id="page-builder-editor-editable-area" class="bg-white grow">
                  <div @mouseup="handleSelectComponent(component)" class="relative group">
                    <div v-html="component.html_code"></div>
                  </div>
                </div>
              </template>
              <div v-else-if="isLoadingComponents" class="p-8 text-center">
                <div class="flex justify-center items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Komponenten werden geladen...</span>
                </div>
              </div>
              <div v-else class="p-8 text-center">
                <p class="text-sm text-gray-500">Keine Komponenten gefunden. Sie können neue Komponenten mit dem Plus-Button hinzufügen.</p>
              </div>
            </div>
            <!-- Added Components to DOM # end -->

            <!-- Add Component # start -->
            <div
              class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 my-12 mx-8">
              <h3 class="mt-2 text-sm font-medium text-gray-900">
                Add Components
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Get started by adding components using the drag & drop Page
                Builder.
              </p>
              <div class="mt-6 flex items-center gap-2 justify-center">
                <button @click="
                  () => {
                    pageBuilderStateStore.setComponentArrayAddMethod('push');
                    handleAddComponent();
                  }
                " type="button" class="myPrimaryButton flex items-center gap-2 justify-center">
                  <span class="myMediumIcon material-symbols-outlined">
                    add
                  </span>
                  Add component
                </button>
              </div>
            </div>
            <!-- Add Component # end -->
          </div>
        </div>
        <!-- Add Component # end -->
      </main>

      <aside aria-label="Menu" :class="{ 'w-0': !getMenuRight, 'w-80 ml-2': getMenuRight }"
        class="h-full duration-300 z-20 flex-shrink-0 overflow-hidden shadow-2xl rounded-l-2xl bg-white">
        <RightSidebarEditor @closeEditor="pageBuilderStateStore.setMenuRight(false)">
        </RightSidebarEditor>
      </aside>
    </div>
  </div>
</template>

<style>
#pagebuilder a {
  cursor: pointer; /* Zeigt an, dass es sich um einen klickbaren Link handelt */
}

#pagebuilder [element] {
  outline: rgba(255, 255, 255, 0) dashed 3px !important;
  outline-offset: -3px !important;
}

#pagebuilder [hovered] {
  outline: rgb(0, 140, 14, 1) dashed 3px !important;
  outline-offset: -3px !important;
}

#pagebuilder [selected] {
  position: relative;

  outline: rgb(185, 16, 16) dashed 3px !important;
  outline-offset: -3px !important;
}

/* sortable */

.sortable-ghost {
  display: flex;
  justify-content: center;
}

.sortable-ghost>* {
  width: 100%;
}

/*main {
  margin-right: .5rem;
}*/
</style>