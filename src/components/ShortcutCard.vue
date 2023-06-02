<template>
  <ion-card @click="redirectToLink(link)" class="shortcut-card">
    <ion-card-header class="card-header">
      <ion-card-title>{{ title }}</ion-card-title>
      <ion-icon class="icon" name="open-outline"></ion-icon>
    </ion-card-header>
  </ion-card>
</template>

<script>
import { IonCard, IonCardHeader, IonCardTitle, IonIcon } from '@ionic/vue';
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ShortcutCard',
  components: {
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonIcon
  },
  props: {
    link: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
  },
  methods: {
    redirectToLink(link) {
      window.location.href = link;
    },
  },
});
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: center;
  align-items: center;
}

.card-header .icon {
  margin-left: 5px;
}

ion-card-title {
  font-family: HelveticaNeue;
  font-size: 24px;

  letter-spacing: -0.57px;
  text-align: center;
  transition: color 0.2s ease-in-out; /* Übergangseffekt auf die Textfarbe */
  cursor: default;
}

ion-card:hover > ion-card-header > ion-card-title {
  color: red !important; /* Textfarbe wird auf Rot geändert */
}

.shortcut-card {
  transition: box-shadow 0.2s ease-in-out;
  /*box-shadow: 0 0 0 rgba(255, 0, 0, 0);/*rgba(255, 255, 255, 0)*/
}

.shortcut-card:hover {
  box-shadow: 0 0 20px rgba(255, 0, 0, 0.5);/*rgba(255, 255, 255, 0)*/
}

.shortcut-card:hover ion-card-title {
  color: #FFF;
}
</style>