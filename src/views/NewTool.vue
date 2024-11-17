<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10" v-if="formView">
            <ion-input
              v-model="title"
              @input="logTitle"
              label="Form Title"
              label-placement="floating"
              fill="outline"
            />
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
                          v-model="option.value"
                          label="Option"
                          label-placement="floating"
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
                  <ion-button @click="submitForm">Create Form</ion-button>
                </ion-col>
              </ion-row>
            </form>
            <div v-html="jsonData"></div>
          </ion-col>
          <ion-col
            style="display: flex; flex-direction: column; align-items: center"
            size="10"
            v-else
          >
            <ion-list class="w100">
              <ion-item class="w100">
                <ion-select
                  aria-label="Tool"
                  interface="action-sheet"
                  placeholder="Select Tool"
                  v-model="selectedTool"
                  :value="selectedTool"
                  @ionInput="selectedTool = $event.target.value"
                >
                  <ion-select-option
                    v-for="tool in tools"
                    :key="tool.id"
                    :value="tool.name"
                  >
                    {{ tool.name }}</ion-select-option
                  >
                  <ion-select-option value="form"> Form</ion-select-option>
                  <ion-select-option value="dashboard">
                    Dashboard (New)</ion-select-option
                  >
                  <!--<ion-select-option value="qr_code_scanner"> QR Code Scanner</ion-select-option>-->
                </ion-select>
              </ion-item>
            </ion-list>
            <ion-button
              style="width: 40%; margin-top: 1rem"
              @click="handleSubmit"
              >Submit</ion-button
            >
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "ToolSelection",
  data() {
    return {
      tools: [],
      selectedTool: "",
      name: "",
      formView: false,
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
        { value: "number", label: "Number" },
        { value: "email", label: "Email" },
        { value: "password", label: "Password" },
        { value: "color", label: "Color" },
        { value: "tel", label: "Tel" },
        { value: "date", label: "Date" },
        { value: "select", label: "Select" },
        { value: "select2", label: "Select from Form (Form pipeline)" },
        { value: "operation", label: "Mathematic Operation" },
      ],
      jsonData: {},
      title: "",
      test: "",
      forms: [],
      selectedForm: "",
    };
  },
  computed: {
    numberInputs() {
      return this.formInputs.filter(input => input.type === 'number');
    }
  },
  async created() {
    this.$axios
      .post(
        "modules.php",
        this.$qs.stringify({ project: this.$route.params.project })
      )
      .then((res) => {
        if (res.data && res.data.length > 0) {
          this.tools = res.data.map((tool, index) => ({
            id: index + 1,
            icon: tool.icon,
            name: tool.name,
          }));
        }
      });
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
    filteredNumberInputs(selectedValue) {
      return this.numberInputs.filter(input => this.toName(input.label) !== selectedValue);
    },
    handleSubmit() {
      if (this.selectedTool == "form") {
        this.formView = true;
      } else if (this.selectedTool == "dashboard") {
        this.$axios
          .post(
            "dashboard.php",
            this.$qs.stringify({
              new_dashboard: "new_dashboard",
              project: this.$route.params.project,
            })
          )
          .then(() => {
            this.emitter.emit("updateSidebar");
          });
      } else {
        const selectedTool = this.tools.find(
          (tool) => tool.name === this.selectedTool
        );
        this.$axios
          .post(
            "tools.php",
            this.$qs.stringify({
              newTool: "newTool",
              toolIcon: selectedTool.icon,
              projectName: this.$route.params.project,
              toolName: selectedTool.name,
            })
          )
          .then(() => {
            this.emitter.emit("updateSidebar");
          });
      }
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
  },
});
</script>
<style scoped>
.w100 {
  width: 100%;
}
</style>
