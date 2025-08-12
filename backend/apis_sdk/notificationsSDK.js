// CMS Notifications API SDK
class NotificationsAPI {
  constructor() {
    this.baseUrl = 'https://alex.polan.sk/control-center/backend/api/v1/notifications';
    this.apiKey = 'demo-api-key-123';
  }

  async getAll(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${this.baseUrl}?${queryString}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async send(notificationData) {
    const response = await fetch(`${this.baseUrl}/send`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(notificationData)
    });
    return this.handleResponse(response);
  }

  async markAsRead(notificationId) {
    const response = await fetch(`${this.baseUrl}/${notificationId}`, {
      method: 'PUT',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async delete(notificationId) {
    const response = await fetch(`${this.baseUrl}/${notificationId}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    if (this.apiKey) headers['Authorization'] = `Bearer ${this.apiKey}`;
    return headers;
  }

  async handleResponse(response) {
    if (!response.ok) {
      throw new Error(`Notifications API Error: ${response.status} ${response.statusText}`);
    }
    return response.json();
  }
}

export default new NotificationsAPI();
