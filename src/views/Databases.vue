<template>
  <ion-page>
    <ion-content>
      <ion-card>
        <table>
          <tbody>
            <tr>
              <th v-for="label in labels" :key="label">{{ label }}</th>
              <th class="search-th">
                <ion-button fill="clear" @click="toggleSearch" class="search-btn">
                  <ion-icon name="search-outline"></ion-icon>
                </ion-button>
              </th>
            </tr>
            <tr v-if="showSearch">
              <td :colspan="labels.length + 1">
                <ion-input
                  v-model="search"
                  placeholder="Tabelle suchen..."
                  clear-input
                  class="search-input"
                  autofocus
                ></ion-input>
              </td>
            </tr>
            <tr v-for="tr in filteredTables" :key="tr">
              <td v-for="td in tr" :key="td">
                <router-link :to="'/databases/table/' + td">
                  <span v-if="search && search.length > 0" v-html="highlightMatch(td, search)"></span>
                  <span v-else>{{ td }}</span>
                </router-link>
              </td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, getCurrentInstance, computed } from "vue";

export default defineComponent({
  name: "DatabasesView",
  data() {
    return {
      labels: ["Table Name"],
    };
  },
  setup() {
    const { appContext } = getCurrentInstance();
    const axios = appContext.config.globalProperties.$axios;
    const qs = appContext.config.globalProperties.$qs;

    const tables = ref([]);
    const search = ref("");
    const showSearch = ref(false);

    axios.post("mysql.php", qs.stringify({getTables: "getTables"})).then((res) => {
      tables.value = res.data;
    });

    const filteredTables = computed(() => {
      if (!search.value) return tables.value;
      return tables.value.filter((tr) => {
        // tr is likely an array, so check all cells
        return tr.some((td) => String(td).toLowerCase().includes(search.value.toLowerCase()));
      });
    });

    function toggleSearch() {
      showSearch.value = !showSearch.value;
      if (!showSearch.value) search.value = "";
    }

    function highlightMatch(text, search) {
      if (!search) return text;
      const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
      return String(text).replace(regex, '<mark class="highlight">$1</mark>');
    }

    return {
      tables: tables,
      search,
      filteredTables,
      showSearch,
      toggleSearch,
      highlightMatch,
    };
  },
});
</script>
<style scoped>
.search-input {
  width: 100%;
  box-sizing: border-box;
}
.search-th {
  width: 48px;
  text-align: right;
}
.search-btn {
  min-width: 0;
  --padding-start: 0;
  --padding-end: 0;
}
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

ion-card {
  margin-top: 20px;
}
</style>
mark.highlight {
  background: #ffe082;
  color: #d32f2f;
  padding: 0 2px;
  border-radius: 2px;
}
