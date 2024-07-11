<template>
  <ion-card>
    <Pie :data="data" :options="options" />
  </ion-card>
</template>

<script lang="ts">
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  Colors,
} from "chart.js";
import { Pie } from "vue-chartjs";

ChartJS.register(ArcElement, Tooltip, Legend, Colors);

export default {
  name: "PieChart",
  components: {
    Pie,
  },
  props: {
    data: Object,
    options: Object,
  },
};
</script>
