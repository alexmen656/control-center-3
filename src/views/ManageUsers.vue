<template>
  <ion-page>
   <ion-content>
    <ion-grid class="md">
     <ion-row class="md">
      <ion-col size="1"></ion-col>
      <ion-col size="10">
       <ion-button @click="select()">Select More Tools</ion-button>
       <ion-button v-if="selectt" @click="multi_delete(selectedTools)">Delete selected tools</ion-button>
       <ion-list>
        <ion-item-sliding v-for="tool in tools" :key="tool.id">
         <ion-item>
          <ion-label><ion-checkbox @click="selectedTools.push(tool.id);" v-if="selectt"></ion-checkbox><ion-icon slot="start" :name="tool.icon"></ion-icon> {{ tool.name }}</ion-label>
         </ion-item>
         <ion-item-options>
          <ion-item-option @click="deleteee(tool)" color="danger" class="delete"><ion-icon class="delete-icon" name="trash-outline"></ion-icon> Löschen</ion-item-option>
         </ion-item-options>
        </ion-item-sliding>
       </ion-list>
      </ion-col>
      <ion-col size="1"></ion-col>
     </ion-row>
    </ion-grid>
   </ion-content>
  </ion-page>
 </template>
   
 <script>
 import { defineComponent, ref } from 'vue';
 import axios from 'axios';
 import qs from 'qs'
 import { useRoute } from "vue-router";
 
 export default defineComponent({
     name: 'ProjectsPage',
     data(){
         return {
             name: "",
             selectt: false,
             selectedTools: []
         }
     },
     setup() {
     const tools = ref([]);
     const route = useRoute();
 
     axios.get('https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=' + route.params.project).then(response => {
      tools.value = response.data.tools;
     });
 
     function deleteee(tool) {
       if (confirm("Do you really want to delete the tool")) {
        axios.post("https://alex.polan.sk/control-center/tools.php", qs.stringify({deleteTool: "deleteTool", toolID: tool.id})).then(res=> {
         alert("Tool deleted successfull");
         axios.get('https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=' + route.params.project)
         .then(response => {
             tools.value = response.data.tools;
         })
         .catch(error => {
             console.error(error);
         });
        });
       }
     }
 
     function multi_delete(toolss) {
       if (confirm("Do you really want to delete the tool")) {
         toolss.forEach((tool) => {
           axios.post("https://alex.polan.sk/control-center/tools.php", qs.stringify({deleteTool: "deleteTool", toolID: tool}))
         });
 
         this.selectedTools = [];
         this.selectt = false;
 
         axios.get('https://alex.polan.sk/control-center/sidebar.php?getSideBarByProjectName=' + route.params.project)
         .then(response => {
           tools.value = response.data.tools;
         })
         .catch(error => {
           console.error(error);
         });
       }
     }
 
     function select(){
         if(this.selectt){
             this.selectt = false;
         }else{
             this.selectt = true;  
         }
     }
   
       return {
         deleteee,
         tools: tools,
         select,
         multi_delete
       };
     }
   });
 </script>
 <style scoped>
 .delete {
     background-color: red !important;
 }
 
 .delete-icon {
     margin-right: 3px;
 }
 </style>