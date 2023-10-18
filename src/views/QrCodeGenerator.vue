<template>
  <ion-page class="ion-padding">
    <ion-content>
      <FloatingSelect v-model="entry" :select="select" />
      <FloatingSelect
        v-if="entry"
        v-model="code_type"
        :select="code_type_select"
      />

      <ion-button v-if="code_type" @click="generate()"
        >Generate
        {{ code_type == "barcode" ? "Barcode" : "QR Code" }}</ion-button
      >
      <div class="img-box">
        <h2>{{ h2 }}</h2>
        <img v-if="link" alt="Barcode Generator TEC-IT" :src="link" />
      </div>
      <ion-grid>
        <ion-row>
          <ion-col>
            <img v-if="link" class="img2" alt="Barcode Generator TEC-IT" :src="link" />

            <ion-button v-if="link" @click="print()"
              >Print
              {{ code_type == "barcode" ? "Barcode" : "QR Code" }}</ion-button
            >
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import qs from "qs";
import { getConfig } from "@/getToolConfig";
import FloatingSelect from "@/components/FloatingSelect.vue";
import {
  IonPage,
  IonContent,
  IonGrid,
  IonRow,
  IonCol,
  IonButton,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  name: "BarcodeScanner",
  components: {
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    IonButton,
    FloatingSelect,
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
      code_type: "",
      link: "",
      h2: "",
      data: [],
      config: {},
      found: {},
      display: "",
      link_data: 0,
      link_dataa: {},

      entry: "",
      select: {
        type: "select",
        name: "entry",
        label: "Entry",
        placeholder: "Entry",
        options: [],
      },
      code_type_select: {
        type: "select",
        name: "form",
        label: "Code Type",
        placeholder: "Code Type",
        options: [
          { value: "barcode", label: "Barcode" },
          { value: "qr_code", label: "QR Code" },
        ],
      },
    };
  },
  mounted() {
    this.$watch(
      () => this.code_type,
      () => {
        this.link = "";
      }
    );
  },
  async created() {
    const config = await getConfig(
      "qr-code-generator",
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
        this.data.forEach((element, index) => {
          this.select.options[index] = {
            value: this.toName(element[config.data.form_label]),
            label: element[config.data.form_label],
          };
        });
      });
  },
  methods: {
    print() {
      window.print();
    },
    generate() {
      this.link_dataa = this.data.find(
        (da) => this.toName(da[this.config.data.form_label]) == this.entry
      );

      this.link_data = this.link_dataa.id;
      this.h2 = this.link_dataa[this.config.data.form_label];

      if (this.code_type == "barcode") {
        this.link = `https://barcode.tec-it.com/barcode.ashx?data=${this.link_data}&code=&translate-esc=true&dpi=600&hidehrt=True`;
      } else if (this.code_type == "qr_code") {
        this.link = `https://qrcode.tec-it.com/API/QRcode?data=${this.link_data}&code=&translate-esc=true&dpi=600&hidehrt=True`;
      }
    },
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
  },
});
</script>
<style>
img {
  max-height: 35%;
}

h2 {
  display: none;
}

.img-box {
  display: none;
}

@media print {
  h2 {
    display: block;
    text-align: center;
    font-size: 3rem;
    margin-bottom: 1.25rem;
  }
  .img-box {
    width: 100% !important;
    height: 100% !important;
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }

  img {
    display: none;
  }

  .img-box > img {
    display: block !important;
    width: 13cm;
    max-height: 100% !important;
  }

  .img2 {
    display: none;
  }

  ion-button,
  .reader,
  ion-header,
  ion-menu,
  ion-input,
  ion-select,
  ion-icon,
  ion-title {
    display: none !important;
    width: 0;
    height: 0;
  }

  @page {
    size: auto;
    margin: 0;
  }
}
</style>
