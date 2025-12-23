import { ref } from 'vue';

/**
 * Vue fetch composable for API calls
 * 
 * @param {String} baseUrl - Base URL for API calls
 * @returns {Object} - Functions for API calls
 */
export function useFetch(baseUrl = 'https://alex.polan.sk/backend/api') {
  const data = ref(null);
  const error = ref(null);
  const loading = ref(false);

  /**
   * Make an API call
   * 
   * @param {String} endpoint - API endpoint
   * @param {Object} options - Fetch options
   * @returns {Promise} - Promise with response data
   */
  const callApi = async (endpoint, options = {}) => {
    loading.value = true;
    error.value = null;
    data.value = null;

    try {
      // Set default headers if not provided
      if (!options.headers) {
        options.headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        };
      }

      // Füge Authorization-Header hinzu, wenn ein Token im localStorage vorhanden ist
      // oder wenn es als Parameter übergeben wurde
      const token = options.token || localStorage.getItem('authToken');
      if (token) {
        options.headers.Authorization = `Bearer ${token}`;
      }
      
      // Entferne das token-Property aus den options, bevor wir fetch aufrufen
      if (options.token) {
        delete options.token;
      }

      const response = await fetch(`${baseUrl}/${endpoint}`, options);
      
      // Parse response as JSON
      const json = await response.json();
      
      // Check if response is ok
      if (!response.ok) {
        throw new Error(json.message || `API call failed with status: ${response.status}`);
      }
      
      data.value = json;
      return json;
    } catch (err) {
      error.value = err.message || 'Unknown API error';
      return Promise.reject(error.value);
    } finally {
      loading.value = false;
    }
  };

  /**
   * GET request
   * 
   * @param {String} endpoint - API endpoint
   * @param {Object} params - URL parameters
   * @param {Object} options - Additional fetch options
   * @returns {Promise} - Promise with response data
   */
  const get = async (endpoint, params = {}, options = {}) => {
    // Add query parameters to URL if provided
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        queryParams.append(key, value);
      }
    });

    const queryString = queryParams.toString();
    const finalEndpoint = queryString ? `${endpoint}?${queryString}` : endpoint;

    return callApi(finalEndpoint, { method: 'GET', ...options });
  };

  /**
   * POST request
   * 
   * @param {String} endpoint - API endpoint
   * @param {Object} body - Request body
   * @param {Object} params - URL parameters
   * @param {Object} options - Additional fetch options
   * @returns {Promise} - Promise with response data
   */
  const post = async (endpoint, body, params = {}, options = {}) => {
    // Add query parameters to URL if provided
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        queryParams.append(key, value);
      }
    });

    const queryString = queryParams.toString();
    const finalEndpoint = queryString ? `${endpoint}?${queryString}` : endpoint;

    return callApi(finalEndpoint, {
      method: 'POST',
      body: JSON.stringify(body),
      ...options
    });
  };

  /**
   * PUT request
   * 
   * @param {String} endpoint - API endpoint
   * @param {Object} body - Request body
   * @param {Object} params - URL parameters
   * @param {Object} options - Additional fetch options
   * @returns {Promise} - Promise with response data
   */
  const put = async (endpoint, body, params = {}, options = {}) => {
    // Add query parameters to URL if provided
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        queryParams.append(key, value);
      }
    });

    const queryString = queryParams.toString();
    const finalEndpoint = queryString ? `${endpoint}?${queryString}` : endpoint;

    return callApi(finalEndpoint, {
      method: 'PUT',
      body: JSON.stringify(body),
      ...options
    });
  };

  /**
   * DELETE request
   * 
   * @param {String} endpoint - API endpoint
   * @param {Object} params - URL parameters
   * @param {Object} options - Additional fetch options
   * @returns {Promise} - Promise with response data
   */
  const del = async (endpoint, params = {}, options = {}) => {
    // Add query parameters to URL if provided
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        queryParams.append(key, value);
      }
    });

    const queryString = queryParams.toString();
    const finalEndpoint = queryString ? `${endpoint}?${queryString}` : endpoint;

    return callApi(finalEndpoint, { method: 'DELETE', ...options });
  };

  return {
    data,
    error,
    loading,
    get,
    post,
    put,
    del
  };
}

/**
 * Simple fetch wrapper for direct use without composable
 * 
 * @param {String} url - Full URL for the request
 * @param {Object} options - Fetch options
 * @returns {Promise} - Promise with response data
 */
const vueFetch = async (url, options = {}) => {
  try {
    // Set default headers if not provided
    if (!options.headers) {
      options.headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
    }

    // Füge Authorization-Header hinzu, wenn ein Token im localStorage vorhanden ist
    // oder wenn es als Parameter übergeben wurde
    const token = options.token || localStorage.getItem('authToken');
    if (token) {
      options.headers.Authorization = `Bearer ${token}`;
    }
    
    // Entferne das token-Property aus den options, bevor wir fetch aufrufen
    if (options.token) {
      delete options.token;
    }

    const response = await fetch(url, options);
    
    // Parse response as JSON
    const json = await response.json();
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error(json.message || `API call failed with status: ${response.status}`);
    }
    
    return json;
  } catch (err) {
    console.error('API Error:', err);
    return Promise.reject(err);
  }
};

export default vueFetch;
