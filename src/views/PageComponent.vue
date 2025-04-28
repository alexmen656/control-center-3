<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1" />
      <ion-col size="10" style="display: flex">
        <!--<div
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
              <ion-item v-for="component in components" :key="component.component_id">
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
                  <ion-icon style="width: 36px; height: 36px" :name="getComponentIcon(component)" />
                  <ion-label :class="{ label: isLabelVisible }">
                    {{ getComponentName(component) }} <br />
                    {{ "Position: " + component.position }}
                  </ion-label>
                </div>
                <ion-buttons slot="end">
                  <ion-button color="primary" @click="editComponent(component)">
                    <ion-icon name="create-outline"></ion-icon>
                  </ion-button>
                  <ion-button color="danger" @click="deleteComponent(component.component_id)">
                    <ion-icon name="trash-outline"></ion-icon>
                  </ion-button>
                </ion-buttons>
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
                    ><ion-button @click="openNewComponentModal()"
                      ><ion-icon name="add-outline"></ion-icon
                      ><span :class="{ label: isLabelVisible }"
                        >New</span
                      ></ion-button
                    ></ion-label
                  >
                </div>
              </ion-item>
            </ion-list>
          </div>  -->
        <div style="
              resize: horizontal;
              overflow-y: auto;
              display: flex;
              align-items: center;
              flex-direction: column;
              width: 100%;
              margin-top: .5rem;
              height: 85vh; /* Add fixed height to enable scrolling */
            ">
          <!--
                    min-width: 70%;
              max-width: 92%;-->
          <code-editor v-if="type === 'script' && currentComponent" :wrap_code="false"
            style="overflow-y: hidden !important" v-model="currentHtml" width="100%" max-height="800px" border-radius="16px"
            :language_selector="true" :languages="[
              /* ['javascript', 'JS'],
               ['python', 'Python'], */
              ['html', 'HTML'],
            ]" />
          <div style="display: flex; justify-content: space-between; width: 100%;">
            <ion-button v-if="type === 'script' && currentComponent" @click="openWebBuilder()" color="tertiary">
              <ion-icon name="globe-outline" slot="start"></ion-icon>
              Open in Web Builder
            </ion-button>
            <ion-button v-if="type === 'script' && currentComponent" @click="saveComponentHtml()" color="primary">Save
              Component</ion-button>
          </div>
          <ion-button v-if="type === 'script' && !currentComponent" @click="openNewComponentModal()"
            color="primary">Create New Component</ion-button>
          <img v-if="type === 'image'" :src="'https://alex.polan.sk/' + currentHtml" alt="Image" />
          <ion-list style="width: 100%" v-if="type === 'menu'">
            <ion-reorder-group :disabled="false" @ionItemReorder="handleReorder($event)">
              <ion-item-sliding v-for="(item, index) in items" :key="item">
                <ion-item>
                  <ion-label> {{ item.name }} </ion-label>
                  <ion-reorder slot="end"></ion-reorder>
                </ion-item>
                <ion-item-options>
                  <ion-item-option @click="deletee(index)">Delete</ion-item-option>
                </ion-item-options>
              </ion-item-sliding>
            </ion-reorder-group>

            <ion-item>
              <ion-input v-model="item" :value="item" placeholder="Add new Item" />
              <ion-button @click="newItem" slot="end">Add</ion-button>
            </ion-item>
          </ion-list>

          <span v-if="currentComponent && currentComponent.updated_at">
            Last Change: {{ formatDate(currentComponent.updated_at) }}
          </span>
          <span v-if="currentComponent && currentComponent.created_at">
            Created: {{ formatDate(currentComponent.created_at) }}
          </span>
        </div>
      </ion-col>
      <ion-col size="1" />
    </ion-row>
  </ion-grid>
  <ion-modal :is-open="isOpen" ref="modal">
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-button style="color: red" @click="setOpen(false)">Cancel</ion-button>
        </ion-buttons>
        <ion-title style="text-align: center">Create Component</ion-title>
        <ion-buttons slot="end">
          <ion-button style="color: red" :strong="true" @click="createNewComponent()">Confirm</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <FloatingSelect v-model="selectedTemplate" :select="templateSelect" />
      <div v-if="selectedTemplate === 'custom'">
        <code-editor :wrap_code="false" style="overflow-y: scroll !important" v-model="newComponentHtml" width="100%"
          height="300px" :language_selector="false" language="html" />
      </div>
      <div v-else-if="selectedTemplate && templateList.length > 0">
        <ion-card>
          <ion-card-header>
            <ion-card-title>{{ getSelectedTemplateName() }}</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <p>{{ getSelectedTemplatePreview() }}</p>
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  </ion-modal>
</template>

<script lang="ts">
import axios from "axios";
import qs from "qs";
import CodeEditor from "simple-code-editor";
import { defineComponent, ref } from "vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import { useElementSize } from "@vueuse/core";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "PageComponentView",
  components: {
    CodeEditor,
    FloatingSelect,
  },
  setup() {
    const isOpen = ref(false);
    const items = ref([]);
    const style = ref({});
    const route = useRoute();
    const item = ref("");
    const el = ref(null);
    const { width, height } = useElementSize(el);
    const components = ref([]);
    const currentComponent = ref(null);
    const currentHtml = ref("");

    const setOpen = (open) => {
      isOpen.value = open;
    };

    try {
      axios
        .post(
          "web_components.php",
          qs.stringify({
            getComponentsByPage: true,
            page: route.params.page,
            project: route.params.project,
          })
        )
        .then((response) => {
          if (response.data.success && response.data.components) {
            components.value = response.data.components;
            // Set initial component if there are any
            if (components.value.length > 0) {
              currentComponent.value = components.value[0];
              currentHtml.value = components.value[0].html_code;
            }
          } else {
            console.error("No components found or error:", response.data.message);
          }
        });
    } catch (error) {
      console.error("Error fetching components:", error);
    }

    const submit = (items) => {
      axios
        .post(
          "web_components.php",
          qs.stringify({
            updateHTML: true,
            component_id: currentComponent.value.component_id,
            page: route.params.page,
            project: route.params.project,
            html: JSON.stringify({ content: items.value, style: style.value }),
          })
        )
        .then(() => {
          console.log("Updated successfully");
        })
        .catch((error) => {
          console.error("Error updating component HTML:", error);
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
      deletee,
      components,
      currentComponent,
      currentHtml
    };
  },

  data() {
    return {
      isResizable: false,
      isLabelVisible: false,
      selectedTemplate: "",
      newComponentHtml: "<div class=\"my-component\">\n  <h2>New Component</h2>\n  <p>This is a new custom component.</p>\n</div>",
      type: "script",
      templateList: [],
      templateSelect: {
        type: "select",
        name: "template",
        label: "Template",
        placeholder: "Select a template",
        options: [
          {
            value: "custom",
            label: "Custom HTML",
            icon: "code-slash-outline"
          }
          // Will be populated with templates from the API
        ]
      }
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

    await this.loadComponents();
    await this.loadTemplates();
  },

  beforeUnmount() {
    if (this.type == "script") {
      document
        .querySelector(".cmps")?.removeEventListener("resize", this.updateSidebarSize);
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
    getComponentName(component) {
      return component.template_title || `Component ${component.position + 1}`;
    },
    getComponentIcon(component) {
      // Default icon or based on component content
      if (component.html_code && component.html_code.includes("<img")) {
        return "image-outline";
      } else if (component.html_code && component.html_code.includes("<nav")) {
        return "menu-outline";
      } else if (component.html_code && component.html_code.includes("<footer")) {
        return "footsteps-outline";
      }
      return "cube-outline";
    },
    formatDate(dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return "Invalid date";

      const formattedDate = date.toLocaleDateString("en-GB").split("/").join(".");
      const formattedTime = date.toLocaleTimeString("en-GB");
      return formattedDate + " at " + formattedTime;
    },
    async loadComponents() {
      try {
        const response = await axios.post(
          "web_components.php",
          qs.stringify({
            getComponentsByPage: true,
            page: this.$route.params.page,
            project: this.$route.params.project,
          })
        );

        if (response.data.success && response.data.components) {
          this.components = response.data.components;

          // Set initial component if there are any
          if (this.components.length > 0) {
            this.currentComponent = this.components[0];
            this.currentHtml = this.components[0].html_code;
          }
        }
      } catch (error) {
        console.error("Error loading components:", error);
      }
    },
    async loadTemplates() {
      try {
        const response = await axios.post(
          "web_components.php",
          qs.stringify({
            getTemplates: true
          })
        );

        if (response.data.success && response.data.templates) {
          this.templateList = response.data.templates;

          // Update template select options
          const templateOptions = [
            {
              value: "custom",
              label: "Custom HTML",
              icon: "code-slash-outline"
            }
          ];

          this.templateList.forEach(template => {
            templateOptions.push({
              value: template.id.toString(),
              label: template.title,
              icon: "document-text-outline"
            });
          });

          this.templateSelect.options = templateOptions;
        }
      } catch (error) {
        console.error("Error loading templates:", error);
      }
    },
    editComponent(component) {
      this.currentComponent = component;
      this.currentHtml = component.html_code;
    },
    async deleteComponent(componentId) {
      try {
        const confirmation = await this.$ionicController.alertController.create({
          header: "Confirm Deletion",
          message: "Are you sure you want to delete this component?",
          buttons: [
            {
              text: "Cancel",
              role: "cancel"
            },
            {
              text: "Delete",
              role: "destructive",
              handler: async () => {
                try {
                  const response = await axios.post(
                    "web_components.php",
                    qs.stringify({
                      deleteComponent: true,
                      component_id: componentId,
                      page: this.$route.params.page,
                      project: this.$route.params.project,
                    })
                  );

                  if (response.data.success) {
                    // Reload components
                    await this.loadComponents();

                    if (this.currentComponent && this.currentComponent.component_id === componentId) {
                      this.currentComponent = this.components.length > 0 ? this.components[0] : null;
                      this.currentHtml = this.currentComponent ? this.currentComponent.html_code : "";
                    }
                  } else {
                    console.error("Error deleting component:", response.data.message);
                  }
                } catch (error) {
                  console.error("Error deleting component:", error);
                }
              }
            }
          ]
        });

        await confirmation.present();
      } catch (error) {
        console.error("Error showing confirmation dialog:", error);
      }
    },
    async saveComponentHtml() {
      if (!this.currentComponent) return;

      try {
        const response = await axios.post(
          "web_components.php",
          qs.stringify({
            updateHTML: true,
            component_id: this.currentComponent.component_id,
            page: this.$route.params.page,
            project: this.$route.params.project,
            html: this.currentHtml,
          })
        );

        if (response.data.success) {
          // Update the component in the local array
          this.currentComponent.html_code = this.currentHtml;

          // Show success toast
          const toast = await this.$ionicController.toastController.create({
            message: "Component updated successfully!",
            duration: 2000,
            color: "success"
          });
          await toast.present();
        } else {
          console.error("Error saving component HTML:", response.data.message);
        }
      } catch (error) {
        console.error("Error saving component HTML:", error);
      }
    },
    openNewComponentModal() {
      this.selectedTemplate = "custom";
      this.newComponentHtml = "<div class=\"my-component\">\n  <h2>New Component</h2>\n  <p>This is a new custom component.</p>\n</div>";
      this.setOpen(true);
    },
    getSelectedTemplateName() {
      if (this.selectedTemplate === "custom") return "Custom HTML";
      const template = this.templateList.find(t => t.id.toString() === this.selectedTemplate);
      return template ? template.title : "Unknown Template";
    },
    getSelectedTemplatePreview() {
      if (this.selectedTemplate === "custom") return this.newComponentHtml;
      const template = this.templateList.find(t => t.id.toString() === this.selectedTemplate);
      return template ? (template.html_code.length > 100 ? template.html_code.substring(0, 100) + "..." : template.html_code) : "";
    },
    async createNewComponent() {
      try {
        let htmlContent = "";

        if (this.selectedTemplate === "custom") {
          htmlContent = this.newComponentHtml;
        } else {
          const template = this.templateList.find(t => t.id.toString() === this.selectedTemplate);
          if (template) {
            htmlContent = template.html_code;
          } else {
            console.error("Selected template not found");
            return;
          }
        }

        const response = await axios.post(
          "web_components.php",
          qs.stringify({
            newComponent: true,
            page: this.$route.params.page,
            project: this.$route.params.project,
            html: htmlContent,
            template_id: this.selectedTemplate !== "custom" ? this.selectedTemplate : null
          })
        );

        if (response.data.success) {
          // Close modal and refresh components
          this.setOpen(false);
          await this.loadComponents();

          // Select the new component
          const newComponentId = response.data.component_id;
          const newComponent = this.components.find(c => c.component_id === newComponentId);
          if (newComponent) {
            this.currentComponent = newComponent;
            this.currentHtml = newComponent.html_code;
          }

          // Show success toast
          const toast = await this.$ionicController.toastController.create({
            message: "Component created successfully!",
            duration: 2000,
            color: "success"
          });
          await toast.present();
        } else {
          console.error("Error creating component:", response.data.message);
        }
      } catch (error) {
        console.error("Error creating component:", error);
      }
    },
    openWebBuilder() {
      // Benutze die Projekt- und Seiten-Parameter, um zur entsprechenden Seite im Web Builder zu navigieren
      const project = this.$route.params.project;
      const page = this.$route.params.page;
      
      // Konstruiere den URL für den Web Builder
      const url = `https://web-builder.control-center.eu/editor/${project}/${page}`;
      
      // Öffne in einem neuen Tab
      window.open(url, '_blank');
      
      // Zeige Bestätigung
      this.$ionicController.toastController.create({
        message: "Opening Web Builder in a new tab",
        duration: 2000,
        position: "bottom",
        color: "tertiary"
      }).then(toast => toast.present());
    },
  }
});
</script>

<style>
.code-area, textarea, pre {
  overflow-y: scroll !important;
  height: 100% !important;
}

.code-area > pre, .code-area > textarea {
  padding-bottom: 50px !important;
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
