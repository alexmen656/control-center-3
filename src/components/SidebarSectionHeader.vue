<template>
  <ion-note class="projects-headline">
    <h4>{{ title }}</h4>
    <div>
      <router-link v-for="(action, idx) in actions" :key="idx" :to="action.to">
        <ion-icon style="color: var(--ion-color-medium-shade)" :name="action.icon" />
      </router-link>
    </div>
  </ion-note>
</template>

<script lang="ts">
import { defineComponent, PropType } from "vue";

interface SectionAction {
  to: string;
  icon: string;
}

export default defineComponent({
  name: "SidebarSectionHeader",
  props: {
    title: {
      type: String,
      required: true
    },
    actions: {
      type: Array as PropType<SectionAction[]>,
      default: () => []
    }
  },
});
</script>

<style scoped>
.projects-headline {
  display: flex;
  margin: 0 !important;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
}

.projects-headline>h4 {
  margin: 0;
  padding: 0;
}

.projects-headline>div {
  display: flex;
}

.projects-headline>div>a {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
