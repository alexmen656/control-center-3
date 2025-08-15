---
sidebar_position: 1
---

# API Documentation

Welcome to the comprehensive API documentation for our CMS platform. This documentation covers both internal CMS APIs and external service integrations.

## API Categories

### 🏠 Internal CMS APIs
These APIs are provided by the CMS platform and require authentication with your project API key.

- **[User Management API](./internal/user-management)** - Authentication and user operations
- **[File Storage API](./internal/file-storage)** - File upload and management
- **[Database API](./internal/database)** - Database operations and queries
- **[Notification API](./internal/notifications)** - Push notifications and alerts
- **[Analytics API](./internal/analytics)** - Event tracking and reporting

### 🤖 AI & Machine Learning
- **[OpenAI API](./external/openai)** - ChatGPT, DALL-E, Whisper integrations
- **[Gemini API](./external/gemini)** - Google AI text and vision generation

### 👨‍💻 Development Tools
- **[GitHub API](./external/github)** - Repository management and operations

### 💬 Communication
- **[Telegram Bot API](./external/telegram)** - Bot messaging and webhooks
- **[Discord API](./external/discord)** - Discord bot and webhook messaging
- **[SendGrid API](./external/sendgrid)** - Email delivery and marketing

### 💳 Payment Processing
- **[Stripe API](./external/stripe)** - Payment processing and subscriptions

### 📊 Data Services
- **[Weather API](./external/weather)** - Weather data and forecasts
- **[News API](./external/news)** - News headlines and articles
- **[Currency API](./external/currency)** - Exchange rates and conversion
- **[Geolocation API](./external/geolocation)** - Location services and maps

### 🛠️ Utility Services
- **[QR Code API](./external/qrcode)** - QR code generation

## Getting Started

### Authentication

#### Internal APIs
Internal CMS APIs require authentication using your project API key:

```javascript
const headers = {
  'Authorization': `Bearer YOUR_PROJECT_API_KEY`,
  'Content-Type': 'application/json'
};
```

#### External APIs
External APIs use their respective SDK classes with environment variables for API keys. Make sure to set the appropriate environment variables in your project.

### SDK Usage

All APIs are available as SDK classes that can be imported and used directly:

```javascript
// Internal APIs
import databaseSDK from './backend/apis_sdk/databaseSDK.js';

// External APIs
import openaiSDK from './backend/apis_sdk/openaiSDK.js';
import weatherSDK from './backend/apis_sdk/weatherSDK.js';

// Usage example
const data = await databaseSDK.query('users', { active: true });
const weather = await weatherSDK.getCurrentWeather('Berlin');
```

### Error Handling

All SDKs follow a consistent error handling pattern:

```javascript
try {
  const result = await apiSDK.someMethod();
  console.log('Success:', result);
} catch (error) {
  console.error('API Error:', error.message);
}
```

## Rate Limits

- **Internal APIs**: Default 100 requests per minute per project
- **External APIs**: Vary by provider (see individual API documentation)

## Support

For questions about these APIs, please refer to the individual documentation pages or contact our support team.
