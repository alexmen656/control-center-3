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
    description: 'Get form submissions',
    inputSchema: {
      type: 'object',
      properties: {
        formId: {
          type: 'string',
          description: 'Form ID'
        },
        limit: {
          type: 'number',
          description: 'Maximum number of submissions to return',
          default: 50
        },
        offset: {
          type: 'number',
          description: 'Offset for pagination',
          default: 0
        }
      },
      required: ['formId']
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
  },
  {
    name: 'content_tasks_list',
    description: 'List tasks in a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        status: {
          type: 'string',
          description: 'Filter by status (all, open, done)',
          default: 'all'
        }
      },
      required: ['project']
    }
  },
  {
    name: 'content_task_create',
    description: 'Create a new task',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        title: {
          type: 'string',
          description: 'Task title'
        },
        description: {
          type: 'string',
          description: 'Task description'
        },
        priority: {
          type: 'string',
          description: 'Task priority (low, medium, high)',
          default: 'medium'
        },
        dueDate: {
          type: 'string',
          description: 'Due date (YYYY-MM-DD)'
        },
        assignee: {
          type: 'string',
          description: 'User ID to assign to'
        }
      },
      required: ['project', 'title']
    }
  },
  {
    name: 'content_task_update',
    description: 'Update a task',
    inputSchema: {
      type: 'object',
      properties: {
        taskId: {
          type: 'string',
          description: 'Task ID'
        },
        title: {
          type: 'string',
          description: 'New title'
        },
        description: {
          type: 'string',
          description: 'New description'
        },
        status: {
          type: 'string',
          description: 'New status (open, in-progress, done)',
          enum: ['open', 'in-progress', 'done']
        },
        priority: {
          type: 'string',
          description: 'New priority'
        }
      },
      required: ['taskId']
    }
  },
  {
    name: 'content_notepad_get',
    description: 'Get notepad content for a project',
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
    name: 'content_notepad_save',
    description: 'Save notepad content',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        content: {
          type: 'string',
          description: 'Notepad content'
        }
      },
      required: ['project', 'content']
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
      
    case 'content_newsletter_list':
      return await listNewsletterSubscribers(args, context);
      
    case 'content_newsletter_send':
      return await sendNewsletter(args, context);
      
    case 'content_tasks_list':
      return await listTasks(args, context);
      
    case 'content_task_create':
      return await createTask(args, context);
      
    case 'content_task_update':
      return await updateTask(args, context);
      
    case 'content_notepad_get':
      return await getNotepad(args, context);
      
    case 'content_notepad_save':
      return await saveNotepad(args, context);
      
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
    const data = await cmsRequest('form.php', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        createForm: true,
        project: args.project,
        name: args.name,
        fields: args.fields,
        settings: args.settings || {}
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Form created successfully',
      form: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getFormSubmissions(args, context) {
  try {
    const data = await cmsRequest('form.php', {
      body: {
        getSubmissions: 'true',
        formId: args.formId,
        limit: args.limit || 50,
        offset: args.offset || 0
      }
    }, context);
    
    return formatResponse({
      success: true,
      submissions: data.submissions || data,
      total: data.total
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

async function listTasks(args, context) {
  try {
    const data = await cmsRequest('tasks.php', {
      body: {
        getTasks: 'true',
        project: args.project,
        status: args.status || 'all'
      }
    }, context);
    
    return formatResponse({
      success: true,
      tasks: data.tasks || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createTask(args, context) {
  try {
    const data = await cmsRequest('tasks.php', {
      body: {
        createTask: 'true',
        project: args.project,
        title: args.title,
        description: args.description || '',
        priority: args.priority || 'medium',
        dueDate: args.dueDate || '',
        assignee: args.assignee || ''
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Task created successfully',
      task: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateTask(args, context) {
  try {
    const body = {
      updateTask: 'true',
      taskId: args.taskId
    };
    
    if (args.title) body.title = args.title;
    if (args.description) body.description = args.description;
    if (args.status) body.status = args.status;
    if (args.priority) body.priority = args.priority;
    
    const data = await cmsRequest('tasks.php', { body }, context);
    
    return formatResponse({
      success: true,
      message: 'Task updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getNotepad(args, context) {
  try {
    const data = await cmsRequest('notepad.php', {
      body: {
        getNotepad: 'true',
        project: args.project
      }
    }, context);
    
    return formatResponse({
      success: true,
      content: data.content || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function saveNotepad(args, context) {
  try {
    const data = await cmsRequest('notepad.php', {
      body: {
        saveNotepad: 'true',
        project: args.project,
        content: args.content
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Notepad saved successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
