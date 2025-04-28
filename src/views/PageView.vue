<template>
 <ion-page>
  <ion-content>
  <ion-grid>
    <ion-row>
      <ion-col size="1" />
      <ion-col size="10" style="display: flex; flex-direction: column;">
        <!-- Page Metadata Card -->
        <ion-card style="width: 100%; margin-bottom: 1rem;">
          <ion-card-header>
            <ion-card-title>{{page.name }} <!--page.title || --><ion-note>{{ page.is_home ? '(Homepage)' : '' }}</ion-note>
            </ion-card-title>
            <!--<ion-card-subtitle>{{ page.is_home ? '(Homepage)' : '' }}</ion-card-subtitle>-->
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item>
                <ion-label>
                 <!-- <h2>Page Details</h2>-->
                  <p class="page-metadata"><strong>Name:</strong> {{ page.name }}</p>
                  <p class="page-metadata"><strong>Slug:</strong> {{ page.slug }}</p>
                  <p class="page-metadata"><strong>Meta Title:</strong> {{ page.title || 'None' }}</p>
                  <p class="page-metadata"><strong>Meta Description:</strong> {{ page.meta_description || 'None' }}</p>
                </ion-label>
              </ion-item>
            <!--  <ion-item-divider>-->
              <!-- <ion-label>Components ({{ page.components ? page.components.length : 0 }})</ion-label>--> 
             <!-- </ion-item-divider>-->
             
              <ion-reorder-group :disabled="!isComponentReorderingEnabled" @ionItemReorder="handleComponentReorder($event)">
                <ion-item-sliding v-if="page.components && page.components.length > 0" 
                        v-for="(component, index) in page.components" 
                        :key="component.component_id">
                  <ion-item
                        :button="true"
                        @click="selectComponent(component)">
                    <div class="component-icon-container">
                      <ion-icon name="code-outline" class="component-icon"></ion-icon>
                    </div>
                    <ion-label>
                      <h3>{{ getComponentName(component, index) }}</h3>
                      <p>ID: {{ component.component_id }}</p>
                      <p>Position: {{ component.position }}</p>
                    </ion-label>
                    <ion-badge v-if="currentComponentId === component.component_id" color="primary" slot="end">Selected</ion-badge>
                    <ion-reorder slot="end"></ion-reorder>
                  </ion-item>
                  <ion-item-options side="end">
                    <ion-item-option color="danger" @click="confirmDeleteComponent(component)">
                      <ion-icon slot="icon-only" name="trash-outline"></ion-icon>
                    </ion-item-option>
                  </ion-item-options>
                </ion-item-sliding>
                <ion-item v-if="!page.components || page.components.length === 0">
                  <ion-label color="medium">
                    <p>No components available</p>
                  </ion-label>
                </ion-item>
              </ion-reorder-group>
              
              <!-- Component Management Buttons -->
              <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                <ion-button size="small" fill="outline" @click="toggleComponentReordering">
                  <ion-icon :name="isComponentReorderingEnabled ? 'checkmark-outline' : 'reorder-three-outline'" slot="start"></ion-icon>
                  {{ isComponentReorderingEnabled ? 'Done Reordering' : 'Reorder Components' }}
                </ion-button>
                <div>
                  <ion-button size="small" color="tertiary" @click="openWebBuilder()">
                    <ion-icon name="globe-outline" slot="start"></ion-icon>
                    Open in Web Builder
                  </ion-button>
                  <ion-button size="small" color="primary" @click="openNewComponentModal()">
                    <ion-icon name="add-circle-outline" slot="start"></ion-icon>
                    Add Component
                  </ion-button>
                </div>
              </div>
            </ion-list>
          </ion-card-content>
        </ion-card>
        
        <!-- Component Details Card -->
        <ion-card v-if="currentComponent" style="width: 100%; margin-bottom: 1rem;">
          <ion-card-header>
            <ion-card-title>Component Details</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div style="
              resize: horizontal;
              overflow-y: auto;
              display: flex;
              align-items: center;
              flex-direction: column;
              width: 100%;
              margin-top: .5rem;
              /* Remove fixed height to enable scrolling */
            ">
              <pre style="white-space: pre-wrap; overflow-wrap: break-word; max-height: 400px; overflow-y: auto;">{{ currentComponentHTML }}</pre>
            </div>
          </ion-card-content>
        </ion-card>
        
        <!-- Rest of template content... -->
        <div v-if="type === 'menu'" style="width: 100%;">
          <ion-list style="width: 100%">
            <ion-reorder-group :disabled="false" @ionItemReorder="handleReorder($event)">
              <ion-item-sliding v-for="(item, index) in items" :key="item">
                <ion-item>
                  <ion-label>{{ item.name }}</ion-label>
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
        </div>

        <div style="width: 100%; text-align: center; margin-top: 20px;">
          <span v-if="dateTimeString && page.last_change_by">Last Change: {{ dateTimeString }} by
            {{ page.last_change_by }}</span>
          <span v-if="createdOn">Created: {{ createdOn }}</span>
        </div>
      </ion-col>
      <ion-col size="1" />
    </ion-row>
  </ion-grid>
  
  <!-- Component-Deletion Alert -->
  <ion-alert
    :is-open="isDeleteComponentAlertOpen"
    header="Delete Component"
    message="Are you sure you want to delete this component? This action cannot be undone."
    :buttons="[
      {
        text: 'Cancel',
        role: 'cancel',
        handler: () => { isDeleteComponentAlertOpen = false; }
      },
      {
        text: 'Delete',
        role: 'confirm',
        handler: () => { deleteComponent(); }
      }
    ]"
  ></ion-alert>
  
  <ion-modal :is-open="isOpen" ref="modal">
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-button style="color: red" @click="setOpen(false)">Cancel</ion-button>
        </ion-buttons>
        <ion-title style="text-align: center">Add Component from Template</ion-title>
        <ion-buttons slot="end">
          <ion-button style="color: red" :strong="true" @click="confirmNewComponent()">Add</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list v-if="isNewComponentModal">
        <ion-radio-group v-model="selectedTemplateId">
          <ion-list-header>
            <ion-label>Select Template</ion-label>
          </ion-list-header>
          
          <ion-item v-for="template in availableTemplates" :key="template.id">
            <ion-label>
              <h2>{{ template.title }}</h2>
            </ion-label>
            <ion-radio slot="end" :value="template.id"></ion-radio>
          </ion-item>
        </ion-radio-group>
      </ion-list>
    </ion-content>
  </ion-modal>
</ion-content>
</ion-page>
</template>

<script lang="ts">
import axios from "axios";
import qs from "qs";
import { defineComponent, ref } from "vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import { useElementSize } from "@vueuse/core";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "PagesView",
  components: {
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
    const setOpen = (open) => {
      isOpen.value = open;
    };
    
    try {
      axios
        .post(
          "web_pages.php",
          qs.stringify({
            getPage: "getPage",
            project: route.params.project,
            name: route.params.page,
          })
        )
        .then((response) => {
          if (response.data.success && response.data.page) {
            if (response.data.page.type === "menu") {
              try {
                const content = JSON.parse(response.data.page.components[0].html_code);
                items.value = content.content || [];
                style.value = content.style || {};
              } catch (e) {
                console.error("Error parsing menu JSON:", e);
                items.value = [];
                style.value = {};
              }
            }
          }
        });
    } catch (error) {
      console.error("Error fetching page data:", error);
    }

    const submit = (items) => {
      axios
        .post(
          "web_pages.php",
          qs.stringify({
            updateHTML: "updateHTML",
            project: route.params.project,
            name: route.params.page,
            html: JSON.stringify({ content: items.value, style: style.value }),
          })
        )
        .then((response) => {
          if (response.data.success) {
            console.log("Updated successfully");
          } else {
            console.error("Error updating page:", response.data.message);
          }
        })
        .catch((error) => {
          console.error("Error saving page HTML:", error);
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
      isResizable: false,
      isLabelVisible: false,
      inputValues: [],
      cmp: "",
      page: {},
      currentComponent: null,
      currentComponentHTML: "",
      type: "",
      dateTimeString: "",
      createdOn: "",
      currentComponentId: null,
      cmps: [],
      Pages: {
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
      isNewComponentModal: false,
      newComponentHTML: "",
      availableTemplates: [],
      selectedTemplateId: null,
      isComponentReorderingEnabled: false,
      isDeleteComponentAlertOpen: false,
      componentToDelete: null,
    };
  },
  async mounted() {
    await this.getPageData();
    await this.fetchTemplates();
  },

  beforeUnmount() {
    if (this.type == "script") {
      document
        .querySelector(".cmps")
        ?.removeEventListener("resize", this.updateSidebarSize);
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
    confirmNewComponent() {
      this.setOpen(false);
      
      if (this.selectedTemplateId) {
        // Sende Anfrage an Backend zum Erstellen einer neuen Komponente aus Template
        axios.post(
          "web_pages.php",
          qs.stringify({
            addComponentFromTemplate: "addComponentFromTemplate",
            project: this.$route.params.project,
            page: this.$route.params.page,
            template_id: this.selectedTemplateId
          })
        ).then((response) => {
          if (response.data.success) {
            console.log("Component added successfully");
            // Nach dem Speichern die Seite neu laden, um die neue Komponente zu sehen
            this.getPageData();
            
            // Toast/Notification anzeigen
            this.$ionic.toastController
              .create({
                message: "Component created successfully!",
                duration: 2000,
                position: "bottom",
                color: "success"
              })
              .then(toast => toast.present());
              
            // Sidebar aktualisieren
            this.emitter.emit("updateSidebar");
          } else {
            console.error("Error adding component:", response.data.message);
            
            // Fehlermeldung anzeigen
            this.$ionic.toastController
              .create({
                message: "Error creating component: " + (response.data.message || "Unknown error"),
                duration: 3000,
                position: "bottom",
                color: "danger"
              })
              .then(toast => toast.present());
          }
        }).catch((error) => {
          console.error("Error saving component:", error);
          
          // Fehlermeldung anzeigen
          this.$ionic.toastController
            .create({
              message: "Error creating component. Please try again.",
              duration: 3000,
              position: "bottom",
              color: "danger"
            })
            .then(toast => toast.present());
        });
        
        // Zurücksetzen des ausgewählten Templates
        this.selectedTemplateId = null;
        this.isNewComponentModal = false;
      } else {
        // Benachrichtigung anzeigen, wenn kein Template ausgewählt wurde
        this.$ionic.toastController
          .create({
            message: "Please select a template",
            duration: 2000,
            position: "bottom",
            color: "warning"
          })
          .then(toast => toast.present());
      }
    },
    async getPageData() {
      try {
        const response = await axios.post(
          "web_pages.php",
          qs.stringify({
            getPage: "getPage",
            project: this.$route.params.project,
            name: this.$route.params.page,
          })
        );

        if (response.data.success && response.data.page) {
          this.page = response.data.page;

          // Handle components
          if (this.page.components && this.page.components.length > 0) {
            const component = this.page.components[0]; // Get first component for now
            this.selectComponent(component);

            if (this.page.is_home) {
              this.type = "script"; // Default to script editor for home page
            } else {
              // Determine type based on content
              try {
                const content = JSON.parse(component.html_code);
                if (content.content && Array.isArray(content.content)) {
                  this.type = "menu";
                } else {
                  this.type = "script";
                }
              } catch (e) {
                // If it's not valid JSON, it's probably HTML
                this.type = "script";
              }
            }

            if (this.type === "menu") {
              try {
                const content = JSON.parse(component.html_code);
                this.items = content.content || [];
                this.style = content.style || {};
              } catch (e) {
                console.error("Error parsing menu JSON:", e);
              }
            } else {
              this.currentComponentHTML = component.html_code.trim();
            }
          } else {
            // No components yet, default to script editor
            this.type = "script";
            this.currentComponentHTML = "";
          }

          this.updateDateTimeString();
        } else {
          console.error("Error fetching page data:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching page data:", error);
      }
    },
    async fetchTemplates() {
      try {
        const response = await axios.post(
          "web_pages.php",
          qs.stringify({
            getTemplates: "getTemplates",
            project: this.$route.params.project,
          })
        );

        if (response.data.success && response.data.templates) {
          this.availableTemplates = response.data.templates;
        } else {
          console.error("Error fetching templates:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching templates:", error);
      }
    },
    updateDateTimeString() {
      const lastChangeDate = new Date(this.page.updated_at);

      if (!isNaN(lastChangeDate.getTime())) {
        const formattedDate = lastChangeDate
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedTime = lastChangeDate.toLocaleTimeString("en-GB");
        this.dateTimeString = formattedDate + " at " + formattedTime;
      } else {
        this.dateTimeString = "";
      }

      const createdOn = new Date(this.page.created_at);

      if (!isNaN(createdOn.getTime())) {
        const formattedCreatedOnDate = createdOn
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedCreatedOnTime = createdOn.toLocaleTimeString("en-GB");
        this.createdOn =
          formattedCreatedOnDate + " at " + formattedCreatedOnTime;
      } else {
        this.createdOn = "Not Available";
      }
    },
    selectComponent(component) {
      if (component && component.component_id) {
        this.currentComponentId = component.component_id;
        this.currentComponent = component;
        this.currentComponentHTML = component.html_code.trim();
      }
    },
    getComponentName(component, index) {
      // Wenn es eine original_template_id gibt, sollte der Name aus den Templates kommen
      if (component.original_template_id) {
        // Hier würden wir idealerweise den Namen aus dem Template abrufen
        // In der Sidebar.php wird dies gemacht mit:
        // $comp = fetch_assoc(query("SELECT * FROM control_center_web_builder_templates WHERE id='" . $comp['original_template_id'] . "'"));
        return component.template_name || `Component ${index + 1}`;
      } else if (component.component_id) {
        // Header ist der Standardname in sidebar.php, wenn keine original_template_id vorhanden ist
        return "Header";
      }
      return `Component ${index + 1}`;
    },
    openNewComponentModal() {
      this.isNewComponentModal = true;
      this.setOpen(true);
    },
    toggleComponentReordering() {
      this.isComponentReorderingEnabled = !this.isComponentReorderingEnabled;
    },
    confirmDeleteComponent(component) {
      this.componentToDelete = component;
      this.isDeleteComponentAlertOpen = true;
    },
    deleteComponent() {
      if (!this.componentToDelete) return;
      
      const componentId = this.componentToDelete.component_id;
      
      // Send request to delete component
      axios.post(
        "web_pages.php",
        qs.stringify({
          deleteComponent: "deleteComponent",
          project: this.$route.params.project,
          pageName: this.$route.params.page,
          component_id: componentId
        })
      )
      .then(response => {
        if (response.data.success) {
          // Remove component from local array
          this.page.components = this.page.components.filter(c => c.component_id !== componentId);
          
          // Clear current component if it was the deleted one
          if (this.currentComponentId === componentId) {
            this.currentComponent = null;
            this.currentComponentHTML = "";
            this.currentComponentId = null;
          }
          
          // Show success message
          this.$ionic.toastController
            .create({
              message: "Component deleted successfully",
              duration: 2000,
              position: "bottom",
              color: "success"
            })
            .then(toast => toast.present());
            
          // Update sidebar if needed
          this.emitter.emit("updateSidebar");
        } else {
          // Show error message
          this.$ionic.toastController
            .create({
              message: "Error deleting component: " + (response.data.message || "Unknown error"),
              duration: 3000,
              position: "bottom",
              color: "danger"
            })
            .then(toast => toast.present());
        }
      })
      .catch(error => {
        console.error("Error deleting component:", error);
        
        // Show error message
        this.$ionic.toastController
          .create({
            message: "Error deleting component. Please try again.",
            duration: 3000,
            position: "bottom",
            color: "danger"
          })
          .then(toast => toast.present());
      });
      
      // Reset delete dialog state
      this.componentToDelete = null;
      this.isDeleteComponentAlertOpen = false;
    },
    handleComponentReorder(event) {
      // Complete the reorder and get the new array ordering
      const reorderedComponents = event.detail.complete(this.page.components);
      
      // Update the positions
      const reorderedComponentsWithPositions = reorderedComponents.map((component, index) => {
        component.position = index + 1; // Position is 1-based in most backend implementations
        return component;
      });
      
      // Update local data
      this.page.components = reorderedComponentsWithPositions;
      
      // Save the new order to the backend
      this.saveComponentOrdering();
      
      // Show success message
      this.$ionic.toastController
        .create({
          message: "Component order updated",
          duration: 2000,
          position: "bottom",
          color: "success"
        })
        .then(toast => toast.present());
    },
    saveComponentOrdering() {
      // Extract just the component IDs and positions
      const componentPositions = this.page.components.map(component => ({
        component_id: component.component_id,
        position: component.position
      }));
      
      // Send the updated ordering to the backend
      axios.post(
        "web_pages.php",
        qs.stringify({
          updateComponentsOrder: "updateComponentsOrder",
          project: this.$route.params.project,
          pageName: this.$route.params.page,
          components: JSON.stringify(componentPositions)
        })
      )
      .then(response => {
        if (!response.data.success) {
          console.error("Error saving component order:", response.data.message);
          
          // Show error message
          this.$ionic.toastController
            .create({
              message: "Error saving component order",
              duration: 2000,
              position: "bottom",
              color: "danger"
            })
            .then(toast => toast.present());
        }
      })
      .catch(error => {
        console.error("Error saving component order:", error);
        
        // Show error message
        this.$ionic.toastController
          .create({
            message: "Error saving component order",
            duration: 2000,
            position: "bottom",
            color: "danger"
          })
          .then(toast => toast.present());
      });
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
      this.$ionic.toastController
        .create({
          message: "Opening Web Builder in a new tab",
          duration: 2000,
          position: "bottom",
          color: "tertiary"
        })
        .then(toast => toast.present());
    },
  },
});
</script>

<style scoped>
.component-code-container {
  background-color: #f8f8f8;
  border: 1px solid #ddd;
  padding: 10px;
  border-radius: 4px;
}

.component-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: var(--ion-color-light);
  margin-right: 16px;
}

.component-icon {
  font-size: 20px;
  color: var(--ion-color-primary);
}

/* Dark mode optimizations */
@media (prefers-color-scheme: dark) {
  .component-icon-container {
    background-color: var(--ion-color-dark-shade);
  }
  
  ion-list {
    --ion-background-color: #000;
    --ion-item-background: #000;
  }
  
  ion-item {
    --ion-background-color: #000;
    --background: #000;
  }
  
  .component-code-container {
    background-color: #1e1e1e;
    border-color: #333;
  }
  
  pre {
    color: var(--ion-color-light);
  }
}

img {
  height: 100%;
}

.jc_space {
  justify-content: space-between;
}

.jc_center {
  justify-content: center;
}

ion-card-content, ion-card-header {
  padding: 0 !important;
}

ion-card-content ion-item {
  --padding-start: 0;
}

ion-label p {
  text-align: left;
  margin-left: 0;
}

.page-metadata {
  font-size: 1rem !important;
}
</style>
