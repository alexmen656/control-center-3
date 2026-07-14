<template>
  <ion-menu-toggle auto-hide="false">
    <ion-item lines="none" detail="false" class="sidebar-empty-item">
      <ion-icon slot="start" :name="icon" color="medium" />
      <ion-label color="medium">{{ message }}</ion-label>
    </ion-item>
  </ion-menu-toggle>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({
  name: "SidebarEmptyItem",
  props: {
    icon: {
      type: String,
      default: "folder-open-outline"
    },
    message: {
      type: String,
      required: true
    }
  },
});
</script>

<style scoped>
.sidebar-empty-item {
  opacity: 0.6;
}

.sidebar-empty-item ion-label {
  font-style: italic;
}

.sidebar-empty-item ion-icon {
  color: var(--ion-color-medium) !important;
}
</style>
