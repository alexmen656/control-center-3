<template>
  <div class="dashboard-grid" ref="gridContainer">
    <!-- Stat Widgets (4x2 grid cells - small info cards) -->
    <div
      v-for="(chart, index) in statCharts"
      :key="'stat-' + index"
      class="grid-item stat-widget"
      :class="{ 'edit-mode': editView, 'dragging': draggingIndex === charts.indexOf(chart) }"
      :style="getWidgetStyle(chart)"
      :draggable="editView"
      @dragstart="handleDragStart($event, charts.indexOf(chart))"
      @dragend="handleDragEnd"
      @dragover.prevent
      @drop="handleDrop($event, charts.indexOf(chart))"
    >
      <div class="widget-card stat-card">
        <div class="drag-handle" v-if="editView">
          <ion-icon icon="move-outline"></ion-icon>
        </div>
        <div class="widget-content">
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
        </div>
        <button v-if="editView" @click="deleteChart(charts.indexOf(chart))" class="delete-btn">
          <ion-icon icon="trash-outline"></ion-icon>
        </button>
        <div v-if="editView" class="resize-controls">
          <button @click="resizeWidget(charts.indexOf(chart), 'smaller')" class="resize-btn">
            <ion-icon icon="contract-outline"></ion-icon>
          </button>
          <button @click="resizeWidget(charts.indexOf(chart), 'larger')" class="resize-btn">
            <ion-icon icon="expand-outline"></ion-icon>
          </button>
        </div>
      </div>
    </div>

    <!-- Medium Charts (8x4 grid cells - Pie, Donut) -->
    <div
      v-for="(chart, index) in smallCharts"
      :key="'small-' + index"
      class="grid-item medium-widget"
      :class="{ 'edit-mode': editView, 'dragging': draggingIndex === charts.indexOf(chart) }"
      :style="getWidgetStyle(chart)"
      :draggable="editView"
      @dragstart="handleDragStart($event, charts.indexOf(chart))"
      @dragend="handleDragEnd"
      @dragover.prevent
      @drop="handleDrop($event, charts.indexOf(chart))"
    >
      <div class="widget-card">
        <div class="drag-handle" v-if="editView">
          <ion-icon icon="move-outline"></ion-icon>
        </div>
        <div class="widget-header" v-if="chart.title">
          <h3 class="widget-title">{{ chart.title }}</h3>
        </div>
        <div class="widget-content chart-content">
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
        </div>
        <button v-if="editView" @click="deleteChart(charts.indexOf(chart))" class="delete-btn">
          <ion-icon icon="trash-outline"></ion-icon>
        </button>
        <div v-if="editView" class="resize-controls">
          <button @click="resizeWidget(charts.indexOf(chart), 'smaller')" class="resize-btn">
            <ion-icon icon="contract-outline"></ion-icon>
          </button>
          <button @click="resizeWidget(charts.indexOf(chart), 'larger')" class="resize-btn">
            <ion-icon icon="expand-outline"></ion-icon>
          </button>
        </div>
      </div>
    </div>

    <!-- Large Charts (16x6 grid cells - Bar, Line) -->
    <div
      v-for="(chart, index) in largeCharts"
      :key="'large-' + index"
      class="grid-item large-widget"
      :class="{ 'edit-mode': editView, 'dragging': draggingIndex === charts.indexOf(chart) }"
      :style="getWidgetStyle(chart)"
      :draggable="editView"
      @dragstart="handleDragStart($event, charts.indexOf(chart))"
      @dragend="handleDragEnd"
      @dragover.prevent
      @drop="handleDrop($event, charts.indexOf(chart))"
    >
      <div class="widget-card">
        <div class="drag-handle" v-if="editView">
          <ion-icon icon="move-outline"></ion-icon>
        </div>
        <div class="widget-header" v-if="chart.title">
          <h3 class="widget-title">{{ chart.title }}</h3>
        </div>
        <div class="widget-content chart-content">
          <BarChart :data="chart.data" :options="options" />
        </div>
        <button v-if="editView" @click="deleteChart(charts.indexOf(chart))" class="delete-btn">
          <ion-icon icon="trash-outline"></ion-icon>
        </button>
        <div v-if="editView" class="resize-controls">
          <button @click="resizeWidget(charts.indexOf(chart), 'smaller')" class="resize-btn">
            <ion-icon icon="contract-outline"></ion-icon>
          </button>
          <button @click="resizeWidget(charts.indexOf(chart), 'larger')" class="resize-btn">
            <ion-icon icon="expand-outline"></ion-icon>
          </button>
        </div>
      </div>
    </div>
  </div>
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
  emits: ["deleteChart", "updateCharts"],
  data() {
    return {
      draggingIndex: null,
      dragOverIndex: null,
    };
  },
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
    },
    
    // Drag & Drop Methods
    handleDragStart(event, index) {
      this.draggingIndex = index;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/html', event.target.innerHTML);
      
      // Add ghost image
      const ghost = event.target.cloneNode(true);
      ghost.style.opacity = '0.5';
      event.dataTransfer.setDragImage(ghost, 0, 0);
    },
    
    handleDragEnd() {
      this.draggingIndex = null;
      this.dragOverIndex = null;
    },
    
    handleDrop(event, dropIndex) {
      event.preventDefault();
      
      if (this.draggingIndex === null || this.draggingIndex === dropIndex) {
        return;
      }
      
      // Reorder charts array
      const newCharts = [...this.charts];
      const draggedItem = newCharts[this.draggingIndex];
      
      // Remove from old position
      newCharts.splice(this.draggingIndex, 1);
      
      // Insert at new position
      const adjustedDropIndex = this.draggingIndex < dropIndex ? dropIndex - 1 : dropIndex;
      newCharts.splice(adjustedDropIndex, 0, draggedItem);
      
      // Emit update to parent
      this.$emit('updateCharts', newCharts);
      
      this.draggingIndex = null;
      this.dragOverIndex = null;
    },
    
    // Resize Methods
    resizeWidget(index, direction) {
      const chart = this.charts[index];
      
      // Initialize size if not set
      if (!chart.gridSize) {
        chart.gridSize = this.getDefaultSize(chart.chart_type);
      }
      
      const currentSize = chart.gridSize;
      const newSize = { ...currentSize };
      
      if (direction === 'larger') {
        // Increase by 2 columns and 1 row
        newSize.cols = Math.min(currentSize.cols + 2, 16);
        newSize.rows = Math.min(currentSize.rows + 1, 10);
      } else if (direction === 'smaller') {
        // Decrease by 2 columns and 1 row
        newSize.cols = Math.max(currentSize.cols - 2, 4);
        newSize.rows = Math.max(currentSize.rows - 1, 2);
      }
      
      // Update chart with new size
      const newCharts = [...this.charts];
      newCharts[index] = {
        ...chart,
        gridSize: newSize
      };
      
      this.$emit('updateCharts', newCharts);
    },
    
    getDefaultSize(chartType) {
      const sizes = {
        'stat': { cols: 4, rows: 2 },
        'pie_chart': { cols: 8, rows: 4 },
        'donut_chart': { cols: 8, rows: 4 },
        'card': { cols: 8, rows: 4 },
        'bar_chart': { cols: 16, rows: 6 },
        'date_bar_chart': { cols: 16, rows: 6 },
      };
      return sizes[chartType] || { cols: 8, rows: 4 };
    },
    
    getWidgetStyle(chart) {
      if (!chart.gridSize) {
        return {};
      }
      
      return {
        gridColumn: `span ${chart.gridSize.cols}`,
        gridRow: `span ${chart.gridSize.rows}`,
        minHeight: `${chart.gridSize.rows * 90}px`, // 90px per row
      };
    }
  },
});
</script>

<style scoped>
/* Grid System - 16 columns, auto rows */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(16, 1fr);
  gap: 20px;
  padding: 0;
  width: 100%;
  min-height: 400px;
}

/* Widget Sizes - Fixed Proportions */
.grid-item {
  position: relative;
  transition: all 0.3s ease;
}

/* Stat Widget: 4 columns × 2 rows (small info card) */
.stat-widget {
  grid-column: span 4;
  grid-row: span 2;
  min-height: 180px;
}

/* Medium Widget: 8 columns × 4 rows (pie/donut charts) */
.medium-widget {
  grid-column: span 8;
  grid-row: span 4;
  min-height: 360px;
}

/* Large Widget: 16 columns × 6 rows (bar/line charts) */
.large-widget {
  grid-column: span 16;
  grid-row: span 6;
  min-height: 420px;
}

/* Edit Mode - Add drag indicator */
.grid-item.edit-mode {
  cursor: move;
}

.grid-item.edit-mode::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  opacity: 0.5;
  z-index: 1;
  pointer-events: none;
}

.grid-item.dragging {
  opacity: 0.5;
  transform: scale(0.95);
}

/* Drag Handle */
.drag-handle {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 8px;
  color: #3b82f6;
  cursor: grab;
  transition: all 0.2s ease;
}

.drag-handle:hover {
  background: rgba(59, 130, 246, 0.2);
  transform: scale(1.1);
}

.drag-handle:active {
  cursor: grabbing;
}

.drag-handle ion-icon {
  font-size: 20px;
}

/* Widget Card - Modern Design */
.widget-card {
  background: var(--ion-background-color, #ffffff);
  border: 1px solid var(--ion-color-step-150, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  height: 100%;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.widget-card:hover {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  transform: translateY(-2px);
}

/* Widget Header */
.widget-header {
  padding: 20px 20px 16px;
  border-bottom: 1px solid var(--ion-color-step-150, #e2e8f0);
}

.widget-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--ion-text-color, #1e293b);
}

/* Widget Content */
.widget-content {
  flex: 1;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
}

.chart-content {
  padding: 20px;
  justify-content: center;
  align-items: center;
}

/* Stat Card Specific Styling */
.stat-card {
  background: linear-gradient(135deg, var(--ion-background-color, #ffffff) 0%, var(--ion-color-step-50, #f8fafc) 100%);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--ion-color-primary), var(--ion-color-secondary));
  z-index: 1;
}

.stat-card .widget-content {
  padding: 24px;
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
  opacity: 0.9;
}

.stat-title {
  font-size: 12px;
  font-weight: 600;
  color: var(--ion-color-medium);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--ion-text-color, #1e293b);
  margin-bottom: 8px;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.stat-label {
  font-size: 13px;
  color: var(--ion-color-medium);
  margin-bottom: 12px;
  font-weight: 500;
}

.stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.3px;
  align-self: flex-start;
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
  font-size: 16px;
}

/* Delete Button */
.delete-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 10;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  opacity: 0;
  pointer-events: none;
}

.grid-item.edit-mode .delete-btn {
  opacity: 1;
  pointer-events: auto;
}

.delete-btn:hover {
  background: #dc2626;
  color: white;
  transform: scale(1.1);
}

.delete-btn ion-icon {
  font-size: 18px;
}

/* Resize Controls */
.resize-controls {
  position: absolute;
  bottom: 12px;
  right: 12px;
  z-index: 20;
  display: flex;
  gap: 8px;
  opacity: 0;
  pointer-events: none;
  transition: all 0.2s ease;
}

.grid-item.edit-mode .resize-controls {
  opacity: 1;
  pointer-events: auto;
}

.resize-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.resize-btn:hover {
  background: #3b82f6;
  color: white;
  transform: scale(1.1);
}

.resize-btn ion-icon {
  font-size: 18px;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .widget-card {
    background: var(--ion-background-color, #1e293b);
    border-color: var(--ion-color-step-250, #334155);
  }

  .stat-card {
    background: linear-gradient(135deg, var(--ion-background-color, #1e293b) 0%, #0f172a 100%);
  }

  .widget-title,
  .stat-value {
    color: var(--ion-text-color, #f1f5f9);
  }

  .widget-header {
    border-bottom-color: var(--ion-color-step-250, #334155);
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

/* Responsive Design - Adjust grid for smaller screens */
@media (max-width: 1400px) {
  /* On medium screens: reduce to 12 columns */
  .dashboard-grid {
    grid-template-columns: repeat(12, 1fr);
  }

  .stat-widget {
    grid-column: span 3;
  }

  .medium-widget {
    grid-column: span 6;
  }

  .large-widget {
    grid-column: span 12;
  }
}

@media (max-width: 1024px) {
  /* On tablets: reduce to 8 columns */
  .dashboard-grid {
    grid-template-columns: repeat(8, 1fr);
    gap: 16px;
  }

  .stat-widget {
    grid-column: span 4;
  }

  .medium-widget {
    grid-column: span 8;
  }

  .large-widget {
    grid-column: span 8;
  }

  .stat-value {
    font-size: 2.25rem;
  }

  .stat-icon {
    font-size: 24px;
  }
}

@media (max-width: 768px) {
  /* On mobile: single column (4 columns grid) */
  .dashboard-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
  }

  .stat-widget,
  .medium-widget,
  .large-widget {
    grid-column: span 4;
  }

  .stat-widget {
    min-height: 160px;
  }

  .medium-widget {
    min-height: 320px;
  }

  .large-widget {
    min-height: 360px;
  }

  .stat-value {
    font-size: 2rem;
  }

  .stat-header {
    gap: 10px;
  }

  .stat-title {
    font-size: 11px;
  }

  .widget-content {
    padding: 20px;
  }

  .stat-card .widget-content {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .stat-value {
    font-size: 1.75rem;
  }

  .widget-header,
  .widget-content,
  .chart-content {
    padding: 16px;
  }

  .stat-card .widget-content {
    padding: 16px;
  }
}
</style>
