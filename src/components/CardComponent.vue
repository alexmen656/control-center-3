<template>
  <ion-card>
    <ion-card-header>
      <ion-card-title>Sales</ion-card-title>
    </ion-card-header>
    <ion-card-content style="display: flex; align-items: end">
      <div style="width: 60%">
        <p>Current Month: {{ trend[3] }}</p>
        <p>Last Month: {{ trend[2] }}</p>
        <p v-if="trend[2] < trend[3]">Increase: {{ Math.round(((trend[3] - trend[2]) / trend[2]) * 100) }}%</p>
        <p v-else>Decrease: {{ Math.round(((trend[3] - trend[2]) / trend[2]) * 100) }}%</p>
      </div>
      <div style="width: 40%; height: 75%; display: flex">
        <trend
          :data="trend"
          :gradient="trend[2] > trend[3] ? ['red'] : ['green']"
          :stroke-width="7"
          :radius="12"
          auto-draw
          smooth
          :stroke-linecap="round"
        >
        </trend>
      </div>
    </ion-card-content>
  </ion-card>
</template>

<script>
//import { useRoute } from "vue-router";
import { defineComponent } from "vue"; //, ref
import Trend from "@ddgll/vue3-trend";
import {
  IonCard,
  IonCardContent,
  IonCardHeader,
  IonCardTitle,
} from "@ionic/vue";
//import TrendChart from "vue-trend-chart";

//import TrendChart from "vue-trend-chart";

/*import {
 // basketballOutline,
//  medkitOutline,
  //desktopOutline,
  //analyticsOutline,
 peopleOutline,
  earthOutline,
  fitnessOutline,
  handRightOutline,
  leafOutline,
  musicalNoteOutline,
  schoolOutline,
  hardwareChipOutline,
  headsetOutline,
  pawOutline,
  planetOutline,
  rocketOutline,
  bandageOutline,
  search,
} from "ionicons/icons";*/

export default defineComponent({
  name: "CardComponent",
  components: {
    //  TrendChart,
    Trend,
    IonCard,
    IonCardContent,
    IonCardHeader,
    IonCardTitle,
  },
  data() {
    return {
      currentSales: 0,
      lastMonthSales: 0,
      increase: 0,
      trend: [Math.floor(Math.random() * 1001), Math.floor(Math.random() * 1001), Math.floor(Math.random() * 1001), Math.floor(Math.random() * 1001)],
    };
  },
  mounted() {
    // Fetch sales data from API
    this.currentSales = 1200;
    this.lastMonthSales = 1000;
    this.increase =
      ((this.currentSales - this.lastMonthSales) / this.lastMonthSales) * 100;
  },
});
</script>

<style scoped>
ion-card {
  /* background: #000000;*/
  border-radius: 18px;
  padding: .25rem !important;
}
</style>
