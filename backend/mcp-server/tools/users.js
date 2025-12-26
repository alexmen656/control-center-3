/**
 * User Management Tools
 * 
 * Tools for managing users and user settings
 */

import { cmsRequest, formatResponse, formatError } from '../utils/api.js';

/**
 * Tool definitions for users
 */
export const userTools = [
  {
    name: 'user_profile',
    description: 'Get current user profile information',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'user_update_profile',
    description: 'Update user profile',
    inputSchema: {
      type: 'object',
      properties: {
        firstName: {
          type: 'string',
          description: 'First name'
        },
        lastName: {
          type: 'string',
          description: 'Last name'
        },
        email: {
          type: 'string',
          description: 'Email address'
        }
      },
      required: []
    }
  },
  {
    name: 'user_list_by_project',
    description: 'List all users in a project',
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
    name: 'user_remove_from_project',
    description: 'Remove a user from a project',
    inputSchema: {
      type: 'object',
      properties: {
        project: {
          type: 'string',
          description: 'Project link/slug'
        },
        userId: {
          type: 'string',
          description: 'User ID to remove'
        }
      },
      required: ['project', 'userId']
    }
  },
  {
    name: 'user_get_notifications',
    description: 'Get user notifications',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Maximum notifications to return',
          default: 20
        },
        unreadOnly: {
          type: 'boolean',
          description: 'Only return unread notifications',
          default: false
        }
      },
      required: []
    }
  },
  {
    name: 'user_mark_notification_read',
    description: 'Mark notification as read',
    inputSchema: {
      type: 'object',
      properties: {
        notificationId: {
          type: 'string',
          description: 'Notification ID'
        }
      },
      required: ['notificationId']
    }
  },
  {
    name: 'user_get_bookmarks',
    description: 'Get user bookmarks',
    inputSchema: {
      type: 'object',
      properties: {},
      required: []
    }
  },
  {
    name: 'user_add_bookmark',
    description: 'Add a bookmark',
    inputSchema: {
      type: 'object',
      properties: {
        url: {
          type: 'string',
          description: 'URL to bookmark'
        },
        title: {
          type: 'string',
          description: 'Bookmark title'
        },
        icon: {
          type: 'string',
          description: 'Icon name',
          default: 'bookmark-outline'
        }
      },
      required: ['url', 'title']
    }
  },
  {
    name: 'user_delete_bookmark',
    description: 'Delete a bookmark',
    inputSchema: {
      type: 'object',
      properties: {
        bookmarkId: {
          type: 'string',
          description: 'Bookmark ID to delete'
        }
      },
      required: ['bookmarkId']
    }
  }
];

/**
 * Handle user tool calls
 */
export async function handleUserTool(toolName, args, context) {
  switch (toolName) {
    case 'user_profile':
      return await getUserProfile(context);
      
    case 'user_update_profile':
      return await updateProfile(args, context);
      
    case 'user_list_by_project':
      return await listUsersByProject(args, context);
      
    case 'user_remove_from_project':
      return await removeUserFromProject(args, context);
      
    case 'user_get_notifications':
      return await getNotifications(args, context);
      
    case 'user_mark_notification_read':
      return await markNotificationRead(args, context);
      
    case 'user_get_bookmarks':
      return await getBookmarks(context);
      
    case 'user_add_bookmark':
      return await addBookmark(args, context);
      
    case 'user_delete_bookmark':
      return await deleteBookmark(args, context);
      
    default:
      return formatError(`Unknown user tool: ${toolName}`);
  }
}

// ============================================
// Tool Implementations
// ============================================

async function getUserProfile(context) {
  try {
    const data = await cmsRequest('user.php', {
      body: { getProfile: 'true' }
    }, context);
    
    return formatResponse({
      success: true,
      user: {
        id: context.user.userID || context.user.id,
        firstName: data.firstname || data.firstName,
        lastName: data.lastname || data.lastName,
        email: data.email,
        profileImg: data.profileImg
      }
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function updateProfile(args, context) {
  try {
    const body = { updateProfile: 'true' };
    
    if (args.firstName) body.firstname = args.firstName;
    if (args.lastName) body.lastname = args.lastName;
    if (args.email) body.email = args.email;
    
    const data = await cmsRequest('user.php', { body }, context);
    
    return formatResponse({
      success: true,
      message: 'Profile updated successfully'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function listUsersByProject(args, context) {
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

async function removeUserFromProject(args, context) {
  try {
    const data = await cmsRequest('projects.php', {
      body: {
        removeUserFromProject: 'true',
        project: args.project,
        userId: args.userId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'User removed from project'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getNotifications(args, context) {
  try {
    const data = await cmsRequest('messages.php', {
      body: {
        getNotifications: 'true',
        limit: args.limit || 20,
        unreadOnly: args.unreadOnly ? '1' : '0'
      }
    }, context);
    
    return formatResponse({
      success: true,
      notifications: data.notifications || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function markNotificationRead(args, context) {
  try {
    const data = await cmsRequest('messages.php', {
      body: {
        markRead: 'true',
        notificationId: args.notificationId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Notification marked as read'
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function getBookmarks(context) {
  try {
    const data = await cmsRequest('bookmarks.php', {
      body: { getBookmarks: 'true' }
    }, context);
    
    return formatResponse({
      success: true,
      bookmarks: data.bookmarks || data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function addBookmark(args, context) {
  try {
    const data = await cmsRequest('bookmarks.php', {
      body: {
        addBookmark: 'true',
        url: args.url,
        title: args.title,
        icon: args.icon || 'bookmark-outline'
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Bookmark added',
      bookmark: data
    });
  } catch (error) {
    return formatError(error.message);
  }
}

async function deleteBookmark(args, context) {
  try {
    const data = await cmsRequest('bookmarks.php', {
      body: {
        deleteBookmark: 'true',
        bookmarkId: args.bookmarkId
      }
    }, context);
    
    return formatResponse({
      success: true,
      message: 'Bookmark deleted'
    });
  } catch (error) {
    return formatError(error.message);
  }
}
