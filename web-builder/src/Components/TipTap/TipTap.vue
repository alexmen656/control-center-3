<script setup>
import PageBuilder from '@/composables/PageBuilder';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useMediaLibraryStore } from '@/stores/media-library';
import { computed } from 'vue';

const mediaLibraryStore = useMediaLibraryStore();
const pageBuilderStateStore = usePageBuilderStateStore();
const pageBuilder = new PageBuilder(pageBuilderStateStore, mediaLibraryStore);

// Computed-Property für ausgewähltes Text-Element
const getSelectedTextElement = computed(() => {
  return pageBuilderStateStore.getSelectedTextElement;
});

// Funktion um festzustellen ob Text-Editor angezeigt werden soll
const shouldShowTextEditor = computed(() => {
  return pageBuilder.selectedElementIsValidText() || getSelectedTextElement.value;
});

// Funktion zum direkten Öffnen des Modals
const openTextEditor = () => {
  // Setze den Status im Store, um das Modal zu öffnen
  pageBuilderStateStore.setShowModalTipTap(true);
};
</script>

<template>
  <div>
    <div class="blockease-linear duration-200 block ease-linear">
      <template v-if="shouldShowTextEditor">
        <div
          class="border-t border-myPrimaryLightGrayColor flex flex-row justify-between items-center pl-3 pr-3 py-5 duration-200 hover:bg-myPrimaryLightGrayColor"
        >
          <div class="px-2 flex items-center justify-start gap-2">
            <button
              @click="openTextEditor"
              type="button"
              class="text-[12.5px] gap-2 text-nowrap pl-2 pr-3 w-full h-10 cursor-pointer rounded-full flex items-center border-none justify-center bg-gray-50 hover:bg-myPrimaryLinkColor hover:text-white focus-visible:ring-0"
            >
              <span class="material-symbols-outlined"> edit </span>
              <span>{{ getSelectedTextElement ? 'Text-Element bearbeiten' : 'Gesamten Text bearbeiten' }}</span>
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
