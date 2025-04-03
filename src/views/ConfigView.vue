<template>
  <ion-page>
    <ion-content class="ion-padding">
      <form @submit.prevent="saveSettings">
        <div v-for="(field, key) in settings" :key="key" class="form-group">
          <label :for="key">{{ field.label }}</label>
          <input
            v-if="field.type === 'text'"
            :id="key"
            v-model="formData[key]"
            type="text"
            :placeholder="field.placeholder || ''"
          />
          <select
            v-else-if="field.type === 'select'"
            :id="key"
            v-model="formData[key]"
          >
            <option
              v-for="option in field.options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
          <input
            v-else-if="field.type === 'checkbox'"
            :id="key"
            v-model="formData[key]"
            type="checkbox"
          />
          <!-- Add more input types as needed -->
        </div>
        <ion-button type="submit" expand="block">Save</ion-button>
      </form>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, reactive, onMounted } from "vue";
import { ToolConfigService } from "@/services/ToolConfigService";
import { useRouter } from "vue-router";

export default defineComponent({
  name: "ConfigView",
  setup() {
    const router = useRouter(); // Use Vue Router
    const settings = ref({});
    const formData = reactive({});
    const moduleName = ref("");

    const loadSettings = async () => {
      try {
        const project = router.currentRoute.value.params.project; // Use project from route params
        const tool = router.currentRoute.value.path.split("/").slice(-2, -1)[0]; // Extract tool name from URL

        // Load the settings.json structure
        const modules = import.meta.glob('@/modules/*/settings.json');
        const settingsPath = `/src/modules/${tool}/settings.json`;

        if (modules[settingsPath]) {
          const response = await modules[settingsPath]();
          settings.value = response.default;

          // Load the saved configuration from the API
          const savedConfig = await ToolConfigService.loadToolConfig(project, tool);
          console.log("Loaded settings:", settings.value);
          console.log("Loaded saved configuration:", savedConfig);

          // Merge saved configuration into the default values and fill inputs
          for (const key in settings.value) {
            formData[key] = savedConfig[key] !== undefined ? savedConfig[key] : settings.value[key].default || "";
            console.log(`Form data for ${key}:`, formData[key]);
          }
        } else {
          console.error(`Settings file not found for module: ${tool}`);
        }
      } catch (error) {
        console.error("Failed to load settings:", error);
      }
    };

    const saveSettings = async () => {
      try {
        const project = router.currentRoute.value.params.project; // Use project from route params
        const tool = router.currentRoute.value.path.split("/").slice(-2, -1)[0]; // Extract tool name from URL

        await ToolConfigService.saveToolConfig(project, tool, formData);
        console.log("Settings saved successfully");
      } catch (error) {
        console.error("Failed to save settings:", error);
      }
    };

    onMounted(loadSettings);

    return {
      settings,
      formData,
      saveSettings,
      module: moduleName,
    };
  },
});
</script>

<style scoped>
.form-group {
  margin-bottom: 1rem;
}
label {
  display: block;
  margin-bottom: 0.5rem;
}
input,
select {
  width: 100%;
  padding: 0.5rem;
  margin-bottom: 1rem;
}
</style>
