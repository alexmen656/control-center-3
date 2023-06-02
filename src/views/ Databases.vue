<template>
  <ion-page> 
    <ion-content>
     <ion-card>
       <table>
         <tr>
           <th v-for="label in labels" :key="label">{{ label }}</th>
          </tr>
          <tr v-for="tr in tables" :key="tr">
            <td v-for="td in tr" :key="td"><router-link :to="'/databases/table/'+td">{{ td }}</router-link></td>
          </tr>
        </table>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script>
import axios from 'axios';
import { defineComponent, ref } from 'vue';
import { IonPage, IonContent, IonCard } from '@ionic/vue';

export default defineComponent({
  name: "DatabasesView",
  components: {
    IonPage,
    IonContent,
    IonCard,
  },
  data(){
    return {
      labels: ["Table Name"]
    }
  },
  setup() {
    const tables = ref([]);

    axios.get('https://alex.polan.sk/control-center/mysql.php?getTables=getTables').then(res => {
      tables.value = res.data;
    });

    return {
      tables: tables
    }
  }
});
</script>
<style scoped>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
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