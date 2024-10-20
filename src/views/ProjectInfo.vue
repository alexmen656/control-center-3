<template>
  <ion-page>
    <ion-content>
      <LoadingSpinner v-if="loading" />
      <ion-grid v-if="!loading">
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
import LoadingSpinner from "@/components/LoadingSpinner.vue";

export default {
  name: "ProjectInfo",
  components: {
    LoadingSpinner,
  },
  data() {
    return {
      projectName: "",
      creationDate: "",
      loading: true,
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
        this.loading = false;
      });
  },
};
</script>
