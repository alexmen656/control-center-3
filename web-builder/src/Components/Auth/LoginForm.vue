<script setup>
import { ref, computed } from 'vue';
import { useUserStore } from '@/stores/user';
import SmallUniversalSpinner from '@/Components/Loaders/SmallUniversalSpinner.vue';

const userStore = useUserStore();

const email = ref('');
const password = ref('');
const verificationCode = ref('');

const isLoading = computed(() => userStore.getIsLoading);
const authError = computed(() => userStore.getAuthError);
const requires2FA = computed(() => userStore.getRequires2FA);
const verificationEmail = computed(() => userStore.getVerificationEmail);
const verificationName = computed(() => userStore.getVerificationName);

const handleLogin = async () => {
  if (!email.value || !password.value) {
    return;
  }
  
  try {
    const result = await userStore.login({
      email: email.value,
      password: password.value
    });
    
    if (result.success) {
      email.value = '';
      password.value = '';
    }
  } catch (err) {
    // Error is already handled in store
  }
};

const handleVerify2FA = async () => {
  if (!verificationCode.value || verificationCode.value.length !== 6) {
    return;
  }
  
  try {
    const result = await userStore.verify2FA(verificationCode.value);
    
    if (result.success) {
      // 2FA erfolgreich, Login abgeschlossen
      verificationCode.value = '';
      email.value = '';
      password.value = '';
    }
  } catch (err) {
    // Error is already handled in store
    verificationCode.value = '';
  }
};

const handleCancel2FA = () => {
  userStore.cancel2FA();
  verificationCode.value = '';
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <img class="mx-auto h-12 w-auto" src="/public/logo/logo.png" alt="Control Center Web Builder">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ requires2FA ? 'Verifizierung erforderlich' : 'Anmelden' }}
        </h2>
        <p v-if="requires2FA" class="mt-2 text-center text-sm text-gray-600">
          Ein 6-stelliger Code wurde an <strong>{{ verificationEmail }}</strong> gesendet.
        </p>
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
      
      <!-- 2FA Verification Form -->
      <form v-if="requires2FA" class="mt-8 space-y-6" @submit.prevent="handleVerify2FA">
        <div class="rounded-md shadow-sm">
          <div>
            <label for="verificationCode" class="sr-only">Verifizierungscode</label>
            <input id="verificationCode" name="verificationCode" type="text" 
                   maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                   autocomplete="one-time-code" required
                   class="appearance-none rounded-md relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 text-center text-2xl tracking-widest font-mono focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10"
                   placeholder="000000" v-model="verificationCode">
          </div>
        </div>
        
        <p class="text-center text-sm text-gray-500">
          Hallo{{ verificationName ? ' ' + verificationName : '' }}, bitte gib den Code aus deiner E-Mail ein.
        </p>

        <div class="space-y-3">
          <button type="submit"
                  :disabled="isLoading || verificationCode.length !== 6"
                  class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <span class="material-symbols-outlined h-5 w-5 text-indigo-500 group-hover:text-indigo-400" 
                    v-if="!isLoading">verified_user</span>
              <SmallUniversalSpinner v-if="isLoading" class="h-5 w-5" />
            </span>
            Code bestätigen
          </button>
          
          <button type="button" @click="handleCancel2FA"
                  class="w-full flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Zurück zur Anmeldung
          </button>
        </div>
      </form>
      
      <!-- Login Form -->
      <form v-else class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">E-Mail</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
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
            Anmelden
          </button>
        </div>
        
        <div class="text-sm text-center text-gray-500">
          <p>Verwende dein Control Center Konto</p>
        </div>
      </form>
    </div>
  </div>
</template>