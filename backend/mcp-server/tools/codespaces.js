import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

const enc = encodeURIComponent;

const CODESPACE_HINT =
  'A codespace is one deployable app inside a project. It must be created with codespace_create BEFORE any files can be written to it. A project can hold several codespaces, each identified by the slug returned by codespace_create. Pass that slug as "codespace"; never invent one. Do not assume a "main" codespace exists — a fresh project has no codespaces at all.';

function fileApi(args, extra = '') {
  return `v2/codespaces/files?project=${enc(args.project)}&codespace=${enc(args.codespace || 'main')}${extra}`;
}

function gitApi(args, extra = '') {
  return `v2/codespaces/git?project=${enc(args.project)}&codespace=${enc(args.codespace || 'main')}${extra}`;
}

function deployApi(args, action) {
  return `vercel_api.php?project=${enc(args.project)}&codespace=${enc(args.codespace || 'main')}&action=${action}`;
}

function backendResult(data) {
  if (data && typeof data === 'object' && 'success' in data) {
    return data.success
      ? formatResponse({ success: true, result: data })
      : formatError(data.message || 'Backend rejected the request');
  }
  return formatError(typeof data === 'string' ? data.slice(0, 500) : 'Unexpected backend response');
}

const projectProp = { type: 'string', description: 'Project link/slug' };
const codespaceProp = {
  type: 'string',
  description: 'Slug of an existing codespace, as returned by codespace_create. The codespace must already exist — writing to a slug that was never created is rejected. Do not guess or default to "main".'
};

export const codespaceTools = [
  {
    name: 'codespace_create',
    description: `Create a new codespace (deployable app) inside a project and return its slug. This is a REQUIRED first step before any file can be written — project_create alone does NOT create a codespace. ${CODESPACE_HINT} Workflow: project_create -> codespace_create (keep the returned slug) -> codespace_create_file/codespace_update_file (pass that slug as "codespace") -> codespace_deploy -> optionally codespace_publish_as_api.`,
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        name: { type: 'string', description: 'Human-readable codespace name (the slug is derived from it)' },
        description: { type: 'string', description: 'Optional description' },
        language: { type: 'string', description: 'Primary language (javascript, typescript, python, php, ...)', default: 'javascript' },
        template: { type: 'string', description: 'Starter template (e.g. node, react, vue, vanilla-js). Use "node" for an HTTP backend.', default: 'node' },
        icon: { type: 'string', description: 'Icon name', default: 'code-outline' }
      },
      required: ['project', 'name']
    }
  },
  {
    name: 'codespace_list_files',
    description: `List source files/folders in a codespace directory (recursive tree). ${CODESPACE_HINT}`,
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'Directory path relative to codespace root', default: '/' }
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_read_file',
    description: 'Read the contents of a source file in a codespace.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'File path relative to codespace root' }
      },
      required: ['project', 'path']
    }
  },
  {
    name: 'codespace_create_file',
    description: 'Create a new source file (with content) in an EXISTING codespace. The codespace must have been created with codespace_create first — if the given slug does not exist the call is rejected (no file is written). Parent folders are created as needed.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'File path relative to codespace root' },
        content: { type: 'string', description: 'File content' }
      },
      required: ['project', 'codespace', 'path', 'content']
    }
  },
  {
    name: 'codespace_update_file',
    description: 'Overwrite the contents of an existing source file in an existing codespace. Rejected if the codespace slug does not exist.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'File path relative to codespace root' },
        content: { type: 'string', description: 'New file content' }
      },
      required: ['project', 'codespace', 'path', 'content']
    }
  },
  {
    name: 'codespace_delete_file',
    description: 'Delete a source file in an existing codespace. Rejected if the codespace slug does not exist.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'File path to delete' }
      },
      required: ['project', 'codespace', 'path']
    }
  },
  {
    name: 'codespace_rename_file',
    description: 'Rename or move a source file within an existing codespace (copies content to the new path and removes the old one). Rejected if the codespace slug does not exist.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        oldPath: { type: 'string', description: 'Current file path' },
        newPath: { type: 'string', description: 'New file path' }
      },
      required: ['project', 'codespace', 'oldPath', 'newPath']
    }
  },
  {
    name: 'codespace_mkdir',
    description: 'Create a new directory in an existing codespace. Rejected if the codespace slug does not exist.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        path: { type: 'string', description: 'Directory path to create' }
      },
      required: ['project', 'codespace', 'path']
    }
  },
  {
    name: 'codespace_search',
    description: 'Search files in a codespace by file name, or by content when searchContent is true.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        query: { type: 'string', description: 'Search query' },
        searchContent: { type: 'boolean', description: 'Also match inside file contents (slower)', default: false }
      },
      required: ['project', 'query']
    }
  },
  {
    name: 'codespace_git_status',
    description: 'Show pending git changes (modified/added/deleted files) in a codespace.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_git_commit',
    description: 'Commit changes in a codespace. Committing + pushing versions your code; it does NOT deploy — call codespace_deploy to build and go live.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        message: { type: 'string', description: 'Commit message' },
        files: { type: 'array', description: 'Files to commit (empty = all changes)', items: { type: 'string' } }
      },
      required: ['project', 'message']
    }
  },
  {
    name: 'codespace_git_push',
    description: 'Push commits to the codespace git remote. This versions the code but does NOT trigger a deploy — use codespace_deploy for that.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_git_pull',
    description: 'Pull latest changes from the codespace git remote.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_deploy',
    description: 'Build and deploy the LAST COMMITTED code of a codespace — this deploys the last git commit, NOT the current working-directory files. Uncommitted changes (files written with codespace_create_file/codespace_update_file but not yet committed) are IGNORED by the build. Commit and push first with codespace_git_commit + codespace_git_push, or pass autoCommit:true to have this tool commit+push all pending changes before building. If there are uncommitted changes and autoCommit is not set, the deploy is refused with an error. Returns the live app URL (https://cs-<id>.apps.fringelo.com) and a deployment id. Poll codespace_list_deployments for build status.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        autoCommit: { type: 'boolean', description: 'If true, automatically commit and push all pending changes before building so the working directory is what gets deployed. If false (default) the deploy is refused when there are uncommitted changes.', default: false }
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_list_deployments',
    description: 'List deployments of a codespace with their status (QUEUED/BUILDING/READY/ERROR) and URLs.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_publish_as_api',
    description: 'Expose a deployed codespace as a public REST API through the gateway. Returns the gateway URL (https://gw.fringelo.com/<slug>). Use this to make a backend callable by others; api_create is only for cataloging external API metadata.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        name: { type: 'string', description: 'Public API name (defaults to codespace name)' },
        slug: { type: 'string', description: 'Gateway slug (defaults to project-codespace)' },
        description: { type: 'string', description: 'Optional API description' },
        rate_limit: { type: 'number', description: 'Requests per minute', default: 60 }
      },
      required: ['project', 'codespace']
    }
  },
  {
    name: 'codespace_unpublish_api',
    description: 'Remove a codespace from the public API gateway (unpublish).',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project', 'codespace']
    }
  },
  {
    name: 'codespace_activate_api',
    description: 'Activate a subscribed API for a specific codespace so its SDK and API key get injected on the next deploy. This is the step that used to be only clickable in the Dashboard (Dashboard → Codespace → APIs). Full workflow: api_subscribe (subscribe the project to the API) -> codespace_activate_api (activate it for THIS codespace) -> codespace_deploy. Identify the API by its slug (recommended, e.g. "database", "weather") or apiId or subscriptionId. After activating, the key is injected as an environment variable on the next deploy.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp,
        slug: { type: 'string', description: 'Slug of the subscribed API to activate (e.g. "database", "weather"). The project must already be subscribed via api_subscribe.' },
        apiId: { type: 'string', description: 'API id to activate (alternative to slug).' },
        subscriptionId: { type: 'string', description: 'Subscription id to activate (alternative to slug/apiId; skips lookup).' }
      },
      required: ['project', 'codespace']
    }
  },
  {
    name: 'codespace_sync_api_keys',
    description: 'Re-inject the keys of all APIs already ACTIVATED for this codespace as environment variables on the next deploy. This only re-syncs APIs that were activated with codespace_activate_api (or in the Dashboard) — it does NOT activate anything. If it reports 0 keys injected, no API is activated for this codespace yet; activate one with codespace_activate_api first.',
    inputSchema: {
      type: 'object',
      properties: {
        project: projectProp,
        codespace: codespaceProp
      },
      required: ['project', 'codespace']
    }
  }
];

async function fetchCodespaces(args, context) {
  const data = await cmsRequest('v2/codespaces?project=' + enc(args.project), { method: 'GET' }, context);
  if (Array.isArray(data?.codespaces)) return data.codespaces;
  if (Array.isArray(data)) return data;
  return [];
}

async function ensureCodespaceExists(args, context) {
  const slug = args.codespace || 'main';
  let list;
  try {
    list = await fetchCodespaces(args, context);
  } catch (error) {
    return formatError(`Could not verify codespace "${slug}" in project "${args.project}": ${error.message}`);
  }

  if (list.some((c) => c.slug === slug)) return null;

  const available = list.map((c) => c.slug).filter(Boolean);
  const hint = available.length
    ? `Available codespaces in this project: ${available.join(', ')}.`
    : 'This project has no codespaces yet.';
  return formatError(
    `Codespace "${slug}" does not exist in project "${args.project}". ` +
    'Create it first with codespace_create (which returns a slug), then pass that slug as "codespace". ' +
    `Do not write files before the codespace exists. ${hint}`
  );
}

export async function handleCodespaceTool(toolName, args, context) {
  if (toolName !== 'codespace_create') {
    const guard = await ensureCodespaceExists(args, context);
    if (guard) return guard;
  }
  switch (toolName) {
    case 'codespace_create':
      return await createCodespace(args, context);
    case 'codespace_list_files':
      return await listFiles(args, context);
    case 'codespace_read_file':
      return await readFile(args, context);
    case 'codespace_create_file':
      return await createFile(args, context);
    case 'codespace_update_file':
      return await updateFile(args, context);
    case 'codespace_delete_file':
      return await deleteFile(args, context);
    case 'codespace_rename_file':
      return await renameFile(args, context);
    case 'codespace_mkdir':
      return await createDirectory(args, context);
    case 'codespace_search':
      return await searchFiles(args, context);
    case 'codespace_git_status':
      return await gitStatus(args, context);
    case 'codespace_git_commit':
      return await gitCommit(args, context);
    case 'codespace_git_push':
      return await gitPush(args, context);
    case 'codespace_git_pull':
      return await gitPull(args, context);
    case 'codespace_deploy':
      return await deployCodespace(args, context);
    case 'codespace_list_deployments':
      return await listDeployments(args, context);
    case 'codespace_publish_as_api':
      return await publishAsApi(args, context);
    case 'codespace_unpublish_api':
      return await unpublishApi(args, context);
    case 'codespace_activate_api':
      return await activateApi(args, context);
    case 'codespace_sync_api_keys':
      return await syncApiKeys(args, context);
    default:
      return formatError(`Unknown codespace tool: ${toolName}`);
  }
}

async function createCodespace(args, context) {
  try {
    const data = await cmsRequest('v2/codespaces', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        name: args.name,
        description: args.description || '',
        language: args.language || 'javascript',
        template: args.template || 'node',
        icon: args.icon || 'code-outline'
      }
    }, context);

    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function listFiles(args, context) {
  try {
    const data = await cmsRequest(fileApi(args, `&action=list&path=${enc(args.path || '/')}`), { method: 'GET' }, context);
    return formatResponse({ success: true, files: Array.isArray(data) ? data : (data.files || data) });
  } catch (error) {
    return formatError(error.message);
  }
}

async function readFile(args, context) {
  try {
    const data = await cmsRequest(fileApi(args, `&action=read&file=${enc(args.path)}`), { method: 'GET' }, context);
    return formatResponse({ success: true, content: data.content, path: args.path });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createFile(args, context) {
  try {
    await cmsRequest(fileApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'create_file', path: args.path, content: args.content }
    }, context);
    return formatResponse({ success: true, message: 'File created', path: args.path });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateFile(args, context) {
  try {
    await cmsRequest(fileApi(args), {
      method: 'PUT',
      contentType: 'application/json',
      body: { file: args.path, content: args.content }
    }, context);
    return formatResponse({ success: true, message: 'File updated', path: args.path });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteFile(args, context) {
  try {
    await cmsRequest(fileApi(args), {
      method: 'DELETE',
      contentType: 'application/json',
      body: { file: args.path }
    }, context);
    return formatResponse({ success: true, message: 'File deleted', path: args.path });
  } catch (error) {
    return formatError(error.message);
  }
}

async function renameFile(args, context) {
  try {
    const read = await cmsRequest(fileApi(args, `&action=read&file=${enc(args.oldPath)}`), { method: 'GET' }, context);
    const content = read.content ?? '';
    await cmsRequest(fileApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'create_file', path: args.newPath, content }
    }, context);
    await cmsRequest(fileApi(args), {
      method: 'DELETE',
      contentType: 'application/json',
      body: { file: args.oldPath }
    }, context);
    return formatResponse({ success: true, message: 'File renamed', from: args.oldPath, to: args.newPath });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createDirectory(args, context) {
  try {
    await cmsRequest(fileApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'create_folder', path: args.path }
    }, context);
    return formatResponse({ success: true, message: 'Directory created', path: args.path });
  } catch (error) {
    return formatError(error.message);
  }
}

function flattenFiles(nodes, acc) {
  for (const node of nodes || []) {
    if (node.type === 'directory') {
      flattenFiles(node.children, acc);
    } else {
      acc.push(node);
    }
  }
  return acc;
}

async function searchFiles(args, context) {
  try {
    const tree = await cmsRequest(fileApi(args, '&action=list&path=/'), { method: 'GET' }, context);
    const files = flattenFiles(Array.isArray(tree) ? tree : (tree.files || []), []);
    const q = String(args.query).toLowerCase();
    const results = [];

    for (const file of files) {
      const nameMatch = file.name.toLowerCase().includes(q) || file.path.toLowerCase().includes(q);
      let contentMatch = false;

      if (args.searchContent && !nameMatch && results.length < 100) {
        try {
          const read = await cmsRequest(fileApi(args, `&action=read&file=${enc(file.path)}`), { method: 'GET' }, context);
          contentMatch = typeof read.content === 'string' && read.content.toLowerCase().includes(q);
        } catch {
          contentMatch = false;
        }
      }

      if (nameMatch || contentMatch) {
        results.push({ name: file.name, path: file.path, matchedContent: contentMatch });
      }
    }

    return formatResponse({ success: true, results });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitStatus(args, context) {
  try {
    const data = await cmsRequest(gitApi(args, '&action=status'), { method: 'GET' }, context);
    return formatResponse({ success: true, status: data });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitCommit(args, context) {
  try {
    const data = await cmsRequest(gitApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'commit', message: args.message, files: args.files || [] }
    }, context);
    return formatResponse({ success: true, message: 'Changes committed', commit: data.commit || data });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitPush(args, context) {
  try {
    await cmsRequest(gitApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'push' }
    }, context);
    return formatResponse({ success: true, message: 'Changes pushed to remote' });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitPull(args, context) {
  try {
    await cmsRequest(gitApi(args), {
      method: 'POST',
      contentType: 'application/json',
      body: { action: 'pull' }
    }, context);
    return formatResponse({ success: true, message: 'Latest changes pulled' });
  } catch (error) {
    return formatError(error.message);
  }
}

async function pendingChanges(args, context) {
  const status = await cmsRequest(gitApi(args, '&action=status'), { method: 'GET' }, context);
  const changes = Array.isArray(status?.changes) ? status.changes : [];
  return changes.map((c) => c.file).filter(Boolean);
}

async function deployCodespace(args, context) {
  try {
    let pending = [];
    try {
      pending = await pendingChanges(args, context);
    } catch {
      pending = [];
    }

    if (pending.length > 0) {
      if (!args.autoCommit) {
        return formatError(
          `Uncommitted changes present (${pending.length}: ${pending.slice(0, 10).join(', ')}${pending.length > 10 ? ', …' : ''}). ` +
          'codespace_deploy builds the LAST git commit, so these changes would NOT be deployed. ' +
          'Commit and push first (codespace_git_commit + codespace_git_push), or call codespace_deploy again with autoCommit:true.'
        );
      }
      await cmsRequest(gitApi(args), {
        method: 'POST',
        contentType: 'application/json',
        body: { action: 'commit', message: 'Auto-commit before deploy', files: [] }
      }, context);
      await cmsRequest(gitApi(args), {
        method: 'POST',
        contentType: 'application/json',
        body: { action: 'push' }
      }, context);
    }

    const data = await cmsRequest(deployApi(args, 'deploy'), {
      method: 'POST',
      contentType: 'application/json',
      body: {}
    }, context);
    const url = data.deployment?.url ? `https://${data.deployment.url}` : null;
    return formatResponse({
      success: true,
      url,
      autoCommitted: pending.length > 0 && !!args.autoCommit,
      deployment: data.deployment || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listDeployments(args, context) {
  try {
    const data = await cmsRequest(deployApi(args, 'deployments'), { method: 'GET' }, context);
    return formatResponse({ success: true, deployments: data.deployments || data });
  } catch (error) {
    return formatError(error.message);
  }
}

async function publishAsApi(args, context) {
  try {
    const body = { project: args.project, codespace: args.codespace };
    if (args.name) body.name = args.name;
    if (args.slug) body.slug = args.slug;
    if (args.description) body.description = args.description;
    if (args.rate_limit) body.rate_limit = args.rate_limit;

    const data = await cmsRequest('v2/codespace-apis/publish', {
      method: 'POST',
      contentType: 'application/json',
      body
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function unpublishApi(args, context) {
  try {
    const data = await cmsRequest('v2/codespace-apis/unpublish', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        codespace: args.codespace
      }
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function activateApi(args, context) {
  try {
    let subscriptionId = args.subscriptionId;

    if (!subscriptionId) {
      const list = await cmsRequest(
        `v2/codespace-apis/?project=${enc(args.project)}&codespace=${enc(args.codespace)}`,
        { method: 'GET' },
        context
      );
      const apis = Array.isArray(list) ? list : (list.apis || []);
      const match = apis.find((a) =>
        (args.slug && a.slug === args.slug) ||
        (args.apiId && String(a.api_id) === String(args.apiId))
      );

      if (!match) {
        const available = apis.map((a) => a.slug).filter(Boolean);
        return formatError(
          `No subscribed API matching ${args.slug ? `slug "${args.slug}"` : `apiId "${args.apiId}"`} was found for this project. ` +
          'Subscribe the project first with api_subscribe. ' +
          (available.length ? `Subscribed APIs available to activate: ${available.join(', ')}.` : 'This project has no subscribed APIs yet.')
        );
      }
      subscriptionId = match.subscription_id;
    }

    if (!subscriptionId) {
      return formatError('Provide one of: slug, apiId, or subscriptionId to identify the API to activate.');
    }

    const data = await cmsRequest('v2/codespace-apis/activate', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        codespace: args.codespace,
        subscription_id: subscriptionId
      }
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function syncApiKeys(args, context) {
  try {
    const data = await cmsRequest('v2/codespace-apis/sync', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        codespace: args.codespace
      }
    }, context);

    if (data && typeof data === 'object' && data.success && Array.isArray(data.synced) && data.synced.length === 0) {
      return formatResponse({
        success: true,
        synced: [],
        reason: 'No API is activated for this codespace yet, so nothing was injected. Activate one with codespace_activate_api (or in Dashboard → Codespace → APIs), then deploy.',
        result: data
      });
    }

    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}
