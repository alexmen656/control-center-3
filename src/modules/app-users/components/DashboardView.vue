<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <!-- Header Card -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>
                  <ion-icon name="apps-outline" slot="start"></ion-icon>
                  App User Management
                </ion-card-title>
                <ion-card-subtitle>Manage your registered applications and their users</ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                  <!-- Platform Filter -->
                  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <ion-chip 
                      v-for="platform in platforms" 
                      :key="platform" 
                      @click="filterByPlatform(platform)" 
                      :color="selectedPlatform === platform ? 'primary' : 'medium'"
                      :outline="selectedPlatform !== platform"
                    >
                      <ion-icon :name="getPlatformIcon(platform)" slot="start"></ion-icon>
                      {{ platform }}
                    </ion-chip>
                    <ion-chip 
                      @click="filterByPlatform('all')" 
                      :color="selectedPlatform === 'all' ? 'primary' : 'medium'"
                      :outline="selectedPlatform !== 'all'"
                    >
                      <ion-icon name="apps-outline" slot="start"></ion-icon>
                      All
                    </ion-chip>
                  </div>
                  
                  <!-- Action Buttons -->
                  <div style="display: flex; gap: 8px;">
                    <ion-button fill="outline" size="default" @click="$router.push('/app-users/statistics')">
                      <ion-icon name="bar-chart-outline" slot="start"></ion-icon>
                      Statistics
                    </ion-button>
                    <ion-button color="primary" @click="$router.push('/app-users/register')">
                      <ion-icon name="add-outline" slot="start"></ion-icon>
                      Register New App
                    </ion-button>
                  </div>
                </div>
              </ion-card-content>
            </ion-card>
        
            <!-- Apps Grid -->
            <div v-if="filteredApps.length > 0">
              <ion-row>
                <ion-col size="12" size-md="6" size-lg="4" v-for="app in filteredApps" :key="app.id">
                  <ion-card class="app-card">
                    <ion-card-header>
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="platform-icon" :class="app.platform.toLowerCase()">
                          <ion-icon :name="getPlatformIcon(app.platform)"></ion-icon>
                        </div>
                        <div style="flex: 1;">
                          <ion-card-title>{{ app.name }}</ion-card-title>
                          <ion-chip :color="getPlatformColor(app.platform)" size="small">
                            {{ app.platform }}
                          </ion-chip>
                        </div>
                        <ion-badge :color="app.status.toLowerCase() === 'active' ? 'success' : 'danger'">
                          {{ app.status }}
                        </ion-badge>
                      </div>
                    </ion-card-header>
                    
                    <ion-card-content>
                      <ion-text color="medium">
                        <p>{{ app.description || 'No description provided' }}</p>
                      </ion-text>
                      
                      <!-- Stats -->
                      <div style="display: flex; justify-content: space-around; margin: 16px 0; padding: 12px; background: var(--ion-color-light); border-radius: 8px;">
                        <div style="text-align: center;">
                          <div style="font-size: 20px; font-weight: bold; color: var(--ion-color-primary);">{{ app.totalUsers || 0 }}</div>
                          <ion-text color="medium">
                            <small>Total Users</small>
                          </ion-text>
                        </div>
                        <div style="text-align: center;">
                          <div style="font-size: 20px; font-weight: bold; color: var(--ion-color-success);">{{ app.activeUsers || 0 }}</div>
                          <ion-text color="medium">
                            <small>Active Users</small>
                          </ion-text>
                        </div>
                      </div>
                      
                      <!-- App Details -->
                      <ion-list lines="none">
                        <ion-item>
                          <ion-label>
                            <h3>App ID</h3>
                            <p>{{ app.appId }}</p>
                          </ion-label>
                        </ion-item>
                        <ion-item>
                          <ion-label>
                            <h3>Bundle ID</h3>
                            <p>{{ app.bundleId }}</p>
                          </ion-label>
                        </ion-item>
                        <ion-item>
                          <ion-label>
                            <h3>API Key</h3>
                            <p>{{ app.apiKey.substring(0, 8) }}...{{ app.apiKey.substring(app.apiKey.length - 4) }}</p>
                          </ion-label>
                          <ion-button fill="clear" @click="copyApiKey(app.apiKey)" slot="end">
                            <ion-icon name="copy-outline"></ion-icon>
                          </ion-button>
                        </ion-item>
                      </ion-list>
                      
                      <!-- Actions -->
                      <div style="display: flex; gap: 8px; margin-top: 16px;">
                        <ion-button expand="block" fill="outline" color="primary">
                          <ion-icon name="create-outline" slot="start"></ion-icon>
                          Edit
                        </ion-button>
                        <ion-button expand="block" fill="outline" color="danger" @click="deleteApp(app.id)">
                          <ion-icon name="trash-outline" slot="start"></ion-icon>
                          Delete
                        </ion-button>
                      </div>
                    </ion-card-content>
                  </ion-card>
                </ion-col>
              </ion-row>
            </div>
            
            <!-- Empty State -->
            <div v-else>
              <ion-card>
                <ion-card-content>
                  <div style="text-align: center; padding: 40px 20px;">
                    <ion-icon name="apps-outline" style="font-size: 64px; color: var(--ion-color-medium); margin-bottom: 16px;"></ion-icon>
                    <h2>No Apps Found</h2>
                    <ion-text color="medium">
                      <p>{{ selectedPlatform === 'all' ? 'No registered apps found.' : `No ${selectedPlatform} apps found.` }}</p>
                    </ion-text>
                    <ion-button color="primary" @click="$router.push('/app-users/register')" style="margin-top: 16px;">
                      <ion-icon name="add-outline" slot="start"></ion-icon>
                      Register Your First App
                    </ion-button>
                  </div>
                </ion-card-content>
              </ion-card>
            </div>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { 
  IonPage, 
  IonContent, 
  IonGrid, 
  IonRow, 
  IonCol, 
  IonCard, 
  IonCardHeader, 
  IonCardTitle, 
  IonCardSubtitle, 
  IonCardContent,
  IonChip,
  IonButton,
  IonIcon,
  IonBadge,
  IonText,
  IonList,
  IonItem,
  IonLabel,
  toastController
} from '@ionic/vue';

export default {
  name: 'DashboardView',
  components: {
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonChip,
    IonButton,
    IonIcon,
    IonBadge,
    IonText,
    IonList,
    IonItem,
    IonLabel
  },
  data() {
    return {
      selectedPlatform: 'all',
      platforms: ['iOS', 'Android', 'Web'],
      apps: []
    };
  },
  mounted() {
    this.loadApps();
  },
  computed: {
    filteredApps() {
      if (this.selectedPlatform === 'all') {
        return this.apps;
      }
      return this.apps.filter(app => app.platform === this.selectedPlatform);
    }
  },
  methods: {
    loadApps() {
      // Load apps from localStorage
      const savedApps = JSON.parse(localStorage.getItem('registeredApps') || '[]');
      this.apps = savedApps;
    },
    filterByPlatform(platform) {
      this.selectedPlatform = platform;
    },
    getPlatformIcon(platform) {
      switch(platform.toLowerCase()) {
        case 'ios':
          return 'logo-apple';
        case 'android':
          return 'logo-android';
        case 'web':
          return 'globe-outline';
        default:
          return 'apps-outline';
      }
    },
    getPlatformColor(platform) {
      switch(platform.toLowerCase()) {
        case 'ios':
          return 'primary';
        case 'android':
          return 'success';
        case 'web':
          return 'warning';
        default:
          return 'medium';
      }
    },
    async copyApiKey(apiKey) {
      try {
        await navigator.clipboard.writeText(apiKey);
        const toast = await toastController.create({
          message: 'API Key copied to clipboard!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        await toast.present();
      } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = apiKey;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        const toast = await toastController.create({
          message: 'API Key copied to clipboard!',
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        await toast.present();
      }
    },
    async deleteApp(id) {
      const alert = document.createElement('ion-alert');
      alert.header = 'Confirm Deletion';
      alert.message = 'Are you sure you want to delete this app? This action cannot be undone.';
      alert.buttons = [
        {
          text: 'Cancel',
          role: 'cancel'
        },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.apps = this.apps.filter(app => app.id !== id);
            localStorage.setItem('registeredApps', JSON.stringify(this.apps));
            
            toastController.create({
              message: 'App deleted successfully',
              duration: 2000,
              color: 'success',
              position: 'bottom'
            }).then(toast => toast.present());
          }
        }
      ];
      
      document.body.appendChild(alert);
      await alert.present();
    }
  }
};
</script>

<style scoped>
.platform-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.platform-icon.ios {
  background-color: var(--ion-color-primary);
}

.platform-icon.android {
  background-color: var(--ion-color-success);
}

.platform-icon.web {
  background-color: var(--ion-color-warning);
}

.app-card {
  margin-bottom: 16px;
}

.app-card:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
  ion-col[size-md="6"] {
    --ion-grid-column-padding: 8px;
  }
  
  .platform-icon {
    width: 36px;
    height: 36px;
    font-size: 18px;
  }
}

@media (prefers-color-scheme: dark) {
  .app-card:hover {
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
  }
  
  ion-list {
    --ion-background-color: transparent;
    --ion-item-background: transparent;
  }
  
  ion-item {
    --ion-background-color: transparent;
    --background: transparent;
  }
}
</style>
