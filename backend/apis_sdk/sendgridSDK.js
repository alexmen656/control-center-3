// SendGrid Email API SDK
class SendGridAPI {
  constructor() {
    this.baseUrl = 'https://api.sendgrid.com/v3';
    this.apiKey = process.env.SENDGRID_API_KEY || '';
  }

  async sendEmail(to, from, subject, content, options = {}) {
    const requestBody = {
      personalizations: [{
        to: Array.isArray(to) ? to : [{ email: to }],
        subject: subject,
        cc: options.cc || [],
        bcc: options.bcc || []
      }],
      from: typeof from === 'string' ? { email: from } : from,
      content: Array.isArray(content) ? content : [{
        type: options.content_type || 'text/plain',
        value: content
      }],
      reply_to: options.reply_to || null,
      attachments: options.attachments || [],
      template_id: options.template_id || null,
      categories: options.categories || [],
      custom_args: options.custom_args || {},
      send_at: options.send_at || null
    };

    const response = await fetch(`${this.baseUrl}/mail/send`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async sendTemplate(to, from, templateId, dynamicTemplateData = {}, options = {}) {
    const requestBody = {
      personalizations: [{
        to: Array.isArray(to) ? to : [{ email: to }],
        dynamic_template_data: dynamicTemplateData,
        cc: options.cc || [],
        bcc: options.bcc || []
      }],
      from: typeof from === 'string' ? { email: from } : from,
      template_id: templateId,
      reply_to: options.reply_to || null,
      categories: options.categories || [],
      custom_args: options.custom_args || {},
      send_at: options.send_at || null
    };

    const response = await fetch(`${this.baseUrl}/mail/send`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getTemplates(options = {}) {
    const params = new URLSearchParams({
      generations: options.generations || 'legacy,dynamic',
      page_size: options.page_size || '200'
    });

    const response = await fetch(`${this.baseUrl}/templates?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getTemplate(templateId) {
    const response = await fetch(`${this.baseUrl}/templates/${templateId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createTemplate(name, generation = 'dynamic') {
    const requestBody = {
      name: name,
      generation: generation
    };

    const response = await fetch(`${this.baseUrl}/templates`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async updateTemplate(templateId, name) {
    const requestBody = {
      name: name
    };

    const response = await fetch(`${this.baseUrl}/templates/${templateId}`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async deleteTemplate(templateId) {
    const response = await fetch(`${this.baseUrl}/templates/${templateId}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getSuppressions(type = 'bounces', options = {}) {
    const params = new URLSearchParams({
      limit: options.limit || '500',
      offset: options.offset || '0'
    });
    if (options.start_time) params.append('start_time', options.start_time);
    if (options.end_time) params.append('end_time', options.end_time);

    const response = await fetch(`${this.baseUrl}/suppression/${type}?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async addSuppression(type, emails) {
    const emailArray = Array.isArray(emails) ? emails : [emails];
    const requestBody = emailArray.map(email => ({
      email: typeof email === 'string' ? email : email.email,
      reason: typeof email === 'object' ? email.reason : undefined
    }));

    const response = await fetch(`${this.baseUrl}/suppression/${type}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async removeSuppression(type, email) {
    const response = await fetch(`${this.baseUrl}/suppression/${type}/${email}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getStats(options = {}) {
    const params = new URLSearchParams({
      start_date: options.start_date || new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      end_date: options.end_date || new Date().toISOString().split('T')[0],
      aggregated_by: options.aggregated_by || 'day'
    });
    if (options.categories) params.append('categories', options.categories);

    const response = await fetch(`${this.baseUrl}/stats?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async validateEmail(email) {
    const response = await fetch(`${this.baseUrl}/validations/email`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ email: email })
    });
    return this.handleResponse(response);
  }

  async getContacts(options = {}) {
    const params = new URLSearchParams();
    if (options.page_size) params.append('page_size', options.page_size);
    if (options.page_token) params.append('page_token', options.page_token);

    const response = await fetch(`${this.baseUrl}/marketing/contacts?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async addContacts(contacts) {
    const requestBody = {
      contacts: Array.isArray(contacts) ? contacts : [contacts]
    };

    const response = await fetch(`${this.baseUrl}/marketing/contacts`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getLists() {
    const response = await fetch(`${this.baseUrl}/marketing/lists`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createList(name) {
    const requestBody = {
      name: name
    };

    const response = await fetch(`${this.baseUrl}/marketing/lists`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  // Utility method to create HTML content
  createHtmlContent(html) {
    return {
      type: 'text/html',
      value: html
    };
  }

  // Utility method to create attachment
  createAttachment(content, filename, type = 'application/pdf', disposition = 'attachment') {
    return {
      content: content, // Base64 encoded
      filename: filename,
      type: type,
      disposition: disposition
    };
  }

  getHeaders() {
    return {
      'Authorization': `Bearer ${this.apiKey}`,
      'Content-Type': 'application/json'
    };
  }

  async handleResponse(response) {
    if (response.status === 202 || response.status === 204) {
      return { success: true, status: response.status };
    }

    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.errors?.[0]?.message || `SendGrid API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new SendGridAPI();
