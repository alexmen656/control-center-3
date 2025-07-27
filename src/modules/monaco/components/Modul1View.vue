<template>
  <div class="monaco-container">
    <MonacoSidebar class="sidebar" />
    <div class="monaco-editor-container">
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
      <i class="ai-icon">🤖</i>
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

const toast = ToastService

const route = useRoute()
const projectName = route.params.project || 'default-project'
const currentFile = ref('main.js')

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
const loadFile = async (filename = 'main.js') => {
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
    const response = await axios.get(`file_api.php?project=${projectName}&action=read&file=${filename}`)
    if (response.data.content !== undefined) {
      code.value = response.data.content
      currentFile.value = filename
    }
  } catch (error) {
    console.log('File not found, creating new file')
    // File doesn't exist, create it
    await saveFile(filename, code.value)
  }
}

// Save file content
const saveFile = async (filename, content) => {
  try {
    const response = await axios.put(`file_api.php?project=${projectName}`, {
      file: filename,
      content: content
    })
    
    if (response.data.success) {
      console.log('File saved successfully')
    }
  } catch (error) {
    console.error('Failed to save file:', error)
  }
}

// Auto-save when code changes
let saveTimeout = null
watch(code, (newCode) => {
  if (saveTimeout) {
    clearTimeout(saveTimeout)
  }
  
  saveTimeout = setTimeout(() => {
    saveFile(currentFile.value, newCode)
  }, 1000) // Auto-save after 1 second of inactivity
})

// Initialize project
const initializeProject = async () => {
  try {
    // Try to list files first
    const response = await axios.get(`file_api.php?project=${projectName}&action=list`)
    if (response.data.length > 0) {
      // Project exists, load the first available file
      const firstFile = response.data.find(f => f.type === 'file') || { name: 'main.js' }
      await loadFile(firstFile.name)
    } else {
      // New project, create initial file
      await saveFile('main.js', code.value)
    }
  } catch (error) {
    console.error('Failed to initialize project:', error)
    // Create default file anyway
    await saveFile('main.js', code.value)
  }
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
      await saveFile(currentFile.value, code.value)
      toast.success('Successfully saved!', 30)
      console.log('Command + S was pressed, file saved.')
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
</style>
