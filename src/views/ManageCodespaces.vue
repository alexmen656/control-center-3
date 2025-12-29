<template>
  <ion-page>
    <ion-content class="modern-content">
      <SiteTitle icon="code-outline" title="Codespaces" />

      <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1>Codespaces</h1>
            <p>Verwalten Sie Ihre Entwicklungsumgebungen für dieses Projekt</p>
          </div>
          <div class="header-actions">
            <button class="action-btn secondary" @click="loadCodespaces">
              <ion-icon name="refresh-outline"></ion-icon>
              Aktualisieren
            </button>
            <button class="action-btn primary" @click="createNewCodespace">
              <ion-icon name="add-outline"></ion-icon>
              Neuer Codespace
            </button>
          </div>
        </div>

        <!-- Codespaces Table -->
        <div class="data-card">
          <div class="card-header">
            <div class="header-left">
              <h3>Alle Codespaces</h3>
              <span class="entry-count">{{ codespaces.length }} Codespace{{ codespaces.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="search-box">
              <ion-icon name="search-outline"></ion-icon>
              <input type="text" placeholder="Codespaces durchsuchen..." v-model="searchTerm">
            </div>
          </div>

          <div class="table-wrapper">
            <!-- Loading State -->
            <div v-if="loading" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Codespaces werden geladen...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredCodespaces.length === 0" class="no-data-state">
              <div class="no-data-content">
                <ion-icon name="code-slash-outline" class="no-data-icon"></ion-icon>
                <h4>Keine Codespaces gefunden</h4>
                <p>{{ searchTerm ? 'Keine Codespaces entsprechen Ihrer Suche.' : 'Erstellen Sie Ihren ersten Codespace, um mit der Entwicklung zu beginnen.' }}</p>
              </div>
            </div>

            <!-- Table -->
            <div v-else class="modern-table">
              <!-- Table Header -->
              <div class="table-header">
                <div class="header-cell" @click="sortBy('name')">
                  <span class="header-text">Name</span>
                  <div class="sort-indicator">
                    <ion-icon v-if="sortColumn === 'name' && sortDirection === 'asc'" name="chevron-up-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else-if="sortColumn === 'name' && sortDirection === 'desc'" name="chevron-down-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else name="swap-vertical-outline" class="sort-default"></ion-icon>
                  </div>
                </div>
                <div class="header-cell">
                  <span class="header-text">Verbindungen</span>
                </div>
                <div class="header-cell" @click="sortBy('language')">
                  <span class="header-text">Sprache</span>
                  <div class="sort-indicator">
                    <ion-icon v-if="sortColumn === 'language' && sortDirection === 'asc'" name="chevron-up-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else-if="sortColumn === 'language' && sortDirection === 'desc'" name="chevron-down-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else name="swap-vertical-outline" class="sort-default"></ion-icon>
                  </div>
                </div>
                <div class="header-cell" @click="sortBy('created_at')">
                  <span class="header-text">Erstellt</span>
                  <div class="sort-indicator">
                    <ion-icon v-if="sortColumn === 'created_at' && sortDirection === 'asc'" name="chevron-up-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else-if="sortColumn === 'created_at' && sortDirection === 'desc'" name="chevron-down-outline" class="sort-active"></ion-icon>
                    <ion-icon v-else name="swap-vertical-outline" class="sort-default"></ion-icon>
                  </div>
                </div>
                <div class="header-cell actions-header">Aktionen</div>
              </div>

              <!-- Table Body -->
              <div class="table-body">
                <div v-for="codespace in filteredCodespaces" :key="codespace.id" class="table-row">
                  <!-- Name -->
                  <div class="table-cell cell-name">
                    <div class="codespace-name-cell">
                      <ion-icon :name="codespace.icon" class="codespace-icon"></ion-icon>
                      <div class="codespace-details">
                        <span class="name">{{ codespace.name }}</span>
                        <span class="description">{{ codespace.description || 'Keine Beschreibung' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Connections -->
                  <div class="table-cell cell-connections">
                    <div class="connections-chips">
                      <span v-if="codespace.connections?.github" class="connection-badge github">
                        <ion-icon name="logo-github"></ion-icon>
                        GitHub
                      </span>
                      <span v-if="codespace.connections?.vercel" class="connection-badge vercel">
                        <ion-icon name="triangle"></ion-icon>
                        Vercel
                      </span>
                      <span v-if="codespace.connections?.domain" class="connection-badge domain">
                        <ion-icon name="globe"></ion-icon>
                        Domain
                      </span>
                      <span v-if="!codespace.connections?.github && !codespace.connections?.vercel && !codespace.connections?.domain" class="no-connections">
                        Keine Verbindungen
                      </span>
                    </div>
                  </div>

                  <!-- Language -->
                  <div class="table-cell cell-language">
                    <span class="language-badge">{{ codespace.language }}</span>
                  </div>

                  <!-- Created Date -->
                  <div class="table-cell cell-date">
                    <span class="date-text">{{ formatDate(codespace.created_at) }}</span>
                  </div>

                  <!-- Actions -->
                  <div class="table-cell cell-actions">
                    <div class="action-buttons">
                      <button class="icon-btn open-btn" @click="$router.push('/project/' + $route.params.project + '/codespace/' + codespace.slug)">
                        <ion-icon name="code-outline"></ion-icon>
                      </button>
                      <button class="icon-btn settings-btn" @click="openSettings(codespace)">
                        <ion-icon name="settings-outline"></ion-icon>
                      </button>
                      <button class="icon-btn edit-btn" @click="editCodespace(codespace)">
                        <ion-icon name="pencil-outline"></ion-icon>
                      </button>
                      <button class="icon-btn transfer-btn" @click="openTransferModal(codespace)">
                        <ion-icon name="swap-horizontal-outline"></ion-icon>
                      </button>
                      <button class="icon-btn delete-btn" @click="deleteCodespace(codespace)">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="custom-modal-overlay" @click="closeModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>{{ editingCodespace ? 'Codespace bearbeiten' : 'Neuer Codespace' }}</h3>
            <button class="modal-close-btn" @click="closeModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div class="form-grid">
              <div class="form-group full-width">
                <label class="form-label">Name *</label>
                <input type="text" class="form-input" v-model="formData.name" placeholder="z.B. Frontend App">
              </div>

              <div class="form-group full-width">
                <label class="form-label">Beschreibung</label>
                <textarea class="form-input" v-model="formData.description" placeholder="Beschreibung des Codespaces" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Sprache</label>
                <select class="form-input" v-model="formData.language">
                  <option value="javascript">JavaScript</option>
                  <option value="typescript">TypeScript</option>
                  <option value="python">Python</option>
                  <option value="php">PHP</option>
                  <option value="html">HTML</option>
                  <option value="css">CSS</option>
                  <option value="vue">Vue.js</option>
                  <option value="react">React</option>
                  <option value="angular">Angular</option>
                  <option value="other">Andere</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Icon</label>
                <select class="form-input" v-model="formData.icon">
                  <option value="code-outline">Code</option>
                  <option value="globe-outline">Web</option>
                  <option value="phone-portrait-outline">Mobile</option>
                  <option value="server-outline">Server</option>
                  <option value="library-outline">Library</option>
                  <option value="build-outline">Build</option>
                </select>
              </div>

              <div class="form-group full-width">
                <label class="form-label">Template</label>
                <select class="form-input" v-model="formData.template">
                  <option v-for="template in availableTemplates" :key="template.id" :value="template.id">
                    {{ template.name }}
                  </option>
                </select>
              </div>

              <!-- Template Preview -->
              <div v-if="formData.template && getSelectedTemplate()" class="template-preview full-width">
                <div class="template-info">
                  <ion-icon :name="getSelectedTemplate().icon" class="template-icon"></ion-icon>
                  <div>
                    <h4>{{ getSelectedTemplate().name }}</h4>
                    <p>{{ getSelectedTemplate().description }}</p>
                  </div>
                </div>
              </div>

              <!-- Auto-create options only for new codespaces -->
              <div v-if="!editingCodespace" class="auto-create-section full-width">
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="formData.createGithubRepo">
                    <span>Automatisch GitHub Repository erstellen</span>
                  </label>
                </div>
                
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="formData.createVercelProject">
                    <span>Automatisch Vercel Projekt erstellen</span>
                  </label>
                </div>
                
                <p class="form-note">Vercel Projekt benötigt ein GitHub Repository</p>
              </div>
            </div>

            <div class="form-actions">
              <button class="action-btn secondary" @click="closeModal">
                Abbrechen
              </button>
              <button class="action-btn primary" @click="saveCodespace" :disabled="!formData.name">
                {{ editingCodespace ? 'Aktualisieren' : 'Erstellen' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Modal -->
      <div v-if="showSettingsModal" class="custom-modal-overlay" @click="closeSettingsModal">
        <div class="custom-modal-content large" @click.stop>
          <div class="custom-modal-header">
            <h3>Codespace Einstellungen</h3>
            <button class="modal-close-btn" @click="closeSettingsModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div v-if="selectedCodespace" class="settings-content">
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
                  <button class="action-btn danger" @click="disconnectGithub">
                    <ion-icon name="unlink-outline"></ion-icon>
                    Trennen
                  </button>
                </div>
                
                <div v-else class="not-connected">
                  <p>Kein GitHub Repository verbunden</p>
                  <div class="connection-actions">
                    <button class="action-btn primary" @click="createGithubRepo">
                      <ion-icon name="add-outline"></ion-icon>
                      Neues Repo erstellen
                    </button>
                    <button class="action-btn secondary" @click="showGithubRepos">
                      <ion-icon name="link-outline"></ion-icon>
                      Vorhandenes verbinden
                    </button>
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
                  <button class="action-btn danger" @click="disconnectVercel">
                    <ion-icon name="unlink-outline"></ion-icon>
                    Trennen
                  </button>
                </div>
                
                <div v-else class="not-connected">
                  <p>Kein Vercel Projekt verbunden</p>
                  <div class="connection-actions">
                    <button class="action-btn primary" @click="createVercelProject" :disabled="!connections.github">
                      <ion-icon name="add-outline"></ion-icon>
                      Neues Projekt erstellen
                    </button>
                    <button class="action-btn secondary" @click="showVercelProjects">
                      <ion-icon name="link-outline"></ion-icon>
                      Vorhandenes verbinden
                    </button>
                  </div>
                  <p v-if="!connections.github" class="form-note warning">
                    GitHub Repository benötigt für Vercel Projekt
                  </p>
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
                  <button class="action-btn danger" @click="disconnectDomain">
                    <ion-icon name="unlink-outline"></ion-icon>
                    Trennen
                  </button>
                </div>
                
                <div v-else class="not-connected">
                  <p>Keine Domain verbunden</p>
                  <div v-if="domainInfo" class="domain-config">
                    <div class="form-group full-width">
                      <div class="radio-group">
                        <label class="radio-label">
                          <input type="radio" v-model="domainType" value="subdomain" :disabled="!connections.vercel">
                          <span>
                            <strong>Subdomain</strong>
                            <small>{{ domainInput || 'subdomain' }}.{{ domainInfo.base_domain }}</small>
                          </span>
                        </label>
                        
                        <label class="radio-label">
                          <input type="radio" v-model="domainType" value="main" :disabled="!connections.vercel || domainInfo.main_domain_taken">
                          <span>
                            <strong>Haupt-Domain {{ domainInfo.main_domain_taken ? '(vergeben)' : '' }}</strong>
                            <small>{{ domainInfo.base_domain }}</small>
                            <small v-if="domainInfo.main_domain_taken" class="warning-text">
                              Verwendet von: {{ domainInfo.main_domain_codespace }}
                            </small>
                          </span>
                        </label>
                      </div>
                    </div>
                    
                    <div v-if="domainType === 'subdomain'" class="form-group full-width">
                      <label class="form-label">Subdomain</label>
                      <input type="text" class="form-input" v-model="domainInput" placeholder="z.B. api, admin, staging" pattern="[a-z0-9-]+">
                    </div>
                    
                    <div class="connection-actions">
                      <button class="action-btn primary" @click="connectDomain" :disabled="!connections.vercel || (domainType === 'subdomain' && (!domainInput || domainInput.length < 2)) || (domainType === 'main' && domainInfo.main_domain_taken)">
                        <ion-icon name="link-outline"></ion-icon>
                        Domain verbinden
                      </button>
                    </div>
                  </div>
                  
                  <div v-else-if="loadingDomainInfo" class="loading-container">
                    <ion-spinner name="circular"></ion-spinner>
                    <p>Domain-Informationen werden geladen...</p>
                  </div>
                  
                  <div v-else>
                    <p class="form-note warning">
                      Vercel Projekt benötigt für Domain-Konfiguration
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- GitHub Repos Selection Modal -->
      <div v-if="showGithubModal" class="custom-modal-overlay" @click="closeGithubModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>GitHub Repository auswählen</h3>
            <button class="modal-close-btn" @click="closeGithubModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div v-if="loadingGithubRepos" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Repositories werden geladen...</p>
            </div>
            
            <div v-else class="repo-list">
              <div v-for="repo in githubRepos" :key="repo.id" class="repo-item" @click="connectGithubRepo(repo)">
                <div class="repo-info">
                  <h4>{{ repo.full_name }}</h4>
                  <p>{{ repo.description || 'Keine Beschreibung' }}</p>
                </div>
                <ion-icon :name="repo.private ? 'lock-closed-outline' : 'globe-outline'" class="repo-icon"></ion-icon>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vercel Projects Selection Modal -->
      <div v-if="showVercelModal" class="custom-modal-overlay" @click="closeVercelModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Vercel Projekt auswählen</h3>
            <button class="modal-close-btn" @click="closeVercelModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div v-if="loadingVercelProjects" class="loading-state">
              <ion-icon name="sync-outline" class="loading-icon"></ion-icon>
              <p>Projekte werden geladen...</p>
            </div>
            
            <div v-else class="repo-list">
              <div v-for="project in vercelProjects" :key="project.id" class="repo-item" @click="connectVercelProject(project)">
                <div class="repo-info">
                  <h4>{{ project.name }}</h4>
                  <p>{{ project.framework || 'Framework unbekannt' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Transfer Modal -->
      <div v-if="showTransferModal" class="custom-modal-overlay" @click="closeTransferModal">
        <div class="custom-modal-content" @click.stop>
          <div class="custom-modal-header">
            <h3>Codespace übertragen</h3>
            <button class="modal-close-btn" @click="closeTransferModal">
              <ion-icon name="close-outline"></ion-icon>
            </button>
          </div>
          
          <div class="custom-modal-body">
            <div v-if="transferCodespace" class="transfer-content">
              <div class="transfer-header">
                <h2>{{ transferCodespace.name }}</h2>
                <p>Wählen Sie das Ziel-Projekt für die Übertragung</p>
              </div>

              <div class="transfer-info">
                <h4>Was wird übertragen?</h4>
                <ul class="transfer-list">
                  <li>
                    <ion-icon name="folder-outline"></ion-icon>
                    <span>Alle Dateien und Ordner</span>
                  </li>
                  <li v-if="transferCodespace.connections?.github">
                    <ion-icon name="logo-github"></ion-icon>
                    <span>GitHub Repository Verbindung</span>
                  </li>
                  <li v-if="transferCodespace.connections?.vercel">
                    <ion-icon name="triangle-outline"></ion-icon>
                    <span>Vercel Projekt Verbindung</span>
                  </li>
                  <li v-if="transferCodespace.connections?.domain">
                    <ion-icon name="globe-outline"></ion-icon>
                    <span>Domain Verbindung</span>
                  </li>
                </ul>
              </div>

              <div class="form-group full-width">
                <label class="form-label">Ziel-Projekt auswählen</label>
                <select class="form-input" v-model="selectedTargetProject">
                  <option value="">Projekt auswählen</option>
                  <option v-for="project in availableProjects" :key="project.id" :value="project.link">
                    {{ project.name }}
                  </option>
                </select>
              </div>

              <div class="checkbox-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="moveInsteadOfCopy">
                  <span>
                    <strong>Verschieben statt Kopieren</strong>
                    <small>Löscht den ursprünglichen Codespace nach dem Transfer</small>
                  </span>
                </label>
              </div>

              <div class="form-actions">
                <button class="action-btn secondary" @click="closeTransferModal">
                  Abbrechen
                </button>
                <button class="action-btn primary" @click="executeTransfer" :disabled="!selectedTargetProject || transferInProgress">
                  <ion-spinner v-if="transferInProgress" name="circular"></ion-spinner>
                  <ion-icon v-else :name="moveInsteadOfCopy ? 'arrow-forward-outline' : 'copy-outline'"></ion-icon>
                  {{ moveInsteadOfCopy ? 'Verschieben' : 'Kopieren' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  IonPage, IonContent, IonIcon, IonSpinner, alertController
} from '@ionic/vue'
import SiteTitle from '@/components/SiteTitle.vue'
import axios from 'axios'
import qs from 'qs'
import { ToastService } from '@/services/ToastService'

const route = useRoute()
const toast = ToastService

const codespaces = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingCodespace = ref(null)
const searchTerm = ref('')
const sortColumn = ref('name')
const sortDirection = ref('asc')

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

// Transfer Modal
const showTransferModal = ref(false)
const transferCodespace = ref(null)
const availableProjects = ref([])
const selectedTargetProject = ref('')
const moveInsteadOfCopy = ref(false)
const transferInProgress = ref(false)

// Available Templates
const availableTemplates = ref([])
const loadingTemplates = ref(false)

const formData = ref({
  name: '',
  description: '',
  language: 'javascript',
  template: 'vanilla-js',
  icon: 'code-outline',
  createGithubRepo: false,
  createVercelProject: false
})

// Computed properties
const filteredCodespaces = computed(() => {
  let filtered = codespaces.value

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(cs => 
      cs.name.toLowerCase().includes(search) ||
      (cs.description && cs.description.toLowerCase().includes(search)) ||
      cs.language.toLowerCase().includes(search)
    )
  }

  // Sort
  if (sortColumn.value) {
    filtered = [...filtered].sort((a, b) => {
      let aVal = a[sortColumn.value]
      let bVal = b[sortColumn.value]
      
      if (sortColumn.value === 'created_at') {
        aVal = new Date(aVal).getTime()
        bVal = new Date(bVal).getTime()
      } else {
        aVal = String(aVal).toLowerCase()
        bVal = String(bVal).toLowerCase()
      }
      
      if (sortDirection.value === 'asc') {
        return aVal > bVal ? 1 : -1
      } else {
        return aVal < bVal ? 1 : -1
      }
    })
  }

  return filtered
})

const sortBy = (column: string) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortDirection.value = 'asc'
  }
}

const loadAvailableTemplates = async () => {
  try {
    loadingTemplates.value = true
    
    const response = await axios.post('project_codespaces.php', qs.stringify({
      getAvailableTemplates: true
    }))

    if (response.data.success) {
      availableTemplates.value = response.data.templates
    } else {
      // Fallback templates if backend fails
      availableTemplates.value = [
        { id: 'vanilla-js', name: 'Vanilla JavaScript', description: 'Basic HTML, CSS and JavaScript setup', icon: 'logo-javascript' },
        { id: 'react', name: 'React', description: 'React application with Vite build tool', icon: 'logo-react' },
        { id: 'vue', name: 'Vue.js', description: 'Vue.js application with Vite build tool', icon: 'logo-vue' },
        { id: 'node', name: 'Node.js', description: 'Node.js server with Express framework', icon: 'logo-nodejs' }
      ]
    }
  } catch (error) {
    console.error('Error loading templates:', error)
    // Fallback templates
    availableTemplates.value = [
      { id: 'vanilla-js', name: 'Vanilla JavaScript', description: 'Basic HTML, CSS and JavaScript setup', icon: 'logo-javascript' },
      { id: 'react', name: 'React', description: 'React application with Vite build tool', icon: 'logo-react' },
      { id: 'vue', name: 'Vue.js', description: 'Vue.js application with Vite build tool', icon: 'logo-vue' },
      { id: 'node', name: 'Node.js', description: 'Node.js server with Express framework', icon: 'logo-nodejs' }
    ]
  } finally {
    loadingTemplates.value = false
  }
}

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

const createNewCodespace = async () => {
  editingCodespace.value = null
  
  // Load templates if not already loaded
  if (availableTemplates.value.length === 0) {
    await loadAvailableTemplates()
  }
  
  formData.value = {
    name: '',
    description: '',
    language: 'javascript',
    template: availableTemplates.value.length > 0 ? availableTemplates.value[0].id : 'vanilla-js',
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
      const data: any = {
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

// Transfer Functions
const openTransferModal = async (codespace) => {
  transferCodespace.value = codespace
  selectedTargetProject.value = ''
  moveInsteadOfCopy.value = false
  
  await loadAvailableProjects()
  showTransferModal.value = true
}

const closeTransferModal = () => {
  showTransferModal.value = false
  transferCodespace.value = null
  availableProjects.value = []
  selectedTargetProject.value = ''
  moveInsteadOfCopy.value = false
  transferInProgress.value = false
}

const loadAvailableProjects = async () => {
  try {
    const response = await axios.post('project_codespaces.php', qs.stringify({
      getUserProjects: true
    }))

    if (response.data.success) {
      // Aktuelles Projekt ausschließen
      const currentProject = route.params.project
      availableProjects.value = response.data.projects.filter(project => project.link !== currentProject)
    } else {
      toast.error('Fehler beim Laden der Projekte')
    }
  } catch (error) {
    console.error('Error loading projects:', error)
    toast.error('Verbindungsfehler')
  }
}

const executeTransfer = async () => {
  if (!selectedTargetProject.value || !transferCodespace.value) {
    return
  }
  
  try {
    transferInProgress.value = true
    
    const data: any = {
      transferCodespace: true,
      codespaceID: transferCodespace.value.id,
      targetProject: selectedTargetProject.value
    }
    
    if (moveInsteadOfCopy.value) {
      data.moveCodespace = 'true'
    }
    
    const response = await axios.post('project_codespaces.php', qs.stringify(data))

    if (response.data.success) {
      const action = moveInsteadOfCopy.value ? 'verschoben' : 'kopiert'
      toast.success(`Codespace erfolgreich ${action}!`)
      
      closeTransferModal()
      
      // Wenn verschoben, Liste neu laden um den entfernten Codespace zu reflektieren
      if (moveInsteadOfCopy.value) {
        await loadCodespaces()
      }
    } else {
      toast.error(response.data.message || 'Fehler beim Übertragen')
    }
  } catch (error) {
    console.error('Error transferring codespace:', error)
    toast.error('Verbindungsfehler')
  } finally {
    transferInProgress.value = false
  }
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
    
    const data: any = {
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

const getSelectedTemplate = () => {
  return availableTemplates.value.find(template => template.id === formData.value.template)
}

const getDefaultLanguageForTemplate = (templateId) => {
  const mapping = {
    'vanilla-js': 'javascript',
    'react': 'javascript',
    'vue': 'javascript', 
    'node': 'javascript',
    'typescript': 'typescript',
    'python': 'python',
    'php': 'php'
  }
  return mapping[templateId] || 'javascript'
}

onMounted(() => {
  loadCodespaces()
  loadAvailableTemplates()
})

// Watch template changes to update language automatically
watch(() => formData.value.template, (newTemplate) => {
  if (newTemplate && !editingCodespace.value) {
    formData.value.language = getDefaultLanguageForTemplate(newTemplate)
  }
})
</script>

<style scoped>
.modern-content {
  --background: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --primary-color: #2563eb;
  --primary-hover: #1d4ed8;
  --success-color: #059669;
  --danger-color: #dc2626;
  --warning-color: #f59e0b;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --radius: 8px;
  --radius-lg: 12px;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--background);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 20px;
}

.header-content h1 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 32px;
  font-weight: 700;
  line-height: 1.2;
}

.header-content p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Action Buttons */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  background: var(--surface);
  color: var(--text-primary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.action-btn.primary {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.action-btn.primary:hover {
  background: var(--primary-hover);
  border-color: var(--primary-hover);
}

.action-btn.secondary {
  background: var(--surface);
  color: var(--text-primary);
}

.action-btn.danger {
  background: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.action-btn.danger:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}

/* Data Card */
.data-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 16px;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
}

.entry-count {
  color: var(--text-secondary);
  font-size: 14px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 8px 12px;
  min-width: 280px;
  transition: all 0.2s ease;
}

.search-box:focus-within {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-box ion-icon {
  color: var(--text-secondary);
  font-size: 18px;
}

.search-box input {
  border: none;
  outline: none;
  background: transparent;
  flex: 1;
  color: var(--text-primary);
  font-size: 14px;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-secondary);
}

.loading-icon {
  font-size: 32px;
  color: var(--primary-color);
  margin-bottom: 12px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* No Data State */
.no-data-state {
  padding: 60px 20px;
  text-align: center;
  background: var(--surface);
}

.no-data-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-data-icon {
  font-size: 64px;
  color: var(--text-muted);
  margin-bottom: 16px;
  opacity: 0.5;
}

.no-data-content h4 {
  margin: 0 0 8px 0;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 600;
}

.no-data-content p {
  margin: 0 0 24px 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

/* Modern Table */
.modern-table {
  width: 100%;
  min-width: 800px;
}

.table-header {
  display: flex;
  background: var(--background);
  border-bottom: 2px solid var(--border);
}

.header-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.header-cell:hover {
  background: var(--border);
}

.actions-header {
  flex: 0 0 200px;
  justify-content: center;
  cursor: default;
}

.actions-header:hover {
  background: var(--background);
}

.header-text {
  font-weight: 600;
}

.sort-indicator {
  display: flex;
  align-items: center;
  margin-left: 8px;
}

.sort-indicator ion-icon {
  font-size: 14px;
  transition: all 0.2s ease;
}

.sort-default {
  opacity: 0.3;
}

.sort-active {
  opacity: 1;
  color: var(--primary-color);
}

/* Table Body */
.table-body {
  background: var(--surface);
}

.table-row {
  display: flex;
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

.table-row:hover {
  background: var(--background);
}

.table-cell {
  flex: 1;
  min-width: 120px;
  padding: 16px;
  display: flex;
  align-items: center;
  font-size: 14px;
  color: var(--text-primary);
}

.cell-actions {
  flex: 0 0 200px;
  justify-content: center;
}

/* Codespace Name Cell */
.codespace-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.codespace-icon {
  font-size: 24px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.codespace-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.codespace-details .name {
  font-weight: 600;
  color: var(--text-primary);
}

.codespace-details .description {
  font-size: 12px;
  color: var(--text-secondary);
}

/* Connections */
.connections-chips {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.connection-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.connection-badge.github {
  background: rgba(36, 41, 47, 0.1);
  color: #24292f;
}

.connection-badge.vercel {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.connection-badge.domain {
  background: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.connection-badge ion-icon {
  font-size: 12px;
}

.no-connections {
  color: var(--text-muted);
  font-size: 12px;
  font-style: italic;
}

/* Language Badge */
.language-badge {
  display: inline-block;
  padding: 4px 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid rgba(37, 99, 235, 0.2);
}

/* Date Text */
.date-text {
  color: var(--text-secondary);
  font-size: 13px;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.icon-btn.open-btn {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
}

.icon-btn.open-btn:hover {
  background: rgba(37, 99, 235, 0.2);
}

.icon-btn.settings-btn {
  background: rgba(100, 116, 139, 0.1);
  color: var(--text-secondary);
}

.icon-btn.settings-btn:hover {
  background: rgba(100, 116, 139, 0.2);
}

.icon-btn.edit-btn {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
}

.icon-btn.edit-btn:hover {
  background: rgba(245, 158, 11, 0.2);
}

.icon-btn.transfer-btn {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.icon-btn.transfer-btn:hover {
  background: rgba(139, 92, 246, 0.2);
}

.icon-btn.delete-btn {
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.icon-btn.delete-btn:hover {
  background: rgba(220, 38, 38, 0.2);
}

/* Custom Modal */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
  animation: modalFadeIn 0.2s ease;
}

.custom-modal-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-lg);
  animation: modalSlideIn 0.3s ease;
}

.custom-modal-content.large {
  max-width: 800px;
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
}

.custom-modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.modal-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: var(--radius);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.modal-close-btn:hover {
  background: var(--background);
  color: var(--text-primary);
}

.custom-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
}

.form-input,
.form-input:focus {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 14px;
  color: var(--text-primary);
  background: var(--surface);
  transition: all 0.2s ease;
  outline: none;
}

.form-input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea.form-input {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

select.form-input {
  cursor: pointer;
}

.form-note {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 8px;
}

.form-note.warning {
  color: var(--warning-color);
}

/* Checkbox & Radio Groups */
.checkbox-group,
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-label,
.radio-label {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.checkbox-label:hover,
.radio-label:hover {
  background: var(--background);
}

.checkbox-label input[type="checkbox"],
.radio-label input[type="radio"] {
  margin-top: 2px;
  cursor: pointer;
}

.checkbox-label span,
.radio-label span {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.checkbox-label strong,
.radio-label strong {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
}

.checkbox-label small,
.radio-label small {
  font-size: 12px;
  color: var(--text-secondary);
}

.warning-text {
  color: var(--warning-color);
}

/* Template Preview */
.template-preview {
  padding: 16px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.template-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.template-icon {
  font-size: 24px;
  color: var(--primary-color);
}

.template-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.template-info p {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary);
}

/* Auto-create Section */
.auto-create-section {
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

/* Settings Content */
.settings-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-header {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}

.settings-header h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
}

.settings-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

/* Connection Section */
.connection-section {
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.section-header ion-icon {
  font-size: 24px;
  color: var(--primary-color);
}

.section-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.connected-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: rgba(5, 150, 105, 0.1);
  border: 1px solid rgba(5, 150, 105, 0.2);
  border-radius: var(--radius);
  gap: 16px;
}

.connection-info h4 {
  margin: 0 0 4px 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.connection-info p {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

.not-connected {
  text-align: center;
  padding: 20px;
}

.not-connected p {
  margin: 0 0 16px 0;
  color: var(--text-secondary);
}

.connection-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}

.domain-config {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Repo List */
.repo-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.repo-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s ease;
}

.repo-item:hover {
  background: var(--background);
  border-color: var(--primary-color);
}

.repo-info h4 {
  margin: 0 0 4px 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.repo-info p {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

.repo-icon {
  font-size: 20px;
  color: var(--text-secondary);
}

/* Transfer Content */
.transfer-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.transfer-header {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}

.transfer-header h2 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
}

.transfer-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.transfer-info {
  padding: 20px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}

.transfer-info h4 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.transfer-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.transfer-list li {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-primary);
  font-size: 14px;
}

.transfer-list li ion-icon {
  font-size: 20px;
  color: var(--primary-color);
}

/* Animations */
@keyframes modalFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-container {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .search-box {
    min-width: 100%;
  }

  .table-wrapper {
    overflow-x: scroll;
  }

  .connection-actions {
    flex-direction: column;
  }

  .connected-item {
    flex-direction: column;
    text-align: center;
  }

  .action-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
  }

  .connection-badge.github {
    background: rgba(240, 246, 252, 0.1);
    color: #f0f6fc;
  }
}

@media (prefers-color-scheme: dark) {
  .modern-content {
    --background: #121212;
    --surface: #1a1a1a;
    --border: #2a2a2a;
    --text-primary: #f1f5f9;
    --text-secondary: #b0b0b0;
    --text-muted: #707070;
  }
}
</style>
