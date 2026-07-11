import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

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
  },
  {
    name: 'domain_disconnect_from_project',
    description: 'Disconnect the domain from a project. Useful when switching domains or removing an unwanted domain connection.',
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
    case 'domain_codespace_connect':
      return await connectCodespaceDomain(args, context);
    case 'domain_disconnect_from_project':
      return await disconnectFromProject(args, context);
    default:
      return formatError(`Unknown domain tool: ${toolName}`);
  }
}

async function listDomains(args, context) {
  try {
    const response = await cmsRequest('v2/domains', {
      method: 'GET'
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
    const response = await cmsRequest('v2/domains/available', {
      method: 'GET'
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
    const body = {
      domain: args.domain,
      registrar: args.registrar || '',
      buy_date: args.buyDate || '',
      expiry_date: args.expiryDate || '',
      auto_renew: args.autoRenew ? true : false,
      notes: args.notes || ''
    };

    const response = await cmsRequest('v2/domains', {
      method: 'POST',
      contentType: 'application/json',
      body
    }, context);

    if (!response.success) {
      return formatError(response.error || 'Failed to add domain');
    }

    return formatResponse({
      success: true,
      message: response.message || 'Domain added successfully'
    });
  } catch (error) {
    return formatError(`Failed to add domain: ${error.message}`);
  }
}

async function updateDomain(args, context) {
  try {
    const body = {
      id: args.domainId
    };

    if (args.registrar !== undefined) body.registrar = args.registrar;
    if (args.buyDate !== undefined) body.buy_date = args.buyDate;
    if (args.expiryDate !== undefined) body.expiry_date = args.expiryDate;
    if (args.autoRenew !== undefined) body.auto_renew = args.autoRenew ? true : false;
    if (args.notes !== undefined) body.notes = args.notes;

    const response = await cmsRequest('v2/domains', {
      method: 'POST',
      contentType: 'application/json',
      body
    }, context);

    if (!response.success) {
      return formatError(response.error || 'Failed to update domain');
    }

    return formatResponse({
      success: true,
      message: response.message || 'Domain updated successfully'
    });
  } catch (error) {
    return formatError(`Failed to update domain: ${error.message}`);
  }
}

async function deleteDomain(args, context) {
  try {
    const response = await cmsRequest(`v2/domains/${args.domainId}`, {
      method: 'DELETE'
    }, context);

    if (!response.success) {
      return formatError(response.error || 'Failed to delete domain');
    }

    return formatResponse({
      success: true,
      message: response.message || 'Domain deleted successfully'
    });
  } catch (error) {
    return formatError(`Failed to delete domain: ${error.message}`);
  }
}

async function fetchCloudflare(args, context) {
  try {
    const response = await cmsRequest('v2/domains/fetch-cloudflare', {
      method: 'POST',
      contentType: 'application/json',
      body: {}
    }, context);

    if (!response.success) {
      return formatError(response.error || 'Failed to fetch Cloudflare domains');
    }

    return formatResponse({
      success: true,
      message: response.message || 'Cloudflare domains fetched successfully',
      imported: response.imported || [],
      total_zones: response.total_zones || 0,
      imported_count: response.imported_count || 0,
      skipped_count: response.skipped_count || 0
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

async function connectCodespaceDomain(args, context) {
  try {
    const body = {
      is_main: args.isMain ? true : false
    };

    if (!args.isMain) {
      if (!args.subdomain) {
        return formatError('subdomain is required when isMain is false');
      }
      body.subdomain = args.subdomain;
    }

    const response = await cmsRequest(`v2/codespaces/${args.codespaceId}/domain`, {
      method: 'POST',
      contentType: 'application/json',
      body
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

async function disconnectFromProject(args, context) {
  try {
    const params = new URLSearchParams({
      action: 'delete',
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
      message: 'Domain disconnected from project successfully'
    });
  } catch (error) {
    return formatError(`Failed to disconnect domain: ${error.message}`);
  }
}
