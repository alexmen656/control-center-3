<template>
  <h1 class="page-title">
    <ion-icon :name="icon" class="page-title-icon"></ion-icon>
    <span>{{ title }}</span>
  </h1>
</template>

<script>
export default {
  name: "PageTitle",
  props: {
    title: {
      type: String,
      default: "",
    },
    icon: {
      type: String,
      default: "",
    },
  },
};
</script>

<style scoped>
.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.2;
  color: var(--text-primary, #1e293b);
}

.page-title-icon {
  font-size: 34px;
  color: #f97316;
  flex-shrink: 0;
}

@media (prefers-color-scheme: dark) {
  .page-title {
    color: var(--text-primary, #ffffff);
  }
}
</style>
