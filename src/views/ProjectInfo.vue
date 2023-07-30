<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Projekt-Info</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <ion-list>
        <ion-item>
          <ion-label position="floating">Projektname</ion-label>
          <ion-input v-model="projectName"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Erstelldatum</ion-label>
          <ion-datetime v-model="creationDate"></ion-datetime>
        </ion-item>
        <ion-item>
          <ion-label>Internes Projekt?</ion-label>
          <ion-checkbox v-model="isInternal"></ion-checkbox>
        </ion-item>
        <ion-item>
          <ion-label>Öffentliche Website?</ion-label>
          <ion-checkbox v-model="hasWebsite"></ion-checkbox>
        </ion-item>
      </ion-list>
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
      isInternal: false,
      hasWebsite: false,
    };
  },
};
</script>
