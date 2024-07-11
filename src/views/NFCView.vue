<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col size="10">
            <ion-button expand="block" @click="read()">Read</ion-button>
            <h4>---Or---</h4>
            <FloatingSelect v-model="entry" :select="select" />
            <ion-button expand="block" @click="write()">Write</ion-button>

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
                    <ion-button
                      color="primary"
                      :strong="true"
                      @click="setOpen(false)"
                      >Confirm</ion-button
                    >
                  </ion-buttons>
                </ion-toolbar>
              </ion-header>
              <ion-content class="ion-padding">
                Details:<br />
                <!--  {{ display }}
     {{ found[1] }}-->
                <span v-for="(value, key) in entry" :key="value">
                  {{ key }}: {{ value }}<br />
                </span>
              </ion-content>
            </ion-modal>
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
/*import {
  //Nfc,
  //NfcUtils,
  //NfcTagTechType
} from "@capawesome-team/capacitor-nfc";*/
import { defineComponent, ref } from "vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
//import { getConfig } from "@/getToolConfig";
//import $axios from "$axios";
import {
  IonPage,
  IonButton,
  IonModal,
  IonHeader,
  IonContent,
  IonToolbar,
  IonTitle,
} from "@ionic/vue";

export default defineComponent({
  components: {
    FloatingSelect,
    IonPage,
    IonButton,
    IonModal,
    IonHeader,
    IonContent,
    IonToolbar,
    IonTitle,
  },
  data() {
    return {
      found: {},
      entry: "",
      select: {
        type: "select",
        name: "entry",
        label: "Entry",
        placeholder: "Entry",
        options: [],
      },
    };
  },
 /* async created() {

    const config = await getConfig("nfc", this.$route.params.project);
    this.config = config;
    await $axios
      .post(
        "form.php",
        this.$qs.stringify({
          get_form_data: "get_form_data",
          form: config.data.form,
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.data = res.data;
        this.data.forEach((element, index) => {
          this.select.options[index] = {
            value: element.id, //this.toName(element.id)
            label: element[config.data.form_label],
          };
        });
      });
  },*/
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
  /*methods: {
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
    bytesToHex(bytes) {
      return Array.from(bytes, (byte) => {
        return ("0" + (byte & 0xff).toString(16)).slice(-2);
      }).join(":");
    },
    async isSupported() {
      const { isSupported } = await Nfc.isSupported();
      console.log(isSupported);
      return isSupported;
    },

    async requestPermissions() {
      const { nfc } = await Nfc.requestPermissions();
      return nfc;
    },

    async checkPermissions() {
      const { nfc } = await Nfc.checkPermissions();
      return nfc;
    },
    async read() {
      const removeAllListeners = async () => {
        await Nfc.removeAllListeners();
      };

      const isNfcSupported = await this.isSupported();
      if (isNfcSupported) {
        const permissionsGranted = await this.requestPermissions();
        if (permissionsGranted) {
          const nfcStatus = await this.checkPermissions();

          if (nfcStatus) {
            Nfc.startScanSession().then(() => {
              Nfc.addListener("nfcTagScanned", async (event) => {
                console.log("Listener react");
                await Nfc.stopScanSession();
                await removeAllListeners();
                const id = event.nfcTag.id;
                const hexString = this.bytesToHex(id);
                console.log("entry:" + this.entry + ", hex:" + hexString);
                $axios
                  .post(
                    "form.php",
                    this.$qs.stringify({
                      get_form_data: "get_form_data",
                      form: "nfc",
                      project: "control_center",
                    })
                  )
                  .then((res) => {
                    console.log(res.data);
                    const entry_id = res.data.find(
                      ({ hex }) => hex === hexString
                    ).entry_id;
                    const form = res.data.find(
                      ({ hex }) => hex === hexString
                    ).form;
                    const project = res.data.find(
                      ({ hex }) => hex === hexString
                    ).project;

                    $axios
                      .post(
                        "form.php",
                        this.$qs.stringify({
                          get_form_data: "get_form_data",
                          form: form,
                          project: project,
                        })
                      )
                      .then((res) => {
                        const entry = res.data.find(
                          ({ id }) => id === entry_id
                        );
                        this.setOpen(true);
                        this.display = entry[this.config.data.form_label];
                        this.entry = entry;

                        // alert(entry.sells);
                      });
                  });
              });
            });
          }
        }
      }
    },

    async write() {
      const removeAllListeners = async () => {
        await Nfc.removeAllListeners();
      };

      const isNfcSupported = await this.isSupported();
      if (isNfcSupported) {
        const permissionsGranted = await this.requestPermissions();
        if (permissionsGranted) {
          const nfcStatus = await this.checkPermissions();

          if (nfcStatus) {
            Nfc.startScanSession().then(() => {
              Nfc.addListener("nfcTagScanned", async (event) => {
                console.log("Listener react");
                await Nfc.stopScanSession();
                await removeAllListeners();
                const id = event.nfcTag.id;
                const hexString = this.bytesToHex(id);
                console.log("entry:" + this.entry + ", hex:" + hexString);
                $axios
                  .post(
                    "nfc.php",
                    this.$qs.stringify({
                      hex: hexString,
                      entry: this.entry,
                      form: this.config.data.form,
                      project: this.$route.params.project,
                    })
                  )
                  .then((res) => {
                    console.log(res.data);
                  });
              });
            });
          }
        }
      }
    },
  },*/
});
</script>
<style scoped>
h4 {
  text-align: center;
}
</style>
