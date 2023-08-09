<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <form @submit.prevent="submitForm">
              <ion-row v-for="(input, index) in formInputs" :key="index">
                <ion-col size="3">
                  <ion-input
                    v-model="input.label"
                    label="Label"
                    label-placement="floating"
                    fill="outline"
                  ></ion-input>
                </ion-col>
                <ion-col size="3">
                  <ion-input
                    v-model="input.placeholder"
                    label="Placeholder"
                    label-placement="floating"
                    fill="outline"
                  ></ion-input>
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
                    <!-- Available Input Types -->
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
                <!-- Options Row -->
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
                  <ion-button @click="addSelect">Add Select</ion-button>
                  <ion-button @click="submitForm">Create Form</ion-button>
                </ion-col>
              </ion-row>
            </form>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { ref } from "vue";
import {
  IonPage,
  IonContent,
  IonGrid,
  IonRow,
  IonCol,
  IonInput,
  IonSelect,
  IonSelectOption,
  IonButton,
  IonLabel,
} from "@ionic/vue";

export default {
  components: {
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonInput,
    IonSelect,
    IonSelectOption,
    IonButton,
    IonLabel,
  },
  data() {
    return {
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
    };
  },
  methods: {
    addInput() {
      this.formInputs.push({
        label: "",
        placeholder: "",
        type: "text",
        optionList: [],
      });
    },
    addSelect() {
      this.formInputs.push({
        label: "",
        placeholder: "",
        type: "select",
        optionList: [{ value: "" }],
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
        title: "Sign Up",
        inputs: this.formInputs.map((input) => ({
          type: input.type,
          name: input.label.toLowerCase().replace(/\s+/g, "_"),
          label: input.label,
          placeholder: input.placeholder,
          options:
            input.type === "select"
              ? input.optionList.map((option) => option.value)
              : [],
        })),
      };

      const jsonData = JSON.stringify(formData, null, 2);
      console.log("Created JSON Data:", jsonData);
    },
  },
};
</script>
