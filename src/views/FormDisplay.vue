<template>
  <DisplayForm :form="form" @submit="handleSubmit"></DisplayForm>
</template>
<script>
import DisplayForm from "@/components/DisplayForm.vue";
import form from "@/forms/inputs.json";

export default {
  name: "FormDisplay",
  components: {
    DisplayForm,
  },
  created() {
    this.form = form;
  },
  methods: {
    handleSubmit(data) {
      console.log(data);
    },
  },
};
</script>
