const http = require('http');
const crypto = require('crypto');
const mysql = require('mysql2/promise');
const PORT = parseInt(process.env.GW_PORT || '8790', 10);
const PORT_BASE = parseInt(process.env.DEPLOY_PORT_BASE || '21000', 10);
const APPS_DOMAIN = process.env.APPS_DOMAIN || 'apps.fringelo.com';

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  connectionLimit: 10,
  waitForConnections: true,
});

const rate = new Map();

function sha256(s) {
  return crypto.createHash('sha256').update(s).digest('hex');
}

async function authenticate(key) {
  const prefix = key.slice(0, 16);
  const [rows] = await pool.query(
    `SELECT pas.id, pas.projectID, pas.rate_limit, pas.key_hash,
            ca.slug, ca.source_type, ca.endpoint_base, ca.upstream, ca.codespace_id
     FROM project_api_subscriptions pas
     JOIN cms_apis ca ON pas.api_id = ca.id
     WHERE pas.key_prefix = ? AND pas.is_enabled = 1`,
    [prefix]
  );

  const h = sha256(key);
  for (const r of rows) {
    if (r.key_hash && r.key_hash.length === h.length &&
      crypto.timingSafeEqual(Buffer.from(r.key_hash), Buffer.from(h))) {
      return r;
    }
  }
  return null;
}

function checkRate(sub) {
  const now = Date.now();
  const limit = parseInt(sub.rate_limit, 10) || 60;
  let e = rate.get(sub.id);

  if (!e || now - e.ts >= 60000) {
    e = { ts: now, count: 0 };
    rate.set(sub.id, e);
  }

  e.count++;
  return e.count <= limit;
}

async function logCall(sub, method, path, status, ms, ip) {
  try {
    await pool.query(
      `INSERT INTO cms_api_usage_logs (subscription_id, activation_id, method, path, status_code, response_time, ip_address, timestamp)
       VALUES (?, NULL, ?, ?, ?, ?, ?, NOW())`,
      [sub.id, method, path.slice(0, 500), status, ms, ip]
    );

    await pool.query(
      `UPDATE project_api_subscriptions SET usage_count = usage_count + 1, last_used = NOW() WHERE id = ?`,
      [sub.id]
    );
  } catch (e) {
    console.error('log error', e.message);
  }
}

function upstreamFor(sub) {
  if (sub.source_type === 'codespace' && sub.codespace_id) {
    return { host: '127.0.0.1', port: PORT_BASE + sub.codespace_id, hostHeader: `cs-${sub.codespace_id}.${APPS_DOMAIN}`, basePath: '' };
  }

  if (sub.source_type === 'internal') {
    return { host: '127.0.0.1', port: 80, hostHeader: 'api.fringelo.com', basePath: sub.upstream || sub.endpoint_base || '' };
  }

  return null;
}

function end(res, code, obj) {
  res.writeHead(code, { 'content-type': 'application/json' });
  res.end(JSON.stringify(obj));
}

const server = http.createServer(async (req, res) => {
  const start = Date.now();
  const ip = (req.headers['x-forwarded-for'] || '').split(',')[0].trim() || req.socket.remoteAddress || '';
  const url = new URL(req.url, 'http://x');
  const parts = url.pathname.replace(/^\/+/, '').split('/');
  const slug = parts.shift() || '';
  const rest = parts.join('/');

  if (slug === 'health') {
    return end(res, 200, { ok: true });
  }

  const authHeader = req.headers['authorization'] || '';
  const m = authHeader.match(/Bearer\s+(.+)/i);
  const key = m ? m[1].trim() : (req.headers['x-api-key'] || '');
  if (!key) {
    return end(res, 401, { error: 'missing api key' });
  }

  let sub;
  try {
    sub = await authenticate(key);
  } catch (e) {
    return end(res, 500, { error: 'auth error' });
  }

  if (!sub) {
    return end(res, 401, { error: 'invalid api key' });
  }

  if (sub.slug !== slug) {
    return end(res, 403, { error: 'key not valid for this api' });
  }

  if (!checkRate(sub)) {
    logCall(sub, req.method, url.pathname, 429, Date.now() - start, ip);
    return end(res, 429, { error: 'rate limit exceeded' });
  }

  const up = upstreamFor(sub);
  if (!up) {
    return end(res, 400, { error: 'this api is not proxied (external key injection only)' });
  }

  const targetPath = (up.basePath || '') + (rest ? '/' + rest : '') + (url.search || '') || '/';
  const headers = { ...req.headers, host: up.hostHeader, 'x-fringelo-project': String(sub.projectID), 'x-forwarded-for': ip };
  delete headers['content-length'];

  const preq = http.request({ host: up.host, port: up.port, method: req.method, path: targetPath, headers }, (pres) => {
    res.writeHead(pres.statusCode, pres.headers);
    pres.pipe(res);
    pres.on('end', () => logCall(sub, req.method, url.pathname, pres.statusCode, Date.now() - start, ip));
  });

  preq.setTimeout(30000, () => { preq.destroy(); });
  preq.on('error', () => {
    logCall(sub, req.method, url.pathname, 502, Date.now() - start, ip);
    if (!res.headersSent) end(res, 502, { error: 'upstream unavailable' });
  });

  req.pipe(preq);
});

server.listen(PORT, '127.0.0.1', () => console.log('fringelo gateway listening on 127.0.0.1:' + PORT));
