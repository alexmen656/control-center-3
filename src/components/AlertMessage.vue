<template>
  <ion-card>
    <ion-card-header>
      <ion-card-title> {{ message.title }} </ion-card-title>
    </ion-card-header>
    <ion-card-content> {{ message.content }} </ion-card-content>
  </ion-card>
</template>

<script>
import { IonCard, IonCardHeader, IonCardTitle, IonCardContent } from '@ionic/vue';

export default {
  name: "AlertMessage",
  components: {
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
  },
  props: {
    message: {},
  },
};
</script>

<style scoped>
ion-card {
  --background: rgba(2, 176, 14, 0.14);
  padding: 0.5rem;
  border-radius: 4px;
}

ion-card-header,
ion-card-content {
  margin: 0;
  padding: 0;
}

ion-card-title {
  --color: rgb(0, 111, 7);
}
</style>
