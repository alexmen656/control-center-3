class GeminiAPI {
  constructor() {
    this.baseUrl = (process.env.FRINGELO_API_URL || 'https://gw.fringelo.com') + '/gemini';
    this.apiKey = process.env['[{[apiKey]}]'] || '';
    this.defaultModel = 'gemini-2.5-flash';
  }

  async models() {
    return this._get('/models');
  }

  async generate(prompt, options = {}) {
    const model = options.model || this.defaultModel;
    return this._post(`/models/${model}:generateContent`, this._body(prompt, options));
  }

  async chat(messages, options = {}) {
    const model = options.model || this.defaultModel;
    const contents = messages.map((m) => ({
      role: (m.role === 'assistant' || m.role === 'model') ? 'model' : 'user',
      parts: [{ text: m.text != null ? m.text : (m.content || '') }]
    }));
    const body = { contents };
    this._applyConfig(body, options);
    return this._post(`/models/${model}:generateContent`, body);
  }

  async stream(prompt, options = {}) {
    const model = options.model || this.defaultModel;
    return this._post(`/models/${model}:streamGenerateContent`, this._body(prompt, options));
  }

  async embed(text, options = {}) {
    const model = options.model || 'text-embedding-004';
    return this._post(`/models/${model}:embedContent`, { model: `models/${model}`, content: { parts: [{ text }] } });
  }

  async countTokens(prompt, options = {}) {
    const model = options.model || this.defaultModel;
    return this._post(`/models/${model}:countTokens`, this._body(prompt, options));
  }

  _body(prompt, options) {
    const contents = typeof prompt === 'string'
      ? [{ role: 'user', parts: [{ text: prompt }] }]
      : prompt;
    const body = { contents };
    this._applyConfig(body, options);
    return body;
  }

  _applyConfig(body, options) {
    if (options.system) {
      body.systemInstruction = { parts: [{ text: options.system }] };
    }
    if (options.generationConfig) {
      body.generationConfig = options.generationConfig;
    } else if (options.temperature != null || options.maxOutputTokens != null) {
      body.generationConfig = {};
      if (options.temperature != null) body.generationConfig.temperature = options.temperature;
      if (options.maxOutputTokens != null) body.generationConfig.maxOutputTokens = options.maxOutputTokens;
    }
    if (options.tools) body.tools = options.tools;
    if (options.safetySettings) body.safetySettings = options.safetySettings;
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

  async _handle(res) {
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error(`Gemini: invalid response (HTTP ${res.status})`);
    }
    if (!res.ok) {
      throw new Error((data.error && data.error.message) || `Gemini error (HTTP ${res.status})`);
    }
    return data;
  }
}

export default new GeminiAPI();
