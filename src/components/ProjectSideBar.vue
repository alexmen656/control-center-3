<template>
  <ion-list id="inbox-list">
    <ion-reorder-group
      :disabled="false"
      @ionItemReorder="handleReorder($event)"
    >
      <ion-menu-toggle auto-hide="false" v-for="(p, i) in tools" :key="i">
        <ion-item
          @dblclick="
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
          "
          @click="this.selectedIndex = i"
          lines="none"
          detail="false"
          :router-link="
            p.icon == 'bar-chart-outline'
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
          "
          class="hydrated menu-item"
          :class="{ selected: this.selectedIndex === i }"
        >
          <ion-icon slot="start" :name="p.icon"/>
          <ion-label
            >{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label
          >
          <ion-reorder slot="end">
            <ion-icon
              v-if="p.hasConfig == 1"
              style="cursor: pointer; z-index: 1000"
              name="cog-outline"
            />
            <pre v-else></pre>
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" style="margin-top: 1rem !important">
        <ion-item
          lines="none"
          detail="false"
          class="new-tool"
          :router-link="'/project/' + $route.params.project + '/new-tool/'"
        >
          <ion-icon slot="start" name="add"/>
          <ion-label>New Tool</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>

  <ion-list id="inbox-list">
    <ion-reorder-group
      :disabled="false"
      @ionItemReorder="handleFrontReorder($event)"
    >
      <ion-menu-toggle auto-hide="false" v-for="(p, i) in components" :key="i">
        <ion-item
          @dblclick="
            goToConfig(
              '/project/'+$route.params.project +'/components/'+
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
          "
          @click="this.selectedIndex = Number(i) + Number(tools.length)"
          lines="none"
          detail="false"
          :router-link="
            '/project/' +
            $route.params.project +
            '/components/' +
            p.name
              .toLowerCase()
              .replaceAll(' ', '-')
              .replaceAll('ä', 'a')
              .replaceAll('Ä', 'a')
              .replaceAll('ö', 'o')
              .replaceAll('Ö', 'o')
              .replaceAll('Ü', 'u')
              .replaceAll('ü', 'u')
          "
          class="hydrated menu-item"
          :class="{
            selected: this.selectedIndex === Number(i) + Number(tools.length),
          }"
        >
          <ion-icon slot="start" :name="getIcon(p.type)" />
          <ion-label
            >{{ p.name[0].toUpperCase() }}{{ p.name.substring(1) }}</ion-label
          >
          <ion-reorder slot="end">
            <ion-icon
              v-if="p.hasConfig == 1 || p.type == 'menu'"
              style="cursor: pointer; z-index: 1000"
              name="cog-outline"
            />
            <pre v-else></pre>
          </ion-reorder>
        </ion-item>
      </ion-menu-toggle>
      <ion-menu-toggle auto-hide="false" style="margin-top: 1rem !important">
        <ion-item
          lines="none"
          detail="false"
          class="new-tool"
          :router-link="'/project/' + $route.params.project + '/new/component'"
        >
          <ion-icon slot="start" name="add"/>
          <ion-label>New Component</ion-label>
        </ion-item>
      </ion-menu-toggle>
    </ion-reorder-group>
  </ion-list>
</template>

<script lang="ts">
/* eslint-disable */
import {
  IonIcon,
  IonItem,
  IonLabel,
  IonList,
  IonMenuToggle,
  IonReorder,
  IonReorderGroup,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import { useIonRouter } from "@ionic/vue";

export default defineComponent({
  name: "ProjectSideBar",
  components: {
    IonIcon,
    IonItem,
    IonLabel,
    IonMenuToggle,
    IonList,
    IonReorder,
    IonReorderGroup,
  },
  setup() {
    const selectedIndex = ref(0);
    const tools = ref<{ id: number; order: number }[]>([]);

    const components = ref([]);
    const route = useRoute();
    const ionRouter = useIonRouter();
    const list = {} as any;
    //const element = {} as any;

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
          axios.post(
            "https://alex.polan.sk/control-center/test.php",
            qs.stringify({ list: JSON.stringify(list) })
          );
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
          axios.post(
            "https://alex.polan.sk/control-center/test.php",
            qs.stringify({ list: JSON.stringify(list) })
          );
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
      .get(
        "https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=" +
          route.params.project
      )
      .then((response) => {
        tools.value = response.data.tools;
        components.value = response.data.components;
        //console.log(components);
        tools.value.forEach((element) => {
          // console.log(element.id);

          list[element.id] = element.order;
        });
        //console.log(list);
      });
    // console.log(tools);

    function goToConfig(route: string) {
      //console.log("Go to settings");
      ionRouter.push(route);
    }

    return {
      tools: tools,
      selectedIndex,
      components: components,
      goToConfig,
      handleReorder,
      handleFrontReorder,
    };
  },
  created() {
    this.emitter.on("updateSidebar", () => {
      axios
        .get(
          "https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=" +
            this.$route.params.project
        )
        .then((response) => {
          this.tools = response.data.tools;
          this.components = response.data.components;
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
</style>
