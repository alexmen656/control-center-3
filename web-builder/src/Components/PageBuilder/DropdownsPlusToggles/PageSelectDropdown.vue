<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import DynamicModal from '@/Components/Modals/DynamicModal.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';

const pageBuilderStateStore = usePageBuilderStateStore();
const router = useRouter();
const route = useRoute();

// Hole die Seiten und die aktuelle Seiten-ID aus dem Store
const pages = computed(() => pageBuilderStateStore.pages);
const currentPageId = computed(() => pageBuilderStateStore.currentPageId);

// Current page
const currentPage = computed(() => 
  pages.value.find(page => page.id === currentPageId.value)
);

// Modals für verschiedene Aktionen
const showAddPageModal = ref(false);
const showRenamePageModal = ref(false);
const showDeletePageModal = ref(false);

// Modaldaten
const typeModal = ref('');
const gridColumnModal = ref(Number(1));
const titleModal = ref('');
const descriptionModal = ref('');
const firstButtonModal = ref('');
const secondButtonModal = ref(null);
const thirdButtonModal = ref(null);

// Modal-Funktionen
const firstModalButtonFunction = ref(null);
const secondModalButtonFunction = ref(null);
const thirdModalButtonFunction = ref(null);

// Daten für die Seite
const newPageName = ref('');
const newPageSlug = ref('');
const pageIdToEdit = ref(null);

// Dynamic Page Logic
const isDynamicPage = ref(false);
const dynamicPrefix = ref('');
const dynamicParam = ref(':id');

watch([isDynamicPage, dynamicPrefix, dynamicParam], () => {
  if (isDynamicPage.value) {
    // Sanitize prefix
    const prefix = dynamicPrefix.value.trim().toLowerCase().replace(/[^a-z0-9-_]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    newPageSlug.value = prefix ? `${prefix}/${dynamicParam.value}` : dynamicParam.value;
  }
});

// Wechsel zu einer anderen Seite
const switchPage = (pageId) => {
  if (pageId !== currentPageId.value) {
    if (route.name === 'project') {
      router.push({
        name: 'project',
        params: {
          id: route.params.id,
          pageId: pageId
        }
      });
    } else {
      // Fallback falls nicht in projekt route
      pageBuilderStateStore.switchToPage(pageId);
    }
  }
};

// Öffne das Modal zum Hinzufügen einer neuen Seite
const handleAddPage = () => {
  showAddPageModal.value = true;
  newPageName.value = '';
  newPageSlug.value = '';
  isDynamicPage.value = false;
  dynamicPrefix.value = '';
  dynamicParam.value = ':id';
  
  typeModal.value = 'success';
  gridColumnModal.value = 2;
  titleModal.value = 'Neue Seite hinzufügen';
  descriptionModal.value = 'Geben Sie einen Namen für die neue Seite ein:';
  firstButtonModal.value = 'Abbrechen';
  secondButtonModal.value = null;
  thirdButtonModal.value = 'Hinzufügen';

  firstModalButtonFunction.value = function() {
    showAddPageModal.value = false;
  };
  
  thirdModalButtonFunction.value = async function() {
    if (newPageName.value.trim() !== '') {
      const newPageId = await pageBuilderStateStore.addPage(newPageName.value, newPageSlug.value);
      
      if (newPageId) {
        if (route.name === 'project') {
          router.push({
            name: 'project',
            params: {
              id: route.params.id,
              pageId: newPageId
            }
          });
        } else {
          pageBuilderStateStore.switchToPage(newPageId);
        }
        
        showAddPageModal.value = false;
      }
    }
  };
};

// Öffne das Modal zum Umbenennen einer Seite
const handleRenamePage = (pageId, pageName) => {
  showRenamePageModal.value = true;
  newPageName.value = pageName;
  pageIdToEdit.value = pageId;
  
  typeModal.value = 'success';
  gridColumnModal.value = 2;
  titleModal.value = 'Seite umbenennen';
  descriptionModal.value = 'Geben Sie einen neuen Namen für die Seite ein:';
  firstButtonModal.value = 'Abbrechen';
  secondButtonModal.value = null;
  thirdButtonModal.value = 'Umbenennen';

  firstModalButtonFunction.value = function() {
    showRenamePageModal.value = false;
  };
  
  thirdModalButtonFunction.value = function() {
    if (newPageName.value.trim() !== '') {
      pageBuilderStateStore.updatePageName(pageIdToEdit.value, newPageName.value);
      showRenamePageModal.value = false;
    }
  };
};

// Öffne das Modal zum Löschen einer Seite
const handleDeletePage = (pageId, pageName) => {
  showDeletePageModal.value = true;
  pageIdToEdit.value = pageId;
  
  typeModal.value = 'delete';
  gridColumnModal.value = 2;
  titleModal.value = 'Seite löschen';
  descriptionModal.value = `Sind Sie sicher, dass Sie die Seite "${pageName}" löschen möchten? Dies kann nicht rückgängig gemacht werden.`;
  firstButtonModal.value = 'Abbrechen';
  secondButtonModal.value = null;
  thirdButtonModal.value = 'Löschen';

  firstModalButtonFunction.value = function() {
    showDeletePageModal.value = false;
  };
  
  thirdModalButtonFunction.value = function() {
    pageBuilderStateStore.deletePage(pageIdToEdit.value);
    showDeletePageModal.value = false;
  };
};
</script>

<template>
  <!-- Modals für Seitenoperationen -->
  <DynamicModal
    :show="showAddPageModal"
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
    <main>
      <div class="myInputGroup">
        <div class="myPrimaryFormOrganizationHeaderDescriptionSection">
          <div class="myPrimaryFormOrganizationHeader">
            <label
              for="pagename"
              class="myPrimaryInputLabel"
            >Seitenname:</label>
            <input
              v-model="newPageName"
              type="text"
              class="myPrimaryInput"
              name="pagename"
              placeholder="z.B. Startseite, Über uns, Kontakt..."
            />
          </div>
        </div>
      </div>
      <div class="myInputGroup" style="margin-top: 15px;">
        <div class="myPrimaryFormOrganizationHeaderDescriptionSection">
           <label class="flex items-center space-x-2 cursor-pointer mb-2">
            <input type="checkbox" v-model="isDynamicPage" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300">
            <span class="text-sm font-medium text-gray-700">Dynamische Seite / Detailansicht</span>
          </label>

          <div v-if="isDynamicPage" class="pl-4 border-l-2 border-gray-200 mt-2 space-y-3">
             <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">URL Präfix (Tabelle/Bereich)</label>
                <input
                  v-model="dynamicPrefix"
                  type="text"
                  class="myPrimaryInput"
                  placeholder="z.B. products"
                />
             </div>
             <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Identifikator</label>
                <select v-model="dynamicParam" class="myPrimaryInput">
                  <option value=":id">ID (:id)</option>
                  <option value=":slug">Slug (:slug)</option>
                </select>
             </div>
             <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded">
                Vorschau: <span class="font-mono font-bold">{{ newPageSlug }}</span>
             </div>
          </div>

          <div v-else>
            <div class="myPrimaryFormOrganizationHeader">
              <label
                for="pageslug"
                class="myPrimaryInputLabel"
              >Pfad / Slug (Optional):</label>
              <input
                v-model="newPageSlug"
                type="text"
                class="myPrimaryInput"
                name="pageslug"
                placeholder="Automatisch generiert aus Name"
              />
            </div>
          </div>
        </div>
      </div>
    </main>
  </DynamicModal>
  
  <DynamicModal
    :show="showRenamePageModal"
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
    <main>
      <div class="myInputGroup">
        <div class="myPrimaryFormOrganizationHeaderDescriptionSection">
          <div class="myPrimaryFormOrganizationHeader">
            <label
              for="pagename"
              class="myPrimaryInputLabel"
            >Seitenname:</label>
            <input
              v-model="newPageName"
              type="text"
              class="myPrimaryInput"
              name="pagename"
            />
          </div>
        </div>
      </div>
    </main>
  </DynamicModal>
  
  <DynamicModal
    :show="showDeletePageModal"
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
  
  <!-- Dropdown für Seitenauswahl -->
  <Menu as="div" class="myPrimaryParagraph relative inline-block text-left">
    <div>
      <MenuButton
        class="inline-flex items-center gap-2 justify-center w-full rounded-md border border-gray-300 shadow-sm pl-4 pr-6 py-2 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-600"
      >
        <span class="material-symbols-outlined text-[16px]">description</span>
        <span class="text-sm">{{ currentPage?.name || 'Seite wählen' }}</span>
      </MenuButton>
    </div>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems
        class="z-50 origin-top-right absolute right-0 mt-2 w-60 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none"
      >
        <!-- Liste der vorhandenen Seiten -->
        <div class="py-1 overflow-y-auto max-h-60">
          <MenuItem v-for="page in pages" :key="page.id" v-slot="{ active }">
            <div class="flex items-center justify-between px-4 py-2 cursor-pointer"
                 :class="[active ? 'bg-myPrimaryLightGrayColor text-gray-900' : 'text-gray-700']">
              <!-- Seiten-Name (anklickbar zum Wechseln) -->
              <div @click="switchPage(page.id)" 
                   class="flex-1 text-sm hover:text-indigo-600"
                   :class="{'font-semibold text-indigo-600': page.id === currentPageId}">
                {{ page.name }}
                <span v-if="page.slug && page.slug.includes(':')" class="ml-1 text-xs text-gray-400 font-mono">({{ page.slug }})</span>
              </div>
              
              <!-- Aktionen (nur anzeigen, wenn die Seite aktiv oder hervorgehoben ist) -->
              <div class="flex space-x-1" v-if="active || page.id === currentPageId">
                <!-- Umbenennen-Button -->
                <button @click.stop="handleRenamePage(page.id, page.name)" 
                        class="p-1 text-gray-500 hover:text-gray-700 focus:outline-none">
                  <span class="material-symbols-outlined text-[14px]">edit</span>
                </button>
                
                <!-- Löschen-Button (deaktiviert, wenn es nur eine Seite gibt) -->
                <button @click.stop="handleDeletePage(page.id, page.name)"
                        :disabled="pages.length <= 1"
                        :class="{'opacity-50 cursor-not-allowed': pages.length <= 1}"
                        class="p-1 text-gray-500 hover:text-red-600 focus:outline-none">
                  <span class="material-symbols-outlined text-[14px]">delete</span>
                </button>
              </div>
            </div>
          </MenuItem>
        </div>
        
        <!-- Trennlinie und "Neue Seite hinzufügen"-Button -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <div @click="handleAddPage" 
                 class="cursor-pointer flex items-center px-4 py-2 text-sm"
                 :class="[active ? 'bg-myPrimaryLightGrayColor text-gray-900' : 'text-gray-700']">
              <span class="material-symbols-outlined text-[16px] mr-2">add</span>
              Neue Seite hinzufügen
            </div>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>