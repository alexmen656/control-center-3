
import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

export const apiTools = [
  {
    name: 'api_list',
    description: 'List API definitions registered in a project API catalog (metadata only, not running code).',
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
    description: 'Register an API definition (name, external baseUrl, endpoints) in the project API catalog. This stores METADATA only — it does NOT implement, host, or deploy a backend. To turn a codespace\'s own code into a live callable API, deploy it and use codespace_publish_as_api instead.',
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
    description: 'Add an endpoint definition (path, method, request/response schema) to a catalog API. This documents the endpoint; it does NOT generate backend code.',
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
    description: 'List available third-party/platform APIs a project can subscribe to and consume.',
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
  },
  {
    name: 'api_sdk_docs',
    description: 'Get JavaScript SDK usage docs for Fringelo APIs so you know EXACTLY how to use one inside a codespace. Fringelo APIs are pre-built JS SDKs that are auto-injected into a codespace and imported from \'apis\' — they are NOT REST endpoints you fetch by URL. ALWAYS call this before writing codespace code that uses an API. Without a slug it returns the list of available SDKs (slug, import name, description); with a slug it returns the import line plus every method with its signature, parameters and a runnable usage example.',
    inputSchema: {
      type: 'object',
      properties: {
        slug: {
          type: 'string',
          description: 'API slug to get full method docs for (e.g. "weather", "stripe", "github"). Omit to list all available SDKs.'
        }
      },
      required: []
    }
  }
];

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

    case 'api_sdk_docs':
      return await getSdkDocs(args, context);

    default:
      return formatError(`Unknown API tool: ${toolName}`);
  }
}


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

function sdkImportName(slug) {
  const special = { 'user-management': 'UsersAPI', 'file-storage': 'FilesAPI' };
  if (special[slug]) return special[slug];
  if (!slug) return 'Api';
  return slug.charAt(0).toUpperCase() + slug.slice(1) + 'API';
}

async function getSdkDocs(args, context) {
  try {
    const list = await cmsRequest('v2/apis/available', { method: 'GET' }, context);
    const apis = Array.isArray(list) ? list : (list.apis || []);

    if (!args.slug) {
      return formatResponse({
        success: true,
        note: "Fringelo APIs are JavaScript SDKs auto-injected into a codespace. Import them from 'apis' and call their methods — do NOT fetch them by URL. Call api_sdk_docs again with a specific slug to get that SDK's methods and examples.",
        sdks: apis.map(a => ({
          slug: a.slug,
          name: a.name,
          importName: sdkImportName(a.slug),
          description: a.description
        }))
      });
    }

    const api = apis.find(a => a.slug === args.slug);
    if (!api) {
      return formatError(`No SDK found for slug '${args.slug}'. Call api_sdk_docs without a slug to list available SDKs.`);
    }

    const detail = await cmsRequest(`v2/apis/by-id/${api.id}`, { method: 'GET' }, context);
    const importName = sdkImportName(api.slug);
    const methods = (detail.endpoints || []).map(e => ({
      name: e.name,
      signature: `${importName}.${e.endpoint}`,
      description: e.description,
      params: (e.parameters && Object.keys(e.parameters).length)
        ? Object.entries(e.parameters).map(([name, p]) => ({
          name,
          type: p.type,
          required: !!p.required,
          description: p.description
        }))
        : [],
      example: (e.example_request && e.example_request.code) ? e.example_request.code : ''
    }));

    return formatResponse({
      success: true,
      slug: api.slug,
      name: api.name,
      importName,
      import: `import { ${importName} } from 'apis';`,
      note: "This SDK is auto-injected into the codespace once activated for it; its API key is injected automatically as an environment variable. Import it as shown and call the methods below on the imported object.",
      methods
    });
  } catch (error) {
    return formatError(error.message);
  }
}
