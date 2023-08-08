<template>
  <div v-for="(input, index) in inputs" :key="input.name">
    <FloatingSelect
      v-model="inputValues[index]"
      :select="input"
      v-if="input.type == 'select'"
    ></FloatingSelect>
    <FloatingCheckbox
      v-model="inputValues[index]"
      :label="input.label"
      v-if="input.type == 'checkbox'"
    ></FloatingCheckbox>
    <FloatingInput
      v-if="input.type != 'select' && input.type != 'checkbox'"
      v-model="inputValues[index]"
      :label="input.name"
      :placeholder="input.placeholder"
      :type="input.type"
    />
  </div>
  <ion-button @click="submit()">Submit</ion-button>
  <div v-for="iV in inputValues" :key="iV">
    {{ iV }}
  </div>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import form from "@/forms/inputs.json";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
  },
  data() {
    return {
      inputValues: [],
    };
  },
  created() {
    this.inputs = form.inputs;
  },
  methods: {
    submit() {
      const formData = {};
      this.inputs.forEach((input, index) => {
        formData[input.name] = this.inputValues[index];
      });

      console.log(formData);
    },
  },
};
</script>
