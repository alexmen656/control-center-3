---
sidebar_position: 6
---

# QR Code Generation API

The QR Code SDK provides comprehensive QR code generation for various data types including URLs, contact cards, WiFi credentials, and more.

## 🔑 API Key Setup

### No API Key Required! 
The QR Code SDK uses free public APIs that don't require authentication:
- **QR Server API**: Free unlimited usage
- **Google Charts API**: Free for QR codes

## 📖 SDK Usage

```javascript
import qrcodeSDK from './backend/apis_sdk/qrcodeSDK.js';
```

## 🚀 Available Methods

### Basic QR Code Generation

```javascript
// Simple text/URL QR code
const qr = await qrcodeSDK.generateQRCode('https://example.com', {
  size: '300x300',
  color: '000000',    // Black
  bgcolor: 'ffffff',  // White background
  format: 'png'
});

console.log('QR Code URL:', qr.url);
// Use qr.blob for direct image data
```

### Contact Card (vCard) QR Code

```javascript
const contact = {
  name: 'John Doe',
  phone: '+1234567890',
  email: 'john@example.com',
  organization: 'Example Corp',
  title: 'CEO',
  url: 'https://johndoe.com',
  address: '123 Main St, City, Country'
};

const vCardQR = await qrcodeSDK.generateVCard(contact, {
  size: '400x400',
  color: '1a73e8'  // Blue color
});

console.log('vCard QR Code:', vCardQR.url);
```

### WiFi QR Code

```javascript
// Generate WiFi connection QR code
const wifiQR = await qrcodeSDK.generateWiFiQR(
  'MyWiFiNetwork',     // SSID
  'mypassword123',     // Password
  'WPA',               // Security: WPA, WEP, or nopass
  false,               // Hidden network
  {
    size: '250x250',
    bgcolor: 'f0f0f0'
  }
);

console.log('WiFi QR Code:', wifiQR.url);
```

### SMS QR Code

```javascript
const smsQR = await qrcodeSDK.generateSMSQR(
  '+1234567890',
  'Hello from QR code!',
  {
    size: '200x200'
  }
);
```

### Email QR Code

```javascript
const emailQR = await qrcodeSDK.generateEmailQR(
  'contact@example.com',
  'Subject Line',
  'Email body content here...',
  {
    size: '300x300',
    color: 'cc0000'  // Red
  }
);
```

### Phone Number QR Code

```javascript
const phoneQR = await qrcodeSDK.generatePhoneQR('+1234567890', {
  size: '200x200'
});
```

### Location QR Code

```javascript
// GPS coordinates QR code
const locationQR = await qrcodeSDK.generateLocationQR(
  52.5200,  // Latitude (Berlin)
  13.4050,  // Longitude
  {
    size: '250x250'
  }
);
```

### Calendar Event QR Code

```javascript
const event = {
  title: 'Important Meeting',
  description: 'Quarterly business review',
  location: 'Conference Room A',
  startDate: '2024-12-01T14:00:00Z',
  endDate: '2024-12-01T15:30:00Z'
};

const eventQR = await qrcodeSDK.generateCalendarEventQR(event, {
  size: '350x350'
});
```

### Batch QR Code Generation

```javascript
const dataArray = [
  'https://example1.com',
  'https://example2.com',
  'Contact: john@example.com'
];

const batchResults = await qrcodeSDK.generateBatchQRCodes(dataArray, {
  size: '200x200',
  returnUrl: true  // Only return URLs, not blob data
});

batchResults.forEach((result, index) => {
  if (result.success) {
    console.log(`QR ${index + 1}:`, result.qr.url);
  } else {
    console.log(`QR ${index + 1} failed:`, result.error);
  }
});
```

## 🎨 Customization Options

### Size Options
```javascript
const sizes = qrcodeSDK.getSupportedSizes();
// ['100x100', '150x150', '200x200', '250x250', '300x300', '400x400', '500x500']
```

### Format Options
```javascript
const formats = qrcodeSDK.getSupportedFormats();
// ['png', 'gif', 'jpeg', 'jpg', 'svg']
```

### Error Correction Levels
```javascript
const eccLevels = qrcodeSDK.getErrorCorrectionLevels();
// {
//   'L': 'Low (~7%)',
//   'M': 'Medium (~15%)',
//   'Q': 'Quartile (~25%)', 
//   'H': 'High (~30%)'
// }

const qr = await qrcodeSDK.generateQRCode('Important data', {
  ecc: 'H',  // High error correction
  size: '300x300'
});
```

### Color Customization
```javascript
const coloredQR = await qrcodeSDK.generateQRCode('Colored QR Code', {
  size: '300x300',
  color: 'ff6b35',    // Orange foreground
  bgcolor: '2c3e50',  // Dark blue background
  margin: '2'         // Margin around QR code
});
```

## 🛠️ Utility Methods

### Data Validation

```javascript
// Validate QR code data
try {
  qrcodeSDK.validateData('Your QR code content');
  console.log('Data is valid');
} catch (error) {
  console.log('Validation error:', error.message);
}
```

### Download QR Code

```javascript
// Download QR code (browser only)
const qr = await qrcodeSDK.generateQRCode('Download me!');
await qrcodeSDK.downloadQRCode(qr, 'my-qr-code');
```

### Alternative Google Charts API

```javascript
// Use Google Charts API instead of QR Server
const googleQR = await qrcodeSDK.generateGoogleChartsQR('Google Charts QR', {
  size: '200x200',
  encoding: 'UTF-8',
  ecc: 'L'
});
```

## 📱 QR Code Types & Use Cases

### Business Cards
```javascript
const businessCard = {
  name: 'Jane Smith',
  title: 'Marketing Director',
  organization: 'Tech Solutions Inc.',
  phone: '+1-555-0123',
  email: 'jane.smith@techsolutions.com',
  url: 'https://techsolutions.com'
};

const businessQR = await qrcodeSDK.generateVCard(businessCard);
```

### Product Information
```javascript
const productData = {
  name: 'Smart Watch Pro',
  model: 'SW-2024',
  serial: 'SW202400001',
  warranty: 'https://warranty.example.com/SW202400001'
};

const productQR = await qrcodeSDK.generateQRCode(JSON.stringify(productData));
```

### Event Check-in
```javascript
const attendeeData = {
  eventId: 'CONF2024',
  attendeeId: 'ATT-12345',
  name: 'John Doe',
  email: 'john@example.com'
};

const checkinQR = await qrcodeSDK.generateQRCode(JSON.stringify(attendeeData));
```

## 📊 Limitations & Best Practices

### Data Limits
- **Maximum capacity**: 4,296 characters for alphanumeric data
- **Recommended**: Keep under 2,000 characters for best scanning
- **URL shorteners**: Use for long URLs

### Scanning Considerations
- **Minimum size**: 21x21 modules (smallest QR code)
- **Print quality**: Use high error correction for printed codes
- **Contrast**: Ensure good contrast between foreground/background
- **Quiet zone**: Always include margin around QR code

### Performance Tips
```javascript
// For large batches, use returnUrl: true to avoid blob processing
const fastBatch = await qrcodeSDK.generateBatchQRCodes(urls, {
  returnUrl: true,
  size: '200x200'
});
```

## 🎯 Example Applications

### WiFi Guest Access
```javascript
async function createGuestWiFiQR(ssid, password) {
  const qr = await qrcodeSDK.generateWiFiQR(ssid, password, 'WPA', false, {
    size: '300x300',
    bgcolor: 'f8f9fa'
  });
  
  return qr.url;
}

const guestWiFi = await createGuestWiFiQR('Guest-WiFi', 'welcome123');
```

### Contact Sharing
```javascript
async function createContactQR(contactInfo) {
  const qr = await qrcodeSDK.generateVCard(contactInfo, {
    size: '250x250',
    color: '1976d2'
  });
  
  return qr;
}
```

### URL Shortener Integration
```javascript
async function createURLQR(longUrl, options = {}) {
  // You could integrate with a URL shortener here
  const qr = await qrcodeSDK.generateQRCode(longUrl, {
    size: '200x200',
    ecc: 'M',
    ...options
  });
  
  return qr;
}
```

## 🔗 Useful Links

- [QR Server API](https://goqr.me/api/)
- [Google Charts QR API](https://developers.google.com/chart/infographics/docs/qr_codes)
- [QR Code Specification](https://www.qrcode.com/en/about/)
- [vCard Format](https://en.wikipedia.org/wiki/VCard)
- [WiFi QR Format](https://github.com/zxing/zxing/wiki/Barcode-Contents#wi-fi-network-config-android-ios-11)
