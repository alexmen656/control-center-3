<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1" />
      <ion-col size="10" style="display: flex">
        <div
          v-if="type == 'script'"
          style="
            resize: horizontal;
            overflow: auto;
            width: 20%;
            max-width: 30%;
            min-width: 8%;
          "
        >
          <ion-list lines="inset" class="cmps" ref="el">
            <ion-item v-for="cmp in cmps" :key="cmp">
              <div
                style="
                  display: flex;
                  align-items: center;
                  width: 100%;
                  height: 100%;
                "
                :class="{
                  jc_center: isLabelVisible,
                  jc_space: !isLabelVisible,
                }"
              >
                <ion-icon style="width: 36px; height: 36px" :name="cmp.icon" />
                <ion-label :class="{ label: isLabelVisible }"
                  >{{ cmp.name }} <br />{{ "<" + cmp.tag + "/>" }}</ion-label
                >
              </div>
            </ion-item>
            <ion-item>
              <div
                style="
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  width: 100%;
                  height: 100%;
                "
              >
                <ion-label
                  ><ion-button @click="setOpen(true)"
                    ><ion-icon name="add-outline"></ion-icon
                    ><span :class="{ label: isLabelVisible }"
                      >New</span
                    ></ion-button
                  ></ion-label
                >
              </div>
            </ion-item>
          </ion-list>
        </div>
        <div
          style="
            resize: horizontal;
            overflow: auto;
            display: flex;
            align-items: center;
            flex-direction: column;
            width: 80%;
            min-width: 70%;
            max-width: 92%;
          "
        >
          <code-editor
            v-if="type === 'script'"
            :wrap_code="false"
            style="overflow-y: scroll !important"
            v-model="newHTML"
            width="100%"
            max_height="600px"
            :language_selector="true"
            :languages="[
              ['javascript', 'JS'],
              ['python', 'Python'],
              ['html', 'HTML'],
            ]"
          />
          <ion-button
            v-if="type === 'script'"
            @click="saveHTML()"
            color="primary"
            >Save Component</ion-button
          >
          <img
            v-if="type === 'image'"
            :src="'https://alex.polan.sk/' + newHTML"
            alt="Image"
          />
          <ion-list style="width: 100%" v-if="type === 'menu'">
            <ion-reorder-group
              :disabled="false"
              @ionItemReorder="handleReorder($event)"
            >
              <ion-item-sliding v-for="(item, index) in items" :key="item">
                <ion-item>
                  <ion-label> {{ item.name }} </ion-label>
                  <ion-reorder slot="end"></ion-reorder>
                </ion-item>
                <ion-item-options>
                  <ion-item-option @click="deletee(index)"
                    >Delete</ion-item-option
                  >
                </ion-item-options>
              </ion-item-sliding>
            </ion-reorder-group>

            <ion-item>
              <ion-input
                v-model="item"
                :value="item"
                placeholder="Add new Item"
              />
              <ion-button @click="newItem" slot="end">Add</ion-button>
            </ion-item>
          </ion-list>
          <!--<code-editor
            v-if="type === 'menu'"
            :wrap_code="false"
            style="overflow-y: scroll !important"
            v-model="newHTML"
            width="100%"
            max_height="600px"
            :languages="[
              ['json', 'JSON'],
            ]"
          />-->

          <span v-if="dateTimeString && component.last_change_by"
            >Last Change: {{ dateTimeString }} by
            {{ component.last_change_by }}</span
          ><span v-if="createdOn">Created: {{ createdOn }}</span>
        </div>
      </ion-col>
      <ion-col size="1" />
    </ion-row>
  </ion-grid>
  <ion-modal :is-open="isOpen" ref="modal">
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-button style="color: red" @click="setOpen(false)"
            >Cancel</ion-button
          >
        </ion-buttons>
        <ion-title style="text-align: center">Create Component</ion-title>
        <ion-buttons slot="end">
          <ion-button style="color: red" :strong="true" @click="confirm()"
            >Confirm</ion-button
          >
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <FloatingSelect v-model="cmp" :select="select" />
      <!-- <div v-if="cmp">
        <div v-for="(input, index) in components[cmp].inputs" :key="input.name">
          <FloatingSelect
            v-model="inputValues[index]"
            :select="input"
            v-if="input.type == 'select'"
          />
          <FloatingCheckbox
            v-model="inputValues[index]"
            :label="input.label"
            v-if="input.type == 'checkbox'"
          />
          <FloatingInput
            v-if="input.type != 'select' && input.type != 'checkbox'"
            v-model="inputValues[index]"
            :label="input.label"
            :placeholder="input.placeholder"
            :type="input.type"
          />
        </div>
      </div>-->
    </ion-content>
  </ion-modal>
</template>

<script lang="ts">
import axios from "axios";
import qs from "qs";
//import { useRoute } from "vue-router";
import CodeEditor from "simple-code-editor";
import {
  IonButton,
  IonGrid,
  IonRow,
  IonCol,
  IonModal,
  IonItem,
  IonLabel,
  IonList,
  IonReorder,
  IonReorderGroup,
  IonHeader,
  IonToolbar,
  IonButtons,
  IonIcon,
  IonTitle,
  IonContent,
  IonInput,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import { useElementSize } from "@vueuse/core";
import { useRoute } from "vue-router";
//import { trophyOutline } from "ionicons/icons";

export default defineComponent({
  name: "ComponentsView",
  components: {
    CodeEditor,
    IonButton,
    IonGrid,
    IonRow,
    IonCol,
    IonModal,
    FloatingSelect,
    IonItem,
    IonLabel,
    IonList,
    IonReorder,
    IonReorderGroup,
    IonHeader,
    IonToolbar,
    IonButtons,
    IonIcon,
    IonTitle,
    IonContent,
    IonInput,
    IonItemSliding,
    IonItemOptions,
    IonItemOption,
  },
  setup() {
    const isOpen = ref(false);
    const items = ref([]);
    const style = ref({});
    const route = useRoute();
    const item = ref("");
    const el = ref(null);
    const { width, height } = useElementSize(el);
    const setOpen = (open) => {
      isOpen.value = open;
    };

    try {
      axios
        .post(
          "/control-center/components.php",
          qs.stringify({
            getCoponent: "getCoponent",
            project: route.params.project,
            name: route.params.component,
          })
        )
        .then((response) => {
          if (response.data.type === "menu") {
            items.value = response.data.content.content;
            style.value = response.data.content.style;
          }
        });
    } catch (error) {
      console.error("Error fetching component data:", error);
    }

    const submit = (items) => {
      axios
        .post(
          "/control-center/components.php",
          qs.stringify({
            updateHTML: "updateHTML",
            project: route.params.project,
            name: route.params.component,
            html: JSON.stringify({content: items.value, style: style.value}),
          })
        )
        .then(() => {
          console.log("Updated successfully");
        })
        .catch((error) => {
          console.error("Error saving component HTML:", error);
        });
    };

    const handleReorder = (event) => {
      items.value = event.detail.complete(items.value);
      submit(items);
    };

    const newItem = () => {
      items.value.push({ name: item.value });
      submit(items);
      item.value = "";
    };

    const deletee = (index) => {
      const newItems = [];
      let i = 0;
      items.value.forEach((item) => {
        if (i != index) {
          newItems.push(item);
        }

        i++;
      });
      items.value = newItems;
      submit(items);
    };

    return {
      isOpen,
      setOpen,
      el,
      width,
      height,
      handleReorder,
      items,
      newItem,
      item,
      deletee
    };
  },

  data() {
    return {
      isResizable: false, // Überwacht die Größe der Sidebar
      isLabelVisible: false, // Überwacht, ob das Label sichtbar sein soll
      inputValues: [],
      cmp: "",
      component: {},
      newHTML: "",
      type: "",
      dateTimeString: "",
      createdOn: "",
      cmps: [],
      select: {
        type: "select",
        name: "component",
        label: "Component",
        placeholder: "Component",
        options: [
          {
            value: "google_maps",
            label: "Google Maps",
            icon: "location-outline",
          },
          {
            value: "slider",
            label: "Image Slider",
            icon: "image-outline",
          },
          {
            value: "navbar",
            label: "Navbar",
            icon: "menu-outline",
          },
          {
            value: "footer",
            label: "Footer",
            icon: "footsteps-outline",
          },
          {
            value: "searchbar",
            label: "Searchbar",
            icon: "search-circle-outline",
          },
        ],
      },
      components: {
        slider: {
          inputs: [
            {
              type: "text",
              name: "image_url",
              label: "Image Url",
              placeholder: "https://example.com/my-image.jpg",
              options: [],
            },
            {
              type: "text",
              name: "image_description",
              label: "Image Description",
              placeholder: "My Image",
              options: [],
            },
            { type: "text", name: "", label: "", placeholder: "", options: [] },
          ],
        },
      },
    };
  },
  async mounted() {
    if (this.type == "script") {
      this.$watch(
        () => this.width,
        () => {
          this.updateSidebarSize();
        }
      );

      this.updateSidebarSize();
    }
    await this.getComponentData();
  },

  beforeUnmount() {
    if (this.type == "script") {
      document
        .querySelector(".cmps")
        .removeEventListener("resize", this.updateSidebarSize);
    }
  },
  methods: {
    updateSidebarSize() {
      const sidebarElement = document.querySelector<HTMLElement>(".cmps");
      if (sidebarElement) {
        const sidebarWidth = sidebarElement.offsetWidth;
        if (sidebarWidth <= 100) {
          this.isLabelVisible = true;
        } else {
          this.isLabelVisible = false;
        }
      }
    },
    confirm() {
      this.setOpen(false);
      this.cmps.push({
        name: this.select.options.find(({ value }) => value == this.cmp).label,
        tag: this.select.options
          .find(({ value }) => value == this.cmp)
          .value.replaceAll("_", "-"),
        icon: this.select.options.find(({ value }) => value == this.cmp).icon,
      });
    },
    async getComponentData() {
      try {
        const response = await axios.post(
          "/control-center/components.php",
          qs.stringify({
            getCoponent: "getCoponent",
            project: this.$route.params.project,
            name: this.$route.params.component,
          })
        );

        this.component = response.data;
        this.newHTML = this.component.content;
        this.type = this.component.type;
        if (this.type != "menu") {
          this.newHTML = this.newHTML.trim();
        }

        this.updateDateTimeString();
      } catch (error) {
        console.error("Error fetching component data:", error);
      }
    },
    saveHTML() {
      const html = this.newHTML.trim();
      axios
        .post(
          "/control-center/components.php",
          qs.stringify({
            updateHTML: "updateHTML",
            project: this.$route.params.project,
            name: this.$route.params.component,
            html: html,
          }),
          {
            headers: {
              "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            },
          }
        )
        .then(() => {
          this.getComponentData();
        })
        .catch((error) => {
          console.error("Error saving component HTML:", error);
        });
    },
    updateDateTimeString() {
      const lastChangeDate = new Date(this.component.last_change);

      if (lastChangeDate) {
        //!= "Invalid Date"
        const formattedDate = lastChangeDate
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedTime = lastChangeDate.toLocaleTimeString("en-GB");
        this.dateTimeString = formattedDate + " at " + formattedTime;
      }

      const createdOn = new Date(this.component.createdOn);

      if (createdOn) {
        //!= "Invalid Date"
        const formattedCreatedOnDate = createdOn
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedCreatedOnTime = createdOn.toLocaleTimeString("en-GB");
        this.createdOn =
          formattedCreatedOnDate + " at " + formattedCreatedOnTime;
      } else {
        this.createdOn = "Not Avaible";
      }
    },
  },
});
</script>

<style>
textarea {
  overflow-y: scroll !important;
}

.code_editor {
  min-height: 300px;
}

img {
  height: 100%;
}

.label {
  display: none !important;
}

div::-webkit-resizer {
  background-image: url("https://unpkg.com/ionicons@7.1.0/dist/svg/move-outline.svg");
}

.jc_space {
  justify-content: space-between;
}

.jc_center {
  justify-content: center;
}
</style>
