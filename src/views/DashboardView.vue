<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col
            v-for="(chart, index) in charts"
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
        </ion-row>
      </ion-grid>
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
          <FloatingSelect
            v-if="comp === 'card'"
            v-model="view"
            :select="view_select"
          />
          <FloatingSelect
            v-if="comp === 'chart'"
            v-model="form"
            :select="select"
          />
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
            v-if="form_label"
            v-model="chart_type"
            :select="chart_select"
          />
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, watch } from "vue";
import DonutChart from "@/components/DonutChart.vue";
import PieChart from "@/components/PieChart.vue";
import ShortcutCard from "@/components/ShortcutCard.vue";

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
    DonutChart,
    PieChart,
    ShortcutCard,
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
        ],
      },
      form_data: "",
      form_label: "",

      forms: [],
      ccharts: [{ labels: [] }],
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
    if (localStorage.getItem("charts")) {
      this.loadCharts();
    }
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
        //this.$refs.modal.$el.dismiss(null, "confirm");
        const json = {
          chart_type: this.chart_type,
          form: this.form,
          label: this.form_label,
          data: this.form_data,
        };

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
          chart.chart_type == "donut_chart"
        ) {
          await this.$axios
            .post(
              "form.php",
              this.$qs.stringify({
                get_form_data: "get_form_data",
                form: chart.form,
                project: this.$route.params.project,
              })
            )
            .then(async (res) => {
              const data = [];
              const labels = [];

              res.data.forEach((element) => {
                data.push(Number(element[chart.data]));
              });

              res.data.forEach((element) => {
                labels.push(element[chart.label]);
              });

              const new_chart = {
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
