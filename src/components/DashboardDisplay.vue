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
/* Modern Design System */
ion-grid {
  --ion-grid-padding: 0;
}

ion-row {
  gap: 20px;
  margin: 0;
}

ion-col {
  padding: 0;
}

/* Modern Card Styling */
ion-card {
  margin: 0;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  border: 1px solid var(--ion-color-step-150, #e2e8f0);
  background: var(--ion-background-color, #ffffff);
  transition: all 0.2s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
}

ion-card:hover {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  transform: translateY(-2px);
}

ion-card-header {
  padding: 20px 20px 0;
  background: transparent;
}

ion-card-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--ion-text-color, #1e293b);
  margin: 0;
}

ion-card-content {
  flex: 1;
  padding: 20px;
}

/* Stat Card Styling */
.stat-card {
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--ion-color-primary), var(--ion-color-secondary));
}

.stat-card ion-card-content {
  padding: 24px;
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.stat-icon {
  font-size: 32px;
  color: var(--ion-color-primary);
  opacity: 0.9;
}

.stat-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--ion-color-medium);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.stat-value {
  font-size: 2.75rem;
  font-weight: 700;
  color: var(--ion-text-color, #1e293b);
  margin-bottom: 8px;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.stat-label {
  font-size: 14px;
  color: var(--ion-color-medium);
  margin-bottom: 16px;
  font-weight: 500;
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.stat-trend.positive {
  background-color: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.stat-trend.negative {
  background-color: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.stat-trend ion-icon {
  font-size: 18px;
}

/* Delete Button */
ion-button {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 10;
  --box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  ion-card {
    background: var(--ion-background-color, #1e293b);
    border-color: var(--ion-color-step-250, #334155);
  }

  ion-card-title {
    color: var(--ion-text-color, #f1f5f9);
  }

  .stat-value {
    color: var(--ion-text-color, #f1f5f9);
  }

  .stat-card::before {
    opacity: 0.8;
  }

  .stat-trend.positive {
    background-color: rgba(16, 185, 129, 0.15);
    color: #10b981;
  }

  .stat-trend.negative {
    background-color: rgba(239, 68, 68, 0.15);
    color: #ef4444;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  ion-row {
    gap: 16px;
  }

  .stat-value {
    font-size: 2.25rem;
  }

  .stat-icon {
    font-size: 28px;
  }

  ion-card-content {
    padding: 16px;
  }

  .stat-card ion-card-content {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .stat-value {
    font-size: 2rem;
  }

  .stat-header {
    gap: 10px;
  }

  .stat-title {
    font-size: 12px;
  }
}
</style>
