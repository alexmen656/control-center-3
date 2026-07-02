import { promises as fs } from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const AUTH_CODE_TTL_MS = 5 * 60 * 1000;
const LOGIN_SESSION_TTL_MS = 15 * 60 * 1000;

class FileClientsStore {
  constructor(dataDir) {
    this.file = path.join(dataDir, 'clients.json');
    this.dataDir = dataDir;
    this.clients = new Map();
  }

  async load() {
    try {
      const raw = await fs.readFile(this.file, 'utf8');
      for (const client of JSON.parse(raw)) {
        this.clients.set(client.client_id, client);
      }
    } catch {
      return;
    }
  }

  async #persist() {
    await fs.mkdir(this.dataDir, { recursive: true });
    await fs.writeFile(
      this.file,
      JSON.stringify([...this.clients.values()], null, 2)
    );
  }

  getClient(clientId) {
    return this.clients.get(clientId);
  }

  async registerClient(client) {
    this.clients.set(client.client_id, client);
    await this.#persist();
    return client;
  }
}

function decodeJwtPayload(jwt) {
  try {
    const [, payload] = jwt.split('.');
    return JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
  } catch {
    return {};
  }
}

export class FringeloOAuthProvider {
  constructor({ backendUrl, dataDir }) {
    this.backendUrl = backendUrl;
    this._clientsStore = new FileClientsStore(dataDir);
    this._loginSessions = new Map();
    this._authCodes = new Map();
  }

  async init() {
    await this._clientsStore.load();
  }

  get clientsStore() {
    return this._clientsStore;
  }

  async authorize(client, params, res) {
    this.#gc();

    const loginSession = crypto.randomUUID();
    this._loginSessions.set(loginSession, {
      clientId: client.client_id,
      redirectUri: params.redirectUri,
      codeChallenge: params.codeChallenge,
      state: params.state,
      scopes: params.scopes ?? [],
      resource: params.resource?.href,
      createdAt: Date.now(),
    });

    res.redirect(302, `/login?session=${encodeURIComponent(loginSession)}`);
  }

  hasLoginSession(loginSession) {
    const session = this._loginSessions.get(loginSession);
    if (!session) return false;
    if (Date.now() - session.createdAt > LOGIN_SESSION_TTL_MS) {
      this._loginSessions.delete(loginSession);
      return false;
    }
    return true;
  }

  completeLogin(loginSession, jwt, user) {
    const session = this._loginSessions.get(loginSession);
    if (!session) {
      throw new Error('Login session expired or not found.');
    }
    this._loginSessions.delete(loginSession);

    const code = crypto.randomBytes(32).toString('hex');
    this._authCodes.set(code, {
      clientId: session.clientId,
      redirectUri: session.redirectUri,
      codeChallenge: session.codeChallenge,
      scopes: session.scopes,
      jwt,
      user,
      expiresAt: Date.now() + AUTH_CODE_TTL_MS,
    });

    return { redirectUri: session.redirectUri, code, state: session.state };
  }

  async challengeForAuthorizationCode(client, authorizationCode) {
    const entry = this._authCodes.get(authorizationCode);
    if (!entry || entry.clientId !== client.client_id) {
      throw new Error('Invalid authorization code.');
    }
    return entry.codeChallenge;
  }

  async exchangeAuthorizationCode(client, authorizationCode, _codeVerifier, redirectUri) {
    const entry = this._authCodes.get(authorizationCode);
    if (!entry || entry.clientId !== client.client_id) {
      throw new Error('Invalid authorization code.');
    }
    this._authCodes.delete(authorizationCode);

    if (Date.now() > entry.expiresAt) {
      throw new Error('Authorization code expired.');
    }
    if (redirectUri && redirectUri !== entry.redirectUri) {
      throw new Error('redirect_uri mismatch.');
    }

    const payload = decodeJwtPayload(entry.jwt);
    const expiresIn = payload.exp
      ? Math.max(0, payload.exp - Math.floor(Date.now() / 1000))
      : undefined;

    return {
      access_token: entry.jwt,
      token_type: 'Bearer',
      ...(expiresIn !== undefined ? { expires_in: expiresIn } : {}),
      scope: (entry.scopes ?? []).join(' ') || undefined,
    };
  }

  async exchangeRefreshToken() {
    throw new Error('Refresh tokens are not supported.');
  }

  async verifyAccessToken(token) {
    const response = await fetch(`${this.backendUrl}/token_verify.php`, {
      method: 'POST',
      headers: {
        Authorization: token,
        'Content-Type': 'application/json',
      },
    });

    const data = await response.json().catch(() => ({}));
    if (!data.valid || !data.user) {
      throw new Error('Invalid or expired token.');
    }

    const payload = decodeJwtPayload(token);
    return {
      token,
      clientId: 'fringelo-mcp',
      scopes: ['mcp'],
      expiresAt: payload.exp,
      extra: { user: data.user },
    };
  }

  #gc() {
    const now = Date.now();
    for (const [key, s] of this._loginSessions) {
      if (now - s.createdAt > LOGIN_SESSION_TTL_MS) this._loginSessions.delete(key);
    }
    for (const [key, c] of this._authCodes) {
      if (now > c.expiresAt) this._authCodes.delete(key);
    }
  }
}
