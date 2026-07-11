<template>
  <div class="global-search">
    <button class="search-trigger" @click="open" aria-label="Search">
      <ion-icon name="search-outline"></ion-icon>
      <span class="trigger-label">Search…</span>
      <span class="trigger-kbd">
        <span class="kbd">{{ metaKeyLabel }}</span><span class="kbd">K</span>
      </span>
    </button>
    <teleport to="body">
      <transition name="gs-fade">
        <div v-if="isOpen" class="gs-overlay" @click.self="close" @mousedown.self="close">
          <div class="gs-palette" role="dialog" aria-modal="true" aria-label="Global search">
            <div class="gs-input-row">
              <ion-icon name="search-outline" class="gs-input-icon"></ion-icon>
              <input ref="searchInput" v-model="term" class="gs-input" type="text"
                placeholder="Search projects and pages…" autocomplete="off" spellcheck="false"
                @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="selectCurrent"
                @keydown.esc.prevent="close" />
              <span class="gs-esc-hint">esc</span>
            </div>

            <div class="gs-results">
              <div v-if="loading" class="gs-empty">
                <ion-icon name="sync-outline" class="gs-spin"></ion-icon>
                <span>Loading…</span>
              </div>

              <template v-else>
                <div v-if="flatResults.length === 0" class="gs-empty">
                  <ion-icon name="search-outline"></ion-icon>
                  <span>No results for “{{ term }}”</span>
                </div>

                <div v-for="group in groupedResults" :key="group.key" class="gs-group">
                  <div class="gs-group-title">{{ group.title }}</div>
                  <button v-for="item in group.items" :key="item.uid" class="gs-item"
                    :class="{ active: item.index === activeIndex }" @click="go(item)"
                    @mousemove="activeIndex = item.index">
                    <ion-icon :name="item.icon" class="gs-item-icon"></ion-icon>
                    <span class="gs-item-label">{{ item.label }}</span>
                    <span v-if="item.hint" class="gs-item-hint">{{ item.hint }}</span>
                    <ion-icon name="return-down-back-outline" class="gs-item-enter"></ion-icon>
                  </button>
                </div>
              </template>
            </div>

            <div class="gs-footer">
              <span><span class="kbd">↑</span><span class="kbd">↓</span> to navigate</span>
              <span><span class="kbd">↵</span> to open</span>
              <span><span class="kbd">esc</span> to close</span>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script>
import { defineComponent } from "vue";

const STATIC_PAGES = [
  { label: "Projects", path: "/projects", icon: "grid-outline" },
  { label: "Users", path: "/users", icon: "people-outline" },
  { label: "Domains", path: "/domains", icon: "globe-outline" },
  { label: "Pages", path: "/pages/", icon: "document-text-outline" },
  { label: "Databases", path: "/databases", icon: "server-outline" },
  { label: "Bookmarks", path: "/manage/bookmarks", icon: "bookmark-outline" },
  { label: "Manage Projects", path: "/manage/projects", icon: "folder-outline" },
  { label: "Access Log", path: "/access-log", icon: "time-outline" },
  { label: "Messages", path: "/messages", icon: "chatbubbles-outline" },
  { label: "New Project", path: "/new/project/", icon: "add-circle-outline" },
  { label: "My Account", path: "/my-account", icon: "person-circle-outline" },
  { label: "Account Security", path: "/my-account/account-security", icon: "shield-checkmark-outline" },
  { label: "Preferences", path: "/my-account/preferences", icon: "settings-outline" },
];

export default defineComponent({
  name: "GlobalSearch",
  data() {
    return {
      isOpen: false,
      term: "",
      activeIndex: 0,
      projects: [],
      projectsLoaded: false,
      loading: false,
    };
  },
  computed: {
    metaKeyLabel() {
      return /Mac|iPhone|iPad/.test(navigator.platform) ? "⌘" : "Ctrl";
    },
    groupedResults() {
      const term = this.term.trim().toLowerCase();

      const pageMatches = STATIC_PAGES.filter(
        (p) => !term || p.label.toLowerCase().includes(term)
      ).map((p) => ({
        type: "page",
        label: p.label,
        icon: p.icon,
        path: p.path,
        hint: "Page",
      }));

      const projectMatches = this.projects
        .filter(
          (p) =>
            !term ||
            (p.name || "").toLowerCase().includes(term) ||
            (p.link || "").toLowerCase().includes(term)
        )
        .map((p) => ({
          type: "project",
          label: p.name || p.link,
          icon: p.icon || "cube-outline",
          path: "/project/" + p.link,
          hint: p.link,
        }));

      const groups = [];
      if (projectMatches.length) {
        groups.push({ key: "projects", title: "Projects", items: projectMatches });
      }
      if (pageMatches.length) {
        groups.push({ key: "pages", title: "Pages", items: pageMatches });
      }

      let idx = 0;
      for (const g of groups) {
        g.items = g.items.map((item) => ({
          ...item,
          index: idx,
          uid: item.type + ":" + item.path + ":" + idx++,
        }));
      }
      return groups;
    },
    flatResults() {
      return this.groupedResults.flatMap((g) => g.items);
    },
  },
  watch: {
    term() {
      this.activeIndex = 0;
    },
  },
  mounted() {
    window.addEventListener("keydown", this.onGlobalKey);
  },
  beforeUnmount() {
    window.removeEventListener("keydown", this.onGlobalKey);
  },
  methods: {
    onGlobalKey(e) {
      const isMeta = e.metaKey || e.ctrlKey;
      if (isMeta && (e.key === "k" || e.key === "K")) {
        e.preventDefault();
        this.isOpen ? this.close() : this.open();
      }
    },
    async open() {
      this.isOpen = true;
      this.term = "";
      this.activeIndex = 0;
      await this.$nextTick();
      if (this.$refs.searchInput) this.$refs.searchInput.focus();
      this.loadProjects();
    },
    close() {
      this.isOpen = false;
    },
    move(delta) {
      const count = this.flatResults.length;
      if (!count) return;
      this.activeIndex = (this.activeIndex + delta + count) % count;
    },
    selectCurrent() {
      const item = this.flatResults.find((i) => i.index === this.activeIndex);
      if (item) this.go(item);
    },
    go(item) {
      this.close();
      this.$router.push(item.path);
    },
    async loadProjects() {
      if (this.projectsLoaded) return;
      this.loading = this.projects.length === 0;
      try {
        const response = await this.$axios.get("v2/projects/");
        this.projects = Array.isArray(response.data) ? response.data : [];
        this.projectsLoaded = true;
      } catch (error) {
        console.error("Global search: error loading projects", error);
        this.projects = [];
      } finally {
        this.loading = false;
      }
    },
  },
});
</script>

<style scoped>
.search-trigger {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 10px 0 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.7);
  color: var(--ion-color-medium, #92949c);
  cursor: pointer;
  font-size: 14px;
  min-width: 220px;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.search-trigger:hover {
  border-color: var(--ion-color-primary, #f97316);
  background: #fff;
}

.search-trigger ion-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.trigger-label {
  flex: 1;
  text-align: left;
}

.trigger-kbd {
  display: flex;
  gap: 3px;
}

.kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border-radius: 5px;
  background: rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(0, 0, 0, 0.08);
  font-size: 11px;
  line-height: 1;
  color: var(--ion-color-medium, #92949c);
}

@media (prefers-color-scheme: dark) {
  .search-trigger {
    border-color: rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.06);
  }

  .search-trigger:hover {
    background: rgba(255, 255, 255, 0.1);
  }

  .kbd {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.12);
  }
}

.hasToBeDarkmode .search-trigger {
  border-color: rgba(255, 255, 255, 0.14);
  background: rgba(255, 255, 255, 0.06);
  color: var(--ion-color-medium, #92949c);
}

.hasToBeDarkmode .search-trigger:hover {
  background: rgba(255, 255, 255, 0.1);
}

.hasToBeDarkmode .kbd {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.12);
}

@media only screen and (max-width: 700px) {
  .search-trigger {
    min-width: 0;
    padding: 0 10px;
  }

  .trigger-label,
  .trigger-kbd {
    display: none;
  }
}

.gs-overlay {
  position: fixed;
  inset: 0;
  z-index: 100000;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 12vh 16px 16px;
}

.gs-palette {
  width: 100%;
  max-width: 600px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 60px -12px rgba(0, 0, 0, 0.4);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 70vh;
}

.gs-input-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
}

.gs-input-icon {
  font-size: 20px;
  color: #94a3b8;
  flex-shrink: 0;
}

.gs-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 16px;
  color: #1e293b;
}

.gs-input::placeholder {
  color: #94a3b8;
}

.gs-esc-hint {
  font-size: 11px;
  color: #94a3b8;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  padding: 2px 6px;
}

.gs-results {
  overflow-y: auto;
  padding: 8px;
}

.gs-group+.gs-group {
  margin-top: 4px;
}

.gs-group-title {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #94a3b8;
  padding: 8px 12px 4px;
}

.gs-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  text-align: left;
  color: #1e293b;
}

.gs-item.active {
  background: var(--ion-color-primary, #f97316);
  color: #fff;
}

.gs-item-icon {
  font-size: 18px;
  flex-shrink: 0;
  color: #64748b;
}

.gs-item.active .gs-item-icon {
  color: #fff;
}

.gs-item-label {
  flex: 1;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.gs-item-hint {
  font-size: 12px;
  color: #94a3b8;
  flex-shrink: 0;
}

.gs-item.active .gs-item-hint {
  color: rgba(255, 255, 255, 0.8);
}

.gs-item-enter {
  font-size: 16px;
  opacity: 0;
  flex-shrink: 0;
}

.gs-item.active .gs-item-enter {
  opacity: 1;
}

.gs-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 40px 16px;
  color: #94a3b8;
  font-size: 14px;
}

.gs-empty ion-icon {
  font-size: 28px;
}

.gs-spin {
  animation: gs-spin 1s linear infinite;
}

@keyframes gs-spin {
  to {
    transform: rotate(360deg);
  }
}

.gs-footer {
  display: flex;
  gap: 16px;
  padding: 10px 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 12px;
  color: #94a3b8;
}

.gs-footer .kbd {
  min-width: 16px;
  height: 16px;
  margin-right: 2px;
}

.gs-fade-enter-active,
.gs-fade-leave-active {
  transition: opacity 0.15s ease;
}

.gs-fade-enter-from,
.gs-fade-leave-to {
  opacity: 0;
}

@media (prefers-color-scheme: dark) {
  .gs-palette {
    background: #1e1e1e;
  }

  .gs-input-row,
  .gs-footer {
    border-color: rgba(255, 255, 255, 0.08);
  }

  .gs-input {
    color: #f1f5f9;
  }

  .gs-esc-hint {
    border-color: rgba(255, 255, 255, 0.12);
  }

  .gs-item {
    color: #f1f5f9;
  }

  .gs-item-icon {
    color: #94a3b8;
  }
}

@media only screen and (max-width: 700px) {
  .gs-overlay {
    padding: 8vh 10px 10px;
  }

  .gs-footer {
    display: none;
  }
}
</style>
