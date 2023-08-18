<template>
  <div>
    <DisplayForm @submit="handleSubmit" />
  </div>
</template>

<script>
import DisplayForm from "@/components/DisplayForm.vue";
import axios from "axios";
import qs from "qs";

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
      axios.post(
        "/control-center/form.php",
        qs.stringify({ submit_form: "submit_form", form: JSON.stringify(data), form_name: this.$route.params.form, project: this.$route.params.project })
      );
    },
  },
};
</script>
