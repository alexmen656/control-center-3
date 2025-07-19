<template>
  <ion-page>
    <ion-content>
      <DashboardDisplay
        :charts="charts"
        :editView="editView"
        @deleteChart="deleteChart"
      />
      <ion-fab slot="fixed" vertical="bottom" horizontal="end">
        <ion-fab-button id="open_menu">
          <ion-icon name="chevron-up-circle"></ion-icon>
        </ion-fab-button>
        <ion-fab-list side="top">
          <ion-fab-button @click="setOpen(true)">
            <ion-icon name="add-outline" />
          </ion-fab-button>
          <ion-fab-button @click="edit()">
            <ion-icon name="create-outline" />
          </ion-fab-button>
        </ion-fab-list>
      </ion-fab>
      <ion-modal :is-open="isOpen" ref="modal">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button color="primary" @click="cancel()">Cancel</ion-button>
            </ion-buttons>
            <ion-title style="text-align: center">Create new Chart</ion-title>
            <ion-buttons slot="end">
              <ion-button color="primary" :strong="true" @click="confirm()"
                >Confirm</ion-button
              >
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <FloatingSelect v-model="comp" :select="comp_select" />

          <!-- Card Selects s-->
          <FloatingSelect
            v-if="comp === 'card'"
            v-model="view"
            :select="view_select"
          />

          <!-- Chart Selects-->
          <FloatingSelect
            v-if="comp === 'chart'"
            v-model="chart_type"
            :select="chart_select"
          />
          <FloatingSelect v-if="chart_type" v-model="form" :select="select" />
          <FloatingSelect
            v-if="form"
            v-model="form_data"
            :select="data_select"
          />
          <FloatingSelect
            v-if="form_data"
            v-model="form_label"
            :select="label_select"
          />
          <FloatingSelect
            v-if="form_label && chart_type == 'date_bar_chart'"
            v-model="date_stamps"
            :select="date_stamps_select"
          />
          <FloatingSelect
            v-if="date_stamps && chart_type == 'date_bar_chart'"
            v-model="date_bar_method"
            :select="date_bar_method_select"
          />

          <!-- Date-Bar Chart-->
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, watch } from "vue";
import DashboardDisplay from "@/components/DashboardDisplay.vue";
//import { OverlayEventDetail } from "@ionic/core/components";
import FloatingSelect from "@/components/FloatingSelect.vue";
import {
  useMagicKeys,
  //whenever
} from "@vueuse/core";

export default defineComponent({
  name: "DefaultPage",
  components: {
    FloatingSelect,
    DashboardDisplay,
  },
  data() {
    return {
      editView: false,
      charts: [],
      form: "",
      view: "",
      comp: "",
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
        ],
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
  created() {
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
  },
  methods: {
    cancel() {
      // this.$refs.modal.$el.dismiss(null, "cancel");
      this.setOpen(false);
    },
    async confirm() {
      this.setOpen(false);

      if (this.comp === "chart") {
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
        if (
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
@media (prefers-color-scheme: dark) {
  ion-fab-button {
    --background: var(--ion-color-primary);
  }

  ion-fab-button > ion-icon {
    color: white;
  }
}
</style>
