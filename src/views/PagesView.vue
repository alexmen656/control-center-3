<template>
  <ion-page>
    <ion-content>
      <ion-grid class="md">
        <ion-row class="md">
          <ion-col size="1"></ion-col>
          <ion-col size="10">
            <ion-button @click="select()">Select More Pages</ion-button>
            <ion-button v-if="selectt" @click="multi_delete(selectedComponents)"
              >Delete selected pages</ion-button
            >
            <ion-list>
              <ion-item-sliding v-for="tool in tools" :key="tool.id">
                <ion-item>
                  <ion-label
                    ><ion-checkbox
                      @click="selectedComponents.push(tool.id)"
                      v-if="selectt"
                    ></ion-checkbox
                    ><ion-icon slot="start" :name="tool.icon"></ion-icon>
                    {{ tool.name }}</ion-label
                  >
                </ion-item>
                <ion-item-options>
                  <ion-item-option
                    @click="deleteee(tool)"
                    color="danger"
                    class="delete"
                    ><ion-icon
                      class="delete-icon"
                      name="trash-outline"
                    ></ion-icon>
                    Löschen</ion-item-option
                  >
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
import { defineComponent, ref, getCurrentInstance } from "vue";
import { useRoute } from "vue-router";

export default defineComponent({
  name: "PagesView",
  data() {
    return {
      name: "",
      selectt: false,
      selectedTools: [],
    };
  },
  setup() {
    const tools = ref([]);
    const route = useRoute();
    const { proxy } = getCurrentInstance();

    // Use proxy to access the global properties
    proxy.$axios
      .post(
        "web_pages.php",
        proxy.$qs.stringify({
          getPagesByProject: "getPagesByProject",
          project: route.params.project,
        })
      )
      .then((response) => {
        tools.value = response.data;
        console.log(tools.value);
      });

    function deleteee(tool) {
      //alert(tool.name);
      if (confirm("Do you really want to delete the tool")) {
        proxy.$axios
          .post(
            "web_pages.php",
            proxy.$qs.stringify({
              deletePage: "deletePage",
              name: tool.code,
              project: route.params.project,
            })
          )
          .then(() => {//res
            alert("Tool deleted successfull");
            proxy.$axios
              .post(
                "web_pages.php",
                proxy.$qs.stringify({
                  getPagesByProject: "getPagesByProject",
                  project: route.params.project,
                })
              )
              .then((response) => {
                tools.value = response.data;
              })
              .catch((error) => {
                console.error(error);
              });
          });
      }
    }

    function multi_delete(toolss) {
      if (confirm("Do you really want to delete the tool")) {
        toolss.forEach((tool) => {
          proxy.$axios.post(
            "web_pages.php",
            proxy.$qs.stringify({ deletePage: "deletePage", toolID: tool })
          );
        });

        proxy.selectedTools = [];
        proxy.selectt = false;

        proxy.$axios
          .get(
            "sidebar.php?getSideBarByProjectName=" +
              route.params.project
          )
          .then((response) => {
            tools.value = response.data.tools;
          })
          .catch((error) => {
            console.error(error);
          });
      }
    }

    function select() {
      if (proxy.selectt) {
        proxy.selectt = false;
      } else {
        proxy.selectt = true;
      }
    }

    return {
      deleteee,
      tools: tools,
      select,
      multi_delete,
    };
  },
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
