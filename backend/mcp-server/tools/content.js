import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

const enc = encodeURIComponent;

export const contentTools = [
  {
    name: 'content_table_list',
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
    name: 'content_table_create',
    description: 'Create a new form/data collection (a database table). Field names keep their original casing as the column name — camelCase like "projectId" is preserved (only spaces and special characters are replaced, e.g. "First Name" becomes "First_Name"). The DatabaseAPI SDK matches columns case-insensitively, so you can read/write that column as projectId, projectid, or PROJECTID and it resolves to the same column.',
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
              type: {
                type: 'string',
                enum: ['text', 'email', 'number', 'textarea', 'select', 'checkbox', 'date', 'image'],
                description: '"image" stores an uploaded file\'s path (any file type, not just images) and renders as a file/image upload field with preview'
              },
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
    name: 'content_table_submissions',
    description: 'Get form submissions/data entries',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
          type: 'string',
          description: 'Form name'
        }
      },
      required: ['project', 'tableName']
    }
  },
  {
    name: 'content_table_submit',
    description: 'Submit/add data to a form (insert a row). Field names are matched to columns case-insensitively — same as the DatabaseAPI SDK — so both paths accept e.g. "projectId" and target the same column; they are interchangeable for the same table.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
          type: 'string',
          description: 'Form name'
        },
        data: {
          type: 'object',
          description: 'Form data as key-value pairs (field name: value)'
        }
      },
      required: ['project', 'tableName', 'data']
    }
  },
  {
    name: 'content_table_update',
    description: 'Update a form data entry',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
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
      required: ['project', 'tableName', 'entryId', 'data']
    }
  },
  {
    name: 'content_table_delete',
    description: 'Delete a form data entry',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
          type: 'string',
          description: 'Form name'
        },
        entryId: {
          type: 'string',
          description: 'Entry ID to delete'
        }
      },
      required: ['project', 'tableName', 'entryId']
    }
  },
  {
    name: 'content_table_rename',
    description: 'Rename a table (its config and underlying data table). Fails if a table with the new name already exists in the project.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
          type: 'string',
          description: 'Current table name'
        },
        newTableName: {
          type: 'string',
          description: 'New table name (letters, numbers, hyphens, underscores only)'
        }
      },
      required: ['project', 'tableName', 'newTableName']
    }
  },
  {
    name: 'content_table_drop',
    description: 'Permanently delete an entire table — its config and all of its data rows. This cannot be undone. Not to be confused with content_table_delete, which removes a single data entry.',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        tableName: {
          type: 'string',
          description: 'Table name to delete'
        }
      },
      required: ['project', 'tableName']
    }
  }
];

export async function handleContentTool(toolName, args, context) {
  switch (toolName) {
    case 'content_table_list':
      return await listForms(args, context);

    case 'content_table_create':
      return await createForm(args, context);

    case 'content_table_submissions':
      return await getFormSubmissions(args, context);

    case 'content_table_submit':
      return await submitFormData(args, context);

    case 'content_table_update':
      return await updateFormData(args, context);

    case 'content_table_delete':
      return await deleteFormData(args, context);

    case 'content_table_rename':
      return await renameTable(args, context);

    case 'content_table_drop':
      return await dropTable(args, context);

    default:
      return formatError(`Unknown content tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function listForms(args, context) {
  try {
    const data = await cmsRequest(`v2/tables/tables?project=${enc(args.project)}`, { method: 'GET' }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to list tables');
    }

    return formatResponse({
      success: true,
      tables: (data.tables || []).map(t => ({
        tableName: t.name,
        exists: t.exists,
        rowCount: t.row_count,
        fieldCount: t.field_count,
        createdOn: t.created_at
      }))
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function createForm(args, context) {
  try {
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

    const data = await cmsRequest('v2/tables', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        name: args.name,
        table: JSON.stringify(formJson)
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to create table');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Table created successfully',
      tableName: args.name
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getFormSubmissions(args, context) {
  try {
    const data = await cmsRequest(
      `v2/tables/data?table=${enc(args.tableName)}&project=${enc(args.project)}`,
      { method: 'GET' },
      context
    );

    return formatResponse({
      success: true,
      data,
      count: Array.isArray(data) ? data.length : 0
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function submitFormData(args, context) {
  try {
    const data = await cmsRequest('v2/tables/submit', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        table_name: args.tableName,
        table: JSON.stringify(args.data)
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to submit table data');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Form data submitted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateFormData(args, context) {
  try {
    const data = await cmsRequest(`v2/tables/entry/${enc(args.entryId)}`, {
      method: 'PUT',
      contentType: 'application/json',
      body: {
        project: args.project,
        table_name: args.tableName,
        table: JSON.stringify(args.data)
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to update entry');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Entry updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteFormData(args, context) {
  try {
    const data = await cmsRequest(`v2/tables/entry/${enc(args.entryId)}`, {
      method: 'DELETE',
      contentType: 'application/json',
      body: {
        project: args.project,
        table_name: args.tableName
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to delete entry');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Entry deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function renameTable(args, context) {
  try {
    const data = await cmsRequest('v2/tables/rename', {
      method: 'POST',
      contentType: 'application/json',
      body: {
        project: args.project,
        old_table_name: args.tableName,
        new_table_name: args.newTableName
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to rename table');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Table renamed successfully',
      tableName: args.newTableName
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function dropTable(args, context) {
  try {
    const data = await cmsRequest('v2/tables/table', {
      method: 'DELETE',
      contentType: 'application/json',
      body: {
        project: args.project,
        table_name: args.tableName
      }
    }, context);

    if (data && data.success === false) {
      return formatError(data.error || 'Failed to delete table');
    }

    return formatResponse({
      success: true,
      message: (data && data.message) || 'Table deleted successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
