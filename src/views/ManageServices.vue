<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <h1 style="text-align: center; margin-top: 2rem;">Manage Services</h1>
            <div class="action-buttons">
              <ion-button @click="$router.push(`/project/${$route.params.project}/new/service`)">
                <ion-icon slot="start" name="add-circle-outline"></ion-icon>
                Add New Service
              </ion-button>
              <ion-button v-if="selectMode" @click="confirmBulkDelete" color="danger">
                <ion-icon slot="start" name="trash-outline"></ion-icon>
                Delete Selected
              </ion-button>
              <ion-button @click="toggleSelectMode">
                <ion-icon slot="start" :name="selectMode ? 'close-circle-outline' : 'checkbox-outline'"></ion-icon>
                {{ selectMode ? 'Cancel Selection' : 'Select Multiple' }}
              </ion-button>
            </div>

            <ion-list v-if="services.length > 0">
              <ion-item-sliding v-for="service in services" :key="service.id">
                <ion-item>
                  <ion-checkbox v-if="selectMode" 
                    @ionChange="toggleServiceSelection(service.id)" 
                    slot="start">
                  </ion-checkbox>
                  <ion-icon :name="service.icon" slot="start"></ion-icon>
                  <ion-label>
                    <h2>{{ service.name }}</h2>
                    <p>{{ service.description || 'No description' }}</p>
                    <ion-badge color="success" v-if="service.status === 'active'">Active</ion-badge>
                    <ion-badge color="warning" v-else-if="service.status === 'maintenance'">Maintenance</ion-badge>
                    <ion-badge color="danger" v-else-if="service.status === 'inactive'">Inactive</ion-badge>
                  </ion-label>
                  <ion-button fill="clear" slot="end" @click="viewService(service)">
                    <ion-icon slot="icon-only" name="eye-outline"></ion-icon>
                  </ion-button>
                  <ion-button fill="clear" slot="end" @click="viewServiceLogs(service)">
                    <ion-icon slot="icon-only" name="list-outline"></ion-icon>
                  </ion-button>
                </ion-item>
                <ion-item-options>
                  <ion-item-option @click="editService(service)" color="primary">
                    <ion-icon class="option-icon" name="create-outline"></ion-icon>
                    Edit
                  </ion-item-option>
                  <ion-item-option @click="confirmDelete(service)" color="danger">
                    <ion-icon class="option-icon" name="trash-outline"></ion-icon>
                    Delete
                  </ion-item-option>
                </ion-item-options>
              </ion-item-sliding>
            </ion-list>
            <ion-item v-else>
              <ion-label class="ion-text-center">
                <h2>No services found</h2>
                <p>Add a new service to get started</p>
              </ion-label>
            </ion-item>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
      
      <!-- Edit Service Modal -->
      <ion-modal :is-open="isEditModalOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeEditModal">Cancel</ion-button>
            </ion-buttons>
            <ion-title>Edit Service</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="saveServiceChanges">Save</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-item>
            <ion-label position="floating">Icon</ion-label>
            <ion-input v-model="editingService.icon" type="text"></ion-input>
            <ion-icon
              slot="end"
              size="large"
              :name="editingService.icon || 'help-circle-outline'"
            ></ion-icon>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Name</ion-label>
            <ion-input v-model="editingService.name" type="text"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Description</ion-label>
            <ion-textarea v-model="editingService.description"></ion-textarea>
          </ion-item>
          <ion-item>
            <ion-label>Status</ion-label>
            <ion-select v-model="editingService.status">
              <ion-select-option value="active">Active</ion-select-option>
              <ion-select-option value="maintenance">Maintenance</ion-select-option>
              <ion-select-option value="inactive">Inactive</ion-select-option>
            </ion-select>
          </ion-item>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref } from "vue";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "ManageServices",
  data() {
    return {
      services: [],
      isEditModalOpen: false,
      editingService: {
        id: null,
        name: "",
        icon: "",
        description: "",
        status: "active"
      },
      selectedServices: [],
      selectMode: false
    };
  },
  created() {
    this.loadServices();
  },
  methods: {
    loadServices() {
      this.$axios
        .post(
          "services.php",
          this.$qs.stringify({
            getServices: "getServices",
            project: this.$route.params.project
          })
        )
        .then(response => {
          this.services = response.data;
        })
        .catch(error => {
          console.error("Error loading services:", error);
          this.$toast.error("Failed to load services");
        });
    },
    viewService(service) {
      // Navigate to the service view
      this.$router.push(`/project/${this.$route.params.project}/services/${service.link}`);
    },
    viewServiceLogs(service) {
      // Navigate to the service view with logs section
      this.$router.push(`/project/${this.$route.params.project}/services/${service.link}`);
    },
    editService(service) {
      this.editingService = { ...service };
      this.isEditModalOpen = true;
    },
    closeEditModal() {
      this.isEditModalOpen = false;
    },
    saveServiceChanges() {
      this.$axios
        .post(
          "services.php",
          this.$qs.stringify({
            updateService: "updateService",
            serviceId: this.editingService.id,
            name: this.editingService.name,
            icon: this.editingService.icon,
            description: this.editingService.description,
            status: this.editingService.status
          })
        )
        .then(response => {
          if (response.data === "success") {
            this.$toast.success("Service updated successfully");
            this.loadServices();
            // Emit event to update sidebar immediately
            this.emitter.emit("updateSidebar");
          } else {
            this.$toast.error("Error updating service: " + response.data);
          }
          this.closeEditModal();
        })
        .catch(error => {
          console.error("Error updating service:", error);
          this.$toast.error("Failed to update service");
          this.closeEditModal();
        });
    },
    confirmDelete(service) {
      if (confirm(`Are you sure you want to delete the service "${service.name}"?`)) {
        this.deleteService(service.id);
      }
    },
    deleteService(serviceId) {
      this.$axios
        .post(
          "services.php",
          this.$qs.stringify({
            deleteService: "deleteService",
            serviceId: serviceId
          })
        )
        .then(response => {
          if (response.data === "success") {
            this.$toast.success("Service deleted successfully");
            this.loadServices();
            // Emit event to update sidebar immediately
            this.emitter.emit("updateSidebar");
          } else {
            this.$toast.error("Error deleting service: " + response.data);
          }
        })
        .catch(error => {
          console.error("Error deleting service:", error);
          this.$toast.error("Failed to delete service");
        });
    },
    toggleSelectMode() {
      this.selectMode = !this.selectMode;
      if (!this.selectMode) {
        this.selectedServices = [];
      }
    },
    toggleServiceSelection(serviceId) {
      const index = this.selectedServices.indexOf(serviceId);
      if (index === -1) {
        this.selectedServices.push(serviceId);
      } else {
        this.selectedServices.splice(index, 1);
      }
    },
    confirmBulkDelete() {
      if (this.selectedServices.length === 0) {
        this.$toast.error("No services selected");
        return;
      }
      
      if (confirm(`Are you sure you want to delete ${this.selectedServices.length} selected services?`)) {
        const deletePromises = this.selectedServices.map(serviceId => {
          return this.$axios.post(
            "services.php",
            this.$qs.stringify({
              deleteService: "deleteService",
              serviceId: serviceId
            })
          );
        });
        
        Promise.all(deletePromises)
          .then(() => {
            this.$toast.success(`${this.selectedServices.length} services deleted successfully`);
            this.selectedServices = [];
            this.selectMode = false;
            this.loadServices();
            // Emit event to update sidebar immediately after bulk delete
            this.emitter.emit("updateSidebar");
          })
          .catch(error => {
            console.error("Error deleting services:", error);
            this.$toast.error("Failed to delete some services");
          });
      }
    }
  }
});
</script>

<style scoped>
.action-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.option-icon {
  margin-right: 5px;
}

ion-badge {
  margin-top: 5px;
}
</style>