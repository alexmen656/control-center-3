<template>
  <div class="project-sidebar" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <div class="project-sidebar__scroll">
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
        <ion-menu-toggle auto-hide="false">
          <ion-item @click="this.selectedIndex = 0" lines="none" detail="false"
            :router-link="'/project/' + $route.params.project + '/'" class="hydrated menu-item"
            :class="{ selected: this.selectedIndex === 0, collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
            :data-tooltip="isCollapsed ? 'Overview' : ''">
            <ion-icon slot="start" name="apps-outline" />
            <ion-label v-if="!isCollapsed">Overview</ion-label>
          </ion-item>
        </ion-menu-toggle>
        <ion-reorder-group v-if="tools.length > 0" :disabled="false" @ionItemReorder="handleReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
            <ion-item
              @dblclick="goToConfig('/project/' + $route.params.project + '/' + formatToolLink(p.link) + '/config')"
              @click="this.selectedIndex = i + 1" lines="none" detail="false"
              :router-link="'/project/' + $route.params.project + '/' + formatToolLink(p.link)"
              class="hydrated menu-item"
              :class="{ selected: this.selectedIndex === i + 1, collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
              :data-tooltip="isCollapsed ? capitalizeFirst(p.name) : ''">
              <ion-icon slot="start" :name="p.icon" />
              <ion-label v-if="!isCollapsed">{{ capitalizeFirst(p.name) }}</ion-label>
              <ion-reorder v-if="!isCollapsed" slot="end">
                <ion-icon v-if="p.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
                <pre v-else></pre>
              </ion-reorder>
            </ion-item>
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
      <template v-for="section in sections" :key="`section-${section.id}`">
        <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed">
          <h4>{{ section.name }}</h4>
          <div>
            <router-link v-if="section.manage_route" :to="section.manage_route">
              <ion-icon style="color: var(--ion-color-medium-shade)" name="ellipsis-horizontal-circle-outline" />
            </router-link>
            <router-link v-if="section.show_add_button && section.add_button_route" :to="section.add_button_route">
              <ion-icon style="color: var(--ion-color-medium-shade)" name="add-circle-outline" />
            </router-link>
          </div>
        </ion-note>
        <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
          <ion-reorder-group :disabled="false" @ionItemReorder="handleSectionItemReorder($event, section.id)">
            <ion-menu-toggle auto-hide="false" v-for="(item, itemIndex) in getSectionItems(section)"
              :key="`section-${section.id}-item-${item.id}`">
              <ion-item v-if="item.item_type === 'tool'"
                @dblclick="goToConfig('/project/' + $route.params.project + '/' + formatToolLink(item.name) + '/config')"
                @click="selectSectionItem(section.id, itemIndex)" lines="none" detail="false"
                :router-link="getToolRoute(item)" class="hydrated menu-item" :class="{
                  selected: isSectionItemSelected(section.id, itemIndex),
                  collapsed: isCollapsed,
                  hasToBeDarkmode: hasToBeDarkmode
                }" :data-tooltip="isCollapsed ? capitalizeFirst(item.name) : ''">
                <ion-icon slot="start" :name="item.icon" />
                <ion-label v-if="!isCollapsed">{{ capitalizeFirst(item.name) }}</ion-label>
                <ion-reorder v-if="!isCollapsed" slot="end">
                  <ion-icon v-if="item.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
                  <pre v-else></pre>
                </ion-reorder>
              </ion-item>
              <ion-item v-else-if="item.item_type === 'table'" @click="selectSectionItem(section.id, itemIndex)"
                lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/tables/' + item.name"
                class="hydrated menu-item" :class="{
                  selected: isSectionItemSelected(section.id, itemIndex),
                  collapsed: isCollapsed,
                  hasToBeDarkmode: hasToBeDarkmode
                }" :data-tooltip="isCollapsed ? capitalizeFirst(item.name) : ''">
                <ion-icon slot="start" :name="item.icon || 'list-outline'" />
                <ion-label v-if="!isCollapsed">{{ capitalizeFirst(item.name) }}</ion-label>
                <ion-reorder v-if="!isCollapsed" slot="end">
                  <pre></pre>
                </ion-reorder>
              </ion-item>
            </ion-menu-toggle>
            <ion-menu-toggle auto-hide="false" v-if="getSectionItems(section).length === 0 && !isCollapsed">
              <ion-item lines="none" detail="false" class="empty-section-item">
                <ion-icon slot="start" name="folder-open-outline" color="medium" />
                <ion-label color="medium">No items in this section</ion-label>
              </ion-item>
            </ion-menu-toggle>
          </ion-reorder-group>
        </ion-list>
      </template>
      <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed && isAdminOrOwner">
        <h4>Codespaces</h4>
        <div>
          <router-link v-for="(action, idx) in codespaceActions" :key="idx" :to="action.to">
            <ion-icon style="color: var(--ion-color-medium-shade)" :name="action.icon" />
          </router-link>
        </div>
      </ion-note>
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
        v-if="isAdminOrOwner">
        <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(codespace, i) in filteredCodespaces" :key="`codespace-${i}`">
            <ion-item
              @click="this.selectedIndex = Number(tools.length) + Number(forms.length) + Number(components.length) + Number(i) + 1"
              lines="none" detail="false"
              :router-link="'/project/' + $route.params.project + '/codespace/' + codespace.slug"
              class="hydrated menu-item" :class="{
                selected: this.selectedIndex === Number(tools.length) + Number(forms.length) + Number(components.length) + Number(i) + 1,
                collapsed: isCollapsed,
                hasToBeDarkmode: hasToBeDarkmode
              }" :data-tooltip="isCollapsed ? codespace.name : ''">
              <ion-icon slot="start" :name="codespace.icon || 'code-outline'" />
              <ion-label v-if="!isCollapsed">{{ codespace.name }}</ion-label>
              <span v-if="!isCollapsed" class="codespace-status-indicator"
                :class="{ 'status-active': codespace.status === 'active', 'status-inactive': codespace.status === 'inactive' }"></span>
            </ion-item>
          </ion-menu-toggle>
          <ion-menu-toggle auto-hide="false" v-if="filteredCodespaces.length === 0 && !isCollapsed && isAdminOrOwner">
            <ion-item lines="none" detail="false" class="no-codespaces-item">
              <ion-icon slot="start" name="code-slash-outline" color="medium" />
              <ion-label color="medium">No Codespaces yet</ion-label>
            </ion-item>
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
      <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed && isAdminOrOwner">
        <h4>APIs</h4>
        <div>
          <router-link v-for="(action, idx) in apiActions" :key="idx" :to="action.to">
            <ion-icon style="color: var(--ion-color-medium-shade)" :name="action.icon" />
          </router-link>
        </div>
      </ion-note>
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
        v-if="isAdminOrOwner">
        <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(api, i) in filteredApis" :key="`api-${i}`">
            <ion-item @click="this.selectedIndex = Number(tools.length) + Number(components.length) + Number(i) + 1"
              lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/apis/' + api.slug"
              class="hydrated menu-item" :class="{
                selected: this.selectedIndex === Number(tools.length) + Number(components.length) + Number(i) + 1,
                collapsed: isCollapsed,
                hasToBeDarkmode: hasToBeDarkmode
              }" :data-tooltip="isCollapsed ? api.name : ''">
              <ion-icon slot="start" :name="api.icon || 'code-outline'" />
              <ion-label v-if="!isCollapsed">{{ api.name }}</ion-label>
            </ion-item>
          </ion-menu-toggle>
          <ion-menu-toggle auto-hide="false" v-if="filteredApis.length === 0 && !isCollapsed && isAdminOrOwner">
            <ion-item lines="none" detail="false" class="no-apis-item">
              <ion-icon slot="start" name="code-slash-outline" color="medium" />
              <ion-label color="medium">No APIs subscribed</ion-label>
            </ion-item>
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
    </div>
    <footer class="sidebar-footer" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
      <button type="button" class="footer-btn footer-toggle" @click="toggleSidebar"
        :data-tooltip="isCollapsed ? 'Expand menu' : ''" :aria-label="isCollapsed ? 'Expand menu' : 'Collapse menu'">
        <ion-icon :name="isCollapsed ? 'chevron-forward-outline' : 'chevron-back-outline'" />
        <span v-if="!isCollapsed">Minimize</span>
      </button>
      <button type="button" id="sidebar-notif-trigger" class="footer-btn footer-notif"
        :data-tooltip="isCollapsed ? 'Notifications' : ''" aria-label="Notifications">
        <ion-icon name="notifications-outline" />
        <span v-if="!isCollapsed">Notifications</span>
        <span v-if="unreadCount > 0" class="notif-dot">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
      </button>
    </footer>
    <ion-popover trigger="sidebar-notif-trigger" side="top" alignment="end" show-backdrop="false"
      :class="{ hasToBeDarkmode: hasToBeDarkmode }" @didPresent="markAllRead">
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
</template>

<script lang="ts">
/* eslint-disable */
import { defineComponent, ref, computed } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import { useIonRouter } from "@ionic/vue";
import { layersOutline, gridOutline, documentsOutline } from 'ionicons/icons';

interface SidebarTool {
  id: number;
  icon: string;
  name: string;
  link: string;
  hasConfig: number;
  order: number;
  section_id?: number;
  item_type?: string;
}

interface SidebarItem {
  id: string | number;
  icon: string;
  name: string;
  link?: string;
  hasConfig?: number;
  order: number;
  section_id?: number;
  item_type: 'tool' | 'table';
  table_id?: number;
}

interface SidebarSection {
  id: number;
  name: string;
  slug: string;
  icon: string;
  order_index: number;
  is_default: boolean;
  is_collapsible: boolean;
  show_add_button: boolean;
  add_button_route: string | null;
  manage_route: string | null;
  tools: SidebarTool[];
  items?: SidebarItem[];
}

interface SidebarForm {
  table_id: number;
  name: string;
  icon?: string;
  section_id?: number;
  order_index?: number;
}

export default defineComponent({
  name: "ProjectSideBar",
  props: {
    isCollapsed: {
      type: Boolean,
      default: false
    },
    hasToBeDarkmode: {
      type: Boolean,
      default: false
    }
  },
  emits: ['sidebarToggled'],
  setup(props, { emit }) {
    const selectedIndex = ref(0);
    const selectedSectionId = ref<number | null>(null);
    const selectedToolIndex = ref<number | null>(null);
    const selectedItemIndex = ref<number | null>(null);
    const tools = ref<SidebarTool[]>([]);
    const sections = ref<SidebarSection[]>([]);
    const components = ref([]);
    const apis = ref([]);
    const codespaces = ref([]);
    const forms = ref<SidebarForm[]>([]);
    const notifications = ref<any[]>([]);
    const unreadCount = ref(0);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    const userPermissions = ref<any>(null);
    const userRole = ref<any>(null);

    const loadUserPermissions = async () => {
      try {
        const response = await axios.post(
          'roles.php',
          qs.stringify({
            getUserRole: 'getUserRole',
            project: route.params.project
          })
        );

        if (response.data.role) {
          userRole.value = response.data.role;
          userPermissions.value = response.data.role.permissions;
        }
      } catch (error) {
        console.error('Failed to load user permissions:', error);
      }
    };

    const isAdminOrOwner = computed(() => {
      console.log('role ' + userRole.value);
      if (!userRole.value) return false;
      return ['admin', 'owner'].includes(userRole.value.slug);
    });

    const filteredApis = computed(() => {
      if (isAdminOrOwner.value) return apis.value;
      return [];
    });

    const filteredCodespaces = computed(() => {
      if (isAdminOrOwner.value) return codespaces.value;
      return [];
    });

    const shouldShowSection = (sectionName: string) => {
      if (isAdminOrOwner.value) return true;

      const adminOnlySections = ['APIs', 'Codespaces'];
      return !adminOnlySections.includes(sectionName);
    };

    const getSectionItems = (section: SidebarSection): SidebarItem[] => {
      return section.items || [];
    };

    const selectSectionItem = (sectionId: number, itemIndex: number) => {
      selectedSectionId.value = sectionId;
      selectedItemIndex.value = itemIndex;
    };

    const isSectionItemSelected = (sectionId: number, itemIndex: number): boolean => {
      return selectedSectionId.value === sectionId && selectedItemIndex.value === itemIndex;
    };

    const handleSectionItemReorder = async (event: CustomEvent, sectionId: number) => {
      const from = event.detail.from;
      const to = event.detail.to;

      const section = sections.value.find(s => s.id === sectionId);
      if (section && section.items) {
        const movedItem = section.items.splice(from, 1)[0];
        section.items.splice(to, 0, movedItem);

        const itemOrder = section.items.map((item, index) => ({
          id: item.item_type === 'tool' ? item.id : item.table_id,
          type: item.item_type,
          order: index
        }));

        try {
          await axios.post(`v2/sidebar/sections/${sectionId}/items/reorder`, {
            project: route.params.project,
            item_order: itemOrder
          });
        } catch (error) {
          console.error("Error saving item order:", error);
        }
      }

      event.detail.complete();
    };

    const toggleSidebar = () => {
      emit('sidebarToggled', !props.isCollapsed);
    };

    const markAllRead = () => {
      unreadCount.value = 0;
    };

    const formatToolLink = (name: string): string => {
      return name
        .toLowerCase()
        .replaceAll(' ', '-')
        .replaceAll('ä', 'a')
        .replaceAll('Ä', 'a')
        .replaceAll('ö', 'o')
        .replaceAll('Ö', 'o')
        .replaceAll('Ü', 'u')
        .replaceAll('ü', 'u');
    };

    const capitalizeFirst = (str: string): string => {
      return str.charAt(0).toUpperCase() + str.slice(1);
    };

    const getToolRoute = (tool: SidebarTool | SidebarItem): string => {
      const projectPath = '/project/' + route.params.project;
      const link = (tool as SidebarTool).link || tool.name;
      const toolSlug = formatToolLink(link);

      return `${projectPath}/${toolSlug}`;
    };

    const selectTool = (sectionId: number, toolIndex: number) => {
      selectedSectionId.value = sectionId;
      selectedToolIndex.value = toolIndex;
    };

    const isToolSelected = (sectionId: number, toolIndex: number): boolean => {
      return selectedSectionId.value === sectionId && selectedToolIndex.value === toolIndex;
    };

    const handleFrontReorder = (event: CustomEvent) => {
      console.log(1);
      event.detail.complete();
    };

    const handleSectionToolReorder = async (event: CustomEvent, sectionId: number) => {
      const from = event.detail.from;
      const to = event.detail.to;

      const section = sections.value.find(s => s.id === sectionId);

      if (section && section.tools) {
        const movedTool = section.tools.splice(from, 1)[0];
        section.tools.splice(to, 0, movedTool);
        const toolIds = section.tools.map(t => t.id);

        try {
          await axios.post(`v2/sidebar/sections/${sectionId}/tools/reorder`, {
            project: route.params.project,
            tool_order: toolIds
          });
        } catch (error) {
          console.error("Error saving tool order:", error);
        }
      }

      event.detail.complete();
    };

    const handleReorder = (event: CustomEvent) => {
      const schluesselMitWertEins = Object.keys(list).find(function (
        schluessel
      ) {
        return list[schluessel] == event.detail.from.toString();
      });

      if (schluesselMitWertEins) {
        if (Number(event.detail.to) < Number(event.detail.from)) {
          for (const [key, value] of Object.entries(list)) {
            if (value == event.detail.to) {
              list[key] = (event.detail.to + 1).toString();
            } else if (
              (value as number) > event.detail.to &&
              (value as number) < event.detail.from
            ) {
              list[key] = (Number(value) + 1).toString();
            }
          }
          list[schluesselMitWertEins] = event.detail.to.toString();
          axios.post("test.php", qs.stringify({ list: JSON.stringify(list) }));
        } else {
          for (const [key, value] of Object.entries(list)) {
            if (value == event.detail.to) {
              list[key] = (event.detail.to - 1).toString();
            } else if (
              (value as number) > event.detail.from &&
              (value as number) < event.detail.to
            ) {
              list[key] = (Number(value) - 1).toString();
            }
          }
          list[schluesselMitWertEins] = event.detail.to.toString();
          axios.post("test.php", qs.stringify({ list: JSON.stringify(list) }));
        }
      }
      event.detail.complete();
    };

    const loadSidebarData = () => {
      axios
        .get("sidebar.php?getSideBarByProjectName=" + route.params.project)
        .then((response) => {
          sections.value = response.data.sections || [];
          tools.value = response.data.tools || [];
          apis.value = response.data.apis || [];
          codespaces.value = response.data.codespaces || [];
          forms.value = response.data.forms || [];

          tools.value.forEach((element: any) => {
            list[element.id] = element.order;
          });
        });
    };

    loadSidebarData();
    loadUserPermissions();

    function goToConfig(route: string) {
      ionRouter.push(route);
    }

    const codespaceActions = computed(() => {
      const projectPath = '/project/' + route.params.project;
      return [
        { to: projectPath + '/manage/codespaces', icon: 'ellipsis-horizontal-circle-outline' },
        { to: projectPath + '/new/codespace', icon: 'add-circle-outline' }
      ];
    });

    const apiActions = computed(() => {
      const projectPath = '/project/' + route.params.project;
      return [
        { to: projectPath + '/manage/apis', icon: 'ellipsis-horizontal-circle-outline' },
        { to: projectPath + '/manage/apis', icon: 'add-circle-outline' }
      ];
    });

    return {
      tools,
      sections,
      selectedIndex,
      selectedSectionId,
      selectedToolIndex,
      selectedItemIndex,
      components,
      apis,
      codespaces,
      forms,
      notifications,
      unreadCount,
      markAllRead,
      goToConfig,
      handleReorder,
      handleFrontReorder,
      handleSectionToolReorder,
      handleSectionItemReorder,
      layersOutline,
      gridOutline,
      documentsOutline,
      isCollapsed: computed(() => props.isCollapsed),
      toggleSidebar,
      formatToolLink,
      capitalizeFirst,
      getToolRoute,
      selectTool,
      isToolSelected,
      getSectionItems,
      selectSectionItem,
      isSectionItemSelected,
      loadSidebarData,
      codespaceActions,
      apiActions,
      userPermissions,
      userRole,
      isAdminOrOwner,
      filteredApis,
      filteredCodespaces,
      shouldShowSection,
    };
  },
  created() {
    this.emitter.on("updateSidebar", () => {
      this.loadSidebarData();
    });
  },
});
</script>

<style scoped>
@media (prefers-color-scheme: light) {

  ion-item,
  ion-list,
  ion-reorder-group {
    --background: #eff3f6;
    background: #eff3f6;
  }
}

.project-sidebar {
  display: flex;
  flex-direction: column;
  height: 100%;
  --footer-bg: var(--ion-background-color, #fff);
}

@media (prefers-color-scheme: light) {
  .project-sidebar {
    --footer-bg: #eff3f6;
  }
}

.project-sidebar.hasToBeDarkmode {
  --footer-bg: #1e1e1e;
}

.project-sidebar__scroll {
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

.sidebar-footer.hasToBeDarkmode .footer-btn:hover {
  background: #2a2a2a;
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

ion-item.new-tool {
  --background: #f97316;
  border-radius: 8px !important;
  margin-top: 0.125rem !important;
}

ion-item.new-tool ion-label {
  color: #fff;
}

ion-item.new-tool ion-icon {
  color: #fff;
}

.empty-section-item {
  opacity: 0.6;
}

.empty-section-item ion-label {
  font-style: italic;
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

.api-status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-left: 8px;
}

.api-status-indicator.status-active {
  background-color: var(--ion-color-success);
}

.api-status-indicator.status-inactive {
  background-color: var(--ion-color-medium);
}

.api-category-badge {
  margin-left: 8px;
  font-size: 0.7em;
  padding: 2px 6px;
}

.no-apis-item {
  opacity: 0.6;
}

.no-apis-item ion-label {
  font-style: italic;
}

.sub-components {
  position: relative;
  margin-left: 20px;
  padding-left: 10px;
  margin-bottom: 8px;
  margin-top: 2px;
}

.sub-components::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 0;
  border-left: 1px dashed var(--ion-color-medium-shade);
  height: 83.6%;
}

.sub-components-wrapper {
  position: relative;
}

.sub-component-item {
  position: relative;
  font-size: 0.9em;
  --padding-start: 10px;
}

.sub-component-item::before {
  content: '';
  position: absolute;
  left: -16px;
  top: 50%;
  width: 16px;
  height: 1px;
  background-color: var(--ion-color-medium-shade);
  border-top: 1px dashed var(--ion-color-medium-shade);
  z-index: 9999;
}

.sub-components .sub-item-container:last-child::after {
  content: '';
  position: absolute;
  left: -10px;
  top: 50%;
  bottom: -8px;
  width: 1px;
  background-color: var(--ion-background-color);
  z-index: 9998;
}

.tree-branch {
  display: none;
}

.sub-component-indent {
  position: absolute;
  left: -20px;
  top: 50%;
  width: 20px;
  height: 1px;
  background-color: var(--ion-color-medium-shade);
}

.sub-component-line {
  position: relative;
  display: inline-block;
  width: 16px;
  height: 16px;
  margin-right: -8px;
}

.sub-component-line:before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  width: 10px;
  height: 1px;
  background-color: var(--ion-color-medium-shade);
  z-index: 9999;

}

.sub-component-line:after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 1px;
  height: 8px;
  background-color: var(--ion-color-medium-shade);
}

.tree-connector {
  position: absolute;
  width: 15px;
  height: 15px;
  left: -15px;
  top: 50%;
  transform: translateY(-50%);
  border-bottom: 1px dashed var(--ion-color-medium-shade);
  border-left: 1px dashed var(--ion-color-medium-shade);
  border-bottom-left-radius: 5px;
}

.tree-connector-line {
  position: absolute;
  width: 10px;
  height: 1px;
  left: -11px;
  margin-top: 23px;
  border-bottom: 0.75px dashed var(--ion-color-medium-shade);
  z-index: 1000;
}

.tree-branch-connector {
  position: absolute;
  width: 10px;
  height: 1px;
  left: -11px;
  top: 50%;
  border-bottom: 1px dashed var(--ion-color-medium-shade);
  z-index: 100;
}

.horizontal-tree-line {
  position: absolute;
  width: 10px;
  height: 1px;
  left: -10px;
  top: 49%;
  border-bottom: 1px dashed var(--ion-color-medium-shade);
  z-index: 100;
}

.parent-component ion-icon[slot="end"] {
  margin-left: 4px;
  font-size: 16px;
  transition: transform 0.2s ease;
}

.parent-component {
  --padding-end: 12px;
}

.sub-item-container {
  position: relative;
  display: block;
  width: 100%;
}

.collapsed.projects-headline {
  display: none;
}

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

.collapsed+div {
  display: none;
}

.collapsed+div {
  display: none;
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

.inner-scroll {
  padding-left: 0 !important;
  padding-right: 0 !important;
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

ion-list.hasToBeDarkmode {
  background: #1e1e1e;
}

.menu-item.hasToBeDarkmode {
  --background: #1e1e1e !important;
}

.codespace-language-badge {
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
}

.codespace-status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-left: auto;
  margin-right: 8px;
}

.codespace-status-indicator.status-active {
  background-color: var(--ion-color-success);
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.codespace-status-indicator.status-inactive {
  background-color: var(--ion-color-medium);
}

.no-codespaces-item {
  opacity: 0.6;
}

.no-codespaces-item ion-icon {
  color: var(--ion-color-medium) !important;
}

.no-webbuilder-item {
  opacity: 0.6;
}

.no-webbuilder-item ion-icon {
  color: var(--ion-color-medium) !important;
}

.no-forms-item {
  opacity: 0.6;
}

.no-forms-item ion-icon {
  color: var(--ion-color-medium) !important;
}

.no-forms-item ion-label {
  font-style: italic;
}
</style>
