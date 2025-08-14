// Weather API SDK (OpenWeatherMap)
class WeatherAPI {
  constructor() {
    this.baseUrl = 'https://api.openweathermap.org/data/2.5';
    this.geoUrl = 'https://api.openweathermap.org/geo/1.0';
    this.apiKey = process.env.OPENWEATHER_API_KEY || '';
  }

  async getCurrentWeather(location, options = {}) {
    let url;
    if (typeof location === 'string') {
      // Location by city name
      url = `${this.baseUrl}/weather?q=${encodeURIComponent(location)}`;
    } else if (location.lat && location.lon) {
      // Location by coordinates
      url = `${this.baseUrl}/weather?lat=${location.lat}&lon=${location.lon}`;
    } else {
      throw new Error('Location must be a city name or {lat, lon} object');
    }

    const params = new URLSearchParams({
      appid: this.apiKey,
      units: options.units || 'metric',
      lang: options.lang || 'en'
    });

    const response = await fetch(`${url}&${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getForecast(location, options = {}) {
    let url;
    if (typeof location === 'string') {
      url = `${this.baseUrl}/forecast?q=${encodeURIComponent(location)}`;
    } else if (location.lat && location.lon) {
      url = `${this.baseUrl}/forecast?lat=${location.lat}&lon=${location.lon}`;
    } else {
      throw new Error('Location must be a city name or {lat, lon} object');
    }

    const params = new URLSearchParams({
      appid: this.apiKey,
      units: options.units || 'metric',
      lang: options.lang || 'en',
      cnt: options.cnt || '40' // Number of timestamps (max 40 for 5 days)
    });

    const response = await fetch(`${url}&${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getWeatherAlerts(lat, lon, options = {}) {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      appid: this.apiKey,
      exclude: options.exclude || 'minutely,daily'
    });

    const response = await fetch(`${this.baseUrl}/onecall?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getHistoricalWeather(lat, lon, timestamp, options = {}) {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      dt: timestamp.toString(),
      appid: this.apiKey,
      units: options.units || 'metric',
      lang: options.lang || 'en'
    });

    const response = await fetch(`${this.baseUrl}/onecall/timemachine?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async geocoding(cityName, options = {}) {
    const params = new URLSearchParams({
      q: cityName,
      limit: options.limit || '5',
      appid: this.apiKey
    });

    const response = await fetch(`${this.geoUrl}/direct?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async reverseGeocoding(lat, lon, options = {}) {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      limit: options.limit || '5',
      appid: this.apiKey
    });

    const response = await fetch(`${this.geoUrl}/reverse?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getAirPollution(lat, lon) {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      appid: this.apiKey
    });

    const response = await fetch(`${this.baseUrl}/air_pollution?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getUVIndex(lat, lon) {
    const params = new URLSearchParams({
      lat: lat.toString(),
      lon: lon.toString(),
      appid: this.apiKey
    });

    const response = await fetch(`${this.baseUrl}/uvi?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  // Utility method to get weather icon URL
  getWeatherIconUrl(iconCode, size = '2x') {
    return `https://openweathermap.org/img/wn/${iconCode}@${size}.png`;
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `Weather API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new WeatherAPI();
