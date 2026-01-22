/**
 * File Management Tools
 * 
 * Tools for managing project files in filesystem
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

export const fileTools = [
  {
    name: 'file_list',
    description: `List files and folders in the Control Center filesystem or project filesystem.

IMPORTANT: Returns "location" (UUID) and "projectID" for each file.
- location: Use this for file_get_signed_url "path" argument.
- projectID: Use this for file_get_signed_url "project" argument.

Example Return:
[
  {
    "displayName": "photo.jpg",
    "location": "17b95407-5ac0...jpg", // UUID
    "projectID": "7xVcWgekyPFsdsfgeUJ9u"
  }
]`,
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Optional: Project link/slug for project filesystem'
        }
      }
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
  },
  {
    name: 'file_get_signed_url',
    description: `Generate a signed URL for a file in the Control Center filesystem or project filesystem.

IMPORTANT: Use the "location" (UUID) and "projectID" from file_list!
Do NOT use the display path or project slug.

USE CASE: Use this when you need to display images or files from the filesystem in Web Builder components.

WORKFLOW:
1. Call file_list -> Get files with "location" (UUID) and "projectID"
2. Call this tool with path=location and project=projectID
3. Use the signed URL in your HTML: <img data-image src="SIGNED_URL" alt="...">`,
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'File location (UUID) from file_list'
        },
        project: {
          type: 'string',
          description: 'Project ID from file_list. Required for project files.'
        },
        validitySeconds: {
          type: 'number',
          description: 'URL validity in seconds (default: 3600 = 1 hour, max: 86400 = 24 hours)',
          default: 3600
        }
      },
      required: ['path']
    }
  },
  {
    name: 'file_get_bulk_signed_urls',
    description: `Generate signed URLs for multiple files at once.

IMPORTANT: Use "location" (UUID) and "projectID" from file_list!

WORKFLOW:
1. List files with file_list
2. Extract location and projectID for each file
3. Pass to this tool to get valid URLs

Returns an array of objects with originalPath and signedUrl.`,
    inputSchema: {
      type: 'object',
      properties: {
        files: {
          type: 'array',
          description: 'Array of file objects',
          items: {
            type: 'object',
            properties: {
              path: {
                type: 'string',
                description: 'File location (UUID)'
              },
              project: {
                type: 'string',
                description: 'Project ID'
              }
            },
            required: ['path']
          }
        },
        validitySeconds: {
          type: 'number',
          description: 'URL validity in seconds for all URLs (default: 3600)',
          default: 3600
        }
      },
      required: ['files']
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

    case 'file_upload_to_filesystem':
      return await uploadToFilesystem(args, context);

    case 'file_create_folder_in_filesystem':
      return await createFolderInFilesystem(args, context);

    case 'file_get_signed_url':
      return await getSignedUrl(args, context);

    case 'file_get_bulk_signed_urls':
      return await getBulkSignedUrls(args, context);

    default:
      return formatError(`Unknown file tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listFiles(args, context) {
  try {
    const { project } = args;
    const queryString = project ? `?project=${encodeURIComponent(project)}` : '';

    const data = await cmsRequest(`filesystem.php${queryString}`, {
      method: 'GET'
    }, context);

    const flattenTree = (items, parentPath = '') => {
      let result = [];
      if (!items) return result;

      for (const item of items) {
        const currentPath = parentPath ? `${parentPath}/${item.name}` : item.name;

        if (item.type === 'folder') {
          if (item.children) {
            result = result.concat(flattenTree(item.children, currentPath));
          }
        } else {
          result.push({
            name: item.name,
            path: currentPath,
            type: 'file',
            location: item.location,
            projectID: item.projectID || null
          });
        }
      }
      return result;
    };

    const flatList = flattenTree(data.items);

    const filesWithLocation = flatList.map(file => ({
      displayName: file.name,
      displayPath: file.path,
      location: file.location,
      type: file.type,
      projectID: file.projectID || data.rootId || null
    }));

    return formatResponse({
      success: true,
      files: filesWithLocation,
      tree: data.items,
      rootId: data.rootId,
      note: 'Use "location" and "projectID" when calling file_get_signed_url'
    });
  } catch (error) {
    return formatError(`Failed to list files: ${error.message}`);
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

async function getSignedUrl(args, context) {
  try {
    const { path, project, validitySeconds = 3600 } = args;

    const body = {
      path,
      validitySeconds: Math.min(validitySeconds, 86400)
    };

    if (project) {
      body.projectID = project;
    }

    const data = await cmsRequest('signed_url_generator.php', {
      method: 'POST',
      contentType: 'application/json',
      body
    }, context);

    if (data.success) {
      return formatResponse({
        success: true,
        url: data.url,
        path: path,
        project: project || null,
        expires: data.expires,
        expiresIn: data.expiresIn,
        usage: `Use this URL directly in HTML: <img data-image src="${data.url}" alt="...">`
      });
    } else {
      throw new Error(data.error || 'Failed to generate signed URL');
    }
  } catch (error) {
    return formatError(`Failed to generate signed URL: ${error.message}`);
  }
}

async function getBulkSignedUrls(args, context) {
  try {
    const { files, validitySeconds = 3600 } = args;

    const body = {
      files: files.map(f => ({
        path: f.path,
        projectID: f.project || null
      })),
      validitySeconds: Math.min(validitySeconds, 86400)
    };

    const data = await cmsRequest('signed_url_generator.php', {
      method: 'POST',
      contentType: 'application/json',
      body
    }, context);

    if (data.success) {
      return formatResponse({
        success: true,
        count: data.count,
        urls: data.urls.map(u => ({
          originalPath: u.originalPath,
          signedUrl: u.signedUrl,
          project: u.projectID || null,
          expires: u.expires
        })),
        usage: 'Use these URLs directly in HTML img tags with data-image attribute for Web Builder editability'
      });
    } else {
      throw new Error(data.error || 'Failed to generate signed URLs');
    }
  } catch (error) {
    return formatError(`Failed to generate bulk signed URLs: ${error.message}`);
  }
}
