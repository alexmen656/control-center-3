<template>
  <ion-card>
    <Doughnut :data="data" :options="options" />
  </ion-card>
</template>

<script lang="ts">
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";
import { Doughnut } from "vue-chartjs";

ChartJS.register(ArcElement, Tooltip, Legend);

export default {
  name: "App",
  components: {
    Doughnut,
  },
  props: {
    data: Object,
    options: Object,
  },
};
</script>
