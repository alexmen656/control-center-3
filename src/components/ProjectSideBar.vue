<template>
  <!-- Overview Item (always at top) -->
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
        <ion-item @dblclick="goToConfig('/project/' + $route.params.project + '/' + formatToolLink(p.name) + '/config')"
          @click="this.selectedIndex = i + 1" lines="none" detail="false" :router-link="p.icon == 'bar-chart-outline'
            ? '/project/' + $route.params.project + '/dashboard/' + formatToolLink(p.name)
            : '/project/' + $route.params.project + '/' + formatToolLink(p.name)" class="hydrated menu-item"
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

  <!-- Custom Sections with Items (Tools + Forms) -->
  <template v-for="(section, sectionIndex) in sections" :key="`section-${section.id}`">
    <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed">
      <h4>{{ section.name }}</h4>
      <div>
        <router-link v-if="section.manage_route" :to="section.manage_route">
          <ion-icon style="color: var(--ion-color-medium-shade)" name="ellipsis-horizontal-circle-outline" />
        </router-link>
        <router-link v-if="section.info_route" :to="section.info_route">
          <ion-icon style="color: var(--ion-color-medium-shade)" name="information-circle-outline" />
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
          <!-- Tool Item -->
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
          <!-- Form Item -->
          <ion-item v-else-if="item.item_type === 'form'" @click="selectSectionItem(section.id, itemIndex)" lines="none"
            detail="false" :router-link="'/project/' + $route.params.project + '/forms/' + item.name"
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
        <!-- Empty state for section -->
        <ion-menu-toggle auto-hide="false" v-if="getSectionItems(section).length === 0 && !isCollapsed">
          <ion-item lines="none" detail="false" class="empty-section-item">
            <ion-icon slot="start" name="folder-open-outline" color="medium" />
            <ion-label color="medium">No items in this section</ion-label>
          </ion-item>
        </ion-menu-toggle>
      </ion-reorder-group>
    </ion-list>
  </template>

  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed">
    <h4>Codespaces</h4>
    <div>
      <router-link :to="'/project/' + $route.params.project + '/manage/codespaces'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/codespaces/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/codespace'"><ion-icon
          style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <ion-menu-toggle auto-hide="false" v-for="(codespace, i) in codespaces" :key="`codespace-${i}`">
        <ion-item
          @click="this.selectedIndex = Number(tools.length) + Number(forms.length) + Number(components.length) + Number(services.length) + Number(i) + 1"
          lines="none" detail="false"
          :router-link="'/project/' + $route.params.project + '/codespace/' + codespace.slug" class="hydrated menu-item"
          :class="{
            selected: this.selectedIndex === Number(tools.length) + Number(forms.length) + Number(components.length) + Number(services.length) + Number(i) + 1,
            collapsed: isCollapsed,
            hasToBeDarkmode: hasToBeDarkmode
          }" :data-tooltip="isCollapsed ? codespace.name : ''">
          <ion-icon slot="start" :name="codespace.icon || 'code-outline'" />
          <ion-label v-if="!isCollapsed">{{ codespace.name }}</ion-label>
          <!--<ion-badge v-if="!isCollapsed && codespace.language" color="primary" class="codespace-language-badge">{{ codespace.language }}</ion-badge>-->
          <span v-if="!isCollapsed" class="codespace-status-indicator"
            :class="{ 'status-active': codespace.status === 'active', 'status-inactive': codespace.status === 'inactive' }"></span>
          <!--  <ion-reorder v-if="!isCollapsed" slot="end">
            <ion-icon style="cursor: pointer; z-index: 1000" name="settings-outline" />
          </ion-reorder>-->
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" v-if="codespaces.length === 0 && !isCollapsed">
        <ion-item lines="none" detail="false" class="no-codespaces-item">
          <ion-icon slot="start" name="code-slash-outline" color="medium" />
          <ion-label color="medium">No Codespaces yet</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }">
    <h4 v-if="!isCollapsed">Web Builder</h4>
    <div v-if="!isCollapsed">
      <router-link :to="'/project/' + $route.params.project + '/manage/pages'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/pages/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/wb'"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <template v-for="(component, i) in components" :key="i">
        <ion-menu-toggle auto-hide="false">
          <ion-item @dblclick="
            goToConfig(
              '/project/' +
              $route.params.project +
              '/wb/' +
              component.name
                .toLowerCase()
                .replaceAll(' ', '-')
                .replaceAll('ä', 'a')
                .replaceAll('Ä', 'a')
                .replaceAll('ö', 'o')
                .replaceAll('Ö', 'o')
                .replaceAll('Ü', 'u')
                .replaceAll('ü', 'u') +
              '/config'
            )
            " @click="toggleComponentExpanded(component.id)" lines="none" detail="false" :router-link="'/project/' +
              $route.params.project +
              '/wb/' +
              component.slug + '/overview'
              /*component.name
                .toLowerCase()
                .replaceAll(' ', '-')
                .replaceAll('ä', 'a')
                .replaceAll('Ä', 'a')
                .replaceAll('ö', 'o')
                .replaceAll('Ö', 'o')
                .replaceAll('Ü', 'u')
                .replaceAll('ü', 'u')*/
              " class="hydrated menu-item parent-component" :class="{
                selected: selectedIndex === Number(i) + Number(tools.length) + Number(forms.length) + 1,
                collapsed: isCollapsed,
                hasToBeDarkmode: hasToBeDarkmode
              }" :data-tooltip="isCollapsed ? component.name : ''">
            <ion-icon slot="start" name="cube-outline" />
            <ion-label v-if="!isCollapsed">{{ component.name[0].toUpperCase() }}{{ component.name.substring(1)
              }}</ion-label>
            <ion-icon v-if="!isCollapsed"
              :name="isComponentExpanded(component.id) ? 'chevron-down-outline' : 'chevron-forward-outline'"
              slot="end"></ion-icon>
            <ion-reorder v-if="!isCollapsed" slot="end">
              <ion-icon v-if="component.hasConfig == 1 || component.type == 'menu'"
                style="cursor: pointer; z-index: 1000" name="cog-outline" />
              <pre v-else></pre>
            </ion-reorder>
          </ion-item>
        </ion-menu-toggle>
        <div v-if="isComponentExpanded(component.id) && !isCollapsed" class="sub-components">
          <ion-menu-toggle auto-hide="false" v-for="(subComp, j) in getSubComponents(component.id)" :key="`${i}-${j}`"
            class="sub-item-container">
            <div class="horizontal-tree-line"></div>
            <ion-item
              @click="selectedIndex = Number(i) + Number(tools.length) + Number(forms.length) + 1 + Number(j) + 0.1"
              lines="none" detail="false" :router-link="'/project/' +
                $route.params.project +
                '/wb/' +
                component.slug
                /*component.name
                  .toLowerCase()
                  .replaceAll(' ', '-')
                  .replaceAll('ä', 'a')
                  .replaceAll('Ä', 'a')
                  .replaceAll('ö', 'o')
                  .replaceAll('Ö', 'o')
                  .replaceAll('Ü', 'u')
                  .replaceAll('ü', 'u')*/
                + '/' +
                subComp.slug
                  .toLowerCase()
                  .replaceAll(' ', '-')" class="hydrated menu-item sub-component-item" :class="{
                    selected: selectedIndex === Number(i) + Number(tools.length) + 1 + Number(j) + 0.1, hasToBeDarkmode: hasToBeDarkmode
                  }">
              <!--  <ion-icon :name="getIcon(subComp.type)" /><--slot="start"---->
              <ion-label>{{ subComp.name }}</ion-label>
            </ion-item>
          </ion-menu-toggle>
        </div>
      </template>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }">
    <h4 v-if="!isCollapsed">Services</h4>
    <div v-if="!isCollapsed">
      <router-link :to="'/project/' + $route.params.project + '/manage/services'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/services/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/service'"><ion-icon
          style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <ion-menu-toggle auto-hide="false" v-for="(p, i) in services" :key="i">
        <ion-item @click="this.selectedIndex = Number(i) + Number(tools.length) + Number(components.length) + 1"
          lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/services/' + p.link"
          class="hydrated menu-item" :class="{
            selected: this.selectedIndex === Number(i) + Number(tools.length) + Number(components.length) + 1,
            collapsed: isCollapsed,
            hasToBeDarkmode: hasToBeDarkmode
          }" :data-tooltip="isCollapsed ? p.name : ''"><!-- target="_blank"-->
          <ion-icon slot="start" :name="p.icon || 'cog-outline'" />
          <ion-label v-if="!isCollapsed">{{ p.name }}</ion-label>
          <span class="service-status-indicator"
            :class="{ 'status-up': p.status === 'up', 'status-down': p.status === 'down' }"></span>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }" v-if="!isCollapsed">
    <h4>APIs</h4>
    <div>
      <router-link :to="'/project/' + $route.params.project + '/manage/apis'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/apis/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/manage/apis'"><ion-icon
          style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <ion-menu-toggle auto-hide="false" v-for="(api, i) in apis" :key="`api-${i}`">
        <ion-item
          @click="this.selectedIndex = Number(tools.length) + Number(components.length) + Number(services.length) + Number(i) + 1"
          lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/apis/' + api.slug"
          class="hydrated menu-item" :class="{
            selected: this.selectedIndex === Number(tools.length) + Number(components.length) + Number(services.length) + Number(i) + 1,
            collapsed: isCollapsed,
            hasToBeDarkmode: hasToBeDarkmode
          }" :data-tooltip="isCollapsed ? api.name : ''">
          <ion-icon slot="start" :name="api.icon || 'code-outline'" />
          <ion-label v-if="!isCollapsed">{{ api.name }}</ion-label>
          <!--  <ion-badge v-if="!isCollapsed && api.category" color="medium" class="api-category-badge">{{ api.category
            }}</ion-badge>
          <span v-if="!isCollapsed" class="api-status-indicator"
            :class="{ 'status-active': api.status === 'active', 'status-inactive': api.status === 'inactive' }"></span>
          <ion-reorder v-if="!isCollapsed" slot="end">
            <ion-icon style="cursor: pointer; z-index: 1000" name="settings-outline" />
          </ion-reorder>-->
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" v-if="apis.length === 0 && !isCollapsed">
        <ion-item lines="none" detail="false" class="no-apis-item">
          <ion-icon slot="start" name="code-slash-outline" color="medium" />
          <ion-label color="medium">No APIs subscribed</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
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
  item_type: 'tool' | 'form';
  form_id?: number;
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
  info_route: string | null;
  manage_route: string | null;
  tools: SidebarTool[];
  items?: SidebarItem[];
}

interface SidebarForm {
  form_id: number;
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
    const services = ref([]);
    const apis = ref([]);
    const codespaces = ref([]);
    const forms = ref<SidebarForm[]>([]);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    const componentsExpanded = ref(true);
    const expandedComponents = ref<{ [key: string]: boolean }>({});
    const componentSubItems = ref<{ [key: string]: any[] }>({});

    // Helper to get section items (tools + forms combined, already sorted from backend)
    const getSectionItems = (section: SidebarSection): SidebarItem[] => {
      return section.items || [];
    };

    // Selection helpers for section items
    const selectSectionItem = (sectionId: number, itemIndex: number) => {
      selectedSectionId.value = sectionId;
      selectedItemIndex.value = itemIndex;
    };

    const isSectionItemSelected = (sectionId: number, itemIndex: number): boolean => {
      return selectedSectionId.value === sectionId && selectedItemIndex.value === itemIndex;
    };

    // Handle reorder for section items (mixed tools and forms)
    const handleSectionItemReorder = async (event: CustomEvent, sectionId: number) => {
      const from = event.detail.from;
      const to = event.detail.to;

      const section = sections.value.find(s => s.id === sectionId);
      if (section && section.items) {
        const movedItem = section.items.splice(from, 1)[0];
        section.items.splice(to, 0, movedItem);

        // Build order data for backend
        const itemOrder = section.items.map((item, index) => ({
          id: item.item_type === 'tool' ? item.id : item.form_id,
          type: item.item_type,
          order: index
        }));

        try {
          await axios.post("sidebar_sections.php", qs.stringify({
            reorderSectionItems: true,
            project: route.params.project,
            section_id: sectionId,
            item_order: JSON.stringify(itemOrder)
          }));
        } catch (error) {
          console.error("Error saving item order:", error);
        }
      }

      event.detail.complete();
    };

    const toggleSidebar = () => {
      emit('sidebarToggled', !props.isCollapsed);
    };

    // Helper function to format tool link (handles special characters)
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

    // Helper to capitalize first letter
    const capitalizeFirst = (str: string): string => {
      return str.charAt(0).toUpperCase() + str.slice(1);
    };

    const getToolRoute = (tool: SidebarTool | SidebarItem): string => {
      const projectPath = '/project/' + route.params.project;
      const link = (tool as SidebarTool).link || tool.name;
      const toolSlug = formatToolLink(link);

      /*if (tool.icon === 'bar-chart-outline') {
        return `${projectPath}/dashboard/${toolSlug}`;
      }*/
      return `${projectPath}/${toolSlug}`;
    };

    // Selection helpers
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

      // Find the section
      const section = sections.value.find(s => s.id === sectionId);
      if (section && section.tools) {
        // Reorder tools in the section
        const movedTool = section.tools.splice(from, 1)[0];
        section.tools.splice(to, 0, movedTool);

        // Update order values and save to backend
        const toolIds = section.tools.map(t => t.id);
        try {
          await axios.post("sidebar_sections.php", qs.stringify({
            reorderToolsInSection: true,
            project: route.params.project,
            section_id: sectionId,
            tool_order: JSON.stringify(toolIds)
          }));
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

    // Load sidebar data
    const loadSidebarData = () => {
      axios
        .get("sidebar.php?getSideBarByProjectName=" + route.params.project)
        .then((response) => {
          sections.value = response.data.sections || [];
          tools.value = response.data.tools || [];
          components.value = response.data.components || [];
          services.value = response.data.services || [];
          apis.value = response.data.apis || [];
          codespaces.value = response.data.codespaces || [];
          forms.value = response.data.forms || [];
          componentSubItems.value = response.data.componentSubItems || {};

          // Build list for legacy tools
          tools.value.forEach((element: any) => {
            list[element.id] = element.order;
          });
        });
    };

    // Initial load
    loadSidebarData();

    function goToConfig(route: string) {
      ionRouter.push(route);
    }

    function toggleComponentExpanded(componentId: number) {
      expandedComponents.value[componentId] = !expandedComponents.value[componentId];
    }

    function isComponentExpanded(componentId: number) {
      return !!expandedComponents.value[componentId];
    }

    function getSubComponents(componentId: number) {
      return componentSubItems.value[componentId] || [];
    }

    return {
      tools,
      sections,
      selectedIndex,
      selectedSectionId,
      selectedToolIndex,
      selectedItemIndex,
      components,
      services,
      apis,
      codespaces,
      forms,
      goToConfig,
      handleReorder,
      handleFrontReorder,
      handleSectionToolReorder,
      handleSectionItemReorder,
      layersOutline,
      gridOutline,
      documentsOutline,
      componentsExpanded,
      toggleComponentExpanded,
      isComponentExpanded,
      getSubComponents,
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
    /*#f7fcff;*/
    background: #eff3f6;
  }
}

ion-item.new-tool {
  --background: #2563eb;
  border-radius: 8px !important;
  margin-top: 0.125rem !important;
}

ion-item.new-tool ion-label {
  color: #fff;
}

ion-item.new-tool ion-icon {
  color: #fff;
}

/* Empty Section Item */
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

.service-status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-left: 8px;
}

.service-status-indicator.status-up {
  background-color: green;
}

.service-status-indicator.status-down {
  background-color: red;
}

/* API Status Indicators */
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

/* API Category Badge */
.api-category-badge {
  margin-left: 8px;
  font-size: 0.7em;
  padding: 2px 6px;
}

/* No APIs Item */
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
  /* Nur bis 85% der Höhe, nicht bis ganz zum Ende */
}

.sub-components-wrapper {
  position: relative;
}

/* Horizontale Verbindungslinien direkt an den Unterelementen */
.sub-component-item {
  position: relative;
  font-size: 0.9em;
  --padding-start: 10px;
}

/* L-förmige Verbindung von der Hauptlinie zu jedem Unterelement */
.sub-component-item::before {
  content: '';
  position: absolute;
  left: -16px;
  /* Wichtig: weiter links, damit es an der vertikalen Linie beginnt */
  top: 50%;
  width: 16px;
  /* Länger, um die gesamte Strecke abzudecken */
  height: 1px;
  background-color: var(--ion-color-medium-shade);
  border-top: 1px dashed var(--ion-color-medium-shade);
  z-index: 9999;
}

/* Entferne den letzten vertikalen Strich nach dem letzten Element */
.sub-components .sub-item-container:last-child::after {
  content: '';
  position: absolute;
  left: -10px;
  top: 50%;
  bottom: -8px;
  width: 1px;
  background-color: var(--ion-background-color);
  /* Gleiche Farbe wie der Hintergrund */
  z-index: 9998;
}

/* Entferne alte Styles die nicht benötigt werden */
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
  background:
    /*var(*/
    #1e1e1e
    /*, var(--ion-background-color, #fff));*/
}

.menu-item.hasToBeDarkmode {
  --background: #1e1e1e !important;
}

/* Codespace specific styling */
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

/* Forms/Tables specific styling */
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
