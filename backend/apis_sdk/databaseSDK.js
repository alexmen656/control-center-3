// CMS Database API SDK
class DatabaseAPI {
  constructor() {
    this.baseUrl = 'https://alex.polan.sk/control-center/api/v1/database.php';
    this.apiKey = process.env.[{[apiKey]}] || 'cms_demo_api_key';
  }

  async listTables() {
    const response = await fetch(`${this.baseUrl}?action=tables`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async query(table, conditions = {}, options = {}) {
    const response = await fetch(`${this.baseUrl}?action=query`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, conditions, options })
    });
    return this.handleResponse(response);
  }

  async insert(table, data) {
    const response = await fetch(`${this.baseUrl}?action=insert`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, data })
    });
    return this.handleResponse(response);
  }

  async update(table, id, data) {
    const response = await fetch(`${this.baseUrl}?action=update`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify({ table, id, data })
    });
    return this.handleResponse(response);
  }

  async delete(table, id) {
    const response = await fetch(`${this.baseUrl}?action=delete`, {
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
    const data = await response.json();
    
    if (!response.ok || !data.success) {
      throw new Error(data.message || `Database API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new DatabaseAPI();
