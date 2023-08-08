<template>
  <FloatingInput
    v-for="(input, index) in inputs"
    :key="input.name"
    v-model="inputValues[index]"
    :label="input.name"
    :placeholder="input.placeholder"
    :type="input.type"
  />
  <!-- <FloatingInput
          v-model="password"
          label="Password"
          placeholder="*****"
          type="password"
        />-->
  <div v-for="iV in inputValues" :key="iV">
    {{ iV }}
  </div>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";
import form from "@/forms/inputs.json";

export default {
  name: "DisplayForm",
  components: {
    FloatingInput,
  },
  data() {
    return {
      inputValues: [],
    };
  },
  created() {
    this.inputs = form.inputs;
  },
};
</script>
