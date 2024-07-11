<template>
  <ion-page>
    <ion-content class="ion-padding">
      <ion-list style="width: 100%">
        <ion-item-sliding v-for="packagee in packages" :key="packagee.name">
          <ion-item>
            <ion-label> {{ packagee.name }} </ion-label>
            <ion-reorder slot="end"></ion-reorder>
          </ion-item>
          <ion-item-options>
            <ion-item-option color="medium" @click="edit(packagee)"
              >Info<!--Edit @click="deletee(index)--></ion-item-option
            >
            <ion-item-option
              >Delete<!--@click="deletee(index)--></ion-item-option
            >
          </ion-item-options>
        </ion-item-sliding>

        <!-- <ion-item>
              <ion-input
                v-model="item"
                :value="item"
                placeholder="Add new Item"
              />
              <ion-button @click="newItem" slot="end">Add</ion-button>
            </ion-item>-->
      </ion-list>

      <!--<form> @submit.prevent="addPackage"-->
      <ion-item>
        <ion-label position="floating">Add new package</ion-label>
        <ion-select multiple v-model="selectedPackages">
          <ion-select-option
            v-for="packagee in availablePackages"
            :key="packagee.name"
            :value="packagee"
          >
            {{ packagee.name }}
          </ion-select-option>
        </ion-select>
      </ion-item>

      <ion-button @click="addPackage()" expand="full" type="submit"
        >Add Package</ion-button
      >
      <!--</form>-->

      <ion-modal
        :is-open="isOpenRef"
        css-class="my-custom-class"
        @didDismiss="closeModal(false)"
      >
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="closeModal()">Cancel</ion-button>
            </ion-buttons>
            <ion-title style="text-align: center">Package Info</ion-title
            ><!--{{ data.id }} {{ data.project }} {{ data.form }}-->
            <ion-buttons slot="end">
              <ion-button :strong="true" @click="closeModal()"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <FloatingInput
            :defaultVal="edit_package.name"
            label="Package Name"
            placeholder="name"
            type="text"
            :disabled="true"
          />
          <FloatingInput
            :defaultVal="edit_package.type"
            label="Package Type"
            placeholder="type"
            type="text"
            :disabled="true"
          />

          <FloatingInput
            v-for="[key, val] in Object.entries(edit_package.attributes)"
            :key="key"
            :defaultVal="val"
            :label="key"
            :placeholder="key"
            type="text"
            :disabled="true"
          />
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>
<script>

import FloatingInput from "@/components/FloatingInput.vue";

import { ref } from "vue";
export default {
  name: "PackageManager",
  components: {
    FloatingInput,
  },
  data() {
    return {
      selectedPackages: [],
      packages: [],
      availablePackages: [
        /*  {
          id: 1,
          name: "Bootstrap@5.3.2 CSS",
          type: "style",
          attributes: {
            href: "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css",
            rel: "stylesheet",
            integrity:
              "sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN",
            crossorigin: "anonymous",
          },
        },
        {
          id: 2,
          name: "Bootstrap@5.3.2 JS",
          type: "script",
          attributes: {
            src: "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js",
            integrity:
              "sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL",
            crossorigin: "anonymous",
          },
        },
        {
          id: 3,
          name: "Font Awesome",
          type: "style",
          attributes: {
            href: "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css",
            integrity:
              "sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==",
            crossorigin: "anonymous",
            referrerpolicy: "no-referrer",
          },
        },
        {
          id: 4,
          name: "jQuery",
          type: "script",
          attributes: {
            src: "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js",
            integrity:
              "sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==",
            crossorigin: "anonymous",
            referrerpolicy: "no-referrer",
          },
        },
        {
          id: 5,
          name: "icofont",
          type: "style",
          attributes: {
            href: "myProjects/webProject/icofont/css/icofont.min.css",
            rel: "stylesheet",
          },
        },*/
      ],
    };
  },
  async created() {
    const packages2 = [];
    const packagess = (await $axios.post(
      "packages.php",
      this.$qs.stringify({
        getPackages: "getPackages",
        project: this.$route.params.project,
      })
    )).data;


    if (packagess) {
      this.packages = packagess;
     // console.log(this.packagess);
    }
    const packages = (await $axios.post("packages.php")).data; //.packages

    if (packages) {
      packages.forEach((packagee) => {
        //   console.log(JSON.parse(packagee.attributes));
        packages2.push({
          name: packagee.name,
          type: packagee.type,
          attributes: packagee.attributes,
        });
      });

      this.availablePackages = packages;
    }
  },
  setup() {
    const isOpenRef = ref(false);
    const edit_package = ref({});
    const edit = (edit_packagee) => {
      isOpenRef.value = true;
      edit_package.value = edit_packagee;
      console.log(edit_package);
    };
    const closeModal = () => {
      isOpenRef.value = false;
    };
    return { isOpenRef, edit, closeModal, edit_package };
  },
  methods: {
    addPackage() {
      console.log(this.selectedPackages);
      this.selectedPackages.forEach((packagee) => {
        console.log(packagee);
        this.packages.push(packagee);
      });

      $axios.post(
        "packages.php",
        this.$qs.stringify({
          project: this.$route.params.project,
          packages: this.selectedPackages,
        })
      );
      console.log(this.packages);
      this.selectedPackages = [];
    },
  },
};
</script>
