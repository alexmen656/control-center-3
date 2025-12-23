import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUserStore = defineStore('user', () => {
  // State
  const currentUser = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const token = ref(localStorage.getItem('authToken') || null);

  // Getters
  const getCurrentUser = computed(() => currentUser.value);
  const getIsAuthenticated = computed(() => !!token.value);
  const getIsLoading = computed(() => isLoading.value);
  const getError = computed(() => error.value);
  const getToken = computed(() => token.value);
  const getAuthError = computed(() => error.value); // Für die LoginForm.vue

  // Actions
  async function login(credentials) {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('https://alex.polan.sk/backend/api/auth.php?action=login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(credentials) // Direkt das credentials-Objekt verwenden
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Login fehlgeschlagen');
      }
      
      // Speichere Token im localStorage
      token.value = data.token;
      localStorage.setItem('authToken', data.token);
      
      // Speichere Benutzerinformationen
      currentUser.value = data.user;
      
      return data;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }
  
  async function logout() {
    isLoading.value = true;
    
    try {
      // Bei JWT-Authentifizierung müssen wir nur Client-seitig ausloggen
      currentUser.value = null;
      token.value = null;
      localStorage.removeItem('authToken');
    } catch (err) {
      error.value = err.message;
    } finally {
      isLoading.value = false;
    }
  }
  
  async function fetchCurrentUser() {
    if (!token.value) {
      return null;
    }
    
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('https://alex.polan.sk/backend/api/auth.php?action=me', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        // Bei ungültigem Token: ausloggen
        if (response.status === 401) {
          logout();
        }
        throw new Error(data.message || 'Fehler beim Abrufen des Benutzers');
      }
      
      currentUser.value = data.user;
      return data.user;
    } catch (err) {
      error.value = err.message;
      return null;
    } finally {
      isLoading.value = false;
    }
  }
  
  async function register(userData) {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('https://alex.polan.sk/backend/api/auth.php?action=register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Registrierung fehlgeschlagen');
      }
      
      return data;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }
  
  function setIsLoading(state) {
    isLoading.value = state;
  }

  return { 
    currentUser, 
    isLoading, 
    error, 
    token,
    getCurrentUser, 
    getIsAuthenticated, 
    getIsLoading, 
    getError,
    getToken,
    getAuthError,
    login, 
    logout, 
    fetchCurrentUser, 
    register, 
    setIsLoading
  };
});
