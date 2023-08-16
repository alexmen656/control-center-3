<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-list>
              <ion-item-sliding v-for="project in projects" :key="project.id">
                <ion-item>
                  <ion-label
                    ><ion-icon slot="start" :name="project.icon"></ion-icon>
                    {{ project.name }}</ion-label
                  >
                </ion-item>
                <ion-item-options>
                  <!--    <ion-item-option @click="favorite(project)">Favorisieren</ion-item-option>-->
                  <ion-item-option @click="info(project)" color="medium"
                    ><ion-icon
                      class="delete-icon"
                      name="information-circle-outline"
                    ></ion-icon>
                    Info</ion-item-option
                  >
                  <ion-item-option
                    @click="deleteee(project)"
                    color="danger"
                    class="delete"
                    ><ion-icon
                      class="delete-icon"
                      name="trash-outline"
                    ></ion-icon>
                    Löschen</ion-item-option
                  >
                </ion-item-options>
              </ion-item-sliding>
            </ion-list>

            <ion-row>
              <ion-col size="12"
                ><!-- size-lg="8"-->

                <h2 style="text-align: center">Create a new project</h2>
              </ion-col>
              <ion-col size-lg="1"></ion-col>
              <ion-col size="12" size-lg="10"
                ><!-- size-lg="8"-->
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
              <ion-col size-lg="1"></ion-col>
              <ion-col size-lg="1"></ion-col>
              <ion-col size="12" size-lg="10"
                ><!-- size-lg="8"-->
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
              <ion-col size-lg="1"></ion-col>
              <ion-col size-lg="1"></ion-col>
              <ion-col size="12" size-lg="10"
                ><!-- size-lg="8"-->
                <ion-button @click="submit(icon, name)"> Create </ion-button>
              </ion-col>
              <ion-col size-lg="1"></ion-col>
            </ion-row>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref } from "vue";
import {
  IonGrid,
  IonRow,
  IonCol,
  IonItem,
  IonButton,
  IonContent,
  IonPage,
  IonInput,
  IonLabel,
  IonItemSliding,
  IonIcon,
  IonItemOptions,
  IonItemOption,
  IonList,
} from "@ionic/vue";
import axios from "axios";
import qs from "qs";

export default defineComponent({
  name: "ProjectsPage",
  components: {
    IonGrid,
    IonRow,
    IonCol,
    IonItem,
    IonButton,
    IonContent,
    IonPage,
    IonInput,
    IonLabel,
    IonItemSliding,
    IonIcon,
    IonItemOptions,
    IonItemOption,
    IonList,
  },
  data() {
    return {
      name: "",
      icon: "",
    };
  },
  setup() {
    const projects = ref([]);

    // Hier wird eine API-Anfrage an den Server gesendet, um eine Liste von Projekten abzurufen.
    // Der Rückgabewert wird dann der 'projects' Ref-Variable zugewiesen.
    axios
      .get("/control-center/projects.php")
      .then((response) => {
        projects.value = response.data;
      })
      .catch((error) => {
        console.error(error);
      });

    /* function favorite(project) {
      console.log(`Projekt ${project.id} favorisiert.`);
    }*/

    function deleteee(project) {
      if (confirm("Do you really want to delte the project?")) {
        axios
          .post(
            "/control-center/projects.php",
            qs.stringify({
              deleteProject: "deleteProject",
              projectID: project.id,
            })
          )
          .then(() => {
            alert("Project deleted successfull");
            axios
              .get("/control-center/projects.php")
              .then((response) => {
                projects.value = response.data;
              })
              .catch((error) => {
                console.error(error);
              });
          });
        console.log(`Projekt ${project.id} gelöscht.`);
      }
    }

    function info(project) {
      // this.$router.push('/project/'+project.link+'/info');
      location.href = `/project/${project.link}/info`;
    }

    return {
      projects,
      // favorite,
      deleteee,
      info,
    };
  },
  methods: {
    submit(icon, name) {
      if (name != "") {
        axios
          .post(
            "/control-center/projects.php",
            qs.stringify({
              createProject: "createProject",
              projectName: name,
              projectIcon: icon,
            })
          )
          .then(() => {
            alert("Project created successfull");
            console.log("Hier!!");
            axios
              .get("/control-center/projects.php")
              .then((response) => {
                this.projects = response.data;
              })
              .catch((error) => {
                console.error(error);
              });
          });
        this.$emit("updateSidebar");
      } else {
        alert("Project Name is empty! " + this.name);
      }
    },
  },
});
</script>
<style scoped>
.delete {
  background-color: red !important;
}

.delete-icon {
  margin-right: 3px;
}
</style>
