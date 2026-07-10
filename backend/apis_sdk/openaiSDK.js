class OpenAIAPI {
  constructor() {
    this.baseUrl = (process.env.FRINGELO_API_URL || 'https://gw.fringelo.com') + '/openai';
    this.apiKey = process.env['[{[apiKey]}]'] || '';
  }

  async models() {
    return this._get('/models');
  }

  async chat(messages, options = {}) {
    const body = { ...options, model: options.model || 'gpt-4o-mini', messages: this._messages(messages) };
    return this._post('/chat/completions', body);
  }

  async respond(input, options = {}) {
    const body = { ...options, model: options.model || 'gpt-4o-mini', input };
    return this._post('/responses', body);
  }

  async embed(input, options = {}) {
    const body = { ...options, model: options.model || 'text-embedding-3-small', input };
    return this._post('/embeddings', body);
  }

  async image(prompt, options = {}) {
    const body = { ...options, model: options.model || 'gpt-image-1', prompt };
    return this._post('/images/generations', body);
  }

  async moderate(input, options = {}) {
    const body = { ...options, model: options.model || 'omni-moderation-latest', input };
    return this._post('/moderations', body);
  }

  async speech(input, options = {}) {
    const body = { ...options, model: options.model || 'gpt-4o-mini-tts', voice: options.voice || 'alloy', input };
    const res = await fetch(`${this.baseUrl}/audio/speech`, { method: 'POST', headers: this._headers(), body: JSON.stringify(body) });
    if (!res.ok) throw new Error(await this._errorText(res));
    return res.arrayBuffer();
  }

  _messages(m) {
    return typeof m === 'string' ? [{ role: 'user', content: m }] : m;
  }

  _headers() {
    return { 'Content-Type': 'application/json', 'Authorization': `Bearer ${this.apiKey}` };
  }

  async _get(path) {
    return this._handle(await fetch(`${this.baseUrl}${path}`, { headers: this._headers() }));
  }

  async _post(path, body) {
    return this._handle(await fetch(`${this.baseUrl}${path}`, { method: 'POST', headers: this._headers(), body: JSON.stringify(body) }));
  }

  async _errorText(res) {
    try {
      const j = await res.json();
      return (j.error && j.error.message) || JSON.stringify(j);
    } catch (e) {
      return `OpenAI error (HTTP ${res.status})`;
    }
  }

  async _handle(res) {
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error(`OpenAI: invalid response (HTTP ${res.status})`);
    }
    if (!res.ok) {
      throw new Error((data.error && data.error.message) || `OpenAI error (HTTP ${res.status})`);
    }
    return data;
  }
}

export default new OpenAIAPI();
