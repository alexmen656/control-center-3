<template>
  <div class="env-container">
    <!-- Professional Environment Variables Management Screen -->
    <div class="env-screen">
      <div class="env-header">
        <div class="header-left">
          <div class="title-section">
            <h1 class="env-title">Environment Variables</h1>
            <p class="env-subtitle">{{ projectName }} / {{ codespace }}</p>
          </div>
        </div>
        <div class="header-actions">
          <button @click="refreshEnvVars" class="refresh-button" :disabled="isLoading">
            <ion-icon name="refresh-outline"></ion-icon>
            <span>Refresh</span>
          </button>
          <button @click="openAddModal" class="add-button">
            <ion-icon name="add-outline"></ion-icon>
            <span>Add Variable</span>
          </button>
        </div>
      </div>

      <div class="env-content">
        <!-- Loading State -->
        <div v-if="isLoading" class="loading-section">
          <ion-spinner></ion-spinner>
          <p>Environment Variables werden geladen...</p>
        </div>

        <!-- No Variables State -->
        <div v-else-if="envVariables.length === 0" class="empty-state">
          <ion-icon name="server-outline" class="empty-icon"></ion-icon>
          <h3>Keine Environment Variables</h3>
          <p>Dieser Codespace hat noch keine Environment Variables.</p>
          <button @click="openAddModal" class="primary-button">
            <ion-icon name="add-outline"></ion-icon>
            Erste Variable hinzufügen
          </button>
        </div>

        <!-- Environment Variables Management -->
        <div v-else class="env-management">
          <!-- Environment Variables Table -->
          <div class="env-section">
            <div class="section-header">
              <h3>Environment Variables</h3>
              <span class="count-badge">{{ envVariables.length }}</span>
            </div>

            <div class="table-container">
              <table class="env-table">
                <thead>
                  <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Environments</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="envVar in envVariables" :key="envVar.id" class="env-row">
                    <td class="key-column">
                      <span class="env-key">{{ envVar.key }}</span>
                    </td>
                    <td class="value-column">
                      <code class="env-value">{{ envVar.showValue ? envVar.decryptedValue : '••••••••' }}</code>
                      <button @click="toggleValueVisibility(envVar)" class="toggle-value-btn">
                        <ion-icon :name="envVar.showValue ? 'eye-off-outline' : 'eye-outline'"></ion-icon>
                      </button>
                    </td>
                    <td class="targets-column">
                      <div class="env-targets">
                        <span v-for="target in envVar.target" :key="target"
                          :class="['env-chip', getTargetClass(target)]">
                          {{ target }}
                        </span>
                      </div>
                    </td>
                    <td class="actions-column">
                      <div class="action-buttons">
                        <button @click="editEnvironmentVariable(envVar)" class="action-btn edit-btn">
                          <ion-icon name="create-outline"></ion-icon>
                        </button>
                        <button @click="deleteEnvironmentVariable(envVar)" class="action-btn delete-btn">
                          <ion-icon name="trash-outline"></ion-icon>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Code Examples Section -->
          <div v-if="envVariables.length > 0" class="env-section">
            <div class="section-header">
              <h3>Code-Beispiele</h3>
            </div>

            <div class="code-examples">
              <div class="example-block">
                <h5>.env Datei:</h5>
                <div class="code-block" @click="copyToClipboard(getEnvFileExample())">
                  <code>{{ getEnvFileExample() }}</code>
                  <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
                </div>
              </div>

              <div class="example-block">
                <h5>JavaScript Verwendung:</h5>
                <div class="code-block" @click="copyToClipboard(getJavaScriptExample())">
                  <code>{{ getJavaScriptExample() }}</code>
                  <ion-icon name="copy-outline" class="copy-icon"></ion-icon>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Variable Modal -->
    <div v-if="addModal.isOpen" class="modal-overlay" @click="closeAddModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Environment Variable hinzufügen</h3>
          <button @click="closeAddModal" class="close-btn">
            <ion-icon name="close-outline"></ion-icon>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Key *</label>
            <input v-model="newEnvVar.key" placeholder="VARIABLE_NAME" class="form-input"
              @keydown.enter="addEnvironmentVariable" />
          </div>

          <div class="form-group">
            <label>Value *</label>
            <textarea v-model="newEnvVar.value" placeholder="variable_value" class="form-textarea" rows="3"></textarea>
          </div>

          <div class="form-group">
            <label>Target Environments</label>
            <div class="checkbox-group">
              <label class="checkbox-item">
                <input type="checkbox" value="production" v-model="newEnvVar.target" />
                <span class="checkmark"></span>
                Production
              </label>
              <label class="checkbox-item">
                <input type="checkbox" value="preview" v-model="newEnvVar.target" />
                <span class="checkmark"></span>
                Preview
              </label>
              <label class="checkbox-item">
                <input type="checkbox" value="development" v-model="newEnvVar.target" />
                <span class="checkmark"></span>
                Development
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeAddModal" class="secondary-button">Abbrechen</button>
          <button @click="addEnvironmentVariable" :disabled="!newEnvVar.key || !newEnvVar.value || isLoading"
            class="primary-button">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            {{ isLoading ? 'Wird hinzugefügt...' : 'Variable hinzufügen' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Variable Modal -->
    <div v-if="editModal.isOpen" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Environment Variable bearbeiten</h3>
          <button @click="closeEditModal" class="close-btn">
            <ion-icon name="close-outline"></ion-icon>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Key *</label>
            <input v-model="editModal.key" placeholder="VARIABLE_NAME" class="form-input"
              @keydown.enter="updateEnvironmentVariable" />
          </div>

          <div class="form-group">
            <label>Value *</label>
            <textarea v-model="editModal.value" placeholder="variable_value" class="form-textarea" rows="3"></textarea>
          </div>

          <div class="form-group">
            <label>Target Environments</label>
            <div class="checkbox-group">
              <label class="checkbox-item">
                <input type="checkbox" value="production" v-model="editModal.target" />
                <span class="checkmark"></span>
                Production
              </label>
              <label class="checkbox-item">
                <input type="checkbox" value="preview" v-model="editModal.target" />
                <span class="checkmark"></span>
                Preview
              </label>
              <label class="checkbox-item">
                <input type="checkbox" value="development" v-model="editModal.target" />
                <span class="checkmark"></span>
                Development
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeEditModal" class="secondary-button">Abbrechen</button>
          <button @click="updateEnvironmentVariable" :disabled="!editModal.key || !editModal.value || isLoading"
            class="primary-button">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            {{ isLoading ? 'Wird aktualisiert...' : 'Variable aktualisieren' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import qs from 'qs'
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
const envVariables = ref([])
const selectedProject = ref(null)

// Modals
const addModal = ref({
  isOpen: false
})

const editModal = ref({
  isOpen: false,
  id: '',
  key: '',
  value: '',
  target: ['production', 'preview', 'development']
})

// Form data
const newEnvVar = ref({
  key: '',
  value: '',
  target: ['production', 'preview', 'development']
})

// Methods
const refreshEnvVars = async () => {
  await loadEnvironmentVariables()
}

const loadEnvironmentVariables = async () => {
  isLoading.value = true
  try {
    // First check if we have a connected Vercel project for this codespace
    const projectResponse = await axios.post('codespace_vercel.php', qs.stringify({
      action: 'get',
      project: props.projectName,
      codespace: props.codespace
    }))

    if (projectResponse.data.vercel_project_id) {
      selectedProject.value = {
        id: projectResponse.data.vercel_project_id,
        name: projectResponse.data.vercel_project_name
      }

      // Load environment variables from Vercel with codespace context
      const envResponse = await axios.get(`vercel_api.php?project=${props.projectName}&codespace=${props.codespace}&action=env`)

      if (envResponse.data.success) {
        envVariables.value = (envResponse.data.envVars.envs || []).map(env => ({
          ...env,
          showValue: false
        }))
      } else {
        envVariables.value = []
      }
    } else {
      envVariables.value = []
      ToastService.warning('Kein Vercel-Projekt für diesen Codespace verbunden. Bitte verbinden Sie zuerst ein Projekt.')
    }
  } catch (error) {
    console.error('Failed to load environment variables:', error)
    envVariables.value = []
    ToastService.error('Fehler beim Laden der Environment Variables')
  } finally {
    isLoading.value = false
  }
}

const openAddModal = () => {
  if (!selectedProject.value) {
    ToastService.warning('Kein Vercel-Projekt für diesen Codespace verbunden')
    return
  }
  addModal.value.isOpen = true
}

const closeAddModal = () => {
  addModal.value.isOpen = false
  newEnvVar.value = {
    key: '',
    value: '',
    target: ['production', 'preview', 'development']
  }
}

const addEnvironmentVariable = async () => {
  if (!newEnvVar.value.key || !newEnvVar.value.value) {
    ToastService.error('Key und Value sind erforderlich')
    return
  }

  if (!selectedProject.value) {
    ToastService.error('Kein Codespace-Projekt ausgewählt')
    return
  }

  isLoading.value = true
  try {
    const response = await axios.post(`vercel_api.php?project=${props.projectName}&codespace=${props.codespace}`, {
      action: 'create_env',
      key: newEnvVar.value.key,
      value: newEnvVar.value.value,
      target: newEnvVar.value.target
    })

    if (response.data.success) {
      ToastService.success('Environment Variable erfolgreich hinzugefügt')
      closeAddModal()
      await loadEnvironmentVariables()
    } else {
      ToastService.error(response.data.message || 'Fehler beim Hinzufügen der Variable')
    }
  } catch (error) {
    console.error('Failed to add environment variable:', error)
    ToastService.error('Fehler beim Hinzufügen der Environment Variable')
  } finally {
    isLoading.value = false
  }
}

const editEnvironmentVariable = (envVar) => {
  editModal.value = {
    isOpen: true,
    id: envVar.id,
    key: envVar.key,
    value: envVar.decryptedValue,
    target: envVar.target || ['production', 'preview', 'development']
  }
}

const closeEditModal = () => {
  editModal.value = {
    isOpen: false,
    id: '',
    key: '',
    value: '',
    target: ['production', 'preview', 'development']
  }
}

const updateEnvironmentVariable = async () => {
  if (!editModal.value.key || !editModal.value.value) {
    ToastService.error('Key und Value sind erforderlich')
    return
  }

  isLoading.value = true
  try {
    const response = await axios.post(`vercel_api.php?project=${props.projectName}&codespace=${props.codespace}`, {
      action: 'update_env',
      envId: editModal.value.id,
      key: editModal.value.key,
      value: editModal.value.value,
      target: editModal.value.target
    })

    if (response.data.success) {
      ToastService.success('Environment Variable erfolgreich aktualisiert')
      closeEditModal()
      await loadEnvironmentVariables()
    } else {
      ToastService.error(response.data.message || 'Fehler beim Aktualisieren der Variable')
    }
  } catch (error) {
    console.error('Failed to update environment variable:', error)
    ToastService.error('Fehler beim Aktualisieren der Environment Variable')
  } finally {
    isLoading.value = false
  }
}

const deleteEnvironmentVariable = async (envVar) => {
  if (!confirm(`Sind Sie sicher, dass Sie die Environment Variable "${envVar.key}" löschen möchten?`)) {
    return
  }

  isLoading.value = true
  try {
    const response = await axios.post(`vercel_api.php?project=${props.projectName}&codespace=${props.codespace}`, {
      action: 'delete_env',
      envId: envVar.id
    })

    if (response.data.success) {
      ToastService.success('Environment Variable erfolgreich gelöscht')
      await loadEnvironmentVariables()
    } else {
      ToastService.error(response.data.message || 'Fehler beim Löschen der Variable')
    }
  } catch (error) {
    console.error('Failed to delete environment variable:', error)
    ToastService.error('Fehler beim Löschen der Environment Variable')
  } finally {
    isLoading.value = false
  }
}

const toggleValueVisibility = (envVar) => {
  envVar.showValue = !envVar.showValue
  if (envVar.showValue && !envVar.decryptedValue) {
    // Load the actual value if not already loaded
    envVar.decryptedValue = envVar.value
  }
}

const getTargetClass = (target) => {
  switch (target) {
    case 'production': return 'production'
    case 'preview': return 'preview'
    case 'development': return 'development'
    default: return 'default'
  }
}

const getEnvFileExample = () => {
  return envVariables.value.map(env => `${env.key}=${env.value || 'your_value_here'}`).join('\n')
}

const getJavaScriptExample = () => {
  if (envVariables.value.length === 0) return ''

  const firstVar = envVariables.value[0]
  return `// Environment Variable abrufen\nconst ${firstVar.key.toLowerCase()} = process.env.${firstVar.key};\n\n// Verwendung\nconsole.log('${firstVar.key}:', ${firstVar.key.toLowerCase()});`
}

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    ToastService.success('In Zwischenablage kopiert!')
  }).catch(() => {
    ToastService.error('Fehler beim Kopieren')
  })
}

// Lifecycle
onMounted(() => {
  loadEnvironmentVariables()

  // Listen for add modal trigger from sidebar
  window.addEventListener('monaco-env-add-modal', () => {
    openAddModal()
  })
})
</script>

<style scoped>
.env-container {
  height: 100%;
  flex: 1;
  min-width: 0;
  background: var(--vscode-editor-background, #1e1e1e);
  color: var(--vscode-editor-foreground, #d4d4d4);
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  box-sizing: border-box;
}

.env-screen {
  height: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  box-sizing: border-box;
}

.env-header {
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

.env-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.env-subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--vscode-descriptionForeground, #cccccc);
}

.header-actions {
  display: flex;
  gap: 12px;
}

.refresh-button,
.add-button {
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

.refresh-button:hover:not(:disabled),
.add-button:hover {
  background: var(--vscode-button-hoverBackground, #1177bb);
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.env-content {
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
}

.primary-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #0e639c 0%, #1177bb 100%);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(14, 99, 156, 0.3);
}

.primary-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #1177bb 0%, #0e639c 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(14, 99, 156, 0.4);
}

.primary-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.secondary-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: transparent;
  color: var(--vscode-button-secondaryForeground, #cccccc);
  border: 1px solid var(--vscode-button-border, #464647);
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s ease;
}

.secondary-button:hover {
  background: var(--vscode-button-secondaryHoverBackground, #2a2d2e);
}

.env-management {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.env-section {
  background: var(--vscode-panel-background, #252526);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 8px;
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--vscode-sideBarSectionHeader-background, #2d2d30);
  border-bottom: 1px solid var(--vscode-panel-border, #2d2d30);
}

.section-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--vscode-sideBarSectionHeader-foreground, #cccccc);
}

.count-badge {
  background: var(--vscode-badge-background, #007acc);
  color: var(--vscode-badge-foreground, #ffffff);
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.table-container {
  overflow-x: auto;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.env-table {
  width: 100%;
  min-width: 600px;
  border-collapse: collapse;
  table-layout: auto;
}

.env-table th {
  background: var(--vscode-list-headerBackground, #333334);
  color: var(--vscode-list-headerForeground, #cccccc);
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--vscode-panel-border, #2d2d30);
}

.env-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--vscode-panel-border, #2d2d30);
  vertical-align: middle;
}

.env-row:hover {
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.key-column {
  min-width: 200px;
}

.env-key {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-weight: 600;
  color: var(--vscode-symbolIcon-variableForeground, #9cdcfe);
  font-size: 13px;
}

.value-column {
  display: flex;
  min-width: 250px;
  position: relative;
}

.env-value {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  background: var(--vscode-textCodeBlock-background, #2d2d30);
  padding: 6px 8px;
  border-radius: 4px;
  font-size: 12px;
  display: inline-block;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.toggle-value-btn {
  background: none;
  border: none;
  color: var(--vscode-icon-foreground, #cccccc);
  cursor: pointer;
  margin-left: 8px;
  padding: 4px;
  border-radius: 3px;
  transition: background 0.2s ease;
}

.toggle-value-btn:hover {
  background: var(--vscode-toolbar-hoverBackground, #2a2d2e);
}

.targets-column {
  min-width: 180px;
}

.env-targets {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.env-chip {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.env-chip.production {
  background: rgba(255, 99, 99, 0.2);
  color: #ff6363;
  border: 1px solid rgba(255, 99, 99, 0.3);
}

.env-chip.preview {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.env-chip.development {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.env-chip.default {
  background: var(--vscode-badge-background, #007acc);
  color: var(--vscode-badge-foreground, #ffffff);
}

.actions-column {
  width: 120px;
  text-align: center;
}

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.action-btn {
  background: none;
  border: 1px solid var(--vscode-button-border, #464647);
  color: var(--vscode-icon-foreground, #cccccc);
  padding: 6px 8px;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background: var(--vscode-toolbar-hoverBackground, #2a2d2e);
}

.edit-btn:hover {
  border-color: var(--vscode-button-background, #0e639c);
  color: var(--vscode-button-background, #0e639c);
}

.delete-btn:hover {
  border-color: #f14c4c;
  color: #f14c4c;
}

.code-examples {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.example-block h5 {
  margin: 0 0 12px 0;
  color: var(--vscode-editor-foreground, #d4d4d4);
  font-size: 14px;
  font-weight: 600;
}

.code-block {
  position: relative;
  background: var(--vscode-textCodeBlock-background, #2d2d30);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 6px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow-x: auto;
}

.code-block:hover {
  border-color: var(--vscode-focusBorder, #007fd4);
  background: var(--vscode-list-hoverBackground, #2a2d2e);
}

.code-block code {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 13px;
  color: var(--vscode-editor-foreground, #d4d4d4);
  white-space: pre;
  line-height: 1.4;
}

.copy-icon {
  position: absolute;
  top: 12px;
  right: 12px;
  color: var(--vscode-icon-foreground, #cccccc);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.code-block:hover .copy-icon {
  opacity: 1;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--vscode-panel-background, #252526);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid var(--vscode-panel-border, #2d2d30);
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.close-btn {
  background: none;
  border: none;
  color: var(--vscode-icon-foreground, #cccccc);
  cursor: pointer;
  padding: 8px;
  border-radius: 3px;
  transition: background 0.2s ease;
}

.close-btn:hover {
  background: var(--vscode-toolbar-hoverBackground, #2a2d2e);
}

.modal-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: var(--vscode-editor-foreground, #d4d4d4);
  font-size: 14px;
  font-weight: 600;
}

.form-input,
.form-textarea {
  background: var(--vscode-input-background, #3c3c3c);
  border: 1px solid var(--vscode-input-border, #464647);
  color: var(--vscode-input-foreground, #cccccc);
  padding: 10px 12px;
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s ease;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--vscode-focusBorder, #007fd4);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  color: var(--vscode-editor-foreground, #d4d4d4);
  font-size: 14px;
}

.checkbox-item input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--vscode-checkbox-background, #007acc);
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid var(--vscode-panel-border, #2d2d30);
  justify-content: flex-end;
}

/* Responsive Design */
@media (max-width: 768px) {
  .env-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    padding: 12px 16px;
  }

  .header-actions {
    width: 100%;
    justify-content: stretch;
  }

  .refresh-button,
  .add-button {
    flex: 1;
    justify-content: center;
  }

  .env-content {
    padding: 16px;
  }

  .env-table {
    font-size: 12px;
  }

  .env-table th,
  .env-table td {
    padding: 8px 12px;
  }

  .action-buttons {
    flex-direction: column;
    gap: 4px;
  }

  .modal-overlay {
    padding: 10px;
  }

  .modal-footer {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .env-header {
    padding: 8px 12px;
  }

  .env-content {
    padding: 12px;
  }

  .table-container {
    overflow-x: scroll;
  }

  .env-table {
    min-width: 500px;
    font-size: 11px;
  }

  .env-table th,
  .env-table td {
    padding: 6px 8px;
  }

  .code-examples {
    padding: 12px;
  }

  .env-value {
    max-width: 100px;
    font-size: 10px;
  }
}
</style>
