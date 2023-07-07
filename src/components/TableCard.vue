<template>
  <ion-card>
    <table>
      <tr>
        <th v-for="label in labels" :key="label">{{ label }}</th>
      </tr>
      <tr v-for="tr in data" :key="tr">
        <td v-for="td in tr" :key="td">{{ td }}</td>
      </tr>
    </table>
  </ion-card>
</template>

<script>
import { IonCard } from "@ionic/vue";

export default {
  name: "TableCard",
  components: {
    IonCard,
  },
  props: {
    labels: Array,
    data: Array,
  },
};
</script>

<style scoped>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td,
th {
  border: none;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #e9e9e9;
}

@media (prefers-color-scheme: dark) {
  tr:nth-child(even) {
    background-color: #121212;
  }
}
</style>
