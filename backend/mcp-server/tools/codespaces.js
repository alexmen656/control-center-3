/**
 * Codespace Management Tools
 * 
 * Tools for managing project codespaces and editing source code files
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for codespaces
 */
export const codespaceTools = [
  {
    name: 'codespace_list_files',
    description: 'List source code files in a project codespace directory',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'Directory path (relative to project root)',
          default: '/'
        }
      },
      required: ['project']
    }
  },
  {
    name: 'codespace_read_file',
    description: 'Read source code file contents',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'File path (relative to project root)'
        }
      },
      required: ['project', 'path']
    }
  },
  {
    name: 'codespace_create_file',
    description: 'Create a new source code file',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'File path (relative to project root)'
        },
        content: {
          type: 'string',
          description: 'File content'
        }
      },
      required: ['project', 'path', 'content']
    }
  },
  {
    name: 'codespace_update_file',
    description: 'Update source code file contents',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'File path'
        },
        content: {
          type: 'string',
          description: 'New file content'
        }
      },
      required: ['project', 'path', 'content']
    }
  },
  {
    name: 'codespace_delete_file',
    description: 'Delete a source code file',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'File path to delete'
        }
      },
      required: ['project', 'path']
    }
  },
  {
    name: 'codespace_rename_file',
    description: 'Rename or move a source code file',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        oldPath: {
          type: 'string',
          description: 'Current file path'
        },
        newPath: {
          type: 'string',
          description: 'New file path'
        }
      },
      required: ['project', 'oldPath', 'newPath']
    }
  },
  {
    name: 'codespace_mkdir',
    description: 'Create a new directory in codespace',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        path: {
          type: 'string',
          description: 'Directory path to create'
        }
      },
      required: ['project', 'path']
    }
  },
  {
    name: 'codespace_search',
    description: 'Search for files in codespace by name or content',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        query: {
          type: 'string',
          description: 'Search query'
        },
        searchContent: {
          type: 'boolean',
          description: 'Search in file contents (not just names)',
          default: false
        }
      },
      required: ['project', 'query']
    }
  },
  {
    name: 'codespace_git_status',
    description: 'Get git status of project files',
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
    name: 'codespace_git_commit',
    description: 'Commit changes to git',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        message: {
          type: 'string',
          description: 'Commit message'
        },
        files: {
          type: 'array',
          description: 'Files to commit (empty = all changes)',
          items: { type: 'string' }
        }
      },
      required: ['project', 'message']
    }
  },
  {
    name: 'codespace_git_push',
    description: 'Push commits to remote',
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
    name: 'codespace_git_pull',
    description: 'Pull latest changes from remote',
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
 * Handle codespace tool calls
 */
export async function handleCodespaceTool(toolName, args, context) {
  switch (toolName) {
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

    default:
      return formatError(`Unknown codespace tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listFiles(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/file_api.php?project=${encodeURIComponent(args.project)}&action=list&path=${encodeURIComponent(args.path || '/')}`,
      {
        headers: { 'Authorization': context.token }
      }
    );
    const data = await response.json();

    return formatResponse({
      success: true,
      files: data.files || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function readFile(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/file_api.php?project=${encodeURIComponent(args.project)}&action=read&file=${encodeURIComponent(args.path)}`,
      {
        headers: { 'Authorization': context.token }
      }
    );
    const data = await response.json();

    return formatResponse({
      success: true,
      content: data.content,
      path: args.path
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createFile(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'create_file',
        project: args.project,
        path: args.path,
        content: args.content
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'File created successfully',
      path: args.path
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateFile(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'save_file',
        project: args.project,
        path: args.path,
        content: args.content
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'File updated successfully',
      path: args.path
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteFile(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'delete',
        project: args.project,
        path: args.path
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'File deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function renameFile(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'rename',
        project: args.project,
        oldPath: args.oldPath,
        newPath: args.newPath
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'File renamed successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createDirectory(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'create_folder',
        project: args.project,
        path: args.path
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'Directory created successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function searchFiles(args, context) {
  try {
    const data = await cmsRequest('file_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'search',
        project: args.project,
        query: args.query,
        searchContent: args.searchContent || false
      }
    }, context);

    return formatResponse({
      success: true,
      results: data.results || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitStatus(args, context) {
  try {
    const data = await cmsRequest('monaco_git_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'status',
        project: args.project
      }
    }, context);

    return formatResponse({
      success: true,
      status: data.status || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitCommit(args, context) {
  try {
    const data = await cmsRequest('monaco_git_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'commit',
        project: args.project,
        message: args.message,
        files: args.files || []
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'Changes committed successfully',
      commit: data.commit
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitPush(args, context) {
  try {
    const data = await cmsRequest('monaco_git_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'push',
        project: args.project
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'Changes pushed to remote'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function gitPull(args, context) {
  try {
    const data = await cmsRequest('monaco_git_api.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'pull',
        project: args.project
      }
    }, context);

    return formatResponse({
      success: true,
      message: 'Latest changes pulled'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
