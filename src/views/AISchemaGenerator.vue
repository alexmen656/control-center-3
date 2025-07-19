<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>AI Schema Generator</ion-title>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/"></ion-back-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    
    <ion-content class="ion-padding">
      <div class="ai-schema-container">
        <!-- Eingabebereich -->
        <ion-card class="input-card">
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="brain-outline"></ion-icon>
              Beschreibe dein gewünschtes Formular
            </ion-card-title>
            <ion-card-subtitle>
              Die AI analysiert deine Beschreibung und erstellt automatisch ein passendes Datenbankschema
            </ion-card-subtitle>
          </ion-card-header>
          
          <ion-card-content>
            <ion-item>
              <ion-label position="stacked">Beschreibung *</ion-label>
              <ion-textarea 
                v-model="description"
                placeholder="z.B. 'Ich möchte eine Produktdatenbank mit Namen, Preisen und Kategorien erstellen' oder 'Ein System für Kundenverwaltung mit Kontaktdaten und Notizen'"
                rows="4"
                maxlength="500"
                show-clear-button
              ></ion-textarea>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Zusätzlicher Kontext (optional)</ion-label>
              <ion-textarea 
                v-model="context"
                placeholder="Weitere Details, spezielle Anforderungen oder Beispiele..."
                rows="2"
                maxlength="300"
              ></ion-textarea>
            </ion-item>
            
            <ion-item>
              <ion-label>AI Provider</ion-label>
              <ion-select v-model="selectedProvider" interface="popover">
                <ion-select-option value="local">Lokale AI (Regelbasiert)</ion-select-option>
                <ion-select-option value="openai">OpenAI GPT (API Key erforderlich)</ion-select-option>
                <ion-select-option value="claude">Anthropic Claude (API Key erforderlich)</ion-select-option>
              </ion-select>
            </ion-item>
            
            <ion-button 
              expand="block" 
              @click="generateSchema" 
              :disabled="!description.trim() || isGenerating"
              class="generate-btn"
            >
              <ion-icon name="sparkles" slot="start"></ion-icon>
              {{ isGenerating ? 'Generiere Schema...' : 'Schema generieren' }}
              <ion-spinner v-if="isGenerating" slot="end"></ion-spinner>
            </ion-button>
          </ion-card-content>
        </ion-card>
        
        <!-- Vorschau des generierten Schemas -->
        <ion-card v-if="generatedSchema" class="schema-preview">
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="document-text-outline"></ion-icon>
              Generiertes Schema
            </ion-card-title>
            <ion-card-subtitle>
              Überprüfe und bearbeite das Schema vor der Erstellung
            </ion-card-subtitle>
          </ion-card-header>
          
          <ion-card-content>
            <ion-item>
              <ion-label position="stacked">Titel</ion-label>
              <ion-input v-model="generatedSchema.title"></ion-input>
            </ion-item>
            
            <ion-item>
              <ion-label position="stacked">Beschreibung</ion-label>
              <ion-textarea v-model="generatedSchema.description" rows="2"></ion-textarea>
            </ion-item>
            
            <div class="fields-section">
              <h3>Felder ({{ generatedSchema.inputs?.length || 0 }})</h3>
              
              <div v-for="(field, index) in generatedSchema.inputs" :key="index" class="field-item">
                <ion-card class="field-card">
                  <ion-card-content>
                    <ion-grid>
                      <ion-row>
                        <ion-col size="6">
                          <ion-item>
                            <ion-label position="stacked">Feldname</ion-label>
                            <ion-input v-model="field.name"></ion-input>
                          </ion-item>
                        </ion-col>
                        <ion-col size="6">
                          <ion-item>
                            <ion-label position="stacked">Label</ion-label>
                            <ion-input v-model="field.label"></ion-input>
                          </ion-item>
                        </ion-col>
                      </ion-row>
                      <ion-row>
                        <ion-col size="4">
                          <ion-item>
                            <ion-label>Typ</ion-label>
                            <ion-select v-model="field.type" interface="popover">
                              <ion-select-option value="text">Text</ion-select-option>
                              <ion-select-option value="textarea">Textarea</ion-select-option>
                              <ion-select-option value="number">Zahl</ion-select-option>
                              <ion-select-option value="email">E-Mail</ion-select-option>
                              <ion-select-option value="select">Dropdown</ion-select-option>
                              <ion-select-option value="checkbox">Checkbox</ion-select-option>
                              <ion-select-option value="date">Datum</ion-select-option>
                              <ion-select-option value="time">Zeit</ion-select-option>
                            </ion-select>
                          </ion-item>
                        </ion-col>
                        <ion-col size="4">
                          <ion-item>
                            <ion-label>Erforderlich</ion-label>
                            <ion-checkbox v-model="field.required"></ion-checkbox>
                          </ion-item>
                        </ion-col>
                        <ion-col size="4">
                          <ion-button 
                            fill="clear" 
                            color="danger" 
                            @click="removeField(index)"
                            size="small"
                          >
                            <ion-icon name="trash"></ion-icon>
                          </ion-button>
                        </ion-col>
                      </ion-row>
                      <ion-row v-if="field.type === 'select'">
                        <ion-col size="12">
                          <ion-item>
                            <ion-label position="stacked">Optionen (kommagetrennt)</ion-label>
                            <ion-input 
                              :value="field.options?.join(', ') || ''"
                              @input="updateFieldOptions(field, $event.target.value)"
                              placeholder="Option 1, Option 2, Option 3"
                            ></ion-input>
                          </ion-item>
                        </ion-col>
                      </ion-row>
                    </ion-grid>
                  </ion-card-content>
                </ion-card>
              </div>
              
              <ion-button 
                fill="outline" 
                @click="addField" 
                expand="block"
                class="add-field-btn"
              >
                <ion-icon name="add" slot="start"></ion-icon>
                Feld hinzufügen
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>
        
        <!-- Formular erstellen -->
        <ion-card v-if="generatedSchema" class="create-form-card">
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="create-outline"></ion-icon>
              Formular erstellen
            </ion-card-title>
          </ion-card-header>
          
          <ion-card-content>
            <ion-item>
              <ion-label position="stacked">Formularname *</ion-label>
              <ion-input 
                v-model="formName" 
                placeholder="z.B. Produktverwaltung"
                maxlength="50"
              ></ion-input>
            </ion-item>
            
            <ion-button 
              expand="block" 
              @click="createForm"
              :disabled="!formName.trim() || isCreating"
              color="success"
              class="create-btn"
            >
              <ion-icon name="checkmark-circle" slot="start"></ion-icon>
              {{ isCreating ? 'Erstelle Formular...' : 'Formular erstellen' }}
              <ion-spinner v-if="isCreating" slot="end"></ion-spinner>
            </ion-button>
          </ion-card-content>
        </ion-card>
        
        <!-- Beispiele und Hilfe -->
        <ion-card class="help-card">
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="help-circle-outline"></ion-icon>
              Beispiele und Tipps
            </ion-card-title>
          </ion-card-header>
          
          <ion-card-content>
            <div class="examples">
              <h4>Beispiel-Beschreibungen:</h4>
              <ion-chip 
                v-for="example in examples" 
                :key="example"
                @click="description = example"
                class="example-chip"
              >
                {{ example }}
              </ion-chip>
            </div>
            
            <div class="tips">
              <h4>Tipps für bessere Ergebnisse:</h4>
              <ul>
                <li>Beschreibe konkret, welche Art von Daten du verwalten möchtest</li>
                <li>Erwähne wichtige Felder, die auf jeden Fall enthalten sein sollen</li>
                <li>Gib den Verwendungszweck an (z.B. "für meinen Online-Shop")</li>
                <li>Nutze Beispiele: "wie Name, Preis, Kategorie"</li>
              </ul>
            </div>
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonItem,
  IonLabel,
  IonTextarea,
  IonInput,
  IonSelect,
  IonSelectOption,
  IonButton,
  IonIcon,
  IonSpinner,
  IonGrid,
  IonRow,
  IonCol,
  IonCheckbox,
  IonChip,
  IonButtons,
  IonBackButton,
  toastController
} from '@ionic/vue';

export default defineComponent({
  name: 'AISchemaGenerator',
  components: {
    IonPage,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardSubtitle,
    IonCardContent,
    IonItem,
    IonLabel,
    IonTextarea,
    IonInput,
    IonSelect,
    IonSelectOption,
    IonButton,
    IonIcon,
    IonSpinner,
    IonGrid,
    IonRow,
    IonCol,
    IonCheckbox,
    IonChip,
    IonButtons,
    IonBackButton
  },
  data() {
    return {
      description: '',
      context: '',
      selectedProvider: 'local',
      generatedSchema: null,
      formName: '',
      isGenerating: false,
      isCreating: false,
      examples: [
        'Produktverwaltung mit Namen, Preisen und Kategorien',
        'Kundendatenbank mit Kontaktinformationen',
        'Mitarbeiterverwaltung mit Abteilungen und Gehältern',
        'Bestellsystem mit Artikeln und Lieferadressen',
        'Veranstaltungsplaner mit Terminen und Locations',
        'Aufgabenverwaltung mit Prioritäten und Deadlines',
        'Lagerverwaltung mit Beständen und Lieferanten'
      ]
    };
  },
  methods: {
    async generateSchema() {
      if (!this.description.trim()) return;
      
      this.isGenerating = true;
      
      try {
        const formData = new FormData();
        formData.append('generate_ai_schema', '1');
        formData.append('description', this.description);
        formData.append('context', this.context);
        formData.append('provider', this.selectedProvider);
        
        const response = await this.$axios.post('ai_schema_generator.php', formData);
        
        if (response.data.success) {
          this.generatedSchema = response.data.schema;
          this.formName = this.generatedSchema.title || '';
          
          await this.showToast('Schema erfolgreich generiert!', 'success');
        } else {
          await this.showToast(response.data.message || 'Fehler beim Generieren des Schemas', 'danger');
        }
      } catch (error) {
        console.error('Error generating schema:', error);
        await this.showToast('Netzwerkfehler beim Generieren des Schemas', 'danger');
      } finally {
        this.isGenerating = false;
      }
    },
    
    async createForm() {
      if (!this.formName.trim() || !this.generatedSchema) return;
      
      this.isCreating = true;
      
      try {
        const formData = new FormData();
        formData.append('create_ai_form', '1');
        formData.append('schema', JSON.stringify(this.generatedSchema));
        formData.append('name', this.formName);
        formData.append('project', this.$route.params.project);
        
        const response = await this.$axios.post('ai_schema_generator.php', formData);
        
        if (response.data.success) {
          await this.showToast('Formular erfolgreich erstellt!', 'success');
          
          // Navigate to form management or created form
          setTimeout(() => {
            this.$router.push(`/project/${this.$route.params.project}/forms`);
          }, 1500);
        } else {
          await this.showToast(response.data.message || 'Fehler beim Erstellen des Formulars', 'danger');
        }
      } catch (error) {
        console.error('Error creating form:', error);
        await this.showToast('Netzwerkfehler beim Erstellen des Formulars', 'danger');
      } finally {
        this.isCreating = false;
      }
    },
    
    addField() {
      if (!this.generatedSchema.inputs) {
        this.generatedSchema.inputs = [];
      }
      
      this.generatedSchema.inputs.push({
        name: 'neues_feld',
        type: 'text',
        label: 'Neues Feld',
        required: false
      });
    },
    
    removeField(index) {
      this.generatedSchema.inputs.splice(index, 1);
    },
    
    updateFieldOptions(field, value) {
      if (value.trim()) {
        field.options = value.split(',').map(opt => opt.trim());
      } else {
        field.options = [];
      }
    },
    
    async showToast(message, color = 'medium') {
      const toast = await toastController.create({
        message,
        duration: 3000,
        color,
        position: 'top'
      });
      await toast.present();
    }
  }
});
</script>

<style scoped>
.ai-schema-container {
  max-width: 800px;
  margin: 0 auto;
}

.input-card {
  margin-bottom: 20px;
}

.input-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.generate-btn {
  margin-top: 16px;
}

.schema-preview {
  margin-bottom: 20px;
}

.schema-preview ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.fields-section {
  margin-top: 20px;
}

.fields-section h3 {
  margin-bottom: 16px;
  color: var(--ion-color-primary);
}

.field-item {
  margin-bottom: 12px;
}

.field-card {
  border-left: 4px solid var(--ion-color-primary);
}

.add-field-btn {
  margin-top: 16px;
}

.create-form-card {
  margin-bottom: 20px;
}

.create-form-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.create-btn {
  margin-top: 16px;
}

.help-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.examples {
  margin-bottom: 20px;
}

.examples h4 {
  margin-bottom: 12px;
  color: var(--ion-color-primary);
}

.example-chip {
  cursor: pointer;
  margin: 4px;
  transition: transform 0.2s;
}

.example-chip:hover {
  transform: scale(1.05);
}

.tips h4 {
  margin-bottom: 12px;
  color: var(--ion-color-primary);
}

.tips ul {
  margin: 0;
  padding-left: 20px;
}

.tips li {
  margin-bottom: 8px;
  line-height: 1.4;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .ai-schema-container {
    padding: 0 8px;
  }
  
  .field-card ion-grid ion-col[size="6"] {
    --ion-col-width: 100%;
  }
  
  .field-card ion-grid ion-col[size="4"] {
    --ion-col-width: 100%;
  }
}
</style>
