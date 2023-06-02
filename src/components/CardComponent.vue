<template>
 <ion-card>
    <ion-card-header>
      <ion-card-title>Sales</ion-card-title>
    </ion-card-header>
    <ion-card-content>
      <p>Current Month: {{ currentSales }}</p>
      <p>Last Month: {{ lastMonthSales }}</p>
      <p v-if="increase > 0">Increase: {{ increase }}%</p>
      <p v-else>Decrease: {{ increase }}%</p>
    </ion-card-content>
  </ion-card>
</template>

<script>
import { useRoute } from 'vue-router';
import { defineComponent, ref, } from 'vue';
import { IonCard, IonCardContent, IonCardHeader, IonCardTitle } from '@ionic/vue';
import { basketballOutline, medkitOutline, desktopOutline, analyticsOutline, peopleOutline, earthOutline, fitnessOutline, handRightOutline, leafOutline, musicalNoteOutline, schoolOutline, hardwareChipOutline, headsetOutline, pawOutline, planetOutline, rocketOutline, bandageOutline, search } from 'ionicons/icons';


export default defineComponent({
name: "CardcCmponent",
components: {
IonCard,
IonCardContent, 
IonCardHeader, 
IonCardTitle 
},
data() {
    return {
      currentSales: 0,
      lastMonthSales: 0,
      increase: 0
    }
  },
  mounted() {
    // Fetch sales data from API
    this.currentSales = 1200
    this.lastMonthSales = 1000
    this.increase = ((this.currentSales - this.lastMonthSales) / this.lastMonthSales) * 100
  }

});
</script>

<style scoped>

ion-card {
 /* background: #000000;*/
  border-radius: 20px;
}
</style>