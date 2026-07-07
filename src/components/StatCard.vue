<template>
  <div class="stat-card">
    <div class="stat-icon" :class="color">
      <ion-icon :name="icon"></ion-icon>
    </div>
    <div class="stat-content">
      <h3>{{ value }}</h3>
      <p>{{ label }}</p>
    </div>
  </div>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "StatCard",
  props: {
    icon: {
      type: String,
      required: true,
    },
    value: {
      type: [String, Number],
      default: "",
    },
    label: {
      type: String,
      default: "",
    },
    color: {
      type: String,
      default: "primary",
      validator: (value) =>
        ["primary", "success", "warning", "info", "danger", "accent"].includes(value),
    },
  },
});
</script>

<style scoped>
.stat-card {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: var(--radius-lg, 12px);
  padding: 24px;
  box-shadow: var(--shadow, 0 1px 3px 0 rgb(0 0 0 / 0.1));
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md, 0 4px 6px -1px rgb(0 0 0 / 0.1));
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg, 12px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  flex-shrink: 0;
}

.stat-icon.primary {
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color, #f97316);
}

.stat-icon.success {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color, #059669);
}

.stat-icon.warning {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning-color, #d97706);
}

.stat-icon.info {
  background: rgba(37, 99, 235, 0.1);
  color: var(--info-color, #2563eb);
}

.stat-icon.danger {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color, #dc2626);
}

.stat-icon.accent {
  background: rgba(124, 58, 237, 0.1);
  color: var(--accent-color, #7c3aed);
}

.stat-content h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary, #1e293b);
  font-size: 28px;
  font-weight: 700;
  line-height: 1;
}

.stat-content p {
  margin: 0;
  color: var(--text-secondary, #64748b);
  font-size: 14px;
  font-weight: 500;
}
</style>
