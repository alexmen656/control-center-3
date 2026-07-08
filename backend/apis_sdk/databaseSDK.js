class DatabaseClient {
  constructor() {
    this.baseUrl = process.env.DATABASE_API_URL || 'http://172.31.241.1:8088/api/v1/database.php';
    this.apiKey = process.env['[{[apiKey]}]'] || '';
  }

  async listTables() {
    return this._request('GET', 'tables');
  }

  async query(table, where = {}, options = {}) {
    return this._request('POST', 'query', { table, where, options });
  }

  async get(table, id) {
    const res = await this.query(table, { id }, { limit: 1 });
    return res.records && res.records.length ? res.records[0] : null;
  }

  async count(table, where = {}) {
    const res = await this._request('POST', 'count', { table, where });
    return res.count;
  }

  async insert(table, data) {
    return this._request('POST', 'insert', { table, data });
  }

  async update(table, target, data) {
    const body = { table, data };
    if (target !== null && typeof target === 'object') {
      body.where = target;
    } else {
      body.id = target;
    }
    return this._request('PUT', 'update', body);
  }

  async delete(table, target) {
    const body = { table };
    if (target !== null && typeof target === 'object') {
      body.where = target;
    } else {
      body.id = target;
    }
    return this._request('DELETE', 'delete', body);
  }

  async _request(method, action, body) {
    const options = { method, headers: this._headers() };
    if (body !== undefined) {
      options.body = JSON.stringify(body);
    }
    const response = await fetch(`${this.baseUrl}?action=${action}`, options);
    return this._handle(response);
  }

  _headers() {
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${this.apiKey}`
    };
  }

  async _handle(response) {
    let payload;
    try {
      payload = await response.json();
    } catch (e) {
      throw new Error(`Database API: invalid response (HTTP ${response.status})`);
    }
    if (!response.ok || !payload.success) {
      throw new Error(payload && payload.message ? payload.message : `Database API error (HTTP ${response.status})`);
    }
    return payload.data !== undefined ? payload.data : payload;
  }
}

const DatabaseAPI = new DatabaseClient();

export { DatabaseAPI };
export default DatabaseAPI;
