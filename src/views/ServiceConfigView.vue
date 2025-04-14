<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-card v-if="service">
              <ion-card-header>
                <ion-card-title>Configure {{ service.name }}</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <ion-list>
                  <ion-item>
                    <ion-label position="floating">Service Name</ion-label>
                    <ion-input v-model="service.name" type="text"></ion-input>
                  </ion-item>
                  
                  <ion-item>
                    <ion-label position="floating">Icon</ion-label>
                    <ion-input v-model="service.icon" type="text"></ion-input>
                    <ion-icon slot="end" :name="service.icon" size="large"></ion-icon>
                  </ion-item>
                  
                  <ion-item>
                    <ion-label position="floating">Description</ion-label>
                    <ion-textarea v-model="service.description" rows="4"></ion-textarea>
                  </ion-item>
                  
                  <ion-item>
                    <ion-label>Status</ion-label>
                    <ion-select v-model="service.status">
                      <ion-select-option value="active">Active</ion-select-option>
                      <ion-select-option value="maintenance">Maintenance</ion-select-option>
                      <ion-select-option value="inactive">Inactive</ion-select-option>
                    </ion-select>
                  </ion-item>
                </ion-list>
                
                <div class="button-container">
                  <ion-button @click="saveChanges" color="primary">
                    <ion-icon name="save-outline" slot="start"></ion-icon>
                    Save Changes
                  </ion-button>
                  <ion-button @click="goToManageServices" color="medium">
                    <ion-icon name="settings-outline" slot="start"></ion-icon>
                    Manage All Services
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>
            <ion-card v-if="!service && !loading">
              <ion-card-header>
                <ion-card-title>Service Not Found</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <p>The requested service could not be found.</p>
                <ion-button @click="$router.push(`/project/${$route.params.project}`)">
                  Return to Project
                </ion-button>
              </ion-card-content>
            </ion-card>
            <ion-card v-if="loading">
              <ion-card-content class="loading-container">
                <ion-spinner name="circular"></ion-spinner>
                <p>Loading service details...</p>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: 'ServiceConfigView',
  data() {
    return {
      service: null,
      loading: true,
    };
  },
  created() {
    this.fetchServiceDetails();
  },
  methods: {
    fetchServiceDetails() {
      this.loading = true;
      
      // Find the service in the project's services list
      this.$axios.post(
        'services.php',
        this.$qs.stringify({
          getServices: 'getServices',
          project: this.$route.params.project,
        })
      )
      .then(response => {
        const services = response.data || [];
        this.service = services.find(s => s.link === this.$route.params.service);
        this.loading = false;
      })
      .catch(error => {
        console.error('Error fetching service:', error);
        this.loading = false;
      });
    },
    saveChanges() {
      if (!this.service) return;
      
      this.$axios.post(
        'services.php',
        this.$qs.stringify({
          updateService: 'updateService',
          serviceId: this.service.id,
          name: this.service.name,
          icon: this.service.icon,
          description: this.service.description,
          status: this.service.status
        })
      )
      .then(response => {
        if (response.data === 'success') {
          this.$toast.success('Service updated successfully');
          this.emitter.emit('updateSidebar');
        } else {
          this.$toast.error('Error updating service: ' + response.data);
        }
      })
      .catch(error => {
        console.error('Error updating service:', error);
        this.$toast.error('Failed to update service');
      });
    },
    goToManageServices() {
      this.$router.push(`/project/${this.$route.params.project}/manage/services`);
    }
  }
};
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.button-container {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}
</style>