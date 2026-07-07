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

// ============================================
// Tool Implementations
// ============================================

async function listProjects(context) {
  try {
    const data = await cmsRequest('projects.php', {
      body: { getUserProjects: 'true' }
    }, context);

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
    const data = await cmsRequest('projects.php', {
      body: {
        createProject: 'createProject',
        projectName: args.name,
        projectIcon: args.icon || 'folder-outline'
      }
    }, context);

    if (data && typeof data === 'object' && data.success === false) {
      return formatError(data.message || 'Failed to create project');
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
    const data = await cmsRequest('projects.php', {
      body: {
        getProjectByLink: 'true',
        project: args.project
      }
    }, context);

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
    const body = {
      updateProject: 'updateProject',
      projectID: args.projectId
    };

    if (args.name) body.projectName = args.name;
    if (args.icon) body.projectIcon = args.icon;

    const data = await cmsRequest('projects.php', { body }, context);

    return formatResponse({
      success: true,
      message: data.message || 'Project updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteProject(args, context) {
  try {
    const data = await cmsRequest('projects.php', {
      body: {
        deleteProject: 'deleteProject',
        projectID: args.projectId
      }
    }, context);

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
    const data = await cmsRequest('projects.php', {
      body: {
        getProjectUsers: 'true',
        project: args.project
      }
    }, context);

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
    const data = await cmsRequest('projects.php', {
      body: {
        addUserToProject: 'true',
        project: args.project,
        email: args.email
      }
    }, context);

    return formatResponse({
      success: true,
      message: data.message || 'User added to project'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
