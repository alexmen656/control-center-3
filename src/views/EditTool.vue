<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="settings-outline" :title="'Edit: ' + title" />
      <div class="page-container">
        <div class="page-header">
          <div class="header-content">
            <h1>Form Editor</h1>
            <p>Configure form structure and field types</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="goBack">
              <ion-icon name="arrow-back-outline"></ion-icon>
              Back
            </button>
            <button class="action-btn primary" @click="updateForm" :disabled="saving">
              <ion-icon name="save-outline"></ion-icon>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>

        <div v-if="loading" class="loading-state">
          <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
          <p>Loading form data...</p>
        </div>

        <div v-else class="form-editor-container">
          <div class="form-title-card">
            <div class="form-group">
              <label class="form-label">Form Title</label>
              <input
                v-model="title"
                type="text"
                placeholder="Enter form title"
                class="modern-input"
              />
            </div>
          </div>

          <div class="fields-card">
            <div class="card-header">
              <h3>Form Fields</h3>
              <button class="action-btn primary sm" @click="addInput">
                <ion-icon name="add-outline"></ion-icon>
                Add Field
              </button>
            </div>

            <div class="fields-list">
              <div v-for="(input, index) in formInputs" :key="index" class="field-item">
                <div class="field-header">
                  <div class="field-number">{{ index + 1 }}</div>
                  <button class="icon-btn delete-btn" @click="removeInput(index)" title="Remove Field">
                    <ion-icon name="trash-outline"></ion-icon>
                  </button>
                </div>

                <div class="field-content">
                  <div class="field-row">
                    <div class="form-group">
                      <label class="form-label">Label</label>
                      <input
                        v-model="input.label"
                        type="text"
                        placeholder="Field label"
                        class="modern-input"
                      />
                    </div>
                    <div class="form-group">
                      <label class="form-label">Placeholder</label>
                      <input
                        v-model="input.placeholder"
                        type="text"
                        placeholder="Field placeholder"
                        class="modern-input"
                      />
                    </div>
                    <div class="form-group">
                      <label class="form-label">Type</label>
                      <select
                        v-model="input.type"
                        class="modern-select"
                        @change="onTypeChange(index, input.type)"
                      >
                        <option
                          v-for="iT in inputTypes"
                          :key="iT.value"
                          :value="iT.value"
                        >
                          {{ iT.label }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- Options for select fields -->
                  <div v-if="input.type === 'select'" class="options-section">
                    <div class="options-header">
                      <label class="form-label">Options</label>
                      <button class="action-btn secondary sm" @click="addOption(input)">
                        <ion-icon name="add-outline"></ion-icon>
                        Add Option
                      </button>
                    </div>
                    <div class="options-list">
                      <div
                        v-for="(option, optIndex) in input.optionList"
                        :key="optIndex"
                        class="option-item"
                      >
                        <input
                          v-model="option.value"
                          type="text"
                          placeholder="Option value"
                          class="modern-input"
                        />
                        <button class="icon-btn delete-btn" @click="removeOption(input, optIndex)">
                          <ion-icon name="close-outline"></ion-icon>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Select2 (Form pipeline) options -->
                  <div v-if="input.type === 'select2'" class="options-section">
                    <label class="form-label">Form Pipeline Configuration</label>
                    <div class="field-row">
                      <div class="form-group">
                        <select
                          v-model="input.optionList[0].value"
                          class="modern-select"
                        >
                          <option value="">Select Form</option>
                          <option
                            v-for="form in forms"
                            :key="form.form.title"
                            :value="toName(form.form.title)"
                          >
                            {{ form.form.title }}
                          </option>
                        </select>
                      </div>
                      <div v-for="form in forms" :key="form">
                        <div
                          v-if="toName(form.form.title) == input.optionList[0].value"
                          class="form-group"
                        >
                          <select
                            v-model="input.optionList[1].value"
                            class="modern-select"
                          >
                            <option value="">Select Field</option>
                            <option
                              v-for="formInput in form.form.inputs"
                              :key="formInput.name"
                              :value="formInput.name"
                            >
                              {{ formInput.label }}
                            </option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Operation type fields -->
                  <div v-if="input.type === 'operation'" class="options-section">
                    <label class="form-label">Mathematical Operation</label>
                    <div class="field-row">
                      <div class="form-group">
                        <select
                          v-model="input.optionList[0].value"
                          class="modern-select"
                        >
                          <option value="">First Number</option>
                          <option
                            v-for="numInput in numberInputs"
                            :key="numInput.label"
                            :value="toName(numInput.label)"
                          >
                            {{ numInput.label }}
                          </option>
                        </select>
                      </div>
                      <div class="form-group">
                        <select
                          v-model="input.optionList[1].value"
                          class="modern-select"
                        >
                          <option value="">Operator</option>
                          <option>+</option>
                          <option>-</option>
                          <option>*</option>
                          <option>/</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <select
                          v-model="input.optionList[2].value"
                          class="modern-select"
                        >
                          <option value="">Second Number</option>
                          <option
                            v-for="numInput in filteredNumberInputs(input.optionList[0].value)"
                            :key="numInput.label"
                            :value="toName(numInput.label)"
                          >
                            {{ numInput.label }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="formInputs.length === 0" class="no-fields-state">
                <ion-icon name="document-outline" class="no-data-icon"></ion-icon>
                <h4>No Fields Yet</h4>
                <p>Add your first field to get started</p>
                <button class="action-btn primary" @click="addInput">
                  <ion-icon name="add-outline"></ion-icon>
                  Add First Field
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";
import SiteTitle from "@/components/SiteTitle.vue";

export default defineComponent({
  name: "EditTool",
  components: {
    SiteTitle,
  },
  data() {
    return {
      loading: true,
      saving: false,
      title: "",
      formInputs: [],
      forms: [],
      showAlert: false,
      showSuccessAlert: false,
      alertMessage: '',
      inputTypes: [
        { value: "text", label: "Text" },
        { value: "email", label: "E-Mail" },
        { value: "number", label: "Number" },
        { value: "textarea", label: "Textarea" },
        { value: "checkbox", label: "Checkbox" },
        { value: "color", label: "Color" },
        { value: "tel", label: "Tel" },
        { value: "date", label: "Date" },
        { value: "time", label: "Time" },
        { value: "select", label: "Select" },
        { value: "select2", label: "Select from Form (Form pipeline)" },
        { value: "operation", label: "Mathematic Operation" },
      ]
    };
  },
  computed: {
    numberInputs() {
      return this.formInputs.filter(input => input.type === 'number');
    }
  },
  async created() {
    this.loading = true;
    try {
      await Promise.all([
        this.loadExistingFormData(),
        this.loadForms()
      ]);
    } catch (error) {
      console.error('Error loading data:', error);
      this.alertMessage = 'Fehler beim Laden der Daten';
      this.showAlert = true;
    } finally {
      this.loading = false;
    }
  },
  methods: {
    async loadExistingFormData() {
      try {
        const response = await this.$axios.post(
          'form.php',
          this.$qs.stringify({
            get_form: 'get_form',
            form: this.$route.params.form,
            project: this.$route.params.project
          })
        );
        
        const formData = response.data.form;
        this.title = formData.title;
        
        // Convert existing inputs to the format expected by the form editor
        this.formInputs = formData.inputs.map(input => ({
          label: input.label,
          placeholder: input.placeholder || '',
          type: input.type,
          optionList: input.options ? input.options.map(opt => ({ value: opt.label || opt.value })) : [
            { value: "" },
            { value: "" },
            { value: "" }
          ]
        }));
      } catch (error) {
        console.error('Error loading form data:', error);
        throw error;
      }
    },
    
    async loadForms() {
      try {
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            get_forms: "get_forms",
            project: this.$route.params.project,
          })
        );
        this.forms = response.data;
      } catch (error) {
        console.error('Error loading forms:', error);
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
        optionList: [{ value: "" }, { value: "" }, { value: "" }],
      });
    },
    
    removeInput(index) {
      this.formInputs.splice(index, 1);
    },
    
    onTypeChange(index, newType) {
      const input = this.formInputs[index];
      input.type = newType;
      if (newType === "select" && !input.optionList) {
        input.optionList = [{ value: "" }];
      } else if (newType !== "select" && newType !== "select2" && newType !== "operation") {
        input.optionList = [{ value: "" }, { value: "" }, { value: "" }];
      }
    },
    
    addOption(input) {
      if (!input.optionList) {
        input.optionList = [];
      }
      input.optionList.push({ value: "" });
    },
    
    removeOption(input, optIndex) {
      input.optionList.splice(optIndex, 1);
    },
    
    async updateForm() {
      this.saving = true;
      
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

      try {
        const response = await this.$axios.post(
          "form.php",
          this.$qs.stringify({
            update_form_structure: "update_form_structure",
            form: JSON.stringify(formData),
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        );

        if (response.data.success) {
          this.showSuccessAlert = true;
        } else {
          this.alertMessage = response.data.error || 'Fehler beim Aktualisieren der Form';
          this.showAlert = true;
        }
      } catch (error) {
        console.error('Error updating form:', error);
        this.alertMessage = 'Netzwerkfehler beim Aktualisieren der Form';
        this.showAlert = true;
      } finally {
        this.saving = false;
      }
    },
    
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
    
    goBack() {
      this.$router.push(`/project/${this.$route.params.project}/${this.$route.params.form}`);
    },
    
    handleSuccess() {
      this.showSuccessAlert = false;
      this.emitter.emit("updateSidebar");
      this.goBack();
    }
  },
});
</script>

<style scoped>
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h1 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover:not(:disabled) {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-secondary);
}

.action-btn.sm {
  padding: 8px 12px;
  font-size: 13px;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn ion-icon {
  font-size: 16px;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 48px;
  color: var(--primary-color);
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

.form-editor-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-title-card,
.fields-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  padding: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 14px;
}

.modern-input,
.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.modern-input:focus,
.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-select {
  cursor: pointer;
}

.fields-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field-item {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  transition: all 0.2s ease;
}

.field-item:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.field-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.field-number {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
  font-weight: 600;
  font-size: 14px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.delete-btn {
  background: #fef2f2;
  color: var(--danger-color);
}

.delete-btn:hover {
  background: #fee2e2;
  transform: scale(1.05);
}

.field-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.options-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  margin-top: 8px;
}

.options-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.options-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.option-item {
  display: flex;
  gap: 8px;
  align-items: center;
}

.option-item .modern-input {
  flex: 1;
  margin: 0;
}

.option-item .icon-btn {
  flex-shrink: 0;
}

.no-fields-state {
  text-align: center;
  padding: 60px 20px;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-fields-state h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-fields-state p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}

@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: stretch;
  }

  .action-btn {
    flex: 1;
  }

  .field-row {
    grid-template-columns: 1fr;
  }
}
</style>
