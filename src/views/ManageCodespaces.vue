<template>
  <ion-page>
  <!--  <ion-header>
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
    </ion-header>-->

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
               <!-- <ion-chip :color="getLanguageColor(codespace.language)">
                  {{ codespace.language }}
                </ion-chip>
                <ion-chip color="medium">
                  {{ codespace.template }}
                </ion-chip>-->
                <!-- Connection status chips -->
                <ion-chip v-if="codespace.connections?.github" color="dark" size="small">
                  <ion-icon name="logo-github" size="small"></ion-icon>
                  <ion-label>GitHub</ion-label>
                </ion-chip>
                <ion-chip v-if="codespace.connections?.vercel" color="primary">
                  <ion-icon name="triangle" size="small"></ion-icon>
                  <ion-label>Vercel</ion-label>
                </ion-chip>
                <ion-chip v-if="codespace.connections?.domain" color="success">
                  <ion-icon name="globe" size="small"></ion-icon>
                  <ion-label>Domain</ion-label><!--{{ codespace.connections.domain.is_main ? 'Main Domain' : 'Subdomain' }}-->
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
              <ion-button fill="clear" size="small" @click="openSettings(codespace)">
                <ion-icon name="settings-outline" slot="icon-only"></ion-icon>
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

            <!-- Auto-create options only for new codespaces -->
            <div v-if="!editingCodespace" class="auto-create-section">
              <ion-item>
                <ion-checkbox v-model="formData.createGithubRepo" slot="start"></ion-checkbox>
                <ion-label>Automatisch GitHub Repository erstellen</ion-label>
              </ion-item>
              
              <ion-item>
                <ion-checkbox v-model="formData.createVercelProject" slot="start"></ion-checkbox>
                <ion-label>Automatisch Vercel Projekt erstellen</ion-label>
              </ion-item>
              
              <ion-note color="medium" class="ion-margin-start" style="font-size:0.9em;">
                Vercel Projekt benötigt ein GitHub Repository
              </ion-note>
            </div>

            <div class="modal-actions">
              <ion-button expand="block" @click="saveCodespace" :disabled="!formData.name">
                {{ editingCodespace ? 'Aktualisieren' : 'Erstellen' }}
              </ion-button>
            </div>
          </div>
        </ion-content>
      </ion-modal>

      <!-- Settings Modal -->
      <ion-modal :is-open="showSettingsModal" @did-dismiss="closeSettingsModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>Codespace Einstellungen</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeSettingsModal">
                <ion-icon name="close-outline"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content>
          <div class="settings-content" v-if="selectedCodespace">
            <div class="settings-header">
              <h2>{{ selectedCodespace.name }}</h2>
              <p>Verwalten Sie GitHub und Vercel Verbindungen</p>
            </div>

            <!-- GitHub Section -->
            <div class="connection-section">
              <div class="section-header">
                <ion-icon name="logo-github"></ion-icon>
                <h3>GitHub Repository</h3>
              </div>
              
              <div v-if="connections.github" class="connected-item">
                <div class="connection-info">
                  <h4>{{ connections.github.repo_full_name }}</h4>
                  <p>Repository ID: {{ connections.github.repo_id }}</p>
                </div>
                <ion-button fill="clear" color="danger" @click="disconnectGithub">
                  <ion-icon name="unlink-outline" slot="start"></ion-icon>
                  Trennen
                </ion-button>
              </div>
              
              <div v-else class="not-connected">
                <p>Kein GitHub Repository verbunden</p>
                <div class="connection-actions">
                  <ion-button @click="createGithubRepo" color="primary">
                    <ion-icon name="add-outline" slot="start"></ion-icon>
                    Neues Repo erstellen
                  </ion-button>
                  <ion-button fill="outline" @click="showGithubRepos">
                    <ion-icon name="link-outline" slot="start"></ion-icon>
                    Vorhandenes verbinden
                  </ion-button>
                </div>
              </div>
            </div>

            <!-- Vercel Section -->
            <div class="connection-section">
              <div class="section-header">
                <ion-icon name="triangle-outline"></ion-icon>
                <h3>Vercel Projekt</h3>
              </div>
              
              <div v-if="connections.vercel" class="connected-item">
                <div class="connection-info">
                  <h4>{{ connections.vercel.vercel_project_name }}</h4>
                  <p>Projekt ID: {{ connections.vercel.vercel_project_id }}</p>
                </div>
                <ion-button fill="clear" color="danger" @click="disconnectVercel">
                  <ion-icon name="unlink-outline" slot="start"></ion-icon>
                  Trennen
                </ion-button>
              </div>
              
              <div v-else class="not-connected">
                <p>Kein Vercel Projekt verbunden</p>
                <div class="connection-actions">
                  <ion-button 
                    @click="createVercelProject" 
                    color="primary"
                    :disabled="!connections.github"
                  >
                    <ion-icon name="add-outline" slot="start"></ion-icon>
                    Neues Projekt erstellen
                  </ion-button>
                  <ion-button fill="outline" @click="showVercelProjects">
                    <ion-icon name="link-outline" slot="start"></ion-icon>
                    Vorhandenes verbinden
                  </ion-button>
                </div>
                <ion-note v-if="!connections.github" color="warning">
                  GitHub Repository benötigt für Vercel Projekt
                </ion-note>
              </div>
            </div>

            <!-- Domain Section -->
            <div class="connection-section">
              <div class="section-header">
                <ion-icon name="globe-outline"></ion-icon>
                <h3>Domain</h3>
              </div>
              
              <div v-if="connections.domain" class="connected-item">
                <div class="connection-info">
                  <h4>{{ connections.domain.domain }}</h4>
                  <p v-if="connections.domain.is_main">Haupt-Domain</p>
                  <p v-else>Subdomain</p>
                </div>
                <ion-button fill="clear" color="danger" @click="disconnectDomain">
                  <ion-icon name="unlink-outline" slot="start"></ion-icon>
                  Trennen
                </ion-button>
              </div>
              
              <div v-else class="not-connected">
                <p>Keine Domain verbunden</p>
                <div v-if="domainInfo" class="domain-config">
                  <div class="domain-option">
                    <ion-radio-group v-model="domainType">
                      <ion-item>
                        <ion-radio 
                          slot="start" 
                          value="subdomain"
                          :disabled="!connections.vercel"
                        ></ion-radio>
                        <ion-label>
                          <h3>Subdomain</h3>
                          <p>{{ domainInput || 'subdomain' }}.{{ domainInfo.base_domain }}</p>
                        </ion-label>
                      </ion-item>
                      
                      <ion-item>
                        <ion-radio 
                          slot="start" 
                          value="main"
                          :disabled="!connections.vercel || domainInfo.main_domain_taken"
                        ></ion-radio>
                        <ion-label>
                          <h3>Haupt-Domain {{ domainInfo.main_domain_taken ? '(vergeben)' : '' }}</h3>
                          <p>{{ domainInfo.base_domain }}</p>
                          <p v-if="domainInfo.main_domain_taken" style="color: var(--ion-color-warning)">
                            Verwendet von: {{ domainInfo.main_domain_codespace }}
                          </p>
                        </ion-label>
                      </ion-item>
                    </ion-radio-group>
                  </div>
                  
                  <div v-if="domainType === 'subdomain'" class="subdomain-input">
                    <ion-item>
                      <ion-label position="stacked">Subdomain</ion-label>
                      <ion-input 
                        v-model="domainInput" 
                        placeholder="z.B. api, admin, staging"
                        pattern="[a-z0-9-]+"
                      ></ion-input>
                    </ion-item>
                  </div>
                  
                  <div class="connection-actions">
                    <ion-button 
                      @click="connectDomain" 
                      color="primary"
                      :disabled="!connections.vercel || (domainType === 'subdomain' && (!domainInput || domainInput.length < 2)) || (domainType === 'main' && domainInfo.main_domain_taken)"
                    >
                      <ion-icon name="link-outline" slot="start"></ion-icon>
                      Domain verbinden
                    </ion-button>
                  </div>
                </div>
                
                <div v-else-if="loadingDomainInfo" class="loading-container">
                  <ion-spinner name="circular"></ion-spinner>
                  <p>Domain-Informationen werden geladen...</p>
                </div>
                
                <div v-else>
                  <ion-note color="warning">
                    Vercel Projekt benötigt für Domain-Konfiguration
                  </ion-note>
                </div>
              </div>
            </div>
          </div>
        </ion-content>
      </ion-modal>

      <!-- GitHub Repos Selection Modal -->
      <ion-modal :is-open="showGithubModal" @did-dismiss="closeGithubModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>GitHub Repository auswählen</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeGithubModal">
                <ion-icon name="close-outline"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content>
          <div v-if="loadingGithubRepos" class="loading-container">
            <ion-spinner name="circular"></ion-spinner>
            <p>Repositories werden geladen...</p>
          </div>
          
          <div v-else>
            <ion-list>
              <ion-item 
                v-for="repo in githubRepos" 
                :key="repo.id"
                button
                @click="connectGithubRepo(repo)"
              >
                <ion-label>
                  <h3>{{ repo.full_name }}</h3>
                  <p>{{ repo.description || 'Keine Beschreibung' }}</p>
                </ion-label>
                <ion-icon 
                  :name="repo.private ? 'lock-closed-outline' : 'globe-outline'" 
                  slot="end"
                ></ion-icon>
              </ion-item>
            </ion-list>
          </div>
        </ion-content>
      </ion-modal>

      <!-- Vercel Projects Selection Modal -->
      <ion-modal :is-open="showVercelModal" @did-dismiss="closeVercelModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>Vercel Projekt auswählen</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeVercelModal">
                <ion-icon name="close-outline"></ion-icon>
              </ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content>
          <div v-if="loadingVercelProjects" class="loading-container">
            <ion-spinner name="circular"></ion-spinner>
            <p>Projekte werden geladen...</p>
          </div>
          
          <div v-else>
            <ion-list>
              <ion-item 
                v-for="project in vercelProjects" 
                :key="project.id"
                button
                @click="connectVercelProject(project)"
              >
                <ion-label>
                  <h3>{{ project.name }}</h3>
                  <p>{{ project.framework || 'Framework unbekannt' }}</p>
                </ion-label>
              </ion-item>
            </ion-list>
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
  IonIcon, IonSpinner, IonChip, IonModal, IonItem, IonLabel,
  IonInput, IonTextarea, IonSelect, IonSelectOption, IonCheckbox, IonNote, IonList,
  IonRadioGroup, IonRadio, alertController
} from '@ionic/vue'
import axios from 'axios'
import qs from 'qs'
import { ToastService } from '@/services/ToastService'

const route = useRoute()
const toast = ToastService

const codespaces = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingCodespace = ref(null)

// Settings Modal
const showSettingsModal = ref(false)
const selectedCodespace = ref(null)
const connections = ref({ github: null, vercel: null, domain: null })

// Domain
const domainType = ref('subdomain') // 'subdomain' or 'main'
const domainInput = ref('')
const domainInfo = ref(null)
const loadingDomainInfo = ref(false)

// GitHub Modal
const showGithubModal = ref(false)
const githubRepos = ref([])
const loadingGithubRepos = ref(false)

// Vercel Modal  
const showVercelModal = ref(false)
const vercelProjects = ref([])
const loadingVercelProjects = ref(false)

const formData = ref({
  name: '',
  description: '',
  language: 'javascript',
  template: 'default',
  icon: 'code-outline',
  createGithubRepo: false,
  createVercelProject: false
})

const loadCodespaces = async () => {
  try {
    loading.value = true
    const project = route.params.project
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('project_codespaces.php', qs.stringify({
      getCodespaces: true,
      project: project
    }))

    if (response.data.success) {
      codespaces.value = response.data.codespaces
      
      // Load connections for each codespace
      for (const codespace of codespaces.value) {
        try {
          const connectionsResponse = await axios.post('codespace_connections.php', qs.stringify({
            action: 'get_all_connections',
            codespace_id: codespace.id,
            user_id: user.userID
          }))
          
          codespace.connections = {
            github: connectionsResponse.data.github,
            vercel: connectionsResponse.data.vercel,
            domain: connectionsResponse.data.domain
          }
        } catch (error) {
          console.error(`Error loading connections for codespace ${codespace.id}:`, error)
          codespace.connections = { github: null, vercel: null, domain: null }
        }
      }
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

const createNewCodespace = () => {
  editingCodespace.value = null
  formData.value = {
    name: '',
    description: '',
    language: 'javascript',
    template: 'default',
    icon: 'code-outline',
    createGithubRepo: false,
    createVercelProject: false
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
    icon: codespace.icon,
    createGithubRepo: false,
    createVercelProject: false
  }
  showModal.value = true
}

const saveCodespace = async () => {
  try {
    const project = route.params.project

    if (editingCodespace.value) {
      // Update existing codespace
      await axios.post('project_codespaces.php', {
        updateCodespace: true,
        codespaceID: editingCodespace.value.id,
        name: formData.value.name,
        description: formData.value.description,
        language: formData.value.language,
        template: formData.value.template,
        icon: formData.value.icon
      })
      toast.success('Codespace aktualisiert!')
    } else {
      // Create new codespace
      const data = {
        createCodespace: true,
        project: project,
        name: formData.value.name,
        description: formData.value.description,
        language: formData.value.language,
        template: formData.value.template,
        icon: formData.value.icon
      }

      // Add auto-create options if selected
      if (formData.value.createGithubRepo) {
        data.createGithubRepo = 'true'
      }
      if (formData.value.createVercelProject) {
        data.createVercelProject = 'true'
      }

      await axios.post('project_codespaces.php', qs.stringify(data))
      toast.success('Codespace erstellt!')
      
      if (formData.value.createGithubRepo || formData.value.createVercelProject) {
        toast.success('GitHub und/oder Vercel Projekte werden erstellt...')
      }
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
        handler: async () => {
          try {
            await axios.post('project_codespaces.php', qs.stringify({
              deleteCodespace: true,
              codespaceID: codespace.id
            }))
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

// Settings Modal Functions
const openSettings = async (codespace) => {
  selectedCodespace.value = codespace
  domainType.value = 'subdomain'
  domainInput.value = ''
  domainInfo.value = null
  
  await loadConnections(codespace.id)
  await loadDomainInfo(codespace.id)
  showSettingsModal.value = true
}

const closeSettingsModal = () => {
  showSettingsModal.value = false
  selectedCodespace.value = null
  connections.value = { github: null, vercel: null, domain: null }
  domainInfo.value = null
  domainInput.value = ''
}

const loadConnections = async (codespaceId) => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'get_all_connections',
      codespace_id: codespaceId,
      user_id: user.userID
    }))

    if (response.data.github || response.data.vercel || response.data.domain) {
      connections.value = {
        github: response.data.github,
        vercel: response.data.vercel,
        domain: response.data.domain
      }
    }
  } catch (error) {
    console.error('Error loading connections:', error)
  }
}

// GitHub Functions
const createGithubRepo = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'create_and_connect_github',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID
    }))

    if (response.data.success) {
      toast.success('GitHub Repository erstellt und verbunden!')
      await loadConnections(selectedCodespace.value.id)
    } else {
      toast.error(response.data.error || 'Fehler beim Erstellen des GitHub Repos')
    }
  } catch (error) {
    console.error('Error creating GitHub repo:', error)
    toast.error('Verbindungsfehler')
  }
}

const showGithubRepos = async () => {
  try {
    loadingGithubRepos.value = true
    showGithubModal.value = true
    
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.get(`github_repos.php?user_id=${user.userID}`)
    githubRepos.value = response.data
  } catch (error) {
    console.error('Error loading GitHub repos:', error)
    toast.error('Fehler beim Laden der GitHub Repositories')
  } finally {
    loadingGithubRepos.value = false
  }
}

const connectGithubRepo = async (repo) => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'connect_github',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID,
      repo: JSON.stringify(repo)
    }))

    if (response.data.success) {
      toast.success('GitHub Repository verbunden!')
      await loadConnections(selectedCodespace.value.id)
      closeGithubModal()
    } else {
      toast.error(response.data.error || 'Fehler beim Verbinden')
    }
  } catch (error) {
    console.error('Error connecting GitHub repo:', error)
    toast.error('Verbindungsfehler')
  }
}

const disconnectGithub = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'disconnect_github',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID
    }))

    if (response.data.success) {
      toast.success('GitHub Repository getrennt!')
      await loadConnections(selectedCodespace.value.id)
    } else {
      toast.error('Fehler beim Trennen')
    }
  } catch (error) {
    console.error('Error disconnecting GitHub:', error)
    toast.error('Verbindungsfehler')
  }
}

const closeGithubModal = () => {
  showGithubModal.value = false
  githubRepos.value = []
}

// Vercel Functions
const createVercelProject = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'create_and_connect_vercel',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID
    }))

    if (response.data.success) {
      toast.success('Vercel Projekt erstellt und verbunden!')
      await loadConnections(selectedCodespace.value.id)
    } else {
      toast.error(response.data.error || 'Fehler beim Erstellen des Vercel Projekts')
    }
  } catch (error) {
    console.error('Error creating Vercel project:', error)
    toast.error('Verbindungsfehler')
  }
}

const showVercelProjects = async () => {
  try {
    loadingVercelProjects.value = true
    showVercelModal.value = true
    
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.get(`vercel_projects.php?user_id=${user.userID}`)
    vercelProjects.value = response.data.projects || []
  } catch (error) {
    console.error('Error loading Vercel projects:', error)
    toast.error('Fehler beim Laden der Vercel Projekte')
  } finally {
    loadingVercelProjects.value = false
  }
}

const connectVercelProject = async (project) => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'connect_vercel',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID,
      vercel_project_id: project.id,
      vercel_project_name: project.name
    }))

    if (response.data.success) {
      toast.success('Vercel Projekt verbunden!')
      await loadConnections(selectedCodespace.value.id)
      closeVercelModal()
    } else {
      toast.error(response.data.error || 'Fehler beim Verbinden')
    }
  } catch (error) {
    console.error('Error connecting Vercel project:', error)
    toast.error('Verbindungsfehler')
  }
}

const disconnectVercel = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'disconnect_vercel',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID
    }))

    if (response.data.success) {
      toast.success('Vercel Projekt getrennt!')
      await loadConnections(selectedCodespace.value.id)
    } else {
      toast.error('Fehler beim Trennen')
    }
  } catch (error) {
    console.error('Error disconnecting Vercel:', error)
    toast.error('Verbindungsfehler')
  }
}

const closeVercelModal = () => {
  showVercelModal.value = false
  vercelProjects.value = []
}

// Domain Functions
const loadDomainInfo = async (codespaceId) => {
  try {
    loadingDomainInfo.value = true
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'get_project_domain_info',
      codespace_id: codespaceId,
      user_id: user.userID
    }))

    if (response.data.base_domain) {
      domainInfo.value = response.data
    } else {
      domainInfo.value = null
    }
  } catch (error) {
    console.error('Error loading domain info:', error)
    domainInfo.value = null
  } finally {
    loadingDomainInfo.value = false
  }
}

const connectDomain = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const data = {
      action: 'connect_domain',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID,
      is_main: domainType.value === 'main' ? 'true' : 'false'
    }
    
    // Nur Subdomain hinzufügen wenn nicht Haupt-Domain
    if (domainType.value === 'subdomain') {
      data.subdomain = domainInput.value
    }
    
    const response = await axios.post('codespace_connections.php', qs.stringify(data))

    if (response.data.success) {
      toast.success('Domain erfolgreich verbunden!')
      await loadConnections(selectedCodespace.value.id)
      await loadDomainInfo(selectedCodespace.value.id)
      domainInput.value = ''
    } else {
      toast.error(response.data.error || 'Fehler beim Verbinden der Domain')
    }
  } catch (error) {
    console.error('Error connecting domain:', error)
    toast.error('Verbindungsfehler')
  }
}

const disconnectDomain = async () => {
  try {
    const { getUserData } = await import('@/userData')
    const user = getUserData()
    
    const response = await axios.post('codespace_connections.php', qs.stringify({
      action: 'disconnect_domain',
      codespace_id: selectedCodespace.value.id,
      user_id: user.userID
    }))

    if (response.data.success) {
      toast.success('Domain getrennt!')
      await loadConnections(selectedCodespace.value.id)
      await loadDomainInfo(selectedCodespace.value.id)
    } else {
      toast.error('Fehler beim Trennen der Domain')
    }
  } catch (error) {
    console.error('Error disconnecting domain:', error)
    toast.error('Verbindungsfehler')
  }
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
  color: var(--ion-color-medium);
  margin-top: 16px;
}

.auto-create-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--ion-color-light-shade);
}

.settings-content {
  padding: 20px;
}

.settings-header {
  margin-bottom: 30px;
  text-align: center;
}

.settings-header h2 {
  margin: 0 0 8px 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.settings-header p {
  margin: 0;
  color: var(--ion-color-medium);
}

.connection-section {
  margin-bottom: 30px;
  padding: 20px;
  background: var(--ion-color-light);
  border-radius: 12px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.section-header ion-icon {
  font-size: 1.5rem;
  color: var(--ion-color-primary);
}

.section-header h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
}

.connected-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--ion-color-success-tint);
  border-radius: 8px;
  border-left: 4px solid var(--ion-color-success);
}

.connection-info h4 {
  margin: 0 0 4px 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--ion-color-success-shade);
}

.connection-info p {
  margin: 0;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.not-connected {
  text-align: center;
  padding: 20px;
  color: var(--ion-color-medium);
}

.connection-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 16px;
}

@media (max-width: 576px) {
  .connection-actions {
    flex-direction: column;
  }
  
  .connected-item {
    flex-direction: column;
    gap: 12px;
    text-align: center;
  }
}

.loading-container p {
  color: var(--ion-color-medium);
  margin-top: 16px;
}

.domain-config {
  width: 100%;
}

.domain-option {
  margin-bottom: 16px;
}

.subdomain-input {
  margin-bottom: 16px;
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
