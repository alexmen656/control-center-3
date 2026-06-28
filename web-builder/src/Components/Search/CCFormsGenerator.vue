<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Modal from '@/Components/Modals/Modal.vue';
import SmallUniversalSpinner from '@/Components/Loaders/SmallUniversalSpinner.vue';
import { useProjectStore } from '@/stores/project';

const projectStore = useProjectStore();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'insertForm']);

// State
const isLoading = ref(false);
const error = ref('');
const forms = ref([]);
const selectedForm = ref(null);
const selectedFields = ref([]);

// Form generation options
const formOptions = ref({
    style: 'modern',
    submitText: 'Absenden',
    successMessage: 'Erfolgreich gesendet!',
    errorMessage: 'Fehler beim Senden.',
    showTitle: true,
    showDescription: true,
});

// Styles available
const styleOptions = [
    { id: 'modern', name: 'Modern', description: 'Modernes Design mit Schatten' },
    { id: 'minimal', name: 'Minimal', description: 'Schlicht und einfach' },
    { id: 'card', name: 'Card', description: 'Karte mit Gradient-Button' },
];

// Current project from store - get the WB project ID
const currentProject = computed(() => {
    return projectStore.getCurrentProject;
});

// WB Project ID for API calls
const wbProjectId = computed(() => {
    return currentProject.value?.id || null;
});

// CC Project name for display
const ccProjectName = computed(() => {
    const project = currentProject.value;
    if (project?.cc_project?.name) {
        return project.cc_project.name;
    }
    return project?.name || '';
});

// CC Forms API URL
const CC_FORMS_API = 'https://api.fringelo.com/api/cc_forms.php';

// Load forms when modal opens
watch(() => props.show, async (newVal) => {
    if (newVal && wbProjectId.value) {
        await loadForms();
    }
});

onMounted(async () => {
    if (props.show && wbProjectId.value) {
        await loadForms();
    }
});

// Load CC Forms for current project
async function loadForms() {
    isLoading.value = true;
    error.value = '';
    forms.value = [];
    selectedForm.value = null;

    try {
        const response = await fetch(`${CC_FORMS_API}?action=list&wb_project_id=${encodeURIComponent(wbProjectId.value)}`, {
            headers: {
                'Authorization': localStorage.getItem('authToken') || '',//Bearer 
            },
        });
        const data = await response.json();

        if (data.success) {
            forms.value = data.forms;
        } else {
            error.value = data.error || 'Fehler beim Laden der Forms';
        }
    } catch (e) {
        error.value = 'Verbindungsfehler: ' + e.message;
    } finally {
        isLoading.value = false;
    }
}

// Select a form
function selectForm(form) {
    selectedForm.value = form;
    // Pre-select all fields
    selectedFields.value = form.fields.map(f => f.name);
}

// Toggle field selection
function toggleField(fieldName) {
    const idx = selectedFields.value.indexOf(fieldName);
    if (idx > -1) {
        selectedFields.value.splice(idx, 1);
    } else {
        selectedFields.value.push(fieldName);
    }
}

// Check if field is selected
function isFieldSelected(fieldName) {
    return selectedFields.value.includes(fieldName);
}

// Move field up in order
function moveFieldUp(index) {
    if (index > 0) {
        const temp = selectedFields.value[index];
        selectedFields.value[index] = selectedFields.value[index - 1];
        selectedFields.value[index - 1] = temp;
    }
}

// Move field down in order
function moveFieldDown(index) {
    if (index < selectedFields.value.length - 1) {
        const temp = selectedFields.value[index];
        selectedFields.value[index] = selectedFields.value[index + 1];
        selectedFields.value[index + 1] = temp;
    }
}

// Get field type icon
function getFieldTypeIcon(type) {
    const icons = {
        text: 'text_fields',
        email: 'email',
        number: 'numbers',
        textarea: 'notes',
        select: 'list',
        select2: 'list',
        checkbox: 'check_box',
        date: 'calendar_today',
        time: 'schedule',
    };
    return icons[type] || 'input';
}

// Generate form and emit
async function generateForm() {
    if (!selectedForm.value || selectedFields.value.length === 0) {
        error.value = 'Bitte wähle mindestens ein Feld aus';
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch(`${CC_FORMS_API}?action=generate&wb_project_id=${encodeURIComponent(wbProjectId.value)}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': localStorage.getItem('authToken') || '',//Bearer 
            },
            body: JSON.stringify({
                wb_project_id: wbProjectId.value,
                form: selectedForm.value.name,
                fields: selectedFields.value,
                style: formOptions.value.style,
                submitText: formOptions.value.submitText,
                successMessage: formOptions.value.successMessage,
                errorMessage: formOptions.value.errorMessage,
                showTitle: formOptions.value.showTitle,
                showDescription: formOptions.value.showDescription,
            }),
        });

        const data = await response.json();

        if (data.success) {
            emit('insertForm', {
                html_code: data.html,
                id: null,
                title: `CC Form: ${selectedForm.value.title}`,
            });
            closeModal();
        } else {
            error.value = data.error || 'Fehler bei der Generierung';
        }
    } catch (e) {
        error.value = 'Verbindungsfehler: ' + e.message;
    } finally {
        isLoading.value = false;
    }
}

// Close modal
function closeModal() {
    selectedForm.value = null;
    selectedFields.value = [];
    error.value = '';
    emit('close');
}

// Go back to form list
function goBack() {
    selectedForm.value = null;
    selectedFields.value = [];
}
</script>

<template>
    <Modal maxWidth="4xl" :show="show" @close="closeModal">
        <div class="w-full relative">
            <!-- Header -->
            <div class="flex items-center border-b border-gray-200 p-4">
                <div class="flex items-center gap-3 flex-1">
                    <button v-if="selectedForm" @click="goBack"
                        class="h-8 w-8 rounded-full flex items-center justify-center bg-gray-100 hover:bg-gray-200">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                    </button>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ selectedForm ? 'Form konfigurieren' : 'CC Form einfügen' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ selectedForm ? selectedForm.title : `Projekt: ${ccProjectName}` }}
                        </p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="h-10 w-10 rounded-full flex items-center justify-center bg-gray-50 hover:bg-red-100 hover:text-red-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 min-h-[400px] max-h-[600px] overflow-y-auto">
                <!-- Error -->
                <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ error }}
                </div>

                <!-- Loading -->
                <div v-if="isLoading" class="flex items-center justify-center h-64">
                    <SmallUniversalSpinner width="w-8" height="h-8" border="border-4" />
                </div>

                <!-- Form List -->
                <template v-else-if="!selectedForm">
                    <div v-if="forms.length === 0" class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-gray-300">inbox</span>
                        <p class="mt-4 text-gray-500">Keine Forms in diesem Projekt gefunden</p>
                        <p class="text-sm text-gray-400">Erstelle zuerst eine Form im Fringelo</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="form in forms" :key="form.id" @click="selectForm(form)"
                            class="p-4 border border-gray-200 rounded-xl hover:border-indigo-400 hover:shadow-md cursor-pointer transition-all group">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 group-hover:text-indigo-600">
                                        {{ form.title }}
                                    </h4>
                                    <p class="text-sm text-gray-500 mt-1">{{ form.name }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">
                                    {{ form.fieldCount }} Felder
                                </span>
                            </div>

                            <p v-if="form.description" class="text-sm text-gray-600 mt-2 line-clamp-2">
                                {{ form.description }}
                            </p>

                            <div class="flex flex-wrap gap-1 mt-3">
                                <span v-for="field in form.fields.slice(0, 5)" :key="field.name"
                                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    <span class="material-symbols-outlined text-xs">{{ getFieldTypeIcon(field.type)
                                        }}</span>
                                    {{ field.label }}
                                </span>
                                <span v-if="form.fields.length > 5" class="text-xs text-gray-400 px-2 py-0.5">
                                    +{{ form.fields.length - 5 }} mehr
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Form Configuration -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left: Field Selection -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Felder auswählen</h4>
                            <p class="text-sm text-gray-500 mb-4">Wähle und ordne die Felder, die im Formular erscheinen
                                sollen</p>

                            <!-- Available Fields -->
                            <div class="space-y-2">
                                <div v-for="field in selectedForm.fields" :key="field.name"
                                    @click="toggleField(field.name)"
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all"
                                    :class="isFieldSelected(field.name)
                                        ? 'border-indigo-400 bg-indigo-50'
                                        : 'border-gray-200 hover:border-gray-300'">
                                    <input type="checkbox" :checked="isFieldSelected(field.name)"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @click.stop @change="toggleField(field.name)" />
                                    <span class="material-symbols-outlined text-gray-400">{{
                                        getFieldTypeIcon(field.type) }}</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ field.label }}</p>
                                        <p class="text-xs text-gray-500">{{ field.name }} • {{ field.type }}</p>
                                    </div>
                                    <span v-if="field.required" class="text-xs text-red-500 font-medium">Pflicht</span>
                                </div>
                            </div>

                            <!-- Selected Order -->
                            <div v-if="selectedFields.length > 0" class="mt-6">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Reihenfolge anpassen:</h5>
                                <div class="space-y-1">
                                    <div v-for="(fieldName, idx) in selectedFields" :key="fieldName"
                                        class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                                        <span class="text-xs text-gray-400 w-4">{{ idx + 1 }}</span>
                                        <span class="flex-1 text-sm">{{ fieldName }}</span>
                                        <button @click="moveFieldUp(idx)" :disabled="idx === 0"
                                            class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">
                                            <span class="material-symbols-outlined text-sm">arrow_upward</span>
                                        </button>
                                        <button @click="moveFieldDown(idx)"
                                            :disabled="idx === selectedFields.length - 1"
                                            class="p-1 hover:bg-gray-200 rounded disabled:opacity-30">
                                            <span class="material-symbols-outlined text-sm">arrow_downward</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Style Options -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Design & Texte</h4>

                            <!-- Style Selection -->
                            <div class="space-y-2 mb-6">
                                <label class="text-sm text-gray-600">Style</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="style in styleOptions" :key="style.id"
                                        @click="formOptions.style = style.id"
                                        class="p-3 border rounded-lg text-center transition-all" :class="formOptions.style === style.id
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-gray-200 hover:border-gray-300'">
                                        <span class="text-sm font-medium">{{ style.name }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Title & Description Toggle -->
                            <div class="flex gap-6 mb-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="formOptions.showTitle"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
                                    <span class="text-sm text-gray-700">Titel anzeigen</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="formOptions.showDescription"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
                                    <span class="text-sm text-gray-700">Beschreibung</span>
                                </label>
                            </div>

                            <!-- Text Inputs -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Button Text</label>
                                    <input v-model="formOptions.submitText" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Erfolgs-Nachricht</label>
                                    <input v-model="formOptions.successMessage" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Fehler-Nachricht</label>
                                    <input v-model="formOptions.errorMessage" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                            </div>

                            <!-- Preview Info -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Zusammenfassung</h5>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Form: <strong>{{ selectedForm.name }}</strong></li>
                                    <li>• Projekt: <strong>{{ ccProjectName }}</strong></li>
                                    <li>• {{ selectedFields.length }} von {{ selectedForm.fields.length }} Feldern</li>
                                    <li>• Style: <strong>{{styleOptions.find(s => s.id === formOptions.style)?.name
                                            }}</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 p-4 bg-gray-50 flex justify-end gap-3">
                <button @click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Abbrechen
                </button>
                <button v-if="selectedForm" @click="generateForm" :disabled="isLoading || selectedFields.length === 0"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Form einfügen
                </button>
            </div>
        </div>
    </Modal>
</template>
