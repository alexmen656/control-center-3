/**
 * Web Builder Tools
 * 
 * Tools for managing Web Builder projects, pages, components, and domains
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for Web Builder
 */
export const webBuilderTools = [
  // ============================================
  // Project Management
  // ============================================
  {
    name: 'webbuilder_project_list',
    description: 'List all Web Builder projects the user has access to. Returns projects with their pages and linked Control Center project info.',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Optional: Filter by Control Center project link/slug'
        }
      },
      required: []
    }
  },
  {
    name: 'webbuilder_project_create',
    description: 'Create a new Web Builder project linked to a Control Center project. This automatically creates a homepage. IMPORTANT: Use project_list first to get the correct project link/slug.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Name of the Web Builder project'
        },
        description: {
          type: 'string',
          description: 'Description of the project'
        },
        ccProjectLink: {
          type: 'string',
          description: 'The Control Center project LINK/SLUG (NOT the numeric ID). Example: "my-project", "demo-website". Use project_list to find the correct link.'
        }
      },
      required: ['name', 'ccProjectLink']
    }
  },
  {
    name: 'webbuilder_project_get',
    description: 'Get detailed information about a specific Web Builder project including all pages',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID'
        }
      },
      required: ['projectId']
    }
  },
  {
    name: 'webbuilder_project_update',
    description: 'Update Web Builder project settings (name, description)',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID'
        },
        name: {
          type: 'string',
          description: 'New project name'
        },
        description: {
          type: 'string',
          description: 'New project description'
        }
      },
      required: ['projectId']
    }
  },
  {
    name: 'webbuilder_project_delete',
    description: 'Delete a Web Builder project and all its pages/components. Use with caution!',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID to delete'
        }
      },
      required: ['projectId']
    }
  },

  // ============================================
  // Page Management
  // ============================================
  {
    name: 'webbuilder_page_list',
    description: 'List all pages in a Web Builder project',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID'
        }
      },
      required: ['projectId']
    }
  },
  {
    name: 'webbuilder_page_create',
    description: 'Create a new page in a Web Builder project',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID'
        },
        name: {
          type: 'string',
          description: 'Page name (e.g., "About Us", "Contact")'
        },
        slug: {
          type: 'string',
          description: 'URL slug for the page (e.g., "about-us"). Auto-generated from name if not provided'
        },
        title: {
          type: 'string',
          description: 'SEO title for the page. Defaults to name if not provided'
        },
        metaDescription: {
          type: 'string',
          description: 'SEO meta description for the page'
        },
        isHome: {
          type: 'boolean',
          description: 'Set this page as the homepage. Only one page can be home.',
          default: false
        }
      },
      required: ['projectId', 'name']
    }
  },
  {
    name: 'webbuilder_page_get',
    description: 'Get detailed information about a specific page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'webbuilder_page_update',
    description: 'Update page settings (name, slug, title, meta description, home status)',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        },
        name: {
          type: 'string',
          description: 'New page name'
        },
        slug: {
          type: 'string',
          description: 'New URL slug'
        },
        title: {
          type: 'string',
          description: 'New SEO title'
        },
        metaDescription: {
          type: 'string',
          description: 'New SEO meta description'
        },
        isHome: {
          type: 'boolean',
          description: 'Set as homepage'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'webbuilder_page_delete',
    description: 'Delete a page from a Web Builder project. Cannot delete the only page.',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID to delete'
        }
      },
      required: ['pageId']
    }
  },

  // ============================================
  // Component Management
  // ============================================
  {
    name: 'webbuilder_components_get',
    description: 'Get all components (HTML sections) for a specific page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'webbuilder_component_add',
    description: 'Add a new HTML component to a page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID to add component to'
        },
        componentId: {
          type: 'string',
          description: 'Unique identifier for the component (e.g., "hero-section-1", "about-block")'
        },
        htmlCode: {
          type: 'string',
          description: 'HTML code for the component'
        }
      },
      required: ['pageId', 'componentId', 'htmlCode']
    }
  },
  {
    name: 'webbuilder_component_update',
    description: 'Update HTML code of an existing component',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        },
        componentId: {
          type: 'string',
          description: 'Component ID to update'
        },
        htmlCode: {
          type: 'string',
          description: 'New HTML code for the component'
        }
      },
      required: ['pageId', 'componentId', 'htmlCode']
    }
  },
  {
    name: 'webbuilder_component_delete',
    description: 'Delete a component from a page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        },
        componentId: {
          type: 'string',
          description: 'Component ID to delete'
        }
      },
      required: ['pageId', 'componentId']
    }
  },
  {
    name: 'webbuilder_components_replace_all',
    description: 'Replace all components on a page with a new set of components. Useful for complete page rebuilds.',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'number',
          description: 'Page ID'
        },
        components: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              id: {
                type: 'string',
                description: 'Component ID'
              },
              html_code: {
                type: 'string',
                description: 'HTML code for the component'
              }
            },
            required: ['id', 'html_code']
          },
          description: 'Array of components with id and html_code'
        }
      },
      required: ['pageId', 'components']
    }
  },

  // ============================================
  // Domain Management
  // ============================================
  {
    name: 'webbuilder_main_domain_get',
    description: 'Get the main domain configured for a Control Center project. The main domain is required before configuring a Web Builder subdomain. Format: xxx.sites.control-center.eu',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        }
      },
      required: ['ccProject']
    }
  },
  {
    name: 'webbuilder_main_domain_configure',
    description: 'Configure the main domain for a Control Center project. This creates a subdomain under sites.control-center.eu (e.g., "myproject" becomes "myproject.sites.control-center.eu"). MUST be done before configuring Web Builder subdomain.',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain name (e.g., "myproject" for myproject.sites.control-center.eu). Only lowercase letters, numbers, and hyphens allowed.'
        },
        userId: {
          type: 'number',
          description: 'User ID (from user_profile or context)'
        }
      },
      required: ['ccProject', 'subdomain', 'userId']
    }
  },
  {
    name: 'webbuilder_domain_get',
    description: 'Get the Web Builder subdomain configured for a project. This is a subdomain of the main domain (e.g., blog.myproject.sites.control-center.eu)',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        }
      },
      required: ['ccProject']
    }
  },
  {
    name: 'webbuilder_domain_configure',
    description: 'Configure a Web Builder subdomain. IMPORTANT: The project MUST have a main domain configured first (use webbuilder_main_domain_get to check, webbuilder_main_domain_configure to create). The Web Builder subdomain will be: subdomain.main_domain (e.g., "blog" + "myproject.sites.control-center.eu" = "blog.myproject.sites.control-center.eu")',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        },
        subdomain: {
          type: 'string',
          description: 'Web Builder subdomain prefix (e.g., "blog", "shop", "app"). Min 3 characters, lowercase alphanumeric with hyphens.'
        },
        enabled: {
          type: 'boolean',
          description: 'Enable or disable the subdomain',
          default: true
        }
      },
      required: ['ccProject', 'subdomain']
    }
  },
  {
    name: 'webbuilder_domain_delete',
    description: 'Delete the domain configuration for a Web Builder project. Removes DNS record.',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        }
      },
      required: ['ccProject']
    }
  },
  {
    name: 'webbuilder_domains_list',
    description: 'List all configured Web Builder domains across all projects',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },

  // ============================================
  // Templates & Quick Actions
  // ============================================
  {
    name: 'webbuilder_create_landing_page',
    description: 'Quick action: Create a complete landing page with hero, features, and CTA sections',
    inputSchema: {
      type: 'object',
      properties: {
        projectId: {
          type: 'number',
          description: 'Web Builder project ID'
        },
        pageName: {
          type: 'string',
          description: 'Name for the landing page',
          default: 'Landing Page'
        },
        headline: {
          type: 'string',
          description: 'Main headline for the hero section'
        },
        subheadline: {
          type: 'string',
          description: 'Subheadline/description text'
        },
        ctaText: {
          type: 'string',
          description: 'Call-to-action button text',
          default: 'Get Started'
        },
        ctaLink: {
          type: 'string',
          description: 'Call-to-action button link',
          default: '#contact'
        },
        features: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              title: { type: 'string' },
              description: { type: 'string' },
              icon: { type: 'string' }
            }
          },
          description: 'Array of features to display'
        }
      },
      required: ['projectId', 'headline']
    }
  }
];

/**
 * Handle Web Builder tool calls
 */
export async function handleWebBuilderTool(name, args, context) {
  switch (name) {
    // ============================================
    // Project Tools
    // ============================================
    case 'webbuilder_project_list':
      return await listProjects(args, context);
    
    case 'webbuilder_project_create':
      return await createProject(args, context);
    
    case 'webbuilder_project_get':
      return await getProject(args, context);
    
    case 'webbuilder_project_update':
      return await updateProject(args, context);
    
    case 'webbuilder_project_delete':
      return await deleteProject(args, context);
    
    // ============================================
    // Page Tools
    // ============================================
    case 'webbuilder_page_list':
      return await listPages(args, context);
    
    case 'webbuilder_page_create':
      return await createPage(args, context);
    
    case 'webbuilder_page_get':
      return await getPage(args, context);
    
    case 'webbuilder_page_update':
      return await updatePage(args, context);
    
    case 'webbuilder_page_delete':
      return await deletePage(args, context);
    
    // ============================================
    // Component Tools
    // ============================================
    case 'webbuilder_components_get':
      return await getComponents(args, context);
    
    case 'webbuilder_component_add':
      return await addComponent(args, context);
    
    case 'webbuilder_component_update':
      return await updateComponent(args, context);
    
    case 'webbuilder_component_delete':
      return await deleteComponent(args, context);
    
    case 'webbuilder_components_replace_all':
      return await replaceAllComponents(args, context);
    
    // ============================================
    // Domain Tools
    // ============================================
    case 'webbuilder_main_domain_get':
      return await getMainDomain(args, context);
    
    case 'webbuilder_main_domain_configure':
      return await configureMainDomain(args, context);
    
    case 'webbuilder_domain_get':
      return await getDomain(args, context);
    
    case 'webbuilder_domain_configure':
      return await configureDomain(args, context);
    
    case 'webbuilder_domain_delete':
      return await deleteDomain(args, context);
    
    case 'webbuilder_domains_list':
      return await listDomains(args, context);
    
    // ============================================
    // Quick Actions
    // ============================================
    case 'webbuilder_create_landing_page':
      return await createLandingPage(args, context);
    
    default:
      return formatError(`Unknown Web Builder tool: ${name}`);
  }
}

// ============================================
// Project Implementation
// ============================================

async function listProjects(args, context) {
  try {
    const response = await fetch(`${context.backendUrl}/web-builder/projects.php`, {
      method: 'GET',
      headers: {
        'Authorization': context.token,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to list projects');
    }
    
    // Filter by CC project if provided
    let projects = data.data || data;
    if (args.ccProject && Array.isArray(projects)) {
      projects = projects.filter(p => 
        p.control_center_project?.link === args.ccProject ||
        p.project_id === args.ccProject
      );
    }
    
    return formatResponse({
      success: true,
      count: Array.isArray(projects) ? projects.length : 1,
      projects: projects
    });
  } catch (error) {
    return formatError(`Failed to list Web Builder projects: ${error.message}`);
  }
}

async function createProject(args, context) {
  try {
    // Support both ccProjectLink (new) and ccProjectId (legacy)
    const projectLink = args.ccProjectLink || args.ccProjectId;
    
    if (!projectLink) {
      return formatError('ccProjectLink is required. Use project_list to find the correct project link/slug.');
    }
    
    const response = await fetch(`${context.backendUrl}/web-builder/projects.php`, {
      method: 'POST',
      headers: {
        'Authorization': context.token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name: args.name,
        description: args.description || '',
        project_id: projectLink
      })
    });
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to create project');
    }
    
    return formatResponse({
      success: true,
      message: 'Web Builder project created successfully',
      project: data.data || data
    });
  } catch (error) {
    return formatError(`Failed to create Web Builder project: ${error.message}`);
  }
}

async function getProject(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/projects.php?id=${args.projectId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Project not found');
    }
    
    return formatResponse(data);
  } catch (error) {
    return formatError(`Failed to get project: ${error.message}`);
  }
}

async function updateProject(args, context) {
  try {
    const body = {};
    if (args.name) body.name = args.name;
    if (args.description !== undefined) body.description = args.description;
    
    const response = await fetch(
      `${context.backendUrl}/web-builder/projects.php?id=${args.projectId}`,
      {
        method: 'PUT',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to update project');
    }
    
    return formatResponse({
      success: true,
      message: 'Project updated successfully',
      project: data.data || data
    });
  } catch (error) {
    return formatError(`Failed to update project: ${error.message}`);
  }
}

async function deleteProject(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/projects.php?id=${args.projectId}`,
      {
        method: 'DELETE',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to delete project');
    }
    
    return formatResponse({
      success: true,
      message: 'Project deleted successfully'
    });
  } catch (error) {
    return formatError(`Failed to delete project: ${error.message}`);
  }
}

// ============================================
// Page Implementation
// ============================================

async function listPages(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/pages.php?project_id=${args.projectId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to list pages');
    }
    
    return formatResponse({
      success: true,
      projectId: args.projectId,
      count: Array.isArray(data) ? data.length : 1,
      pages: data
    });
  } catch (error) {
    return formatError(`Failed to list pages: ${error.message}`);
  }
}

async function createPage(args, context) {
  try {
    const body = {
      name: args.name,
      project_id: args.projectId
    };
    
    if (args.slug) body.slug = args.slug;
    if (args.title) body.title = args.title;
    if (args.metaDescription) body.meta_description = args.metaDescription;
    if (args.isHome !== undefined) body.is_home = args.isHome ? 1 : 0;
    
    const response = await fetch(
      `${context.backendUrl}/web-builder/pages.php?project_id=${args.projectId}`,
      {
        method: 'POST',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to create page');
    }
    
    return formatResponse({
      success: true,
      message: 'Page created successfully',
      page: data
    });
  } catch (error) {
    return formatError(`Failed to create page: ${error.message}`);
  }
}

async function getPage(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/pages.php?id=${args.pageId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Page not found');
    }
    
    return formatResponse(data);
  } catch (error) {
    return formatError(`Failed to get page: ${error.message}`);
  }
}

async function updatePage(args, context) {
  try {
    const body = {};
    if (args.name) body.name = args.name;
    if (args.slug) body.slug = args.slug;
    if (args.title) body.title = args.title;
    if (args.metaDescription !== undefined) body.meta_description = args.metaDescription;
    if (args.isHome !== undefined) body.is_home = args.isHome ? 1 : 0;
    
    const response = await fetch(
      `${context.backendUrl}/web-builder/pages.php?id=${args.pageId}`,
      {
        method: 'PUT',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to update page');
    }
    
    return formatResponse({
      success: true,
      message: 'Page updated successfully',
      page: data
    });
  } catch (error) {
    return formatError(`Failed to update page: ${error.message}`);
  }
}

async function deletePage(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/pages.php?id=${args.pageId}`,
      {
        method: 'DELETE',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to delete page');
    }
    
    return formatResponse({
      success: true,
      message: 'Page deleted successfully'
    });
  } catch (error) {
    return formatError(`Failed to delete page: ${error.message}`);
  }
}

// ============================================
// Component Implementation
// ============================================

async function getComponents(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/components.php?page_id=${args.pageId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to get components');
    }
    
    return formatResponse({
      success: true,
      pageId: args.pageId,
      count: Array.isArray(data) ? data.length : 0,
      components: data
    });
  } catch (error) {
    return formatError(`Failed to get components: ${error.message}`);
  }
}

async function addComponent(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/components.php?page_id=${args.pageId}`,
      {
        method: 'POST',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: args.componentId,
          html_code: args.htmlCode
        })
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to add component');
    }
    
    return formatResponse({
      success: true,
      message: 'Component added successfully',
      componentId: args.componentId
    });
  } catch (error) {
    return formatError(`Failed to add component: ${error.message}`);
  }
}

async function updateComponent(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/components.php?page_id=${args.pageId}`,
      {
        method: 'PUT',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: args.componentId,
          html_code: args.htmlCode
        })
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to update component');
    }
    
    return formatResponse({
      success: true,
      message: 'Component updated successfully',
      componentId: args.componentId
    });
  } catch (error) {
    return formatError(`Failed to update component: ${error.message}`);
  }
}

async function deleteComponent(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/components.php?page_id=${args.pageId}&component_id=${args.componentId}`,
      {
        method: 'DELETE',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to delete component');
    }
    
    return formatResponse({
      success: true,
      message: 'Component deleted successfully'
    });
  } catch (error) {
    return formatError(`Failed to delete component: ${error.message}`);
  }
}

async function replaceAllComponents(args, context) {
  try {
    const response = await fetch(
      `${context.backendUrl}/web-builder/components.php?page_id=${args.pageId}`,
      {
        method: 'POST',
        headers: {
          'Authorization': context.token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(args.components)
      }
    );
    
    const data = await response.json();
    
    if (data.error) {
      return formatError(data.message || 'Failed to replace components');
    }
    
    return formatResponse({
      success: true,
      message: 'All components replaced successfully',
      count: args.components.length
    });
  } catch (error) {
    return formatError(`Failed to replace components: ${error.message}`);
  }
}

// ============================================
// Domain Implementation
// ============================================

async function getMainDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'get',
      project: args.ccProject
    });
    
    const response = await cmsRequest('project_domain.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (response.error) {
      return formatResponse({
        success: true,
        hasMainDomain: false,
        message: 'No main domain configured for this project. Use webbuilder_main_domain_configure to create one.',
        hint: 'Main domain format: subdomain.sites.control-center.eu'
      });
    }
    
    return formatResponse({
      success: true,
      hasMainDomain: true,
      domain: response.domain,
      message: `Main domain is: ${response.domain}`
    });
  } catch (error) {
    return formatError(`Failed to get main domain: ${error.message}`);
  }
}

async function configureMainDomain(args, context) {
  try {
    // Validate subdomain
    if (!/^[a-z0-9-]+$/.test(args.subdomain)) {
      return formatError('Subdomain must contain only lowercase letters, numbers, and hyphens');
    }
    
    if (args.subdomain.length < 2) {
      return formatError('Subdomain must be at least 2 characters long');
    }
    
    const params = new URLSearchParams({
      action: 'connect',
      project: args.ccProject,
      domain: args.subdomain.toLowerCase(),
      user_id: args.userId.toString()
    });
    
    const response = await cmsRequest('project_domain.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (response.error) {
      return formatError(response.error);
    }
    
    return formatResponse({
      success: true,
      message: 'Main domain configured successfully',
      domain: response.domain,
      fullDomain: response.domain,
      note: 'You can now configure a Web Builder subdomain using webbuilder_domain_configure'
    });
  } catch (error) {
    return formatError(`Failed to configure main domain: ${error.message}`);
  }
}

async function getDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'get',
      project: args.ccProject
    });
    
    const response = await cmsRequest('web_builder_domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to get domain');
    }
    
    return formatResponse({
      success: true,
      domain: response.data
    });
  } catch (error) {
    return formatError(`Failed to get domain: ${error.message}`);
  }
}

async function configureDomain(args, context) {
  try {
    // First, check if main domain exists
    const mainDomainParams = new URLSearchParams({
      action: 'get',
      project: args.ccProject
    });
    
    const mainDomainResponse = await cmsRequest('project_domain.php', {
      method: 'POST',
      body: mainDomainParams
    }, context);
    
    if (mainDomainResponse.error || !mainDomainResponse.domain) {
      return formatError(
        'No main domain configured for this project. ' +
        'You must first configure a main domain using webbuilder_main_domain_configure. ' +
        'The main domain will be: subdomain.sites.control-center.eu'
      );
    }
    
    const mainDomain = mainDomainResponse.domain;
    
    // Validate subdomain
    if (!/^[a-z0-9-]+$/.test(args.subdomain)) {
      return formatError('Subdomain must contain only lowercase letters, numbers, and hyphens');
    }
    
    if (args.subdomain.length < 3) {
      return formatError('Subdomain must be at least 3 characters long');
    }
    
    const params = new URLSearchParams({
      action: 'save',
      project: args.ccProject,
      subdomain: args.subdomain.toLowerCase(),
      main_domain: mainDomain,
      is_enabled: args.enabled !== false ? 'true' : 'false'
    });
    
    const response = await cmsRequest('web_builder_domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to configure domain');
    }
    
    const fullDomain = `${args.subdomain.toLowerCase()}.${mainDomain}`;
    
    return formatResponse({
      success: true,
      message: response.message || 'Domain configured successfully',
      subdomain: args.subdomain.toLowerCase(),
      mainDomain: mainDomain,
      fullDomain: fullDomain,
      domain: response.domain || fullDomain,
      sslNote: 'SSL certificate will be automatically created within a few minutes'
    });
  } catch (error) {
    return formatError(`Failed to configure domain: ${error.message}`);
  }
}

async function deleteDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'delete',
      project: args.ccProject
    });
    
    const response = await cmsRequest('web_builder_domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to delete domain');
    }
    
    return formatResponse({
      success: true,
      message: 'Domain deleted successfully'
    });
  } catch (error) {
    return formatError(`Failed to delete domain: ${error.message}`);
  }
}

async function listDomains(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'list'
    });
    
    const response = await cmsRequest('web_builder_domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to list domains');
    }
    
    return formatResponse({
      success: true,
      count: response.domains?.length || 0,
      domains: response.domains || []
    });
  } catch (error) {
    return formatError(`Failed to list domains: ${error.message}`);
  }
}

// ============================================
// Quick Actions Implementation
// ============================================

async function createLandingPage(args, context) {
  try {
    // First, create the page
    const pageResponse = await createPage({
      projectId: args.projectId,
      name: args.pageName || 'Landing Page',
      slug: (args.pageName || 'landing-page').toLowerCase().replace(/[^a-z0-9]+/g, '-'),
      title: args.headline,
      metaDescription: args.subheadline || args.headline
    }, context);
    
    // Parse the page response to get the page ID
    let pageData;
    try {
      pageData = JSON.parse(pageResponse.content[0].text);
    } catch {
      return formatError('Failed to parse page creation response');
    }
    
    if (!pageData.success || !pageData.page?.id) {
      return formatError('Failed to create landing page');
    }
    
    const pageId = pageData.page.id;
    
    // Generate components
    const components = [];
    
    // Hero Section
    components.push({
      id: `hero-${Date.now()}`,
      html_code: generateHeroSection(args.headline, args.subheadline, args.ctaText, args.ctaLink)
    });
    
    // Features Section (if features provided)
    if (args.features && args.features.length > 0) {
      components.push({
        id: `features-${Date.now()}`,
        html_code: generateFeaturesSection(args.features)
      });
    }
    
    // CTA Section
    components.push({
      id: `cta-${Date.now()}`,
      html_code: generateCtaSection(args.ctaText || 'Get Started', args.ctaLink || '#contact')
    });
    
    // Add components to the page
    const componentsResponse = await replaceAllComponents({
      pageId: pageId,
      components: components
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Landing page created successfully',
      page: pageData.page,
      componentsAdded: components.length
    });
  } catch (error) {
    return formatError(`Failed to create landing page: ${error.message}`);
  }
}

// ============================================
// HTML Template Generators
// ============================================

function generateHeroSection(headline, subheadline, ctaText, ctaLink) {
  return `
<section class="hero-section" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 60px 20px;">
  <div class="hero-content" style="max-width: 800px;">
    <h1 style="font-size: 3.5rem; margin-bottom: 1.5rem; font-weight: 700;">${escapeHtml(headline)}</h1>
    ${subheadline ? `<p style="font-size: 1.5rem; margin-bottom: 2rem; opacity: 0.9;">${escapeHtml(subheadline)}</p>` : ''}
    <a href="${escapeHtml(ctaLink || '#')}" style="display: inline-block; padding: 15px 40px; background: white; color: #667eea; font-size: 1.2rem; font-weight: 600; text-decoration: none; border-radius: 50px; transition: transform 0.3s ease;">
      ${escapeHtml(ctaText || 'Get Started')}
    </a>
  </div>
</section>`.trim();
}

function generateFeaturesSection(features) {
  const featureCards = features.map(f => `
    <div class="feature-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
      ${f.icon ? `<div style="font-size: 3rem; margin-bottom: 1rem;">${escapeHtml(f.icon)}</div>` : ''}
      <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: #333;">${escapeHtml(f.title)}</h3>
      <p style="color: #666; line-height: 1.6;">${escapeHtml(f.description)}</p>
    </div>
  `).join('');
  
  return `
<section class="features-section" style="padding: 80px 20px; background: #f8f9fa;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #333;">Features</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
      ${featureCards}
    </div>
  </div>
</section>`.trim();
}

function generateCtaSection(ctaText, ctaLink) {
  return `
<section class="cta-section" style="padding: 80px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">
  <div style="max-width: 600px; margin: 0 auto;">
    <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">Ready to Get Started?</h2>
    <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">Join thousands of satisfied customers today.</p>
    <a href="${escapeHtml(ctaLink)}" style="display: inline-block; padding: 15px 40px; background: white; color: #667eea; font-size: 1.2rem; font-weight: 600; text-decoration: none; border-radius: 50px;">
      ${escapeHtml(ctaText)}
    </a>
  </div>
</section>`.trim();
}

function escapeHtml(str) {
  if (!str) return '';
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
