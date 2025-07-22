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
                <div style="display: flex; align-items: center; justify-content: space-between;">
                  <div>
                    <ion-card-title>
                      <ion-icon name="add-circle-outline" slot="start"></ion-icon>
                      Register New App
                    </ion-card-title>
                    <ion-card-subtitle>Create a new application to manage users</ion-card-subtitle>
                  </div>
                  <ion-button fill="clear" @click="$router.push('/app-users/dashboard')">
                    <ion-icon name="arrow-back-outline" slot="start"></ion-icon>
                    Back
                  </ion-button>
                </div>
              </ion-card-header>
            </ion-card>

            <!-- App Information Form -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>App Information</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <form @submit.prevent="registerApp">
                  <ion-list>
                    <!-- App Name -->
                    <ion-item>
                      <ion-input
                        v-model="appName"
                        label="App Name"
                        label-placement="floating"
                        fill="outline"
                        placeholder="My Awesome App"
                        required
                      ></ion-input>
                    </ion-item>
                    <ion-text color="medium">
                      <p style="margin: 8px 16px; font-size: 0.9em;">The name of your application as it will appear in the dashboard</p>
                    </ion-text>
                    
                    <!-- App ID & Bundle ID Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 16px 0;">
                      <ion-item>
                        <ion-input
                          v-model="appId"
                          label="App ID"
                          label-placement="floating"
                          fill="outline"
                          placeholder="unique-app-id"
                          required
                        ></ion-input>
                      </ion-item>
                      
                      <ion-item>
                        <ion-input
                          v-model="bundleId"
                          label="Bundle ID"
                          label-placement="floating"
                          fill="outline"
                          placeholder="com.company.appname"
                          required
                        ></ion-input>
                      </ion-item>
                    </div>
                    
                    <!-- Description -->
                    <ion-item>
                      <ion-textarea
                        v-model="description"
                        label="Description"
                        label-placement="floating"
                        fill="outline"
                        placeholder="A brief description of your app"
                        rows="3"
                      ></ion-textarea>
                    </ion-item>
                  </ion-list>
                </form>
              </ion-card-content>
            </ion-card>

            <!-- Platform Selection -->
            <ion-card>
              <ion-card-header>
                <ion-card-title>Platform</ion-card-title>
                <ion-card-subtitle>Select the platform for your application</ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <div class="platform-grid">
                  <div 
                    v-for="platform in platforms" 
                    :key="platform.value"
                    class="platform-option"
                    :class="{ 'selected': selectedPlatform === platform.value }"
                    @click="selectedPlatform = platform.value"
                  >
                    <div class="platform-icon" :class="platform.value.toLowerCase()">
                      <ion-icon :name="platform.icon"></ion-icon>
                    </div>
                    <div class="platform-label">{{ platform.label }}</div>
                  </div>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- API Key Display (after generation) -->
            <ion-card v-if="apiKey">
              <ion-card-header>
                <ion-card-title color="success">
                  <ion-icon name="checkmark-circle-outline" slot="start"></ion-icon>
                  Your API Key
                </ion-card-title>
                <ion-card-subtitle>App successfully registered!</ion-card-subtitle>
              </ion-card-header>
              <ion-card-content>
                <div class="api-key-container">
                  <ion-item>
                    <ion-label>
                      <h3>API Key</h3>
                      <p style="font-family: monospace; font-size: 14px; word-break: break-all;">{{ apiKey }}</p>
                    </ion-label>
                    <ion-button fill="clear" @click="copyApiKey" slot="end">
                      <ion-icon name="copy-outline"></ion-icon>
                    </ion-button>
                  </ion-item>
                </div>
                
                <div style="background: var(--ion-color-warning-tint); padding: 16px; border-radius: 8px; margin-top: 16px;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <ion-icon name="warning-outline" color="warning"></ion-icon>
                    <ion-text color="warning">
                      <strong>Important:</strong>
                    </ion-text>
                  </div>
                  <ion-text color="medium">
                    <p style="margin: 8px 0 0 0;">This API key will only be shown once. Please copy and store it securely.</p>
                  </ion-text>
                </div>
              </ion-card-content>
            </ion-card>

            <!-- Action Buttons -->
            <div style="margin-top: 24px;">
              <ion-button 
                v-if="!apiKey" 
                @click="registerApp" 
                expand="block" 
                color="primary" 
                :disabled="isSubmitting || !isFormValid"
                size="large"
              >
                <ion-icon v-if="isSubmitting" name="sync-outline" class="spin" slot="start"></ion-icon>
                <ion-icon v-else name="rocket-outline" slot="start"></ion-icon>
                {{ isSubmitting ? 'Generating...' : 'Generate API Key & Register App' }}
              </ion-button>
              
              <div v-else style="display: flex; gap: 12px;">
                <ion-button expand="block" color="primary" @click="$router.push('/app-users/dashboard')">
                  <ion-icon name="apps-outline" slot="start"></ion-icon>
                  Go to Dashboard
                </ion-button>
                <ion-button expand="block" fill="outline" @click="registerAnother">
                  <ion-icon name="add-outline" slot="start"></ion-icon>
                  Register Another
                </ion-button>
              </div>
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
  IonList,
  IonItem,
  IonInput,
  IonTextarea,
  IonButton,
  IonIcon,
  IonText,
  IonLabel,
  toastController
} from '@ionic/vue';

export default {
  name: 'RegisterAppView',
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
    IonList,
    IonItem,
    IonInput,
    IonTextarea,
    IonButton,
    IonIcon,
    IonText,
    IonLabel
  },
  data() {
    return {
      appName: '',
      bundleId: '',
      appId: '',
      description: '',
      selectedPlatform: 'Web',
      apiKey: '',
      isSubmitting: false,
      platforms: [
        { value: 'iOS', label: 'iOS', icon: 'logo-apple' },
        { value: 'Android', label: 'Android', icon: 'logo-android' },
        { value: 'Web', label: 'Web', icon: 'globe-outline' }
      ]
    };
  },
  computed: {
    isFormValid() {
      return this.appName.trim() && this.bundleId.trim() && this.appId.trim() && this.selectedPlatform;
    }
  },
  methods: {
    generateApiKey() {
      // Generate a random API key with prefix
      const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let key = 'api_key_';
      for (let i = 0; i < 32; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return key;
    },
    async registerApp() {
      if (!this.isFormValid) {
        const toast = await toastController.create({
          message: 'Please fill all required fields',
          duration: 3000,
          color: 'warning',
          position: 'bottom'
        });
        await toast.present();
        return;
      }
      
      this.isSubmitting = true;
      
      try {
        // Simulate API call with a short delay
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Generate API key
        this.apiKey = this.generateApiKey();
        
        // Create app object
        const newApp = {
          id: Date.now(), // Simple unique ID generation
          name: this.appName,
          platform: this.selectedPlatform,
          description: this.description,
          appId: this.appId,
          bundleId: this.bundleId,
          apiKey: this.apiKey,
          status: 'Active',
          totalUsers: 0,
          activeUsers: 0,
          createdAt: new Date().toISOString()
        };
        
        // Save to localStorage
        const apps = JSON.parse(localStorage.getItem('registeredApps') || '[]');
        apps.push(newApp);
        localStorage.setItem('registeredApps', JSON.stringify(apps));
        
        // Show success message
        const toast = await toastController.create({
          message: 'App registered successfully!',
          duration: 3000,
          color: 'success',
          position: 'bottom'
        });
        await toast.present();
        
      } catch (error) {
        console.error('Error registering app:', error);
        const toast = await toastController.create({
          message: 'Error registering app. Please try again.',
          duration: 3000,
          color: 'danger',
          position: 'bottom'
        });
        await toast.present();
      } finally {
        this.isSubmitting = false;
      }
    },
    async copyApiKey() {
      try {
        await navigator.clipboard.writeText(this.apiKey);
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
        textArea.value = this.apiKey;
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
    registerAnother() {
      // Reset form
      this.appName = '';
      this.bundleId = '';
      this.appId = '';
      this.description = '';
      this.selectedPlatform = 'Web';
      this.apiKey = '';
      this.isSubmitting = false;
    }
  }
};
</script>

<style scoped>
.platform-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.platform-option {
  border: 2px solid var(--ion-color-light);
  border-radius: 12px;
  padding: 20px 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--ion-color-step-50);
}

.platform-option:hover {
  background: var(--ion-color-light);
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.platform-option.selected {
  border-color: var(--ion-color-primary);
  background: var(--ion-color-primary-tint);
  color: var(--ion-color-primary-contrast);
  box-shadow: 0 4px 10px rgba(var(--ion-color-primary-rgb), 0.2);
}

.platform-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  color: white;
  font-size: 24px;
}

.platform-icon.ios {
  background: var(--ion-color-primary);
}

.platform-icon.android {
  background: var(--ion-color-success);
}

.platform-icon.web {
  background: var(--ion-color-warning);
}

.platform-label {
  font-weight: 500;
  font-size: 14px;
}

.api-key-container {
  background: var(--ion-color-light);
  border-radius: 8px;
  margin: 16px 0;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .platform-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }
  
  .platform-option {
    padding: 16px 12px;
  }
  
  .platform-icon {
    width: 40px;
    height: 40px;
    font-size: 20px;
  }
}

@media (prefers-color-scheme: dark) {
  .platform-option:hover {
    box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
  }
  
  .platform-option.selected {
    box-shadow: 0 4px 10px rgba(var(--ion-color-primary-rgb), 0.3);
  }
}
</style>
