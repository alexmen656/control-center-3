import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUserStore = defineStore('user', () => {
  // State
  const currentUser = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const token = ref(localStorage.getItem('controlCenter_auth_token') || null);

  // 2FA State
  const requires2FA = ref(false);
  const verificationToken = ref(null);
  const verificationEmail = ref(null);
  const verificationName = ref(null);

  // Getters
  const getCurrentUser = computed(() => currentUser.value);
  const getIsAuthenticated = computed(() => !!token.value);
  const getIsLoading = computed(() => isLoading.value);
  const getError = computed(() => error.value);
  const getToken = computed(() => token.value);
  const getAuthError = computed(() => error.value);
  const getRequires2FA = computed(() => requires2FA.value);
  const getVerificationEmail = computed(() => verificationEmail.value);
  const getVerificationName = computed(() => verificationName.value);

  async function login(credentials) {
    isLoading.value = true;
    error.value = null;
    requires2FA.value = false;

    try {
      const formData = new URLSearchParams();
      formData.append('email', credentials.email);
      formData.append('password', credentials.password);

      const response = await fetch('https://alex.polan.sk/control-center/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      });

      const data = await response.json();

      if (data.errorMessage) {
        throw new Error(data.errorMessage);
      }

      // Check if 2FA is required (new IP)
      if (data.command === 'verify-ip') {
        requires2FA.value = true;
        verificationToken.value = data.verification_token;
        verificationEmail.value = data.verification_email;
        verificationName.value = data.verification_name;
        return { requires2FA: true, email: data.verification_email };
      }

      // Login successful, save token
      if (data.token) {
        token.value = data.token;
        localStorage.setItem('controlCenter_auth_token', data.token);

        // Store user info
        currentUser.value = {
          firstname: data.firstname,
          assigned_project: data.assigned_project || null
        };

        return { success: true, user: currentUser.value };
      }

      throw new Error('Login fehlgeschlagen');
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  // 2FA Verification
  async function verify2FA(code) {
    isLoading.value = true;
    error.value = null;

    try {
      const formData = new URLSearchParams();
      formData.append('verificationToken', verificationToken.value);
      formData.append('verificationCode', code);

      const response = await fetch('https://alex.polan.sk/control-center/verification.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      });

      const data = await response.json();

      if (data.token) {
        token.value = data.token;
        localStorage.setItem('controlCenter_auth_token', data.token);

        // Reset 2FA state
        requires2FA.value = false;
        verificationToken.value = null;
        verificationEmail.value = null;
        verificationName.value = null;

        await fetchCurrentUser();
        return { success: true };
      }

      throw new Error('Ungültiger Verifizierungscode');
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  function cancel2FA() {
    requires2FA.value = false;
    verificationToken.value = null;
    verificationEmail.value = null;
    verificationName.value = null;
    error.value = null;
  }

  async function logout() {
    isLoading.value = true;

    try {
      currentUser.value = null;
      token.value = null;
      localStorage.removeItem('controlCenter_auth_token');
      // Reset 2FA state
      requires2FA.value = false;
      verificationToken.value = null;
      verificationEmail.value = null;
      verificationName.value = null;
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
      const response = await fetch('https://alex.polan.sk/control-center/sidebar.php', {
        method: 'GET',
        headers: {
          'Authorization': token.value,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();

      if (!response.ok || data.error) {
        if (response.status === 401) {
          logout();
        }
        throw new Error(data.message || 'Fehler beim Abrufen des Benutzers');
      }

      currentUser.value = data.user || data;
      return currentUser.value;
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
      throw new Error('Registrierung ist nicht verfügbar. Bitte kontaktiere einen Administrator.');
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
    requires2FA,
    verificationToken,
    verificationEmail,
    verificationName,
    getCurrentUser,
    getIsAuthenticated,
    getIsLoading,
    getError,
    getToken,
    getAuthError,
    getRequires2FA,
    getVerificationEmail,
    getVerificationName,
    login,
    logout,
    fetchCurrentUser,
    register,
    setIsLoading,
    verify2FA,
    cancel2FA
  };
});
