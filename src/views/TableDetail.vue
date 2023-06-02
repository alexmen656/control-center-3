<template>
  <ion-page>    
    <ion-content>
        <TableCard :labels="labels" :data="data"/>
    </ion-content>  
  </ion-page>
</template>

<script>
import axios from 'axios';
import { defineComponent, ref } from 'vue';
import TableCard from '@/components/TableCard.vue'
import { IonContent, IonPage } from '@ionic/vue';
import { useRoute } from 'vue-router';

export default defineComponent({
    name: "DatabasesView",
    components: {
    TableCard,
    IonPage,
    IonContent,
  },
    setup() {
    const labels = ref([]);
    const data = ref([]);
    const route = useRoute();

    axios.get('https://alex.polan.sk/control-center/mysql.php?getTableByName=' + route.params.name).then(res => {
      labels.value = res.data.labels;
      data.value = res.data.data;
     });

    return {
      labels: labels,
      data:data
    }

  },

});
</script>