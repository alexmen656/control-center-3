// CMS Files API SDK
class FilesAPI {
  constructor() {
    this.baseUrl = 'https://alex.polan.sk/control-center/backend/api/v1/files';
    this.apiKey = 'demo-api-key-123'; // Wird später durch echte API-Keys ersetzt
  }

  async upload(file, folder = '') {
    const formData = new FormData();
    formData.append('file', file);
    if (folder) formData.append('folder', folder);

    const response = await fetch(`${this.baseUrl}/upload`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${this.apiKey}` },
      body: formData
    });
    return this.handleResponse(response);
  }

  async list(folder = '') {
    const queryString = folder ? `?folder=${encodeURIComponent(folder)}` : '';
    const response = await fetch(`${this.baseUrl}${queryString}`, {
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async delete(fileId) {
    const response = await fetch(`${this.baseUrl}/${fileId}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getDownloadUrl(fileId) {
    const response = await fetch(`${this.baseUrl}/${fileId}/download-url`, {
      headers: this.getHeaders()
    });
    const data = await this.handleResponse(response);
    return data.downloadUrl;
  }

  getHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    if (this.apiKey) headers['Authorization'] = `Bearer ${this.apiKey}`;
    return headers;
  }

  async handleResponse(response) {
    if (!response.ok) {
      throw new Error(`Files API Error: ${response.status} ${response.statusText}`);
    }
    return response.json();
  }
}

export default new FilesAPI();
