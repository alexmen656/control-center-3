---
sidebar_position: 4
---

# Weather API Integration

The Weather SDK provides comprehensive weather data including current conditions, forecasts, historical data, and weather alerts using OpenWeatherMap API.

## 🔑 API Key Setup

### Getting Your API Key
1. Visit [OpenWeatherMap](https://openweathermap.org/api)
2. Sign up for a free account
3. Go to **API Keys** section in your account
4. Copy your API key
5. **Note**: Free tier includes 1,000 calls/day, 60 calls/minute

### Environment Setup
Add your API key to your environment variables:

```bash
OPENWEATHER_API_KEY=your-actual-api-key-here
```

## 📖 SDK Usage

```javascript
import weatherSDK from './backend/apis_sdk/weatherSDK.js';
```

## 🚀 Available Methods

### Current Weather

```javascript
// By city name
const weather = await weatherSDK.getCurrentWeather('Berlin', {
  units: 'metric', // 'metric', 'imperial', 'kelvin'
  lang: 'en'
});

console.log(`Temperature: ${weather.main.temp}°C`);
console.log(`Description: ${weather.weather[0].description}`);

// By coordinates
const weatherByCoords = await weatherSDK.getCurrentWeather({
  lat: 52.5200,
  lon: 13.4050
}, {
  units: 'metric'
});
```

### Weather Forecast

```javascript
// 5-day forecast with 3-hour intervals
const forecast = await weatherSDK.getForecast('London', {
  units: 'metric',
  cnt: 40 // Number of timestamps (max 40)
});

forecast.list.forEach(item => {
  const date = new Date(item.dt * 1000);
  console.log(`${date}: ${item.main.temp}°C - ${item.weather[0].description}`);
});
```

### Weather Alerts & Extended Data

```javascript
// One Call API (includes alerts, UV index, etc.)
const detailedWeather = await weatherSDK.getWeatherAlerts(52.5200, 13.4050, {
  exclude: 'minutely,hourly' // Exclude parts you don't need
});

if (detailedWeather.alerts) {
  detailedWeather.alerts.forEach(alert => {
    console.log(`Alert: ${alert.event} - ${alert.description}`);
  });
}
```

### Historical Weather

```javascript
// Weather data for a specific past date
const timestamp = Math.floor(Date.now() / 1000) - (24 * 60 * 60); // Yesterday
const historical = await weatherSDK.getHistoricalWeather(52.5200, 13.4050, timestamp);

console.log('Yesterday:', historical.current);
```

### Geocoding

```javascript
// Convert city name to coordinates
const locations = await weatherSDK.geocoding('Berlin, Germany');
locations.forEach(location => {
  console.log(`${location.name}: ${location.lat}, ${location.lon}`);
});

// Reverse geocoding
const cityInfo = await weatherSDK.reverseGeocoding(52.5200, 13.4050);
console.log('Location:', cityInfo[0]);
```

### Air Pollution

```javascript
const pollution = await weatherSDK.getAirPollution(52.5200, 13.4050);

console.log('Air Quality Index:', pollution.list[0].main.aqi);
console.log('Components:', pollution.list[0].components);
```

### UV Index

```javascript
const uvData = await weatherSDK.getUVIndex(52.5200, 13.4050);
console.log('UV Index:', uvData.value);
```

## 🎛️ Configuration Options

### Units
- `metric`: Celsius, m/s, mm
- `imperial`: Fahrenheit, mph, inches  
- `kelvin`: Kelvin, m/s, mm

### Language Support
```javascript
const weather = await weatherSDK.getCurrentWeather('Paris', {
  lang: 'de' // German descriptions
});
```

Supported languages: `ar`, `bg`, `ca`, `cz`, `da`, `de`, `el`, `en`, `es`, `eu`, `fa`, `fi`, `fr`, `gl`, `he`, `hi`, `hr`, `hu`, `id`, `it`, `ja`, `kr`, `la`, `lt`, `lv`, `mk`, `nl`, `no`, `pl`, `pt`, `pt_br`, `ro`, `ru`, `sk`, `sl`, `sp`, `sr`, `sv`, `th`, `tr`, `ua`, `vi`, `zh_cn`, `zh_tw`, `zu`

## 🖼️ Weather Icons

Get weather icon URLs:

```javascript
const iconCode = weather.weather[0].icon; // e.g., "01d"
const iconUrl = weatherSDK.getWeatherIconUrl(iconCode, '2x');

// Icon sizes: '1x' (50x50), '2x' (100x100), '4x' (200x200)
```

## 📊 Data Structure Examples

### Current Weather Response
```javascript
{
  "coord": { "lon": 13.4105, "lat": 52.5244 },
  "weather": [
    {
      "id": 800,
      "main": "Clear",
      "description": "clear sky",
      "icon": "01d"
    }
  ],
  "main": {
    "temp": 15.32,
    "feels_like": 14.55,
    "temp_min": 13.89,
    "temp_max": 16.67,
    "pressure": 1013,
    "humidity": 72
  },
  "wind": { "speed": 3.09, "deg": 270 },
  "visibility": 10000,
  "dt": 1605182400,
  "timezone": 3600,
  "name": "Berlin"
}
```

## 💰 Pricing Information

### Free Tier
- 1,000 API calls/day
- 60 calls/minute
- Current weather, 5-day forecast, maps

### Paid Plans
- **Startup**: $40/month - 100,000 calls/month
- **Developer**: $180/month - 500,000 calls/month
- **Professional**: $600/month - 2,000,000 calls/month

Check [OpenWeatherMap Pricing](https://openweathermap.org/price) for current rates.

## 📊 Rate Limits

- **Free**: 60 calls/minute, 1,000 calls/day
- **Paid**: Higher limits based on subscription

## ⚠️ Error Handling

```javascript
try {
  const weather = await weatherSDK.getCurrentWeather('InvalidCity');
  console.log(weather);
} catch (error) {
  if (error.message.includes('404')) {
    console.log('City not found');
  } else if (error.message.includes('401')) {
    console.log('Invalid API key');
  } else if (error.message.includes('429')) {
    console.log('Rate limit exceeded');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 🌡️ Weather Condition Codes

Common weather condition IDs:
- **200-232**: Thunderstorm
- **300-321**: Drizzle
- **500-531**: Rain
- **600-622**: Snow
- **701-781**: Atmosphere (fog, smoke, etc.)
- **800**: Clear sky
- **801-804**: Clouds

## 📍 Location Formats

The SDK accepts multiple location formats:

```javascript
// City name
await weatherSDK.getCurrentWeather('London');

// City, country
await weatherSDK.getCurrentWeather('London,UK');

// Coordinates
await weatherSDK.getCurrentWeather({ lat: 51.5074, lon: -0.1278 });
```

## 🔗 Useful Links

- [OpenWeatherMap](https://openweathermap.org)
- [API Documentation](https://openweathermap.org/api)
- [Weather Condition Codes](https://openweathermap.org/weather-conditions)
- [Pricing Plans](https://openweathermap.org/price)
- [Weather Icons](https://openweathermap.org/weather-conditions#Weather-Condition-Codes-2)
