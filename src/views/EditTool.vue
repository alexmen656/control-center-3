<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <ion-card v-if="loading">
              <ion-card-content>
                <div style="text-align: center;">
                  <ion-spinner></ion-spinner>
                  <p>Form wird geladen...</p>
                </div>
              </ion-card-content>
            </ion-card>

            <div v-else>
              <div class="form-header">
                <ion-input
                  v-model="title"
                  label="Form Title"
                  label-placement="floating"
                  fill="outline"
                />
                <ion-button 
                  fill="clear" 
                  @click="goBack"
                  color="medium"
                  class="back-btn"
                >
                  <ion-icon name="arrow-back" slot="start"></ion-icon>
                  Zurück
                </ion-button>
              </div>
              
              <form @submit.prevent="updateForm">
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
                    <ion-button @click="removeInput(index)" color="danger">Remove</ion-button>
                  </ion-col>
                  
                  <!-- Options for select fields -->
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
                            v-model="option.value"
                            label="Option"
                            label-placement="floating"
                            fill="outline"
                          />
                          <ion-button @click="removeOption(input, optIndex)" color="danger">
                            Remove Option
                          </ion-button>
                        </ion-col>
                      </ion-row>
                    </ion-col>
                    <ion-col size="12">
                      <ion-button @click="addOption(input)">Add Option</ion-button>
                    </ion-col>
                  </ion-row>
                  
                  <!-- Select2 (Form pipeline) options -->
                  <ion-row v-if="input.type === 'select2'">
                    <ion-col size="3">
                      <ion-label>Options</ion-label>
                    </ion-col>
                    <ion-col size="9">
                      <ion-row>
                        <ion-col size="12" style="display: flex">
                          <ion-select
                            aria-label="Form"
                            placeholder="Select Form"
                            interface="popover"
                            fill="outline"
                            v-model="input.optionList[0].value"
                          >
                            <ion-select-option
                              v-for="form in forms"
                              :key="form.form.title"
                              :value="toName(form.form.title)"
                              >{{ form.form.title }}</ion-select-option
                            >
                          </ion-select>

                          <div v-for="form in forms" :key="form">
                            <div
                              v-if="
                                toName(form.form.title) ==
                                input.optionList[0].value
                              "
                            >
                              <ion-select
                                aria-label="Form"
                                placeholder="Select Form"
                                interface="popover"
                                fill="outline"
                                v-model="input.optionList[1].value"
                              >
                                <ion-select-option
                                  v-for="input in form.form.inputs"
                                  :key="input.name"
                                  :value="input.name"
                                  >{{ input.label }}</ion-select-option
                                >
                              </ion-select>
                            </div>
                          </div>
                        </ion-col>
                      </ion-row>
                    </ion-col>
                  </ion-row>
                  
                  <!-- Operation type fields -->
                  <ion-row v-if="input.type === 'operation'">
                    <ion-col size="3">
                      <ion-label>Operation</ion-label>
                    </ion-col>
                    <ion-col size="9">
                      <ion-row>
                        <ion-col size="12" style="display: flex">
                          <ion-select
                            aria-label="Form"
                            placeholder="Select Form"
                            interface="popover"
                            fill="outline"
                            v-model="input.optionList[0].value"
                          >
                            <ion-select-option
                              v-for="input in numberInputs"
                              :key="input.label"
                              :value="toName(input.label)"
                              >{{ input.label }}</ion-select-option
                            >
                          </ion-select>

                          <ion-select
                            aria-label="Form"
                            placeholder="Select Form"
                            interface="popover"
                            fill="outline"
                            v-model="input.optionList[1].value"
                          >
                            <ion-select-option>+</ion-select-option>
                            <ion-select-option>-</ion-select-option>
                            <ion-select-option>*</ion-select-option>
                            <ion-select-option>/</ion-select-option>
                          </ion-select>

                          <ion-select
                            aria-label="Form"
                            placeholder="Select Form"
                            interface="popover"
                            fill="outline"
                            v-model="input.optionList[2].value"
                          >
                            <ion-select-option
                              v-for="input in filteredNumberInputs(input.optionList[0].value)"
                              :key="input.label"
                              :value="toName(input.label)"
                              >{{ input.label }}</ion-select-option
                            >
                          </ion-select>
                        </ion-col>
                      </ion-row>
                    </ion-col>
                  </ion-row>
                </ion-row>
                
                <ion-row>
                  <ion-col size="12">
                    <ion-button @click="addInput">Add Input</ion-button>
                    <ion-button @click="updateForm" :disabled="saving">
                      {{ saving ? 'Updating...' : 'Update Form' }}
                    </ion-button>
                  </ion-col>
                </ion-row>
              </form>
            </div>
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>
      
      <ion-alert
        :is-open="showAlert"
        header="Fehler"
        :message="alertMessage"
        :buttons="['OK']"
        @didDismiss="showAlert = false"
      ></ion-alert>
      
      <ion-alert
        :is-open="showSuccessAlert"
        header="Erfolg"
        message="Form wurde erfolgreich aktualisiert!"
        :buttons="['OK']"
        @didDismiss="handleSuccess"
      ></ion-alert>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "EditTool",
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
    await this.loadExistingFormData();
    await this.loadForms();
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
        
        this.loading = false;
      } catch (error) {
        console.error('Error loading form data:', error);
        this.alertMessage = 'Fehler beim Laden der Form-Daten';
        this.showAlert = true;
        this.loading = false;
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
.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.back-btn {
  margin-left: 16px;
}

ion-row {
  margin-bottom: 16px;
}

ion-button[disabled] {
  opacity: 0.6;
}
</style>
