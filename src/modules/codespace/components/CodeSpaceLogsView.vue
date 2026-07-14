<template>
  <div class="logs-container">
    <div class="logs-screen">
      <div class="logs-header">
        <div class="header-left">
          <div class="title-section">
            <h1 class="logs-title">Runtime Logs</h1>
            <p class="logs-subtitle">{{ projectName }} / {{ codespace }}</p>
          </div>
        </div>
        <div class="header-actions">
          <label class="auto-refresh-toggle">
            <input type="checkbox" v-model="autoRefresh" />
            <span>Auto-refresh</span>
          </label>
          <button @click="refreshLogs" class="refresh-button" :disabled="isLoading">
            <ion-icon name="refresh-outline"></ion-icon>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <div class="logs-content">
        <div v-if="isLoading && !logs" class="loading-section">
          <ion-spinner></ion-spinner>
          <p>Loading runtime logs...</p>
        </div>

        <div v-else-if="!isRunning" class="empty-state">
          <ion-icon name="terminal-outline" class="empty-icon"></ion-icon>
          <h3>No Runtime Logs</h3>
          <p>This codespace isn't running as a Node app right now, or hasn't produced any output yet.</p>
        </div>

        <div v-else-if="!logs" class="empty-state">
          <ion-icon name="terminal-outline" class="empty-icon"></ion-icon>
          <h3>No Output Yet</h3>
          <p>The app is running but hasn't logged anything yet.</p>
        </div>

        <pre v-else class="log-output">{{ logs }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { ToastService } from '@/services/ToastService'

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

const isLoading = ref(true)
const isRunning = ref(false)
const logs = ref('')
const autoRefresh = ref(true)
let refreshInterval = null

const loadLogs = async () => {
  try {
    const response = await axios.get('v2/runtime-logs', {
      params: { project: props.projectName, codespace: props.codespace }
    })

    if (response.data.success) {
      isRunning.value = !!response.data.running
      logs.value = response.data.logs || ''
    }
  } catch (error) {
    console.error('Failed to load runtime logs:', error)
    ToastService.error('Failed to load runtime logs')
  } finally {
    isLoading.value = false
  }
}

const refreshLogs = async () => {
  isLoading.value = true
  await loadLogs()
}

onMounted(() => {
  loadLogs()
  refreshInterval = setInterval(() => {
    if (autoRefresh.value) loadLogs()
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<style scoped>
.logs-container {
  height: 100%;
  flex: 1;
  min-width: 0;
  background: var(--vscode-editor-background, #1e1e1e);
  color: var(--vscode-editor-foreground, #d4d4d4);
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  box-sizing: border-box;
}

.logs-screen {
  height: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  box-sizing: border-box;
}

.logs-header {
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

.title-section {
  display: flex;
  flex-direction: column;
}

.logs-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

.logs-subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--vscode-descriptionForeground, #cccccc);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.auto-refresh-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--vscode-descriptionForeground, #cccccc);
  cursor: pointer;
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

.logs-content {
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
  margin: 0;
  color: var(--vscode-descriptionForeground, #cccccc);
}

.log-output {
  margin: 0;
  padding: 16px;
  background: var(--vscode-textCodeBlock-background, #2d2d30);
  border: 1px solid var(--vscode-panel-border, #2d2d30);
  border-radius: 6px;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
  color: var(--vscode-editor-foreground, #d4d4d4);
}

@media (max-width: 768px) {
  .logs-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    padding: 12px 16px;
  }

  .header-actions {
    justify-content: space-between;
  }

  .logs-content {
    padding: 16px;
  }
}
</style>
