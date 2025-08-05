<template>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleReorder($event)">
      <ion-menu-toggle auto-hide="false">
        <ion-item @click="this.selectedIndex = 0" lines="none" detail="false"
          :router-link="'/project/' + $route.params.project + '/'" 
          class="hydrated menu-item"
          :class="{ selected: this.selectedIndex === 0, collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
          :data-tooltip="isCollapsed ? 'Overview' : ''">
          <ion-icon slot="start" name="apps-outline" />
          <ion-label v-if="!isCollapsed">Overview</ion-label>
        </ion-item>
      </ion-menu-toggle>

      <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
        <!-- {{ i }}-->
        <ion-item @dblclick="
          goToConfig(
            '/project/' +
            $route.params.project +
            '/' +
            p.name
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
          " @click="this.selectedIndex = i + 1" lines="none" detail="false" :router-link="p.icon == 'bar-chart-outline'
            ? '/project/' +
            $route.params.project +
            '/dashboard/' +
            p.name
              .toLowerCase()
              .replaceAll(' ', '-')
              .replaceAll('ä', 'a')
              .replaceAll('Ä', 'a')
              .replaceAll('ö', 'o')
              .replaceAll('Ö', 'o')
              .replaceAll('Ü', 'u')
              .replaceAll('ü', 'u')
            : '/project/' +
            $route.params.project +
            '/' +
            p.name
              .toLowerCase()
              .replaceAll(' ', '-')
              .replaceAll('ä', 'a')
              .replaceAll('Ä', 'a')
              .replaceAll('ö', 'o')
              .replaceAll('Ö', 'o')
              .replaceAll('Ü', 'u')
              .replaceAll('ü', 'u')
            " class="hydrated menu-item" 
            :class="{ selected: this.selectedIndex === i + 1, collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }"
            :data-tooltip="isCollapsed ? (p.name[0].toUpperCase() + p.name.substring(1)) : ''">
          <ion-icon slot="start" :name="p.icon" />
          <ion-label v-if="!isCollapsed">{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label>
          <ion-reorder v-if="!isCollapsed" slot="end">
            <ion-icon v-if="p.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
            <pre v-else></pre>
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" style="margin-top: 1rem !important" v-if="!isCollapsed">
        <ion-item lines="none" detail="false" class="new-tool"
          :router-link="'/project/' + $route.params.project + '/new-tool/'">
          <ion-icon slot="start" name="add" />
          <ion-label>New Tool</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline" :class="{ collapsed: isCollapsed }">
    <h4 v-if="!isCollapsed">Pages</h4>
    <div v-if="!isCollapsed">
      <router-link :to="'/project/' + $route.params.project + '/manage/pages'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/pages/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/page'"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list" :class="{ collapsed: isCollapsed, hasToBeDarkmode: hasToBeDarkmode }">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <template v-for="(component, i) in components" :key="i">
        <!-- Parent component -->
        <ion-menu-toggle auto-hide="false">
          <ion-item @dblclick="
            goToConfig(
              '/project/' +
              $route.params.project +
              '/page/' +
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
                '/page/' +
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
                " class="hydrated menu-item parent-component" :class="{
                  selected: selectedIndex === Number(i) + Number(tools.length) + 1,
                  collapsed: isCollapsed,
                  hasToBeDarkmode: hasToBeDarkmode
                }"
                :data-tooltip="isCollapsed ? component.name : ''">
            <ion-icon slot="start" :name="getIcon(component.type)" />
            <ion-label v-if="!isCollapsed">{{ component.name[0].toUpperCase() }}{{ component.name.substring(1) }}</ion-label>
            <ion-icon v-if="!isCollapsed" :name="isComponentExpanded(component.id) ? 'chevron-down-outline' : 'chevron-forward-outline'"
              slot="end"></ion-icon>
            <ion-reorder v-if="!isCollapsed" slot="end">
              <ion-icon v-if="component.hasConfig == 1 || component.type == 'menu'"
                style="cursor: pointer; z-index: 1000" name="cog-outline" />
              <pre v-else></pre>
            </ion-reorder>
          </ion-item>
        </ion-menu-toggle>

        <!-- Sub components with improved tree structure -->
        <div v-if="isComponentExpanded(component.id) && !isCollapsed" class="sub-components">
          <ion-menu-toggle auto-hide="false" v-for="(subComp, j) in getSubComponents(component.id)" :key="`${i}-${j}`"
            class="sub-item-container">
            <div class="horizontal-tree-line"></div>
            <ion-item @click="selectedIndex = Number(i) + Number(tools.length) + 1 + Number(j) + 0.1" lines="none"
              detail="false" :router-link="'/project/' +
                $route.params.project +
                '/page/' +
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
                subComp.name
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
          class="hydrated menu-item" 
          :class="{
            selected: this.selectedIndex === Number(i) + Number(tools.length) + Number(components.length) + 1,
            collapsed: isCollapsed,
            hasToBeDarkmode: hasToBeDarkmode
          }"
          :data-tooltip="isCollapsed ? p.name : ''"><!-- target="_blank"-->
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
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/apis/"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/manage/apis'"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="add-circle-outline"></ion-icon></router-link>
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
          }"
          :data-tooltip="isCollapsed ? api.name : ''">
          <ion-icon slot="start" :name="api.icon || 'code-outline'" />
          <ion-label v-if="!isCollapsed">{{ api.name }}</ion-label>
          <ion-badge v-if="!isCollapsed && api.category" color="medium" class="api-category-badge">{{ api.category }}</ion-badge>
          <span v-if="!isCollapsed" class="api-status-indicator"
            :class="{ 'status-active': api.status === 'active', 'status-inactive': api.status === 'inactive' }"></span>
          <ion-reorder v-if="!isCollapsed" slot="end">
            <ion-icon style="cursor: pointer; z-index: 1000" name="settings-outline" />
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
      
      <!-- No APIs message -->
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
    const tools = ref<{ id: number; order: number }[]>([]);
    const components = ref([]);
    const services = ref([]);
    const apis = ref([]);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    const componentsExpanded = ref(true);
    const expandedComponents = ref<{ [key: string]: boolean }>({});
    const componentSubItems = ref<{ [key: string]: any[] }>({});

    const toggleSidebar = () => {
      emit('sidebarToggled', !props.isCollapsed);
    };

    const handleFrontReorder = (event: CustomEvent) => {
      console.log(1);
      event.detail.complete();
    };
    const handleReorder = (event: CustomEvent) => {
      // The `from` and `to` properties contain the index of the item
      // when the drag started and ended, respectively

      const schluesselMitWertEins = Object.keys(list).find(function (
        schluessel
      ) {
        return list[schluessel] == event.detail.from.toString();
      });

      if (schluesselMitWertEins) {
        if (Number(event.detail.to) < Number(event.detail.from)) {
          console.log(
            "Der Schlüssel mit dem Wert " +
            event.detail.from +
            " ist: " +
            schluesselMitWertEins
          );
          for (const [key, value] of Object.entries(list)) {
            console.log(
              `${key}: ${value}, From: ${event.detail.from}, To: ${event.detail.to}`
            );
            if (value == event.detail.to) {
              console.log(key);
              list[key] = (event.detail.to + 1).toString();
            } else if (
              (value as number) > event.detail.to &&
              (value as number) < event.detail.from
            ) {
              console.log("yes");
              list[key] = (Number(value) + 1).toString();
            }
          }
          list[schluesselMitWertEins] = event.detail.to.toString();

          console.log(list);
          axios.post("test.php", qs.stringify({ list: JSON.stringify(list) }));
        } else {
          // console.log("Der Schlüssel mit dem Wert " + event.detail.from +" ist: " + schluesselMitWertEins);
          for (const [key, value] of Object.entries(list)) {
            //console.log(`${key}: ${value}, From: ${event.detail.from}, To: ${event.detail.to}`);
            if (value == event.detail.to) {
              //console.log(key);
              list[key] = (event.detail.to - 1).toString();
            } else if (
              (value as number) > event.detail.from &&
              (value as number) < event.detail.to
            ) {
              //console.log("yes");
              list[key] = (Number(value) - 1).toString();
            }
          }
          list[schluesselMitWertEins] = event.detail.to.toString();

          console.log(list);
          axios.post("test.php", qs.stringify({ list: JSON.stringify(list) }));
        }
      } else {
        console.log(
          "Es gibt keinen Schlüssel mit dem Wert " + event.detail.from + "."
        );
      }
      console.log(
        "Dragged from index",
        event.detail.from,
        "to",
        event.detail.to
      );

      // Finish the reorder and position the item in the DOM based on
      // where the gesture ended. This method can also be called directly
      // by the reorder group
      event.detail.complete();
    };

    axios
      .get("sidebar.php?getSideBarByProjectName=" + route.params.project)
      .then((response) => {
        tools.value = response.data.tools;
        components.value = response.data.components;
        services.value = response.data.services || [];
        apis.value = response.data.apis || [];
        componentSubItems.value = response.data.componentSubItems || {};
        tools.value.forEach((element) => {
          list[element.id] = element.order;
        });
      });

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
      tools: tools,
      selectedIndex,
      components: components,
      services: services,
      apis: apis,
      goToConfig,
      handleReorder,
      handleFrontReorder,
      layersOutline,
      gridOutline,
      documentsOutline,
      componentsExpanded,
      toggleComponentExpanded,
      isComponentExpanded,
      getSubComponents,
      isCollapsed: computed(() => props.isCollapsed),
      toggleSidebar,
    };
  },
  created() {
    this.emitter.on("updateSidebar", () => {
      axios
        .get(
          "sidebar.php?getSideBarByProjectName=" + this.$route.params.project
        )
        .then((response) => {
          this.tools = response.data.tools;
          this.components = response.data.components;
          this.services = response.data.services || [];
          this.apis = response.data.apis || [];
          this.componentSubItems = response.data.componentSubItems || {};
        });
    });
  },
  methods: {
    getIcon(type) {
      if (type == "script") {
        return "code-slash-outline";
      } else if (type == "image") {
        return "image-outline";
      } else if (type == "menu") {
        return "menu-outline";
      } else {
        return "help-outline";
      }
    },
  },
});
</script>

<style scoped>
ion-item.new-tool {
  --background: #333;
  border-radius: 20px !important;
  margin-top: 0.5rem !important;
}

ion-item.new-tool ion-label {
  color: #fff;
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
.collapsed + div {
  display: none;
}

/* Hide version and other text elements when collapsed */
.collapsed + div {
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
  background: /*var(*/#1e1e1e/*, var(--ion-background-color, #fff));*/
}

.menu-item.hasToBeDarkmode {
  --background: #1e1e1e !important;
}
</style>
