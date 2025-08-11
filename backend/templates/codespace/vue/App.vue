<template>
  <div class="container">
    <h1>Welcome to [{[codespaceName]}]</h1>
    <div class="app">
      <p>This is your new Vue.js codespace!</p>
      <p>Edit the files to start building your application.</p>
      
      <div class="counter">
        <button @click="count--">-</button>
        <span>Count: {{ count }}</span>
        <button @click="count++">+</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const count = ref(0)
</script>

<style scoped>
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem;
  text-align: center;
}

.app {
  background: #1a1a1a;
  padding: 2rem;
  border-radius: 8px;
  margin: 2rem 0;
}

h1 {
  font-size: 3.2em;
  line-height: 1.1;
  color: #4fc08d;
}

.counter {
  margin: 2rem 0;
}

.counter button {
  border-radius: 8px;
  border: 1px solid transparent;
  padding: 0.6em 1.2em;
  font-size: 1em;
  font-weight: 500;
  font-family: inherit;
  background-color: #1a1a1a;
  color: #ffffff;
  cursor: pointer;
  transition: border-color 0.25s;
  margin: 0 0.5rem;
}

.counter button:hover {
  border-color: #4fc08d;
}

.counter span {
  margin: 0 1rem;
  font-size: 1.2em;
}
</style>
