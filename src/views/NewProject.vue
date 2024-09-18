<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <!--   <ion-col size="12" size-lg="8"> Create a new project </ion-col>-->
          <ion-col size="12" size-lg="8">
            <ion-item>
              <ion-label position="floating">Icon</ion-label>
              <ion-input
                type="text"
                v-model="icon"
                :value="icon"
                @ionInput="icon = $event.target.value"
                placeholder="Enter Icon Code"
              >
              </ion-input>
              <ion-icon
                class="input-icon"
                slot="end"
                size="large"
                :name="icon ? icon : 'help-circle-outline'"
              ></ion-icon>
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
export default {
  name: "NewProject",
  data() {
    return {
      name: "",
      icon: "",
    };
  },
  methods: {
    submit() {
      if (this.name != "") {
        this.$axios
          .post(
            "projects.php",
            this.$qs.stringify({
              createProject: "createProject",
              projectName: this.name,
              projectIcon: this.icon,
            })
          )
          .then(() => {
            //res
            alert("Project created successfull");
          });
      } else {
        alert("Project Name is empty!");
      }
    },
  },
};
</script>
<style scoped>
.input-icon {
  height: 100%;
  margin: 0 !important;
  padding: 0 !important;
}
</style>
