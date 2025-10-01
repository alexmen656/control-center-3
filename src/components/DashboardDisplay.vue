<template>
  <ion-grid>
    <ion-row>
      <!-- Stat Widgets (Module Widgets) -->
      <ion-col
        v-for="(chart, index) in statCharts"
        :key="'stat-' + index"
        size="12"
        size-md="6"
        size-lg="4"
        size-xl="3"
      >
        <ion-card class="stat-card">
          <ion-card-content>
            <div class="stat-header">
              <ion-icon v-if="chart.icon" :icon="chart.icon" class="stat-icon"></ion-icon>
              <h3 class="stat-title">{{ chart.title }}</h3>
            </div>
            <div class="stat-value">{{ formatStatValue(chart.data.value) }}</div>
            <div class="stat-label">{{ chart.data.label }}</div>
            <div v-if="chart.data.trend" class="stat-trend" :class="{ 'positive': chart.data.trend > 0, 'negative': chart.data.trend < 0 }">
              <ion-icon :icon="chart.data.trend > 0 ? 'trending-up' : 'trending-down'"></ion-icon>
              {{ Math.abs(chart.data.trend) }}%
            </div>
          </ion-card-content>
          <ion-button v-if="editView" @click="deleteChart(charts.indexOf(chart))" color="danger" size="small">
            <ion-icon slot="icon-only" :icon="'trash-outline'"></ion-icon>
          </ion-button>
        </ion-card>
      </ion-col>

      <!-- Small Charts (Pie, Donut) -->
      <ion-col
        v-for="(chart, index) in smallCharts"
        :key="'small-' + index"
        size="12"
        size-lg="6"
        size-xl="4"
      >
        <ion-card>
          <ion-card-header v-if="chart.title">
            <ion-card-title>{{ chart.title }}</ion-card-title>
          </ion-card-header>
          <ion-card-content>
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
          </ion-card-content>
          <ion-button v-if="editView" @click="deleteChart(charts.indexOf(chart))" color="danger" size="small">
            <ion-icon slot="icon-only" :icon="'trash-outline'"></ion-icon>
          </ion-button>
        </ion-card>
      </ion-col>

      <!-- Large Charts (Bar, Line) -->
      <ion-col
        v-for="(chart, index) in largeCharts"
        :key="'large-' + index"
        size="12"
        size-lg="12"
        size-xl="8"
      >
        <ion-card>
          <ion-card-header v-if="chart.title">
            <ion-card-title>{{ chart.title }}</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <BarChart :data="chart.data" :options="options" />
          </ion-card-content>
          <ion-button v-if="editView" @click="deleteChart(charts.indexOf(chart))" color="danger" size="small">
            <ion-icon slot="icon-only" :icon="'trash-outline'"></ion-icon>
          </ion-button>
        </ion-card>
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
    options: {
      type: Object,
      default: () => ({ responsive: true })
    }
  },
  components: {
    DonutChart,
    PieChart,
    ShortcutCard,
    BarChart,
  },
  emits: ["deleteChart"],
  computed: {
    statCharts() {
      return this.charts.filter(chart => chart.chart_type === 'stat');
    },
    smallCharts() {
      return this.charts.filter(chart => 
        chart.chart_type !== 'date_bar_chart' && 
        chart.chart_type !== 'bar_chart' &&
        chart.chart_type !== 'stat'
      );
    },
    largeCharts() {
      return this.charts.filter(chart => 
        chart.chart_type === 'date_bar_chart' || 
        chart.chart_type === 'bar_chart'
      );
    }
  },
  methods: {
    deleteChart(index) {
      this.$emit("deleteChart", index);
    },
    formatStatValue(value) {
      if (typeof value === 'number') {
        return new Intl.NumberFormat('de-DE').format(value);
      }
      return value;
    }
  },
});
</script>

<style scoped>
.stat-card {
  height: 100%;
}

.stat-card ion-card-content {
  padding: 20px;
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.stat-icon {
  font-size: 28px;
  color: var(--ion-color-primary);
}

.stat-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--ion-color-medium);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--ion-color-dark);
  margin-bottom: 8px;
  line-height: 1;
}

.stat-label {
  font-size: 14px;
  color: var(--ion-color-medium);
  margin-bottom: 12px;
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
}

.stat-trend.positive {
  background-color: rgba(16, 185, 129, 0.1);
  color: var(--ion-color-success);
}

.stat-trend.negative {
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--ion-color-danger);
}

.stat-trend ion-icon {
  font-size: 16px;
}

@media (prefers-color-scheme: dark) {
  .stat-value {
    color: var(--ion-color-light);
  }
}
</style>
