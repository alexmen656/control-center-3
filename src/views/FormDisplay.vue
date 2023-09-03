<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row
          ><ion-col size="0" size-md="1" />
          <ion-col size="12" size-md="10">
            <ion-card>
              <table>
                <tr>
                  <th
                    v-for="label in labels.slice(0, 5)"
                    :key="label"
                  >
                    {{ label }}
                  </th>
                  <th></th>
                </tr>
                <tr v-for="tr in data" :key="tr">
                  <td v-for="td in tr.slice(0, 5)" :key="td">{{ td }}</td>
                  <td>
                    <ion-icon @click="deletee(tr[0])" name="trash-outline" />
                  </td>
                  <td>
                    <ion-icon @click="edit(tr[0])" name="create-outline" />
                  </td>
                </tr>
              </table>
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
import axios from "axios";
import qs from "qs";
import {
  IonPage,
  IonContent,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonIcon,
  IonModal,
} from "@ionic/vue";
import EditEntry from "@/components/EditEntry.vue";
import { defineComponent, ref } from "vue";

export default defineComponent({
  name: "FormDisplay",
  components: {
    DisplayForm,
    IonPage,
    IonContent,
    IonGrid,
    IonRow,
    IonCol,
    EditEntry,
    IonCard,
    IonIcon,
    IonModal,
  },
  data() {
    return {
      form: {},
      labels: [],
      data: [],
      load_more_btn: false,
      current_limit: 0,
    };
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
    handleSubmit(data) {
      axios
        .post(
          "/control-center/form.php",
          qs.stringify({
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
      axios
        .post(
          "/control-center/form.php",
          qs.stringify({
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
      axios
        .post(
          "/control-center/form.php",
          qs.stringify({
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
      const table_name = `${this.$route.params.project}_${this.$route.params.form}`;
      axios
        .post(
          `/control-center/mysql.php`,
          qs.stringify({ getTableByName: table_name, limit: 30 })
        )
        .then((res) => {
          this.labels = res.data.labels;
          this.data = res.data.data;
          this.load_more_btn = res.data.load_more_btn;
          this.current_limit = 1;
        });
    },
    loadMore(){
      const table_name = `${this.$route.params.project}_${this.$route.params.form}`;
      axios.post("/control-center/mysql.php", qs.stringify({load_more: "load_more", current_limit: this.current_limit,table: table_name})).then((res) => {
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
