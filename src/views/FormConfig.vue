<template>
  <ion-page class="ion-padding">
    <ion-content>
      <ion-toggle v-model="form">Enable API</ion-toggle>
     <!-- <ion-button v-if="enabledMethods.length" @click="save()"
        >Save Config</ion-button
      >-->
      <h3 v-if="form">Methods</h3>
      <div v-if="form && enabledMethods.length">
        <ion-card v-for="(method, index) in enabledMethods" :key="index">
          <ion-card-header>
            <ion-card-title>{{ capitalizeFirstLetter(method) }}</ion-card-title>
          </ion-card-header>
        </ion-card>
      </div>
      <ion-card class="add-method" v-if="form">
        <ion-card-header>
          <ion-card-title @click="setOpen(true)">Add Method</ion-card-title>
        </ion-card-header>
      </ion-card>

      <ion-modal :is-open="isOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="setOpen(false)" style="color: red"
                >Cancel</ion-button
              >
            </ion-buttons>
            <ion-title style="text-align: center">Add New Method</ion-title>
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="confirm()" style="color: red"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <FloatingSelect v-if="form" v-model="method" :select="select" />
          <ion-button v-if="method" @click="addMethod()">Add Method</ion-button>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import FloatingSelect from "@/components/FloatingSelect.vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  name: "BarcodeScanner",
  components: {
    FloatingSelect,
  },
  data() {
    return {
      forms: [],
      form: false,
      method: "",
      enabledMethods: [],
      form_label: "",
      select: {
        type: "select",
        name: "type",
        label: "Type",
        placeholder: "GET",
        options: [
          { value: "get", label: "GET" },
          { value: "post", label: "POST" },
          { value: "update", label: "UPDATE" },
          { value: "delete", label: "DELETE" },
        ],
      },
      //isset($_POST['newToolConfig']) && isset($_POST['config']) && isset($_POST['project']) && isset($_POST['tool'])
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
  mounted() {
    this.$watch(
      () => this.form,
      () => {
        this.save();
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
    /* this.$axios
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
      });*/

    this.$axios
      .post(
        "tools.php",
        this.$qs.stringify({
          getToolConfig: "getToolConfig",
          project: this.$route.params.project,
          tool: this.$route.params.form,
        })
      )
      .then((res) => {
        if (res.data.apiEnabled) {
          this.form = res.data.apiEnabled;
        }

        if (res.data.enabledMethods.length > 0) {
          this.enabledMethods = res.data.enabledMethods;
        }
      });
  },
  methods: {
    cancel() {
      this.$refs.modal.$el.dismiss(null, "cancel");
    },
    confirm() {
      this.addMethod();
      this.setOpen(false);
    },
    addMethod() {
      this.enabledMethods.push(this.method);
      this.method = "";
      this.save();
    },
    save() {
      const config = {
        apiEnabled: this.form,
        enabledMethods: this.enabledMethods,
      };
      this.$axios.post(
        "tools.php",
        this.$qs.stringify({
          newToolConfig: "newToolConfig",
          config: JSON.stringify(config),
          project: this.$route.params.project,
          tool: this.$route.params.form,
        })
      );
    },
    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
    capitalizeFirstLetter(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
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

.add-method {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  border: 2px dashed gray;
  background-color: transparent;
}
</style>
