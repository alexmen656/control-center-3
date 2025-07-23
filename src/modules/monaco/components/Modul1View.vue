
<template>
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
</template>


<script setup>
import { ref } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'

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
.monaco-editor-container {
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
</style>
