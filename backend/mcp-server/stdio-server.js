/**
 * Control Center MCP Server - STDIO Transport
 * 
 * For local MCP clients that communicate via STDIO
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { 
  CallToolRequestSchema, 
  ListToolsRequestSchema,
  ListResourcesRequestSchema,
  ReadResourceRequestSchema 
} from '@modelcontextprotocol/sdk/types.js';

// Import tool handlers
import { projectTools, handleProjectTool } from './tools/projects.js';
import { pageTools, handlePageTool } from './tools/pages.js';
import { apiTools, handleApiTool } from './tools/apis.js';
import { contentTools, handleContentTool } from './tools/content.js';
import { fileTools, handleFileTool } from './tools/files.js';
import { userTools, handleUserTool } from './tools/users.js';

// Import resources
import { getResources, readResource } from './resources/index.js';

// Configuration from environment
const CMS_BACKEND_URL = process.env.CMS_BACKEND_URL || 'https://alex.polan.sk/control-center';
const JWT_TOKEN = process.env.CMS_JWT_TOKEN;

if (!JWT_TOKEN) {
  console.error('CMS_JWT_TOKEN environment variable is required');
  process.exit(1);
}

/**
 * Verify and get user from JWT token
 */
async function getUser() {
  try {
    const response = await fetch(`${CMS_BACKEND_URL}/token_verify.php`, {
      method: 'POST',
      headers: {
        'Authorization': JWT_TOKEN,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    if (!data.valid) {
      throw new Error('Invalid token');
    }
    return data.user;
  } catch (error) {
    console.error('Token verification failed:', error);
    process.exit(1);
  }
}

async function main() {
  const user = await getUser();
  const context = { user, token: JWT_TOKEN, backendUrl: CMS_BACKEND_URL };
  
  const server = new Server(
    {
      name: 'control-center-cms',
      version: '1.0.0',
    },
    {
      capabilities: {
        tools: {},
        resources: {}
      }
    }
  );

  // Register tool handlers
  server.setRequestHandler(ListToolsRequestSchema, async () => {
    const allTools = [
      ...projectTools,
      ...pageTools,
      ...apiTools,
      ...contentTools,
      ...fileTools,
      ...userTools
    ];
    
    return { tools: allTools };
  });

  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    
    try {
      if (name.startsWith('project_')) {
        return await handleProjectTool(name, args, context);
      } else if (name.startsWith('page_')) {
        return await handlePageTool(name, args, context);
      } else if (name.startsWith('api_')) {
        return await handleApiTool(name, args, context);
      } else if (name.startsWith('content_')) {
        return await handleContentTool(name, args, context);
      } else if (name.startsWith('file_')) {
        return await handleFileTool(name, args, context);
      } else if (name.startsWith('user_')) {
        return await handleUserTool(name, args, context);
      }
      
      return {
        content: [{ type: 'text', text: `Unknown tool: ${name}` }],
        isError: true
      };
    } catch (error) {
      return {
        content: [{ type: 'text', text: `Error: ${error.message}` }],
        isError: true
      };
    }
  });

  // Register resource handlers
  server.setRequestHandler(ListResourcesRequestSchema, async () => {
    return { resources: await getResources(user, CMS_BACKEND_URL) };
  });

  server.setRequestHandler(ReadResourceRequestSchema, async (request) => {
    const { uri } = request.params;
    return await readResource(uri, user, CMS_BACKEND_URL);
  });

  // Connect via STDIO
  const transport = new StdioServerTransport();
  await server.connect(transport);
  
  console.error('Control Center MCP Server connected via STDIO');
}

main().catch(console.error);
