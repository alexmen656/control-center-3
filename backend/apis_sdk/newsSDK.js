// News API SDK
class NewsAPI {
  constructor() {
    this.baseUrl = 'https://newsapi.org/v2';
    this.apiKey = process.env.NEWS_API_KEY || '';
  }

  async getTopHeadlines(options = {}) {
    const params = new URLSearchParams({
      apiKey: this.apiKey,
      country: options.country || 'us',
      category: options.category || '',
      sources: options.sources || '',
      q: options.q || '',
      pageSize: options.pageSize || '20',
      page: options.page || '1'
    });

    // Remove empty parameters
    for (let [key, value] of params.entries()) {
      if (!value) params.delete(key);
    }

    const response = await fetch(`${this.baseUrl}/top-headlines?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async searchEverything(query, options = {}) {
    const params = new URLSearchParams({
      apiKey: this.apiKey,
      q: query,
      sources: options.sources || '',
      domains: options.domains || '',
      excludeDomains: options.excludeDomains || '',
      from: options.from || '',
      to: options.to || '',
      language: options.language || 'en',
      sortBy: options.sortBy || 'publishedAt',
      pageSize: options.pageSize || '20',
      page: options.page || '1'
    });

    // Remove empty parameters
    for (let [key, value] of params.entries()) {
      if (!value) params.delete(key);
    }

    const response = await fetch(`${this.baseUrl}/everything?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getSources(options = {}) {
    const params = new URLSearchParams({
      apiKey: this.apiKey,
      category: options.category || '',
      language: options.language || '',
      country: options.country || ''
    });

    // Remove empty parameters
    for (let [key, value] of params.entries()) {
      if (!value) params.delete(key);
    }

    const response = await fetch(`${this.baseUrl}/sources?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getNewsByCategory(category, options = {}) {
    return this.getTopHeadlines({
      ...options,
      category: category
    });
  }

  async getNewsByCountry(country, options = {}) {
    return this.getTopHeadlines({
      ...options,
      country: country
    });
  }

  async getBusinessNews(options = {}) {
    return this.getNewsByCategory('business', options);
  }

  async getTechNews(options = {}) {
    return this.getNewsByCategory('technology', options);
  }

  async getSportsNews(options = {}) {
    return this.getNewsByCategory('sports', options);
  }

  async getHealthNews(options = {}) {
    return this.getNewsByCategory('health', options);
  }

  async searchByDate(query, fromDate, toDate, options = {}) {
    return this.searchEverything(query, {
      ...options,
      from: fromDate,
      to: toDate
    });
  }

  async searchByDomain(query, domains, options = {}) {
    return this.searchEverything(query, {
      ...options,
      domains: Array.isArray(domains) ? domains.join(',') : domains
    });
  }

  // Utility methods
  getAvailableCountries() {
    return ['ae', 'ar', 'at', 'au', 'be', 'bg', 'br', 'ca', 'ch', 'cn', 'co', 'cu', 'cz', 'de', 'eg', 'fr', 'gb', 'gr', 'hk', 'hu', 'id', 'ie', 'il', 'in', 'it', 'jp', 'kr', 'lt', 'lv', 'ma', 'mx', 'my', 'ng', 'nl', 'no', 'nz', 'ph', 'pl', 'pt', 'ro', 'rs', 'ru', 'sa', 'se', 'sg', 'si', 'sk', 'th', 'tr', 'tw', 'ua', 'us', 've', 'za'];
  }

  getAvailableCategories() {
    return ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'];
  }

  getAvailableLanguages() {
    return ['ar', 'de', 'en', 'es', 'fr', 'he', 'it', 'nl', 'no', 'pt', 'ru', 'sv', 'ud', 'zh'];
  }

  getSortOptions() {
    return ['relevancy', 'popularity', 'publishedAt'];
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok || data.status === 'error') {
      throw new Error(data.message || `News API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new NewsAPI();
