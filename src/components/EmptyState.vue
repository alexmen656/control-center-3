<template>
  <div class="no-data-state">
    <div class="no-data-content">
      <ion-icon :name="icon" class="no-data-icon"></ion-icon>
      <h4>{{ title }}</h4>
      <p v-if="description">{{ description }}</p>
      <slot></slot>
    </div>
  </div>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "EmptyState",
  props: {
    icon: {
      type: String,
      default: "file-tray-outline",
    },
    title: {
      type: String,
      default: "No Data Found",
    },
    description: {
      type: String,
      default: "",
    },
  },
});
</script>

<style scoped>
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}
</style>
