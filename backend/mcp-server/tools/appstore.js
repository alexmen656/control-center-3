/**
 * App Store Metadata Tools
 * 
 * Tools for managing App Store Connect apps, versions, localizations, and sync
 * AI agents can fully automate app metadata management across all languages
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for App Store Metadata
 */
export const appstoreTools = [
  // ============================================
  // APP MANAGEMENT
  // ============================================
  {
    name: 'appstore_list_apps',
    description: 'List all App Store apps in a project with version and locale counts',
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
    name: 'appstore_get_app',
    description: 'Get detailed information about a specific app including all localizations and versions',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID (not Apple ID)'
        }
      },
      required: ['project', 'appId']
    }
  },
  {
    name: 'appstore_browse_apps',
    description: 'Browse all apps available in the connected App Store Connect account (requires API credentials)',
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
    name: 'appstore_connect_app',
    description: 'Connect an existing App Store app to this project for metadata management',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appleAppId: {
          type: 'string',
          description: 'Apple App Store ID (e.g., "1234567890")'
        },
        bundleId: {
          type: 'string',
          description: 'App bundle identifier (e.g., "com.company.app")'
        },
        name: {
          type: 'string',
          description: 'App name'
        }
      },
      required: ['project', 'appleAppId', 'bundleId', 'name']
    }
  },

  // ============================================
  // VERSION MANAGEMENT
  // ============================================
  {
    name: 'appstore_list_versions',
    description: 'List all versions of an app with their status and localization counts',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID'
        }
      },
      required: ['project', 'appId']
    }
  },
  {
    name: 'appstore_get_version',
    description: 'Get detailed version info including all localizations and screenshots',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        versionId: {
          type: 'number',
          description: 'Version ID'
        }
      },
      required: ['project', 'versionId']
    }
  },
  {
    name: 'appstore_create_version',
    description: 'Create a new app version for localization',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID'
        },
        versionString: {
          type: 'string',
          description: 'Version number (e.g., "2.0.0")'
        },
        platform: {
          type: 'string',
          enum: ['iOS', 'macOS', 'tvOS', 'watchOS', 'visionOS'],
          description: 'Platform (default: iOS)'
        },
        releaseType: {
          type: 'string',
          enum: ['manual', 'afterApproval', 'scheduled'],
          description: 'Release type (default: afterApproval)'
        }
      },
      required: ['project', 'appId', 'versionString']
    }
  },

  // ============================================
  // APP LOCALIZATIONS (App-Level Metadata)
  // ============================================
  {
    name: 'appstore_list_app_localizations',
    description: 'List all app-level localizations (name, subtitle, privacy policy per language)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID'
        }
      },
      required: ['project', 'appId']
    }
  },
  {
    name: 'appstore_create_app_localization',
    description: 'Add a new language/locale to an app with name and subtitle',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID'
        },
        locale: {
          type: 'string',
          description: 'Locale code (e.g., "de-DE", "fr-FR", "ja")'
        },
        name: {
          type: 'string',
          description: 'Localized app name'
        },
        subtitle: {
          type: 'string',
          description: 'App subtitle (max 30 characters)'
        },
        privacyPolicyUrl: {
          type: 'string',
          description: 'Privacy policy URL for this locale'
        }
      },
      required: ['project', 'appId', 'locale', 'name']
    }
  },
  {
    name: 'appstore_update_app_localization',
    description: 'Update app-level localization (name, subtitle, privacy policy)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        localizationId: {
          type: 'number',
          description: 'Localization ID'
        },
        name: {
          type: 'string',
          description: 'Localized app name'
        },
        subtitle: {
          type: 'string',
          description: 'App subtitle (max 30 characters)'
        },
        privacyPolicyUrl: {
          type: 'string',
          description: 'Privacy policy URL'
        },
        privacyPolicyText: {
          type: 'string',
          description: 'Privacy policy text (for some regions)'
        }
      },
      required: ['project', 'localizationId']
    }
  },

  // ============================================
  // VERSION LOCALIZATIONS (Version-Specific Metadata)
  // ============================================
  {
    name: 'appstore_list_version_localizations',
    description: 'List all localizations for a specific version (description, keywords, what\'s new)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        versionId: {
          type: 'number',
          description: 'Version ID'
        }
      },
      required: ['project', 'versionId']
    }
  },
  {
    name: 'appstore_create_version_localization',
    description: 'Add a new language to a version with description, keywords, and release notes',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        versionId: {
          type: 'number',
          description: 'Version ID'
        },
        locale: {
          type: 'string',
          description: 'Locale code (e.g., "de-DE", "fr-FR", "ja")'
        },
        description: {
          type: 'string',
          description: 'App description for this version (max 4000 characters)'
        },
        keywords: {
          type: 'string',
          description: 'Keywords, comma-separated (max 100 characters total)'
        },
        whatsNew: {
          type: 'string',
          description: 'Release notes / What\'s New text'
        },
        promotionalText: {
          type: 'string',
          description: 'Promotional text (max 170 characters, can be updated anytime)'
        },
        marketingUrl: {
          type: 'string',
          description: 'Marketing URL'
        },
        supportUrl: {
          type: 'string',
          description: 'Support URL'
        }
      },
      required: ['project', 'versionId', 'locale']
    }
  },
  {
    name: 'appstore_update_version_localization',
    description: 'Update version localization (description, keywords, release notes, etc.)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        localizationId: {
          type: 'number',
          description: 'Localization ID'
        },
        description: {
          type: 'string',
          description: 'App description (max 4000 characters)'
        },
        keywords: {
          type: 'string',
          description: 'Keywords, comma-separated (max 100 characters)'
        },
        whatsNew: {
          type: 'string',
          description: 'Release notes / What\'s New'
        },
        promotionalText: {
          type: 'string',
          description: 'Promotional text (max 170 characters)'
        },
        marketingUrl: {
          type: 'string',
          description: 'Marketing URL'
        },
        supportUrl: {
          type: 'string',
          description: 'Support URL'
        }
      },
      required: ['project', 'localizationId']
    }
  },
  {
    name: 'appstore_bulk_update_localizations',
    description: 'Update multiple version localizations at once (e.g., update description in all languages)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        versionId: {
          type: 'number',
          description: 'Version ID'
        },
        localizations: {
          type: 'array',
          description: 'Array of localization updates',
          items: {
            type: 'object',
            properties: {
              locale: { type: 'string', description: 'Locale code' },
              description: { type: 'string' },
              keywords: { type: 'string' },
              whatsNew: { type: 'string' },
              promotionalText: { type: 'string' }
            },
            required: ['locale']
          }
        }
      },
      required: ['project', 'versionId', 'localizations']
    }
  },

  // ============================================
  // SYNC WITH APP STORE CONNECT
  // ============================================
  {
    name: 'appstore_sync_pull',
    description: 'Pull latest metadata from App Store Connect to local database. Updates all localizations and versions.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID (optional - if not provided, syncs all apps)'
        }
      },
      required: ['project']
    }
  },
  {
    name: 'appstore_sync_push',
    description: 'Push local metadata changes to App Store Connect. Only editable versions (PREPARE_FOR_SUBMISSION) will be updated.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        appId: {
          type: 'number',
          description: 'Local app ID'
        }
      },
      required: ['project', 'appId']
    }
  },

  // ============================================
  // API CREDENTIALS
  // ============================================
  {
    name: 'appstore_get_credentials',
    description: 'Check if App Store Connect API credentials are configured',
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
    name: 'appstore_set_credentials',
    description: 'Set App Store Connect API credentials (Key ID, Issuer ID, Private Key)',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        keyId: {
          type: 'string',
          description: 'API Key ID from App Store Connect'
        },
        issuerId: {
          type: 'string',
          description: 'Issuer ID from App Store Connect'
        },
        privateKey: {
          type: 'string',
          description: 'Private key content (PEM format)'
        },
        keyName: {
          type: 'string',
          description: 'Friendly name for this key'
        }
      },
      required: ['project', 'keyId', 'issuerId', 'privateKey']
    }
  },

  // ============================================
  // SUPPORTED LOCALES
  // ============================================
  {
    name: 'appstore_list_locales',
    description: 'List all supported App Store locales with their codes and names',
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

  // ============================================
  // DASHBOARD & OVERVIEW
  // ============================================
  {
    name: 'appstore_dashboard',
    description: 'Get dashboard overview with app count, total localizations, and sync status',
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

/**
 * Handle App Store tool calls
 */
export async function handleAppstoreTool(name, args, context) {
  const { project } = args;
  
  try {
    switch (name) {
      // ============================================
      // APP MANAGEMENT
      // ============================================
      case 'appstore_list_apps': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=apps&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_get_app': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=app&app_id=${args.appId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_browse_apps': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=browse_apps&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_connect_app': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=connect_app&project=${project}`,
          { 
            method: 'POST', 
            contentType: 'application/json',
            body: {
              apple_app_id: args.appleAppId,
              bundle_id: args.bundleId,
              name: args.name
            }
          },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // VERSION MANAGEMENT
      // ============================================
      case 'appstore_list_versions': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=versions&app_id=${args.appId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_get_version': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=version&version_id=${args.versionId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_create_version': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=versions&app_id=${args.appId}&project=${project}`,
          { 
            method: 'POST', 
            contentType: 'application/json',
            body: {
              version_string: args.versionString,
              platform: args.platform || 'iOS',
              release_type: args.releaseType || 'afterApproval'
            }
          },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // APP LOCALIZATIONS
      // ============================================
      case 'appstore_list_app_localizations': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=localizations&app_id=${args.appId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_create_app_localization': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=localizations&app_id=${args.appId}&project=${project}`,
          { 
            method: 'POST', 
            contentType: 'application/json',
            body: {
              locale: args.locale,
              name: args.name,
              subtitle: args.subtitle || '',
              privacy_policy_url: args.privacyPolicyUrl || ''
            }
          },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_update_app_localization': {
        const body = {};
        if (args.name !== undefined) body.name = args.name;
        if (args.subtitle !== undefined) body.subtitle = args.subtitle;
        if (args.privacyPolicyUrl !== undefined) body.privacy_policy_url = args.privacyPolicyUrl;
        if (args.privacyPolicyText !== undefined) body.privacy_policy_text = args.privacyPolicyText;

        const response = await cmsRequest(
          `appstore_metadata.php?action=localization&id=${args.localizationId}&project=${project}`,
          { 
            method: 'PUT', 
            contentType: 'application/json',
            body
          },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // VERSION LOCALIZATIONS
      // ============================================
      case 'appstore_list_version_localizations': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=version_localizations&version_id=${args.versionId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_create_version_localization': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=version_localizations&version_id=${args.versionId}&project=${project}`,
          { 
            method: 'POST', 
            contentType: 'application/json',
            body: {
              locale: args.locale,
              description: args.description || '',
              keywords: args.keywords || '',
              whats_new: args.whatsNew || '',
              promotional_text: args.promotionalText || '',
              marketing_url: args.marketingUrl || '',
              support_url: args.supportUrl || ''
            }
          },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_update_version_localization': {
        const body = {};
        if (args.description !== undefined) body.description = args.description;
        if (args.keywords !== undefined) body.keywords = args.keywords;
        if (args.whatsNew !== undefined) body.whats_new = args.whatsNew;
        if (args.promotionalText !== undefined) body.promotional_text = args.promotionalText;
        if (args.marketingUrl !== undefined) body.marketing_url = args.marketingUrl;
        if (args.supportUrl !== undefined) body.support_url = args.supportUrl;

        const response = await cmsRequest(
          `appstore_metadata.php?action=version_localization&id=${args.localizationId}&project=${project}`,
          { 
            method: 'PUT', 
            contentType: 'application/json',
            body
          },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_bulk_update_localizations': {
        const results = [];
        
        // First get existing localizations
        const existingResponse = await cmsRequest(
          `appstore_metadata.php?action=version_localizations&version_id=${args.versionId}&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        
        const existingLocalizations = existingResponse.localizations || [];
        const existingByLocale = {};
        existingLocalizations.forEach(loc => {
          existingByLocale[loc.locale] = loc;
        });
        
        for (const loc of args.localizations) {
          const existing = existingByLocale[loc.locale];
          
          if (existing) {
            // Update existing
            const body = {};
            if (loc.description !== undefined) body.description = loc.description;
            if (loc.keywords !== undefined) body.keywords = loc.keywords;
            if (loc.whatsNew !== undefined) body.whats_new = loc.whatsNew;
            if (loc.promotionalText !== undefined) body.promotional_text = loc.promotionalText;
            
            const response = await cmsRequest(
              `appstore_metadata.php?action=version_localization&id=${existing.id}&project=${project}`,
              { method: 'PUT', contentType: 'application/json', body },
              context
            );
            results.push({ locale: loc.locale, action: 'updated', response });
          } else {
            // Create new
            const response = await cmsRequest(
              `appstore_metadata.php?action=version_localizations&version_id=${args.versionId}&project=${project}`,
              { 
                method: 'POST', 
                contentType: 'application/json',
                body: {
                  locale: loc.locale,
                  description: loc.description || '',
                  keywords: loc.keywords || '',
                  whats_new: loc.whatsNew || '',
                  promotional_text: loc.promotionalText || ''
                }
              },
              context
            );
            results.push({ locale: loc.locale, action: 'created', response });
          }
        }
        
        return formatResponse({ success: true, results });
      }

      // ============================================
      // SYNC
      // ============================================
      case 'appstore_sync_pull': {
        let url = `appstore_metadata.php?action=sync_pull&project=${project}`;
        if (args.appId) url += `&app_id=${args.appId}`;
        
        const response = await cmsRequest(url, { method: 'POST', contentType: 'application/json' }, context);
        return formatResponse(response);
      }

      case 'appstore_sync_push': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=sync_push&app_id=${args.appId}&project=${project}`,
          { method: 'POST', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // CREDENTIALS
      // ============================================
      case 'appstore_get_credentials': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=credentials&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      case 'appstore_set_credentials': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=credentials&project=${project}`,
          { 
            method: 'POST', 
            contentType: 'application/json',
            body: {
              key_id: args.keyId,
              issuer_id: args.issuerId,
              private_key: args.privateKey,
              key_name: args.keyName || 'API Key'
            }
          },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // LOCALES
      // ============================================
      case 'appstore_list_locales': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=locales&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      // ============================================
      // DASHBOARD
      // ============================================
      case 'appstore_dashboard': {
        const response = await cmsRequest(
          `appstore_metadata.php?action=dashboard&project=${project}`,
          { method: 'GET', contentType: 'application/json' },
          context
        );
        return formatResponse(response);
      }

      default:
        return formatError(`Unknown App Store tool: ${name}`);
    }
  } catch (error) {
    return formatError(error.message);
  }
}
