/**
 * CMS API Helper
 * 
 * Common functions for making requests to the Control Center backend
 */

/**
 * Make authenticated request to CMS backend
 */
export async function cmsRequest(endpoint, options = {}, context) {
  const url = `${context.backendUrl}/${endpoint}`;
  
  const headers = {
    'Authorization': context.token,
    'Content-Type': options.contentType || 'application/x-www-form-urlencoded',
    ...options.headers
  };
  
  const fetchOptions = {
    method: options.method || 'POST',
    headers
  };
  
  if (options.body) {
    if (options.contentType === 'application/json') {
      fetchOptions.body = JSON.stringify(options.body);
    } else if (options.body instanceof URLSearchParams) {
      fetchOptions.body = options.body;
    } else {
      fetchOptions.body = new URLSearchParams(options.body);
    }
  }
  
  try {
    const response = await fetch(url, fetchOptions);
    const data = await response.json();
    return data;
  } catch (error) {
    throw new Error(`CMS API Error: ${error.message}`);
  }
}

/**
 * Format tool response
 */
export function formatResponse(data, isError = false) {
  return {
    content: [{
      type: 'text',
      text: typeof data === 'string' ? data : JSON.stringify(data, null, 2)
    }],
    isError
  };
}

/**
 * Format error response
 */
export function formatError(message) {
  return formatResponse(`Error: ${message}`, true);
}
