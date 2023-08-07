<template>
  <ion-card :class="color">
    <ion-card-header>
      <ion-card-title :class="color"> {{ message.title }} </ion-card-title>
    </ion-card-header>
    <ion-card-content> {{ message.content }} </ion-card-content>
  </ion-card>
</template>

<script>
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
} from "@ionic/vue";

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
    color: {
      type: String,
      required: false,
      default: "success",
    },
  },
};
</script>

<style scoped>
ion-card {
  padding: 0.5rem;
  border-radius: 4px;
}

ion-card-header,
ion-card-content {
  margin: 0;
  padding: 0;
}

ion-card.success {
  --background: rgba(2, 176, 14, 0.14);
}
ion-card-title.success {
  --color: rgb(0, 111, 7);
}

ion-card.danger {
  --background: #f8d7da;
}
ion-card-title.danger {
  --color: #842029;
}
</style>
