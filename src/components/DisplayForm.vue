<template>
  <div v-for="(input, index) in inputs" :key="input.name">
    <FloatingSelect
      v-model="inputValues[index]"
      :select="input"
      v-if="input.type == 'select'"
    />
    <FloatingSelect
        v-model="inputValues[index]"
        :select="input"
        v-if="input.type == 'select2'"
      />
    <FloatingCheckbox
      v-model="inputValues[index]"
      :label="input.label"
      v-if="input.type == 'checkbox'"
    />
    <FloatingInput
      v-if="
        input.type != 'select' &&
        input.type != 'select2' &&
        input.type != 'checkbox'
      "
      v-model="inputValues[index]"
      :label="input.label"
      :placeholder="input.placeholder"
      :type="input.type"
    />
  </div>
  <form @submit.prevent="submit">
    <ion-button type="submit">Submit</ion-button>
  </form>
  <!--  <div v-for="iV in inputValues" :key="iV">
    {{ iV }}
  </div>-->
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import { IonButton } from "@ionic/vue";
import qs from "qs";
import axios from "axios";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
    IonButton,
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
    };
  },
  created() {
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
          //console.log(input);
          const  inputInstance = { ...input }; // Create a copy of the input object
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
              //console.log(res.data);
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
          //console.log(this.inputs);
        } else {
          this.inputs.push(input);
        }
        //console.log(this.inputs);
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
    /*async select2(form, field) {
      try {
        const response = await axios.post(
          "/control-center/form.php",
          qs.stringify({
            get_form_data: "get_form_data",
            project: this.$route.params.project,
            form: form,
          })
        )
       

        const array = [];
        response.data.forEach((input, index) => {
          array.push(input[field]);
        });
        return array;
    
      } catch (error) {
        console.error("Error fetching select2 data:", error);
        return [];
      }
    },*/
  },
};
</script>
