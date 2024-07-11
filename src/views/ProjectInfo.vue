<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col></ion-col>
          <ion-col size="11">
            <ion-list>
              <ion-item>
                <ion-label position="floating">Project Name</ion-label>
                <ion-input disabled :value="projectName"></ion-input>
              </ion-item>
              <ion-item>
                <ion-label position="floating">Created On</ion-label>
                <ion-input disabled :value="creationDate"></ion-input>
              </ion-item>
              <!--  <ion-item>
          <ion-label>Internes Projekt?</ion-label>
          <ion-checkbox v-model="isInternal"></ion-checkbox>
        </ion-item>
        <ion-item>
          <ion-label>Öffentliche Website?</ion-label>
          <ion-checkbox v-model="hasWebsite"></ion-checkbox>
        </ion-item>-->
            </ion-list>
          </ion-col>
          <ion-col></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
export default {
  name: "ProjectInfo",
  data() {
    return {
      projectName: "",
      creationDate: "",
      //isInternal: false,
      //hasWebsite: false,
    };
  },
  created() {
    this.$axios
      .post(
        "projects.php",
        this.$qs.stringify({
          getProjectInfo: "getProjectInfo",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.projectName = res.data.name;
        this.creationDate = new Date(res.data.createdOn)
          .toLocaleDateString("en-GB")
          .replaceAll("/", ".");
      });
  },
};
</script>
