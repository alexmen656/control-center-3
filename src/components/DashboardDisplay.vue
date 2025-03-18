<template>
  <ion-grid>
    <ion-row>
      <ion-col
        v-for="(chart, index) in charts"
        v-show="
          chart.chart_type != 'date_bar_chart' &&
          chart.chart_type != 'bar_chart'
        "
        :key="index"
        size="12"
        size-lg="6"
        size-xl="4"
      >
        <DonutChart
          v-if="chart.chart_type == 'donut_chart'"
          :data="chart.data"
          :options="options"
        />
        <PieChart
          v-if="chart.chart_type == 'pie_chart'"
          :data="chart.data"
          :options="options"
        />
        <ShortcutCard
          v-if="chart.chart_type == 'card'"
          :link="'/' + chart.url"
          :title="chart.name"
        />
        <ion-button v-if="editView" @click="deleteChart(index)"
          >Delete Chart</ion-button
        >
      </ion-col>
      <ion-col
        v-for="(chart, index) in charts"
        v-show="
          chart.chart_type == 'date_bar_chart' ||
          chart.chart_type == 'bar_chart'
        "
        :key="index"
        size="12"
        size-lg="12"
        size-xl="8"
      >
        <ion-card
          v-if="
            chart.chart_type == 'date_bar_chart' ||
            chart.chart_type == 'bar_chart'
          "
        >
          <BarChart :data="chart.data" :options="options" />
        </ion-card>
        <ion-button v-if="editView" @click="deleteChart(index)"
          >Delete Chart</ion-button
        >
      </ion-col>
    </ion-row>
  </ion-grid>
</template>
<script>
import { defineComponent } from "vue";
import DonutChart from "@/components/DonutChart.vue";
import PieChart from "@/components/PieChart.vue";
import ShortcutCard from "@/components/ShortcutCard.vue";
import BarChart from "@/components/BarChart.vue";

export default defineComponent({
  name: "DefaultPage",
  props: {
    charts: {
      type: Array,
      required: true,
    },
    editView: {
      type: Boolean,
      required: true,
    },
  },
  components: {
    DonutChart,
    PieChart,
    ShortcutCard,
    BarChart,
  },
  emits: ["deleteChart"],
  methods: {
    deleteChart(index) {
      this.$emit("deleteChart", index);
    },
  },
});
</script>
