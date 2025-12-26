/**
 * Page Management Tools
 * 
 * Tools for managing CMS pages within projects
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for pages
 */
export const pageTools = [
  {
    name: 'page_list',
    description: 'List all pages in a project',
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
    name: 'page_get',
    description: 'Get detailed information about a specific page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'page_create',
    description: 'Create a new page in a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        name: {
          type: 'string',
          description: 'Page name'
        },
        slug: {
          type: 'string',
          description: 'URL slug for the page'
        },
        title: {
          type: 'string',
          description: 'Page title (SEO)'
        },
        metaDescription: {
          type: 'string',
          description: 'Meta description (SEO)'
        },
        content: {
          type: 'string',
          description: 'Page content (HTML or JSON)'
        },
        isHome: {
          type: 'boolean',
          description: 'Set as home page',
          default: false
        }
      },
      required: ['project', 'name']
    }
  },
  {
    name: 'page_update',
    description: 'Update an existing page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID to update'
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
          description: 'New page title'
        },
        metaDescription: {
          type: 'string',
          description: 'New meta description'
        },
        content: {
          type: 'string',
          description: 'New page content'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'page_delete',
    description: 'Delete a page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID to delete'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'page_duplicate',
    description: 'Duplicate an existing page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID to duplicate'
        },
        newName: {
          type: 'string',
          description: 'Name for the duplicated page'
        }
      },
      required: ['pageId', 'newName']
    }
  },
  {
    name: 'page_get_components',
    description: 'Get all components/blocks on a page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID'
        }
      },
      required: ['pageId']
    }
  },
  {
    name: 'page_update_components',
    description: 'Update components/blocks on a page',
    inputSchema: {
      type: 'object',
      properties: {
        pageId: {
          type: 'string',
          description: 'Page ID'
        },
        components: {
          type: 'array',
          description: 'Array of component objects',
          items: {
            type: 'object'
          }
        }
      },
      required: ['pageId', 'components']
    }
  }
];

/**
 * Handle page tool calls
 */
export async function handlePageTool(toolName, args, context) {
  switch (toolName) {
    case 'page_list':
      return await listPages(args, context);
      
    case 'page_get':
      return await getPage(args, context);
      
    case 'page_create':
      return await createPage(args, context);
      
    case 'page_update':
      return await updatePage(args, context);
      
    case 'page_delete':
      return await deletePage(args, context);
      
    case 'page_duplicate':
      return await duplicatePage(args, context);
      
    case 'page_get_components':
      return await getPageComponents(args, context);
      
    case 'page_update_components':
      return await updatePageComponents(args, context);
      
    default:
      return formatError(`Unknown page tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listPages(args, context) {
  try {
    const data = await cmsRequest('web_pages.php', {
      body: {
        getPagesByProject: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      pages: data.pages || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getPage(args, context) {
  try {
    const data = await cmsRequest('pages.php', {
      body: {
        getPage: 'true',
        pageId: args.pageId
      }
    }, context);
    
    return formatResponse({
      success: true,
      page: data.page || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createPage(args, context) {
  try {
    // Generate slug from name if not provided
    const slug = args.slug || args.name.toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '');
    
    const data = await cmsRequest('pages.php', {
      body: {
        createPage: 'true',
        project: args.project,
        name: args.name,
        slug: slug,
        title: args.title || args.name,
        metaDescription: args.metaDescription || '',
        content: args.content || '',
        isHome: args.isHome ? '1' : '0'
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Page created successfully',
      page: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updatePage(args, context) {
  try {
    const body = {
      updatePage: 'true',
      pageId: args.pageId
    };
    
    if (args.name) body.name = args.name;
    if (args.slug) body.slug = args.slug;
    if (args.title) body.title = args.title;
    if (args.metaDescription) body.metaDescription = args.metaDescription;
    if (args.content) body.content = args.content;
    
    const data = await cmsRequest('pages.php', { body }, context);
    
    return formatResponse({
      success: true,
      message: 'Page updated successfully',
      page: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deletePage(args, context) {
  try {
    const data = await cmsRequest('pages.php', {
      body: {
        deletePage: 'true',
        pageId: args.pageId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: data.message || 'Page deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function duplicatePage(args, context) {
  try {
    const data = await cmsRequest('pages.php', {
      body: {
        duplicatePage: 'true',
        pageId: args.pageId,
        newName: args.newName
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Page duplicated successfully',
      page: data.page
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getPageComponents(args, context) {
  try {
    const data = await cmsRequest('components.php', {
      body: {
        getComponents: 'true',
        pageId: args.pageId
      }
    }, context);
    
    return formatResponse({
      success: true,
      components: data.components || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updatePageComponents(args, context) {
  try {
    const data = await cmsRequest('components.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        updateComponents: true,
        pageId: args.pageId,
        components: args.components
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Components updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
