<template>
  <ion-page>
    <ion-content>
      <!--<ion-button id="open-modal" expand="block">Open</ion-button>-->
      <ion-button expand="block" @click="edit()">Edit</ion-button>

      <p>{{ message }}</p>

     

      <ion-grid>
        <ion-row>
          <!-- <ion-col size="12" size-lg="12" size-xl="9">
        <ion-grid>
          <ion-row>
            <ion-col size="12"
              ><ion-card><BarChart></BarChart></ion-card
            ></ion-col>
            <ion-col size="12"
              ><TableCard :labels="labels" :data="tableData"></TableCard
            ></ion-col>
          </ion-row>
        </ion-grid>
      </ion-col>

      <ion-col size="12" size-lg="6" size-xl="3">
        <ion-grid>
          <ion-row>
            <ion-col size="12" size-lg="12" size-xl="12"
              ><DonutChart :data="data" :options="options"></DonutChart
            ></ion-col>
            <ion-col size="12" size-lg="12" size-xl="12"
              ><PieChart :data="data" :options="options"></PieChart
            ></ion-col>
          </ion-row>
        </ion-grid>
      </ion-col>-->
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
            <ion-button v-if="editView" @click="deleteChart(index)"
              >Delete Chart</ion-button
            >
          </ion-col>
        </ion-row>
      </ion-grid>
      <ion-fab slot="fixed" vertical="bottom" horizontal="end">
        <ion-fab-button>
          <ion-icon name="chevron-up-circle"></ion-icon>
        </ion-fab-button>
        <ion-fab-list side="top">
          <ion-fab-button id="open-modal">
            <ion-icon name="add-outline" />
          </ion-fab-button>
          <ion-fab-button @click="edit()">
            <ion-icon name="create-outline" />
          </ion-fab-button>
        </ion-fab-list>
      </ion-fab>
      <ion-modal ref="modal" trigger="open-modal">
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
          <FloatingSelect v-model="form" :select="select" />
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
          <!--<FloatingCheckbox
        v-model="inputValues[index]"
        :label="input.label"
        :defaultVal="defaults_values[input.name]"
        v-if="input.type == 'checkbox'"
      />
      <FloatingInput
        v-model="inputValues[index]"
        :defaultVal="defaults_values[input.name]"
        :label="input.label"
        :placeholder="input.placeholder"
        :type="input.type"
      />-->
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
import {
  IonGrid,
  IonCard,
  IonRow,
  IonCol,
  IonButtons,
  IonButton,
  IonModal,
  IonHeader,
  IonContent,
  IonToolbar,
  IonTitle,
  IonFab,
  IonFabButton,
  IonFabList,
} from "@ionic/vue";
import { defineComponent, ref } from "vue";
import BarChart from "@/components/BarChart.vue";
import TableCard from "@/components/TableCard.vue";
import DonutChart from "@/components/DonutChart.vue";
import PieChart from "@/components/PieChart.vue";
import { OverlayEventDetail } from "@ionic/core/components";
import FloatingInput from "@/components/FloatingInput.vue";
import FloatingSelect from "@/components/FloatingSelect.vue";
import FloatingCheckbox from "@/components/FloatingCheckbox.vue";
import qs from "qs";
import axios from "axios";
import {
  chevronDownCircle,
  chevronForwardCircle,
  chevronUpCircle,
  colorPalette,
  document,
  globe,
} from "ionicons/icons";

Array.prototype.remove = function (index) {
  this.splice(index, 1);
};

export default defineComponent({
  name: "DefaultPage",
  setup() {
    return {
      chevronDownCircle,
      chevronForwardCircle,
      chevronUpCircle,
      colorPalette,
      document,
      globe,
    };
  },
  data() {
    return {
      editView: false,
      charts: [],
      form: "",
      labels: ["ID", "Name"],
      tableData: [
        ["1", "Alex"],
        ["2", "Matej"],
        ["3", "Martin"],
        ["4", "John"],
        ["5", "Michael"],
        ["6", "Elias"],
        ["7", "Johann"],
      ],
      options: {
        responsive: true,
        //maintainAspectRatio: false
      },
      isBookmark: false,
      email: "",
      password: "",
      inputs: [{}],
      inputValues: [],
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
      backgroundColors: [
        "#41B883",
        "#E46651",
        "#00D8FF",
        "#DD1B16",
        "#f06d00",
        "#5865F4",
      ],
    };
  },
  created() {
    axios
      .post(
        "/control-center/form.php",
        qs.stringify({
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

    if (localStorage.getItem("charts")) {
      JSON.parse(localStorage.getItem("charts")).forEach(async (chart) => {
        await axios
          .post(
            "/control-center/form.php",
            qs.stringify({
              get_form_data: "get_form_data",
              form: chart.form,
              project: this.$route.params.project,
            })
          )
          .then(async (res) => {
            const data = [];

            res.data.forEach((element) => {
              data.push(Number(element[chart.data]));
            });

            const labels = [];

            res.data.forEach((element) => {
              labels.push(element[chart.label]);
            });

            const new_chart = {
              chart_type: chart.chart_type,
              data: {
                labels: labels,
                datasets: [
                  {
                    /* backgroundColor: [
                      "#41B883",
                      "#E46651",
                      "#00D8FF",
                      "#DD1B16",
                      "#f06d00",
                      "#5865F4",
                    ],*/
                    label:
                      chart.data.charAt(0).toUpperCase() + chart.data.slice(1),
                    data: data,
                    borderWidth: 0,
                  },
                ],
                canDismiss: false,
                presentingElement: undefined,
              },
            };

            await this.charts.push(new_chart);
            console.log(this.charts);
          });
      });
    }
  },
  mounted() {
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
      this.$refs.modal.$el.dismiss(null, "cancel");
    },
    confirm() {
      this.$refs.modal.$el.dismiss(null, "confirm");
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
        localStorage.setItem("charts", JSON.stringify(chartsData));
      } else {
        localStorage.setItem("charts", JSON.stringify([json]));
      }

      console.log(json);
      this.chart_type = "";
      this.form = "";
      this.form_label = "";
      this.form_data = "";
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
    deleteChart(index) {
      //[{"chart_type":"pie_chart","form":"cars","label":"brand","data":"sells"},{"chart_type":"donut_chart","form":"cars","label":"brand","data":"sells"}]
      alert(index);
      const charts = JSON.parse(localStorage.getItem("charts"));
      const new_charts = [];
      charts.forEach((element, indexx) => {
        if (indexx != index) {
          alert("index: " + index + ", indexx: " + indexx);
          new_charts.push(element);
        }
      });
      localStorage.setItem("charts", JSON.stringify(new_charts));
    },
  },
  components: {
    //BarChart,
    DonutChart,
    PieChart,
    //TableCard,
    IonGrid,
    IonCol,
    IonRow,
    // IonCard,
    IonButtons,
    IonButton,
    IonModal,
    IonHeader,
    IonContent,
    IonToolbar,
    IonTitle,
    //  FloatingCheckbox,
    // FloatingInput,
    FloatingSelect,
    IonFab,
    IonFabButton,
    IonFabList,
  },
});
</script>
