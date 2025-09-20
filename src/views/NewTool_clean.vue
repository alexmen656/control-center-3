<template>
  <ion-page>
    <ion-content>
      <ion-grid>
        <ion-row>
          <ion-col size="1" />
          <ion-col
            style="display: flex; flex-direction: column; align-items: center"
            size="10"
          >
            <ion-list class="w100">
              <ion-item class="w100">
                <ion-select
                  aria-label="Tool"
                  interface="action-sheet"
                  placeholder="Select Tool"
                  v-model="selectedTool"
                  :value="selectedTool"
                  @ionInput="selectedTool = $event.target.value"
                >
                  <ion-select-option
                    v-for="tool in tools"
                    :key="tool.id"
                    :value="tool.name"
                  >
                    {{ tool.name }}</ion-select-option
                  >
                  <ion-select-option value="dashboard">
                    Dashboard (New)</ion-select-option
                  >
                  <ion-select-option value="ai_dashboard">
                    AI Dashboard Generator</ion-select-option
                  >
                  <!--<ion-select-option value="qr_code_scanner"> QR Code Scanner</ion-select-option>-->
                </ion-select>
              </ion-item>
            </ion-list>
            <ion-button
              style="width: 40%; margin-top: 1rem"
              @click="handleSubmit"
              >Submit</ion-button
            >
          </ion-col>
          <ion-col size="1" />
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent } from "vue";

export default defineComponent({
  name: "ToolSelection",
  data() {
    return {
      tools: [],
      selectedTool: "",
      name: "",
    };
  },
  async created() {
    this.$axios
      .post(
        "modules.php",
        this.$qs.stringify({ project: this.$route.params.project })
      )
      .then((res) => {
        if (res.data && res.data.length > 0) {
          this.tools = res.data.map((tool, index) => ({
            id: index + 1,
            icon: tool.icon,
            name: tool.name,
          }));
        }
      });
  },

  methods: {
    handleSubmit() {
      if (this.selectedTool == "dashboard") {
        this.$axios
          .post(
            "dashboard.php",
            this.$qs.stringify({
              new_dashboard: "new_dashboard",
              project: this.$route.params.project,
            })
          )
          .then(() => {
            this.emitter.emit("updateSidebar");
          });
      } else if (this.selectedTool == "ai_dashboard") {
        // Navigate to AI Dashboard Generator
        this.$router.push(`/project/${this.$route.params.project}/ai-dashboard-generator`);
      } else {
        const selectedTool = this.tools.find(
          (tool) => tool.name === this.selectedTool
        );
        this.$axios
          .post(
            "tools.php",
            this.$qs.stringify({
              newTool: "newTool",
              toolIcon: selectedTool.icon,
              projectName: this.$route.params.project,
              toolName: selectedTool.name,
            })
          )
          .then(() => {
            this.emitter.emit("updateSidebar");
          });
      }
    },
  },
});
</script>

<style scoped>
.w100 {
  width: 100%;
}
</style>
