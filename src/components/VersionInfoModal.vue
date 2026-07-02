<template>
  <ion-modal :is-open="isOpen" @didDismiss="$emit('close')" class="version-modal">
    <div class="vm-wrapper">
      <header class="vm-header">
        <button class="vm-close" @click="$emit('close')" aria-label="Close">
          <ion-icon name="close-outline"></ion-icon>
        </button>
        <div class="vm-brand">
          <div class="vm-logo">
            <ion-icon name="layers-outline"></ion-icon>
          </div>
          <div class="vm-brand-text">
            <h1>Fringelo</h1>
            <div class="vm-version-badge">
              <span class="vm-dot"></span>
              v{{ appVersion }}
            </div>
          </div>
        </div>
        <p class="vm-tagline">{{ current.title }}</p>
      </header>
      <div class="vm-body">
        <section class="vm-release vm-release--current">
          <div class="vm-release-head">
            <div class="vm-release-meta">
              <span class="vm-release-version">v{{ current.version }}</span>
              <span class="vm-chip">Latest</span>
            </div>
            <span class="vm-release-date">{{ formatDate(current.date) }}</span>
          </div>
          <p class="vm-summary">{{ current.summary }}</p>
          <ul class="vm-changes">
            <li v-for="(change, i) in current.changes" :key="i" class="vm-change">
              <span class="vm-tag" :class="'vm-tag--' + change.type">
                <ion-icon :name="iconFor(change.type)"></ion-icon>
                {{ labelFor(change.type) }}
              </span>
              <span class="vm-change-text">{{ change.text }}</span>
            </li>
          </ul>
        </section>
        <template v-if="previous.length">
          <h3 class="vm-section-title">Previous releases</h3>
          <details v-for="rel in previous" :key="rel.version" class="vm-release vm-release--past">
            <summary class="vm-release-head">
              <div class="vm-release-meta">
                <span class="vm-release-version">v{{ rel.version }}</span>
                <span class="vm-release-subtitle">{{ rel.title }}</span>
              </div>
              <div class="vm-release-right">
                <span class="vm-release-date">{{ formatDate(rel.date) }}</span>
                <ion-icon class="vm-chevron" name="chevron-down-outline"></ion-icon>
              </div>
            </summary>
            <ul class="vm-changes">
              <li v-for="(change, i) in rel.changes" :key="i" class="vm-change">
                <span class="vm-tag" :class="'vm-tag--' + change.type">
                  <ion-icon :name="iconFor(change.type)"></ion-icon>
                  {{ labelFor(change.type) }}
                </span>
                <span class="vm-change-text">{{ change.text }}</span>
              </li>
            </ul>
          </details>
        </template>
      </div>
      <footer class="vm-footer">
        <span class="vm-copyright">© {{ year }} Fringelo</span>
        <button class="vm-done" @click="$emit('close')">Done</button>
      </footer>
    </div>
  </ion-modal>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import {
  releases,
  currentRelease,
  type ReleaseChangeType,
} from "@/data/releaseNotes";

const TYPE_META: Record<ReleaseChangeType, { label: string; icon: string }> = {
  feature: { label: "New", icon: "sparkles-outline" },
  improvement: { label: "Improved", icon: "trending-up-outline" },
  fix: { label: "Fixed", icon: "bug-outline" },
  removed: { label: "Removed", icon: "trash-outline" },
};

export default defineComponent({
  name: "VersionInfoModal",
  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["close"],
  data() {
    return {
      appVersion: import.meta.env.VITE_APP_VERSION ?? currentRelease.version,
      current: currentRelease,
      previous: releases.slice(1),
      year: new Date().getFullYear(),
    };
  },
  methods: {
    labelFor(type: ReleaseChangeType) {
      return TYPE_META[type].label;
    },
    iconFor(type: ReleaseChangeType) {
      return TYPE_META[type].icon;
    },
    formatDate(iso: string) {
      const d = new Date(iso);
      if (isNaN(d.getTime())) return iso;
      return d.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
  },
});
</script>

<style scoped>
.version-modal {
  --width: 92%;
  --max-width: 520px;
  --height: auto;
  --max-height: 86vh;
  --border-radius: 18px;
  --box-shadow: 0 24px 64px rgba(0, 0, 0, 0.28);
  --backdrop-opacity: 0.5;
}

.vm-wrapper {
  display: flex;
  flex-direction: column;
  max-height: 86vh;
  background: var(--ion-background-color, #fff);
  font-family: var(--ion-font-family, inherit);
}

.vm-header {
  position: relative;
  padding: 28px 26px 24px;
  background: linear-gradient(135deg,
      var(--ion-color-primary, #3880ff) 0%,
      var(--ion-color-primary-shade, #3171e0) 100%);
  color: #fff;
  overflow: hidden;
}

.vm-header::after {
  content: "";
  position: absolute;
  top: -60px;
  right: -40px;
  width: 180px;
  height: 180px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 50%;
}

.vm-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  cursor: pointer;
  transition: background 0.18s ease;
  z-index: 2;
}

.vm-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

.vm-close ion-icon {
  font-size: 20px;
}

.vm-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 1;
}

.vm-logo {
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
}

.vm-logo ion-icon {
  font-size: 28px;
}

.vm-brand-text h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.vm-version-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 5px;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.vm-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3);
}

.vm-tagline {
  position: relative;
  z-index: 1;
  margin: 16px 0 0;
  font-size: 14px;
  font-weight: 500;
  opacity: 0.92;
}

.vm-body {
  padding: 22px 26px 8px;
  overflow-y: auto;
  flex: 1;
}

.vm-release {
  margin-bottom: 18px;
}

.vm-release-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.vm-release-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.vm-release-version {
  font-size: 16px;
  font-weight: 700;
  color: var(--ion-text-color, #1a1a1a);
}

.vm-chip {
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ion-color-primary, #3880ff);
  background: rgba(var(--ion-color-primary-rgb, 56, 128, 255), 0.12);
}

.vm-release-date {
  font-size: 12px;
  color: var(--ion-color-medium, #92949c);
  white-space: nowrap;
  font-weight: 500;
}

.vm-summary {
  margin: 12px 0 16px;
  font-size: 14px;
  line-height: 1.55;
  color: var(--ion-color-medium-shade, #6a6d73);
}

.vm-changes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.vm-change {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.vm-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  padding: 3px 9px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.4;
  min-width: 82px;
  justify-content: center;
}

.vm-tag ion-icon {
  font-size: 13px;
}

.vm-tag--feature {
  color: #1d8a4f;
  background: rgba(34, 197, 94, 0.13);
}

.vm-tag--improvement {
  color: #2563c9;
  background: rgba(56, 128, 255, 0.13);
}

.vm-tag--fix {
  color: #c2410c;
  background: rgba(249, 115, 22, 0.13);
}

.vm-tag--removed {
  color: #b91c4b;
  background: rgba(244, 63, 94, 0.13);
}

.vm-change-text {
  font-size: 13.5px;
  line-height: 1.5;
  color: var(--ion-text-color, #2a2a2a);
  padding-top: 1px;
}

.vm-section-title {
  margin: 8px 0 14px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: var(--ion-color-medium, #92949c);
}

.vm-release--past {
  border: 1px solid var(--ion-color-step-150, #e3e4e6);
  border-radius: 12px;
  padding: 14px 16px;
  margin-bottom: 12px;
  background: var(--ion-color-step-50, #f9fafb);
}

.vm-release--past summary {
  cursor: pointer;
  list-style: none;
}

.vm-release--past summary::-webkit-details-marker {
  display: none;
}

.vm-release-subtitle {
  font-size: 13px;
  font-weight: 500;
  color: var(--ion-color-medium-shade, #6a6d73);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.vm-release-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vm-chevron {
  font-size: 16px;
  color: var(--ion-color-medium, #92949c);
  transition: transform 0.2s ease;
}

.vm-release--past[open] .vm-chevron {
  transform: rotate(180deg);
}

.vm-release--past .vm-changes {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid var(--ion-color-step-150, #e3e4e6);
}

.vm-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 26px;
  border-top: 1px solid var(--ion-color-step-150, #e3e4e6);
  background: var(--ion-background-color, #fff);
}

.vm-copyright {
  font-size: 12px;
  color: var(--ion-color-medium, #92949c);
}

.vm-done {
  padding: 9px 22px;
  border: none;
  border-radius: 10px;
  background: var(--ion-color-primary, #3880ff);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s ease, transform 0.1s ease;
}

.vm-done:hover {
  background: var(--ion-color-primary-shade, #3171e0);
}

.vm-done:active {
  transform: scale(0.97);
}

@media (prefers-color-scheme: dark) {
  .vm-release--past {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
  }

  .vm-release--past .vm-changes {
    border-top-color: rgba(255, 255, 255, 0.08);
  }
}
</style>
