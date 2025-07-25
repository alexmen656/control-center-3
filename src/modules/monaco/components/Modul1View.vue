
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
  </div>
</template>


<script setup>
import { ref, onMounted, watch } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import MonacoSidebar from './MonacoSidebar.vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

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
})
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
</style>
