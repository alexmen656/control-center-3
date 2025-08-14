// Geolocation & Maps API SDK
class GeolocationAPI {
  constructor() {
    this.nominatimUrl = 'https://nominatim.openstreetmap.org';
    this.ipApiUrl = 'http://ip-api.com/json';
    this.timezoneUrl = 'http://worldtimeapi.org/api';
  }

  async getCurrentPosition(options = {}) {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation is not supported by this browser'));
        return;
      }

      const defaultOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
      };

      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            altitudeAccuracy: position.coords.altitudeAccuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            timestamp: position.timestamp
          });
        },
        (error) => {
          reject(new Error(`Geolocation error: ${error.message}`));
        },
        { ...defaultOptions, ...options }
      );
    });
  }

  async watchPosition(callback, errorCallback, options = {}) {
    if (!navigator.geolocation) {
      throw new Error('Geolocation is not supported by this browser');
    }

    const defaultOptions = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000
    };

    return navigator.geolocation.watchPosition(
      callback,
      errorCallback,
      { ...defaultOptions, ...options }
    );
  }

  clearWatch(watchId) {
    if (navigator.geolocation) {
      navigator.geolocation.clearWatch(watchId);
    }
  }

  async geocode(address) {
    const params = new URLSearchParams({
      q: address,
      format: 'json',
      limit: '5',
      addressdetails: '1'
    });

    const response = await fetch(`${this.nominatimUrl}/search?${params}`, {
      method: 'GET',
      headers: {
        'User-Agent': 'CMS-SDK/1.0'
      }
    });

    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(`Geocoding API Error: ${response.status} ${response.statusText}`);
    }

    return data.map(result => ({
      latitude: parseFloat(result.lat),
      longitude: parseFloat(result.lon),
      display_name: result.display_name,
      address: result.address,
      importance: result.importance,
      place_id: result.place_id
    }));
  }

  async reverseGeocode(latitude, longitude) {
    const params = new URLSearchParams({
      lat: latitude.toString(),
      lon: longitude.toString(),
      format: 'json',
      addressdetails: '1'
    });

    const response = await fetch(`${this.nominatimUrl}/reverse?${params}`, {
      method: 'GET',
      headers: {
        'User-Agent': 'CMS-SDK/1.0'
      }
    });

    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(`Reverse Geocoding API Error: ${response.status} ${response.statusText}`);
    }

    return {
      latitude: parseFloat(data.lat),
      longitude: parseFloat(data.lon),
      display_name: data.display_name,
      address: data.address,
      place_id: data.place_id
    };
  }

  async getLocationByIP(ip = '') {
    const url = ip ? `${this.ipApiUrl}/${ip}` : this.ipApiUrl;
    
    const response = await fetch(url, {
      method: 'GET'
    });

    const data = await response.json();
    
    if (!response.ok || data.status === 'fail') {
      throw new Error(data.message || `IP Geolocation API Error: ${response.status}`);
    }

    return {
      ip: data.query,
      country: data.country,
      countryCode: data.countryCode,
      region: data.region,
      regionName: data.regionName,
      city: data.city,
      zip: data.zip,
      latitude: data.lat,
      longitude: data.lon,
      timezone: data.timezone,
      isp: data.isp,
      org: data.org,
      as: data.as
    };
  }

  async getTimezone(latitude, longitude) {
    const response = await fetch(`${this.timezoneUrl}/timezone/${latitude},${longitude}`, {
      method: 'GET'
    });

    if (!response.ok) {
      // Fallback to IP-based timezone
      const ipLocation = await this.getLocationByIP();
      return {
        timezone: ipLocation.timezone,
        utc_offset: null,
        datetime: new Date().toISOString(),
        fallback: true
      };
    }

    const data = await response.json();
    return {
      timezone: data.timezone,
      utc_offset: data.utc_offset,
      datetime: data.datetime,
      day_of_week: data.day_of_week,
      day_of_year: data.day_of_year,
      week_number: data.week_number,
      fallback: false
    };
  }

  async calculateDistance(lat1, lon1, lat2, lon2, unit = 'km') {
    const R = unit === 'mi' ? 3959 : 6371; // Earth's radius in miles or kilometers
    const dLat = this.toRadians(lat2 - lat1);
    const dLon = this.toRadians(lon2 - lon1);
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;

    return {
      distance: Math.round(distance * 100) / 100,
      unit: unit,
      from: { latitude: lat1, longitude: lon1 },
      to: { latitude: lat2, longitude: lon2 }
    };
  }

  async findNearbyPlaces(latitude, longitude, radius = 1000, amenity = '') {
    let query = `[out:json][timeout:25];
      (
        node["amenity"${amenity ? `="${amenity}"` : ''}](around:${radius},${latitude},${longitude});
        way["amenity"${amenity ? `="${amenity}"` : ''}](around:${radius},${latitude},${longitude});
        relation["amenity"${amenity ? `="${amenity}"` : ''}](around:${radius},${latitude},${longitude});
      );
      out center;`;

    const response = await fetch('https://overpass-api.de/api/interpreter', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `data=${encodeURIComponent(query)}`
    });

    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(`Overpass API Error: ${response.status} ${response.statusText}`);
    }

    return data.elements.map(element => ({
      id: element.id,
      type: element.type,
      latitude: element.lat || element.center?.lat,
      longitude: element.lon || element.center?.lon,
      tags: element.tags || {},
      amenity: element.tags?.amenity,
      name: element.tags?.name,
      address: this.formatAddress(element.tags)
    })).filter(place => place.latitude && place.longitude);
  }

  async getMapTileUrl(latitude, longitude, zoom = 15, size = '512x512', style = 'osm') {
    // Using different map tile providers
    const providers = {
      osm: `https://tile.openstreetmap.org/${zoom}/${this.lonToTile(longitude, zoom)}/${this.latToTile(latitude, zoom)}.png`,
      satellite: `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/${zoom}/${this.latToTile(latitude, zoom)}/${this.lonToTile(longitude, zoom)}`,
      terrain: `https://tile.opentopomap.org/${zoom}/${this.lonToTile(longitude, zoom)}/${this.latToTile(latitude, zoom)}.png`
    };

    return {
      url: providers[style] || providers.osm,
      center: { latitude, longitude },
      zoom: zoom,
      style: style
    };
  }

  // Utility methods
  toRadians(degrees) {
    return degrees * (Math.PI / 180);
  }

  toDegrees(radians) {
    return radians * (180 / Math.PI);
  }

  latToTile(lat, zoom) {
    return Math.floor((1 - Math.log(Math.tan(lat * Math.PI / 180) + 1 / Math.cos(lat * Math.PI / 180)) / Math.PI) / 2 * Math.pow(2, zoom));
  }

  lonToTile(lon, zoom) {
    return Math.floor((lon + 180) / 360 * Math.pow(2, zoom));
  }

  formatAddress(tags) {
    const parts = [];
    if (tags['addr:housenumber']) parts.push(tags['addr:housenumber']);
    if (tags['addr:street']) parts.push(tags['addr:street']);
    if (tags['addr:city']) parts.push(tags['addr:city']);
    if (tags['addr:postcode']) parts.push(tags['addr:postcode']);
    return parts.join(', ');
  }

  isValidCoordinate(latitude, longitude) {
    return latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
  }

  formatCoordinates(latitude, longitude, format = 'decimal') {
    if (format === 'dms') {
      return {
        latitude: this.toDMS(latitude, 'lat'),
        longitude: this.toDMS(longitude, 'lon')
      };
    }
    return {
      latitude: Math.round(latitude * 1000000) / 1000000,
      longitude: Math.round(longitude * 1000000) / 1000000
    };
  }

  toDMS(coordinate, type) {
    const absolute = Math.abs(coordinate);
    const degrees = Math.floor(absolute);
    const minutesNotTruncated = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesNotTruncated);
    const seconds = Math.floor((minutesNotTruncated - minutes) * 60);
    
    const direction = type === 'lat' 
      ? (coordinate >= 0 ? 'N' : 'S')
      : (coordinate >= 0 ? 'E' : 'W');
    
    return `${degrees}°${minutes}'${seconds}"${direction}`;
  }
}

export default new GeolocationAPI();
