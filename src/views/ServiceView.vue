<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-card v-if="service">
              <ion-card-header>
                <ion-card-title>
                  <ion-icon :name="service.icon" style="vertical-align: middle; margin-right: 10px;"></ion-icon>
                  {{ service.name }}
                </ion-card-title>
                <ion-card-subtitle>
                  <ion-badge :color="getBadgeColor(service.status)">{{ getStatusText(service.status) }}</ion-badge>
                </ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <p>{{ service.description || 'No description available' }}</p>
                
                <!-- Service Details and Content will be added here -->
                <div class="service-content">
                  <h2>Service Details</h2>
                  <p>This is the main service view. Configure this service in your project settings.</p>
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
  name: 'ServiceView',
  data() {
    return {
      service: null,
      loading: true,
    };
  },
  created() {
    this.fetchServiceDetails();
  },
  watch: {
    // Watch for route changes to update the service details
    '$route.params.service': 'fetchServiceDetails',
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
    getBadgeColor(status) {
      switch(status) {
        case 'active': return 'success';
        case 'maintenance': return 'warning';
        case 'inactive': return 'danger';
        default: return 'medium';
      }
    },
    getStatusText(status) {
      switch(status) {
        case 'active': return 'Active';
        case 'maintenance': return 'Maintenance';
        case 'inactive': return 'Inactive';
        default: return 'Unknown';
      }
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

.service-content {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--ion-color-light);
}
</style>