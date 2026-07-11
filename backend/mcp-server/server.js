import express from 'express';
import cors from 'cors';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import {
  mcpAuthRouter,
  getOAuthProtectedResourceMetadataUrl,
} from '@modelcontextprotocol/sdk/server/auth/router.js';
import { requireBearerAuth } from '@modelcontextprotocol/sdk/server/auth/middleware/bearerAuth.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  ListResourcesRequestSchema,
  ReadResourceRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';

import { projectTools, handleProjectTool } from './tools/projects.js';
import { apiTools, handleApiTool } from './tools/apis.js';
import { contentTools, handleContentTool } from './tools/content.js';
import { fileTools, handleFileTool } from './tools/files.js';
import { codespaceTools, handleCodespaceTool } from './tools/codespaces.js';
import { userTools, handleUserTool } from './tools/users.js';
import { domainTools, handleDomainTool } from './tools/domains.js';
import { getResources, readResource } from './resources/index.js';

import { FringeloOAuthProvider } from './auth/provider.js';
import { renderLoginPage } from './auth/loginPage.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const PORT = process.env.MCP_PORT || 3001;
const CMS_BACKEND_URL = process.env.CMS_BACKEND_URL || 'https://api.fringelo.com';
const PUBLIC_URL = (process.env.MCP_PUBLIC_URL || `http://localhost:${PORT}`).replace(/\/$/, '');
const DATA_DIR = process.env.MCP_DATA_DIR || path.join(__dirname, '.data');

const ALL_TOOLS = [
  ...projectTools,
  ...apiTools,
  ...contentTools,
  ...fileTools,
  ...codespaceTools,
  ...userTools,
  ...domainTools,
];

const SERVER_INSTRUCTIONS = `Fringelo Control Center — build and run backends/apps for a project.

Core concepts:
- Project: the top-level container. Almost every tool needs a "project" (link/slug).
- Codespace: one deployable app inside a project (its own code + git + deploy URL). A project can have several; each has a "slug". A codespace does NOT exist until you create it with codespace_create — creating a project does not create a "main" codespace. You must create a codespace before writing any files; codespace_* file tools reject a slug that does not exist.
- Data storage: there is no raw SQL database tool. Use content_table_* as your data tables — content_table_create defines a collection (table) with typed fields, and content_table_submit / content_table_submissions / content_table_update / content_table_delete are its create/read/update/delete. Treat a "form" as a database table.
- API catalog (api_*): registers/documents API metadata (name, endpoints, external baseUrl) and subscribes to third-party APIs. api_create does NOT implement or host a backend.
- Gateway API: to make a codespace's own code callable as a public REST API, deploy it and then call codespace_publish_as_api (returns https://gw.fringelo.com/<slug>).

Recommended workflow to build a backend (e.g. "program me a calorie counter backend"):
1. project_create(name) -> note the returned link/slug. This creates ONLY the project, no codespace.
2. codespace_create(project, name, template:"node") -> ALWAYS do this before writing any file, and note the returned slug. Skipping this step and calling codespace_create_file directly will be rejected.
3. Write code with codespace_create_file / codespace_update_file, passing the slug from step 2 as "codespace". For a Node backend include a package.json with a start script and an HTTP server listening on the PORT env var.
4. Persist data with content_table_create (e.g. a "calorie_entries" collection with fields date/food/calories) and read/write it via content_table_submit / content_table_submissions — or from inside the codespace via the gateway.
5. Deploy with codespace_deploy -> returns the live URL https://cs-<id>.apps.fringelo.com. Poll codespace_list_deployments for READY/ERROR.
6. Optional: codespace_publish_as_api to expose it at https://gw.fringelo.com/<slug>; api_generate_key issues a project API key; domain_codespace_connect / connect a custom domain.

Notes:
- git commit/push versions code but does NOT deploy; always call codespace_deploy to go live.
- A codespace must exist before writing files; if a file tool reports the codespace does not exist, call codespace_create first (do not retry the write with a guessed slug).`;

function routeTool(name, args, context) {
  if (name.startsWith('project_')) return handleProjectTool(name, args, context);
  if (name.startsWith('api_')) return handleApiTool(name, args, context);
  if (name.startsWith('content_')) return handleContentTool(name, args, context);
  if (name.startsWith('file_')) return handleFileTool(name, args, context);
  if (name.startsWith('codespace_')) return handleCodespaceTool(name, args, context);
  if (name.startsWith('user_')) return handleUserTool(name, args, context);
  if (name.startsWith('domain_')) return handleDomainTool(name, args, context);
  return Promise.resolve({
    content: [{ type: 'text', text: `Unknown tool: ${name}` }],
    isError: true,
  });
}

function buildMcpServer(context) {
  const server = new Server(
    { name: 'control-center-cms', version: '2.0.0' },
    { capabilities: { tools: {}, resources: {} }, instructions: SERVER_INSTRUCTIONS }
  );

  server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: ALL_TOOLS }));

  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    try {
      return await routeTool(name, args, context);
    } catch (error) {
      return {
        content: [{ type: 'text', text: `Error: ${error.message}` }],
        isError: true,
      };
    }
  });

  server.setRequestHandler(ListResourcesRequestSchema, async () => ({
    resources: await getResources(context.user, context.backendUrl, context.token),
  }));

  server.setRequestHandler(ReadResourceRequestSchema, async (request) =>
    readResource(request.params.uri, context.user, context.backendUrl, context.token)
  );

  return server;
}

const provider = new FringeloOAuthProvider({ backendUrl: CMS_BACKEND_URL, dataDir: DATA_DIR });
await provider.init();

const app = express();

app.use(
  cors({
    exposedHeaders: ['Mcp-Session-Id', 'WWW-Authenticate'],
    allowedHeaders: ['Authorization', 'Content-Type', 'Mcp-Session-Id', 'Mcp-Protocol-Version'],
  })
);
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

app.get('/health', (req, res) => {
  res.json({ status: 'ok', version: '2.0.0' });
});

app.get('/login', (req, res) => {
  const session = String(req.query.session || '');
  if (!provider.hasLoginSession(session)) {
    res.status(400).send(renderLoginPage({ session: '', error: 'Invalid or expired login session. Please restart the authorization from your client.' }));
    return;
  }
  res.type('html').send(renderLoginPage({ session }));
});

app.post('/login', async (req, res) => {
  const { session, email, password } = req.body;

  if (!provider.hasLoginSession(session)) {
    res.status(400).type('html').send(renderLoginPage({ session: '', error: 'Login session expired. Please restart the authorization from your client.' }));
    return;
  }

  try {
    const response = await fetch(`${CMS_BACKEND_URL}/v2/auth/mcp-login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ email: email || '', password: password || '' }),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.token) {
      res.status(401).type('html').send(
        renderLoginPage({ session, email, error: data.error || 'Invalid email or password.' })
      );
      return;
    }

    const { redirectUri, code, state } = provider.completeLogin(session, data.token, data.user);
    const target = new URL(redirectUri);
    target.searchParams.set('code', code);
    if (state) target.searchParams.set('state', state);
    res.redirect(302, target.href);
  } catch (error) {
    res.status(500).type('html').send(
      renderLoginPage({ session, email, error: `Authentication failed: ${error.message}` })
    );
  }
});

app.use(
  mcpAuthRouter({
    provider,
    issuerUrl: new URL(PUBLIC_URL),
    resourceServerUrl: new URL(`${PUBLIC_URL}/mcp`),
    scopesSupported: ['mcp'],
    resourceName: 'Fringelo CMS',
  })
);

const bearerAuth = requireBearerAuth({
  verifier: provider,
  resourceMetadataUrl: getOAuthProtectedResourceMetadataUrl(new URL(`${PUBLIC_URL}/mcp`)),
});

app.post('/mcp', bearerAuth, async (req, res) => {
  const context = {
    user: req.auth?.extra?.user,
    token: req.auth?.token,
    backendUrl: CMS_BACKEND_URL,
  };

  const server = buildMcpServer(context);
  const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined });

  res.on('close', () => {
    transport.close();
    server.close();
  });

  try {
    await server.connect(transport);
    await transport.handleRequest(req, res, req.body);
  } catch (error) {
    if (!res.headersSent) {
      res.status(500).json({
        jsonrpc: '2.0',
        error: { code: -32603, message: `Internal error: ${error.message}` },
        id: null,
      });
    }
  }
});

const methodNotAllowed = (req, res) => {
  res.status(405).set('Allow', 'POST').json({
    jsonrpc: '2.0',
    error: { code: -32000, message: 'Method not allowed. This is a stateless server; use POST.' },
    id: null,
  });
};

app.get('/mcp', bearerAuth, methodNotAllowed);
app.delete('/mcp', bearerAuth, methodNotAllowed);

app.listen(PORT, () => {
  console.log(`Fringelo MCP Server (OAuth) running on port ${PORT}`);
  console.log(`Public URL: ${PUBLIC_URL}`);
  console.log(`MCP endpoint: ${PUBLIC_URL}/mcp`);
  console.log(`Auth metadata: ${PUBLIC_URL}/.well-known/oauth-authorization-server`);
});

export { app, buildMcpServer, provider };
