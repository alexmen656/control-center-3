<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="12" size-lg="8">
            <h2>Create a new project</h2>
          </ion-col>
          
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Icon</ion-label>
              <ion-input
                type="text"
                v-model="icon"
                :value="icon"
                @ionInput="icon = $event.target.value"
                placeholder="Enter Icon Code"
              >
              </ion-input>
              <ion-icon
                class="input-icon"
                slot="end"
                size="large"
                :name="icon ? icon : 'help-circle-outline'"
              ></ion-icon>
            </ion-item>
          </ion-col>
          
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Project Name</ion-label>
              <ion-input
                v-model="name"
                :value="name"
                @ionInput="name = $event.target.value"
                type="text"
                placeholder="Enter Project Name"
              ></ion-input>
            </ion-item>
          </ion-col>
          
          <ion-col size="12" size-lg="8">
            <project-template-selector v-model="selectedTemplateId" />
          </ion-col>
          
          <ion-col size="12" size-lg="8" class="ion-margin-top">
            <div class="action-buttons">
              <ion-button @click="createWithoutTemplate" :disabled="!name">Create Empty Project</ion-button>
              <ion-button 
                @click="createFromTemplate" 
                color="primary"
                :disabled="!name || !selectedTemplateId">
                Create From Template
              </ion-button>
            </div>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import ProjectTemplateSelector from '@/components/ProjectTemplateSelector.vue';
import { defineComponent } from 'vue';
import axios from 'axios';
import qs from 'qs';

export default defineComponent({
  name: "NewProject",
  components: {
    ProjectTemplateSelector
  },
  data() {
    return {
      name: "",
      icon: "folder-outline",
      selectedTemplateId: null
    };
  },
  methods: {
    createWithoutTemplate() {
      if (this.name) {
        this.createProject();
      } else {
        this.showError("Project Name is empty!");
      }
    },
    
    createFromTemplate() {
      if (!this.name) {
        this.showError("Project Name is empty!");
        return;
      }
      
      if (!this.selectedTemplateId) {
        this.showError("Please select a template");
        return;
      }
      
      // Create project from template
      axios.post(
        "project_templates.php",
        qs.stringify({
          action: "apply",
          template_id: this.selectedTemplateId,
          project_name: this.name,
          project_icon: this.icon || "folder-outline"
        })
      )
      .then((response) => {
        if (response.data.success) {
          this.showSuccess("Project created successfully from template!");
          this.emitter.emit("updateSidebar");
          this.$router.push(`/project/${this.name}/`);
        } else {
          this.showError(response.data.message || "Failed to create project from template");
        }
      })
      .catch((error) => {
        console.error("Error creating project from template:", error);
        this.showError("Network or server error");
      });
    },
    
    createProject() {
      axios.post(
        "projects.php",
        qs.stringify({
          createProject: "createProject",
          projectName: this.name,
          projectIcon: this.icon,
        })
      )
      .then(() => {
        this.showSuccess("Project created successfully");
        this.emitter.emit("updateSidebar");
        this.$router.push(`/project/${this.name}/`);
      })
      .catch((error) => {
        console.error("Error creating project:", error);
        this.showError("Network or server error");
      });
    },
    
    showSuccess(message) {
      if (this.$ionic && this.$ionic.toastController) {
        this.$ionic.toastController.create({
          message,
          duration: 2000,
          position: "bottom",
          color: "success"
        }).then(toast => toast.present());
      } else {
        alert(message);
      }
    },
    
    showError(message) {
      if (this.$ionic && this.$ionic.toastController) {
        this.$ionic.toastController.create({
          message,
          duration: 3000,
          position: "bottom",
          color: "danger"
        }).then(toast => toast.present());
      } else {
        alert(message);
      }
    }
  },
});
</script>

<style scoped>
.input-icon {
  margin: 20px 8px;
  font-size: 1.2em;
  height: 100%;
  margin: 0 !important;
  padding: 0 !important;
}

h2 {
  margin-bottom: 20px;
  font-weight: 600;
  color: var(--ion-color-dark);
}

.action-buttons {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

@media (max-width: 576px) {
  .action-buttons {
    flex-direction: column;
  }
}
</style>
