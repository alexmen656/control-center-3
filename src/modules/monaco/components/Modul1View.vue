<template>
  <div class="monaco-container">
    <MonacoSidebar class="sidebar" />
    
    <!-- Modern Welcome Screen -->
    <div v-if="showWelcome" class="welcome-screen">
      <div class="welcome-backdrop"></div>
      <div class="welcome-content">
        <!-- Header mit Gradient -->
        <div class="modern-header">
          <div class="header-content">
            <h1 class="main-title">Monaco Code Editor</h1>
            <p class="subtitle">Professionelle Entwicklungsumgebung mit KI-Integration</p>
          </div>
          <div class="header-decoration">
            <div class="code-symbols">
              <span>&lt;/&gt;</span>
              <span>{ }</span>
              <span>[ ]</span>
            </div>
          </div>
        </div>

        <!-- Action Grid -->
        <div class="action-grid">
          <!-- Quick Start Card -->
          <div class="action-card primary">
            <div class="card-header">
              <div class="card-icon">
                <ion-icon name="rocket-outline"></ion-icon>
              </div>
              <h3>Schnellstart</h3>
            </div>
            <div class="card-actions">
              <button @click="createNewFile" class="action-button primary">
                <ion-icon name="document-outline"></ion-icon>
                <span>Neue Datei</span>
              </button>
              <button @click="openFolder" class="action-button">
                <ion-icon name="folder-outline"></ion-icon>
                <span>Ordner öffnen</span>
              </button>
              <button @click="cloneRepository" class="action-button">
                <ion-icon name="git-branch-outline"></ion-icon>
                <span>Repository klonen</span>
              </button>
            </div>
          </div>

          <!-- Recent Files Card -->
          <div class="action-card">
            <div class="card-header">
              <div class="card-icon">
                <ion-icon name="time-outline"></ion-icon>
              </div>
              <h3>Kürzlich verwendet</h3>
            </div>
            <div class="recent-files" v-if="recentFiles.length > 0">
              <div v-for="file in recentFiles" :key="file.name" 
                   @click="loadFile(file.name)" 
                   class="recent-item">
                <div class="file-icon">
                  <ion-icon :name="getFileIcon(file.name)" :style="{color: getFileColor(file.name)}"></ion-icon>
                </div>
                <div class="file-info">
                  <span class="file-name">{{ file.name }}</span>
                  <span class="file-path">~{{ projectName }}</span>
                </div>
                <ion-icon name="chevron-forward-outline" class="arrow-icon"></ion-icon>
              </div>
            </div>
            <div v-else class="empty-state">
              <ion-icon name="document-outline"></ion-icon>
              <p>Keine kürzlich verwendeten Dateien</p>
            </div>
          </div>

          <!-- AI Assistant Card -->
          <div class="action-card ai">
            <div class="card-header">
              <div class="card-icon ai-icon">
                <ion-icon name="sparkles-outline"></ion-icon>
              </div>
              <h3>KI-Assistent</h3>
            </div>
            <div class="card-actions">
              <button @click="showAIHelp" class="action-button ai">
                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                <span>Chat starten</span>
              </button>
              <button @click="showGitHelp" class="action-button">
                <ion-icon name="git-commit-outline"></ion-icon>
                <span>Git-Hilfe</span>
              </button>
              <button @click="showEditorFeatures" class="action-button">
                <ion-icon name="flash-outline"></ion-icon>
                <span>Features</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="shortcuts-section">
          <h4>Tastenkürzel</h4>
          <div class="shortcuts-grid">
            <div class="shortcut-item">
              <kbd>Ctrl</kbd> + <kbd>S</kbd>
              <span>Speichern</span>
            </div>
            <div class="shortcut-item">
              <kbd>Ctrl</kbd> + <kbd>N</kbd>
              <span>Neue Datei</span>
            </div>
            <div class="shortcut-item">
              <kbd>Ctrl</kbd> + <kbd>P</kbd>
              <span>Datei suchen</span>
            </div>
            <div class="shortcut-item">
              <kbd>F1</kbd>
              <span>Befehlspalette</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Editor Container -->
    <div v-else class="monaco-editor-container">
      <vue-monaco-editor
        v-model:value="code"
        :language="language"
        theme="vs-dark"
        :options="editorOptions"
        width="100%"
        height="100%"
      />
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
          <button 
            class="new-chat-btn" 
            @click="startNewChat"
            title="Neuer Chat"
          >
            🔄
          </button>
          <button 
            class="mode-toggle" 
            @click="toggleAgentMode"
            :class="{ active: agentMode }"
            :title="agentMode ? 'Agent Mode - AI kann Code bearbeiten' : 'Chat Mode - Nur Antworten'"
          >
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
        <textarea 
          v-model="userQuestion" 
          @keydown.enter.prevent="handleEnterKey"
          placeholder="Stelle eine Frage oder bitte um Code-Änderungen..."
          ref="chatInput"
        ></textarea>
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

const toast = ToastService

const route = useRoute()
const projectName = route.params.project || 'default-project'
const codespaceName = route.params.codespace || 'main'  // Default to 'main' if no codespace specified

// Initialize codespace composable
const codespace = useCodespace(route.params)

const currentFile = ref('')
const showWelcome = ref(true)
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
    
    // Use new codespace API
    const content = await codespace.loadFile(filename)
    code.value = content
    currentFile.value = filename
    showWelcome.value = false // Hide welcome screen when file is loaded
    
    // Add to recent files
    addToRecentFiles(filename)
    
    // Emit active file changed event
    window.dispatchEvent(new CustomEvent('monaco-active-file-changed', { 
      detail: { 
        filePath: filename,
        projectName 
      } 
    }))
  } catch (error) {
    console.log('File not found, creating new file')
    // File doesn't exist, create it
    await saveFile(filename, code.value)
    
    // Emit active file changed event for new file
    window.dispatchEvent(new CustomEvent('monaco-active-file-changed', { 
      detail: { 
        filePath: filename,
        projectName 
      } 
    }))
  }
}

// Save file content
const saveFile = async (filename, content, manual=false) => {
  try {
    // Use new codespace API
    await codespace.saveFile(filename, content)
    
    if(manual){
      toast.success(`Datei ${filename} erfolgreich gespeichert!`, 1000)
    }
    console.log('File saved successfully')
    // Emit event to notify sidebar about file save
    window.dispatchEvent(new CustomEvent('monaco-file-saved', { 
      detail: { 
        filename, 
        content,
        projectName 
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
        projectName 
      } 
    }))
  }
})

// Initialize project
const initializeProject = async () => {
  try {
    // Try to list files first
    const response = await axios.get(`file_api.php?project=${projectName}&action=list`)
    if (response.data.length > 0) {
      // Filter out Monaco metadata files and get regular files
      const regularFiles = response.data.filter(f => 
        f.type === 'file' && 
        !f.name.startsWith('.monaco_') &&
        !f.name.startsWith('.git')
      )
      
      // Update recent files for welcome screen
      recentFiles.value = regularFiles.slice(0, 5) // Show last 5 files
      
      // Don't auto-load any file - show welcome screen instead
      showWelcome.value = true
    } else {
      // New project, show welcome screen
      showWelcome.value = true
    }
  } catch (error) {
    console.error('Failed to initialize project:', error)
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

/* Modern Welcome Screen Design */
.welcome-screen {
  display: flex;
  flex-direction: column;
  height: 100vh;
  /*width: 100vw;*/
  background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0d1421 100%);
  position: relative;
  overflow: auto;
  padding: 0;
}

.welcome-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 30%, rgba(234, 14, 43, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(0, 174, 255, 0.08) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(138, 43, 226, 0.05) 0%, transparent 50%);
  z-index: 1;
}

.welcome-content {
  position: relative;
  z-index: 2;
  max-width: 1400px;
  margin: 0 auto;
  padding: 40px 20px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Modern Header */
.modern-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 60px;
  padding: 40px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-content {
  flex: 1;
}

.main-title {
  font-size: 3.5rem;
  font-weight: 100;
  background: linear-gradient(135deg, #ffffff 0%, #ea0e2b 50%, #00aeff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
  letter-spacing: -0.02em;
  line-height: 1.1;
}

.subtitle {
  font-size: 1.2rem;
  color: #888;
  margin: 16px 0 0 0;
  font-weight: 300;
}

.header-decoration {
  display: flex;
  align-items: center;
}

.code-symbols {
  display: flex;
  gap: 20px;
  font-family: 'JetBrains Mono', 'Monaco', 'Consolas', monospace;
  font-size: 2rem;
  color: rgba(234, 14, 43, 0.3);
}

.code-symbols span {
  animation: float 3s ease-in-out infinite;
  animation-delay: calc(var(--i) * 0.5s);
}

.code-symbols span:nth-child(1) { --i: 0; }
.code-symbols span:nth-child(2) { --i: 1; }
.code-symbols span:nth-child(3) { --i: 2; }

/* Action Grid */
.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 30px;
  margin-bottom: 60px;
  flex: 1;
}

.action-card {
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.02) 0%, rgba(255, 255, 255, 0.01) 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 32px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
}

.action-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, rgba(234, 14, 43, 0.5) 50%, transparent 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.action-card:hover::before {
  opacity: 1;
}

.action-card:hover {
  transform: translateY(-8px);
  border-color: rgba(234, 14, 43, 0.3);
  box-shadow: 
    0 20px 40px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(234, 14, 43, 0.1);
}

.action-card.primary {
  background: linear-gradient(145deg, rgba(234, 14, 43, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
}

.action-card.ai {
  background: linear-gradient(145deg, rgba(138, 43, 226, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(234, 14, 43, 0.1) 0%, rgba(234, 14, 43, 0.05) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ea0e2b;
  font-size: 24px;
}

.card-icon.ai-icon {
  background: linear-gradient(135deg, rgba(138, 43, 226, 0.1) 0%, rgba(138, 43, 226, 0.05) 100%);
  color: #8a2be2;
}

.card-header h3 {
  font-size: 1.5rem;
  font-weight: 500;
  color: #ffffff;
  margin: 0;
}

.card-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #cccccc;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  text-align: left;
}

.action-button:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(234, 14, 43, 0.3);
  transform: translateX(4px);
  color: #ffffff;
}

.action-button.primary {
  background: linear-gradient(135deg, #ea0e2b 0%, #cf3c4f 100%);
  border-color: #ea0e2b;
  color: white;
}

.action-button.primary:hover {
  background: linear-gradient(135deg, #cf3c4f 0%, #ea0e2b 100%);
  transform: translateX(4px) translateY(-2px);
  box-shadow: 0 8px 25px rgba(234, 14, 43, 0.3);
}

.action-button.ai {
  background: linear-gradient(135deg, #8a2be2 0%, #9932cc 100%);
  border-color: #8a2be2;
  color: white;
}

.action-button.ai:hover {
  background: linear-gradient(135deg, #9932cc 0%, #8a2be2 100%);
  transform: translateX(4px) translateY(-2px);
  box-shadow: 0 8px 25px rgba(138, 43, 226, 0.3);
}

/* Recent Files */
.recent-files {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.recent-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.recent-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(234, 14, 43, 0.3);
  transform: translateX(8px);
}

.file-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.file-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.file-name {
  color: #ffffff;
  font-weight: 500;
  font-size: 14px;
}

.file-path {
  color: #888;
  font-size: 12px;
}

.arrow-icon {
  color: #666;
  font-size: 16px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.recent-item:hover .arrow-icon {
  opacity: 1;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.empty-state ion-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.3;
}

/* Keyboard Shortcuts */
.shortcuts-section {
  margin-top: auto;
  padding-top: 40px;
}

.shortcuts-section h4 {
  color: #ffffff;
  font-size: 1.2rem;
  font-weight: 500;
  margin-bottom: 20px;
  text-align: center;
}

.shortcuts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  max-width: 800px;
  margin: 0 auto;
}

.shortcut-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  transition: all 0.2s ease;
}

.shortcut-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(234, 14, 43, 0.2);
}

.shortcut-item kbd {
  background: rgba(234, 14, 43, 0.1);
  border: 1px solid rgba(234, 14, 43, 0.3);
  color: #ea0e2b;
  padding: 4px 8px;
  border-radius: 4px;
  font-family: 'JetBrains Mono', 'Monaco', 'Consolas', monospace;
  font-size: 11px;
  font-weight: bold;
  margin: 0 2px;
}

.shortcut-item span {
  color: #cccccc;
  font-size: 13px;
}

/* Animations */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

/* Responsive */
@media (max-width: 768px) {
  .modern-header {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  
  .main-title {
    font-size: 2.5rem;
  }
  
  .action-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .shortcuts-grid {
    grid-template-columns: 1fr;
  }
  
  .code-symbols {
    font-size: 1.5rem;
    gap: 15px;
  }
}

.recent-file-item ion-label p {
  color: #666;
  font-size: 12px;
  margin: 4px 0 0 0;
}

.no-recent {
  text-align: center;
  padding: 20px;
}

.tips-card {
  --background: rgba(255, 255, 255, 0.05);
}

.tips-card ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.tips-card ion-chip {
  --background: rgba(255, 255, 255, 0.1);
  --color: #ffffff;
  --border-color: rgba(255, 255, 255, 0.2);
  margin: 0 8px 0 0;
}

.tips-card ion-text {
  font-size: 14px;
}

.markdown-preview {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
  background-color: #f5f5f5;
  color: #333;
  border-left: 1px solid #ddd;
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

.old-code, .new-code {
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
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-10px); }
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
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
    font-size: 16px; /* Prevent zoom on iOS */
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
