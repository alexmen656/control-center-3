<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <!-- Form Creation Mode Selection -->
            <ion-segment v-model="creationMode" class="mode-selector">
              <ion-segment-button value="create">
                <ion-label>Neue Tabelle erstellen</ion-label>
              </ion-segment-button>
              <ion-segment-button value="import">
                <ion-label>Bestehende Tabelle importieren</ion-label>
              </ion-segment-button>
            </ion-segment>

            <!-- Import Existing Table Section -->
            <ion-card v-if="creationMode === 'import'" class="import-card">
              <ion-card-header>
                <ion-card-title>
                  <ion-icon name="download-outline" color="primary"></ion-icon>
                  Tabelle importieren
                </ion-card-title>
                <ion-card-subtitle>
                  Wähle eine bestehende Tabelle aus einem anderen Projekt zum Importieren
                </ion-card-subtitle>
              </ion-card-header>
              
              <ion-card-content>
                <ion-item>
                  <ion-label position="stacked">Projekt auswählen</ion-label>
                  <ion-select 
                    v-model="selectedSourceProject" 
                    interface="popover" 
                    placeholder="Projekt wählen..."
                    @ionChange="loadTablesFromProject"
                  >
                    <ion-select-option 
                      v-for="project in availableProjects" 
                      :key="project.name" 
                      :value="project.name"
                    >
                      {{ project.display_name || project.name }}
                    </ion-select-option>
                  </ion-select>
                </ion-item>

                <ion-item v-if="selectedSourceProject">
                  <ion-label position="stacked">Tabelle auswählen</ion-label>
                  <ion-select 
                    v-model="selectedTable" 
                    interface="popover" 
                    placeholder="Tabelle wählen..."
                  >
                    <ion-select-option 
                      v-for="table in availableTables" 
                      :key="table.name" 
                      :value="table"
                    >
                      {{ table.display_name || table.name }}
                      <ion-note v-if="table.description" slot="end">{{ table.description }}</ion-note>
                    </ion-select-option>
                  </ion-select>
                </ion-item>

                <ion-item v-if="selectedTable">
                  <ion-label position="stacked">Neuer Tabellenname (optional)</ion-label>
                  <ion-input 
                    v-model="importTableName"
                    :placeholder="`project_${selectedTable.name}`"
                    fill="outline"
                  ></ion-input>
                  <ion-note slot="helper">
                    Standard: project_{{ selectedTable.name }}
                  </ion-note>
                </ion-item>

                <div class="import-preview" v-if="selectedTable">
                  <h4>Vorschau der zu importierenden Tabelle:</h4>
                  <ion-chip color="primary">
                    <ion-icon name="table-outline"></ion-icon>
                    <ion-label>{{ selectedTable.display_name || selectedTable.name }}</ion-label>
                  </ion-chip>
                  <p v-if="selectedTable.description">{{ selectedTable.description }}</p>
                </div>

                <ion-button 
                  expand="block" 
                  @click="importTable" 
                  :disabled="!selectedTable || isImporting"
                  color="primary"
                  class="import-button"
                >
                  <ion-icon name="download-outline" slot="start"></ion-icon>
                  {{ isImporting ? 'Importiere...' : 'Tabelle importieren' }}
                  <ion-spinner v-if="isImporting" slot="end"></ion-spinner>
                </ion-button>
              </ion-card-content>
            </ion-card>

            <!-- Create New Form Section -->
            <div v-if="creationMode === 'create'">
              <!-- AI Schema Generator Section -->
              <ion-card v-if="!showManualForm" class="ai-card">
                <ion-card-header>
                  <ion-card-title>
                    <ion-icon name="sparkles" color="primary"></ion-icon>
                    AI Schema Generator
                  </ion-card-title>
                  <ion-card-subtitle>
                    Beschreibe dein gewünschtes Formular und die AI erstellt automatisch die passende Struktur
                  </ion-card-subtitle>
                </ion-card-header>
                
                <ion-card-content>
                  <ion-item>
                    <ion-label position="stacked">Beschreibung *</ion-label>
                    <ion-textarea 
                      v-model="aiDescription"
                      placeholder="z.B. 'Produktverwaltung mit Namen, Preisen und Kategorien' oder 'Kundenverwaltung mit Kontaktdaten'"
                      rows="3"
                      maxlength="500"
                    ></ion-textarea>
                  </ion-item>
                  
                  <ion-item>
                    <ion-label position="stacked">Zusätzlicher Kontext (optional)</ion-label>
                    <ion-textarea 
                      v-model="aiContext"
                      placeholder="Weitere Details oder spezielle Anforderungen..."
                      rows="2"
                      maxlength="300"
                    ></ion-textarea>
                  </ion-item>
                  
                  <ion-item>
                    <ion-icon name="logo-openai" color="primary"></ion-icon>
                    <FloatingCheckbox 
                      v-model="checkForms"
                      label="Andere Forms beachten"
                    />
                  </ion-item>

                  <ion-item>
                    <ion-label>
                      <ion-icon name="logo-openai" color="primary"></ion-icon>
                      ChatGPT Schema Generator
                    </ion-label>
                    <ion-note slot="end" color="success">Empfohlen</ion-note>
                  </ion-item>
                  
                  <div class="ai-buttons">
                    <ion-button 
                      expand="block" 
                      @click="generateAiSchema" 
                      :disabled="!aiDescription.trim() || isGeneratingAi"
                      color="primary"
                    >
                      <ion-icon name="sparkles" slot="start"></ion-icon>
                      {{ isGeneratingAi ? 'Generiere Schema...' : 'AI Schema generieren' }}
                      <ion-spinner v-if="isGeneratingAi" slot="end"></ion-spinner>
                    </ion-button>
                    
                    <ion-button 
                      fill="outline" 
                      expand="block" 
                      @click="showManualForm = true"
                      style="margin-top: 8px;"
                    >
                      <ion-icon name="create-outline" slot="start"></ion-icon>
                      Manuell erstellen
                    </ion-button>
                  </div>
                  
                  <!-- Quick Examples -->
                  <div class="examples-section">
                    <ion-label class="examples-label">Beispiele:</ion-label>
                    <div class="examples-chips">
                      <ion-chip 
                        v-for="example in aiExamples" 
                        :key="example"
                        @click="aiDescription = example"
                        class="example-chip"
                      >
                        {{ example }}
                      </ion-chip>
                    </div>
                  </div>
                </ion-card-content>
              </ion-card>
              
              <!-- Manual Form Creation -->
              <div v-if="showManualForm">
                <div class="form-header">
                  <ion-input
                    v-model="title"
                    @input="logTitle"
                    label="Form Title"
                    label-placement="floating"
                    fill="outline"
                  />
                  <ion-button 
                    fill="clear" 
                    @click="showManualForm = false"
                    color="medium"
                    class="back-to-ai-btn"
                  >
                    <ion-icon name="arrow-back" slot="start"></ion-icon>
                    Zurück zur AI
                  </ion-button>
                </div>
                <form @submit.prevent="submitForm">
                  <ion-row v-for="(input, index) in formInputs" :key="index">
                    <ion-col size="3">
                      <ion-input
                        v-model="input.label"
                        label="Label"
                        label-placement="floating"
                        fill="outline"
                      />
                    </ion-col>
                    <ion-col size="3">
                      <ion-input
                        v-model="input.placeholder"
                        label="Placeholder"
                        label-placement="floating"
                        fill="outline"
                      />
                    </ion-col>
                    <ion-col size="3">
                      <ion-select
                        v-model="input.type"
                        label="Type"
                        aria-label="Type"
                        interface="popover"
                        fill="outline"
                        @ionChange="onTypeChange(index, input.type)"
                      >
                        <ion-select-option
                          v-for="iT in inputTypes"
                          :value="iT.value"
                          :key="iT.value"
                          >{{ iT.label }}</ion-select-option
                        >
                      </ion-select>
                    </ion-col>
                    <ion-col size="2">
                      <ion-button @click="removeInput(index)">Remove</ion-button>
                    </ion-col>
                    <ion-row v-if="input.type === 'select'">
                      <ion-col size="3">
                        <ion-label>Options</ion-label>
                      </ion-col>
                      <ion-col size="9">
                        <ion-row>
                          <ion-col
                            size="12"
                            v-for="(option, optIndex) in input.optionList"
                            :key="optIndex"
                            style="display: flex"
                          >
                            <ion-input
                              fill="outline"
                            />
                            <ion-button @click="removeOption(input, optIndex)"
                              >Remove Option</ion-button
                            >
                          </ion-col>
                        </ion-row>
                      </ion-col>
                      <ion-col size="12">
                        <ion-button @click="addOption(input)"
                          >Add Option</ion-button
                        >
                      </ion-col>
                    </ion-row>
                    <ion-row v-if="input.type === 'select2'">
                      <ion-col size="3">
                        <ion-label>Options</ion-label>
                      </ion-col>
                      <ion-col size="9">
                        <ion-row>
                          <ion-col size="12" style="display: flex">
                            <ion-select
                              v-model="input.optionList[0].value"
                            >
                            </ion-select>

                            <div v-for="form in forms" :key="form">
                            </div>
                          </ion-col>
                        </ion-row>
                      </ion-col>
                    </ion-row>
                    <ion-row v-if="input.type === 'operation'">
                      <ion-col size="3">
                        <ion-label>Operation</ion-label>
                      </ion-col>
                      <ion-col size="9">
                        <ion-row>
                          <ion-col size="12" style="display: flex">
                          </ion-col>
                        </ion-row>
                      </ion-col>
                    </ion-row>
                  </ion-row>
                  <ion-row>
                    <ion-col size="12">
                      <ion-button @click="addInput">Add Input</ion-button>
                      <ion-button @click="submitForm">Create Form</ion-button>
                    </ion-col>
                  </ion-row>
                </form>
              </div>
              <div v-html="jsonData"></div>
            </div>
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";

export default defineComponent({
  name: "NewForm",
  components: {
    FloatingCheckbox,
  },
  data() {
    return {
      creationMode: "create", // 'create' or 'import'
      
      // Import related data
      availableProjects: [],
      availableTables: [],
      selectedSourceProject: "",
      selectedTable: null,
      importTableName: "",
      isImporting: false,
      
      // Form creation data
      checkForms: false,
      formInputs: [
        {
          label: "",
          placeholder: "",
          type: "text",
          optionList: [{ value: "" }],
        },
      ],
      inputTypes: [
        { value: "text", label: "Text" },
        { value: "textarea", label: "Textarea" },
        { value: "number", label: "Number" },
        { value: "email", label: "Email" },
        { value: "password", label: "Password" },
        { value: "checkbox", label: "Checkbox" },
        { value: "color", label: "Color" },
        { value: "tel", label: "Tel" },
        { value: "date", label: "Date" },
        { value: "time", label: "Time" },
        { value: "select", label: "Select" },
        { value: "select2", label: "Select from Form (Form pipeline)" },
        { value: "operation", label: "Mathematic Operation" },
      ],
      jsonData: {},
      title: "",
      test: "",
      forms: [],
      selectedForm: "",
      
      // AI Properties
      showManualForm: false,
      aiDescription: '',
      aiContext: '',
      isGeneratingAi: false,
      aiExamples: [
        'Produktverwaltung mit Namen, Preisen und Kategorien',
        'Kundendatenbank mit Kontaktinformationen',
        'Mitarbeiterverwaltung mit Abteilungen',
        'Bestellsystem mit Artikeln und Adressen',
        'Veranstaltungsplaner mit Terminen',
        'Aufgabenverwaltung mit Prioritäten'
      ]
    };
  },
  computed: {
    numberInputs() {
      return this.formInputs.filter(input => input.type === 'number');
    }
  },
  async created() {
    // Load available projects for import
    await this.loadAvailableProjects();
    
    // Load forms for current project
    this.$axios
      .post(
        "form.php",
        this.$qs.stringify({
          get_forms: "get_forms",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        console.log(res);
        this.forms = res.data;
      });
  },

  methods: {
    async loadAvailableProjects() {
      try {
        const response = await this.$axios.post(
          "projects.php",
          this.$qs.stringify({
            get_projects_for_import: "get_projects_for_import",
            current_project: this.$route.params.project,
          })
        );
        this.availableProjects = response.data;
      } catch (error) {
        console.error('Error loading projects:', error);
        this.showToast('Fehler beim Laden der Projekte', 'danger');
      }
    },

    async loadTablesFromProject() {
      if (!this.selectedSourceProject) return;
      
      try {
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            get_tables_from_project: "get_tables_from_project",
            source_project: this.selectedSourceProject,
            exclude_project: this.$route.params.project,
          })
        );
        this.availableTables = response.data;
        this.selectedTable = null;
        this.importTableName = "";
      } catch (error) {
        console.error('Error loading tables:', error);
        this.showToast('Fehler beim Laden der Tabellen', 'danger');
      }
    },

    async importTable() {
      if (!this.selectedTable) return;
      
      this.isImporting = true;
      
      try {
        const newTableName = this.importTableName || `project_${this.selectedTable.name}`;
        
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            import_table: "import_table",
            source_project: this.selectedSourceProject,
            source_table: this.selectedTable.name,
            target_project: this.$route.params.project,
            new_table_name: newTableName,
          })
        );
        
        if (response.data.success) {
          // Create tool entry for the imported table
          await this.$axios.post(
            "tools.php",
            this.$qs.stringify({
              newTool: "newTool",
              toolIcon: "document-text-outline",
              projectName: this.$route.params.project,
              toolName: this.selectedTable.display_name || this.selectedTable.name,
            })
          );
          
          this.emitter.emit("updateSidebar");
          this.showToast('Tabelle erfolgreich importiert!', 'success');
          
          // Reset form
          this.selectedSourceProject = "";
          this.selectedTable = null;
          this.importTableName = "";
          this.availableTables = [];
        } else {
          this.showToast(response.data.message || 'Fehler beim Importieren der Tabelle', 'danger');
        }
      } catch (error) {
        console.error('Error importing table:', error);
        this.showToast('Netzwerkfehler beim Importieren der Tabelle', 'danger');
      } finally {
        this.isImporting = false;
      }
    },

    filteredNumberInputs(selectedValue) {
      return this.numberInputs.filter(input => this.toName(input.label) !== selectedValue);
    },
    
    addInput() {
      this.formInputs.push({
        label: "",
        placeholder: "",
        type: "text",
        optionList: [],
      });
    },
    removeInput(index) {
      this.formInputs.splice(index, 1);
    },
    onTypeChange(index, type) {
      console.log(index, type);
      if (type == "select2") {
        this.formInputs[index].optionList = [{ value: "" }, { value: "" }];
      } else if (type == "operation") {
        this.formInputs[index].optionList = [{ value: "" }, { value: "" }, { value: "" }];
      } else {
        this.formInputs[index].optionList = [{ value: "" }];
      }
    },
    addOption(input) {
      input.optionList.push({ value: "" });
    },
    removeOption(input, optIndex) {
      input.optionList.splice(optIndex, 1);
    },
    submitForm() {
      const formData = {
        title: this.title,
        inputs: this.formInputs.map((input) => ({
          type: input.type,
          name: input.label.toLowerCase().replace(/\s+/g, "_"),
          label: input.label,
          placeholder: input.placeholder,
          options:
            input.type === "select" || input.type === "select2" || input.type === "operation"
              ? input.optionList.map((option) => ({
                  value: option.value.toLowerCase().replace(/\s+/g, "_"),
                  label: option.value,
                }))
              : [],
        })),
      };
      console.log(formData);
      this.jsonData = JSON.stringify(formData, null, 2);
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            create_form: "create_form",
            form: this.jsonData,
            name: this.title.toLowerCase().replace(/\s+/g, "-"),
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.$axios
            .post(
              "tools.php",
              this.$qs.stringify({
                newTool: "newTool",
                toolIcon: "document-text-outline",
                projectName: this.$route.params.project,
                toolName: this.title,
              })
            )
            .then(() => {
              this.emitter.emit("updateSidebar");
            });
        });
    },
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
    
    // AI Methods
    async generateAiSchema() {
      if (!this.aiDescription.trim()) return;
      
      this.isGeneratingAi = true;
      
      try {
        const formData = new FormData();
        formData.append('generate_ai_schema', '1');
        formData.append('description', this.aiDescription);
        formData.append('context', this.aiContext);
        formData.append('checkForms', this.checkForms);
        formData.append('project', this.$route.params.project || '');
        
        const response = await this.$axios.post('ai_schema_generator.php', formData);
        
        if (response.data.success) {
          const schema = response.data.schema;
          
          // Apply AI generated schema to current form
          this.title = schema.title || '';
          this.formInputs = schema.inputs.map(input => ({
            label: input.label,
            placeholder: input.label,
            type: input.type,
            // Für select2 mit optionList vom Backend verwenden, sonst aus options konvertieren
            optionList: input.optionList ? input.optionList : 
                       (input.options ? input.options.map(opt => ({ value: opt })) : [{ value: "" }])
          }));
          
          console.log(this.formInputs);
          // Switch to manual form view to show the generated schema
          this.showManualForm = true;
          
          // Show success message
          this.showToast('AI Schema erfolgreich generiert! Du kannst es jetzt bearbeiten.', 'success');
        } else {
          this.showToast(response.data.message || 'Fehler beim Generieren des Schemas', 'danger');
        }
      } catch (error) {
        console.error('Error generating AI schema:', error);
        this.showToast('Netzwerkfehler beim Generieren des Schemas', 'danger');
      } finally {
        this.isGeneratingAi = false;
      }
    },
    
    async showToast(message, color = 'medium') {
      if (this.$ionic && this.$ionic.toastController) {
        const toast = await this.$ionic.toastController.create({
          message,
          duration: 3000,
          color,
          position: 'top'
        });
        await toast.present();
      }
    },
  },
});
</script>

<style scoped>
.mode-selector {
  margin-bottom: 20px;
}

.import-card {
  margin-bottom: 20px;
  border-left: 4px solid var(--ion-color-secondary);
}

.import-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.import-preview {
  margin: 16px 0;
  padding: 16px;
  background: var(--ion-color-step-50);
  border-radius: 8px;
}

.import-preview h4 {
  margin: 0 0 8px 0;
  color: var(--ion-color-primary);
}

.import-button {
  margin-top: 16px;
}

/* AI Integration Styles */
.ai-card {
  margin-bottom: 20px;
  border-left: 4px solid var(--ion-color-primary);
}

.ai-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-buttons {
  margin-top: 16px;
}

.examples-section {
  margin-top: 20px;
}

.examples-label {
  font-weight: 600;
  color: var(--ion-color-primary);
  margin-bottom: 8px;
  display: block;
}

.examples-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.example-chip {
  cursor: pointer;
  transition: transform 0.2s;
}

.example-chip:hover {
  transform: scale(1.05);
}

.form-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.back-to-ai-btn {
  flex-shrink: 0;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .form-header {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .examples-chips {
    justify-content: center;
  }
}
</style>
