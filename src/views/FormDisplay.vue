<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row
          ><ion-col size="0" size-md="1" />
          <ion-col size="12" size-md="10">
            <ion-card>
              <div class="table-container">
                <table>
                  <thead>
                    <tr>
                      <th
                        v-for="(label, index) in labels"
                        :key="label"
                        @click="sortBy(index)"
                        class="sortable-header"
                      ><!--.slice(0, 5)-->
                        {{ label }}
                        <span class="sort-indicator">
                          <ion-icon 
                            v-if="sortColumn === index && sortDirection === 'asc'" 
                            name="chevron-up-outline"
                          ></ion-icon>
                          <ion-icon 
                            v-else-if="sortColumn === index && sortDirection === 'desc'" 
                            name="chevron-down-outline"
                          ></ion-icon>
                          <ion-icon 
                            v-else 
                            name="swap-vertical-outline" 
                            class="sort-default"
                          ></ion-icon>
                        </span>
                      </th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="tr in sortedData" :key="tr">
                      <td v-for="td in tr" :key="td">{{ td }}</td><!--.slice(0, 5)-->
                      <td>
                        <ion-icon @click="deletee(tr[0])" name="trash-outline" />
                      </td>
                      <td>
                        <ion-icon @click="edit(tr[0])" name="create-outline" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </ion-card>
            <ion-row
              v-if="load_more_btn"
              style="display: flex; justify-content: center"
            >
              <ion-button @click="loadMore()">Load More</ion-button>
            </ion-row>
            <DisplayForm @submit="handleSubmit" /> </ion-col
          ><ion-col size="0" size-md="1" />
        </ion-row>
      </ion-grid>
      <ion-modal
        :is-open="isOpenRef"
        css-class="my-custom-class"
        @didDismiss="closeModal(false)"
      >
        <EditEntry
          @submit="handleEdit"
          :data="{
            id: edit_id,
            form: $route.params.form,
            project: $route.params.project,
          }"
        />
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script>
//lang="ts"
import DisplayForm from "@/components/DisplayForm.vue";
import EditEntry from "@/components/EditEntry.vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  name: "FormDisplay",
  components: {
    DisplayForm,
    EditEntry,
  },
  data() {
    return {
      form: {},
      labels: [],
      data: [],
      load_more_btn: false,
      current_limit: 0,
      sortColumn: null,
      sortDirection: 'asc',
    };
  },
  computed: {
    sortedData() {
      if (this.sortColumn === null) {
        return this.data;
      }
      
      const sorted = [...this.data].sort((a, b) => {
        const aVal = a[this.sortColumn];
        const bVal = b[this.sortColumn];
        
        // Check if values are numbers
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
          // Numeric sort
          return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
          // String sort
          const aStr = String(aVal).toLowerCase();
          const bStr = String(bVal).toLowerCase();
          
          if (this.sortDirection === 'asc') {
            return aStr.localeCompare(bStr);
          } else {
            return bStr.localeCompare(aStr);
          }
        }
      });
      
      return sorted;
    }
  },
  setup() {
    const isOpenRef = ref(false);
    const edit_id = ref("");
    const edit = (id) => {
      isOpenRef.value = true;
      edit_id.value = id;
    }; //: number
    const closeModal = (state) => {
      isOpenRef.value = state;
    }; //: number
    return { isOpenRef, edit, closeModal, edit_id };
  },
  created() {
    this.loadData();
  },
  methods: {
    sortBy(columnIndex) {
      if (this.sortColumn === columnIndex) {
        // Toggle direction if same column
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        // New column, start with ascending
        this.sortColumn = columnIndex;
        this.sortDirection = 'asc';
      }
    },
    handleSubmit(data) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            submit_form: "submit_form",
            form: JSON.stringify(data),
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.loadData();
        });
    },
    handleEdit(data) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            update_entry: "update_entry",
            entry_id: this.edit_id,
            form: JSON.stringify(data),
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.closeModal(false);
          this.loadData();
        });
    },
    deletee(id) {
      this.$axios
        .post(
          "form.php",
          this.$qs.stringify({
            delete_entry: "delete_entry",
            entry_id: id,
            form_name: this.$route.params.form,
            project: this.$route.params.project,
          })
        )
        .then(() => {
          this.loadData();
        });
    },
    /*{edit(id){
      alert(id);
      
      const isOpenRef = ref(false);
      const setOpen = () => (isOpenRef.value = true);
      //const data = { content: 'New Content' };
      return { isOpenRef, setOpen};//, data 

    },*/
    loadData() {
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.form.replaceAll("-", "_")}`;
      this.$axios
        .post(
          `mysql.php`,
          this.$qs.stringify({ getTableByName: table_name, limit: 30 })
        )
        .then((res) => {
          this.labels = res.data.labels;
          this.data = res.data.data;
          this.load_more_btn = res.data.load_more_btn;
          this.current_limit = 1;
        });
    },
    loadMore(){
      const table_name = `${this.$route.params.project.replaceAll("-", "_")}_${this.$route.params.form}`;
      this.$axios.post("mysql.php", this.$this.$qs.stringify({load_more: "load_more", current_limit: this.current_limit,table: table_name})).then((res) => {
        this.current_limit = this.current_limit+1;

        res.data.data.forEach(element =>{
          this.data.push(element);
        });
      });
    }
  },
});
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
  min-width: 600px; /* Minimum width to ensure all columns are visible */
}

td,
th {
  border: none;
  text-align: left;
  padding: 8px;
  white-space: nowrap; /* Prevent text wrapping in cells */
}

th {
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.9em;
  color: var(--ion-color-medium);
}

.sortable-header {
  cursor: pointer;
  user-select: none;
  position: relative;
  transition: background-color 0.2s ease;
}

.sortable-header:hover {
  background-color: var(--ion-color-light);
}

.sort-indicator {
  display: inline-flex;
  align-items: center;
  margin-left: 8px;
  font-size: 0.8em;
}

.sort-default {
  opacity: 0.3;
}

.sortable-header:hover .sort-default {
  opacity: 0.6;
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
