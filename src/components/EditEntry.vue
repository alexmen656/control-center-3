<template>
  <ion-header>
    <ion-toolbar>
      <ion-buttons slot="start">
        <ion-button @click="cancel()">Cancel</ion-button>
      </ion-buttons>
      <ion-title style="text-align: center">Edit Entry</ion-title
      ><!--{{ data.id }} {{ data.project }} {{ data.form }}-->
      <ion-buttons slot="end">
        <ion-button :strong="true" @click="confirm()">Confirm</ion-button>
      </ion-buttons>
    </ion-toolbar>
  </ion-header>
  <ion-content class="ion-padding">
    <div v-for="(input, index) in inputs" :key="input.name">
      <FloatingSelect
        v-model="inputValues[index]"
        :select="input"
        :defaultVal="defaults_values[input.name]"
        v-if="input.type == 'select'"
      />
      <FloatingSelect
        v-model="inputValues[index]"
        :select="input"
        :defaultVal="defaults_values[input.name]"
        v-if="input.type == 'select2'"
      />
      <FloatingCheckbox
        v-model="inputValues[index]"
        :label="input.label"
        :defaultVal="defaults_values[input.name]"
        v-if="input.type == 'checkbox'"
      />
      <FloatingInput
        v-if="
          input.type != 'select' &&
          input.type != 'select2' &&
          input.type != 'checkbox'
        "
        v-model="inputValues[index]"
        :defaultVal="defaults_values[input.name]"
        :label="input.label"
        :placeholder="input.placeholder"
        :type="input.type"
      />
    </div>
    <form @submit.prevent="submit">
      <ion-button type="submit">Submit</ion-button>
    </form>
  </ion-content>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import { defineComponent } from "vue";
import {
  IonButton,
  ionContent,
  IonButtons,
  IonToolbar,
  IonHeader,
  IonTitle,
} from "@ionic/vue";
import qs from "qs";
import axios from "axios";

export default defineComponent({
  name: "EditEntry",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
    IonButton,
    ionContent,
    IonButtons,
    IonToolbar,
    IonHeader,
    IonTitle,
  },
  props: {
    data: String,
  },
  data() {
    return {
      inputValues: [],
      inputs: [],
      inputss: [],
      input: {
        label: "",
        name: "",
        options: [],
      },
      defaults_values: {},
    };
  },
  created() {
    axios
      .post(
        "/control-center/mysql.php",
        qs.stringify({
          getDataById: "getDataById",
          id: this.data.id,
          project: this.data.project,
          form: this.data.form,
        })
      )
      .then((res) => {
        this.defaults_values = res.data;
      });

    axios
      .post(
        "/control-center/form.php",
        qs.stringify({
          get_form: "get_form",
          project: this.$route.params.project,
          form: this.$route.params.form,
        })
      )
      .then((res) => {
        this.form = res.data.form;
        this.inputss = this.form.inputs;
        this.inputss.forEach(async (input) => {
          if (input.type == "select2") {
            console.log(input);
            const inputInstance = { ...input }; // Create a copy of the input object
            await axios
              .post(
                "/control-center/form.php",
                qs.stringify({
                  get_form_data: "get_form_data",
                  project: this.$route.params.project,
                  form: input.options[0].value,
                })
              )
              .then((res) => {
                console.log(res.data);
                inputInstance.options = [];
                inputInstance.label = input.label;
                inputInstance.name = input.name;
                inputInstance.type = input.type;

                res.data.forEach((inputt) => {
                  inputInstance.options.push({
                    value: inputt[input.options[1].value],
                    label: inputt[input.options[1].value],
                  });
                });
              });
            this.inputs.push(inputInstance);
            console.log(this.inputs);
          } else {
            this.inputs.push(input);
          }
          console.log(this.inputs);
        });
      });
  },
  emits: ["submit"],
  methods: {
    submit() {
      const formData = {};
      this.inputs.forEach((input, index) => {
        formData[input.name] = this.inputValues[index];
        this.inputValues[index] = "";
      });

      this.$emit("submit", formData);
    },
    cancel() {
      this.$refs.modal.$el.dismiss(null, "cancel");
    },
    confirm() {
      this.$refs.modal.$el.dismiss(null, "confirm");
    },
  },
});
</script>
