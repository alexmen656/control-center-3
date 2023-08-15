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
                    @ionChange="onTypeChange(index)"
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
import {
  IonButton,
  IonCol,
  IonContent,
  IonGrid,
  IonItem,
  IonList,
  IonPage,
  IonSelect,
  IonSelectOption,
  IonRow,
  IonInput,
  IonLabel,
} from "@ionic/vue";
import { defineComponent } from "vue";
import axios from "axios";
import qs from "qs";

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
      ],
      jsonData: {},
      title: "",
    };
  },
  async created() {
    axios.post("/control-center/modules.php").then((res) => {
      this.tools = res.data.map((tool, index) => ({
        id: index + 1,
        icon: tool.icon,
        name: tool.name,
      }));
    });
  },
  methods: {
    handleSubmit() {
      if (this.selectedTool == "form") {
        this.formView = true;
      } else {
        const selectedTool = this.tools.find(
          (tool) => tool.name === this.selectedTool
        );
        axios.post(
          "/control-center/tools.php",
          qs.stringify({
            newTool: "newTool",
            toolIcon: selectedTool.icon,
            projectName: this.$route.params.project,
            toolName: selectedTool.name,
          })
        );
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
    onTypeChange(index) {
      this.formInputs[index].optionList = [{ value: "" }];
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
            input.type === "select"
              ? input.optionList.map((option) => ({
                  value: option.value.toLowerCase().replace(/\s+/g, "_"),
                  label: option.value,
                }))
              : [],
        })),
      };

      this.jsonData = JSON.stringify(formData, null, 2);
      axios
        .post(
          "/control-center/form.php",
          qs.stringify({
            create_form: "create_form",
            form: this.jsonData,
            name: this.title.toLowerCase().replace(/\s+/g, "-"),
            project: this.$route.params.project,
          })
        )
        .then(() => {//res
          axios.post(
            "/control-center/tools.php",
            qs.stringify({
              newTool: "newTool",
              toolIcon: "document-text-outline",
              projectName: this.$route.params.project,
              toolName: this.title,
            })
          );
        });
    },
  },
  components: {
    IonButton,
    IonCol,
    IonContent,
    IonGrid,
    IonItem,
    IonList,
    IonPage,
    IonSelect,
    IonSelectOption,
    IonRow,
    IonInput,
    IonLabel,
  },
});
</script>
<style scoped>
.w100 {
  width: 100%;
}
</style>
