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
    <div class="ai-assistant" @click="toggleAssistant">
      <span>🤖</span>
    </div>

    <!-- AI Assistant Modal -->
    <div v-if="showAssistant" class="ai-modal">
      <textarea v-model="userQuestion" placeholder="Stelle eine Frage..." />
      <button @click="askAI">Frage AI</button>
      <div v-if="aiResponse" class="ai-response">{{ aiResponse }}</div>
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
const aiResponse = ref('');

const toggleAssistant = () => {
  showAssistant.value = !showAssistant.value;
};

const askAI = async () => {
  if (!userQuestion.value.trim()) {
    aiResponse.value = 'Bitte stelle eine Frage.';
    return;
  }

  try {
    const response = await axios.post('ai_assistant.php', {
      question: userQuestion.value,
      fileContent: code.value,
      language: language.value
    });

    if (response.data.success) {
      aiResponse.value = response.data.answer;
    } else {
      aiResponse.value = response.data.message || 'Fehler bei der Verarbeitung der Anfrage.';
    }
  } catch (error) {
    console.error('AI Assistant Error:', error);
    aiResponse.value = 'Fehler bei der Verbindung zur AI.';
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

.ai-assistant {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 50px;
  height: 50px;
  background-color: #007bff;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  z-index: 1000;
}

.ai-modal {
  position: fixed;
  bottom: 80px;
  right: 20px;
  width: 300px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 16px;
  z-index: 1000;
}

.ai-modal textarea {
  width: 100%;
  height: 80px;
  margin-bottom: 8px;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  resize: none;
}

.ai-modal button {
  width: 100%;
  padding: 8px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.ai-modal .ai-response {
  margin-top: 8px;
  padding: 8px;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 4px;
}
</style>
