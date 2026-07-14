
import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

const enc = encodeURIComponent;

export const apiTools = [
  {
    name: 'api_list',
    description: 'List APIs a project is subscribed to (metadata + usage, not running code).',
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
    name: 'api_get',
    description: 'Get detailed information about an API for a project, including its endpoints, subscription usage stats and recent activity.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        slug: {
          type: 'string',
          description: 'API slug (e.g. "weather", "stripe", "database")'
        }
      },
      required: ['project', 'slug']
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
          description: 'API ID to subscribe to (see api_available_list)'
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

    case 'api_get':
      return await getApi(args, context);

    case 'api_subscribe':
      return await subscribeToApi(args, context);

    case 'api_available_list':
      return await listAvailableApis(context);

    case 'api_sdk_docs':
      return await getSdkDocs(args, context);

    default:
      return formatError(`Unknown API tool: ${toolName}`);
  }
}


async function listApis(args, context) {
  try {
    const data = await cmsRequest(`v2/apis/project?project=${enc(args.project)}`, { method: 'GET' }, context);

    return formatResponse({
      success: true,
      apis: Array.isArray(data) ? data : (data.apis || [])
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getApi(args, context) {
  try {
    const data = await cmsRequest(
      `v2/apis/${enc(args.slug)}?project=${enc(args.project)}`,
      { method: 'GET' },
      context
    );

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to get API');
    }

    return formatResponse({
      success: true,
      api: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function subscribeToApi(args, context) {
  try {
    const data = await cmsRequest('v2/apis/subscribe', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        apiId: args.apiId
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to subscribe to API');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Subscribed to API successfully',
      apiKey: data && data.api_key
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listAvailableApis(context) {
  try {
    const data = await cmsRequest('v2/apis/available', { method: 'GET' }, context);

    return formatResponse({
      success: true,
      apis: Array.isArray(data) ? data : (data.apis || [])
    });
  } catch (error) {
    return formatError(error.message);
  }
}

function sdkImportName(slug) {
  const special = { 'user-management': 'UsersAPI' };
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

    let note = "This SDK is auto-injected into the codespace once activated for it; its API key is injected automatically as an environment variable. Import it as shown and call the methods below on the imported object.";
    if (api.slug === 'files' || api.slug === 'database') {
      note += " To store a file/image in a database field: upload it with FilesAPI.upload(file, folder) first, then save the returned result.data.id (a short path string like 'photos/<uuid>.jpg') as the field value with DatabaseAPI.insert(). Never store raw file bytes or a base64 data URI directly in a database field — text/varchar columns are size-limited and this will error on anything but tiny files. Activate both the 'files' and 'database' APIs for the codespace to use this pattern.";
    }

    return formatResponse({
      success: true,
      slug: api.slug,
      name: api.name,
      importName,
      import: `import { ${importName} } from 'apis';`,
      note,
      methods
    });
  } catch (error) {
    return formatError(error.message);
  }
}
