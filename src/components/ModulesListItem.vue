<template>
  <div
    v-for="(module, index) in modules"
    :key="index"
    class="item"
    style="
      padding-left: 1rem;
      padding-right: 1rem;
      margin-bottom: 0.5rem;
      height: 100%;
    "
  >
    <!--
        size-xl="4"
    size-lg="6"
    size="12"
  -->
    <ion-row class="bg-card" style="border-radius: 1rem">
      <ion-col size="3" style="display: flex; align-items: center"
        ><img class="app-icon" :src="module.icon" alt="" /></ion-col
      ><ion-col
        size="6"
        style="
          padding-left: 0.5rem;
          display: flex;
          flex-direction: column;
          justify-content: center;
        "
        ><h3>{{ module.name }}</h3>
        ^0.6.3-alpha<br />
        {{ module.price > 0 ? module.price + "$" : "Free" }}</ion-col
      ><ion-col
        size="2"
        style="display: flex; align-items: center; justify-content: center"
      >
        <ion-button
          v-if="module.status == 'installed'"
          @click="deinstall(module.id, index)"
          >Deinstall</ion-button
        ><ion-button
          v-if="module.status == 'not_installed'"
          @click="install(module.id, index)"
          >Install</ion-button
        >
        <circle-progress
          v-if="module.status == 'installing'"
          size="36"
          show-percent="true"
          fill-color="red"
          :percent="module.progress"
          border-width="4"
          border-bg-width="4"
          empty-color="#fa9d9d"
        /> </ion-col
      ><ion-col size="1" />
    </ion-row>
</div>
</template>
<script>
import "vue3-circle-progress/dist/circle-progress.css";
import CircleProgress from "vue3-circle-progress";
import {
  IonRow,
  IonCol,
  IonButton,
} from "@ionic/vue";

export default {
  name: "ModulesListItem",
  components: {
    CircleProgress,
    IonRow,
    IonCol,
    IonButton,
  },
  props: {
    modules: {
      type: Array,
      required: true,
    },
  },
  methods: {
    install(id, index){
        this.emitter.emit("install", { moduleID: id, index: index });
    },
    deinstall(id, index){
        this.emitter.emit("deinstall", { moduleID: id, index: index });
    }
  }
};
</script>
<style scoped>
ion-card {
  --background: var(--ion-item-background, var(--ion-background-color, #fff));
}

@media (prefers-color-scheme: dark) {
  .bg-card {
    background: #000000;
  }
}

@media (prefers-color-scheme: light) {
  .bg-card {
    background: #e9e9e9;
  }
}

h3 {
  margin: 0;
  margin-bottom: 0.5rem !important;
  line-height: 1;
}


@media only screen and (max-width: 1100px) {
    .item {
        width: 100%;
    }
}

@media only screen and (min-width: 1101px) {
    .item {
        width: 50%;
    }
}

@media only screen and (min-width: 1600px) {
    .item {
        width: 33.333333%;
    }
}

.app-icon {
    height: 100%;
    width: 100%;
}
</style>
