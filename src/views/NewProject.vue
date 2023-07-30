<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="12" size-lg="8"> Create a new project </ion-col>
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Icon</ion-label>
              <ion-input
                type="text"
                v-model="icon"
                :value="icon"
                @ionInput="icon = $event.target.value"
                placeholder="Enter Icon Code"
              ></ion-input>
            </ion-item>
          </ion-col>
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Project Name</ion-label>
              <ion-input
                v-model="name"
                :value="name"
                @ionInput="name = $event.target.value"
                type="text"
                placeholder="Enter Project Name"
              ></ion-input>
            </ion-item>
          </ion-col>
          <ion-col size="12" size-lg="8">
            <ion-button @click="submit"> Create </ion-button>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from "axios";
import qs from "qs";
import {
  IonPage,
  IonContent,
  IonCol,
  IonList,
  IonCard,
  IonInput,
  IonToolbar,
  IonTitle,
  IonHeader,
  IonLabel,
  IonRow,
  IonItem,
} from "@ionic/vue";

export default {
  name: "NewProject",

  components: {
    IonItem,
    IonLabel,
    IonInput,
    IonPage,
    IonContent,
  },
  data() {
    return {
      name: "",
    };
  },
  methods: {
    submit() {
      if (this.name != "") {
        axios
          .post(
            "/control-center/projects.php",
            qs.stringify({
              createProject: "createProject",
              projectName: this.name,
            })
          )
          .then((res) => {
            alert("Project created successfull");
          });
      } else {
        alert("Project Name is empty!");
      }
    },
  },
};
</script>
