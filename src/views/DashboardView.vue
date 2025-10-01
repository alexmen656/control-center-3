<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="stats-chart" title="Dashboard" bg="transparent" />

      <div class="page-container">
        <!-- Action Bar -->
        <div class="action-bar">
          <div class="action-group-left">
            <div class="header-info">
              <h2>{{ dashboardName }}</h2>
              <p>{{ charts.length }} Widgets aktiv</p>
            </div>
          </div>
          <div class="action-group-right">
            <button class="action-btn" @click="edit()">
              <ion-icon :icon="editView ? 'checkmark-outline' : 'create-outline'"></ion-icon>
              {{ editView ? 'Fertig' : 'Bearbeiten' }}
            </button>
            <button class="action-btn primary" @click="setOpen(true)">
              <ion-icon icon="add-outline"></ion-icon>
              Widget hinzufügen
            </button>
          </div>
        </div>

        <!-- Dashboard Display -->
        <DashboardDisplay :charts="charts" :editView="editView" @deleteChart="deleteChart"
          @updateCharts="updateCharts" />

        <!-- Empty State -->
        <div v-if="charts.length === 0" class="no-data-state">
          <div class="no-data-content">
            <ion-icon icon="stats-chart-outline" class="no-data-icon"></ion-icon>
            <h4>Noch keine Widgets</h4>
            <p>Füge dein erstes Widget hinzu, um mit der Dashboard-Visualisierung zu beginnen.</p>
            <button class="action-btn primary" @click="setOpen(true)">
              <ion-icon icon="add-outline"></ion-icon>
              Widget hinzufügen
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Overlay -->
      <div v-if="isOpen" class="custom-modal-overlay" @click="cancel()">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Neues Widget erstellen</h3>
            <button class="modal-close-btn" @click="cancel()">
              <ion-icon icon="close-outline"></ion-icon>
            </button>
          </div>

          <div class="custom-modal-body">
            <div class="form-group">
              <label class="form-label">Widget Typ</label>
              <select v-model="comp" class="modern-select">
                <option value="">Typ auswählen</option>
                <option value="module_widget">Module Widget</option>
                <option value="chart">Form Chart</option>
                <option value="card">Card</option>
              </select>
            </div>

            <!-- Module Widget Selects -->
            <div v-if="comp === 'module_widget'" class="widget-config">
              <div class="form-group">
                <label class="form-label">Modul</label>
                <select v-model="module" class="modern-select">
                  <option value="">Modul auswählen</option>
                  <option v-for="opt in module_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="module" class="form-group">
                <label class="form-label">Widget</label>
                <select v-model="widget" class="modern-select">
                  <option value="">Widget auswählen</option>
                  <option v-for="opt in widget_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Card Selects -->
            <div v-if="comp === 'card'" class="widget-config">
              <div class="form-group">
                <label class="form-label">View</label>
                <select v-model="view" class="modern-select">
                  <option value="">View auswählen</option>
                  <option v-for="opt in view_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Chart Selects -->
            <div v-if="comp === 'chart'" class="widget-config">
              <div class="form-group">
                <label class="form-label">Chart Typ</label>
                <select v-model="chart_type" class="modern-select">
                  <option value="">Chart Typ auswählen</option>
                  <option v-for="opt in chart_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="chart_type" class="form-group">
                <label class="form-label">Form</label>
                <select v-model="form" class="modern-select">
                  <option value="">Form auswählen</option>
                  <option v-for="opt in select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="form" class="form-group">
                <label class="form-label">Datenfeld</label>
                <select v-model="form_data" class="modern-select">
                  <option value="">Datenfeld auswählen</option>
                  <option v-for="opt in data_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="form_data" class="form-group">
                <label class="form-label">Label</label>
                <select v-model="form_label" class="modern-select">
                  <option value="">Label auswählen</option>
                  <option v-for="opt in label_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="form_label && chart_type == 'date_bar_chart'" class="form-group">
                <label class="form-label">Zeitstempel</label>
                <select v-model="date_stamps" class="modern-select">
                  <option value="">Zeitstempel auswählen</option>
                  <option v-for="opt in date_stamps_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div v-if="date_stamps && chart_type == 'date_bar_chart'" class="form-group">
                <label class="form-label">Methode</label>
                <select v-model="date_bar_method" class="modern-select">
                  <option value="">Methode auswählen</option>
                  <option v-for="opt in date_bar_method_select.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button class="action-btn" @click="cancel()">Abbrechen</button>
            <button class="action-btn primary" @click="confirm()">Widget hinzufügen</button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, watch } from "vue";
import DashboardDisplay from "@/components/DashboardDisplay.vue";
import SiteTitle from "@/components/SiteTitle.vue";
import {
  useMagicKeys,
  //whenever
} from "@vueuse/core";
import { dashboardRegistry } from "@/core/registry/DashboardRegistry";

export default defineComponent({
  name: "DefaultPage",
  components: {
    DashboardDisplay,
    SiteTitle,
  },
  data() {
    return {
      editView: false,
      charts: [],
      form: "",
      view: "",
      comp: "",
      module: "",
      widget: "",
      options: {
        responsive: true,
      },
      isBookmark: false,
      email: "",
      password: "",
      inputs: [{}],
      inputValues: [],
      comp_select: {
        type: "select",
        name: "comp",
        label: "Component",
        placeholder: "Chart",
        options: [
          { value: "card", label: "Card" },
          { value: "chart", label: "Chart" },
          { value: "module_widget", label: "Module Widget" },
        ],
      },
      module_select: {
        type: "select",
        name: "module",
        label: "Module",
        placeholder: "Select Module",
        options: [],
      },
      widget_select: {
        type: "select",
        name: "widget",
        label: "Widget",
        placeholder: "Select Widget",
        options: [],
      },
      view_select: {
        type: "select",
        name: "view",
        label: "View",
        placeholder: "Overview",
        options: [],
      },
      select: {
        type: "select",
        name: "form",
        label: "Form",
        placeholder: "Form",
        options: [],
      },
      data_select: {
        type: "select",
        name: "data",
        label: "Data",
        placeholder: "Data",
        options: [],
      },
      label_select: {
        type: "select",
        name: "label",
        label: "Label",
        placeholder: "Label",
        options: [],
      },
      chart_select: {
        type: "select",
        name: "chart_type",
        label: "Chart",
        placeholder: "Chart",
        options: [
          {
            value: "pie_chart",
            label: "Pie-Chart",
          },
          {
            value: "donut_chart",
            label: "Donut-Chart",
          },
          {
            value: "bar_chart",
            label: "Bar-Chart",
          },
          {
            value: "date_bar_chart",
            label: "Date-Bar-Chart",
          },
        ],
      },
      date_stamps_select: {
        type: "select",
        name: "date_stamps",
        label: "Date Stamps",
        placeholder: "Date Stamps",
        options: [
          {
            value: "hours",
            label: "Hours",
          },
          {
            value: "days",
            label: "Days",
          },
          {
            value: "weeks",
            label: "Weeks",
          },
          {
            value: "months",
            label: "Months",
          },
          //quartals comming maybe later
          {
            value: "years",
            label: "Years",
          },
        ],
      },
      date_bar_method_select: {
        type: "select",
        name: "date_bar_method",
        label: "Method",
        placeholder: "Method",
        options: [
          {
            value: "count",
            label: "Count",
          },
        ],
      },
      form_data: "",
      form_label: "",

      forms: [],
      ccharts: [{ labels: [] }],
      chart_type: "",
    };
  },
  computed: {
    dashboardName() {
      return this.$route.params.dashboard || 'Dashboard';
    }
  },
  created() {
    // Load available modules for the selector
    this.loadModuleOptions();

    this.$axios
      .post(
        "form.php",
        this.$qs.stringify({
          get_forms: "get_forms",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.forms = res.data;
        res.data.forEach((form) => {
          this.select.options.push({
            value: this.toName(form.form.title),
            label: form.form.title,
          });
        });
      });
    this.$axios
      .post(
        "projects.php",
        this.$qs.stringify({
          getProjectViews: "getProjectViews",
          project: this.$route.params.project,
        })
      )
      .then((res) => {
        this.views = res.data;
        res.data.forEach((view) => {
          this.view_select.options.push({
            value: view.id,
            label: view.name,
          });
        });
      });
    // Immer Charts laden, nicht nur wenn localStorage existiert
    this.loadCharts();
  },
  setup() {
    const isOpen = ref(false);
    const setOpen = (open) => {
      isOpen.value = open;
      console.log(1);
    };

    return {
      isOpen,
      setOpen,
    };
  },
  mounted() {
    const keys = useMagicKeys();
    const neww = keys["Ctrl+N"];
    const edit = keys["E+Ctrl"];
    // const editWindows = keys["E+Ctrl"];
    //const editMac = keys["E+Command"];

    watch(neww, async (v) => {
      if (v)
        if (this.isOpen) {
          //document.getElementById("open_menu").click();
          this.setOpen(false);
        } else {
          this.setOpen(true);
        }
    });

    watch(edit, (v) => {
      if (v) this.edit();
    });
    /* watch(editWindows, (v) => {
      if (v) this.edit();
    });

    watch(editMac, (v) => {
      if (v) this.edit();
    });*/

    this.$watch(
      () => this.form,
      () => {
        const options = [];
        this.forms.forEach((form) => {
          if (this.toName(form.form.title) == this.form) {
            form.form.inputs.forEach((element) => {
              options.push({ value: element.name, label: element.label });
            });
          }
        });
        this.data_select.options = options;
        this.label_select.options = options;
      }
    );

    // Watch module selection to load widgets
    this.$watch(
      () => this.module,
      () => {
        if (this.module) {
          const provider = dashboardRegistry.getProvider(this.module);
          if (provider) {
            this.widget_select.options = provider.widgets.map(widget => ({
              value: widget.id,
              label: widget.title
            }));
          }
        }
      }
    );
  },
  methods: {
    loadModuleOptions() {
      const providers = dashboardRegistry.getAllProviders();
      this.module_select.options = providers.map(module => ({
        value: module.moduleId,
        label: module.moduleName
      }));
    },

    cancel() {
      // this.$refs.modal.$el.dismiss(null, "cancel");
      this.setOpen(false);
    },
    async confirm() {
      this.setOpen(false);

      if (this.comp === "module_widget") {
        // Handle module widget
        if (!this.module || !this.widget) {
          alert("Bitte wähle ein Modul und ein Widget aus");
          return;
        }

        const widgetConfig = {
          chart_type: "module_widget",
          module: this.module,
          widget: this.widget,
        };

        let chartsData = localStorage.getItem("charts");
        if (chartsData) {
          chartsData = JSON.parse(chartsData);
          chartsData.push(widgetConfig);
          await localStorage.setItem("charts", JSON.stringify(chartsData));
        } else {
          await localStorage.setItem("charts", JSON.stringify([widgetConfig]));
        }

        await this.$axios.post(
          "dashboard.php",
          this.$qs.stringify({
            new_chart: "new_chart",
            json: JSON.stringify([widgetConfig]),
            dashboard: this.$route.params.dashboard,
            project: this.$route.params.project,
          })
        );

        this.loadCharts();

        this.module = "";
        this.widget = "";
      } else if (this.comp === "chart") {
        let json = {};
        if (this.chart_type == "date_bar_chart") {
          console.log("date_bar_chart");
          json = {
            chart_type: this.chart_type,
            form: this.form,
            label: this.form_label,
            data: this.form_data,
            date_stamps: this.date_stamps,
            method: this.date_bar_method,
          };
        } else {
          //this.$refs.modal.$el.dismiss(null, "confirm");
          json = {
            chart_type: this.chart_type,
            form: this.form,
            label: this.form_label,
            data: this.form_data,
          };
        }
        let chartsData = localStorage.getItem("charts");
        if (chartsData) {
          chartsData = JSON.parse(chartsData);
          chartsData.push(json);
          await localStorage.setItem("charts", JSON.stringify(chartsData));
        } else {
          await localStorage.setItem("charts", JSON.stringify([json]));
        }
        await this.$axios.post(
          "dashboard.php",
          this.$qs.stringify({
            new_chart: "new_chart",
            json: JSON.stringify([json]),
            dashboard: this.$route.params.dashboard,
            project: this.$route.params.project,
          })
        );

        this.loadCharts();

        this.chart_type = "";
        this.form = "";
        this.form_label = "";
        this.form_data = "";
      } else if (this.comp === "card") {
        const viewObject = this.views.find((view) => view.id === this.view);
        if (viewObject) {
          viewObject.chart_type = "card";
          let cardsData = localStorage.getItem("charts");
          if (cardsData) {
            cardsData = JSON.parse(cardsData);
            cardsData.push(viewObject);
            await localStorage.setItem("charts", JSON.stringify(cardsData));
          } else {
            await localStorage.setItem("charts", JSON.stringify([viewObject]));
          }
          await this.$axios.post(
            "dashboard.php",
            this.$qs.stringify({
              new_chart: "new_chart",
              json: JSON.stringify([viewObject]),
              dashboard: this.$route.params.dashboard,
              project: this.$route.params.project,
            })
          );
          this.loadCharts();
        }
      }
    },

    toName(name) {
      return name.replaceAll(" ", "_").toLowerCase();
    },
    edit() {
      if (this.editView == true) {
        this.editView = false;
      } else {
        this.editView = true;
      }
    },
    async deleteChart(index) {
      /*  const charts = JSON.parse(localStorage.getItem("charts"));
      const new_charts = [];
      charts.forEach((element, indexx) => {
        if (indexx != index) {
          //alert("index: " + index + ", indexx: " + indexx);
          new_charts.push(element);
        }
      });
      await localStorage.setItem("charts", JSON.stringify(new_charts));*/
      await this.$axios.post(
        "dashboard.php",
        this.$qs.stringify({
          delete_chart: "delete_chart",
          dashboard: this.$route.params.dashboard,
          project: this.$route.params.project,
          chart_index: index,
        })
      );
      this.loadCharts();
    },
    async updateCharts(newCharts) {
      // Update local charts array
      this.charts = newCharts;

      // Save to backend
      await this.$axios.post(
        "dashboard.php",
        this.$qs.stringify({
          update_dashboard: "update_dashboard",
          dashboard: this.$route.params.dashboard,
          project: this.$route.params.project,
          charts: JSON.stringify(newCharts),
        })
      );
    },
    async loadCharts() {
      this.charts = [];
      //JSON.parse(localStorage.getItem("charts"))
      const request = await this.$axios.post(
        "dashboard.php",
        this.$qs.stringify({
          get_dashboard: "get_dashboard",
          dashboard: this.$route.params.dashboard,
          project: this.$route.params.project,
        })
      );
      request.data.forEach(async (chart) => {
        // Handle module widgets
        if (chart.chart_type === "module_widget") {
          try {
            const widget = dashboardRegistry.getWidget(chart.module, chart.widget);

            if (widget) {
              const widgetData = await widget.getData({
                period: 30,
                project: this.$route.params.project
              });

              let new_chart = {};

              if (widget.type === 'stat') {
                // Stat widget
                new_chart = {
                  chart_type: 'stat',
                  title: widget.title,
                  icon: widget.icon,
                  data: widgetData
                };
              } else if (widget.type === 'chart') {
                // Chart widget
                const chartTypeMap = {
                  'pie': 'pie_chart',
                  'donut': 'donut_chart',
                  'bar': 'bar_chart',
                  'line': 'date_bar_chart'
                };

                new_chart = {
                  chart_type: chartTypeMap[widget.config?.chartType] || 'bar_chart',
                  title: widget.title,
                  data: widgetData
                };
              }

              await this.charts.push(new_chart);
            }
          } catch (error) {
            console.error('Error loading module widget:', error);
          }
        }
        else if (
          chart.chart_type == "pie_chart" ||
          chart.chart_type == "donut_chart" ||
          chart.chart_type == "date_bar_chart" ||
          chart.chart_type == "bar_chart"
        ) {
          await this.$axios
            .post(
              "form.php",
              this.$qs.stringify({
                get_form_data: "get_form_data",
                form: chart.form
                  .replaceAll("ä", "a")
                  .replaceAll("ö", "o")
                  .replaceAll("ü", "u"),
                project: this.$route.params.project,
              })
            )
            .then(async (res) => {
              const data = [];
              const labels = [];

              if (chart.chart_type == "date_bar_chart") {
                if (chart.date_stamps == "hours") {
                  const groupedData = {};
                  console.log("res.data.hours", res.data);
                  res.data.forEach((element) => {
                    const dateTime = element.created_at.split(" ");
                    const date = dateTime[0];
                    const hour = dateTime[1].split(":")[0];
                    const dateHour = `${date} ${hour}:00:00`;
                    console.log("created at", element.created_at);
                    if (!groupedData[dateHour]) {
                      groupedData[dateHour] = 0;
                    }
                    console.log("please add", Number(element.anzahl));
                    groupedData[dateHour] += Number(element.anzahl);
                  });
                  console.log("grouped-data", groupedData);
                  const now = new Date();
                  for (let i = 9; i >= 0; i--) {
                    const date = new Date(now);
                    date.setHours(now.getHours() - i + 1); // + 1
                    const dateString =
                      date.toISOString().split(":")[0] + ":00:00";
                    console.log("dateString", dateString);
                    data.push(
                      groupedData[dateString.replace("T", " ")] || 0.00001
                    );
                  }
                  console.log("Hour-Data", data);
                } else if (chart.date_stamps == "days") {
                  const groupedData = {};
                  console.log("res.data", res.data);
                  res.data.forEach((element) => {
                    const date = element.created_at.split(" ")[0]; // Nur das Datum ohne Zeit
                    console.log("created at", element.created_at);
                    if (!groupedData[date]) {
                      groupedData[date] = 0;
                    }
                    console.log("please add", Number(element.anzahl));
                    groupedData[date] += Number(element.anzahl);
                  });
                  console.log("grouped-data", groupedData);
                  const today = new Date();
                  for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(today.getDate() - i);
                    const dateString = date.toISOString().split("T")[0]; // Format: YYYY-MM-DD
                    data.push(groupedData[dateString] || 0.00001);
                  }
                  console.log("Day-Data", data);
                } else if (chart.date_stamps == "weeks") {
                  const groupedData = {};
                  console.log("res.data", res.data);
                  res.data.forEach((element) => {
                    const date = new Date(element.created_at.split(" ")[0]);
                    const weekStart = new Date(
                      date.setDate(date.getDate() - date.getDay())
                    ); // Get the start of the week (Sunday)
                    const weekStartString = weekStart
                      .toISOString()
                      .split("T")[0];
                    console.log("created at", element.created_at);
                    if (!groupedData[weekStartString]) {
                      groupedData[weekStartString] = 0;
                    }
                    console.log("please add", Number(element.anzahl));
                    groupedData[weekStartString] += Number(element.anzahl);
                  });
                  console.log("grouped-data", groupedData);
                  const today = new Date();
                  for (let i = 9; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(today.getDate() - i * 7); // Subtract weeks
                    const weekStart = new Date(
                      date.setDate(date.getDate() - date.getDay())
                    ); // Get the start of the week (Sunday)
                    const weekStartString = weekStart
                      .toISOString()
                      .split("T")[0];
                    data.push(groupedData[weekStartString] || 0.00001);
                  }
                  console.log("Week-Data", data);
                } else if (chart.date_stamps == "months") {
                  const groupedData = {};
                  console.log("res.data", res.data);
                  res.data.forEach((element) => {
                    const date = new Date(element.created_at.split(" ")[0]);
                    const monthStart = new Date(
                      date.getFullYear(),
                      date.getMonth(),
                      1
                    ); // Get the start of the month
                    const monthStartString = monthStart
                      .toISOString()
                      .split("T")[0];
                    console.log("created at", element.created_at);
                    if (!groupedData[monthStartString]) {
                      groupedData[monthStartString] = 0;
                    }
                    console.log("please add", Number(element.anzahl));
                    groupedData[monthStartString] += Number(element.anzahl);
                  });
                  console.log("grouped-data", groupedData);
                  const today = new Date();
                  for (let i = 5; i >= 0; i--) {
                    const date = new Date(today);
                    date.setMonth(today.getMonth() - i); // Subtract months
                    const monthStart = new Date(
                      date.getFullYear(),
                      date.getMonth(),
                      1
                    ); // Get the start of the month
                    const monthStartString = monthStart
                      .toISOString()
                      .split("T")[0];
                    data.push(groupedData[monthStartString] || 0.00001);
                  }
                  console.log("Month-Data", data);
                } else if (chart.date_stamps == "years") {
                  const groupedData = {};
                  console.log("res.data", res.data);
                  res.data.forEach((element) => {
                    const date = new Date(element.created_at.split(" ")[0]);
                    const yearStart = new Date(date.getFullYear(), 0, 1); // Get the start of the year
                    const yearStartString = yearStart
                      .toISOString()
                      .split("T")[0];
                    console.log("created at", element.created_at);
                    if (!groupedData[yearStartString]) {
                      groupedData[yearStartString] = 0;
                    }
                    console.log("please add", Number(element.anzahl));
                    groupedData[yearStartString] += Number(element.anzahl);
                  });
                  console.log("grouped-data", groupedData);
                  const today = new Date();
                  for (let i = 2; i >= 0; i--) {
                    const date = new Date(today);
                    date.setFullYear(today.getFullYear() - i); // Subtract years
                    const yearStart = new Date(date.getFullYear(), 0, 1); // Get the start of the year
                    const yearStartString = yearStart
                      .toISOString()
                      .split("T")[0];
                    //labels.push(date.getFullYear().toString()); // Add the year name to labels
                    data.push(groupedData[yearStartString] || 0.00001); // Add the corresponding data
                  }
                }
              } else {
                res.data.forEach((element) => {
                  data.push(Number(element[chart.data]));
                });
              }

              if (chart.chart_type == "date_bar_chart") {
                if (chart.date_stamps == "hours") {
                  const now = new Date();
                  for (let i = 9; i >= 0; i--) {
                    const date = new Date(now);
                    date.setHours(now.getHours() - i); // + 1
                    const hours = date.getHours().toString().padStart(2, "0");
                    const dateString = `${hours}:00`; // Format: HH:00
                    labels.push(dateString);
                    //data.push(groupedData[dateString] || 0);
                  }
                  console.log("Hour-Data", data);
                } else if (chart.date_stamps == "days") {
                  const today = new Date();
                  for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(today.getDate() - i);
                    labels.push(date.toISOString().split("T")[0]); // Format: YYYY-MM-DD
                  }
                } else if (chart.date_stamps == "weeks") {
                  const today = new Date();
                  for (let i = 9; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(today.getDate() - i * 7); // Subtract weeks
                    const weekStart = new Date(
                      date.setDate(date.getDate() - date.getDay())
                    ); // Get the start of the week (Sunday)
                    labels.push(weekStart.toISOString().split("T")[0]); // Format: YYYY-MM-DD
                  }
                } else if (chart.date_stamps == "months") {
                  const today = new Date();
                  for (let i = 5; i >= 0; i--) {
                    const date = new Date(today);
                    date.setMonth(today.getMonth() - i); // Subtract months
                    const monthName = date.toLocaleString("default", {
                      month: "long",
                    }); // Get the month name
                    labels.push(monthName); // Add the month name to labels
                    /*const monthStart = new Date(
                      date.getFullYear(),
                      date.getMonth(),
                      1
                    );*/ // Get the start of the month
                    // labels.push(monthStart.toISOString().split("T")[0]); // Format: YYYY-MM-DD
                  }
                } else if (chart.date_stamps == "years") {
                  const today = new Date();
                  for (let i = 2; i >= 0; i--) {
                    const date = new Date(today);
                    date.setFullYear(today.getFullYear() - i); // Subtract years
                    const yearName = date.getFullYear().toString(); // Get the year name
                    labels.push(yearName); // Add the year name to labels
                  }
                }
              } else {
                res.data.forEach((element) => {
                  labels.push(element[chart.label]);
                });
              }

              let new_chart = {};
              if (
                chart.chart_type == "date_bar_chart" ||
                chart.chart_type == "bar_chart"
              ) {
                new_chart = {
                  chart_type: chart.chart_type,
                  data: {
                    labels: labels,
                    datasets: [
                      {
                        label:
                          chart.data.charAt(0).toUpperCase() +
                          chart.data.slice(1),
                        data: data,
                        backgroundColor: "#f87979",
                        title: "cvf",
                      },
                    ],
                  },
                };
                console.log("new_chart: ", new_chart);
              } else {
                new_chart = {
                  chart_type: chart.chart_type,
                  data: {
                    labels: labels,
                    datasets: [
                      {
                        label:
                          chart.data.charAt(0).toUpperCase() +
                          chart.data.slice(1),
                        data: data,
                        borderWidth: 0,
                      },
                    ],
                    canDismiss: false,
                    presentingElement: undefined,
                  },
                };
              }
              await this.charts.push(new_chart);
            });
        } else if (chart.chart_type == "card") {
          this.charts.push(chart);
        }
      });
    },
  },
});
</script>

<style scoped>
/* Modern Design System - Same as FormDisplay & ManageUsers */
.modern-content {
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --secondary-color: #64748b;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --info-color: #0891b2;
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
  }
}

ion-content.modern-content {
  --background: var(--background);
}

.page-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

/* Action Bar */
.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
  padding: 0 4px;
}

.action-group-left,
.action-group-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-info h2 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 28px;
  font-weight: 700;
}

.header-info p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn ion-icon {
  font-size: 18px;
}

/* No Data State */
.no-data-state {
  padding: 80px 20px;
  text-align: center;
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  margin: 20px 0;
}

.no-data-content {
  max-width: 500px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 80px;
  color: var(--text-muted);
  margin-bottom: 24px;
  opacity: 0.4;
}

.no-data-content h4 {
  margin: 0 0 12px 0;
  color: var(--text-primary);
  font-size: 24px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 32px 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.6;
}

/* Custom Modal */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  max-width: 90vw;
  max-height: 90vh;
  width: 600px;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease;
  overflow: hidden;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--background);
}

.custom-modal-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--border);
  color: var(--text-primary);
}

.modal-close-btn ion-icon {
  font-size: 24px;
}

.custom-modal-body {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  min-height: 0;
}

/* Form Styling */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modern-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  background: var(--surface);
  color: var(--text-primary);
  transition: all 0.2s ease;
  box-sizing: border-box;
  cursor: pointer;
}

.modern-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
}

.modern-select option {
  padding: 10px;
}

.widget-config {
  background: var(--background);
  padding: 20px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  margin-top: 16px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px;
  border-top: 1px solid var(--border);
  background: var(--background);
}

/* Animations */
@keyframes modalFadeIn {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .action-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .action-group-left,
  .action-group-right {
    justify-content: center;
  }

  .header-info h2 {
    font-size: 24px;
  }

  .custom-modal-content {
    width: 95vw;
    max-width: none;
    margin: 20px;
  }

  .custom-modal-header,
  .custom-modal-body,
  .form-actions {
    padding: 20px;
  }

  .no-data-icon {
    font-size: 64px;
  }

  .no-data-content h4 {
    font-size: 20px;
  }

  .no-data-content p {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .action-btn {
    padding: 8px 14px;
    font-size: 13px;
  }

  .action-btn ion-icon {
    font-size: 16px;
  }
}
</style>
