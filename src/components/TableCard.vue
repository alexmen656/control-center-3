<template>
  <ion-card>
    <div class="table-container">
      <table>
        <tbody>
          <tr>
            <th v-for="label in labels" :key="label">{{ label }}</th>
          </tr>
          <tr v-for="tr in data" :key="tr">
            <td v-for="td in tr" :key="td">{{ td }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </ion-card>
</template>

<script>
export default {
  name: "TableCard",
  props: {
    labels: Array,
    data: Array,
  },
};
</script>

<style scoped>
.table-container {
  overflow-x: auto;
  width: 100%;
}

table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
  min-width: 600px; /* Minimum width to ensure readability */
}

td,
th {
  border: none;
  text-align: left;
  padding: 8px;
  white-space: nowrap; /* Prevent text wrapping in cells */
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
