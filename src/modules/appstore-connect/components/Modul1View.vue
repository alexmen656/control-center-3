<template>
  <ion-page class="ion-padding">
    <ion-content>
      <h2>App Store Downloads Dashboard</h2>
      <ion-card>
        <ion-card-header>
          <ion-card-title>Statistiken</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div v-if="loading">Lade Daten...</div>
          <div v-else-if="error">Fehler: {{ error }}</div>
          <div v-else>
            <ul>
              <li>Gesamt-Downloads: <b>{{ stats.total }}</b></li>
              <li>Durchschnitt pro Eintrag: <b>{{ stats.average }}</b></li>
              <li>Top-Tag: <b>{{ stats.topDay }}</b> ({{ stats.topCount }} Downloads)</li>
            </ul>
            <canvas ref="downloadsChart" height="120"></canvas>
            <h3 style="margin-top:2em">Download-Tabelle</h3>
            <table style="width:100%;border-collapse:collapse">
              <thead>
                <tr>
                  <th>Datum</th>
                  <th>Anzahl</th>
                  <th>Version</th>
                  <th>Land</th>
                  <th>Plattform</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in downloads" :key="idx">
                  <td>{{ item.date }}</td>
                  <td>{{ item.count }}</td>
                  <td>{{ item.version }}</td>
                  <td>{{ item.country }}</td>
                  <td>{{ item.platform }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>
<script>
import { Chart, LineController, LineElement, PointElement, LinearScale, Title, CategoryScale } from 'chart.js';
Chart.register(LineController, LineElement, PointElement, LinearScale, Title, CategoryScale);
export default {
  name: 'ModulView',
  data() {
    return {
      downloads: [],
      stats: {},
      loading: true,
      error: null,
      chart: null
    };
  },
  mounted() {
    this.loadDownloads();
  },
  methods: {
    async loadDownloads() {
      this.loading = true;
      this.error = null;
      try {
        const res = await this.$axios.get('appstore_downloads.php');
        this.downloads = res.data.downloads || [];
        this.stats = res.data.stats || {};
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
        // Chart erst nach DOM-Update rendern
        this.$nextTick(() => {
          if (this.$refs.downloadsChart) {
            this.renderChart();
          } else {
            console.warn('Canvas-Ref im nextTick nicht gefunden!');
          }
        });
      }
    },
    renderChart() {
      if (this.chart) {
        this.chart.destroy();
      }
      console.log('downloads:', this.downloads);
      const ctx = this.$refs.downloadsChart;
      console.log('Canvas (ref):', ctx);
      if (!ctx) {
        console.warn('Canvas-Ref nicht gefunden!');
        return;
      }
      if (!this.downloads || this.downloads.length === 0) {
        console.warn('Keine Download-Daten vorhanden!');
        return;
      }
      this.chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: this.downloads.map(d => d.date),
          datasets: [{
            label: 'Downloads',
            data: this.downloads.map(d => d.count),
            borderColor: '#3880ff',
            backgroundColor: 'rgba(56,128,255,0.1)',
            fill: true
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true }
          }
        }
      });
    }
  }
};
</script>
<style scoped>
ion-card { margin-top: 20px; }
table, th, td { border: 1px solid #ccc; padding: 4px; }
th { background: #f4f4f4; }
</style>