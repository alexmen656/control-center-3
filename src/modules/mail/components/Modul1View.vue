<template>
  <div class="email-module">
    <div v-if="loading" class="loading-container">
      <ion-spinner></ion-spinner>
      <p>Lade Emails...</p>
    </div>
    
    <div v-else-if="error" class="error-container">
      <p>{{ error }}</p>
      <ion-button @click="fetchEmails">Erneut versuchen</ion-button>
    </div>
    
    <EmailList v-else :emails="emails" />
  </div>
</template>
  
<script>
import EmailList from './EmailList.vue';
import axios from 'axios';
import { IonSpinner, IonButton } from '@ionic/vue';
import { ToolConfigService } from '@/services/ToolConfigService';

export default {
  name: "Modul1View",
  components: {
    EmailList,
    IonSpinner,
    IonButton
  },
  data() {
    return {
      emails: [],
      loading: true,
      error: null,
      config: null // Store the configuration data
    };
  },
  async created() {
    try {
      this.loading = true;
      this.config = await ToolConfigService.loadToolConfig(this.$route.params.project, 'mail');
      this.fetchEmails();
    } catch (err) {
      console.error('Failed to load configuration:', err);
      this.error = 'Fehler beim Laden der Konfiguration. Bitte versuchen Sie es später erneut.';
      this.loading = false;
    }
  },
  methods: {
    async fetchEmails() {
      if (!this.config) {
        this.error = 'Keine Konfiguration verfügbar.';
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post('http://192.168.178.149:3000/emails', this.config);
        this.emails = response.data;
        console.log(`Loaded ${this.emails.length} emails`);
        this.loading = false;
      } catch (error) {
        console.error("Fehler beim Laden der Emails:", error);
        this.error = "Fehler beim Laden der Emails. Bitte versuchen Sie es später erneut.";
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.email-module {
  height: 100%;
}

.loading-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
  text-align: center;
  padding: 20px;
}

.error-container {
  color: #d33;
}
</style>