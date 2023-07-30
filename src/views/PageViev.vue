<template>
  <ion-page>
    <ion-content>
      <div>
        <!--v-if="page.pageID"-->
        <DefaultPage /><!-- :title="page.title"-->
      </div>
      <!-- <div v-else>
      Error 404, not found
    </div>-->
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
/* eslint-disable */
import { IonPage, IonContent, useIonRouter } from "@ionic/vue";
//import axios from 'axios';
import { defineComponent, ref } from "vue";
import DefaultPage from "@/components/DefaultPage.vue";
import CardComponent from "@/components/CardComponent.vue";
//import { useRoute } from "vue-router";

export default defineComponent({
  name: "LogIn",
  components: {
    DefaultPage,
    IonPage,
    IonContent,
    CardComponent,
  },
});
</script>
