<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1"></ion-col>
          <ion-col style="display: flex; flex-direction: column; align-items: center;" size="10">
            <ion-list style="width: 100%;">
              <ion-item style="width: 100%;">
                <ion-select aria-label="Fruit" interface="action-sheet" placeholder="Select Tool" v-model="selectedTool" :value="selectedTool" @ionInput="selectedTool = $event.target.value;">
                  <ion-select-option v-for="tool in tools" :key="tool.id" :value="tool.name"><ion-icon slot="start" :name="tool.icon"></ion-icon> {{tool.name}}</ion-select-option>
                </ion-select>
              </ion-item>
            </ion-list>
           <p> -------Or Createe a custom One-------</p>
           <ion-input placeholder="Name" v-model="name" :value="name" @ionInput="name = $event.target.value;"/>
           The url will look like: https://alex.polan.sk/project/{{ $route.params.project }}/{{ name.replaceAll("'", '').replaceAll(" ", "-").toLowerCase() }}
            <ion-button style="width: 40%; margin-top: 1rem;" @click="handleSubmit">Submit</ion-button>
          </ion-col>
          <ion-col size="1"></ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>
  
<script>
  import { IonButton, IonCol, IonContent, IonGrid, IonItem, IonList, IonPage, IonSelect, IonSelectOption, IonRow } from '@ionic/vue';
  import { defineComponent } from 'vue';
  import axios from 'axios';
  import qs from 'qs';

  export default defineComponent({
    name: 'ToolSelection',
    data() {
      return {
        tools: [],
        selectedTool: '',
        name: ''
      };
    },
    async created() {
      const response = await fetch('https://alex.polan.sk/control-center/modules.php');
      const data = await response.json();
      this.tools = data.map((tool, index) => ({ id: index + 1, icon: tool.icon, name: tool.name }));
    },
    methods: {
      handleSubmit() {
        const selectedTool = this.tools.find(tool => tool.name === this.selectedTool);
        axios.post("https://alex.polan.sk/control-center/tools.php", qs.stringify({newTool: "newTool", toolIcon: selectedTool.icon, projectName: this.$route.params.project, toolName: selectedTool.name}));
      },
    },
    components: {
      IonButton,
      IonCol,
      IonContent,
      IonGrid,
      IonItem,
      IonList,
      IonPage,
      IonSelect,
      IonSelectOption,
      IonRow,
    },
  });
</script>
