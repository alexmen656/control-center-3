// GitHub API SDK
class GitHubAPI {
  constructor() {
    this.baseUrl = 'https://api.github.com';
    this.apiKey = process.env.GITHUB_TOKEN || '';
  }

  async getUser(username = null) {
    const endpoint = username ? `/users/${username}` : '/user';
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getUserRepos(username = null, options = {}) {
    const endpoint = username ? `/users/${username}/repos` : '/user/repos';
    const params = new URLSearchParams({
      type: options.type || 'all',
      sort: options.sort || 'updated',
      direction: options.direction || 'desc',
      per_page: options.per_page || '30',
      page: options.page || '1'
    });

    const response = await fetch(`${this.baseUrl}${endpoint}?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getRepo(owner, repo) {
    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createRepo(name, options = {}) {
    const requestBody = {
      name: name,
      description: options.description || '',
      private: options.private || false,
      auto_init: options.auto_init || true,
      gitignore_template: options.gitignore_template || null,
      license_template: options.license_template || null
    };

    const response = await fetch(`${this.baseUrl}/user/repos`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getRepoContents(owner, repo, path = '', options = {}) {
    const params = new URLSearchParams();
    if (options.ref) params.append('ref', options.ref);

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/contents/${path}?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createOrUpdateFile(owner, repo, path, content, message, options = {}) {
    const requestBody = {
      message: message,
      content: btoa(content), // Base64 encode
      branch: options.branch || 'main'
    };

    if (options.sha) {
      requestBody.sha = options.sha;
    }

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/contents/${path}`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async deleteFile(owner, repo, path, message, sha, options = {}) {
    const requestBody = {
      message: message,
      sha: sha,
      branch: options.branch || 'main'
    };

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/contents/${path}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getCommits(owner, repo, options = {}) {
    const params = new URLSearchParams({
      sha: options.sha || 'main',
      per_page: options.per_page || '30',
      page: options.page || '1'
    });
    if (options.since) params.append('since', options.since);
    if (options.until) params.append('until', options.until);
    if (options.author) params.append('author', options.author);

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/commits?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getBranches(owner, repo, options = {}) {
    const params = new URLSearchParams({
      per_page: options.per_page || '30',
      page: options.page || '1'
    });

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/branches?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createBranch(owner, repo, branchName, fromBranch = 'main') {
    // First get the SHA of the source branch
    const refResponse = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/git/refs/heads/${fromBranch}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    const refData = await this.handleResponse(refResponse);

    // Create new branch
    const requestBody = {
      ref: `refs/heads/${branchName}`,
      sha: refData.object.sha
    };

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/git/refs`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getIssues(owner, repo, options = {}) {
    const params = new URLSearchParams({
      state: options.state || 'open',
      sort: options.sort || 'created',
      direction: options.direction || 'desc',
      per_page: options.per_page || '30',
      page: options.page || '1'
    });
    if (options.labels) params.append('labels', options.labels);
    if (options.assignee) params.append('assignee', options.assignee);

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/issues?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createIssue(owner, repo, title, body = '', options = {}) {
    const requestBody = {
      title: title,
      body: body,
      labels: options.labels || [],
      assignees: options.assignees || []
    };

    const response = await fetch(`${this.baseUrl}/repos/${owner}/${repo}/issues`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async searchRepositories(query, options = {}) {
    const params = new URLSearchParams({
      q: query,
      sort: options.sort || 'stars',
      order: options.order || 'desc',
      per_page: options.per_page || '30',
      page: options.page || '1'
    });

    const response = await fetch(`${this.baseUrl}/search/repositories?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Accept': 'application/vnd.github.v3+json',
      'Authorization': `token ${this.apiKey}`,
      'User-Agent': 'CMS-SDK/1.0'
    };
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `GitHub API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new GitHubAPI();
