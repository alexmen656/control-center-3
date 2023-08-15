<template>
  <div>
    <DisplayForm :form="form" @submit="handleSubmit" />
  </div>
</template>

<script>
import DisplayForm from "@/components/DisplayForm.vue";
//import axios from "axios";
//import qs from "qs";
//import { getForm } from "@/form";

export default {
  name: "FormDisplay",
  components: {
    DisplayForm,
  },
  data() {
    return {
      form: {},
    };
  },
  methods: {
    handleSubmit(data) {
      console.log(data);
    },
  },
};
</script>
