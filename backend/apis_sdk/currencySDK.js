// Currency Exchange API SDK
class CurrencyAPI {
  constructor() {
    this.baseUrl = 'https://api.exchangerate-api.com/v4';
    this.freebaseUrl = 'https://api.fxapi.com/v1'; // Alternative free API
    this.apiKey = process.env.CURRENCY_API_KEY || '';
  }

  async getLatestRates(baseCurrency = 'USD') {
    const response = await fetch(`${this.baseUrl}/latest/${baseCurrency.toUpperCase()}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async convertCurrency(from, to, amount = 1) {
    const rates = await this.getLatestRates(from);
    const rate = rates.rates[to.toUpperCase()];
    
    if (!rate) {
      throw new Error(`Currency ${to} not found`);
    }

    return {
      from: from.toUpperCase(),
      to: to.toUpperCase(),
      amount: amount,
      converted: amount * rate,
      rate: rate,
      date: rates.date
    };
  }

  async getHistoricalRates(baseCurrency, date) {
    // Format: YYYY-MM-DD
    const response = await fetch(`${this.baseUrl}/historical/${date}?base=${baseCurrency.toUpperCase()}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getSupportedCurrencies() {
    const response = await fetch(`${this.baseUrl}/latest/USD`, {
      method: 'GET'
    });
    const data = await this.handleResponse(response);
    
    return {
      base: data.base,
      currencies: Object.keys(data.rates),
      count: Object.keys(data.rates).length,
      date: data.date
    };
  }

  async getTimeSeries(baseCurrency, symbols, startDate, endDate) {
    // This would require a premium API, but we can simulate with multiple historical calls
    const currencies = Array.isArray(symbols) ? symbols : [symbols];
    const start = new Date(startDate);
    const end = new Date(endDate);
    const series = {};

    // Limit to avoid too many API calls
    const daysDiff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    if (daysDiff > 30) {
      throw new Error('Date range too large. Maximum 30 days for time series.');
    }

    const dates = [];
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      dates.push(new Date(d).toISOString().split('T')[0]);
    }

    for (const date of dates) {
      try {
        const historicalData = await this.getHistoricalRates(baseCurrency, date);
        series[date] = {};
        for (const currency of currencies) {
          series[date][currency] = historicalData.rates[currency.toUpperCase()];
        }
      } catch (error) {
        // Skip weekends/holidays where data might not be available
        continue;
      }
    }

    return {
      base: baseCurrency.toUpperCase(),
      symbols: currencies,
      timeseries: series
    };
  }

  async getCurrencyInfo(currencyCode) {
    // Static currency information
    const currencyData = {
      'USD': { name: 'US Dollar', symbol: '$', country: 'United States' },
      'EUR': { name: 'Euro', symbol: '€', country: 'European Union' },
      'GBP': { name: 'British Pound', symbol: '£', country: 'United Kingdom' },
      'JPY': { name: 'Japanese Yen', symbol: '¥', country: 'Japan' },
      'AUD': { name: 'Australian Dollar', symbol: 'A$', country: 'Australia' },
      'CAD': { name: 'Canadian Dollar', symbol: 'C$', country: 'Canada' },
      'CHF': { name: 'Swiss Franc', symbol: 'CHF', country: 'Switzerland' },
      'CNY': { name: 'Chinese Yuan', symbol: '¥', country: 'China' },
      'SEK': { name: 'Swedish Krona', symbol: 'kr', country: 'Sweden' },
      'NZD': { name: 'New Zealand Dollar', symbol: 'NZ$', country: 'New Zealand' }
    };

    const code = currencyCode.toUpperCase();
    return currencyData[code] || { 
      name: `Unknown Currency (${code})`, 
      symbol: code, 
      country: 'Unknown' 
    };
  }

  async getMultipleRates(baseCurrency, targetCurrencies) {
    const rates = await this.getLatestRates(baseCurrency);
    const result = {
      base: baseCurrency.toUpperCase(),
      date: rates.date,
      rates: {}
    };

    for (const currency of targetCurrencies) {
      const code = currency.toUpperCase();
      result.rates[code] = rates.rates[code] || null;
    }

    return result;
  }

  async getCryptoRates() {
    // Using a free crypto API
    const response = await fetch('https://api.coinbase.com/v2/exchange-rates?currency=USD', {
      method: 'GET'
    });
    
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`Crypto API Error: ${response.status}`);
    }

    return {
      base: 'USD',
      date: new Date().toISOString(),
      crypto_rates: data.data.rates
    };
  }

  // Utility methods
  formatCurrency(amount, currencyCode, locale = 'en-US') {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currencyCode.toUpperCase()
    }).format(amount);
  }

  getPopularCurrencies() {
    return ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD'];
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `Currency API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new CurrencyAPI();
