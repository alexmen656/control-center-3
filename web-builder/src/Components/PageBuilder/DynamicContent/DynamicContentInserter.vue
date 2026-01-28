<script setup>
/**
 * DynamicContentInserter Component
 * 
 * Provides a UI for inserting dynamic content references into text.
 * Shows available CC Forms and allows selecting columns/indices.
 */

import { ref, computed, watch, onMounted } from 'vue';
import Modal from '@/Components/Modals/Modal.vue';
import { usePageBuilderStateStore } from '@/stores/page-builder-state';
import { useProjectStore } from '@/stores/project';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'insert']);

const pageBuilderStateStore = usePageBuilderStateStore();
const projectStore = useProjectStore();

// CC Forms API URL
const CC_FORMS_API = 'https://api.fringelo.com/api/cc_forms.php';

// Loading state
const isLoading = ref(false);
const loadError = ref(null);

// Available forms (CC Forms from the project)
const forms = ref([]);

// Get current WB project ID
const wbProjectId = computed(() => {
  return projectStore.getCurrentProject?.id || null;
});

// Load forms when modal opens
watch(() => props.show, async (newVal) => {
  if (newVal) {
    await loadForms();
  }
});

// Load forms from backend using cc_forms.php API
const loadForms = async () => {
  if (!wbProjectId.value) {
    loadError.value = 'Kein Projekt ausgewählt';
    return;
  }
  
  isLoading.value = true;
  loadError.value = null;
  
  try {
    const response = await fetch(
      `${CC_FORMS_API}?action=list&wb_project_id=${encodeURIComponent(wbProjectId.value)}`,
      {
        headers: {
          'Authorization': localStorage.getItem('authToken') || '',
        },
      }
    );
    
    const data = await response.json();
    
    if (data.success && Array.isArray(data.forms)) {
      // Transform forms to usable format
      forms.value = data.forms.map(form => ({
        id: form.id,
        name: form.title || form.name || 'Unnamed Form',
        columns: (form.fields || []).map(f => f.name),
        tableName: form.table_name || generateTableName(form.title || form.name),
        rowCount: form.entry_count || 10
      }));
    } else {
      loadError.value = data.error || 'Fehler beim Laden der Formulare';
      forms.value = [];
    }
  } catch (error) {
    console.error('Error loading forms:', error);
    loadError.value = 'Fehler beim Laden der Formulare';
    forms.value = [];
  } finally {
    isLoading.value = false;
  }
};

// Generate table name (fallback, same logic as PHP backend)
const generateTableName = (formName) => {
  if (!formName) return '';
  return formName.toLowerCase()
    .replace(/[-äÄüÜöÖ\s]/g, match => ({ '-': '_', ' ': '_', 'ä': 'a', 'Ä': 'a', 'ü': 'u', 'Ü': 'u', 'ö': 'o', 'Ö': 'o' })[match] || match);
};

// Form state
const selectedForm = ref(null);
const selectedColumn = ref('');
const selectedIndex = ref(0);

// Get columns for selected form
const availableColumns = computed(() => {
  if (!selectedForm.value) return [];
  return selectedForm.value.columns || [];
});

// Max index for selected form (would need rowCount from backend)
const maxIndex = computed(() => {
  if (!selectedForm.value) return 0;
  return Math.max(0, (selectedForm.value.rowCount || 10) - 1);
});

// Preview of the syntax
const syntaxPreview = computed(() => {
  if (!selectedForm.value || !selectedColumn.value) return '';
  return `{{${selectedForm.value.tableName}.${selectedColumn.value}[${selectedIndex.value}]}}`;
});

// Reset column when form changes
watch(selectedForm, () => {
  selectedColumn.value = '';
  selectedIndex.value = 0;
});

// Reset form when modal closes
watch(() => props.show, (newVal) => {
  if (!newVal) {
    selectedForm.value = null;
    selectedColumn.value = '';
    selectedIndex.value = 0;
    manualMode.value = false;
    manualInput.value = '';
  }
});

const handleClose = () => {
  emit('close');
};

const handleInsert = () => {
  if (syntaxPreview.value) {
    emit('insert', syntaxPreview.value);
    handleClose();
  }
};

// Manual input mode
const manualMode = ref(false);
const manualInput = ref('');

const toggleManualMode = () => {
  manualMode.value = !manualMode.value;
  if (!manualMode.value) {
    manualInput.value = '';
  }
};

const handleManualInsert = () => {
  // Validate the syntax
  const regex = /^[a-zA-Z_][a-zA-Z0-9_]*\.[a-zA-Z_][a-zA-Z0-9_]*\[\d+\]$/;
  if (regex.test(manualInput.value)) {
    emit('insert', `{{${manualInput.value}}}`);
    handleClose();
  }
};
</script>

<template>
  <Modal
    maxWidth="md"
    :show="show"
    @close="handleClose"
  >
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">
          Dynamic Content einfügen
        </h3>
        <button
          @click="handleClose"
          class="h-8 w-8 rounded-full flex items-center justify-center hover:bg-gray-100"
        >
          <span class="material-symbols-outlined text-gray-500">close</span>
        </button>
      </div>

      <p class="text-sm text-gray-600 mb-4">
        Füge dynamischen Content aus deinen CC Forms ein. 
        Der Wert wird automatisch aus der Datenbank geladen.
      </p>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <span class="ml-3 text-gray-600">Formulare werden geladen...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="loadError" class="p-4 bg-red-50 rounded-lg text-red-700 text-sm">
        {{ loadError }}
      </div>

      <template v-else>
        <!-- Mode Toggle -->
        <div class="flex gap-2 mb-6">
          <button
            @click="manualMode = false"
            :class="[
              'px-3 py-1.5 text-sm rounded-md transition-colors',
              !manualMode ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            Auswählen
          </button>
          <button
            @click="manualMode = true"
            :class="[
              'px-3 py-1.5 text-sm rounded-md transition-colors',
              manualMode ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            Manuell eingeben
          </button>
        </div>

        <!-- Selection Mode -->
        <template v-if="!manualMode">
          <div class="space-y-4">
            <!-- Form Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                CC Formular
              </label>
              <select
                v-model="selectedForm"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Formular auswählen...</option>
                <option 
                  v-for="form in forms" 
                  :key="form.id" 
                  :value="form"
                >
                  {{ form.name }} ({{ form.rowCount }} Einträge)
                </option>
              </select>
              <p v-if="forms.length === 0" class="text-xs text-gray-500 mt-1">
                Keine Formulare gefunden. Erstelle zuerst ein CC Form.
              </p>
            </div>

            <!-- Column Selection -->
            <div v-if="selectedForm">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Spalte / Feld
              </label>
              <select
                v-model="selectedColumn"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Spalte auswählen...</option>
                <option 
                  v-for="column in availableColumns" 
                  :key="column" 
                  :value="column"
                >
                  {{ column }}
                </option>
              </select>
            </div>

            <!-- Index Selection -->
            <div v-if="selectedColumn">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Index (0 = erster Eintrag)
              </label>
              <input
                v-model.number="selectedIndex"
                type="number"
                :min="0"
                :max="maxIndex"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Verfügbare Einträge: 0 bis {{ maxIndex }}
              </p>
            </div>

            <!-- Preview -->
            <div v-if="syntaxPreview" class="mt-4 p-3 bg-gray-50 rounded-lg">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Vorschau
              </label>
              <code class="text-sm bg-white px-3 py-2 rounded border border-gray-200 block">
                {{ syntaxPreview }}
              </code>
            </div>
          </div>
        </template>

        <!-- Manual Mode -->
        <template v-else>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Syntax eingeben
              </label>
              <div class="flex items-center gap-2">
                <span class="text-gray-500 font-mono"></span>
                <input
                  v-model="manualInput"
                  type="text"
                  placeholder="form_name.spalte[0]"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                />
                <span class="text-gray-500 font-mono"></span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Format: formular_tabellenname.spalten_name[index]
              </p>
            </div>

            <!-- Example -->
            <div class="p-3 bg-blue-50 rounded-lg">
              <p class="text-sm text-blue-800">
                <strong>Beispiel:</strong> kontaktformular.name[0]
              </p>
              <p class="text-xs text-blue-600 mt-1">
                Holt den ersten Eintrag aus der "name" Spalte des "Kontaktformular" Formulars.
              </p>
            </div>
          </div>
        </template>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
          <button
            @click="handleClose"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Abbrechen
          </button>
          <button
            v-if="!manualMode"
            @click="handleInsert"
            :disabled="!syntaxPreview"
            :class="[
              'px-4 py-2 text-sm font-medium text-white rounded-md',
              syntaxPreview 
                ? 'bg-indigo-600 hover:bg-indigo-700' 
                : 'bg-gray-300 cursor-not-allowed'
            ]"
          >
            Einfügen
          </button>
          <button
            v-else
            @click="handleManualInsert"
            :disabled="!manualInput"
            :class="[
              'px-4 py-2 text-sm font-medium text-white rounded-md',
              manualInput 
                ? 'bg-indigo-600 hover:bg-indigo-700' 
                : 'bg-gray-300 cursor-not-allowed'
            ]"
          >
            Einfügen
          </button>
        </div>
      </template>
    </div>
  </Modal>
</template>
