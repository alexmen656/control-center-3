<template>
  <ion-item :router-link="to" lines="none" detail="false" class="hydrated menu-item" :class="{
    selected: isSelected,
    collapsed: isCollapsed,
    hasToBeDarkmode: hasToBeDarkmode
  }" :data-tooltip="isCollapsed ? (tooltip || label) : ''">
    <ion-icon slot="start" :name="icon" />
    <ion-label v-if="!isCollapsed">{{ label }}</ion-label>
    <slot v-if="!isCollapsed" />
  </ion-item>
</template>

<script lang="ts">
import { defineComponent, computed } from "vue";
import { useRoute } from "vue-router";

const stripTrailingSlash = (path: string): string =>
  path.length > 1 && path.endsWith('/') ? path.slice(0, -1) : path;

export default defineComponent({
  name: "SidebarMenuItem",
  props: {
    to: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    label: {
      type: String,
      default: ""
    },
    tooltip: {
      type: String,
      default: ""
    },
    isCollapsed: {
      type: Boolean,
      default: false
    },
    hasToBeDarkmode: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const route = useRoute();
    const isSelected = computed(
      () => stripTrailingSlash(route.path) === stripTrailingSlash(props.to)
    );

    return { isSelected };
  },
});
</script>

<style scoped>
@media (prefers-color-scheme: light) {
  ion-item {
    --background: #eff3f6;
    background: #eff3f6;
  }
}

.menu-item.hasToBeDarkmode {
  --background: #1e1e1e !important;
}

.collapsed ion-item {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  width: 100% !important;
  max-width: 60px !important;
  --inner-padding-end: 0 !important;
  --inner-padding-start: 0 !important;
  --padding-start: 0 !important;
  --padding-end: 0 !important;
  --border-radius: 8px;
  margin: 1px 2px !important;
}

.collapsed ion-item:hover {
  --background: var(--ion-color-step-100);
}

.collapsed .menu-item {
  justify-content: center !important;
  --padding-start: 0 !important;
  --padding-end: 0 !important;
  --inner-padding-start: 0 !important;
  --inner-padding-end: 0 !important;
  --min-height: 48px;
  width: 100% !important;
  max-width: 60px !important;
  overflow: hidden !important;
  margin: 1px 0 !important;
}

.collapsed .menu-item ion-icon {
  margin: 0 !important;
  font-size: 28px !important;
  color: var(--ion-color-medium);
}

.collapsed .menu-item:hover ion-icon {
  color: var(--ion-color-primary) !important;
}

.collapsed .menu-item.selected {
  --background: var(--ion-color-primary-tint) !important;
}

.collapsed .menu-item.selected ion-icon {
  color: var(--ion-color-primary) !important;
}

.collapsed .menu-item:hover {
  position: relative;
  overflow: visible;
}

.collapsed .menu-item:hover::after {
  content: attr(data-tooltip);
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  background: var(--ion-color-dark, #222);
  color: var(--ion-color-light, #fff);
  padding: 8px 12px;
  border-radius: 6px;
  white-space: nowrap;
  z-index: 1001;
  margin-left: 12px;
  font-size: 14px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  opacity: 0;
  animation: fadeInTooltip 0.2s ease-in-out forwards;
}

@keyframes fadeInTooltip {
  from {
    opacity: 0;
    transform: translateY(-50%) translateX(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
  }
}
</style>
