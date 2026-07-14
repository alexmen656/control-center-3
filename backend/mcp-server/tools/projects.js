import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

export const projectTools = [
  {
    name: 'project_list',
    description: 'List all projects the user has access to',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'project_create',
    description: 'Create a new project in the CMS',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Name of the project'
        },
        icon: {
          type: 'string',
          description: 'Icon name for the project (e.g., folder-outline, rocket-outline)',
          default: 'folder-outline'
        }
      },
      required: ['name']
    }
  },
  {
    name: 'project_get',
    description: 'Get detailed information about a specific project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug identifier'
        }
      },
      required: ['project']
    }
  },
  {
    name: 'project_update',
    description: 'Update project settings',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'string',
          description: 'Project ID'
        },
        name: {
          type: 'string',
          description: 'New project name'
        },
        icon: {
          type: 'string',
          description: 'New project icon'
        }
      },
      required: ['projectId']
    }
  },
  {
    name: 'project_rename',
    description: 'Rename a project',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'string',
          description: 'Project ID'
        },
        name: {
          type: 'string',
          description: 'New project name'
        }
      },
      required: ['projectId', 'name']
    }
  },
  {
    name: 'project_delete',
    description: 'Delete a project (use with caution)',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'string',
          description: 'Project ID to delete'
        }
      },
      required: ['projectId']
    }
  },
  {
    name: 'project_get_users',
    description: 'Get users who have access to a project',
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
    name: 'project_add_user',
    description: 'Add a user to a project by email',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        email: {
          type: 'string',
          description: 'Email of the user to add'
        }
      },
      required: ['project', 'email']
    }
  }
];

export async function handleProjectTool(toolName, args, context) {
  switch (toolName) {
    case 'project_list':
      return await listProjects(context);

    case 'project_create':
      return await createProject(args, context);

    case 'project_get':
      return await getProject(args, context);

    case 'project_update':
      return await updateProject(args, context);

    case 'project_rename':
      return await renameProject(args, context);

    case 'project_delete':
      return await deleteProject(args, context);

    case 'project_get_users':
      return await getProjectUsers(args, context);

    case 'project_add_user':
      return await addUserToProject(args, context);

    default:
      return formatError(`Unknown project tool: ${toolName}`);
  }
}

async function listProjects(context) {
  try {
    const data = await cmsRequest('v2/projects', { method: 'GET' }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to list projects');
    }

    return formatResponse({
      success: true,
      projects: data.projects || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createProject(args, context) {
  try {
    const data = await cmsRequest('v2/projects', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        projectName: args.name,
        projectIcon: args.icon || 'folder-outline'
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || data.message || 'Failed to create project');
    }

    return formatResponse({
      success: true,
      message: data.message || 'Project created successfully',
      link: data.link,
      projectId: data.projectID,
      project: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getProject(args, context) {
  try {
    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.project)}`, {
      method: 'GET'
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to get project');
    }

    return formatResponse({
      success: true,
      project: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateProject(args, context) {
  try {
    const body = {};
    if (args.name) body.projectName = args.name;
    if (args.icon) body.projectIcon = args.icon;

    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.projectId)}`, {
      method: 'PUT',
      contentType: 'application/json',
      body
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to update project');
    }

    return formatResponse({
      success: true,
      message: data.message || 'Project updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function renameProject(args, context) {
  try {
    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.projectId)}`, {
      method: 'PUT',
      contentType: 'application/json',
      body: { projectName: args.name }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to rename project');
    }

    return formatResponse({
      success: true,
      message: data.message || 'Project renamed successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteProject(args, context) {
  try {
    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.projectId)}`, {
      method: 'DELETE'
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to delete project');
    }

    return formatResponse({
      success: true,
      message: data.message || 'Project deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getProjectUsers(args, context) {
  try {
    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.project)}/users`, {
      method: 'GET'
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to get project users');
    }

    return formatResponse({
      success: true,
      users: data.users || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function addUserToProject(args, context) {
  try {
    const data = await cmsRequest(`v2/projects/${encodeURIComponent(args.project)}/users`, {
      method: 'POST',
      contentType: 'application/json',
      body: { email: args.email }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to add user to project');
    }

    return formatResponse({
      success: true,
      message: data.message || 'User added to project'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
