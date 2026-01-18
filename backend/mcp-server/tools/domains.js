/**
 * Domain Management Tools
 * 
 * Tools for managing domains with Cloudflare integration
 * Includes Super Admin features for userID 152
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for domain management
 */
export const domainTools = [
  {
    name: 'domain_list',
    description: 'List all domains managed by the current user. Super Admin (userID 152) sees all domains in the system.',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'domain_list_available',
    description: 'List available domains for project/webbuilder configuration. Super Admin (userID 152) can select ANY domain from domain management for ANY project. Normal users only see their own domains. Use this for domain selection dropdowns in Project Info, Web Builder, and Codespaces.',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'domain_add',
    description: 'Add a new domain to the domain management system. Automatically fetches Cloudflare zone information if available.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain name (e.g., example.com)'
        },
        registrar: {
          type: 'string',
          description: 'Domain registrar (e.g., Namecheap, GoDaddy, Cloudflare)'
        },
        buyDate: {
          type: 'string',
          description: 'Purchase date (YYYY-MM-DD format)'
        },
        expiryDate: {
          type: 'string',
          description: 'Expiration date (YYYY-MM-DD format)'
        },
        autoRenew: {
          type: 'boolean',
          description: 'Whether domain auto-renewal is enabled',
          default: false
        },
        notes: {
          type: 'string',
          description: 'Additional notes about the domain'
        }
      },
      required: ['domain']
    }
  },
  {
    name: 'domain_update',
    description: 'Update domain information (registrar, dates, notes, etc.)',
    inputSchema: {
      type: 'object',
      properties: {
        domainId: {
          type: 'number',
          description: 'Domain ID from domain_list'
        },
        registrar: {
          type: 'string',
          description: 'Domain registrar'
        },
        buyDate: {
          type: 'string',
          description: 'Purchase date (YYYY-MM-DD)'
        },
        expiryDate: {
          type: 'string',
          description: 'Expiration date (YYYY-MM-DD)'
        },
        autoRenew: {
          type: 'boolean',
          description: 'Auto-renewal status'
        },
        notes: {
          type: 'string',
          description: 'Notes about the domain'
        }
      },
      required: ['domainId']
    }
  },
  {
    name: 'domain_delete',
    description: 'Remove a domain from the domain management system. Does not affect actual domain registration or DNS records.',
    inputSchema: {
      type: 'object',
      properties: {
        domainId: {
          type: 'number',
          description: 'Domain ID to delete'
        }
      },
      required: ['domainId']
    }
  },
  {
    name: 'domain_fetch_cloudflare',
    description: 'Fetch all domains from Cloudflare account and import them into domain management. Automatically detects Cloudflare zone IDs.',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'domain_connect_to_project',
    description: 'Connect a domain to a project (Project Info). Super Admin (userID 152) can connect ANY custom domain to ANY project. Normal users can only use subdomains from sites.control-center.eu. Use domain_type: "custom" for custom domains (Super Admin only) with optional subdomain.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        domainType: {
          type: 'string',
          description: 'Domain type: "subdomain" (default), "custom" (Super Admin only), or "main"',
          enum: ['subdomain', 'custom', 'main'],
          default: 'subdomain'
        },
        customBaseDomain: {
          type: 'string',
          description: 'Custom base domain from domain management (required when domainType is "custom"). Super Admin only.'
        },
        subdomain: {
          type: 'string',
          description: 'Optional subdomain prefix. For custom domains, creates subdomain.customBaseDomain. For main domain type, this becomes the subdomain under sites.control-center.eu.'
        },
        userId: {
          type: 'number',
          description: 'User ID (from user_profile or context)'
        }
      },
      required: ['project', 'domainType', 'userId']
    }
  },
  {
    name: 'domain_get_project',
    description: 'Get the current domain configuration for a project',
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
    name: 'domain_webbuilder_configure',
    description: 'Configure domain for Web Builder. Can use either a subdomain of the project main domain OR the main domain itself (exclusive - only Web Builder OR Codespace can use main domain at a time). IMPORTANT: Check for main domain conflicts first.',
    inputSchema: {
      type: 'object',
      properties: {
        ccProject: {
          type: 'string',
          description: 'Control Center project link/slug'
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain prefix (required when not using main domain). Min 3 characters, lowercase alphanumeric with hyphens.'
        },
        useMainDomain: {
          type: 'boolean',
          description: 'Use the project main domain directly (exclusive with Codespaces). Default: false',
          default: false
        },
        enabled: {
          type: 'boolean',
          description: 'Enable or disable the domain',
          default: true
        }
      },
      required: ['ccProject']
    }
  },
  {
    name: 'domain_webbuilder_get',
    description: 'Get the Web Builder domain configuration for a project',
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
    name: 'domain_codespace_connect',
    description: 'Connect domain to a codespace. Can use either a subdomain OR the main domain (exclusive - only Web Builder OR Codespace can use main domain at a time). IMPORTANT: Check for Web Builder main domain usage first.',
    inputSchema: {
      type: 'object',
      properties: {
        codespaceId: {
          type: 'number',
          description: 'Codespace ID'
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain prefix (required when isMain is false)'
        },
        isMain: {
          type: 'boolean',
          description: 'Use the main domain directly (exclusive with Web Builder). Default: false',
          default: false
        },
        userId: {
          type: 'number',
          description: 'User ID'
        }
      },
      required: ['codespaceId', 'userId']
    }
  }
];

/**
 * Handle domain tool calls
 */
export async function handleDomainTool(toolName, args, context) {
  switch (toolName) {
    case 'domain_list':
      return await listDomains(args, context);
    case 'domain_list_available':
      return await listAvailableDomains(args, context);
    case 'domain_add':
      return await addDomain(args, context);
    case 'domain_update':
      return await updateDomain(args, context);
    case 'domain_delete':
      return await deleteDomain(args, context);
    case 'domain_fetch_cloudflare':
      return await fetchCloudflare(args, context);
    case 'domain_connect_to_project':
      return await connectToProject(args, context);
    case 'domain_get_project':
      return await getProjectDomain(args, context);
    case 'domain_webbuilder_configure':
      return await configureWebBuilderDomain(args, context);
    case 'domain_webbuilder_get':
      return await getWebBuilderDomain(args, context);
    case 'domain_codespace_connect':
      return await connectCodespaceDomain(args, context);
    default:
      return formatError(`Unknown domain tool: ${toolName}`);
  }
}

// ============================================
// Implementation
// ============================================

async function listDomains(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'list'
    });
    
    const response = await cmsRequest('domains.php', {
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

async function listAvailableDomains(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'list_available'
    });
    
    const response = await cmsRequest('domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to list available domains');
    }
    
    const isSuperAdmin = response.is_super_admin || false;
    const features = response.features || [];
    
    return formatResponse({
      success: true,
      is_super_admin: isSuperAdmin,
      features: features,
      count: response.domains?.length || 0,
      domains: response.domains || [],
      note: isSuperAdmin 
        ? 'Super Admin: You can select ANY domain for ANY project and create subdomains from custom domains'
        : 'Normal User: Only subdomains from sites.control-center.eu available'
    });
  } catch (error) {
    return formatError(`Failed to list available domains: ${error.message}`);
  }
}

async function addDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'add',
      domain: args.domain,
      registrar: args.registrar || '',
      buy_date: args.buyDate || '',
      expiry_date: args.expiryDate || '',
      auto_renew: args.autoRenew ? '1' : '0',
      notes: args.notes || ''
    });
    
    const response = await cmsRequest('domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to add domain');
    }
    
    return formatResponse({
      success: true,
      message: 'Domain added successfully',
      domain: response.domain
    });
  } catch (error) {
    return formatError(`Failed to add domain: ${error.message}`);
  }
}

async function updateDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'update',
      id: args.domainId.toString()
    });
    
    if (args.registrar) params.append('registrar', args.registrar);
    if (args.buyDate) params.append('buy_date', args.buyDate);
    if (args.expiryDate) params.append('expiry_date', args.expiryDate);
    if (args.autoRenew !== undefined) params.append('auto_renew', args.autoRenew ? '1' : '0');
    if (args.notes !== undefined) params.append('notes', args.notes);
    
    const response = await cmsRequest('domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to update domain');
    }
    
    return formatResponse({
      success: true,
      message: 'Domain updated successfully'
    });
  } catch (error) {
    return formatError(`Failed to update domain: ${error.message}`);
  }
}

async function deleteDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'delete',
      id: args.domainId.toString()
    });
    
    const response = await cmsRequest('domains.php', {
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

async function fetchCloudflare(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'fetch_cloudflare'
    });
    
    const response = await cmsRequest('domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to fetch Cloudflare domains');
    }
    
    return formatResponse({
      success: true,
      message: 'Cloudflare domains fetched successfully',
      added: response.added || 0,
      updated: response.updated || 0,
      domains: response.domains || []
    });
  } catch (error) {
    return formatError(`Failed to fetch Cloudflare domains: ${error.message}`);
  }
}

async function connectToProject(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'connect',
      project: args.project,
      domain_type: args.domainType || 'subdomain',
      user_id: args.userId.toString()
    });
    
    if (args.customBaseDomain) {
      params.append('custom_base_domain', args.customBaseDomain);
    }
    
    if (args.subdomain) {
      params.append('domain', args.subdomain);
      params.append('subdomain', args.subdomain);
    }
    
    const response = await cmsRequest('project_domain.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (response.error) {
      return formatError(response.error);
    }
    
    return formatResponse({
      success: true,
      message: 'Domain connected to project successfully',
      domain: response.domain,
      fullDomain: response.domain
    });
  } catch (error) {
    return formatError(`Failed to connect domain: ${error.message}`);
  }
}

async function getProjectDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'get',
      project: args.project
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
      domain: response.domain || null,
      configured: !!response.domain
    });
  } catch (error) {
    return formatError(`Failed to get project domain: ${error.message}`);
  }
}

async function configureWebBuilderDomain(args, context) {
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
        'No main domain configured for this project. Use domain_connect_to_project first.'
      );
    }
    
    const mainDomain = mainDomainResponse.domain;
    
    const params = new URLSearchParams({
      action: 'save',
      project: args.ccProject,
      main_domain: mainDomain,
      is_enabled: args.enabled !== false ? 'true' : 'false',
      use_main_domain: args.useMainDomain ? 'true' : 'false'
    });
    
    if (!args.useMainDomain) {
      if (!args.subdomain) {
        return formatError('subdomain is required when not using main domain');
      }
      
      if (!/^[a-z0-9-]+$/.test(args.subdomain)) {
        return formatError('Subdomain must contain only lowercase letters, numbers, and hyphens');
      }
      
      if (args.subdomain.length < 3) {
        return formatError('Subdomain must be at least 3 characters long');
      }
      
      params.append('subdomain', args.subdomain.toLowerCase());
    }
    
    const response = await cmsRequest('web_builder_domains.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to configure Web Builder domain');
    }
    
    const fullDomain = args.useMainDomain 
      ? mainDomain 
      : `${args.subdomain.toLowerCase()}.${mainDomain}`;
    
    return formatResponse({
      success: true,
      message: response.message || 'Web Builder domain configured successfully',
      subdomain: args.useMainDomain ? null : args.subdomain.toLowerCase(),
      mainDomain: mainDomain,
      fullDomain: fullDomain,
      useMainDomain: args.useMainDomain || false,
      note: args.useMainDomain 
        ? 'Using main domain (exclusive - Codespaces cannot use main domain while Web Builder is using it)'
        : 'Using subdomain of main domain'
    });
  } catch (error) {
    return formatError(`Failed to configure Web Builder domain: ${error.message}`);
  }
}

async function getWebBuilderDomain(args, context) {
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
      return formatResponse({
        success: true,
        configured: false,
        domain: null
      });
    }
    
    return formatResponse({
      success: true,
      configured: true,
      domain: response.data
    });
  } catch (error) {
    return formatError(`Failed to get Web Builder domain: ${error.message}`);
  }
}

async function connectCodespaceDomain(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'connect_domain',
      codespace_id: args.codespaceId.toString(),
      user_id: args.userId.toString(),
      is_main: args.isMain ? 'true' : 'false'
    });
    
    if (!args.isMain) {
      if (!args.subdomain) {
        return formatError('subdomain is required when isMain is false');
      }
      params.append('subdomain', args.subdomain);
    }
    
    const response = await cmsRequest('codespace_connections.php', {
      method: 'POST',
      body: params
    }, context);
    
    if (!response.success) {
      return formatError(response.error || 'Failed to connect Codespace domain');
    }
    
    return formatResponse({
      success: true,
      message: 'Codespace domain connected successfully',
      domain: response.domain,
      isMain: args.isMain || false,
      note: args.isMain 
        ? 'Using main domain (exclusive - Web Builder cannot use main domain while Codespace is using it)'
        : 'Using subdomain of main domain'
    });
  } catch (error) {
    return formatError(`Failed to connect Codespace domain: ${error.message}`);
  }
}
