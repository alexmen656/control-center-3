/**
 * Project Management Tools
 * 
 * Tools for managing CMS projects
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for projects
 */
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
    name: 'project_get_services',
    description: 'Get all services/modules within a project',
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
  },
  {
    name: 'project_apply_template',
    description: 'Create a new project from a template',
    inputSchema: {
      type: 'object',
      properties: {
        templateId: {
          type: 'string',
          description: 'ID of the template to use'
        },
        projectName: {
          type: 'string',
          description: 'Name for the new project'
        },
        projectIcon: {
          type: 'string',
          description: 'Icon for the new project',
          default: 'folder-outline'
        }
      },
      required: ['templateId', 'projectName']
    }
  },
  {
    name: 'project_list_templates',
    description: 'List available project templates',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  }
];

/**
 * Handle project tool calls
 */
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
      
    case 'project_get_services':
      return await getProjectServices(args, context);
      
    case 'project_get_users':
      return await getProjectUsers(args, context);
      
    case 'project_add_user':
      return await addUserToProject(args, context);
      
    case 'project_apply_template':
      return await applyTemplate(args, context);
      
    case 'project_list_templates':
      return await listTemplates(context);
      
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
    
    return formatResponse({
      success: true,
      message: data.message || 'Project created successfully',
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

async function getProjectServices(args, context) {
  try {
    const data = await cmsRequest('services.php', {
      body: {
        getServices: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      services: data.services || data
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

async function applyTemplate(args, context) {
  try {
    const data = await cmsRequest('project_templates.php', {
      body: {
        action: 'apply',
        template_id: args.templateId,
        project_name: args.projectName,
        project_icon: args.projectIcon || 'folder-outline'
      }
    }, context);
    
    return formatResponse({
      success: data.success,
      message: data.message,
      project: data.project
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listTemplates(context) {
  try {
    const response = await fetch(`${context.backendUrl}/project_templates.php?action=list`, {
      headers: { 'Authorization': context.token }
    });
    const data = await response.json();
    
    return formatResponse({
      success: true,
      templates: data.templates || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}
