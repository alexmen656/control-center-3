/**
 * File Management Tools
 * 
 * Tools for managing project files and codespaces
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for files
 */
export const fileTools = [
  {
    name: 'file_list',
    description: 'List files in a project directory',
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
    name: 'file_read',
    description: 'Read file contents',
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
    name: 'file_create',
    description: 'Create a new file',
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
    name: 'file_update',
    description: 'Update file contents',
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
    name: 'file_delete',
    description: 'Delete a file',
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
    name: 'file_rename',
    description: 'Rename or move a file',
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
    name: 'file_mkdir',
    description: 'Create a new directory',
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
    name: 'file_search',
    description: 'Search for files by name or content',
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
    name: 'file_git_status',
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
    name: 'file_git_commit',
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
    name: 'file_git_push',
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
    name: 'file_git_pull',
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
  },
  {
    name: 'file_upload_to_filesystem',
    description: 'Upload a file to the Control Center filesystem or project filesystem',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Name of the file to create'
        },
        content: {
          type: 'string',
          description: 'File content (base64 encoded for binary files)'
        },
        directory: {
          type: 'string',
          description: 'Directory path where to upload the file (e.g., "Documents", "Images")',
          default: ''
        },
        project: {
          type: 'string',
          description: 'Optional: Project link/slug if uploading to project filesystem'
        },
        isBase64: {
          type: 'boolean',
          description: 'Whether the content is base64 encoded',
          default: false
        }
      },
      required: ['name', 'content']
    }
  },
  {
    name: 'file_create_folder_in_filesystem',
    description: 'Create a new folder in the Control Center filesystem or project filesystem',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Name of the folder to create'
        },
        directory: {
          type: 'string',
          description: 'Parent directory path (e.g., "Documents")',
          default: ''
        },
        project: {
          type: 'string',
          description: 'Optional: Project link/slug if creating in project filesystem'
        }
      },
      required: ['name']
    }
  }
];

/**
 * Handle file tool calls
 */
export async function handleFileTool(toolName, args, context) {
  switch (toolName) {
    case 'file_list':
      return await listFiles(args, context);

    case 'file_read':
      return await readFile(args, context);

    case 'file_create':
      return await createFile(args, context);

    case 'file_update':
      return await updateFile(args, context);

    case 'file_delete':
      return await deleteFile(args, context);

    case 'file_rename':
      return await renameFile(args, context);

    case 'file_mkdir':
      return await createDirectory(args, context);

    case 'file_search':
      return await searchFiles(args, context);

    case 'file_git_status':
      return await gitStatus(args, context);

    case 'file_git_commit':
      return await gitCommit(args, context);

    case 'file_git_push':
      return await gitPush(args, context);

    case 'file_git_pull':
      return await gitPull(args, context);

    case 'file_upload_to_filesystem':
      return await uploadToFilesystem(args, context);

    case 'file_create_folder_in_filesystem':
      return await createFolderInFilesystem(args, context);

    default:
      return formatError(`Unknown file tool: ${toolName}`);
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

async function uploadToFilesystem(args, context) {
  try {
    const { name, content, directory = '', project, isBase64 = false } = args;

    const data = await cmsRequest('filesystem_upload.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'upload_file',
        name,
        content,
        directory,
        project,
        isBase64
      }
    }, context);

    if (data.success) {
      return formatResponse({
        success: true,
        message: `File "${name}" uploaded successfully to ${project ? `project ${project}` : 'Control Center filesystem'}`,
        path: data.path
      });
    } else {
      throw new Error(data.message || 'Upload failed');
    }
  } catch (error) {
    return formatError(`Failed to upload file: ${error.message}`);
  }
}

async function createFolderInFilesystem(args, context) {
  try {
    const { name, directory = '', project } = args;

    const data = await cmsRequest('filesystem_upload.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        action: 'create_folder',
        name,
        directory,
        project
      }
    }, context);

    if (data.success) {
      return formatResponse({
        success: true,
        message: `Folder "${name}" created successfully in ${project ? `project ${project}` : 'Control Center filesystem'}`,
        path: data.path
      });
    } else {
      throw new Error(data.message || 'Folder creation failed');
    }
  } catch (error) {
    return formatError(`Failed to create folder: ${error.message}`);
  }
}
