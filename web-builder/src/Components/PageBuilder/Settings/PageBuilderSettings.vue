<script setup>
import SlideOverRightParent from '@/Components/PageBuilder/Slidebars/SlideOverRightParent.vue';
import AdvancedPageBuilderSettings from '@/Components/PageBuilder/Settings/AdvancedPageBuilderSettings.vue';
import { ref } from 'vue';

const showAdvancedSettingsSlideOverRight = ref(false);
const titleSettingsSlideOverRight = ref('');

const handleAdvancedSettingsSlideOver = function () {
  titleSettingsSlideOverRight.value = 'Advanced Page Builder Settings';
  showAdvancedSettingsSlideOverRight.value = true;
};

const settingsAdvancedSlideOverButton = function () {
  showAdvancedSettingsSlideOverRight.value = false;
};
</script>

<template>
  <SlideOverRightParent :open="showAdvancedSettingsSlideOverRight" :title="titleSettingsSlideOverRight"
    @slideOverButton="settingsAdvancedSlideOverButton">
    <AdvancedPageBuilderSettings></AdvancedPageBuilderSettings>
  </SlideOverRightParent>
  <div class="mt-4 mb-4 py-8 border-b border-myPrimbryLightGrayColor">
    <div class="flex items-left flex-col gap-1">
      <h3 class="myQuaternaryHeader">Advanced Settings</h3>
      <p class="myPrimaryParagraph text-xs">
        Manage advanced settings here. Like an overview of Selected Element,
        Component, and Components in real-time.
      </p>
    </div>
    <div class="mt-4">
      <button @click="handleAdvancedSettingsSlideOver" type="button" class="myPrimaryButton text-xs">
        Advanced Settings
      </button>
    </div>
  </div>
</template>
