<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <h1 style="text-align: center; margin-top: 2rem;">Add New Service</h1>
            <ion-item>
              <ion-label position="floating">Icon</ion-label>
              <ion-input
                type="text"
                v-model="icon"
                :value="icon"
                @ionInput="icon = $event.target.value"
                placeholder="Enter Icon Code (e.g., 'cog-outline')"
              >
              </ion-input>
              <ion-icon
                class="input-icon"
                slot="end"
                size="large"
                :name="icon ? icon : 'help-circle-outline'"
              ></ion-icon>
            </ion-item>
            
            <ion-item>
              <ion-label position="floating">Service Name</ion-label>
              <ion-input
                v-model="name"
                :value="name"
                @ionInput="name = $event.target.value"
                type="text"
                placeholder="Enter Service Name"
              ></ion-input>
            </ion-item>
            
            <ion-item>
              <ion-label position="floating">Description</ion-label>
              <ion-textarea
                v-model="description"
                :value="description"
                @ionInput="description = $event.target.value"
                placeholder="Enter Service Description"
              ></ion-textarea>
            </ion-item>
            
            <div class="button-container">
              <ion-button @click="submit">Add Service</ion-button>
            </div>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "NewService",
  data() {
    return {
      name: "",
      icon: "cog-outline",
      description: "",
    };
  },
  methods: {
    submit() {
      if (this.name) {
        this.$axios
          .post(
            "services.php",
            this.$qs.stringify({
              addService: "addService",
              name: this.name,
              icon: this.icon,
              description: this.description,
              project: this.$route.params.project,
            })
          )
          .then((response) => {
            if (response.data && response.data.status === "success") {
              this.$toast.success(response.data.message || "Service added successfully");
              
              // Emit event to update sidebar immediately
              this.emitter.emit("updateSidebar");
              
              // Navigate back to the project view
              this.$router.push(`/project/${this.$route.params.project}`);
            } else {
              const errorMessage = response.data && response.data.message 
                ? response.data.message 
                : "Error adding service";
              this.$toast.error(errorMessage);
            }
          })
          .catch((error) => {
            console.error("Error adding service:", error);
            this.$toast.error("Error adding service");
          });
      } else {
        this.$toast.error("Service name is required");
      }
    },
  },
});
</script>

<style scoped>
.input-icon {
  margin-right: 8px;
}

.button-container {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}
</style>