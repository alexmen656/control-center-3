<template>
  <div class="">
    <!---- <ion-title size="large">{{ title }}<ion-icon @click="toggleBookmark()" :name="isBookmark ? 'star' : 'star-outline'"></ion-icon></ion-title>-->

    <!-- <span v-html="page.html"></span>-->
    <ion-grid>
      <ion-row>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><CardComponent></CardComponent
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><CardComponent></CardComponent
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><CardComponent></CardComponent
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><CardComponent></CardComponent
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><ShortcutCard title="Manage Users" link="/users"></ShortcutCard
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><ShortcutCard title="Statistics" link="/statistics"></ShortcutCard
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><ShortcutCard title="Dashboard" link="/dashboard"></ShortcutCard
        ></ion-col>
        <ion-col size="12" size-lg="6" size-xl="3"
          ><ShortcutCard
            title="Alexs's Blog"
            link="/project/alexs-blog"
          ></ShortcutCard
        ></ion-col>

        <ion-col size="12" size-lg="12" size-xl="9">
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
        </ion-col>
      </ion-row>
    </ion-grid>
  </div>
</template>

<script lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonGrid,
  IonCard,
  IonRow,
  IonCol,
} from "@ionic/vue";
import { defineComponent } from "vue";
import CardComponent from "@/components/CardComponent.vue";
import BarChart from "@/components/BarChart.vue";
import TableCard from "@/components/TableCard.vue";
import DonutChart from "@/components/DonutChart.vue";
import PieChart from "@/components/PieChart.vue";
import ShortcutCard from "@/components/ShortcutCard.vue";
import axios from "axios";
import qs from "qs";

export default defineComponent({
  name: "DefaultPage",
  data() {
    return {
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
      data: {
        labels: ["VueJs", "EmberJs", "ReactJs", "AngularJs"],
        datasets: [
          {
            backgroundColor: ["#41B883", "#E46651", "#00D8FF", "#DD1B16"],
            label: "Verkäufe",
            data: [990, 20, 80, 10],
            borderWidth: [0, 0, 0, 0],
          },
        ],
      },

      options: {
        responsive: true,
        //maintainAspectRatio: false
      },
      isBookmark: false,
    };
  },
  components: {
    CardComponent,
    BarChart,
    DonutChart,
    PieChart,
    TableCard,
    IonGrid,
    // IonHeader,
    //IonToolbar,
    //IonTitle,
    IonCol,
    IonRow,
    IonCard,
    ShortcutCard,
  },
  props: {
    title: String,
  },
  mounted() {
    axios
      .post(
        "https://alex.polan.sk/control-center/bookmarks.php?" +
          qs.stringify({
            location: window.location.href,
            checkBookmark: "checkBookmark",
          })
      )
      .then((response) => {
        this.isBookmark = response.data;
      });
  },
  methods: {
    toggleBookmark() {
      if (this.isBookmark) {
        this.isBookmark = false;
        this.$emit("newData");
        axios.post(
          "https://alex.polan.sk/control-center/bookmarks.php?deleteBookmark=deleteBookmark&location=" +
            window.location.href
        );
      } else {
        this.isBookmark = true;
        this.$emit("newData");
        axios.post(
          "https://alex.polan.sk/control-center/bookmarks.php?newBookmark=newBookmark&title=" +
            this.title +
            "&location=" +
            window.location.href
        );
      }
    },
  },
});
</script>
