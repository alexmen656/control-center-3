---
sidebar_position: 13
---

# Geolocation API Integration

The Geolocation SDK provides location services including IP geolocation, reverse geocoding, and location-based services.

## 🔑 API Key Setup

### Getting Your API Key
We support multiple geolocation providers. Choose the one that best fits your needs:

#### Option 1: IPGeolocation.io (Recommended)
1. Go to [IPGeolocation.io](https://ipgeolocation.io/)
2. Click **"Sign Up"** for a free account
3. Choose your plan:
   - **Free**: 1,000 requests/month
   - **Basic**: 50,000 requests/month
   - **Pro**: 300,000 requests/month
4. Copy your API key from the dashboard

#### Option 2: IPStack
1. Go to [IPStack](https://ipstack.com/)
2. Sign up for a free account
3. Plans available:
   - **Free**: 1,000 requests/month
   - **Basic**: 10,000 requests/month
   - **Professional**: 100,000 requests/month

#### Option 3: IPInfo
1. Go to [IPInfo.io](https://ipinfo.io/)
2. Sign up for free
3. Plans available:
   - **Free**: 50,000 requests/month
   - **Basic**: 250,000 requests/month

### Environment Setup
Add your API key to your environment variables:

```bash
# Choose your provider
IPGEOLOCATION_API_KEY=your-ipgeolocation-key-here
# OR
IPSTACK_API_KEY=your-ipstack-key-here
# OR
IPINFO_API_KEY=your-ipinfo-key-here

# Optional: Set default provider
GEOLOCATION_PROVIDER=ipgeolocation
```

## 📖 SDK Usage

```javascript
import geolocationSDK from './backend/apis_sdk/geolocationSDK.js';
```

## 🚀 Available Methods

### IP Geolocation

```javascript
// Get current user's location (auto-detect IP)
const location = await geolocationSDK.getCurrentLocation();
console.log('Current location:', location);

// Get location for specific IP
const ipLocation = await geolocationSDK.getLocationByIP('8.8.8.8');
console.log('Google DNS location:', ipLocation);

// Bulk IP lookup
const bulkLocations = await geolocationSDK.getBulkLocations([
  '8.8.8.8',
  '1.1.1.1',
  '208.67.222.222'
]);

// Get location with additional details
const detailedLocation = await geolocationSDK.getLocationByIP('8.8.8.8', {
  fields: 'geo,time_zone,isp,threat',
  includeHostname: true
});
```

### Coordinate-based Operations

```javascript
// Reverse geocoding (coordinates to address)
const address = await geolocationSDK.reverseGeocode(40.7128, -74.0060);
console.log('Address:', address.display_name); // New York City

// Get timezone for coordinates
const timezone = await geolocationSDK.getTimezone(40.7128, -74.0060);
console.log('Timezone:', timezone.name); // America/New_York

// Calculate distance between two points
const distance = await geolocationSDK.calculateDistance(
  40.7128, -74.0060, // New York
  34.0522, -118.2437  // Los Angeles
);
console.log('Distance:', distance.km, 'km');

// Get nearby places
const nearbyPlaces = await geolocationSDK.getNearbyPlaces(
  40.7128, -74.0060,
  {
    radius: 1000, // 1km
    type: 'restaurant'
  }
);
```

### Address and Place Search

```javascript
// Forward geocoding (address to coordinates)
const coordinates = await geolocationSDK.geocodeAddress('1600 Amphitheatre Parkway, Mountain View, CA');
console.log('Coordinates:', coordinates.lat, coordinates.lng);

// Search for places
const places = await geolocationSDK.searchPlaces('coffee shops near times square');

// Get place details
const placeDetails = await geolocationSDK.getPlaceDetails('ChIJN1t_tDeuEmsRUsoyG83frY4');

// Autocomplete places
const suggestions = await geolocationSDK.autocompletePlaces('statue of lib');
```

### ISP and Network Information

```javascript
// Get ISP information
const ispInfo = await geolocationSDK.getISPInfo('8.8.8.8');
console.log('ISP:', ispInfo.isp);
console.log('Organization:', ispInfo.organization);
console.log('ASN:', ispInfo.asn);

// Check if IP is VPN/Proxy
const threatInfo = await geolocationSDK.getThreatInfo('192.168.1.1');
console.log('Is VPN:', threatInfo.is_vpn);
console.log('Is Proxy:', threatInfo.is_proxy);
console.log('Is Tor:', threatInfo.is_tor);
```

### Multiple Provider Support

```javascript
// Use specific provider
const locationIPGeo = await geolocationSDK.getCurrentLocation({ provider: 'ipgeolocation' });
const locationIPStack = await geolocationSDK.getCurrentLocation({ provider: 'ipstack' });
const locationIPInfo = await geolocationSDK.getCurrentLocation({ provider: 'ipinfo' });

// Compare results from multiple providers
const comparison = await geolocationSDK.compareProviders('8.8.8.8');
console.log('Provider comparison:', comparison);
```

## 🎯 Advanced Features

### Location Analytics

```javascript
async function analyzeUserLocations(ipAddresses) {
  const locations = await geolocationSDK.getBulkLocations(ipAddresses);
  
  // Analyze by country
  const countryStats = {};
  locations.forEach(location => {
    const country = location.country_name;
    countryStats[country] = (countryStats[country] || 0) + 1;
  });

  // Analyze by timezone
  const timezoneStats = {};
  locations.forEach(location => {
    const tz = location.time_zone?.name;
    if (tz) {
      timezoneStats[tz] = (timezoneStats[tz] || 0) + 1;
    }
  });

  // Analyze by ISP
  const ispStats = {};
  locations.forEach(location => {
    const isp = location.isp;
    if (isp) {
      ispStats[isp] = (ispStats[isp] || 0) + 1;
    }
  });

  return {
    totalIPs: ipAddresses.length,
    countries: Object.entries(countryStats)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10),
    timezones: Object.entries(timezoneStats)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10),
    isps: Object.entries(ispStats)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10)
  };
}
```

### Geofencing

```javascript
class GeofencingService {
  constructor() {
    this.geofences = [];
  }

  addGeofence(id, centerLat, centerLng, radiusKm, name) {
    this.geofences.push({
      id,
      center: { lat: centerLat, lng: centerLng },
      radius: radiusKm,
      name
    });
  }

  async checkGeofences(ip) {
    const location = await geolocationSDK.getLocationByIP(ip);
    const results = [];

    for (const geofence of this.geofences) {
      const distance = await geolocationSDK.calculateDistance(
        location.latitude,
        location.longitude,
        geofence.center.lat,
        geofence.center.lng
      );

      const isInside = distance.km <= geofence.radius;
      results.push({
        geofenceId: geofence.id,
        geofenceName: geofence.name,
        isInside,
        distance: distance.km
      });
    }

    return {
      ip,
      location,
      geofenceResults: results
    };
  }
}

// Usage
const geofencing = new GeofencingService();
geofencing.addGeofence('nyc', 40.7128, -74.0060, 50, 'New York City Area');
geofencing.addGeofence('sf', 37.7749, -122.4194, 30, 'San Francisco Area');

const result = await geofencing.checkGeofences('8.8.8.8');
```

### Location-based Content Delivery

```javascript
async function getLocationBasedContent(ip) {
  const location = await geolocationSDK.getLocationByIP(ip);
  
  // Determine content based on location
  const content = {
    language: getLanguageByCountry(location.country_code),
    currency: getCurrencyByCountry(location.country_code),
    timezone: location.time_zone?.name,
    localTime: new Date().toLocaleString('en-US', {
      timeZone: location.time_zone?.name
    }),
    region: location.continent_name,
    country: location.country_name,
    city: location.city
  };

  // Add region-specific features
  if (location.country_code === 'US') {
    content.features = ['express_shipping', 'local_support'];
  } else if (location.continent_code === 'EU') {
    content.features = ['gdpr_compliant', 'eu_support'];
    content.showCookieBanner = true;
  }

  return content;
}

function getLanguageByCountry(countryCode) {
  const languageMap = {
    'US': 'en',
    'GB': 'en',
    'FR': 'fr',
    'DE': 'de',
    'ES': 'es',
    'IT': 'it',
    'JP': 'ja',
    'CN': 'zh'
  };
  return languageMap[countryCode] || 'en';
}

function getCurrencyByCountry(countryCode) {
  const currencyMap = {
    'US': 'USD',
    'GB': 'GBP',
    'FR': 'EUR',
    'DE': 'EUR',
    'ES': 'EUR',
    'IT': 'EUR',
    'JP': 'JPY',
    'CN': 'CNY'
  };
  return currencyMap[countryCode] || 'USD';
}
```

### Fraud Detection

```javascript
async function detectSuspiciousLocation(ip, userProfile) {
  const location = await geolocationSDK.getLocationByIP(ip);
  const threatInfo = await geolocationSDK.getThreatInfo(ip);
  
  const riskFactors = [];
  let riskScore = 0;

  // Check for VPN/Proxy usage
  if (threatInfo.is_vpn || threatInfo.is_proxy) {
    riskFactors.push('VPN/Proxy detected');
    riskScore += 30;
  }

  // Check for Tor usage
  if (threatInfo.is_tor) {
    riskFactors.push('Tor network detected');
    riskScore += 50;
  }

  // Check for unusual location
  if (userProfile.usualCountry && location.country_code !== userProfile.usualCountry) {
    riskFactors.push('Unusual country');
    riskScore += 20;
  }

  // Check for high-risk countries (example list)
  const highRiskCountries = ['CN', 'RU', 'KP']; // Example
  if (highRiskCountries.includes(location.country_code)) {
    riskFactors.push('High-risk country');
    riskScore += 25;
  }

  // Check time zone vs usual activity
  const currentHour = new Date().getHours();
  const localTime = new Date().toLocaleString('en-US', {
    timeZone: location.time_zone?.name,
    hour: 'numeric',
    hour12: false
  });
  
  if (parseInt(localTime) < 6 || parseInt(localTime) > 23) {
    riskFactors.push('Unusual time of access');
    riskScore += 15;
  }

  return {
    ip,
    location,
    riskScore,
    riskLevel: riskScore > 50 ? 'HIGH' : riskScore > 25 ? 'MEDIUM' : 'LOW',
    riskFactors,
    recommendation: riskScore > 50 ? 'BLOCK' : riskScore > 25 ? 'CHALLENGE' : 'ALLOW'
  };
}
```

## 🎨 Use Cases

### User Registration Enhancement

```javascript
async function enhanceUserRegistration(ip, formData) {
  const location = await geolocationSDK.getLocationByIP(ip);
  
  // Pre-fill form fields
  const enhancements = {
    suggestedCountry: location.country_name,
    suggestedTimezone: location.time_zone?.name,
    suggestedCurrency: getCurrencyByCountry(location.country_code),
    suggestedLanguage: getLanguageByCountry(location.country_code)
  };

  // Add location-specific validation
  if (location.country_code === 'US' && !formData.zipCode) {
    enhancements.requiredFields = ['zipCode'];
  }

  // Add compliance requirements
  if (location.continent_code === 'EU') {
    enhancements.gdprConsent = true;
  }

  return {
    ...formData,
    ...enhancements,
    detectedLocation: location
  };
}
```

### Dynamic Pricing

```javascript
async function getDynamicPricing(ip, basePrice) {
  const location = await geolocationSDK.getLocationByIP(ip);
  
  // Price adjustments based on location
  const priceMultipliers = {
    'US': 1.0,
    'GB': 1.1,
    'DE': 1.05,
    'IN': 0.3,
    'BR': 0.4,
    'CN': 0.5
  };

  const multiplier = priceMultipliers[location.country_code] || 1.0;
  const adjustedPrice = basePrice * multiplier;
  
  return {
    basePrice,
    adjustedPrice,
    currency: getCurrencyByCountry(location.country_code),
    location: location.country_name,
    multiplier
  };
}
```

### Content Localization

```javascript
async function getLocalizedContent(ip, contentKey) {
  const location = await geolocationSDK.getLocationByIP(ip);
  const language = getLanguageByCountry(location.country_code);
  
  // Load localized content
  const content = await loadContent(contentKey, language);
  
  // Add location-specific elements
  const localizedContent = {
    ...content,
    localTime: new Date().toLocaleString(language, {
      timeZone: location.time_zone?.name
    }),
    weather: await getLocalWeather(location.latitude, location.longitude),
    nearbyStores: await getNearbyStores(location.latitude, location.longitude),
    shippingInfo: await getShippingInfo(location.country_code),
    legalNotices: await getLegalNotices(location.country_code)
  };

  return localizedContent;
}
```

### Analytics Dashboard

```javascript
async function generateLocationAnalytics(ipLogs, timeRange) {
  const locations = await geolocationSDK.getBulkLocations(
    ipLogs.map(log => log.ip)
  );

  // Create analytics report
  const analytics = {
    timeRange,
    totalVisitors: ipLogs.length,
    uniqueCountries: new Set(locations.map(l => l.country_code)).size,
    topCountries: getTopCountries(locations),
    topCities: getTopCities(locations),
    continentDistribution: getContinentDistribution(locations),
    timezoneDistribution: getTimezoneDistribution(locations),
    mobileVsDesktop: await analyzeMobileUsage(ipLogs),
    suspiciousActivity: await detectSuspiciousActivity(ipLogs)
  };

  return analytics;
}

function getTopCountries(locations) {
  const countryCount = {};
  locations.forEach(location => {
    const country = location.country_name;
    countryCount[country] = (countryCount[country] || 0) + 1;
  });
  
  return Object.entries(countryCount)
    .sort(([,a], [,b]) => b - a)
    .slice(0, 10)
    .map(([country, count]) => ({ country, count }));
}
```

## 📊 Response Data Structure

### Basic Location Response

```javascript
{
  "ip": "8.8.8.8",
  "continent_code": "NA",
  "continent_name": "North America",
  "country_code2": "US",
  "country_code3": "USA",
  "country_name": "United States",
  "country_capital": "Washington, D.C.",
  "state_prov": "California",
  "district": "Santa Clara County",
  "city": "Mountain View",
  "zipcode": "94043",
  "latitude": "37.42301",
  "longitude": "-122.083352",
  "is_eu": false,
  "calling_code": "+1",
  "country_tld": ".us",
  "languages": "en-US,es-US,haw,fr",
  "country_flag": "https://ipgeolocation.io/static/flags/us_64.png",
  "geoname_id": "5375480",
  "isp": "Google LLC",
  "connection_type": "",
  "organization": "Google LLC",
  "asn": "AS15169",
  "currency": {
    "code": "USD",
    "name": "US Dollar",
    "symbol": "$"
  },
  "time_zone": {
    "name": "America/Los_Angeles",
    "offset": -8,
    "current_time": "2024-01-15 10:30:45.123-0800",
    "current_time_unix": 1705344645.123,
    "is_dst": false,
    "dst_savings": 1
  }
}
```

## 📊 Rate Limits

### Provider Comparison

| Provider | Free Tier | Paid Plans | Rate Limits |
|----------|-----------|------------|-------------|
| IPGeolocation.io | 1,000/month | 50K-5M/month | 1,000/day |
| IPStack | 1,000/month | 10K-2M/month | Standard |
| IPInfo | 50,000/month | 250K+/month | 50K/month |

### Best Practices
- Cache location data for repeat IPs
- Use bulk lookup when possible
- Implement fallback providers
- Monitor usage to avoid limits

## ⚠️ Error Handling

```javascript
try {
  const location = await geolocationSDK.getLocationByIP('invalid-ip');
} catch (error) {
  if (error.message.includes('Invalid IP')) {
    console.log('IP address format is invalid');
  } else if (error.message.includes('rate limit')) {
    console.log('API rate limit exceeded');
  } else if (error.message.includes('API key')) {
    console.log('Invalid or missing API key');
  } else if (error.message.includes('quota')) {
    console.log('Monthly quota exceeded');
  } else {
    console.log('Geolocation API error:', error.message);
  }
}
```

## 🔐 Privacy & Compliance

### GDPR Compliance
- Always inform users about location tracking
- Provide opt-out mechanisms
- Don't store location data longer than necessary
- Implement data deletion requests

### Best Practices
- Use HTTPS for all API calls
- Minimize data collection
- Implement proper access controls
- Regular security audits

## 🔗 Useful Links

- [IPGeolocation.io](https://ipgeolocation.io/)
- [IPStack](https://ipstack.com/)
- [IPInfo.io](https://ipinfo.io/)
- [IP Geolocation Best Practices](https://developers.google.com/maps/documentation/geolocation/best-practices)
- [Privacy Guidelines](https://www.privacypolicies.com/blog/privacy-policy-geolocation/)
