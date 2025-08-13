// OpenAI API SDK
class OpenAIAPI {
  constructor() {
    this.baseUrl = 'https://api.openai.com/v1';
    this.apiKey = process.env.[{[apiKey]}] || 'cms_demo_api_key';
  }

  async listModels() {
    const response = await fetch(`${this.baseUrl}/models`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createCompletion(prompt, options = {}) {
    const requestBody = {
      model: options.model || 'gpt-3.5-turbo-instruct',
      prompt: prompt,
      max_tokens: options.max_tokens || 150,
      temperature: options.temperature || 0.7,
      top_p: options.top_p || 1,
      frequency_penalty: options.frequency_penalty || 0,
      presence_penalty: options.presence_penalty || 0,
      stop: options.stop || null
    };

    const response = await fetch(`${this.baseUrl}/completions`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async createChatCompletion(messages, options = {}) {
    const requestBody = {
      model: options.model || 'gpt-3.5-turbo',
      messages: messages,
      max_tokens: options.max_tokens || 150,
      temperature: options.temperature || 0.7,
      top_p: options.top_p || 1,
      frequency_penalty: options.frequency_penalty || 0,
      presence_penalty: options.presence_penalty || 0,
      stop: options.stop || null,
      stream: options.stream || false
    };

    const response = await fetch(`${this.baseUrl}/chat/completions`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async createEmbedding(input, options = {}) {
    const requestBody = {
      model: options.model || 'text-embedding-ada-002',
      input: input,
      encoding_format: options.encoding_format || 'float'
    };

    const response = await fetch(`${this.baseUrl}/embeddings`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async createImage(prompt, options = {}) {
    const requestBody = {
      prompt: prompt,
      n: options.n || 1,
      size: options.size || '1024x1024',
      response_format: options.response_format || 'url',
      //quality: options.quality || 'standard',
      //style: options.style || 'vivid'
    };

    const response = await fetch(`${this.baseUrl}/images/generations`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async transcribeAudio(audioFile, options = {}) {
    const formData = new FormData();
    formData.append('file', audioFile);
    formData.append('model', options.model || 'whisper-1');
    if (options.language) formData.append('language', options.language);
    if (options.prompt) formData.append('prompt', options.prompt);
    if (options.response_format) formData.append('response_format', options.response_format);
    if (options.temperature) formData.append('temperature', options.temperature.toString());

    const response = await fetch(`${this.baseUrl}/audio/transcriptions`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async translateAudio(audioFile, options = {}) {
    const formData = new FormData();
    formData.append('file', audioFile);
    formData.append('model', options.model || 'whisper-1');
    if (options.prompt) formData.append('prompt', options.prompt);
    if (options.response_format) formData.append('response_format', options.response_format);
    if (options.temperature) formData.append('temperature', options.temperature.toString());

    const response = await fetch(`${this.baseUrl}/audio/translations`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async moderateContent(input) {
    const requestBody = {
      input: input
    };

    const response = await fetch(`${this.baseUrl}/moderations`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${this.apiKey}`
    };
  }

  getAuthHeaders() {
    return {
      'Authorization': `Bearer ${this.apiKey}`
    };
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error?.message || `OpenAI API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new OpenAIAPI();
