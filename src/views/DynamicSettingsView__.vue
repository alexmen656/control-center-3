<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Settings for {{ module }}</ion-title>
      </ion-toolbar>
    </ion-header>
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
          <!-- Add more input types as needed -->
        </div>
        <ion-button type="submit" expand="block">Save</ion-button>
      </form>
    </ion-content>
  </ion-page>
</template>

<script>
import { defineComponent, ref, reactive, onMounted } from "vue";

export default defineComponent({
  name: "DynamicSettingsView",
  props: {
    project: {
      type: String,
      required: true,
    },
    module: {
      type: String,
      required: false, // Module will be inferred manually
    },
  },
  setup() {
    const settings = ref({});
    const formData = reactive({});
    const moduleName = ref("");

    const loadSettings = async () => {
      try {
        // Manually extract module name from the URL
        const pathSegments = window.location.pathname.split("/");
        moduleName.value = pathSegments[pathSegments.length - 2];

        // Use import.meta.glob to dynamically load the settings.json
        const modules = import.meta.glob('@/modules/*/settings.json');
        const settingsPath = `@/modules/${moduleName.value}/settings.json`;

        if (modules[settingsPath]) {
          const response = await modules[settingsPath]();
          settings.value = response.default;

          // Initialize formData with default values
          for (const key in settings.value) {
            formData[key] = settings.value[key].default || "";
          }
        } else {
          console.error(`Settings file not found for module: ${moduleName.value}`);
        }
      } catch (error) {
        console.error("Failed to load settings:", error);
      }
    };

    const saveSettings = () => {
      console.log("Saving settings:", formData);
      // Implement save logic (e.g., API call)
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
