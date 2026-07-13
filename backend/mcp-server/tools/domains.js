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
    description: 'List available custom domains for connecting to a codespace. Super Admin (userID 152) can select ANY of their own domains from domain management. Normal users only see their own domains. Use this for domain selection dropdowns in Codespaces.',
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
    name: 'domain_codespace_connect',
    description: 'Connect a domain directly to a codespace. Normal users can only use a subdomain of sites.control-center.eu. Super Admin (userID 152) can instead use domainType "custom" to pick one of their own domains from domain management, with an optional subdomain prefix (root domain used if omitted).',
    inputSchema: {
      type: 'object',
      properties: {
        codespaceId: {
          type: 'number',
          description: 'Codespace ID'
        },
        domainType: {
          type: 'string',
          description: 'Domain type: "subdomain" (default, under sites.control-center.eu) or "custom" (Super Admin only)',
          enum: ['subdomain', 'custom'],
          default: 'subdomain'
        },
        customBaseDomain: {
          type: 'string',
          description: 'Custom base domain from domain management (required when domainType is "custom"). Super Admin only.'
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain prefix. Required for domainType "subdomain". Optional for "custom" (root domain used if omitted).'
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
    name: 'domain_codespace_disconnect',
    description: 'Disconnect the domain currently connected to a codespace.',
    inputSchema: {
      type: 'object',
      properties: {
        codespaceId: {
          type: 'number',
          description: 'Codespace ID'
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
    case 'domain_codespace_connect':
      return await connectCodespaceDomain(args, context);
    case 'domain_codespace_disconnect':
      return await disconnectCodespaceDomain(args, context);
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

async function connectCodespaceDomain(args, context) {
  try {
    const domainType = args.domainType || 'subdomain';
    const body = {
      domain_type: domainType
    };

    if (domainType === 'custom') {
      if (!args.customBaseDomain) {
        return formatError('customBaseDomain is required when domainType is "custom"');
      }
      body.custom_base_domain = args.customBaseDomain;
      if (args.subdomain) {
        body.subdomain = args.subdomain;
      }
    } else {
      if (!args.subdomain) {
        return formatError('subdomain is required when domainType is "subdomain"');
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
      isMain: response.is_main || false
    });
  } catch (error) {
    return formatError(`Failed to connect Codespace domain: ${error.message}`);
  }
}

async function disconnectCodespaceDomain(args, context) {
  try {
    const response = await cmsRequest(`v2/codespaces/${args.codespaceId}/domain`, {
      method: 'DELETE'
    }, context);

    if (!response.success) {
      return formatError(response.error || 'Failed to disconnect Codespace domain');
    }

    return formatResponse({
      success: true,
      message: 'Codespace domain disconnected successfully'
    });
  } catch (error) {
    return formatError(`Failed to disconnect Codespace domain: ${error.message}`);
  }
}
