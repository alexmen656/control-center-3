<template>
  <ion-page>
    <ion-content class="modern-content" :scroll-y="true">
      <SiteTitle icon="grid-outline" title="New Table" />
      <div class="new-form-page">
        <div class="page-header">
          <h1 class="page-title">
            <ion-icon name="albums-outline"></ion-icon>
            Create New Table
          </h1>
          <p class="page-subtitle">
            Build a table from scratch, generate one with AI, or import an existing table.
          </p>
        </div>

        <div class="mode-tabs">
          <button class="mode-tab" :class="{ active: creationMode === 'create' }" @click="creationMode = 'create'">
            <ion-icon name="add-circle-outline"></ion-icon>
            <span>Create new table</span>
          </button>
          <button class="mode-tab" :class="{ active: creationMode === 'import' }" @click="creationMode = 'import'">
            <ion-icon name="download-outline"></ion-icon>
            <span>Import existing table</span>
          </button>
        </div>

        <div v-if="creationMode === 'import'" class="panel accent-secondary">
          <div class="panel-header">
            <div class="panel-icon secondary">
              <ion-icon name="download-outline"></ion-icon>
            </div>
            <div class="panel-heading">
              <h2>Import table</h2>
              <p>Choose an existing table from another project to import.</p>
            </div>
          </div>

          <div class="panel-body">
            <div class="field">
              <label class="field-label">Select project</label>
              <ion-select class="modern-select" v-model="selectedSourceProject" interface="popover"
                placeholder="Choose project..." @ionChange="loadTablesFromProject">
                <ion-select-option v-for="project in availableProjects" :key="project.name" :value="project.name">
                  {{ project.display_name || project.name }}
                </ion-select-option>
              </ion-select>
            </div>

            <div class="field" v-if="selectedSourceProject">
              <label class="field-label">Select table</label>
              <ion-select class="modern-select" v-model="selectedTable" interface="popover"
                placeholder="Choose table...">
                <ion-select-option v-for="table in availableTables" :key="table.name" :value="table">
                  {{ table.display_name || table.name }}
                </ion-select-option>
              </ion-select>
            </div>

            <div class="field" v-if="selectedTable">
              <label class="field-label">New table name (optional)</label>
              <input class="modern-input" v-model="importTableName" :placeholder="`project_${selectedTable.name}`" />
              <span class="field-hint">Default: project_{{ selectedTable.name }}</span>
            </div>

            <div class="import-preview" v-if="selectedTable">
              <span class="preview-label">Preview of table to import</span>
              <div class="preview-chip">
                <ion-icon name="grid-outline"></ion-icon>
                <span>{{ selectedTable.display_name || selectedTable.name }}</span>
              </div>
              <p v-if="selectedTable.description" class="preview-desc">
                {{ selectedTable.description }}
              </p>
            </div>

            <button class="btn btn-primary btn-block" @click="importTable" :disabled="!selectedTable || isImporting">
              <ion-icon name="download-outline"></ion-icon>
              {{ isImporting ? 'Importing...' : 'Import table' }}
              <ion-spinner v-if="isImporting" name="crescent"></ion-spinner>
            </button>
          </div>
        </div>

        <div v-if="creationMode === 'create'">
          <div v-if="!showManualForm" class="panel accent-primary">
            <div class="panel-header">
              <div class="panel-icon primary">
                <ion-icon name="sparkles"></ion-icon>
              </div>
              <div class="panel-heading">
                <h2>AI Schema Generator</h2>
                <p>Describe the table you want and AI will build the structure for you.</p>
              </div>
              <span class="recommended-badge">Recommended</span>
            </div>

            <div class="panel-body">
              <div class="field">
                <label class="field-label">Description <span class="req">*</span></label>
                <textarea class="modern-input modern-textarea" v-model="aiDescription"
                  placeholder="e.g. 'Product management with names, prices and categories' or 'Customer management with contact details'"
                  rows="3" maxlength="500"></textarea>
              </div>

              <div class="field">
                <label class="field-label">Additional context (optional)</label>
                <textarea class="modern-input modern-textarea" v-model="aiContext"
                  placeholder="More details or special requirements..." rows="2" maxlength="300"></textarea>
              </div>

              <div class="toggle-row">
                <FloatingCheckbox v-model="checkTables" label="Consider other forms" />
              </div>

              <div class="ai-buttons">
                <button class="btn btn-primary btn-block" @click="generateAiSchema"
                  :disabled="!aiDescription.trim() || isGeneratingAi">
                  <ion-icon name="sparkles"></ion-icon>
                  {{ isGeneratingAi ? 'Generating schema...' : 'Generate AI schema' }}
                  <ion-spinner v-if="isGeneratingAi" name="crescent"></ion-spinner>
                </button>

                <button class="btn btn-ghost btn-block" @click="showManualForm = true">
                  <ion-icon name="create-outline"></ion-icon>
                  Create manually
                </button>
              </div>

              <div class="examples-section">
                <span class="examples-label">Examples</span>
                <div class="examples-chips">
                  <button v-for="example in aiExamples" :key="example" class="example-chip"
                    @click="aiDescription = example">
                    {{ example }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="showManualForm" class="panel">
            <div class="panel-header">
              <div class="panel-icon primary">
                <ion-icon name="construct-outline"></ion-icon>
              </div>
              <div class="panel-heading">
                <h2>Table builder</h2>
                <p>Define the fields of your table.</p>
              </div>
              <button class="btn btn-ghost btn-sm" @click="showManualForm = false">
                <ion-icon name="arrow-back"></ion-icon>
                Back to AI
              </button>
            </div>

            <div class="panel-body">
              <div class="field">
                <label class="field-label">Table title</label>
                <input class="modern-input" v-model="title" placeholder="e.g. Customers" />
              </div>

              <form @submit.prevent="submitForm">
                <div class="fields-list">
                  <div class="field-card" v-for="(input, index) in formInputs" :key="index">
                    <div class="field-card-header">
                      <span class="field-number">Field {{ index + 1 }}</span>
                      <button type="button" class="icon-btn danger" @click="removeInput(index)" title="Remove field">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>

                    <div class="field-grid">
                      <div class="field">
                        <label class="field-label">Label</label>
                        <input class="modern-input" v-model="input.label" placeholder="Label" />
                      </div>
                      <div class="field">
                        <label class="field-label">Placeholder</label>
                        <input class="modern-input" v-model="input.placeholder" placeholder="Placeholder" />
                      </div>
                      <div class="field">
                        <label class="field-label">Type</label>
                        <ion-select class="modern-select" v-model="input.type" aria-label="Type" interface="popover"
                          @ionChange="onTypeChange(index, input.type)">
                          <ion-select-option v-for="iT in inputTypes" :value="iT.value" :key="iT.value">{{ iT.label
                          }}</ion-select-option>
                        </ion-select>
                      </div>
                    </div>

                    <div class="sub-section" v-if="input.type === 'select'">
                      <label class="field-label">Options</label>
                      <div class="option-row" v-for="(option, optIndex) in input.optionList" :key="optIndex">
                        <input class="modern-input" placeholder="Option value" />
                        <button type="button" class="icon-btn danger" @click="removeOption(input, optIndex)"
                          title="Remove option">
                          <ion-icon name="close-outline"></ion-icon>
                        </button>
                      </div>
                      <button type="button" class="btn btn-ghost btn-sm" @click="addOption(input)">
                        <ion-icon name="add-outline"></ion-icon>
                        Add option
                      </button>
                    </div>

                    <div class="sub-section" v-if="input.type === 'select2'">
                      <label class="field-label">Options</label>
                      <ion-select class="modern-select" v-model="input.optionList[0].value">
                      </ion-select>
                      <div v-for="form in forms" :key="form"></div>
                    </div>

                    <div class="sub-section" v-if="input.type === 'operation'">
                      <label class="field-label">Operation</label>
                    </div>
                  </div>
                </div>

                <div class="builder-actions">
                  <button type="button" class="btn btn-ghost" @click="addInput">
                    <ion-icon name="add-outline"></ion-icon>
                    Add field
                  </button>
                  <button type="button" class="btn btn-primary" @click="submitForm">
                    <ion-icon name="checkmark-outline"></ion-icon>
                    Create table
                  </button>
                </div>
              </form>
            </div>
          </div>
          <div v-html="jsonData"></div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import SiteTitle from "@/components/SiteTitle.vue";

export default defineComponent({
  name: "NewForm",
  components: {
    FloatingCheckbox,
    SiteTitle,
  },
  data() {
    return {
      creationMode: "create",

      availableProjects: [],
      availableTables: [],
      selectedSourceProject: "",
      selectedTable: null,
      importTableName: "",
      isImporting: false,

      checkTables: false,
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
    await this.loadAvailableProjects();

    this.$axios
      .post(
        "table.php",
        this.$qs.stringify({
          get_tables: "get_tables",
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
          "table.php",
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
          "table.php",
          this.$qs.stringify({
            import_table: "import_table",
            source_project: this.selectedSourceProject,
            source_table: this.selectedTable.name,
            target_project: this.$route.params.project,
            new_table_name: newTableName,
          })
        );

        if (response.data.success) {
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
          "table.php",
          this.$qs.stringify({
            create_table: "create_table",
            table: this.jsonData,
            name: this.title.toLowerCase().replace(/\s+/g, "-"),
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.emitter.emit("updateSidebar");
        });
    },
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },


    async generateAiSchema() {
      if (!this.aiDescription.trim()) return;
      this.isGeneratingAi = true;

      try {
        const response = await this.$axios.post('v2/ai-schema/generate', {
          description: this.aiDescription,
          context: this.aiContext,
          checkTables: this.checkTables,
          project: this.$route.params.project || ''
        });

        if (response.data.success) {
          const schema = response.data.schema;

          this.title = schema.title || '';
          this.formInputs = schema.inputs.map(input => ({
            label: input.label,
            placeholder: input.label,
            type: input.type,
            optionList: input.optionList ? input.optionList :
              (input.options ? input.options.map(opt => ({ value: opt })) : [{ value: "" }])
          }));

          console.log(this.formInputs);
          this.showManualForm = true;

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
.modern-content {
  --background: #f8fafc;
}

.new-form-page {
  --primary-color: #f97316;
  --primary-hover: #ea580c;
  --secondary-color: #0ea5e9;
  --background: #f8fafc;
  --surface: #ffffff;
  --surface-alt: #f9fafb;
  --border: #e2e8f0;
  --border-strong: #cbd5e1;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --danger: #ef4444;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.06);
  --shadow-md: 0 4px 12px -2px rgb(0 0 0 / 0.12);
  --radius: 10px;
  --radius-sm: 8px;
  padding: 24px;
  max-width: 900px;
  margin: 0 auto;
  background: var(--background);
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 6px 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--text-primary);
}

.page-title ion-icon {
  font-size: 28px;
  color: var(--primary-color);
}

.page-subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

/* Mode tabs */
.mode-tabs {
  display: flex;
  gap: 6px;
  padding: 5px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 24px;
}

.mode-tab {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 16px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.mode-tab ion-icon {
  font-size: 18px;
}

.mode-tab:hover {
  color: var(--text-primary);
  background: var(--surface-alt);
}

.mode-tab.active {
  background: var(--primary-color);
  color: #fff;
}

.mode-tab.active:hover {
  background: var(--primary-hover);
}

/* Panel / cards */
.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 20px;
  overflow: hidden;
}

.panel.accent-primary {
  border-top: 3px solid var(--primary-color);
}

.panel.accent-secondary {
  border-top: 3px solid var(--secondary-color);
}

.panel-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px;
  border-bottom: 1px solid var(--border);
}

.panel-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 10px;
  font-size: 22px;
}

.panel-icon ion-icon {
  font-size: 22px;
}

.panel-icon.primary {
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
}

.panel-icon.secondary {
  background: rgba(14, 165, 233, 0.1);
  color: var(--secondary-color);
}

.panel-heading {
  flex: 1;
  min-width: 0;
}

.panel-heading h2 {
  margin: 0 0 3px 0;
  font-size: 17px;
  font-weight: 600;
  color: var(--text-primary);
}

.panel-heading p {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

.recommended-badge {
  flex-shrink: 0;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
  font-size: 12px;
  font-weight: 600;
}

.panel-body {
  padding: 20px;
}

/* Fields */
.field {
  margin-bottom: 16px;
}

.field-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}

.field-label .req {
  color: var(--danger);
}

.field-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: var(--text-muted);
}

.modern-input {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  box-sizing: border-box;
}

.modern-input::placeholder {
  color: var(--text-muted);
}

.modern-input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
}

.modern-textarea {
  resize: vertical;
  min-height: 60px;
  line-height: 1.5;
}

.modern-select {
  width: 100%;
  --padding-start: 14px;
  --padding-end: 14px;
  --padding-top: 11px;
  --padding-bottom: 11px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-primary);
  font-size: 14px;
  min-height: 44px;
}

.toggle-row {
  margin-bottom: 16px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 18px;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease,
    transform 0.15s ease, opacity 0.15s ease;
  white-space: nowrap;
}

.btn ion-icon {
  font-size: 18px;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-block {
  width: 100%;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 13px;
}

.btn-primary {
  background: var(--primary-color);
  color: #fff;
}

.btn-primary:not(:disabled):hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.btn-ghost {
  background: var(--surface);
  color: var(--text-secondary);
  border-color: var(--border);
}

.btn-ghost:not(:disabled):hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
  background: rgba(249, 115, 22, 0.06);
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s ease;
}

.icon-btn ion-icon {
  font-size: 18px;
}

.icon-btn.danger:hover {
  color: var(--danger);
  border-color: var(--danger);
  background: rgba(239, 68, 68, 0.08);
}

/* AI section */
.ai-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 4px;
}

.examples-section {
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

.examples-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 10px;
}

.examples-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.example-chip {
  padding: 7px 12px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--surface-alt);
  color: var(--text-secondary);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.example-chip:hover {
  color: var(--primary-color);
  border-color: var(--primary-color);
  background: rgba(249, 115, 22, 0.06);
  transform: translateY(-1px);
}

/* Import preview */
.import-preview {
  margin: 4px 0 18px 0;
  padding: 16px;
  background: var(--surface-alt);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}

.preview-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 10px;
}

.preview-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(249, 115, 22, 0.1);
  color: var(--primary-color);
  font-size: 13px;
  font-weight: 600;
}

.preview-chip ion-icon {
  font-size: 16px;
}

.preview-desc {
  margin: 10px 0 0 0;
  font-size: 13px;
  color: var(--text-secondary);
}

/* Manual builder */
.fields-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 18px;
}

.field-card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface-alt);
}

.field-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.field-number {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-muted);
}

.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}

.field-grid .field {
  margin-bottom: 0;
}

.sub-section {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px dashed var(--border-strong);
}

.option-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.option-row .modern-input {
  flex: 1;
}

.builder-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

/* Responsive */
@media (max-width: 768px) {
  .new-form-page {
    padding: 16px;
  }

  .mode-tab span {
    display: none;
  }

  .field-grid {
    grid-template-columns: 1fr;
  }

  .panel-header {
    flex-wrap: wrap;
  }

  .recommended-badge {
    order: 3;
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
  }

  .new-form-page {
    --background: #121212;
    --surface: #1a1a1a;
    --surface-alt: #222222;
    --border: #2a2a2a;
    --border-strong: #3a3a3a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.4);
    --shadow-md: 0 4px 12px -2px rgb(0 0 0 / 0.5);
  }
}
</style>
