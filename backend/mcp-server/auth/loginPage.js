function escapeHtml(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function renderLoginPage({ session, error = '', email = '' }) {
  const errorHtml = error
    ? `<div class="error">${escapeHtml(error)}</div>`
    : '';

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign in · Fringelo</title>
  <style>
    :root { color-scheme: light dark; }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: #f6f6f8; color: #1a1a1a;
    }
    .card {
      width: 100%; max-width: 380px; background: #fff; border-radius: 14px;
      box-shadow: 0 8px 30px rgba(0,0,0,.08); padding: 32px 28px;
    }
    .logo { text-align: center; margin-bottom: 8px; }
    .logo img { max-width: 120px; }
    h1 { font-size: 20px; text-align: center; margin: 8px 0 4px; }
    p.sub { text-align: center; color: #777; font-size: 14px; margin: 0 0 24px; }
    label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; }
    input {
      width: 100%; padding: 11px 12px; font-size: 15px; border: 1px solid #d9d9de;
      border-radius: 9px; background: #fff; color: #1a1a1a;
    }
    input:focus { outline: none; border-color: #0078d4; box-shadow: 0 0 0 3px rgba(0,120,212,.15); }
    button {
      width: 100%; margin-top: 22px; padding: 12px; font-size: 15px; font-weight: 600;
      color: #fff; background: #0078d4; border: none; border-radius: 9px; cursor: pointer;
    }
    button:hover { background: #0068ba; }
    .error {
      background: #fdecea; color: #b3261e; border: 1px solid #f5c2c0;
      padding: 10px 12px; border-radius: 9px; font-size: 14px; margin-bottom: 8px;
    }
    .foot { text-align: center; color: #aaa; font-size: 12px; margin-top: 20px; }
    @media (prefers-color-scheme: dark) {
      body { background: #16161a; color: #eee; }
      .card { background: #202027; box-shadow: 0 8px 30px rgba(0,0,0,.4); }
      input { background: #2a2a32; border-color: #3a3a44; color: #eee; }
      p.sub { color: #999; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <img src="https://fringelo.com/fringelo_logo.png" alt="Fringelo" />
    </div>
    <h1>Sign in</h1>
    <p class="sub">Authorize access to your Fringelo workspace</p>
    ${errorHtml}
    <form method="post" action="/login">
      <input type="hidden" name="session" value="${escapeHtml(session)}" />
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="username"
             required autofocus value="${escapeHtml(email)}" />
      <label for="password">Password</label>
      <input id="password" name="password" type="password"
             autocomplete="current-password" required />
      <button type="submit">Sign in &amp; authorize</button>
    </form>
    <div class="foot">Secured by Fringelo · OAuth 2.1</div>
  </div>
</body>
</html>`;
}
