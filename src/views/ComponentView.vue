<template>
  <ion-grid>
    <ion-row>
      <ion-col size="1" />
      <ion-col
        size="10"
        style="display: flex; content-align: center; flex-direction: column"
      >
        <code-editor
          v-if="type == 'script'"
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
        <ion-button v-if="type == 'script'" @click="saveHTML()" color="danger"
          >Save Component</ion-button
        >
        <img
          v-if="type == 'image'"
          :src="'https://alex.polan.sk/' + newHTML"
          alt=""
        />
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
    };
  },
  async mounted() {
    const route = useRoute();
    const component = {};

    await axios
      .post(
        "https://alex.polan.sk/control-center/components.php",
        qs.stringify({
          getCoponent: "getCoponent",
          project: route.params.project,
          name: route.params.component,
        })
      )
      .then((res) => {
        component.content = res.data.content;
        component.type = res.data.type;
        //console.log(component.content);
      });

    this.component = component;
    this.newHTML = component.content.replaceAll("\n", "\n");
    this.type = component.type;
    //this.newHTML = "hello\n\nhello\n\n"
    //console.log(this.type);
  },
  methods: {
    saveHTML() {
      const route = useRoute();

      console.log(this.newHTML);
      axios
        .post(
          "https://alex.polan.sk/control-center/components.php",
          qs.stringify({
            updateHTML: "updateHTML",
            project: this.$route.params.project,
            name: this.$route.params.component,
            html: this.newHTML.replaceAll("\n", "\n").trim(),
          }),
          {
            headers: {
              "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            },
          }
        )
        .then((res) => {
          //replaceAll("\n", "\r\n")
          //component.content = res.data.content;
          //console.log(component.content);
        });
    },
  },
};
</script>
<style>
textarea {
  overflow-y: scroll !important;
}
</style>
