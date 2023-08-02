<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1" />
      <ion-col
        size="10"
        style="display: flex; align-items: center; flex-direction: column"
      >
        <code-editor
          v-if="type === 'script'"
          :wrap_code="false"
          style="overflow-y: scroll !important"
          v-model="newHTML"
          width="100%"
          max_height="600px"
          :language_selector="true"
          :languages="[
            ['javascript', 'JS'],
            ['python', 'Python'],
            ['html', 'HTML'],
          ]"
        />
        <ion-button v-if="type === 'script'" @click="saveHTML()" color="primary"
          >Save Component</ion-button
        >
        <img
          v-if="type === 'image'"
          :src="'https://alex.polan.sk/' + newHTML"
          alt="Image"
        />
        <span v-if="dateTimeString && component.last_change_by"
          >Last Change: {{ dateTimeString }} by
          {{ component.last_change_by }}</span
        ><span v-if="createdOn">Created: {{ createdOn }}</span>
      </ion-col>
      <ion-col size="1" />
    </ion-row>
  </ion-grid>
</template>

<script>
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import CodeEditor from "simple-code-editor";
import { IonButton, IonGrid, IonRow, IonCol } from "@ionic/vue";

export default {
  name: "ComponentsView",
  components: {
    CodeEditor,
    IonButton,
    IonGrid,
    IonRow,
    IonCol,
  },
  data() {
    return {
      component: {},
      newHTML: "",
      type: "",
      dateTimeString: "",
      createdOn: "",
    };
  },
  async mounted() {
    await this.getComponentData();
  },
  methods: {
    async getComponentData() {
      try {
        const response = await axios.post(
          "/control-center/components.php",
          qs.stringify({
            getCoponent: "getCoponent",
            project: this.$route.params.project,
            name: this.$route.params.component,
          })
        );

        this.component = response.data;
        this.newHTML = this.component.content.trim();
        this.type = this.component.type;

        this.updateDateTimeString();
      } catch (error) {
        console.error("Error fetching component data:", error);
      }
    },
    saveHTML() {
      const html = this.newHTML.trim();
      axios
        .post(
          "/control-center/components.php",
          qs.stringify({
            updateHTML: "updateHTML",
            project: this.$route.params.project,
            name: this.$route.params.component,
            html: html,
          }),
          {
            headers: {
              "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            },
          }
        )
        .then(() => {
          this.getComponentData();
        })
        .catch((error) => {
          console.error("Error saving component HTML:", error);
        });
    },
    updateDateTimeString() {
      if (new Date(this.component.last_change) != "Invalid Date") {
        const lastChangeDate = new Date(this.component.last_change);
        const formattedDate = lastChangeDate
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedTime = lastChangeDate.toLocaleTimeString("en-GB");
        this.dateTimeString = formattedDate + " at " + formattedTime;
      }
      if (new Date(this.component.createdOn) != "Invalid Date") {
        const createdOn = new Date(this.component.createdOn);
        const formattedCreatedOnDate = createdOn
          .toLocaleDateString("en-GB")
          .split("/")
          .join(".");
        const formattedCreatedOnTime = createdOn.toLocaleTimeString("en-GB");
        this.createdOn =
          formattedCreatedOnDate + " at " + formattedCreatedOnTime;
      } else {
        this.createdOn = "Not Avaible";
      }
    },
  },
};
</script>

<style>
textarea {
  overflow-y: scroll !important;
}

img {
  height: 100%;
}
</style>
