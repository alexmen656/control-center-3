<template>
  <ion-page>
    <ion-content>
      <DefaultPage />
    </ion-content>
  </ion-page>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import DefaultPage from "@/components/DefaultPage.vue";

export default defineComponent({
  name: "LogIn",
  components: {
    DefaultPage,
  },
});
</script>
