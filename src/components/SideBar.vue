<template>
  <div class="sidebar-shell" :class="{ collapsed: isCollapsed }">
  <div class="sidebar-shell__scroll">
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed }">
    <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
      <ion-item button @click="this.selectedIndex = i" lines="none" detail="false"
        :router-link="'/' + p.name.toLowerCase().replaceAll(' ', '-') + '/'" class="hydrated menu-item"
        :class="{ selected: this.selectedIndex === i, collapsed: isCollapsed }"
        :data-tooltip="isCollapsed ? (p.name[0].toUpperCase() + p.name.substring(1)) : ''">
        <ion-icon slot="start" :name="p.icon"></ion-icon>
        <ion-label v-if="!isCollapsed">{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label>
      </ion-item>
    </ion-menu-toggle>
    <ion-menu-toggle auto-hide="false" v-for="(p, i) in dev_tools" :key="i">
      <ion-item button @click="this.selectedIndex = i + tools.length" lines="none" detail="false"
        :router-link="'/' + p.name.toLowerCase().replaceAll(' ', '-') + '/'" class="hydrated menu-item"
        :class="{ selected: this.selectedIndex === i + tools.length, collapsed: isCollapsed }"
        :data-tooltip="isCollapsed ? (p.name[0].toUpperCase() + p.name.substring(1)) : ''">
        <ion-icon slot="start" :name="p.icon"></ion-icon>
        <ion-label v-if="!isCollapsed">{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label>
      </ion-item>
    </ion-menu-toggle>
  </ion-list>
  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }">
    <h4 v-if="!isCollapsed">Bookmarks</h4>
    <div v-if="!isCollapsed">
      <router-link to="/manage/bookmarks/"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link>
    </div>
  </ion-note>
  <ion-list v-if="bookmarks.length > 0" :class="{ collapsed: isCollapsed }">
    <ion-menu-toggle auto-hide="false" v-for="(p, i) in bookmarks" :key="i">
      <ion-item button @click="this.selectedIndex = i + 2 + tools.length + dev_tools.length; goToBookmark(p.location)"
        lines="none" detail="false" class="hydrated menu-item"
        :class="{ collapsed: isCollapsed, selected: this.selectedIndex === i + 2 + tools.length + dev_tools.length }"
        :data-tooltip="isCollapsed ? (p.title[0].toUpperCase() + p.title.substring(1)) : ''"
        v-if="p.title">
        <ion-icon slot="start" :name="p.icon ? p.icon : 'help-circle-outline'"></ion-icon>
        <ion-label v-if="!isCollapsed">{{ p.title[0].toUpperCase() }}{{ p.title.substring(1) }}</ion-label>
      </ion-item>
    </ion-menu-toggle>
  </ion-list>

  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }">
    <h4 v-if="!isCollapsed">Projects</h4>
    <div v-if="!isCollapsed">
      <router-link to="/manage/projects/"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/new/project/"><ion-icon
          style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list :class="{ collapsed: isCollapsed }">
    <ion-menu-toggle auto-hide="false" v-for="(p, i) in projects" :key="i">
      <ion-item button lines="none" detail="false" @click="goToProject(p.link)" class="hydrated menu-item"
        :class="{ collapsed: isCollapsed }"
        :data-tooltip="isCollapsed ? (p.name[0].toUpperCase() + p.name.substring(1)) : ''">
        <ion-icon slot="start" :name="p.icon ? p.icon : 'folder-outline'"></ion-icon>
        <ion-label v-if="!isCollapsed">{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label>
      </ion-item>
    </ion-menu-toggle>
  </ion-list>
  <div v-if="!isCollapsed" class="version-footer" @click="isVersionModalOpen = true">
    <ion-icon name="information-circle-outline"></ion-icon>
    <span>v{{ version }}</span>
  </div>
  </div>
  <footer class="sidebar-footer" :class="{ collapsed: isCollapsed }">
    <button type="button" class="footer-btn footer-toggle" @click="toggleSidebar"
      :data-tooltip="isCollapsed ? 'Expand menu' : ''"
      :aria-label="isCollapsed ? 'Expand menu' : 'Collapse menu'">
      <svg v-if="isCollapsed" class="sidebar-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
        <path d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2H6a2 2 0 0 1 -2 -2z" stroke-width="2"></path>
        <path d="M15 4v16" stroke-width="2"></path>
        <path d="m9 10 2 2 -2 2" stroke-width="2"></path>
      </svg>
      <svg v-else class="sidebar-toggle-icon" viewBox="-0.5 -0.5 16 16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
        <path d="M12.7769375 14.284625H2.2230625c-0.8326875 0 -1.5076875 -0.675 -1.5076875 -1.5076875l0 -10.553875c0 -0.8326875 0.675 -1.5076875 1.5076875 -1.5076875h10.553875c0.8326875 0 1.5076875 0.675 1.5076875 1.5076875v10.553875c0 0.8326875 -0.675 1.5076875 -1.5076875 1.5076875Z" stroke-width="1"></path>
        <path d="M3.9192500000000003 5.9923125 2.6 7.5l1.3192499999999998 1.5076875" stroke-width="1"></path>
        <path d="M5.615375 14.284625V0.7153750000000001" stroke-width="1"></path>
      </svg>
    </button>
    <button type="button" id="main-sidebar-notif-trigger" class="footer-btn footer-notif"
      :data-tooltip="isCollapsed ? 'Notifications' : ''" aria-label="Notifications">
      <ion-icon name="notifications-outline" />
      <span v-if="!isCollapsed">Notifications</span>
      <span v-if="unreadCount > 0" class="notif-dot">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
    </button>
  </footer>
  <ion-popover trigger="main-sidebar-notif-trigger" side="top" alignment="end" show-backdrop="false"
    @didPresent="markAllRead">
    <ion-content class="notif-popover">
      <div class="notif-header">
        <h4>Notifications</h4>
      </div>
      <div v-if="notifications.length === 0" class="notif-empty">
        <ion-icon name="notifications-off-outline" />
        <span>You're all caught up</span>
      </div>
      <ul v-else class="notif-list">
        <li v-for="n in notifications" :key="n.id" class="notif-item" :class="{ unread: n.read_status == 0 }">
          <strong>{{ n.title }}</strong>
          <p>{{ n.message }}</p>
        </li>
      </ul>
    </ion-content>
  </ion-popover>
  </div>

  <VersionInfoModal :is-open="isVersionModalOpen" @close="isVersionModalOpen = false" />
</template>

<script>
import { defineComponent, ref } from "vue";
import VersionInfoModal from "@/components/VersionInfoModal.vue";

export default defineComponent({
  name: "SideBar",
  components: {
    VersionInfoModal,
  },
  props: {
    bookmarks: Array,
    projects: Array,
    isCollapsed: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isVersionModalOpen: false,
      version: import.meta.env.VITE_APP_VERSION ?? "0.0.0",
      tools: [
        { icon: "apps-outline", name: "Projects" },
        { icon: "rocket-outline", name: "Deployments" },
        { icon: "people-outline", name: "Users" },
      ],
      dev_tools: [
        { icon: "key-outline", name: "Access Log" },
        { icon: "server-outline", name: "Databases" },
        { icon: "cloud-outline", name: "Pages" },
        { icon: "globe-outline", name: "Domains" },
      ],
    };
  },
  methods: {
    toggleSidebar() {
      this.$emit('sidebarToggled', !this.isCollapsed);
    },
    goToProject(name) {
      this.$router.push(
        "/project/" +
        (name[0].toLowerCase() + name.substring(1))
          .replaceAll(` `, `-`)
          .replaceAll(`'`, ``)
          .toLowerCase()
      );
    },
    goToBookmark(link) {
      this.$router.push(link);
    },
  },

  setup() {
    const selectedIndex = ref(0);
    const notifications = ref([]);
    const unreadCount = ref(0);
    const markAllRead = () => {
      unreadCount.value = 0;
    };
    return {
      selectedIndex,
      notifications,
      unreadCount,
      markAllRead,
    };
  },
});
</script>

<style scoped>
@media (prefers-color-scheme: light) {

  ion-item,
  ion-list,
  ion-reorder-group {
    --background: #eff3f6;
    /*#f7fcff;*/
    background: #eff3f6;
  }
}

ion-menu.md ion-list[data-v-7ba5bd90] {
  padding-top: 48px;
}

.btn-red {
  --background: var(--ion-color-primary) !important;
}

.ion-menu {
  width: 300px;
}

ion-menu ion-content {
  --background: var(--ion-item-background, var(--ion-background-color, #fff));
}

/*ion-menu.md ion-content {
  --padding-start: 8px;
  --padding-end: 8px;
  --padding-top: 20px;
  --padding-bottom: 20px;
}*/

ion-menu.md ion-list {
  padding: 20px 0;
}

ion-list {
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
  margin-bottom: 1rem;
}

ion-menu.md ion-note {
  margin-bottom: 30px;
}

ion-menu.md ion-list-header,
ion-menu.md ion-note {
  padding-left: 10px;
}

ion-menu.md ion-list#inbox-list {
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
}

ion-menu.md ion-list#inbox-list ion-list-header {
  font-size: 22px;
  font-weight: 600;

  min-height: 20px;
}

ion-menu.md ion-list#labels-list ion-list-header {
  font-size: 16px;

  margin-bottom: 18px;

  color: #757575;

  min-height: 26px;
}

ion-menu.md ion-item {
  --padding-start: 10px;
  --padding-end: 10px;
  border-radius: 4px;
}

ion-menu.md ion-item.selected {
  --background: rgba(var(--ion-color-primary-rgb), 0.14);
}

ion-menu.md ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.md ion-item ion-icon {
  color: #616e7e;
}

ion-menu.md ion-item ion-label {
  font-weight: 500;
}

ion-menu.ios ion-content {
  --padding-bottom: 20px;
}

ion-menu.ios ion-list {
  padding: 20px 0 0 0;
}

ion-menu.ios ion-note {
  line-height: 24px;
  margin-bottom: 20px;
}

ion-menu.ios ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 50px;
}

ion-menu.ios ion-item.selected ion-icon {
  color: var(--ion-color-primary);
}

ion-menu.ios ion-item ion-icon {
  font-size: 24px;
  color: #73849a;
}

ion-menu.ios ion-list#labels-list ion-list-header {
  margin-bottom: 8px;
}

ion-menu.ios ion-list-header,
ion-menu.ios ion-note {
  padding-left: 16px;
  padding-right: 16px;
}

ion-menu.ios ion-note {
  margin-bottom: 8px;
}

ion-note {
  display: inline-block;
  font-size: 16px;

  color: var(--ion-color-medium-shade);
}

ion-item.selected {
  --color: var(--ion-color-primary);
}

a {
  text-decoration: none;
  color: var(--ion-color-primary);
}

/*ion-header,
ion-toolbar,*/
.header {
  --background: #000;
}

.mobile-only {
  display: none;
}

.desktop-only {
  display: block;
}

@media only screen and (max-width: 600px) {
  .only-web {
    display: none;
  }

  .mobile-only {
    display: block;
  }

  .desktop-only {
    display: none;
  }
}

router-link,
a {
  color: var(--ion-color-primary);
}

ion-menu-button {
  color: var(--ion-color-primary) !important;
}

a {
  color: var(--ion-color-primary) !important;
}

.link-container {
  display: flex;
  justify-content: center;
}

ion-footer ion-toolbar {
  color: #000;
}

/*ion-title {
  color: white;
}*/
.link {
  text-decoration: none;
}

/*ion-menu.md ion-item.selected {
  --background: rgba(255, 0, 0, 0.14) !important;
}*/

ion-item.selected {
  --color: var(--ion-color-primary) !important;
}

ion-menu ion-item.selected ion-icon {
  color: var(--ion-color-primary) !important;
}

/*ion-item:focus {
  --background: var(--ion-color-primary);
}*/

.list-md.articles {
  background: var(--ion-background-color);
}

.projects-headline {
  display: flex;
  margin: 0 !important;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
}

.projects-headline>h4 {
  margin: 0;
  /*margin-top: 0.35rem !important;*/
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

.menu-item {
  cursor: default;
}

/* Sidebar Toggle Button - REMOVED */

/* Collapsed Sidebar Styles */
.collapsed.projects-headline {
  display: none;
}

/* Section Dividers */
.collapsed ion-list {
  padding: 0 !important;
  margin: 0 !important;
  width: 100% !important;
  max-width: 60px !important;
  border-bottom: 1px solid var(--ion-color-step-200);
  margin-bottom: 8px !important;
  padding-bottom: 8px !important;
}

.collapsed ion-list:last-child {
  border-bottom: none;
  margin-bottom: 0 !important;
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

.collapsed {
  text-align: center;
  width: 100% !important;
  max-width: 60px !important;
  overflow: hidden !important;
}

/* Ensure icons are centered in collapsed state */
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

/* Force collapse the menu content */
.ion-menu.collapsed-menu ion-content {
  width: 76px !important;
  max-width: 76px !important;
  overflow: hidden !important;
  --padding-start: 0 !important;
  --padding-end: 0 !important;
}

.ion-menu.collapsed-menu ion-list {
  width: 60px !important;
  max-width: 60px !important;
  padding: 20px 0 !important;
}

/* Hide version and other text elements when collapsed */
.collapsed+div {
  display: none;
}

/* Add tooltip-like behavior on hover in collapsed state */
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

.inner-scroll {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.version-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 10px;
  margin-top: 16px;
  border-top: 1px solid var(--ion-color-step-150, #d7d8da);
  font-size: 12px;
  color: var(--ion-color-medium-shade);
  font-weight: 500;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.version-footer ion-icon {
  font-size: 16px;
  color: var(--ion-color-medium);
}

.version-footer:hover {
  color: var(--ion-color-primary);
  background: var(--ion-color-step-50, #f9f9f9);
  border-radius: 4px;
}

.version-footer:hover ion-icon {
  color: var(--ion-color-primary);
}

.sidebar-shell {
  display: flex;
  flex-direction: column;
  height: 100%;
  --footer-bg: var(--ion-background-color, #fff);
}

@media (prefers-color-scheme: light) {
  .sidebar-shell {
    --footer-bg: #eff3f6;
  }
}

.sidebar-shell__scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

.sidebar-footer {
  position: relative;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 6px;
  border-top: 1px solid var(--ion-color-step-150, #d7d8da);
  background: var(--footer-bg);
}

.sidebar-footer::before {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: -16px;
  height: 16px;
  pointer-events: none;
  background: linear-gradient(to top, var(--footer-bg), transparent);
}

.sidebar-footer.collapsed {
  flex-direction: column;
  gap: 2px;
  padding: 3px 0;
  align-items: center;
  max-width: 76px;
}

.footer-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  color: var(--ion-color-medium-shade);
  font-size: 12px;
  font-weight: 500;
  transition: background 0.15s ease, color 0.15s ease;
}

.footer-btn:hover {
  background: var(--ion-color-step-100, #f1f1f1);
  color: var(--ion-color-primary);
}

.footer-btn ion-icon {
  font-size: 17px;
}

.footer-notif {
  margin-left: auto;
  position: relative;
}

.sidebar-footer.collapsed .footer-btn {
  margin-left: 0;
  justify-content: center;
  width: 40px;
}

.notif-dot {
  position: absolute;
  top: 2px;
  right: 4px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  background: var(--ion-color-primary, #f97316);
  color: #fff;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidebar-footer.collapsed .notif-dot {
  top: 4px;
  right: 10px;
}

.footer-btn:hover::after {
  content: attr(data-tooltip);
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  background: var(--ion-color-dark, #222);
  color: var(--ion-color-light, #fff);
  padding: 6px 10px;
  border-radius: 6px;
  white-space: nowrap;
  z-index: 1001;
  margin-left: 12px;
  font-size: 13px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.footer-btn[data-tooltip='']:hover::after {
  content: none;
}

.notif-popover {
  --width: 280px;
}

.notif-header {
  padding: 12px 16px 8px;
  border-bottom: 1px solid var(--ion-color-step-150, #d7d8da);
}

.notif-header h4 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

.notif-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 28px 16px;
  color: var(--ion-color-medium-shade);
  font-size: 13px;
}

.notif-empty ion-icon {
  font-size: 26px;
}

.notif-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
}

.notif-item {
  padding: 10px 16px;
  border-bottom: 1px solid var(--ion-color-step-100, #f1f1f1);
}

.notif-item strong {
  display: block;
  font-size: 13px;
  margin-bottom: 2px;
}

.notif-item p {
  margin: 0;
  font-size: 12px;
  color: var(--ion-color-medium-shade);
}

.notif-item.unread {
  background: rgba(var(--ion-color-primary-rgb), 0.08);
}
</style>
