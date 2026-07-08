<template>
  <div class="loading-state">
    <ion-icon :name="icon" class="loading-icon"></ion-icon>
    <p>{{ message }}</p>
  </div>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "LoadingState",
  props: {
    message: {
      type: String,
      default: "Loading...",
    },
    icon: {
      type: String,
      default: "sync-outline",
    },
  },
});
</script>

<style scoped>
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
