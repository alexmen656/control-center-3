<template>
  <ion-page>
    <StreamBarcodeReader
      class="reader"
      @decode="onDecode"
    /><!--      @loaded="onLoaded"-->
    <ion-modal :is-open="isOpen" ref="modal">
      <ion-header>
        <ion-toolbar>
          <ion-buttons slot="start">
            <ion-button color="primary" @click="setOpen(false)"
              >Cancel</ion-button
            >
          </ion-buttons>
          <ion-title style="text-align: center">{{ display }}</ion-title
          ><!-- Found Entry -->
          <ion-buttons slot="end">
            <ion-button color="primary" :strong="true" @click="setOpen(false)"
              >Confirm</ion-button
            >
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        Details:<br />
        <!--  {{ display }}
     {{ found[1] }}-->
        <span v-for="(value, key) in found" :key="value">
          {{ key }}: {{ value }}<br />
        </span>
      </ion-content>
    </ion-modal>
  </ion-page>
</template>

<script>
import { StreamBarcodeReader } from "vue-barcode-reader";
import axios from "axios";
import qs from "qs";
import { getConfig } from "@/getToolConfig";
import {
  IonPage,
  IonButton,
  IonModal,
  IonHeader,
  IonContent,
  IonToolbar,
  IonTitle,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  name: "BarcodeScanner",
  components: {
    StreamBarcodeReader,
    IonPage,
    IonButton,
    IonModal,
    IonHeader,
    IonContent,
    IonToolbar,
    IonTitle,
  },
  setup() {
    const isOpen = ref(false);
    const setOpen = (open) => {
      isOpen.value = open;
    };

    return {
      isOpen,
      setOpen,
    };
  },
  data() {
    return {
      data: [],
      config: {},
      found: {},
      display: "",
    };
  },
  async created() {
    const config = await getConfig(
      "qr-code-scanner",
      this.$route.params.project
    );
    this.config = config;
    await axios
      .post(
        "/control-center/form.php",
        qs.stringify({
          get_form_data: "get_form_data",
          form: config.data.form,
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.data = res.data;
      });
  },
  methods: {
    async onDecode(text) {
      const audio = new Audio(
        "https://alex.polan.sk/control-center/scanner.mp3"
      );
      const found = await this.data.find(
        ({ id }) => Number(id) == Number(text)
      );
      console.log(found);
      await audio.play().then(() => {
        this.setOpen(true);
        this.display = found[this.config.data.form_label];
        this.found = found;
      });
    },
    /*onLoaded() {
      console.log(`Ready to start scanning barcodes`);
    },*/
  },
});
</script>
<style>
@media print {
  .img-box {
    width: 210mm !important;
    height: 297mm !important;
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }

  img {
    display: block !important;
    width: 11cm;
    /*  left: 5cm !important;*/
    top: 40% !important;
  }

  ion-button,
  .reader,
  ion-header,
  ion-menu,
  ion-title {
    display: none !important;
    width: 0;
    height: 0;
  }

  @page {
    size: auto; /* auto is the initial value */
    margin: 0; /* this affects the margin in the printer settings */
  }
}
</style>
