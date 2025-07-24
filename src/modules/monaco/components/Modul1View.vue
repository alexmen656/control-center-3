
<template>
  <div class="monaco-container">
    <MonacoSidebar class="sidebar" />
    <div class="monaco-editor-container">
      <vue-monaco-editor
        v-model:value="code"
        language="javascript"
        theme="vs-dark"
        :options="editorOptions"
        width="100%"
        height="100%"
      />
    </div>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import MonacoSidebar from './MonacoSidebar.vue'

const code = ref('// Schreibe hier deinen Code...\nconsole.log("Hello Monaco!")')
const editorOptions = {
  fontSize: 16,
  minimap: { enabled: false },
  automaticLayout: true,
  formatOnType: true,
  formatOnPaste: true,
}
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
