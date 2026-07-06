import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

const enc = encodeURIComponent;

const CODESPACE_HINT =
  'A codespace is one deployable app inside a project. It must be created with codespace_create BEFORE any files can be written to it. A project can hold several codespaces, each identified by the slug returned by codespace_create. Pass that slug as "codespace"; never invent one. Do not assume a "main" codespace exists — a fresh project has no codespaces at all.';

function fileApi(args, extra = '') {
  return `file_api.php?project=${enc(args.project)}&codespace=${enc(args.codespace || 'main')}${extra}`;
}

function gitApi(args, extra = '') {
  return `monaco_git_api.php?project=${enc(args.project)}&codespace=${enc(args.codespace || 'main')}${extra}`;
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
    description: 'Build and deploy the current codespace code. Returns the live app URL (https://cs-<id>.apps.fringelo.com) and a deployment id. Poll codespace_list_deployments for build status.',
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
    name: 'codespace_sync_api_keys',
    description: 'Re-inject the keys of all APIs activated for this codespace as environment variables on the next deploy.',
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
  const data = await cmsRequest('project_codespaces.php', {
    body: { getCodespaces: 'true', project: args.project }
  }, context);
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
    case 'codespace_sync_api_keys':
      return await syncApiKeys(args, context);
    default:
      return formatError(`Unknown codespace tool: ${toolName}`);
  }
}

async function createCodespace(args, context) {
  try {
    const data = await cmsRequest('project_codespaces.php', {
      body: {
        createCodespace: 'true',
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

async function deployCodespace(args, context) {
  try {
    const data = await cmsRequest(deployApi(args, 'deploy'), {
      method: 'POST',
      contentType: 'application/json',
      body: {}
    }, context);
    const url = data.deployment?.url ? `https://${data.deployment.url}` : null;
    return formatResponse({ success: true, url, deployment: data.deployment || data });
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
    const data = await cmsRequest('codespace_apis.php', {
      body: {
        publishCodespaceAsAPI: 'true',
        project: args.project,
        codespace: args.codespace,
        name: args.name || '',
        slug: args.slug || '',
        description: args.description || '',
        rate_limit: String(args.rate_limit || 60)
      }
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function unpublishApi(args, context) {
  try {
    const data = await cmsRequest('codespace_apis.php', {
      body: {
        unpublishCodespaceAPI: 'true',
        project: args.project,
        codespace: args.codespace
      }
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}

async function syncApiKeys(args, context) {
  try {
    const data = await cmsRequest('codespace_apis.php', {
      body: {
        syncCodespaceAPIKeysToVercel: 'true',
        project: args.project,
        codespace: args.codespace
      }
    }, context);
    return backendResult(data);
  } catch (error) {
    return formatError(error.message);
  }
}
