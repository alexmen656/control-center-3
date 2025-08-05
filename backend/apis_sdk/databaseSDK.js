// CMS Database API SDK
class DatabaseAPI {
  constructor() {
    this.baseUrl = '/backend/api/v1/database';
    this.apiKey = null;
  }

  async query(table, conditions = {}, options = {}) {
    const response = await fetch(`${this.baseUrl}/query`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, conditions, options })
    });
    return this.handleResponse(response);
  }

  async insert(table, data) {
    const response = await fetch(`${this.baseUrl}/insert`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, data })
    });
    return this.handleResponse(response);
  }

  async update(table, id, data) {
    const response = await fetch(`${this.baseUrl}/update`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, id, data })
    });
    return this.handleResponse(response);
  }

  async delete(table, id) {
    const response = await fetch(`${this.baseUrl}/delete`, {
      method: 'DELETE',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, id })
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${this.apiKey}`
    };
  }

  async handleResponse(response) {
    if (!response.ok) {
      throw new Error(`Database API Error: ${response.status} ${response.statusText}`);
    }
    return response.json();
  }
}

export default new DatabaseAPI();
