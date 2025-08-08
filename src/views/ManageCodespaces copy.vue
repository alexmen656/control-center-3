<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="'/project/' + $route.params.project + '/'"></ion-back-button>
        </ion-buttons>
        <ion-title>Codespaces verwalten</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="createNewCodespace">
            <ion-icon name="add-outline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <div class="codespaces-container">
        <!-- Header Section -->
        <div class="header-section">
          <h1>Codespaces</h1>
          <p>Verwalten Sie Ihre Entwicklungsumgebungen für dieses Projekt.</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
          <ion-spinner name="circular"></ion-spinner>
          <p>Codespaces werden geladen...</p>
        </div>

        <!-- Codespaces Grid -->
        <div v-else class="codespaces-grid">
          <div 
            v-for="codespace in codespaces" 
            :key="codespace.id"
            class="codespace-card"
            :class="{ 'inactive': codespace.status !== 'active' }"
          >
            <div class="card-header">
              <ion-icon :name="codespace.icon" class="codespace-icon"></ion-icon>
              <div class="codespace-info">
                <h3>{{ codespace.name }}</h3>
                <p>{{ codespace.description || 'Keine Beschreibung' }}</p>
              </div>
              <div class="status-indicator" :class="codespace.status"></div>
            </div>

            <div class="card-body">
              <div class="metadata">
                <ion-chip :color="getLanguageColor(codespace.language)">
                  {{ codespace.language }}
                </ion-chip>
                <ion-chip color="medium">
                  {{ codespace.template }}
                </ion-chip>
              </div>
              
              <div class="timestamps">
                <small>Erstellt: {{ formatDate(codespace.created_at) }}</small>
                <small>Geändert: {{ formatDate(codespace.updated_at) }}</small>
              </div>
            </div>

            <div class="card-actions">
              <ion-button 
                fill="clear" 
                size="small"
                :router-link="'/project/' + $route.params.project + '/monaco/' + codespace.slug"
              >
                <ion-icon name="code-outline" slot="icon-only"></ion-icon>
              </ion-button>
              <ion-button fill="clear" size="small" @click="editCodespace(codespace)">
                <ion-icon name="pencil-outline" slot="icon-only"></ion-icon>
              </ion-button>
              <ion-button fill="clear" size="small" color="danger" @click="deleteCodespace(codespace)">
                <ion-icon name="trash-outline" slot="icon-only"></ion-icon>
              </ion-button>
            </div>
          </div>

          <!-- Add New Card -->
          <div class="codespace-card add-new" @click="createNewCodespace">
            <div class="add-new-content">
              <ion-icon name="add-outline" class="add-icon"></ion-icon>
              <h3>Neuer Codespace</h3>
              <p>Erstellen Sie eine neue Entwicklungsumgebung</p>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && codespaces.length === 0" class="empty-state">
          <ion-icon name="code-slash-outline" class="empty-icon"></ion-icon>
          <h2>Keine Codespaces vorhanden</h2>
          <p>Erstellen Sie Ihren ersten Codespace, um mit der Entwicklung zu beginnen.</p>
          <ion-button @click="createNewCodespace" class="create-button">
            <ion-icon name="add-outline" slot="start"></ion-icon>
            Codespace erstellen
          </ion-button>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <ion-modal :is-open="showModal" @did-dismiss="closeModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ editingCodespace ? 'Codespace bearbeiten' : 'Neuer Codespace' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeModal">
                <ion-icon name="close-outline"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content>
          <div class="modal-content">
            <ion-item>
              <ion-label position="stacked">Name *</ion-label>
              <ion-input v-model="formData.name" placeholder="z.B. Frontend App"></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Beschreibung</ion-label>
              <ion-textarea v-model="formData.description" placeholder="Beschreibung des Codespaces"></ion-textarea>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Sprache</ion-label>
              <ion-select v-model="formData.language" placeholder="Hauptsprache auswählen">
                <ion-select-option value="javascript">JavaScript</ion-select-option>
                <ion-select-option value="typescript">TypeScript</ion-select-option>
                <ion-select-option value="python">Python</ion-select-option>
                <ion-select-option value="php">PHP</ion-select-option>
                <ion-select-option value="html">HTML</ion-select-option>
                <ion-select-option value="css">CSS</ion-select-option>
                <ion-select-option value="vue">Vue.js</ion-select-option>
                <ion-select-option value="react">React</ion-select-option>
                <ion-select-option value="angular">Angular</ion-select-option>
                <ion-select-option value="other">Andere</ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Template</ion-label>
              <ion-select v-model="formData.template" placeholder="Template auswählen">
                <ion-select-option value="default">Standard</ion-select-option>
                <ion-select-option value="web-app">Web App</ion-select-option>
                <ion-select-option value="api">API</ion-select-option>
                <ion-select-option value="library">Library</ion-select-option>
                <ion-select-option value="static">Static Site</ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Icon</ion-label>
              <ion-select v-model="formData.icon" placeholder="Icon auswählen">
                <ion-select-option value="code-outline">Code</ion-select-option>
                <ion-select-option value="globe-outline">Web</ion-select-option>
                <ion-select-option value="phone-portrait-outline">Mobile</ion-select-option>
                <ion-select-option value="server-outline">Server</ion-select-option>
                <ion-select-option value="library-outline">Library</ion-select-option>
                <ion-select-option value="build-outline">Build</ion-select-option>
              </ion-select>
            </ion-item>

            <div class="modal-actions">
              <ion-button expand="block" @click="saveCodespace" :disabled="!formData.name">
                {{ editingCodespace ? 'Aktualisieren' : 'Erstellen' }}
              </ion-button>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton,
  IonBackButton, IonIcon, IonSpinner, IonChip, IonModal, IonItem, IonLabel,
  IonInput, IonTextarea, IonSelect, IonSelectOption, alertController
} from '@ionic/vue'
import axios from 'axios'
import { ToastService } from '@/services/ToastService'

const route = useRoute()
const toast = ToastService

const codespaces = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingCodespace = ref(null)

const formData = ref({
  name: '',
  description: '',
  language: 'javascript',
  template: 'default',
  icon: 'code-outline'
})

const loadCodespaces = async () => {
  try {
    loading.value = true
    const projectID = await getProjectID()
    const response = await axios.post('project_codespaces.php', {
      getCodespaces: true,
      projectID: projectID
    })
    
    if (response.data.success) {
      codespaces.value = response.data.codespaces
    } else {
      toast.error('Fehler beim Laden der Codespaces')
    }
  } catch (error) {
    console.error('Error loading codespaces:', error)
    toast.error('Verbindungsfehler')
  } finally {
    loading.value = false
  }
}

const getProjectID = async () => {
  const response = await axios.post('projects.php', {
    getProjectInfo: true,
    project: route.params.project
  })
  return response.data.projectID
}

const createNewCodespace = () => {
  editingCodespace.value = null
  formData.value = {
    name: '',
    description: '',
    language: 'javascript',
    template: 'default',
    icon: 'code-outline'
  }
  showModal.value = true
}

const editCodespace = (codespace) => {
  editingCodespace.value = codespace
  formData.value = {
    name: codespace.name,
    description: codespace.description,
    language: codespace.language,
    template: codespace.template,
    icon: codespace.icon
  }
  showModal.value = true
}

const saveCodespace = async () => {
  try {
    const projectID = await getProjectID()
    
    if (editingCodespace.value) {
      // Update existing codespace
      await axios.post('project_codespaces.php', {
        updateCodespace: true,
        codespaceID: editingCodespace.value.id,
        ...formData.value
      })
      toast.success('Codespace aktualisiert!')
    } else {
      // Create new codespace
      await axios.post('project_codespaces.php', {
        createCodespace: true,
        projectID: projectID,
        ...formData.value
      })
      toast.success('Codespace erstellt!')
    }
    
    closeModal()
    loadCodespaces()
  } catch (error) {
    console.error('Error saving codespace:', error)
    toast.error('Fehler beim Speichern')
  }
}

const deleteCodespace = async (codespace) => {
  const alert = await alertController.create({
    header: 'Codespace löschen',
    message: `Möchten Sie "${codespace.name}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
    buttons: [
      {
        text: 'Abbrechen',
        role: 'cancel'
      },
      {
        text: 'Löschen',
        role: 'destructive',
        handler: async () => {
          try {
            await axios.post('project_codespaces.php', {
              deleteCodespace: true,
              codespaceID: codespace.id
            })
            toast.success('Codespace gelöscht!')
            loadCodespaces()
          } catch (error) {
            console.error('Error deleting codespace:', error)
            toast.error('Fehler beim Löschen')
          }
        }
      }
    ]
  })
  
  await alert.present()
}

const closeModal = () => {
  showModal.value = false
  editingCodespace.value = null
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('de-DE')
}

const getLanguageColor = (language) => {
  const colors = {
    javascript: 'warning',
    typescript: 'primary',
    python: 'success',
    php: 'secondary',
    html: 'danger',
    css: 'tertiary',
    vue: 'success',
    react: 'primary',
    angular: 'danger'
  }
  return colors[language] || 'medium'
}

onMounted(() => {
  loadCodespaces()
})
</script>

<style scoped>
.codespaces-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.header-section {
  margin-bottom: 30px;
}

.header-section h1 {
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 8px;
}

.header-section p {
  color: var(--ion-color-medium);
  margin: 0;
}

.loading-container {
  text-align: center;
  padding: 60px 20px;
}

.loading-container p {
  margin-top: 16px;
  color: var(--ion-color-medium);
}

.codespaces-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.codespace-card {
  background: var(--ion-color-light);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--ion-color-light-shade);
  transition: all 0.2s ease;
  position: relative;
}

.codespace-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.codespace-card.inactive {
  opacity: 0.6;
}

.card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.codespace-icon {
  font-size: 24px;
  color: var(--ion-color-primary);
  margin-top: 4px;
}

.codespace-info {
  flex: 1;
}

.codespace-info h3 {
  margin: 0 0 4px 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.codespace-info p {
  margin: 0;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-top: 8px;
}

.status-indicator.active {
  background-color: var(--ion-color-success);
}

.status-indicator.inactive {
  background-color: var(--ion-color-medium);
}

.card-body {
  margin-bottom: 16px;
}

.metadata {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.timestamps {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.timestamps small {
  color: var(--ion-color-medium-shade);
  font-size: 0.8rem;
}

.card-actions {
  display: flex;
  justify-content: flex-end;
  gap: 4px;
}

.add-new {
  border: 2px dashed var(--ion-color-medium);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  min-height: 200px;
}

.add-new:hover {
  border-color: var(--ion-color-primary);
  background: var(--ion-color-primary-tint);
}

.add-new-content {
  text-align: center;
}

.add-icon {
  font-size: 48px;
  color: var(--ion-color-medium);
  margin-bottom: 12px;
}

.add-new:hover .add-icon {
  color: var(--ion-color-primary);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 64px;
  color: var(--ion-color-medium);
  margin-bottom: 20px;
}

.empty-state h2 {
  margin-bottom: 8px;
  color: var(--ion-color-dark);
}

.empty-state p {
  color: var(--ion-color-medium);
  margin-bottom: 24px;
}

.modal-content {
  padding: 20px;
}

.modal-actions {
  margin-top: 24px;
}
</style>
