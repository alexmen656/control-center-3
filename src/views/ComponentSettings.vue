<template>
  <ion-page>
    <ion-content>
      <FloatingInput
        v-model="style['nav1']"
        label="Nav1"
        placeholder="<nav>"
        type="text"
        defaultVal=""
      /><!--:defaultVal="defaults_values[input.name]"-->
      <FloatingInput
        v-model="style['nav2']"
        label="Nav2"
        placeholder="<nav>"
        type="text"
        defaultVal=""
      />
      <FloatingInput
        v-model="style['par1']"
        label="Par1"
        placeholder="<nav>"
        type="text"
        defaultVal=""
      />
      <FloatingInput
        v-model="style['par2']"
        label="Par2"
        placeholder="<nav>"
        type="text"
        defaultVal=""
      />
      <FloatingInput
        v-model="style['logo']"
        label="Logo"
        placeholder="<nav>"
        type="text"
        defaultVal=""
      />
      <ion-button @click="save">Save</ion-button>
      <!--<FloatingInput
        v-model="inputValues"
        label="Nav1"
        placeholder="<nav>"
        type="text"
        defaultVal=""

      />-->
    </ion-content>
  </ion-page>
</template>
<script>
import FloatingInput from "@/components/FloatingInput.vue";

export default {
  name: "ComponentSettings",
  components: {
    FloatingInput,
  },
  data() {
    return {
      inputValues: "",
      style: {},
    };
  },
  created() {
    try {
      $axios
        .post(
          "components.php",
          this.$qs.stringify({
            getCoponent: "getCoponent",
            project: this.$route.params.project,
            name: this.$route.params.component,
          })
        )
        .then((response) => {
          if (response.data.type === "menu") {
            this.items = response.data.content.content;
            this.style = response.data.content.style;
          }
        });
    } catch (error) {
      console.error("Error fetching component data:", error);
    }
  },
  methods: {
    save() {
      $axios
        .post(
          "components.php",
          this.$qs.stringify({
            updateHTML: "updateHTML",
            project: this.$route.params.project,
            name: this.$route.params.component,
            html: JSON.stringify({ content: this.items, style: this.style }),
          })
        )
        .then(() => {
          console.log("Updated successfully");
        })
        .catch((error) => {
          console.error("Error saving component HTML:", error);
        });
    },
  },
};
</script>
