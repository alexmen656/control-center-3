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
  import FloatingSelect from "@/components/FloatingSelect.vue";
  
  export default {
    name: "BarcodeScanner",
    components: {
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
      $axios
        .post(
          "form.php",
          this.$qs.stringify({
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
          $axios.post("tools.php", this.$qs.stringify({newToolConfig: "newToolConfig", config: JSON.stringify({form: this.form, form_label: this.form_label}), project: this.$route.params.project, tool: "nfc"}));
      },
      toName(name) {
        return name.replaceAll(" ", "_").toLowerCase();
      }
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
  