// CMS Users API SDK
class UsersAPI {
  constructor() {
    this.baseUrl = 'https://alex.polan.sk/control-center/backend/api/v1/users';
    this.apiKey = 'demo-api-key-123'; // Will be set from project settings
  }

  async getAll(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${this.baseUrl}?${queryString}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getById(id) {
    const response = await fetch(`${this.baseUrl}/${id}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async create(userData) {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(userData)
    });
    return this.handleResponse(response);
  }

  async update(id, userData) {
    const response = await fetch(`${this.baseUrl}/${id}`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(userData)
    });
    return this.handleResponse(response);
  }

  async delete(id) {
    const response = await fetch(`${this.baseUrl}/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    const headers = {
      'Content-Type': 'application/json'
    };
    if (this.apiKey) {
      headers['Authorization'] = `Bearer ${this.apiKey}`;
    }
    return headers;
  }

  async handleResponse(response) {
    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`);
    }
    return response.json();
  }
}

export default new UsersAPI();
