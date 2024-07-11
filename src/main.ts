import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import "./axios";

import { IonicVue } from "@ionic/vue";

/* Core CSS required for Ionic components to work properly */
import "@ionic/vue/css/core.css";

/* Basic CSS for apps built with Ionic */
import "@ionic/vue/css/normalize.css";
import "@ionic/vue/css/structure.css";
import "@ionic/vue/css/typography.css";

/* Optional CSS utils that can be commented out */
import "@ionic/vue/css/padding.css";
import "@ionic/vue/css/float-elements.css";
import "@ionic/vue/css/text-alignment.css";
import "@ionic/vue/css/text-transformation.css";
import "@ionic/vue/css/flex-utils.css";
import "@ionic/vue/css/display.css";

/* QuillEditor */
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

/* Theme variables */
//import "./theme/variables.css";
import "./theme/variables.scss";

import axios from "axios";
import qs from "qs";

import mitt from "mitt";


import * as IonComponents from '@ionic/vue';



const emitter = mitt();
const app = createApp(App).use(IonicVue).use(router);


app.component("QuillEditor", QuillEditor);

Object.keys(IonComponents).forEach(key => {
  if (/^Ion[A-Z]\w+$/.test(key)) {
      app.component(key, IonComponents[key]);
  }
});

app.config.globalProperties.emitter = emitter;
app.config.globalProperties.$axios = axios;
app.config.globalProperties.$qs = qs;


router.isReady().then(() => {
  app.mount("#app");
});
