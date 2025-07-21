<template>
  <ion-page>
    <ion-content>
      <DatabasesTableCard :labels="labels" :data="data" @updateField="updateField" />
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, getCurrentInstance } from "vue";
import DatabasesTableCard from "@/components/DatabasesTableCard.vue";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "DatabasesView",
  components: {
    DatabasesTableCard,
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

    const updateField = (rowIndex, fieldName, newValue) => {
      data.value[rowIndex][fieldName] = newValue;
      axios
        .post(
          "mysql.php",
          qs.stringify({
            updateField: true,
            tableName: route.params.name,
            fieldName,
            newValue,
            rowIndex,
          })
        )
        .then(() => {
          // Reload data after successful update
          axios
            .post(
              "mysql.php",
              qs.stringify({ getTableByName: route.params.name })
            )
            .then((res) => {
              labels.value = res.data.labels;
              data.value = res.data.data;
            });
        });
    };

    return {
      labels,
      data,
      updateField,
    };
  },
});
</script>
