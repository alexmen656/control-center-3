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
//import { QuillEditor } from "@vueup/vue-quill";
//import "@vueup/vue-quill/dist/vue-quill.snow.css";

/* Theme variables */
//import "./theme/variables.css";
import "./theme/variables.scss";
import "./theme/app.css";

// Fix for highlight.js import issue with simple-code-editor
import * as hljs from 'highlight.js';
window.hljs = hljs;

import axios from "axios";
import qs from "qs";

import mitt from "mitt";


import * as IonComponents from '@ionic/vue';

import { ToastService } from "./services/ToastService";


const debug = false;

const emitter = mitt();
const app = createApp(App).use(IonicVue).use(router);

// Load all modules with dashboard providers
const modules = import.meta.glob('./modules/*/index.ts', { eager: true });
console.log('📦 Loading modules with dashboard providers...');
Object.keys(modules).forEach(path => {
  console.log(`✓ Loaded module: ${path}`);
});

//app.component("QuillEditor", QuillEditor);

Object.keys(IonComponents).forEach(key => {
  if (/^Ion[A-Z]\w+$/.test(key)) {
      app.component(key, IonComponents[key]);
  }
});

app.config.globalProperties.emitter = emitter;
app.config.globalProperties.$axios = axios;
app.config.globalProperties.$qs = qs;
app.config.globalProperties.$toast = ToastService;

if(debug){
app.config.errorHandler = (err, vm, info) => {
  console.error("Error:", err);
  console.error("Vue component:", vm);
  console.error("Additional info:", info);
};
}

router.isReady().then(() => {
  app.mount("#app");
});
