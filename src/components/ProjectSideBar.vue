<template>
  <ion-list id="inbox-list">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleReorder($event)">
      <ion-menu-toggle auto-hide="false">
        <ion-item @click="this.selectedIndex = 0" lines="none" detail="false"
          :router-link="'/project/' + $route.params.project + '/'" class="hydrated menu-item"
          :class="{ selected: this.selectedIndex === 0 }">
          <ion-icon slot="start" name="apps-outline" />
          <ion-label>Overview</ion-label>
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
            " class="hydrated menu-item" :class="{ selected: this.selectedIndex === i + 1 }">
          <ion-icon slot="start" :name="p.icon" />
          <ion-label>{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label>
          <ion-reorder slot="end">
            <ion-icon v-if="p.hasConfig == 1" style="cursor: pointer; z-index: 1000" name="cog-outline" />
            <pre v-else></pre>
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" style="margin-top: 1rem !important">
        <ion-item lines="none" detail="false" class="new-tool"
          :router-link="'/project/' + $route.params.project + '/new-tool/'">
          <ion-icon slot="start" name="add" />
          <ion-label>New Tool</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline">
    <h4>Pages</h4>
    <div>
      <router-link :to="'/project/' + $route.params.project + '/manage/pages'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/pages/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/page'"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list">
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
                }">
            <ion-icon slot="start" :name="getIcon(component.type)" />
            <ion-label>{{ component.name[0].toUpperCase() }}{{ component.name.substring(1) }}</ion-label>
            <ion-icon :name="isComponentExpanded(component.id) ? 'chevron-down-outline' : 'chevron-forward-outline'"
              slot="end"></ion-icon>
            <ion-reorder slot="end">
              <ion-icon v-if="component.hasConfig == 1 || component.type == 'menu'"
                style="cursor: pointer; z-index: 1000" name="cog-outline" />
              <pre v-else></pre>
            </ion-reorder>
          </ion-item>
        </ion-menu-toggle>

        <!-- Sub components with improved tree structure -->
        <div v-if="isComponentExpanded(component.id)" class="sub-components">
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
                      selected: selectedIndex === Number(i) + Number(tools.length) + 1 + Number(j) + 0.1,
                    }">
            <!--  <ion-icon :name="getIcon(subComp.type)" /><--slot="start"---->
              <ion-label>{{ subComp.name }}</ion-label>
            </ion-item>
          </ion-menu-toggle>
        </div>
      </template>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline">
    <h4>Services</h4>
    <div>
      <router-link :to="'/project/' + $route.params.project + '/manage/services'"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link><router-link to="/info/services/"><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/service'"><ion-icon
          style="color: var(--ion-color-medium-shade)" name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <ion-menu-toggle auto-hide="false" v-for="(p, i) in services" :key="i">
        <ion-item @click="this.selectedIndex = Number(i) + Number(tools.length) + Number(components.length) + 1"
          lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/services/' + p.link"
          class="hydrated menu-item" :class="{
            selected: this.selectedIndex === Number(i) + Number(tools.length) + Number(components.length) + 1,
          }"><!-- target="_blank"-->
          <ion-icon slot="start" :name="p.icon || 'cog-outline'" />
          <ion-label>{{ p.name }}</ion-label>
          <span class="service-status-indicator"
            :class="{ 'status-up': p.status === 'up', 'status-down': p.status === 'down' }"></span>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
  <ion-note class="projects-headline">
    <h4>APIs</h4>
    <div>
      <!---<router-link to="/manage/projects/"
        ><ion-icon
          style="color: var(--ion-color-medium-shade)"
          name="ellipsis-horizontal-circle-outline" /></router-link
      >--><router-link to="/info/apis/"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="information-circle-outline"></ion-icon></router-link><router-link
        :to="'/project/' + $route.params.project + '/new/api'"><ion-icon style="color: var(--ion-color-medium-shade)"
          name="add-circle-outline"></ion-icon></router-link>
    </div>
  </ion-note>
  <ion-list id="inbox-list">
    <ion-reorder-group :disabled="false" @ionItemReorder="handleFrontReorder($event)">
      <ion-menu-toggle auto-hide="false">
        <ion-item
          @click="this.selectedIndex = Number(tools.length) + Number(components.length) + Number(services.length) + 1"
          lines="none" detail="false" :router-link="'/project/' + $route.params.project + '/apis/weather-api'"
          class="hydrated menu-item" :class="{
            selected: this.selectedIndex === Number(tools.length) + Number(components.length) + Number(services.length) + 1,
          }">
          <ion-icon slot="start" name="cloud-outline" />
          <ion-label>Weather API</ion-label>
          <ion-reorder slot="end">
            <ion-icon style="cursor: pointer; z-index: 1000" name="cog-outline" />
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
</template>

<script lang="ts">
/* eslint-disable */
import { defineComponent, ref } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import { useIonRouter } from "@ionic/vue";
import { layersOutline, gridOutline, documentsOutline } from 'ionicons/icons';

export default defineComponent({
  name: "ProjectSideBar",
  setup() {
    const selectedIndex = ref(0);
    const tools = ref<{ id: number; order: number }[]>([]);
    const components = ref([]);
    const services = ref([]);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    const componentsExpanded = ref(true);
    const expandedComponents = ref<{ [key: string]: boolean }>({});
    const componentSubItems = ref<{ [key: string]: any[] }>({});

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
</style>
