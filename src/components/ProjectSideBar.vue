<template>
  <div class="project-sidebar" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <div class="project-sidebar__scroll">
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
        <ion-menu-toggle auto-hide="false">
          <SidebarMenuItem :to="'/project/' + $route.params.project + '/'" icon="apps-outline" label="Overview"
            :is-collapsed="isCollapsed" :has-to-be-darkmode="hasToBeDarkmode" />
        </ion-menu-toggle>
        <ion-reorder-group v-if="tools.length > 0" :disabled="false" @ionItemReorder="handleReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
            <SidebarMenuItem :to="'/project/' + $route.params.project + '/' + formatToolLink(p.link)" :icon="p.icon"
              :label="capitalizeFirst(p.name)" :is-collapsed="isCollapsed" :has-to-be-darkmode="hasToBeDarkmode"
              @dblclick="goToConfig('/project/' + $route.params.project + '/' + formatToolLink(p.link) + '/config')">
              <ion-reorder slot="end">
                <ion-icon v-if="p.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
                <pre v-else></pre>
              </ion-reorder>
            </SidebarMenuItem>
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
      <template v-for="section in sections" :key="`section-${section.id}`">
        <SidebarSectionHeader v-if="!isCollapsed" :title="section.name" :actions="getSectionActions(section)" />
        <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
          <ion-reorder-group :disabled="false" @ionItemReorder="handleSectionItemReorder($event, section.id)">
            <ion-menu-toggle auto-hide="false" v-for="(item, itemIndex) in getSectionItems(section)"
              :key="`section-${section.id}-item-${item.id}`">
              <SidebarMenuItem v-if="item.item_type === 'tool'" :to="getToolRoute(item)" :icon="item.icon"
                :label="capitalizeFirst(item.name)" :is-collapsed="isCollapsed" :has-to-be-darkmode="hasToBeDarkmode"
                @dblclick="goToConfig('/project/' + $route.params.project + '/' + formatToolLink(item.name) + '/config')">
                <ion-reorder slot="end">
                  <ion-icon v-if="item.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
                  <pre v-else></pre>
                </ion-reorder>
              </SidebarMenuItem>
              <SidebarMenuItem v-else-if="item.item_type === 'table'"
                :to="'/project/' + $route.params.project + '/tables/' + item.name" :icon="item.icon || 'list-outline'"
                :label="capitalizeFirst(item.name)" :is-collapsed="isCollapsed" :has-to-be-darkmode="hasToBeDarkmode">
                <ion-reorder slot="end">
                  <pre></pre>
                </ion-reorder>
              </SidebarMenuItem>
            </ion-menu-toggle>
            <ion-menu-toggle auto-hide="false" v-if="getSectionItems(section).length === 0 && !isCollapsed">
              <SidebarEmptyItem message="No items in this section" />
            </ion-menu-toggle>
          </ion-reorder-group>
        </ion-list>
      </template>
      <SidebarSectionHeader v-if="!isCollapsed && isAdminOrOwner" title="Codespaces" :actions="codespaceActions" />
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
        v-if="isAdminOrOwner">
        <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(codespace, i) in filteredCodespaces" :key="`codespace-${i}`">
            <SidebarMenuItem :to="'/project/' + $route.params.project + '/codespace/' + codespace.slug"
              :icon="codespace.icon || 'code-outline'" :label="codespace.name" :is-collapsed="isCollapsed"
              :has-to-be-darkmode="hasToBeDarkmode">
              <span class="codespace-status-indicator" :class="{
                'status-active': codespace.status === 'active',
                'status-inactive': codespace.status === 'inactive'
              }"></span>
            </SidebarMenuItem>
          </ion-menu-toggle>
          <ion-menu-toggle auto-hide="false" v-if="filteredCodespaces.length === 0 && !isCollapsed && isAdminOrOwner">
            <SidebarEmptyItem icon="code-slash-outline" message="No Codespaces yet" />
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
      <SidebarSectionHeader v-if="!isCollapsed && isAdminOrOwner" title="APIs" :actions="apiActions" />
      <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
        v-if="isAdminOrOwner">
        <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
          <ion-menu-toggle auto-hide="false" v-for="(api, i) in filteredApis" :key="`api-${i}`">
            <SidebarMenuItem :to="'/project/' + $route.params.project + '/apis/' + api.slug"
              :icon="api.icon || 'code-outline'" :label="api.name" :is-collapsed="isCollapsed"
              :has-to-be-darkmode="hasToBeDarkmode" />
          </ion-menu-toggle>
          <ion-menu-toggle auto-hide="false" v-if="filteredApis.length === 0 && !isCollapsed && isAdminOrOwner">
            <SidebarEmptyItem icon="code-slash-outline" message="No APIs subscribed" />
          </ion-menu-toggle>
        </ion-reorder-group>
      </ion-list>
    </div>
    <footer class="sidebar-footer" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
      <button type="button" class="footer-btn footer-toggle" @click="toggleSidebar"
        :data-tooltip="isCollapsed ? 'Expand menu' : ''" :aria-label="isCollapsed ? 'Expand menu' : 'Collapse menu'">
        <svg v-if="isCollapsed" class="sidebar-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
          <path d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2H6a2 2 0 0 1 -2 -2z" stroke-width="2"></path>
          <path d="M15 4v16" stroke-width="2"></path>
          <path d="m9 10 2 2 -2 2" stroke-width="2"></path>
        </svg>
        <svg v-else class="sidebar-toggle-icon" viewBox="-0.5 -0.5 16 16" fill="none" stroke="currentColor"
          stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
          <path
            d="M12.7769375 14.284625H2.2230625c-0.8326875 0 -1.5076875 -0.675 -1.5076875 -1.5076875l0 -10.553875c0 -0.8326875 0.675 -1.5076875 1.5076875 -1.5076875h10.553875c0.8326875 0 1.5076875 0.675 1.5076875 1.5076875v10.553875c0 0.8326875 -0.675 1.5076875 -1.5076875 1.5076875Z"
            stroke-width="1"></path>
          <path d="M3.9192500000000003 5.9923125 2.6 7.5l1.3192499999999998 1.5076875" stroke-width="1"></path>
          <path d="M5.615375 14.284625V0.7153750000000001" stroke-width="1"></path>
        </svg>
      </button>
    </footer>
  </div>
</template>

<script lang="ts">
/* eslint-disable */
import { defineComponent, ref, computed } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import { useIonRouter } from "@ionic/vue";
import SidebarMenuItem from "./SidebarMenuItem.vue";
import SidebarSectionHeader from "./SidebarSectionHeader.vue";
import SidebarEmptyItem from "./SidebarEmptyItem.vue";

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
  components: {
    SidebarMenuItem,
    SidebarSectionHeader,
    SidebarEmptyItem
  },
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
    const tools = ref<SidebarTool[]>([]);
    const sections = ref<SidebarSection[]>([]);
    const components = ref([]);
    const apis = ref([]);
    const codespaces = ref([]);
    const forms = ref<SidebarForm[]>([]);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    const userPermissions = ref<any>(null);
    const userRole = ref<any>(null);

    const loadUserPermissions = async () => {
      try {
        const response = await axios.get(
          'v2/roles/me',
          {
            params: {
              project: route.params.project
            }
          }
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

    const getSectionItems = (section: SidebarSection): SidebarItem[] => {
      return section.items || [];
    };

    const getSectionActions = (section: SidebarSection): { to: string; icon: string }[] => {
      const actions: { to: string; icon: string }[] = [];
      if (section.manage_route) {
        actions.push({ to: section.manage_route, icon: 'ellipsis-horizontal-circle-outline' });
      }
      if (section.show_add_button && section.add_button_route) {
        actions.push({ to: section.add_button_route, icon: 'add-circle-outline' });
      }
      return actions;
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

    const handleFrontReorder = (event: CustomEvent) => {
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
        .get("v2/sidebar?project=" + route.params.project)
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
      components,
      apis,
      codespaces,
      forms,
      goToConfig,
      handleReorder,
      handleFrontReorder,
      handleSectionToolReorder,
      handleSectionItemReorder,
      isCollapsed: computed(() => props.isCollapsed),
      toggleSidebar,
      formatToolLink,
      capitalizeFirst,
      getToolRoute,
      getSectionItems,
      getSectionActions,
      loadSidebarData,
      codespaceActions,
      apiActions,
      userPermissions,
      userRole,
      isAdminOrOwner,
      filteredApis,
      filteredCodespaces,
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

@media (prefers-color-scheme: light) {

  ion-list,
  ion-reorder-group {
    --background: #eff3f6;
    background: #eff3f6;
  }
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

.collapsed {
  text-align: center;
  width: 100% !important;
  max-width: 60px !important;
  overflow: hidden !important;
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

.inner-scroll {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

ion-list.hasToBeDarkmode {
  background: #1e1e1e;
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
</style>
