<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/"></ion-back-button>
        </ion-buttons>
        <ion-title>AI Dashboard Generator</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-content class="ion-padding">
      <!-- Beschreibung eingeben -->
      <ion-card v-if="step === 1">
        <ion-card-header>
          <ion-card-title>Dashboard beschreiben</ion-card-title>
          <ion-card-subtitle>Was für ein Dashboard möchten Sie erstellen?</ion-card-subtitle>
        </ion-card-header>
        <ion-card-content>
          <ion-item>
            <ion-label position="stacked">Dashboard Beschreibung</ion-label>
            <ion-textarea 
              v-model="description" 
              placeholder="z.B. 'Verkaufsdashboard mit monatlichen Trends und Produktkategorien' oder 'Kundendaten-Analyse mit Bewertungen'"
              :rows="4"
            ></ion-textarea>
          </ion-item>
          
          <ion-button 
            expand="block" 
            @click="generateDashboard" 
            :disabled="!description.trim() || loading"
            style="margin-top: 16px"
          >
            <ion-icon name="sparkles" slot="start"></ion-icon>
            <span v-if="loading">Generiere Dashboard...</span>
            <span v-else>Dashboard mit AI generieren</span>
          </ion-button>
        </ion-card-content>
      </ion-card>
      
      <!-- Loading -->
      <ion-card v-if="loading">
        <ion-card-content style="text-align: center; padding: 40px;">
          <ion-spinner name="crescent" style="width: 48px; height: 48px;"></ion-spinner>
          <p style="margin-top: 16px;">AI analysiert Ihre Formulare und erstellt passendes Dashboard...</p>
        </ion-card-content>
      </ion-card>
      
      <!-- Generiertes Dashboard anzeigen -->
      <ion-card v-if="step === 2 && generatedDashboard">
        <ion-card-header>
          <ion-card-title>{{ generatedDashboard.dashboard_title }}</ion-card-title>
          <ion-card-subtitle>{{ generatedDashboard.charts.length }} Charts vorgeschlagen</ion-card-subtitle>
        </ion-card-header>
        <ion-card-content>
          <!-- Chart Preview -->
          <div v-for="(chart, index) in generatedDashboard.charts" :key="index" style="margin-bottom: 16px;">
            <ion-item>
              <ion-icon :name="getChartIcon(chart.chart_type)" slot="start"></ion-icon>
              <ion-label>
                <h3>{{ getChartTypeName(chart.chart_type) }}</h3>
                <p>Daten: {{ chart.form }} → {{ chart.label }} / {{ chart.data }}</p>
                <p v-if="chart.date_stamps">Zeitraum: {{ chart.date_stamps }}</p>
              </ion-label>
            </ion-item>
          </div>
          
          <div style="margin-top: 24px;">
            <ion-button 
              expand="block" 
              color="success" 
              @click="createDashboard"
              :disabled="creating"
            >
              <ion-icon name="checkmark-circle" slot="start"></ion-icon>
              <span v-if="creating">Erstelle Dashboard...</span>
              <span v-else>Dashboard erstellen</span>
            </ion-button>
            
            <ion-button 
              expand="block" 
              fill="outline" 
              @click="goBack"
              style="margin-top: 8px;"
            >
              <ion-icon name="arrow-back" slot="start"></ion-icon>
              Neue Beschreibung
            </ion-button>
          </div>
        </ion-card-content>
      </ion-card>
      
      <!-- Erfolg -->
      <ion-card v-if="step === 3 && dashboardCreated">
        <ion-card-content style="text-align: center; padding: 40px;">
          <ion-icon name="checkmark-circle" style="font-size: 64px; color: var(--ion-color-success);"></ion-icon>
          <h2 style="margin-top: 16px;">Dashboard erstellt!</h2>
          <p>Ihr AI-generiertes Dashboard wurde erfolgreich erstellt.</p>
          
          <ion-button 
            expand="block" 
            @click="openDashboard"
            style="margin-top: 16px;"
          >
            <ion-icon name="open" slot="start"></ion-icon>
            Dashboard öffnen
          </ion-button>
          
          <ion-button 
            expand="block" 
            fill="outline" 
            @click="createAnother"
            style="margin-top: 8px;"
          >
            <ion-icon name="add" slot="start"></ion-icon>
            Weiteres Dashboard erstellen
          </ion-button>
        </ion-card-content>
      </ion-card>
      
      <!-- Error -->
      <ion-card v-if="error" color="danger">
        <ion-card-content>
          <ion-icon name="alert-circle" style="margin-right: 8px;"></ion-icon>
          {{ error }}
        </ion-card-content>
      </ion-card>
      
      <!-- Verfügbare Formulare anzeigen -->
      <ion-card v-if="step === 1 && availableForms.length > 0">
        <ion-card-header>
          <ion-card-title>Verfügbare Datenquellen</ion-card-title>
          <ion-card-subtitle>{{ availableForms.length }} Formulare gefunden</ion-card-subtitle>
        </ion-card-header>
        <ion-card-content>
          <div v-for="form in availableForms.slice(0, 3)" :key="form.form.title" style="margin-bottom: 8px;">
            <ion-chip>
              <ion-icon name="document-text" style="margin-right: 4px;"></ion-icon>
              {{ form.form.title }} ({{ form.form.inputs.length }} Felder)
            </ion-chip>
          </div>
          <p v-if="availableForms.length > 3" style="margin-top: 8px; color: var(--ion-color-medium);">
            +{{ availableForms.length - 3 }} weitere Formulare
          </p>
        </ion-card-content>
      </ion-card>
      
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
  IonItem, IonLabel, IonTextarea, IonButton, IonIcon, IonSpinner, IonChip
} from '@ionic/vue';

export default defineComponent({
  name: 'AIDashboardGenerator',
  components: {
    IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent,
    IonItem, IonLabel, IonTextarea, IonButton, IonIcon, IonSpinner, IonChip
  },
  data() {
    return {
      step: 1, // 1: Beschreibung, 2: Preview, 3: Erfolg
      description: '',
      loading: false,
      creating: false,
      error: null,
      generatedDashboard: null,
      dashboardCreated: null,
      availableForms: []
    };
  },
  async created() {
    await this.loadAvailableForms();
  },
  methods: {
    async loadAvailableForms() {
      try {
        const response = await this.$axios.post('form.php', this.$qs.stringify({
          get_forms: 'get_forms',
          project: this.$route.params.project
        }));
        
        this.availableForms = response.data || [];
      } catch (error) {
        console.error('Fehler beim Laden der Formulare:', error);
      }
    },
    
    async generateDashboard() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await this.$axios.post('ai_dashboard_generator.php', this.$qs.stringify({
          generate_dashboard: 'generate_dashboard',
          description: this.description,
          project: this.$route.params.project
        }));
        
        if (response.data.error) {
          this.error = response.data.error;
        } else {
          this.generatedDashboard = response.data;
          this.step = 2;
        }
      } catch (error) {
        this.error = 'Fehler beim Generieren des Dashboards: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    async createDashboard() {
      this.creating = true;
      this.error = null;
      
      try {
        const response = await this.$axios.post('ai_dashboard_generator.php', this.$qs.stringify({
          create_ai_dashboard: 'create_ai_dashboard',
          project: this.$route.params.project,
          dashboard_config: JSON.stringify(this.generatedDashboard)
        }));
        
        if (response.data.error) {
          this.error = response.data.error;
        } else {
          this.dashboardCreated = response.data;
          this.step = 3;
        }
      } catch (error) {
        this.error = 'Fehler beim Erstellen des Dashboards: ' + error.message;
      } finally {
        this.creating = false;
      }
    },
    
    openDashboard() {
      if (this.dashboardCreated && this.dashboardCreated.dashboard_url) {
        this.$router.push('/' + this.dashboardCreated.dashboard_url);
      }
    },
    
    createAnother() {
      this.step = 1;
      this.description = '';
      this.generatedDashboard = null;
      this.dashboardCreated = null;
      this.error = null;
    },
    
    goBack() {
      this.step = 1;
      this.generatedDashboard = null;
      this.error = null;
    },
    
    getChartIcon(chartType) {
      const icons = {
        'pie_chart': 'pie-chart',
        'donut_chart': 'pie-chart',
        'bar_chart': 'bar-chart',
        'date_bar_chart': 'trending-up'
      };
      return icons[chartType] || 'stats-chart';
    },
    
    getChartTypeName(chartType) {
      const names = {
        'pie_chart': 'Kreisdiagramm',
        'donut_chart': 'Donut-Diagramm',
        'bar_chart': 'Balkendiagramm',
        'date_bar_chart': 'Zeitverlauf-Diagramm'
      };
      return names[chartType] || chartType;
    }
  }
});
</script>

<style scoped>
ion-card {
  margin-bottom: 16px;
}

ion-chip {
  margin-right: 8px;
  margin-bottom: 4px;
}

.chart-preview {
  border: 1px solid var(--ion-color-light);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
}
</style>
