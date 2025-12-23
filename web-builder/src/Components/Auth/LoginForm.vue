<script setup>
import { ref, computed } from 'vue';
import { useUserStore } from '@/stores/user';
import SmallUniversalSpinner from '@/Components/Loaders/SmallUniversalSpinner.vue';

const userStore = useUserStore();

const username = ref('');
const password = ref('');
const showRegisterForm = ref(false);
const email = ref('');

const isLoading = computed(() => userStore.getIsLoading);
const authError = computed(() => userStore.getAuthError);

const handleLogin = async () => {
  if (!username.value || !password.value) {
    return;
  }
  
  const success = await userStore.login({
    username: username.value,
    password: password.value
  });
  
  if (success) {
    // Login war erfolgreich, die App wird durch Watcher in App.vue neu gerendert
    username.value = '';
    password.value = '';
  }
};

const handleRegister = async () => {
  if (!username.value || !password.value || !email.value) {
    return;
  }
  
  const success = await userStore.register({
    username: username.value,
    password: password.value,
    email: email.value
  });
  
  if (success) {
    // Nach erfolgreicher Registrierung zurück zum Login-Formular wechseln
    showRegisterForm.value = false;
    username.value = '';
    password.value = '';
    email.value = '';
    // Optional: Direkt einloggen nach Registrierung
    // await handleLogin();
  }
};

const toggleForm = () => {
  showRegisterForm.value = !showRegisterForm.value;
  username.value = '';
  password.value = '';
  email.value = '';
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <img class="mx-auto h-12 w-auto" src="/public/logo/logo.png" alt="Control Center Web Builder">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ showRegisterForm ? 'Registrieren' : 'Anmelden' }}
        </h2>
      </div>
      
      <div v-if="authError" class="rounded-md bg-red-50 p-4 mb-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <span class="material-symbols-outlined text-red-400">error_outline</span>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">{{ authError }}</h3>
          </div>
        </div>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="showRegisterForm ? handleRegister() : handleLogin()">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="username" class="sr-only">Benutzername</label>
            <input id="username" name="username" type="text" autocomplete="username" required
                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                   placeholder="Benutzername" v-model="username">
          </div>
          
          <div v-if="showRegisterForm">
            <label for="email" class="sr-only">E-Mail</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                   placeholder="E-Mail Adresse" v-model="email">
          </div>
          
          <div>
            <label for="password" class="sr-only">Passwort</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                   placeholder="Passwort" v-model="password">
          </div>
        </div>

        <div>
          <button type="submit"
                  :disabled="isLoading"
                  class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <span class="material-symbols-outlined h-5 w-5 text-indigo-500 group-hover:text-indigo-400" 
                    v-if="!isLoading">lock</span>
              <SmallUniversalSpinner v-if="isLoading" class="h-5 w-5" />
            </span>
            {{ showRegisterForm ? 'Registrieren' : 'Anmelden' }}
          </button>
        </div>
        
        <div class="text-sm text-center">
          <a href="#" @click.prevent="toggleForm" class="font-medium text-indigo-600 hover:text-indigo-500">
            {{ showRegisterForm ? 'Zurück zur Anmeldung' : 'Neu hier? Registrieren' }}
          </a>
        </div>
      </form>
    </div>
  </div>
</template>