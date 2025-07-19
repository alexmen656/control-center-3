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
    <FloatingTextarea
      v-model="inputValues[index]"
      :label="input.label"
      :placeholder="input.placeholder"
      v-if="input.type == 'textarea'"
      @change="checkOperation(input.label, inputValues[index])"
    />
    <FloatingInput
      v-if="input.type == 'operation'"
      v-model="inputValues[index]"
      :label="input.label"
      :placeholder="input.placeholder"
      disabled="true"
      type="number"
    />
    <FloatingInput
      v-if="
        input.type != 'select' &&
        input.type != 'select2' &&
        input.type != 'checkbox' &&
        input.type != 'textarea' &&
        input.type != 'operation'
      "
      v-model="inputValues[index]"
      :label="input.label"
      :placeholder="input.placeholder"
      :type="input.type"
      @change="checkOperation(input.label, inputValues[index])"
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
import FloatingTextarea from "@/components/FloatingTextarea.vue";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
    FloatingTextarea,
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
    this.$axios
      .post(
        "form.php",
        this.$qs.stringify({
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
            const inputInstance = { ...input }; // Create a copy of the input object
            await this.$axios
              .post(
                "form.php",
                this.$qs.stringify({
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
    checkOperation(label) {
      this.inputs.forEach((input, index) => {
        if (input.type == "operation") {
          if (input.options[0].value == label.toLowerCase()  || input.options[2].value == label.toLowerCase()) {
            let value1 = 0;
            let value2 = 0;

            this.inputs.forEach((input2, index) => {
              //console.log(input2.label, input.options[0].value)
              if (input2.label.toLowerCase() == input.options[0].value) {
                //console.log(index);
                //console.log("value1", this.inputValues[index])
                value1 = this.inputValues[index];
              }
            });

            this.inputs.forEach((input2, index) => {
              //console.log(input2.label, input.options[2].value)
              if (input2.label.toLowerCase() == input.options[2].value) {
                //console.log(index);
                //console.log("value2", this.inputValues[index])
                value2 = this.inputValues[index];
              }
            });

            if (input.options[1].value == "+") {
              this.inputValues[index] = value1 + value2;
            } else if (input.options[1].value == "-") {
              this.inputValues[index] = value1 - value2;
            } else if (input.options[1].value == "*") {
              this.inputValues[index] = value1 * value2;
            } else if (input.options[1].value == "/") {
              this.inputValues[index] = value1 / value2;
            }
            //console.log(this.inputValues[index] ?? "No value");
          }
        }
      });
    },
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
        const response = await this.$axios.post(
          "/control-center/form.php",
          this.$qs.stringify({
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
