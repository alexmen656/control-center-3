/**
 * API Management Tools
 * 
 * Tools for managing CMS APIs and endpoints
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for APIs
 */
export const apiTools = [
  {
    name: 'api_list',
    description: 'List all APIs in a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        }
      },
      required: ['project']
    }
  },
  {
    name: 'api_create',
    description: 'Create a new API in a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        name: {
          type: 'string',
          description: 'API name'
        },
        slug: {
          type: 'string',
          description: 'API slug/identifier'
        },
        description: {
          type: 'string',
          description: 'API description'
        },
        icon: {
          type: 'string',
          description: 'API icon',
          default: 'cloud-outline'
        },
        type: {
          type: 'string',
          description: 'API type (rest, graphql)',
          default: 'rest'
        },
        baseUrl: {
          type: 'string',
          description: 'Base URL for the API'
        },
        authType: {
          type: 'string',
          description: 'Authentication type (none, api_key, bearer)',
          default: 'api_key'
        }
      },
      required: ['project', 'name', 'slug']
    }
  },
  {
    name: 'api_get',
    description: 'Get detailed information about an API',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        apiId: {
          type: 'string',
          description: 'API ID'
        }
      },
      required: ['project', 'apiId']
    }
  },
  {
    name: 'api_delete',
    description: 'Delete an API',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        apiId: {
          type: 'string',
          description: 'API ID to delete'
        }
      },
      required: ['project', 'apiId']
    }
  },
  {
    name: 'api_endpoint_create',
    description: 'Create a new endpoint for an API',
    inputSchema: {
      type: 'object',
      properties: {
        apiId: {
          type: 'string',
          description: 'API ID'
        },
        name: {
          type: 'string',
          description: 'Endpoint name'
        },
        method: {
          type: 'string',
          description: 'HTTP method (GET, POST, PUT, DELETE)',
          enum: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
        },
        endpoint: {
          type: 'string',
          description: 'Endpoint path (e.g., /users, /users/:id)'
        },
        description: {
          type: 'string',
          description: 'Endpoint description'
        },
        parameters: {
          type: 'object',
          description: 'Request parameters schema'
        },
        responseSchema: {
          type: 'object',
          description: 'Response schema'
        },
        requiresAuth: {
          type: 'boolean',
          description: 'Whether endpoint requires authentication',
          default: true
        }
      },
      required: ['apiId', 'name', 'method', 'endpoint']
    }
  },
  {
    name: 'api_endpoint_list',
    description: 'List all endpoints for an API',
    inputSchema: {
      type: 'object',
      properties: {
        apiId: {
          type: 'string',
          description: 'API ID'
        }
      },
      required: ['apiId']
    }
  },
  {
    name: 'api_subscribe',
    description: 'Subscribe a project to an available API',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        apiId: {
          type: 'string',
          description: 'API ID to subscribe to'
        }
      },
      required: ['project', 'apiId']
    }
  },
  {
    name: 'api_available_list',
    description: 'List all available APIs that can be subscribed to',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'api_generate_key',
    description: 'Generate a new API key for a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        }
      },
      required: ['project']
    }
  }
];

/**
 * Handle API tool calls
 */
export async function handleApiTool(toolName, args, context) {
  switch (toolName) {
    case 'api_list':
      return await listApis(args, context);
      
    case 'api_create':
      return await createApi(args, context);
      
    case 'api_get':
      return await getApi(args, context);
      
    case 'api_delete':
      return await deleteApi(args, context);
      
    case 'api_endpoint_create':
      return await createEndpoint(args, context);
      
    case 'api_endpoint_list':
      return await listEndpoints(args, context);
      
    case 'api_subscribe':
      return await subscribeToApi(args, context);
      
    case 'api_available_list':
      return await listAvailableApis(context);
      
    case 'api_generate_key':
      return await generateApiKey(args, context);
      
    default:
      return formatError(`Unknown API tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listApis(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        getApis: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      apis: data.apis || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createApi(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        addApi: 'true',
        project: args.project,
        name: args.name,
        slug: args.slug,
        description: args.description || '',
        icon: args.icon || 'cloud-outline',
        type: args.type || 'rest',
        base_url: args.baseUrl || '',
        auth_type: args.authType || 'api_key'
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'API created successfully',
      api: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getApi(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        getApi: 'true',
        project: args.project,
        apiId: args.apiId
      }
    }, context);
    
    return formatResponse({
      success: true,
      api: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteApi(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        deleteApi: 'true',
        project: args.project,
        apiId: args.apiId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: data.message || 'API deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createEndpoint(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        addEndpoint: true,
        apiId: args.apiId,
        name: args.name,
        method: args.method,
        endpoint: args.endpoint,
        description: args.description || '',
        parameters: args.parameters || {},
        response_schema: args.responseSchema || {},
        requires_auth: args.requiresAuth !== false
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Endpoint created successfully',
      endpoint: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listEndpoints(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        getEndpoints: 'true',
        apiId: args.apiId
      }
    }, context);
    
    return formatResponse({
      success: true,
      endpoints: data.endpoints || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function subscribeToApi(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        subscribeToApi: 'true',
        project: args.project,
        apiId: args.apiId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: data.message || 'Subscribed to API successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listAvailableApis(context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        getAvailableApis: 'true'
      }
    }, context);
    
    return formatResponse({
      success: true,
      apis: data.apis || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function generateApiKey(args, context) {
  try {
    const data = await cmsRequest('apis.php', {
      body: {
        generateApiKey: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      apiKey: data.apiKey,
      message: 'API key generated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
