<template>
  <div v-for="(input, index) in inputs" :key="input.name">
    <FloatingSelect v-model="inputValues[index]" :select="input" v-if="input.type == 'select'" />
    <FloatingSelect v-model="inputValues[index]" :select="input" v-if="input.type == 'select2'" />
    <FloatingCheckbox v-model="inputValues[index]" :label="input.label" v-if="input.type == 'checkbox'" />
    <FloatingTextarea v-model="inputValues[index]" :label="input.label" :placeholder="input.placeholder"
      v-if="input.type == 'textarea'" @change="checkOperation(input.label, inputValues[index])" />
    <FloatingInput v-if="input.type == 'operation'" v-model="inputValues[index]" :label="input.label"
      :placeholder="input.placeholder" disabled="true" type="number" />
    <FloatingFileUpload v-if="input.type == 'image'" v-model="inputValues[index]" :label="input.label"
      :project="$route.params.project" />
    <FloatingInput v-if="
      input.type != 'select' &&
      input.type != 'select2' &&
      input.type != 'checkbox' &&
      input.type != 'textarea' &&
      input.type != 'operation' &&
      input.type != 'image'
    " v-model="inputValues[index]" :label="input.label" :placeholder="input.placeholder" :type="input.type"
      @change="checkOperation(input.label, inputValues[index])" />
  </div>
  <form @submit.prevent="submit">
    <ion-button type="submit">Submit</ion-button>
  </form>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import FloatingTextarea from "@/components/FloatingTextarea.vue";
import FloatingFileUpload from "@/components/FloatingFileUpload.vue";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
    FloatingTextarea,
    FloatingFileUpload,
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
      .get(
        "v2/tables/single",
        {
          params: {
            project: this.$route.params.project,
            table: this.$route.params.table,
          }
        }
      )
      .then((res) => {
        this.form = res.data.table;
        if (!this.form || !Array.isArray(this.form.inputs)) return;
        this.inputss = this.form.inputs;
        this.inputss.forEach(async (input) => {
          if (input.type == "select2") {
            const inputInstance = { ...input };
            await this.$axios
              .get(
                "v2/tables/data",
                {
                  params: {
                    project: this.$route.params.project,
                    table: input.options[0].value,
                  }
                }
              )
              .then((res) => {
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
          } else {
            this.inputs.push(input);
          }
        });
      });
  },

  emits: ["submit"],
  methods: {
    checkOperation(label) {
      this.inputs.forEach((input, index) => {
        if (input.type == "operation") {
          if (input.options[0].value == label.toLowerCase() || input.options[2].value == label.toLowerCase()) {
            let value1 = 0;
            let value2 = 0;

            this.inputs.forEach((input2, index) => {
              if (input2.label.toLowerCase() == input.options[0].value) {
                value1 = this.inputValues[index];
              }
            });

            this.inputs.forEach((input2, index) => {
              if (input2.label.toLowerCase() == input.options[2].value) {
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
  },
};
</script>
