// CMS Analytics API SDK
class AnalyticsAPI {
  constructor() {
    this.baseUrl = 'https://alex.polan.sk/control-center/backend/api/v1/analytics';
    this.apiKey = 'demo-api-key-123';
  }

  async track(eventName, eventData = {}) {
    const response = await fetch(`${this.baseUrl}/track`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({
        event_name: eventName,
        data: eventData,
        session_id: this.getSessionId()
      })
    });
    return this.handleResponse(response);
  }

  async getEvents(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${this.baseUrl}/events?${queryString}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getReport(reportType = 'daily', params = {}) {
    const queryString = new URLSearchParams({ type: reportType, ...params }).toString();
    const response = await fetch(`${this.baseUrl}/report?${queryString}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getStats() {
    const response = await fetch(`${this.baseUrl}/stats`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  getSessionId() {
    // Erstelle oder hole Session-ID aus localStorage
    let sessionId = localStorage.getItem('analytics_session_id');
    if (!sessionId) {
      sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem('analytics_session_id', sessionId);
    }
    return sessionId;
  }

  getHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    if (this.apiKey) headers['Authorization'] = `Bearer ${this.apiKey}`;
    return headers;
  }

  async handleResponse(response) {
    if (!response.ok) {
      throw new Error(`Analytics API Error: ${response.status} ${response.statusText}`);
    }
    return response.json();
  }
}

export default new AnalyticsAPI();
