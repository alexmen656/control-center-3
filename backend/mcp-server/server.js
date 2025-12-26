/**
 * Control Center MCP Server - HTTP Transport
 * 
 * This server exposes the CMS functionality via MCP protocol over HTTP.
 * AI agents can connect via HTTP to manage projects, pages, content, and more.
 * 
 * Authentication: JWT token from Control Center
 */

import express from 'express';
import cors from 'cors';
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
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

// Configuration
const PORT = process.env.MCP_PORT || 3001;
const CMS_BACKEND_URL = process.env.CMS_BACKEND_URL || 'https://alex.polan.sk/control-center';

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Store active MCP sessions
const sessions = new Map();

/**
 * Verify JWT token with CMS backend
 */
async function verifyToken(token) {
  try {
    const response = await fetch(`${CMS_BACKEND_URL}/token_verify.php`, {
      method: 'POST',
      headers: {
        'Authorization': token,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    return data.valid ? data.user : null;
  } catch (error) {
    console.error('Token verification failed:', error);
    return null;
  }
}

/**
 * Create MCP server instance for a session
 */
function createMcpServer(user) {
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
    
    // Add user context to all tool calls
    const context = { user, backendUrl: CMS_BACKEND_URL };
    
    try {
      // Route to appropriate tool handler
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

  return server;
}

// ============================================
// HTTP Endpoints for MCP over HTTP
// ============================================

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
  res.json({ status: 'ok', version: '1.0.0' });
});

/**
 * MCP Server Info
 */
app.get('/mcp', (req, res) => {
  res.json({
    name: 'control-center-cms',
    version: '1.0.0',
    description: 'MCP Server for Control Center CMS',
    capabilities: ['tools', 'resources'],
    authentication: 'JWT Bearer token required'
  });
});

/**
 * Authentication middleware
 */
async function authMiddleware(req, res, next) {
  const authHeader = req.headers.authorization;
  
  if (!authHeader) {
    return res.status(401).json({ error: 'Authorization header required' });
  }
  
  const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : authHeader;
  const user = await verifyToken(token);
  
  if (!user) {
    return res.status(401).json({ error: 'Invalid or expired token' });
  }
  
  req.user = user;
  req.token = token;
  next();
}

/**
 * List available tools
 */
app.get('/mcp/tools', authMiddleware, async (req, res) => {
  try {
    const allTools = [
      ...projectTools,
      ...pageTools,
      ...apiTools,
      ...contentTools,
      ...fileTools,
      ...userTools
    ];
    
    res.json({ tools: allTools });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * Execute a tool
 */
app.post('/mcp/tools/:toolName', authMiddleware, async (req, res) => {
  const { toolName } = req.params;
  const args = req.body;
  const context = { 
    user: req.user, 
    token: req.token,
    backendUrl: CMS_BACKEND_URL 
  };
  
  try {
    let result;
    
    if (toolName.startsWith('project_')) {
      result = await handleProjectTool(toolName, args, context);
    } else if (toolName.startsWith('page_')) {
      result = await handlePageTool(toolName, args, context);
    } else if (toolName.startsWith('api_')) {
      result = await handleApiTool(toolName, args, context);
    } else if (toolName.startsWith('content_')) {
      result = await handleContentTool(toolName, args, context);
    } else if (toolName.startsWith('file_')) {
      result = await handleFileTool(toolName, args, context);
    } else if (toolName.startsWith('user_')) {
      result = await handleUserTool(toolName, args, context);
    } else {
      return res.status(404).json({ error: `Unknown tool: ${toolName}` });
    }
    
    res.json(result);
  } catch (error) {
    res.status(500).json({ 
      content: [{ type: 'text', text: `Error: ${error.message}` }],
      isError: true 
    });
  }
});

/**
 * List available resources
 */
app.get('/mcp/resources', authMiddleware, async (req, res) => {
  try {
    const resources = await getResources(req.user, CMS_BACKEND_URL);
    res.json({ resources });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * Read a specific resource
 */
app.get('/mcp/resources/:type', authMiddleware, async (req, res) => {
  const { type } = req.params;
  const uri = `cms://${type}`;
  
  try {
    const resource = await readResource(uri, req.user, CMS_BACKEND_URL);
    res.json(resource);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.get('/mcp/resources/:type/:id', authMiddleware, async (req, res) => {
  const { type, id } = req.params;
  const uri = `cms://${type}/${id}`;
  
  try {
    const resource = await readResource(uri, req.user, CMS_BACKEND_URL);
    res.json(resource);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.get('/mcp/resources/:type/:id/:subResource', authMiddleware, async (req, res) => {
  const { type, id, subResource } = req.params;
  const uri = `cms://${type}/${id}/${subResource}`;
  
  try {
    const resource = await readResource(uri, req.user, CMS_BACKEND_URL);
    res.json(resource);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * Batch tool execution
 */
app.post('/mcp/batch', authMiddleware, async (req, res) => {
  const { operations } = req.body;
  const context = { 
    user: req.user, 
    token: req.token,
    backendUrl: CMS_BACKEND_URL 
  };
  
  const results = [];
  
  for (const op of operations) {
    try {
      let result;
      const { tool, arguments: args } = op;
      
      if (tool.startsWith('project_')) {
        result = await handleProjectTool(tool, args, context);
      } else if (tool.startsWith('page_')) {
        result = await handlePageTool(tool, args, context);
      } else if (tool.startsWith('api_')) {
        result = await handleApiTool(tool, args, context);
      } else if (tool.startsWith('content_')) {
        result = await handleContentTool(tool, args, context);
      } else if (tool.startsWith('file_')) {
        result = await handleFileTool(tool, args, context);
      } else if (tool.startsWith('user_')) {
        result = await handleUserTool(tool, args, context);
      } else {
        result = { error: `Unknown tool: ${tool}` };
      }
      
      results.push({ tool, success: true, result });
    } catch (error) {
      results.push({ tool: op.tool, success: false, error: error.message });
    }
  }
  
  res.json({ results });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Control Center MCP Server running on port ${PORT}`);
  console.log(`📚 API Docs: http://localhost:${PORT}/mcp`);
  console.log(`🔧 Tools: http://localhost:${PORT}/mcp/tools`);
  console.log(`📁 Resources: http://localhost:${PORT}/mcp/resources`);
});

export { app, createMcpServer };
