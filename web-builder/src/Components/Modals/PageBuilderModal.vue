<script setup>
import Modal from '@/Components/Modals/Modal.vue';
import DynamicModal from '@/Components/Modals/DynamicModal.vue';
import PageBuilder from '@/composables/PageBuilder';
import { delay } from '@/composables/delay';

import {
  Dialog,
  DialogOverlay,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { computed, onMounted, ref } from 'vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useUserStore } from '@/stores/user';
import { useMediaLibraryStore } from '@/stores/media-library';
import { useProjectStore } from '@/stores/project';
const mediaLibraryStore = useMediaLibraryStore();
const userStore = useUserStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const projectStore = useProjectStore();

const currentProject = computed(() => {
  return projectStore.getCurrentProject;
});

defineProps({
  show: {
    type: Boolean,
    default: false,
    required: true,
  },
  updateOrCreate: {
    required: true,
  },
});

const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

const hideDraftButton = ref(true);

const showModalConfirmClosePageBuilder = ref(false);
//
// use dynamic model
const typeModal = ref('');
const gridColumnModal = ref(Number(1));
const titleModal = ref('');
const descriptionModal = ref('');
const firstButtonModal = ref('');
const secondButtonModal = ref(null);
const thirdButtonModal = ref(null);
// set dynamic modal handle functions
const firstModalButtonFunction = ref(null);
const secondModalButtonFunction = ref(null);
const thirdModalButtonFunction = ref(null);

const emit = defineEmits([
  'pageBuilderPrimaryHandler',
  'pageBuilderSecondaryHandler',
  'handleDraftForUpdate',
]);

const firstButton = function () {
  showModalConfirmClosePageBuilder.value = true;
  typeModal.value = 'danger';
  gridColumnModal.value = 3;
  titleModal.value = 'Close page builder without save?';
  descriptionModal.value =
    'Are you sure you want to close the page builder without saving? Any changes will be lost.';
  firstButtonModal.value = 'Close';
  secondButtonModal.value = null;
  thirdButtonModal.value = 'Exit Page Builder';

  // handle click
  firstModalButtonFunction.value = function () {
    showModalConfirmClosePageBuilder.value = false;
  };
  // handle click
  secondModalButtonFunction.value = function () {
    secondButton();
  };

  // handle click
  thirdModalButtonFunction.value = async function () {
    showModalConfirmClosePageBuilder.value = false;
    emit('pageBuilderPrimaryHandler');

    pageBuilder.removeHoveredAndSelected();
    userStore.setIsLoading(true);
    await delay();
    userStore.setIsLoading(false);
  };
  //
  // end modal
};

const handleEscapeKey = function () {
  firstButton();
};

// first button function
const secondButton = function () {
  emit('pageBuilderSecondaryHandler');
  pageBuilder.removeHoveredAndSelected();
};

const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success'); // 'success', 'error', usw.

// Neue Funktion, die speichert und veröffentlicht, ohne zu schließen
const saveOnly = async function() {
  try {
    // Erst lokal speichern
    await pageBuilder.saveComponentsToBackend();
    
    // Dann die Publish-URL anpingen
    //console.log(currentProject);
    const response = await fetch(`https://www.control-center.eu/publish.php?project_id=${currentProject.value.id}&css=true`);
    if (!response.ok) {
      console.error('Veröffentlichung fehlgeschlagen:', response.status);
      toastMessage.value = 'Fehler beim Veröffentlichen!';
      toastType.value = 'error';
    } else {
      console.log('Seite erfolgreich veröffentlicht');
      toastMessage.value = 'Erfolgreich gespeichert und veröffentlicht!';
      toastType.value = 'success';
    }
    
    // Toast anzeigen
    showToast.value = true;
    
    // Nach 3 Sekunden ausblenden
    setTimeout(() => {
      showToast.value = false;
    }, 3000);
  } catch (error) {
    console.error('Fehler beim Speichern oder Veröffentlichen:', error);
    toastMessage.value = 'Fehler beim Speichern!';
    toastType.value = 'error';
    showToast.value = true;
    setTimeout(() => {
      showToast.value = false;
    }, 3000);
  }
};

//
//
const getLocalStorageItemNameUpdate = computed(() => {
  return pageBuilderStateStore.getLocalStorageItemNameUpdate;
});
//
const handleDraftForUpdate = function () {
  hideDraftButton.value = false;
  emit('handleDraftForUpdate');
};

onMounted(() => {
  const item = localStorage.getItem(getLocalStorageItemNameUpdate.value);
  if (item) {
    const parsedValue = JSON.parse(item);

    if (Array.isArray(parsedValue) && parsedValue.length === 0) {
      hideDraftButton.value = false;
    }
  }
  if (!item) {
    hideDraftButton.value = false;
  }
});
</script>

<template>
  <teleport to="body">
    <TransitionRoot
      :show="show"
      as="template"
    >
      <Dialog
        @close="firstButton"
        @keydown.escape.prevent="handleEscapeKey"
        as="div"
        class="fixed z-30 inset-0 overflow-y-auto focus:outline-none"
        tabindex="0"
      >
        <div
          class="flex items-end justify-center pb-20 text-center sm:block sm:p-0 bg-white"
        >
          <TransitionChild
            as="template"
            enter="ease-out duration-100"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in duration-200"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <DialogOverlay
              class="fixed inset-0 bg-white bg-opacity-75 transition-opacity"
            />
          </TransitionChild>

          <!-- This element is to trick the browser into centering the modal contents. -->
          <span
            aria-hidden="true"
            class="hidden sm:inline-block sm:align-middle sm:h-screen"
            >&#8203;</span
          >
          <TransitionChild
            as="template"
            enter="ease-out duration-100"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              class="bg-red-100 inline-block align-bottom text-left transform transition-all sm:align-middle w-full overflow-hidden h-[100vh] top-0 left-0 right-0 absolute"
            >
              <DynamicModal
                :show="showModalConfirmClosePageBuilder"
                :type="typeModal"
                :gridColumnAmount="gridColumnModal"
                :title="titleModal"
                :description="descriptionModal"
                :firstButtonText="firstButtonModal"
                :secondButtonText="secondButtonModal"
                :thirdButtonText="thirdButtonModal"
                @firstModalButtonFunction="firstModalButtonFunction"
                @secondModalButtonFunction="secondModalButtonFunction"
                @thirdModalButtonFunction="thirdModalButtonFunction"
              >
                <header></header>
                <main></main>
              </DynamicModal>
              <div
                @click="pageBuilderStateStore.setComponent(null)"
                class="px-4 px-4 lg:h-[7vh] h-[16vh] flex items-center justify-between border-b border-gray-200 bg-white"
              >
                <div
                  class="flex items-center justify-start"
                ><!-- divide-x divide-gray-200-->
                  <button
                    type="button"
                    @click="firstButton"
                    class="pr-6"
                  ><!--border-r border-gray-200 -->
                    <img
                      class="h-6"
                      src="/logo/logo.png"
                      alt="Logo"
                    />
                  </button>
                </div>

                <button
                    class="myPrimaryButton btn-red lg:text-sm text-[10px] lg:py-2 py-2 min-h-2 ml-4"
                    @click="saveOnly"
                    type="button"
                  >
                    <span class="material-symbols-outlined text-[18px]">
                      save
                    </span>
                    Save & Publish
                  </button>
                  <button
                    v-if="updateOrCreate === 'update' && hideDraftButton"
                    class="mySecondaryButton lg:text-sm text-[10px] lg:py-2 py-2 min-h-2 ml-2"
                    @click="handleDraftForUpdate"
                    type="button"
                  >
                    <span class="material-symbols-outlined text-[18px]">
                      settings_backup_restore
                    </span>
                    Use Draft
                  </button>
               <!-- <button
                  type="button"
                  @click="firstButton"
                  class="flex items-center justify-center gap-1 cursor-pointer"
                >
                  <span class="myPrimaryParagraph font-medium">
                    Close Builder
                  </span>
                  <div
                    class="h-10 w-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 aspect-square hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
                  >
                    <span class="material-symbols-outlined"> close </span>
                  </div>
                </button>-->


              </div>
              <slot></slot>
              <div
                v-if="showToast"
                :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-xl shadow-lg toasty"
              >
                {{ toastMessage }}
              </div>
            </div>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
  </teleport>
</template>

<style scoped>
.btn-red {
  background-color: #ea0e2b;
}

/* Focus-Effekt entfernen, aber aktiven Zustand behalten */
button:focus {
  outline: none !important;
  box-shadow: none !important;
}

button:focus-visible {
  outline: none !important;
  box-shadow: none !important;
}

/* Aktiven Klick-Zustand verbessern */
.myPrimaryButton:active {
  transform: scale(0.98);
  opacity: 0.9;
  transition: transform 0.1s, opacity 0.1s;
}

.toasty {
  z-index: 9999;
}
</style>