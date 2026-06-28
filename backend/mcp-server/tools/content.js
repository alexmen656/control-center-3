/**
 * Content Management Tools
 * 
 * Tools for managing dynamic content, forms, and data
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for content
 */
export const contentTools = [
  {
    name: 'content_form_list',
    description: 'List all forms in a project',
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
    name: 'content_form_create',
    description: 'Create a new form/data collection',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        name: {
          type: 'string',
          description: 'Form name'
        },
        fields: {
          type: 'array',
          description: 'Array of field definitions',
          items: {
            type: 'object',
            properties: {
              name: { type: 'string' },
              type: { type: 'string', enum: ['text', 'email', 'number', 'textarea', 'select', 'checkbox', 'date', 'file'] },
              label: { type: 'string' },
              required: { type: 'boolean' },
              options: { type: 'array', items: { type: 'string' } }
            }
          }
        },
        settings: {
          type: 'object',
          description: 'Form settings (email notifications, redirect, etc.)'
        }
      },
      required: ['project', 'name', 'fields']
    }
  },
  {
    name: 'content_form_submissions',
    description: 'Get form submissions/data entries',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        formName: {
          type: 'string',
          description: 'Form name'
        }
      },
      required: ['project', 'formName']
    }
  },
  {
    name: 'content_form_submit',
    description: 'Submit/add data to a form',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        formName: {
          type: 'string',
          description: 'Form name'
        },
        data: {
          type: 'object',
          description: 'Form data as key-value pairs (field name: value)'
        }
      },
      required: ['project', 'formName', 'data']
    }
  },
  {
    name: 'content_form_update',
    description: 'Update a form data entry',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        formName: {
          type: 'string',
          description: 'Form name'
        },
        entryId: {
          type: 'string',
          description: 'Entry ID to update'
        },
        data: {
          type: 'object',
          description: 'Updated form data as key-value pairs'
        }
      },
      required: ['project', 'formName', 'entryId', 'data']
    }
  },
  {
    name: 'content_form_delete',
    description: 'Delete a form data entry',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        formName: {
          type: 'string',
          description: 'Form name'
        },
        entryId: {
          type: 'string',
          description: 'Entry ID to delete'
        }
      },
      required: ['project', 'formName', 'entryId']
    }
  },
  {
    name: 'content_newsletter_list',
    description: 'List newsletter subscribers',
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
    name: 'content_newsletter_send',
    description: 'Send a newsletter to subscribers',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        subject: {
          type: 'string',
          description: 'Email subject'
        },
        content: {
          type: 'string',
          description: 'Email content (HTML)'
        },
        testEmail: {
          type: 'string',
          description: 'Send test email to this address first (optional)'
        }
      },
      required: ['project', 'subject', 'content']
    }
  }
];

/**
 * Handle content tool calls
 */
export async function handleContentTool(toolName, args, context) {
  switch (toolName) {
    case 'content_form_list':
      return await listForms(args, context);
      
    case 'content_form_create':
      return await createForm(args, context);
      
    case 'content_form_submissions':
      return await getFormSubmissions(args, context);
      
    case 'content_form_submit':
      return await submitFormData(args, context);
      
    case 'content_form_update':
      return await updateFormData(args, context);
      
    case 'content_form_delete':
      return await deleteFormData(args, context);
      
    case 'content_newsletter_list':
      return await listNewsletterSubscribers(args, context);
      
    case 'content_newsletter_send':
      return await sendNewsletter(args, context);

    default:
      return formatError(`Unknown content tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listForms(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        getForms: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      forms: data.forms || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createForm(args, context) {
  try {
    // Convert fields to the format expected by backend
    const formJson = {
      title: args.name,
      inputs: args.fields.map(field => ({
        name: field.name,
        type: field.type,
        label: field.label || field.name,
        required: field.required || false,
        options: field.options || []
      }))
    };
    
    const data = await cmsRequest('form.php', {
      method: 'POST',
      body: {
        create_form: '1',
        project: args.project,
        name: args.name,
        form: JSON.stringify(formJson)
      },
      expectJson: false
    }, context);
    
    return formatResponse({
      success: true,
      message: data || 'Form created successfully',
      formName: args.name
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getFormSubmissions(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        get_form_data: '1',
        project: args.project,
        form: args.formName
      }
    }, context);
    
    return formatResponse({
      success: true,
      data: data,
      count: Array.isArray(data) ? data.length : 0
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function submitFormData(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        submit_form: '1',
        project: args.project,
        form_name: args.formName,
        form: JSON.stringify(args.data)
      },
      expectJson: false
    }, context);
    
    return formatResponse({
      success: true,
      message: data || 'Form data submitted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateFormData(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        update_entry: '1',
        project: args.project,
        form_name: args.formName,
        entry_id: args.entryId,
        form: JSON.stringify(args.data)
      },
      expectJson: false
    }, context);
    
    return formatResponse({
      success: true,
      message: data || 'Entry updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteFormData(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        delete_entry: '1',
        project: args.project,
        form_name: args.formName,
        entry_id: args.entryId
      },
      expectJson: false
    }, context);
    
    return formatResponse({
      success: true,
      message: data || 'Entry deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listNewsletterSubscribers(args, context) {
  try {
    const data = await cmsRequest('newsletter.php', {
      body: {
        getSubscribers: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      subscribers: data.subscribers || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function sendNewsletter(args, context) {
  try {
    const body = {
      sendNewsletter: 'true',
      project: args.project,
      subject: args.subject,
      content: args.content
    };
    
    if (args.testEmail) {
      body.testEmail = args.testEmail;
    }
    
    const data = await cmsRequest('newsletter.php', { body }, context);
    
    return formatResponse({
      success: true,
      message: data.message || 'Newsletter sent successfully',
      sentCount: data.sentCount
    });
  } catch (error) {
    return formatError(error.message);
  }
}
