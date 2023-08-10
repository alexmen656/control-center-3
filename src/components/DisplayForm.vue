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
      :label="input.label"
      :placeholder="input.placeholder"
      :type="input.type"
    />
  </div>
  <form @submit.prevent="submit">
    <ion-button type="submit">Submit</ion-button>
  </form>
  <div v-for="iV in inputValues" :key="iV">
    {{ iV }}
  </div>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
    FloatingSelect,
    FloatingCheckbox,
  },
  props: {
    form: {
      type: Object,
      required: true,
    },
  },
  created() {
    this.inputs = this.form.inputs;
  },
  data() {
    return {
      inputValues: [],
    };
  },
  methods: {
    submit() {
      const formData = {};
      this.inputs.forEach((input, index) => {
        formData[input.name] = this.inputValues[index];
      });

      this.$emit("submit", formData);
    },
  },
};
</script>
