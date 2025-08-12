<template>
  <div class="monaco-container">
    <MonacoSidebar class="sidebar" />

    <!-- Professional Welcome Screen -->
    <div v-if="showWelcome" class="welcome-screen">
      <div class="welcome-header">
        <h1 class="welcome-title">Codespaces</h1>
        <p class="welcome-subtitle">Entwicklungsumgebung</p>
      </div>

      <div class="welcome-content">
        <div class="welcome-section">
          <h3>Start</h3>
          <div class="action-list">
            <button @click="createNewFile" class="action-item">
              <ion-icon name="document-outline"></ion-icon>
              <span>Neue Datei...</span>
              <kbd>Ctrl+N</kbd>
            </button>
            <button @click="openFolder" class="action-item">
              <ion-icon name="folder-outline"></ion-icon>
              <span>Ordner öffnen...</span>
            </button>
            <button @click="cloneRepository" class="action-item">
              <ion-icon name="git-branch-outline"></ion-icon>
              <span>Repository klonen...</span>
            </button>
          </div>
        </div>

        <div class="welcome-section" v-if="recentFiles.length > 0">
          <h3>Zuletzt verwendet</h3>
          <div class="recent-files-list">
            <button v-for="file in recentFiles" :key="file.name" @click="loadFile(file.name)" class="recent-file-item">
              <ion-icon :name="getFileIcon(file.name)" :color="getFileColor(file.name)"></ion-icon>
              <span class="file-name">{{ file.name }}</span>
              <span class="file-path">{{ codespaceName }}</span>
            </button>
          </div>
        </div>

        <div class="welcome-section">
          <h3>Hilfe</h3>
          <div class="action-list">
            <button @click="showAIHelp" class="action-item">
              <ion-icon name="chatbubble-outline"></ion-icon>
              <span>KI-Assistent öffnen</span>
            </button>
            <a href="#" class="action-item">
              <ion-icon name="book-outline"></ion-icon>
              <span>Dokumentation</span>
            </a>
            <a href="#" class="action-item">
              <ion-icon name="help-circle-outline"></ion-icon>
              <span>Tastenkürzel anzeigen</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- APIs Management View -->
    <MonacoAPIsView v-if="showAPIsView" :project-name="projectName" :codespace="codespaceName"/>

    <!-- Editor Container -->
    <div v-if="!showAPIsView && !showWelcome" class="monaco-editor-container">
      <vue-monaco-editor v-model:value="code" :language="language" theme="vs-dark" :options="editorOptions" width="100%"
        height="100%" />
    </div>

    <div class="markdown-preview" v-html="renderedMarkdown" v-if="language === 'markdown'"></div>

    <!-- Floating AI Assistant Button -->
    <div class="ai-assistant-button" @click="toggleAssistant" :class="{ active: showAssistant }">
      <i class="ai-icon">AI</i>
      <span v-if="unreadMessages > 0" class="notification-badge">{{ unreadMessages }}</span>
    </div>

    <!-- AI Assistant Chat Modal -->
    <div v-if="showAssistant" class="ai-chat-modal">
      <div class="chat-header">
        <h3>AI Assistant</h3>
        <div class="chat-controls">
          <button class="new-chat-btn" @click="startNewChat" title="Neuer Chat">
            🔄
          </button>
          <button class="mode-toggle" @click="toggleAgentMode" :class="{ active: agentMode }"
            :title="agentMode ? 'Agent Mode - AI kann Code bearbeiten' : 'Chat Mode - Nur Antworten'">
            {{ agentMode ? '🔧' : '💬' }}
          </button>
          <button class="close-btn" @click="toggleAssistant">×</button>
        </div>
      </div>

      <div class="chat-messages" ref="chatMessages">
        <div v-for="(message, index) in chatHistory" :key="index" class="message" :class="message.type">
          <div class="message-content" v-html="message.content"></div>
          <div v-if="message.replacements && message.replacements.length > 0" class="code-replacements">
            <div v-for="(replacement, rIndex) in message.replacements" :key="rIndex" class="replacement-item">
              <button @click="applyReplacement(replacement)" class="apply-btn">
                Code ändern anwenden
              </button>
              <div class="replacement-preview">
                <div class="old-code">
                  <strong>Alt:</strong>
                  <pre>{{ replacement.oldCode }}</pre>
                </div>
                <div class="new-code">
                  <strong>Neu:</strong>
                  <pre>{{ replacement.newCode }}</pre>
                </div>
              </div>
            </div>
          </div>
          <div class="message-time">{{ message.time }}</div>
        </div>
        <div v-if="isTyping" class="message ai typing">
          <div class="typing-indicator">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </div>

      <div class="chat-input">
        <textarea v-model="userQuestion" @keydown.enter.prevent="handleEnterKey"
          placeholder="Stelle eine Frage oder bitte um Code-Änderungen..." ref="chatInput"></textarea>
        <button @click="askAI" :disabled="!userQuestion.trim() || isTyping" class="send-btn">
          {{ isTyping ? '⏳' : '➤' }}
        </button>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted, watch } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import MonacoSidebar from './MonacoSidebar.vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ToastService } from "@/services/ToastService";
import { marked } from 'marked';
import { useCodespace } from '@/composables/useCodespace'
import {
  IonIcon
} from '@ionic/vue'
import MonacoAPIsView from './MonacoAPIsView.vue'

const toast = ToastService

const route = useRoute()
const projectName = route.params.project || 'default-project'
const codespaceName = route.params.codespace || 'main'  // Default to 'main' if no codespace specified

// Initialize codespace composable
const codespace = useCodespace(route.params)

const currentFile = ref('')
const showWelcome = ref(true)
const showAPIsView = ref(false)
const recentFiles = ref([])

const code = ref('// Schreibe hier deinen Code...\nconsole.log("Hello Monaco!")')
const language = ref('javascript')
const editorOptions = {
  fontSize: 16,
  minimap: { enabled: false },
  automaticLayout: true,
  formatOnType: true,
  formatOnPaste: true,
}

// Load file content
const loadFile = async (filename = 'index.html') => {
  try {
    const languageMap = {
      'js': 'javascript',
      'ts': 'typescript',
      'py': 'python',
      'html': 'html',
      'css': 'css',
      'php': 'php',
      'md': 'markdown',
      'json': 'json',
      'vue': 'vue',
      'txt': 'plaintext',
      'c': 'c',
      'cpp': 'cpp',
      'java': 'java',
      'go': 'go',
      'rb': 'ruby',
      'sh': 'shell',
      'lua': 'lua',
      'swift': 'swift',
      'kotlin': 'kotlin',
      'rust': 'rust',
      // Add more mappings as needed
    }

    language.value = languageMap[filename.split('.').pop()] || 'javascript'

    // Use codespace API to load file from specific codespace
    const content = await codespace.loadFile(filename)
    code.value = content

    if (filename == "apis") {
      showAPIsView.value = true
    } else {
      showAPIsView.value = false
      currentFile.value = filename
    }

    showWelcome.value = false // Hide welcome screen when file is loaded

    // Add to recent files
    addToRecentFiles(filename)

    // Emit active file changed event
    window.dispatchEvent(new CustomEvent('monaco-active-file-changed', {
      detail: {
        filePath: filename,
        projectName,
        codespaceName
      }
    }))
  } catch (error) {
    console.log('File not found, creating new file')
    // File doesn't exist, create it using codespace API
    await codespace.createFile(filename, code.value)
    currentFile.value = filename
    showWelcome.value = false

    // Emit active file changed event for new file
    window.dispatchEvent(new CustomEvent('monaco-active-file-changed', {
      detail: {
        filePath: filename,
        projectName,
        codespaceName
      }
    }))
  }
}

// Save file content
const saveFile = async (filename, content, manual = false) => {
  try {
    // Use codespace API to save file in specific codespace
    await codespace.saveFile(filename, content)

    if (manual) {
      toast.success(`Datei ${filename} erfolgreich gespeichert!`, 1000)
    }
    console.log('File saved successfully to codespace:', codespaceName)
    // Emit event to notify sidebar about file save
    window.dispatchEvent(new CustomEvent('monaco-file-saved', {
      detail: {
        filename,
        content,
        projectName,
        codespaceName
      }
    }))
  } catch (error) {
    console.error('Failed to save file:', error)
    toast.error(`Fehler beim Speichern von ${filename}: ${error.message}`, 1000)
  }
}

// Auto-save when code changes
let saveTimeout = null
let lastSavedContent = code.value
watch(code, (newCode) => {
  // Only auto-save if a file is currently loaded
  if (!currentFile.value || showWelcome.value) return

  if (saveTimeout) {
    clearTimeout(saveTimeout)
  }

  saveTimeout = setTimeout(async () => {
    try {
      await saveFile(currentFile.value, newCode)
      lastSavedContent = newCode
      // Optionally show a subtle success indicator (only on first save)
      if (lastSavedContent !== newCode) {
        console.log(`Auto-saved: ${currentFile.value}`)
      }
    } catch (error) {
      toast.error(`Auto-Save fehlgeschlagen für ${currentFile.value}: ${error.message}`, 1000)
    }
  }, 1000) // Auto-save after 1 second of inactivity

  // Emit change event immediately for live git updates
  if (newCode !== lastSavedContent) {
    window.dispatchEvent(new CustomEvent('monaco-file-changed', {
      detail: {
        filename: currentFile.value,
        content: newCode,
        projectName,
        codespaceName
      }
    }))
  }
})

// Initialize project
const initializeProject = async () => {
  try {
    // Use codespace API to load files from specific codespace
    await codespace.loadFiles()

    if (codespace.files.value.length > 0) {
      // Filter out Monaco metadata files and get regular files
      const regularFiles = codespace.files.value.filter(f =>
        f.type === 'file' &&
        !f.name.startsWith('.monaco_') &&
        !f.name.startsWith('.git')
      )

      // Update recent files for welcome screen
      recentFiles.value = regularFiles.slice(0, 5) // Show last 5 files

      // Don't auto-load any file - show welcome screen instead
      showWelcome.value = true
    } else {
      // New codespace, show welcome screen
      showWelcome.value = true
    }

    console.log(`Initialized codespace: ${codespaceName} in project: ${projectName}`)
  } catch (error) {
    console.error('Failed to initialize codespace:', error)
    // Show welcome screen on error
    showWelcome.value = true
  }
}

// Welcome screen functions
const createNewFile = async () => {
  try {
    const filename = prompt('Dateiname eingeben:', 'neue-datei.js')
    if (filename && filename.trim()) {
      await loadFile(filename.trim())
      toast.success(`Neue Datei ${filename} erstellt!`, 30)
    }
  } catch (error) {
    toast.error(`Fehler beim Erstellen der Datei: ${error.message}`, 30)
  }
}

const openFolder = () => {
  toast.info('Ordner-Explorer wird bald verfügbar sein!', 30)
}

const cloneRepository = () => {
  toast.info('Git-Clone-Feature wird bald verfügbar sein!', 30)
}

const showAIHelp = () => {
  showAssistant.value = true
  addAIMessage('Willkommen zum KI-Assistenten! Ich kann dir beim Programmieren helfen, Code-Probleme lösen und sogar automatisch Änderungen vorschlagen. Probier es aus!')
  toast.success('KI-Assistent geöffnet!', 30)
}

const showGitHelp = () => {
  toast.info('Git-Tutorial wird bald verfügbar sein!', 30)
}

const showEditorFeatures = () => {
  toast.info('Editor-Features-Tour wird bald verfügbar sein!', 30)
}

const addToRecentFiles = (filename) => {
  // Remove if already exists
  recentFiles.value = recentFiles.value.filter(f => f.name !== filename)
  // Add to beginning
  recentFiles.value.unshift({ name: filename, type: 'file' })
  // Keep only 5 most recent
  recentFiles.value = recentFiles.value.slice(0, 5)
}

const getFileIcon = (filename) => {
  const ext = filename.split('.').pop()?.toLowerCase()
  const iconMap = {
    'js': 'logo-javascript',
    'ts': 'logo-javascript',
    'py': 'logo-python',
    'html': 'logo-html5',
    'css': 'logo-css3',
    'php': 'code-outline',
    'md': 'document-text-outline',
    'json': 'settings-outline',
    'vue': 'logo-vue',
    'txt': 'document-outline',
    'c': 'code-outline',
    'cpp': 'code-outline',
    'java': 'cafe-outline',
    'go': 'code-outline',
    'rb': 'diamond-outline',
    'sh': 'terminal-outline',
    'lua': 'moon-outline',
    'swift': 'code-outline',
    'kotlin': 'code-outline',
    'rust': 'code-outline'
  }
  return iconMap[ext] || 'document-outline'
}

const getFileColor = (filename) => {
  const ext = filename.split('.').pop()?.toLowerCase()
  const colorMap = {
    'js': 'warning',
    'ts': 'primary',
    'py': 'success',
    'html': 'danger',
    'css': 'tertiary',
    'php': 'secondary',
    'md': 'medium',
    'json': 'dark',
    'vue': 'success',
    'txt': 'medium'
  }
  return colorMap[ext] || 'medium'
}

onMounted(() => {
  initializeProject()

  // Initialize AI Assistant with welcome message
  addAIMessage('Hallo! Ich bin dein AI Code-Assistent. Ich kann dir bei Fragen zu deinem Code helfen und im Agent-Modus sogar automatisch Änderungen vorschlagen. Wie kann ich dir helfen?');

  // Listen for file open events from sidebar
  window.addEventListener('monaco-open-file', (event) => {
    loadFile(event.detail.path)
  })

  // Listen for file refresh events from sidebar
  window.addEventListener('monaco-refresh-file', (event) => {
    if (event.detail.path === currentFile.value) {
      loadFile(currentFile.value)
    }
  })

  // Prevent default action for Command + S and trigger save with toast notification
  window.addEventListener('keydown', async (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault()
      if (currentFile.value && !showWelcome.value) {
        try {
          await saveFile(currentFile.value, code.value, true)
          //toast.success(`${currentFile.value} erfolgreich gespeichert!`, 30)
        } catch (error) {
          toast.error(`Fehler beim Speichern von ${currentFile.value}: ${error.message}`, 30)
        }
      } else {
        toast.warning('Keine Datei geöffnet zum Speichern', 30)
      }
      console.log('Command + S was pressed, file saved.')
    }

    // Ctrl+N für neue Datei
    if ((event.metaKey || event.ctrlKey) && event.key === 'n' && !event.shiftKey) {
      event.preventDefault()
      if (showWelcome.value) {
        createNewFile()
      } else {
        toast.info('Neue Datei: Nutze die Sidebar oder kehre zur Welcome-Seite zurück', 30)
      }
    }
  })
})

// Markdown rendering
const renderedMarkdown = ref('');
watch(code, (newCode) => {
  if (language.value === 'markdown') {
    renderedMarkdown.value = marked(newCode);
  } else {
    renderedMarkdown.value = '';
  }
});

// AI Assistant state
const showAssistant = ref(false);
const userQuestion = ref('');
const chatHistory = ref([]);
const isTyping = ref(false);
const agentMode = ref(true);
const unreadMessages = ref(0);
const chatMessages = ref(null);
const chatInput = ref(null);

const toggleAssistant = () => {
  showAssistant.value = !showAssistant.value;
  if (showAssistant.value) {
    unreadMessages.value = 0;
    // Focus input when opening
    setTimeout(() => {
      if (chatInput.value) {
        chatInput.value.focus();
      }
    }, 100);
  }
};

const startNewChat = () => {
  chatHistory.value = [];
  userQuestion.value = '';
  addAIMessage('Neuer Chat gestartet! Wie kann ich dir helfen?');
  toast.success('Neuer Chat gestartet!', 30);
};

const toggleAgentMode = () => {
  agentMode.value = !agentMode.value;
  addSystemMessage(agentMode.value ?
    'Agent-Modus aktiviert: AI kann automatisch Code-Änderungen vorschlagen und anwenden.' :
    'Chat-Modus aktiviert: AI antwortet nur mit Textnachrichten.'
  );
};

const addSystemMessage = (content) => {
  chatHistory.value.push({
    type: 'system',
    content: content,
    time: new Date().toLocaleTimeString()
  });
  scrollToBottom();
};

const addUserMessage = (content) => {
  chatHistory.value.push({
    type: 'user',
    content: content,
    time: new Date().toLocaleTimeString()
  });
  scrollToBottom();
};

const addAIMessage = (content, replacements = null) => {
  const message = {
    type: 'ai',
    content: marked(content),
    time: new Date().toLocaleTimeString()
  };

  if (replacements && replacements.length > 0) {
    message.replacements = replacements;
  }

  chatHistory.value.push(message);

  if (!showAssistant.value) {
    unreadMessages.value++;
  }

  scrollToBottom();
};

const scrollToBottom = () => {
  setTimeout(() => {
    if (chatMessages.value) {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight;
    }
  }, 50);
};

const handleEnterKey = (event) => {
  if (!event.shiftKey) {
    askAI();
  }
};

const askAI = async () => {
  if (!userQuestion.value.trim() || isTyping.value) return;

  const question = userQuestion.value.trim();
  addUserMessage(question);
  userQuestion.value = '';
  isTyping.value = true;

  try {
    // Prepare chat history for API (only user and assistant messages)
    const apiChatHistory = chatHistory.value
      .filter(msg => msg.type === 'user' || msg.type === 'ai')
      .map(msg => ({
        role: msg.type === 'user' ? 'user' : 'assistant',
        content: msg.type === 'ai' ? msg.content.replace(/<[^>]*>/g, '') : msg.content
      }));

    const response = await axios.post('ai_assistant.php', {
      question: question,
      filename: currentFile.value,
      fileContent: code.value,
      language: language.value,
      agentMode: agentMode.value,
      chatHistory: apiChatHistory.slice(-10) // Keep last 10 messages for context
    });

    if (response.data.success) {
      addAIMessage(response.data.answer, response.data.replacements);
    } else {
      addAIMessage(response.data.message || 'Fehler bei der Verarbeitung der Anfrage.');
    }
  } catch (error) {
    console.error('AI Assistant Error:', error);
    addAIMessage('Fehler bei der Verbindung zur AI.');
  } finally {
    isTyping.value = false;
  }
};

const applyReplacement = (replacement) => {
  const oldCode = replacement.oldCode;
  let newCode = replacement.newCode;

  // Clean up any trailing END_REPLACE that might have been included
  newCode = newCode.replace(/\s*END_REPLACE\s*$/, '').trim();

  // Check if current code is minimal (empty, single line, or just comments)
  const currentCodeTrimmed = code.value.trim();
  const isMinimalCode = !currentCodeTrimmed ||
    currentCodeTrimmed.split('\n').length <= 2 ||
    currentCodeTrimmed.match(/^\/\/.*$/) ||
    currentCodeTrimmed.match(/^\/\*.*\*\/$/) ||
    currentCodeTrimmed === 'console.log("Hello Monaco!")';

  // If oldCode is empty or current code is minimal, replace everything or append
  if (!oldCode.trim() || isMinimalCode) {
    if (isMinimalCode && newCode.includes('<!DOCTYPE html>')) {
      // Replace entire content for HTML files
      code.value = newCode;
      toast.success('Vollständiger Code wurde eingefügt!', 30);
      addSystemMessage('Kompletter Code wurde erfolgreich eingefügt.');
    } else {
      // Append to existing code
      code.value += (code.value.trim() ? '\n\n' : '') + newCode;
      toast.success('Code wurde hinzugefügt!', 30);
      addSystemMessage('Neuer Code wurde hinzugefügt.');
    }
    return;
  }

  // Try exact match first
  if (code.value.includes(oldCode)) {
    code.value = code.value.replace(oldCode, newCode);
    toast.success('Code-Änderung wurde angewendet!', 30);
    addSystemMessage('Code-Änderung wurde erfolgreich angewendet.');
    return;
  }

  // Try with normalized whitespace
  const normalizedOldCode = oldCode.replace(/\s+/g, ' ').trim();
  const normalizedCurrentCode = code.value.replace(/\s+/g, ' ').trim();

  if (normalizedCurrentCode.includes(normalizedOldCode)) {
    // Find the original text with original formatting
    const lines = code.value.split('\n');
    let found = false;

    for (let i = 0; i < lines.length; i++) {
      const lineSection = lines.slice(i, i + oldCode.split('\n').length).join('\n');
      if (lineSection.replace(/\s+/g, ' ').trim() === normalizedOldCode) {
        // Replace the section
        const beforeLines = lines.slice(0, i);
        const afterLines = lines.slice(i + oldCode.split('\n').length);
        code.value = [...beforeLines, newCode, ...afterLines].join('\n');
        found = true;
        break;
      }
    }

    if (found) {
      toast.success('Code-Änderung wurde angewendet!', 30);
      addSystemMessage('Code-Änderung wurde erfolgreich angewendet.');
    } else {
      // Fallback: append the new code
      code.value += '\n\n' + newCode;
      toast.success('Code wurde hinzugefügt (Fallback)!', 30);
      addSystemMessage('Code wurde als Fallback hinzugefügt, da der ursprüngliche Abschnitt nicht gefunden wurde.');
    }
  } else {
    // Fallback: append the new code
    code.value += '\n\n' + newCode;
    toast.success('Code wurde hinzugefügt (Fallback)!', 30);
    addSystemMessage('Code wurde als Fallback hinzugefügt, da der ursprüngliche Abschnitt nicht gefunden wurde.');
  }
};
</script>


<style scoped>
.monaco-container {
  display: flex;
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.sidebar {
  flex-shrink: 0;
}

.monaco-editor-container {
  flex: 1;
  display: flex;
  overflow: hidden;
}

/* Professional Welcome Screen - VS Code Style */
.welcome-screen {
  display: flex;
  flex-direction: column;
  height: 100vh;
  flex: 1;
  background-color: #1e1e1e;
  color: #cccccc;
  overflow: auto;
  padding: 0;
}

.welcome-header {
  padding: 32px 48px 24px;
  border-bottom: 1px solid #323233;
}

.welcome-title {
  font-size: 32px;
  font-weight: 300;
  color: #ffffff;
  margin: 0 0 8px 0;
}

.welcome-subtitle {
  font-size: 14px;
  color: #8c8c8c;
  margin: 0;
}

.welcome-content {
  display: flex;
  flex: 1;
  padding: 24px 48px;
  gap: 48px;
}

.welcome-section {
  flex: 1;
  min-width: 300px;
}

.welcome-section h3 {
  font-size: 13px;
  font-weight: 600;
  color: #cccccc;
  margin: 0 0 16px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.action-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: transparent;
  border: none;
  border-radius: 3px;
  color: #cccccc;
  font-size: 13px;
  cursor: pointer;
  transition: background-color 0.1s ease;
  text-decoration: none;
  width: 100%;
  text-align: left;
}

.action-item:hover {
  background-color: #2a2d2e;
}

.action-item ion-icon {
  font-size: 16px;
  color: #8c8c8c;
}

.action-item span {
  flex: 1;
}

.action-item kbd {
  background-color: #444444;
  border: 1px solid #5a5a5a;
  border-radius: 2px;
  padding: 2px 6px;
  font-size: 11px;
  color: #cccccc;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.recent-files-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.recent-file-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: transparent;
  border: none;
  border-radius: 3px;
  color: #cccccc;
  font-size: 13px;
  cursor: pointer;
  transition: background-color 0.1s ease;
  width: 100%;
  text-align: left;
}

.recent-file-item:hover {
  background-color: #2a2d2e;
}

.recent-file-item ion-icon {
  font-size: 16px;
}

.file-name {
  flex: 1;
  font-weight: 400;
}

.file-path {
  font-size: 11px;
  color: #8c8c8c;
}

.markdown-preview {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
  background-color: #f5f5f5;
  color: #333;
  border-left: 1px solid #ddd;
}

/* Responsive adjustments for Welcome Screen */
@media (max-width: 768px) {
  .welcome-content {
    flex-direction: column;
    gap: 24px;
    padding: 24px 24px;
  }

  .welcome-header {
    padding: 24px 24px 16px;
  }

  .welcome-title {
    font-size: 24px;
  }

  .welcome-section {
    min-width: auto;
  }
}

/* AI Assistant Styles - CMS Red Theme */
.ai-assistant-button {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #ea0e2b 0%, #cf3c4f 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(234, 14, 43, 0.3);
  cursor: pointer;
  z-index: 1000;
  transition: all 0.3s ease;
  font-size: 24px;
}

.ai-assistant-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(234, 14, 43, 0.4);
}

.ai-assistant-button.active {
  background: linear-gradient(135deg, #cf3c4f 0%, #ea0e2b 100%);
  transform: scale(1.1);
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ff4757;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  animation: pulse 2s infinite;
}

.ai-chat-modal {
  position: fixed;
  bottom: 90px;
  right: 20px;
  width: 420px;
  height: 550px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 15px 50px rgba(234, 14, 43, 0.2);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 2px solid rgba(234, 14, 43, 0.1);
}

.chat-header {
  background: linear-gradient(135deg, #ea0e2b 0%, #cf3c4f 100%);
  color: white;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.chat-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.new-chat-btn,
.mode-toggle {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  border-radius: 6px;
  padding: 6px 8px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.2s ease;
}

.new-chat-btn:hover,
.mode-toggle:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
}

.mode-toggle.active {
  background: rgba(255, 255, 255, 0.4);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
}

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: linear-gradient(to bottom, #fafafa 0%, #f5f5f5 100%);
}

.message {
  margin-bottom: 16px;
  animation: fadeIn 0.3s ease;
}

.message.user {
  text-align: right;
}

.message.user .message-content {
  background: linear-gradient(135deg, #ea0e2b 0%, #cf3c4f 100%);
  color: white;
  display: inline-block;
  padding: 12px 16px;
  border-radius: 18px 18px 4px 18px;
  max-width: 80%;
  word-wrap: break-word;
  box-shadow: 0 2px 10px rgba(234, 14, 43, 0.2);
}

.message.ai .message-content {
  background: white;
  border: 1px solid #e9ecef;
  display: inline-block;
  padding: 12px 16px;
  border-radius: 18px 18px 18px 4px;
  max-width: 80%;
  word-wrap: break-word;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.message.system .message-content {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  color: #856404;
  display: inline-block;
  padding: 8px 12px;
  border-radius: 12px;
  max-width: 80%;
  font-size: 14px;
  font-style: italic;
}

.message-time {
  font-size: 11px;
  color: #6c757d;
  margin-top: 4px;
}

.message.user .message-time {
  text-align: right;
}

.code-replacements {
  margin-top: 12px;
}

.replacement-item {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 12px;
  margin-top: 8px;
  border-left: 4px solid #ea0e2b;
}

.apply-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  margin-bottom: 8px;
  font-weight: 600;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
}

.apply-btn:hover {
  background: linear-gradient(135deg, #218838 0%, #1ea97a 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.replacement-preview {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  font-size: 12px;
}

.old-code,
.new-code {
  background: #f1f3f4;
  padding: 8px;
  border-radius: 4px;
}

.old-code {
  border-left: 3px solid #dc3545;
}

.new-code {
  border-left: 3px solid #28a745;
}

.replacement-preview pre {
  margin: 4px 0 0 0;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.typing {
  display: flex;
  align-items: center;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 18px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ea0e2b;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

.chat-input {
  display: flex;
  padding: 16px;
  background: white;
  border-top: 1px solid #e9ecef;
  gap: 8px;
}

.chat-input textarea {
  flex: 1;
  border: 2px solid #dee2e6;
  border-radius: 20px;
  padding: 10px 16px;
  resize: none;
  outline: none;
  font-family: inherit;
  min-height: 20px;
  max-height: 80px;
  transition: border-color 0.2s ease;
}

.chat-input textarea:focus {
  border-color: #ea0e2b;
  box-shadow: 0 0 0 3px rgba(234, 14, 43, 0.1);
}

.send-btn {
  background: linear-gradient(135deg, #ea0e2b 0%, #cf3c4f 100%);
  color: white;
  border: none;
  border-radius: 50%;
  width: 42px;
  height: 42px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(234, 14, 43, 0.2);
}

.send-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #cf3c4f 0%, #ea0e2b 100%);
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(234, 14, 43, 0.3);
}

.send-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
  transform: none;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes typing {

  0%,
  60%,
  100% {
    transform: translateY(0);
  }

  30% {
    transform: translateY(-10px);
  }
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }

  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }

  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Responsive adjustments */
@media (max-width: 480px) {
  .ai-chat-modal {
    width: calc(100vw - 40px);
    height: 450px;
    bottom: 90px;
    right: 20px;
  }

  .replacement-preview {
    grid-template-columns: 1fr;
  }

  .chat-input textarea {
    font-size: 16px;
    /* Prevent zoom on iOS */
  }
}

/* Ionic List Dark Theme Overrides */
ion-list {
  --background: transparent;
  --color: #cccccc;
}

ion-item {
  --background: transparent;
  --color: #cccccc;
  --border-color: rgba(255, 255, 255, 0.1);
  --inner-border-width: 0 0 1px 0;
}

ion-label h3 {
  color: #ffffff !important;
  font-weight: 500;
}

ion-label p {
  color: #999999 !important;
}

/* File Icons Styling */
ion-icon[slot="start"] {
  margin-inline-end: 16px;
}
</style>
