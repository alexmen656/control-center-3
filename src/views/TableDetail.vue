<template>
  <ion-page>
    <ion-content>
      <TableCard :labels="labels" :data="data" />
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, getCurrentInstance } from "vue";
import TableCard from "@/components/TableCard.vue";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "DatabasesView",
  components: {
    TableCard,
  },
  setup() {
    const labels = ref([]);
    const data = ref([]);
    const route = useRoute();
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    axios
      .post(
        "mysql.php",
        qs.stringify({ getTableByName: route.params.name })
      )
      .then((res) => {
        labels.value = res.data.labels;
        data.value = res.data.data;
      });

    return {
      labels: labels,
      data: data,
    };
  },
});
</script>
