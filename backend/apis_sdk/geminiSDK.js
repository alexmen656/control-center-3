// Google Gemini API SDK
class GeminiAPI {
  constructor() {
    this.baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    this.apiKey = process.env.[{[apiKey]}] || 'cms_demo_api_key';
  }

  async listModels() {
    const response = await fetch(`${this.baseUrl}/models?key=${this.apiKey}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async generateContent(prompt, options = {}) {
    const model = options.model || 'gemini-pro';
    const requestBody = {
      contents: [{
        parts: [{
          text: prompt
        }]
      }],
      generationConfig: {
        temperature: options.temperature || 0.7,
        topK: options.topK || 40,
        topP: options.topP || 0.95,
        maxOutputTokens: options.maxOutputTokens || 1024,
        stopSequences: options.stopSequences || []
      },
      safetySettings: options.safetySettings || [
        {
          category: "HARM_CATEGORY_HARASSMENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_HATE_SPEECH",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_DANGEROUS_CONTENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        }
      ]
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:generateContent?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async generateContentWithHistory(messages, options = {}) {
    const model = options.model || 'gemini-pro';
    const contents = messages.map(msg => ({
      role: msg.role || 'user',
      parts: [{
        text: msg.content
      }]
    }));

    const requestBody = {
      contents: contents,
      generationConfig: {
        temperature: options.temperature || 0.7,
        topK: options.topK || 40,
        topP: options.topP || 0.95,
        maxOutputTokens: options.maxOutputTokens || 1024,
        stopSequences: options.stopSequences || []
      },
      safetySettings: options.safetySettings || [
        {
          category: "HARM_CATEGORY_HARASSMENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_HATE_SPEECH",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_DANGEROUS_CONTENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        }
      ]
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:generateContent?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async generateContentWithImage(prompt, imageData, mimeType = 'image/jpeg', options = {}) {
    const model = options.model || 'gemini-pro-vision';
    const requestBody = {
      contents: [{
        parts: [
          {
            text: prompt
          },
          {
            inline_data: {
              mime_type: mimeType,
              data: imageData
            }
          }
        ]
      }],
      generationConfig: {
        temperature: options.temperature || 0.4,
        topK: options.topK || 32,
        topP: options.topP || 1,
        maxOutputTokens: options.maxOutputTokens || 4096
      },
      safetySettings: options.safetySettings || [
        {
          category: "HARM_CATEGORY_HARASSMENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_HATE_SPEECH",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        },
        {
          category: "HARM_CATEGORY_DANGEROUS_CONTENT",
          threshold: "BLOCK_MEDIUM_AND_ABOVE"
        }
      ]
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:generateContent?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async streamGenerateContent(prompt, options = {}) {
    const model = options.model || 'gemini-pro';
    const requestBody = {
      contents: [{
        parts: [{
          text: prompt
        }]
      }],
      generationConfig: {
        temperature: options.temperature || 0.7,
        topK: options.topK || 40,
        topP: options.topP || 0.95,
        maxOutputTokens: options.maxOutputTokens || 1024
      }
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:streamGenerateContent?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error?.message || `Gemini API Error: ${response.status} ${response.statusText}`);
    }

    return response;
  }

  async embedContent(text, options = {}) {
    const model = options.model || 'embedding-001';
    const requestBody = {
      content: {
        parts: [{
          text: text
        }]
      }
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:embedContent?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async batchEmbedContents(texts, options = {}) {
    const model = options.model || 'embedding-001';
    const requests = texts.map(text => ({
      content: {
        parts: [{
          text: text
        }]
      }
    }));

    const requestBody = {
      requests: requests
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:batchEmbedContents?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async countTokens(text, options = {}) {
    const model = options.model || 'gemini-pro';
    const requestBody = {
      contents: [{
        parts: [{
          text: text
        }]
      }]
    };

    const response = await fetch(`${this.baseUrl}/models/${model}:countTokens?key=${this.apiKey}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Content-Type': 'application/json'
    };
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error?.message || `Gemini API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new GeminiAPI();
