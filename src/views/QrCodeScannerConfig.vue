<template>
  <ion-page class="ion-padding">
    <ion-content>
      <FloatingSelect v-model="form" :select="select" />
  <!--    <FloatingSelect v-if="form" v-model="form_data" :select="data_select" />-->
      <FloatingSelect
        v-if="form"
        v-model="form_label"
        :select="label_select"
      />
      <ion-button v-if="form_label" @click="save()">Save Config</ion-button>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import qs from "qs";
import { IonPage, IonButton } from "@ionic/vue";
import FloatingSelect from "@/components/FloatingSelect.vue";

export default {
  name: "BarcodeScanner",
  components: {
    IonPage,
    FloatingSelect,
  },
  data() {
    return {
      forms: [],
      form: "",
      form_label: "",
      select: {
        type: "select",
        name: "form",
        label: "Form",
        placeholder: "Form",
        options: [],
      },

      data_select: {
        type: "select",
        name: "data",
        label: "Data",
        placeholder: "Data",
        options: [],
      },
      label_select: {
        type: "select",
        name: "label",
        label: "Label",
        placeholder: "Label",
        options: [],
      },
    };
  },
  mounted() {
    this.$watch(
      () => this.form,
      () => {
        const options = [];
        this.forms.forEach((form) => {
          if (this.toName(form.form.title) == this.form) {
            form.form.inputs.forEach((element) => {
              options.push({ value: element.name, label: element.label });
            });
          }
        });
        this.data_select.options = options;
        this.label_select.options = options;
      }
    );
  },
  created() {
    axios
      .post(
        "/control-center/form.php",
        qs.stringify({
          get_forms: "get_forms",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.forms = res.data;
        res.data.forEach((form) => {
          this.select.options.push({
            value: this.toName(form.form.title),
            label: form.form.title,
          });
          console.log(this.select);
        });
      });
  },
  methods: {
    save(){
        axios.post("/control-center/tools.php", qs.stringify({newToolConfig: "newToolConfig", config: JSON.stringify({form: this.form, form_label: this.form_label}), project: this.$route.params.project, tool: "qr-code-scanner"}));
    },
    onDecode(text) {
      const audio = new Audio("/control-center/scanner.mp3");

      console.log(`Decode text from QR code is ${text}`);
      //alert(text);
      axios
        .post(
          "/control-center/products.php",
          qs.stringify({ getProductByCode: "getProductByCode", code: text })
        )
        .then((res) => {
          audio.play().then(() => {
            alert(res.data.title + "\n" + res.data.price + "€");
          });

          // audio.play();
        });
    },
    onLoaded() {
      console.log(`Ready to start scanning barcodes`);
    },
    print() {
      window.print();
    },
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },

    subscribe() {
      // Überprüfen, ob der Browser Push-Benachrichtigungen unterstützt
      if ("serviceWorker" in navigator && "PushManager" in window) {
        // Service Worker registrieren
        navigator.serviceWorker
          .register("/service-worker.js")
          .then((registration) => {
            // Benutzer um Erlaubnis zur Anzeige von Push-Benachrichtigungen bitten
            return registration.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: "AIzaSyAF53AYFblvyoeCHvXqUT--C5lnYf095VY",
            });
          })
          .then((subscription) => {
            // Senden Sie das Push-Abonnementobjekt an Ihren Server
            axios
              .post("/api/subscribe", subscription)
              .then(() => {
                //response
                alert("Push-Abonnement erfolgreich gespeichert");
              })
              .catch((error) => {
                console.error(
                  "Fehler beim Speichern des Push-Abonnements:",
                  error
                );
              });
          })
          .catch((error) => {
            console.error(
              "Fehler bei der Registrierung des Service Workers:",
              error
            );
          });
      } else {
        alert(
          "Push-Benachrichtigungen werden von diesem Browser nicht unterstützt."
        );
      }
    },
  },
};
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
