<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/"></ion-back-button>
        </ion-buttons>
        <ion-title>Sidebar Editor</ion-title>
        <ion-buttons slot="end">
            <ion-button @click="save">Save</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item v-for="(tool, index) in tools" :key="index">
            <ion-grid>
                <ion-row class="ion-align-items-center">
                    <ion-col size="auto">
                        <ion-icon :name="tool.icon" style="font-size: 24px;"></ion-icon>
                    </ion-col>
                    <ion-col>
                        <ion-input label="Name" label-placement="floating" v-model="tool.name" placeholder="Name"></ion-input>
                    </ion-col>
                    <ion-col>
                        <ion-input label="Icon" label-placement="floating" v-model="tool.icon" placeholder="Icon"></ion-input>
                    </ion-col>
                </ion-row>
            </ion-grid>
        </ion-item>
      </ion-list>
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from "vue";
import axios from "axios";
import qs from "qs";
import { useRoute } from "vue-router";
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonInput,
  IonIcon,
  IonButton,
  IonButtons,
  IonBackButton,
  IonGrid,
  IonRow,
  IonCol,
  toastController
} from "@ionic/vue";

export default defineComponent({
  name: "SidebarEditor",
  components: {
    IonPage,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonList,
    IonItem,
    IonInput,
    IonIcon,
    IonButton,
    IonButtons,
    IonBackButton,
    IonGrid,
    IonRow,
    IonCol
  },
  setup() {
    const route = useRoute();
    const tools = ref<any[]>([]);

    const loadData = () => {
      axios
        .get("sidebar.php?getSideBarByProjectName=" + route.params.project)
        .then((response) => {
          if (response.data.tools) {
            tools.value = response.data.tools;
          }
        })
        .catch((error) => {
            console.error("Error loading sidebar data:", error);
        });
    };

    const save = async () => {
      try {
        await axios.post(
            "update_sidebar.php", 
            qs.stringify({
                project: route.params.project,
                tools: JSON.stringify(tools.value)
            })
        );
        
        const toast = await toastController.create({
          message: 'Sidebar updated successfully',
          duration: 2000,
          color: 'success'
        });
        await toast.present();
      } catch (error) {
        console.error("Error saving sidebar:", error);
        const toast = await toastController.create({
          message: 'Error saving sidebar',
          duration: 2000,
          color: 'danger'
        });
        await toast.present();
      }
    };

    onMounted(() => {
      loadData();
    });

    return {
      tools,
      save,
    };
  },
});
</script>