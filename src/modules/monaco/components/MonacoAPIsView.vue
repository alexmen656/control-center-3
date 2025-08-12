<template>
  <div class="apis-container">
    <!-- Professional APIs Management Screen -->
    <div class="apis-screen">
      <div class="apis-header">
        <div class="header-left">
          <div class="title-section">
            <h1 class="apis-title">CMS APIs</h1>
            <p class="apis-subtitle">{{ projectName }} / {{ codespace }}</p>
          </div>
        </div>
        <div class="header-actions">
          <button @click="refreshAPIs" class="refresh-button" :disabled="isLoading">
            <ion-icon name="refresh-outline"></ion-icon>
            <span>Refresh</span>
          </button>
        </div>
      </div>
      
      <div class="apis-content">
        <!-- Loading State -->
        <div v-if="isLoading" class="loading-section">
          <ion-spinner></ion-spinner>
          <p>APIs werden geladen...</p>
        </div>

        <!-- No APIs State -->
        <div v-else-if="availableAPIs.length === 0" class="empty-state">
          <ion-icon name="server-outline" class="empty-icon"></ion-icon>
          <h3>Keine APIs verfügbar</h3>
          <p>Abonnieren Sie APIs im Haupt-Control Center, um sie hier zu verwalten.</p>
          <button @click="openControlCenter" class="primary-button">
            <ion-icon name="open-outline"></ion-icon>
            Control Center öffnen
          </button>
        </div>

        <!-- APIs Management -->
        <div v-else class="apis-management">
          <!-- Active APIs Section -->
          <div class="apis-section">
            <div class="section-header">
              <h3>Aktive APIs</h3>
              <span class="count-badge">{{ activeAPIs.length }}</span>
            </div>
            
            <div v-if="activeAPIs.length === 0" class="no-active-apis">
              <p>Keine APIs aktiviert. Aktivieren Sie APIs unten, um sie zu nutzen.</p>
            </div>
            
            <div v-else class="apis-grid">
              <div 
                v-for="api in activeAPIs" 
                :key="api.subscription_id"
                class="api-card active"
              >
                <div class="api-card-header">
                  <ion-icon :name="api.icon || 'server-outline'" class="api-icon"></ion-icon>
                  <div class="api-info">
                    <h4 class="api-name">{{ api.name }}</h4>
                    <span class="api-category">{{ api.category }}</span>
                  </div>
                  <ion-toggle 
                    :checked="true" 
                    @ionChange="toggleAPI(api)"
                    :disabled="api.isToggling"
                    color="success"
                  ></ion-toggle>
                </div>
                
                <div class="api-card-content">
                  <p class="api-description">{{ api.description || 'Keine Beschreibung verfügbar.' }}</p>
                  
                  <div class="api-usage">
                    <h5>Verwendung:</h5>
                    <div class="code-example" @click="copyToClipboard(getAPIImportExample(api))">
                      <code>{{ getAPIImportExample(api) }}</code>
                      <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
                    </div>
                  </div>
                </div>
                
                <div class="api-card-actions">
                  <button @click="openAPIDocumentation(api)" class="secondary-button">
                    <ion-icon name="book-outline"></ion-icon>
                    Dokumentation
                  </button>
                  <button @click="testAPI(api)" class="secondary-button">
                    <ion-icon name="flash-outline"></ion-icon>
                    Testen
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Available APIs Section -->
          <div class="apis-section">
            <div class="section-header">
              <h3>Verfügbare APIs</h3>
              <span class="count-badge">{{ inactiveAPIs.length }}</span>
            </div>
            
          <div v-if="inactiveAPIs.length === 0" class="no-inactive-apis">
            <p>All available APIs are already activated.</p>
          </div>            <div v-else class="apis-grid">
              <div 
                v-for="api in inactiveAPIs" 
                :key="api.subscription_id"
                class="api-card inactive"
              >
                <div class="api-card-header">
                  <ion-icon :name="api.icon || 'server-outline'" class="api-icon"></ion-icon>
                  <div class="api-info">
                    <h4 class="api-name">{{ api.name }}</h4>
                    <span class="api-category">{{ api.category }}</span>
                  </div>
                  <ion-toggle 
                    :checked="false" 
                    @ionChange="toggleAPI(api)"
                    :disabled="api.isToggling"
                  ></ion-toggle>
                </div>
                
                <div class="api-card-content">
                  <p class="api-description">{{ api.description || 'Keine Beschreibung verfügbar.' }}</p>
                </div>
                
                <div class="api-card-actions">
                  <button @click="openAPIDocumentation(api)" class="secondary-button">
                    <ion-icon name="book-outline"></ion-icon>
                    Dokumentation
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Code Examples Section -->
          <div v-if="activeAPIs.length > 0" class="apis-section">
            <div class="section-header">
              <h3>Code-Beispiele</h3>
            </div>
            
            <div class="code-examples">
              <div class="example-block">
                <h5>Alle APIs importieren:</h5>
                <div class="code-block" @click="copyToClipboard(getAllImportsExample())">
                  <code>{{ getAllImportsExample() }}</code>
                  <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
                </div>
              </div>
              
              <div class="example-block">
                <h5>Verwendungsbeispiel:</h5>
                <div class="code-block" @click="copyToClipboard(getUsageExample())">
                  <code>{{ getUsageExample() }}</code>
                  <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { ToastService } from '@/services/ToastService'

// Props
const props = defineProps({
  projectName: {
    type: String,
    required: true
  },
  codespace: {
    type: String,
    required: true
  }
})

// State
const isLoading = ref(true)
const availableAPIs = ref([])

// Computed
const activeAPIs = computed(() => availableAPIs.value.filter(api => api.is_active))
const inactiveAPIs = computed(() => availableAPIs.value.filter(api => !api.is_active))

// Methods
const refreshAPIs = async () => {
  await loadAvailableAPIs()
}

const loadAvailableAPIs = async () => {
  isLoading.value = true
  try {
    const formData = new FormData()
    formData.append('getCodespaceAPIs', '1')
    formData.append('project', props.projectName)
    formData.append('codespace', props.codespace)
    
    const response = await axios.post('codespace_apis.php', formData)
    
    if (response.data && Array.isArray(response.data)) {
      availableAPIs.value = response.data.map(api => ({
        ...api,
        isToggling: false
      }))
    } else {
      availableAPIs.value = []
    }
  } catch (error) {
    console.error('Failed to load available APIs:', error)
    availableAPIs.value = []
    ToastService.error('Fehler beim Laden der APIs')
  } finally {
    isLoading.value = false
  }
}

const toggleAPI = async (api) => {
  if (api.isToggling) return
  
  api.isToggling = true
  
  try {
    const formData = new FormData()
    formData.append('project', props.projectName)
    formData.append('codespace', props.codespace)
    formData.append('subscription_id', api.subscription_id)
    
    if (api.is_active) {
      formData.append('deactivateCodespaceAPI', '1')
      const response = await axios.post('codespace_apis.php', formData)
      
      if (response.data && response.data.success) {
        api.is_active = false
        ToastService.success('API erfolgreich deaktiviert')
      } else {
        ToastService.error(response.data?.message || 'Fehler beim Deaktivieren der API')
      }
    } else {
      formData.append('activateCodespaceAPI', '1')
      const response = await axios.post('codespace_apis.php', formData)
      
      if (response.data && response.data.success) {
        api.is_active = true
        ToastService.success('API erfolgreich aktiviert')
      } else {
        ToastService.error(response.data?.message || 'Fehler beim Aktivieren der API')
      }
    }
  } catch (error) {
    console.error('Failed to toggle API:', error)
    ToastService.error('Fehler beim Umschalten der API')
  } finally {
    api.isToggling = false
  }
}

const getAPIClassName = (slug) => {
  return slug.charAt(0).toUpperCase() + slug.slice(1) + 'API'
}

const getAPIImportExample = (api) => {
  const className = getAPIClassName(api.slug)
  return `import { ${className} } from 'apis';`
}

const getAllImportsExample = () => {
  if (activeAPIs.value.length === 0) return ''
  
  const imports = activeAPIs.value.map(api => getAPIClassName(api.slug)).join(', ')
  return `import { ${imports} } from 'apis';`
}

const getUsageExample = () => {
  if (activeAPIs.value.length === 0) return ''
  
  const firstAPI = activeAPIs.value[0]
  const className = getAPIClassName(firstAPI.slug)
  
  switch (firstAPI.slug) {
    case 'user-management':
      return `// Alle Benutzer abrufen\nconst users = await ${className}.getAll();\n\n// Neuen Benutzer erstellen\nconst user = await ${className}.create({ name: 'John', email: 'john@example.com' });`
    case 'file-storage':
      return `// Datei hochladen\nconst result = await ${className}.upload(file);\n\n// Dateien auflisten\nconst files = await ${className}.list();`
    case 'database':
      return `// Datenbank abfragen\nconst records = await ${className}.query('users', { active: true });\n\n// Datensatz einfügen\nconst result = await ${className}.insert('users', { name: 'John' });`
    case 'notifications':
      return `// Benachrichtigung senden\nconst result = await ${className}.send({ message: 'Hallo!', userId: 123 });\n\n// Benachrichtigungen abrufen\nconst notifications = await ${className}.list();`
    case 'analytics':
      return `// Event verfolgen\nconst result = await ${className}.track('user_login', { userId: 123 });\n\n// Analytics abrufen\nconst data = await ${className}.getReport('daily');`
    default:
      return `// ${firstAPI.name} API verwenden\nconst result = await ${className}.get();`
  }
}

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    ToastService.success('In Zwischenablage kopiert!')
  }).catch(() => {
    ToastService.error('Fehler beim Kopieren')
  })
}

const openAPIDocumentation = (api) => {
  // Open API documentation in new tab
  const docUrl = `/backend/api_documentation.php?api=${api.slug}`
  window.open(docUrl, '_blank')
}

const testAPI = (api) => {
  // Open API testing interface
  ToastService.info(`API-Test-Interface für ${api.name} wird geöffnet...`)
  // TODO: Implement API testing interface
}

const openControlCenter = () => {
  window.open('/dashboard', '_blank')
}

// Lifecycle
onMounted(() => {
  loadAvailableAPIs()
})
</script>

<style scoped>
.apis-container {
  height: 100%;
  flex: 1;
  min-width: 0;
  background: var(--vscode-editor-background, #1e1e1e);
  color: var(--vscode-editor-foreground, #d4d4d4);
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  box-sizing: border-box;
}

.apis-screen {
  height: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  box-sizing: border-box;
}

.apis-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  border-bottom: 1px solid var(--vscode-panel-border, #2d2d30);
  background: var(--vscode-editor-background, #1e1e1e);
  flex-shrink: 0;
  min-width: 0;
  box-sizing: border-box;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.title-section {
  display: flex;
  flex-direction: column;
}

.apis-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.apis-subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--vscode-descriptionForeground, #cccccc);
}

.header-actions {
  display: flex;
  gap: 12px;
}

.refresh-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid var(--vscode-button-border, #464647);
  border-radius: 3px;
  background: var(--vscode-button-background, #0e639c);
  color: var(--vscode-button-foreground, #ffffff);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 13px;
}

.refresh-button:hover:not(:disabled) {
  background: var(--vscode-button-hoverBackground, #1177bb);
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.apis-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 24px;
  background: var(--vscode-editor-background, #1e1e1e);
  min-width: 0;
  box-sizing: border-box;
}

.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: var(--vscode-descriptionForeground, #cccccc);
}

.loading-section ion-spinner {
  margin-bottom: 16px;
  --color: var(--vscode-progressBar-background, #0e70c0);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  color: var(--vscode-icon-foreground, #cccccc);
  margin-bottom: 24px;
  opacity: 0.6;
}

.empty-state h3 {
  margin: 0 0 12px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.empty-state p {
  margin: 0 0 24px 0;
  color: var(--vscode-descriptionForeground, #cccccc);
  max-width: 400px;
  line-height: 1.5;
}

.primary-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 3px;
  background: var(--vscode-button-background, #0e639c);
  color: var(--vscode-button-foreground, #ffffff);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 13px;
}

.primary-button:hover {
  background: var(--vscode-button-hoverBackground, #1177bb);
}

.apis-management {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.apis-section {
  background: var(--vscode-sideBar-background, #252526);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 6px;
  padding: 24px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.section-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.count-badge {
  background: var(--vscode-badge-background, #4c7bd6);
  color: var(--vscode-badge-foreground, #ffffff);
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.apis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.api-card {
  background: var(--vscode-editor-background, #1e1e1e);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 6px;
  padding: 20px;
  transition: all 0.2s ease;
}

.api-card:hover {
  border-color: var(--vscode-focusBorder, #007fd4);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.api-card.active {
  border-color: var(--vscode-terminal-ansiGreen, #16825d);
  background: var(--vscode-sideBar-background, #252526);
}

.api-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.api-icon {
  font-size: 24px;
  color: var(--vscode-symbolIcon-colorForeground, #4fc1ff);
}

.api-info {
  flex: 1;
}

.api-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.api-category {
  font-size: 12px;
  color: var(--vscode-descriptionForeground, #cccccc);
  text-transform: uppercase;
  font-weight: 500;
}

.api-card-content {
  margin-bottom: 16px;
}

.api-description {
  margin: 0 0 16px 0;
  color: var(--vscode-descriptionForeground, #cccccc);
  font-size: 14px;
  line-height: 1.4;
}

.api-usage h5 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.code-example {
  background: var(--vscode-textCodeBlock-background, #0f0f0f);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 3px;
  padding: 12px;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
}

.code-example:hover {
  border-color: var(--vscode-focusBorder, #007fd4);
}

.code-example code {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 12px;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.copy-icon {
  position: absolute;
  top: 8px;
  right: 8px;
  color: var(--vscode-descriptionForeground, #cccccc);
  font-size: 16px;
  opacity: 0.6;
}

.code-example:hover .copy-icon {
  opacity: 1;
}

.api-card-actions {
  display: flex;
  gap: 8px;
}

.secondary-button {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid var(--vscode-button-border, #464647);
  border-radius: 3px;
  background: var(--vscode-button-secondaryBackground, #3c3c3c);
  color: var(--vscode-button-secondaryForeground, #cccccc);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.secondary-button:hover {
  background: var(--vscode-button-secondaryHoverBackground, #464647);
}

.no-active-apis,
.no-inactive-apis {
  padding: 40px 20px;
  text-align: center;
  color: var(--vscode-descriptionForeground, #cccccc);
  font-style: italic;
}

.code-examples {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.example-block h5 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.code-block {
  background: var(--vscode-textCodeBlock-background, #0f0f0f);
  color: var(--vscode-editor-foreground, #d4d4d4);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 3px;
  padding: 16px;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow-x: auto;
}

.code-block:hover {
  border-color: var(--vscode-focusBorder, #007fd4);
}

.code-block code {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 13px;
  white-space: pre-wrap;
}

.code-block .copy-icon {
  color: var(--vscode-descriptionForeground, #cccccc);
}

/* Responsive Design */
@media (max-width: 768px) {
  .apis-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .apis-header {
    padding: 12px 16px;
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    width: 100%;
    justify-content: stretch;
  }
  
  .refresh-button {
    flex: 1;
    justify-content: center;
  }
  
  .apis-content {
    padding: 16px;
  }
  
  .apis-section {
    padding: 16px;
  }
}

@media (max-width: 480px) {
  .apis-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .apis-header {
    padding: 8px 12px;
  }
  
  .apis-content {
    padding: 12px;
  }
  
  .api-card {
    padding: 12px;
  }
}
</style>
