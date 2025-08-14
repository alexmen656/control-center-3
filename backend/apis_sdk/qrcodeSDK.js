// QR Code Generator API SDK
class QRCodeAPI {
  constructor() {
    this.baseUrl = 'https://api.qrserver.com/v1/create-qr-code';
    this.chartUrl = 'https://chart.googleapis.com/chart';
  }

  async generateQRCode(data, options = {}) {
    const params = new URLSearchParams({
      data: data,
      size: options.size || '200x200',
      color: options.color || '000000',
      bgcolor: options.bgcolor || 'ffffff',
      format: options.format || 'png',
      margin: options.margin || '0',
      qzone: options.qzone || '1',
      ecc: options.ecc || 'L' // Error correction level: L, M, Q, H
    });

    const url = `${this.baseUrl}/?${params}`;
    
    if (options.returnUrl) {
      return { url: url, data: data, options: options };
    }

    const response = await fetch(url, {
      method: 'GET'
    });

    if (!response.ok) {
      throw new Error(`QR Code API Error: ${response.status} ${response.statusText}`);
    }

    return {
      url: url,
      blob: await response.blob(),
      data: data,
      options: options
    };
  }

  async generateVCard(contact, options = {}) {
    const vcard = this.createVCard(contact);
    return this.generateQRCode(vcard, {
      ...options,
      size: options.size || '300x300'
    });
  }

  async generateWiFiQR(ssid, password, security = 'WPA', hidden = false, options = {}) {
    const wifiString = `WIFI:T:${security};S:${ssid};P:${password};H:${hidden ? 'true' : 'false'};;`;
    return this.generateQRCode(wifiString, {
      ...options,
      size: options.size || '250x250'
    });
  }

  async generateSMSQR(phoneNumber, message = '', options = {}) {
    const smsString = `SMS:${phoneNumber}:${message}`;
    return this.generateQRCode(smsString, options);
  }

  async generateEmailQR(email, subject = '', body = '', options = {}) {
    const emailString = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    return this.generateQRCode(emailString, options);
  }

  async generatePhoneQR(phoneNumber, options = {}) {
    const phoneString = `tel:${phoneNumber}`;
    return this.generateQRCode(phoneString, options);
  }

  async generateLocationQR(latitude, longitude, options = {}) {
    const locationString = `geo:${latitude},${longitude}`;
    return this.generateQRCode(locationString, options);
  }

  async generateCalendarEventQR(event, options = {}) {
    const eventString = this.createCalendarEvent(event);
    return this.generateQRCode(eventString, {
      ...options,
      size: options.size || '300x300'
    });
  }

  async generateGoogleChartsQR(data, options = {}) {
    const params = new URLSearchParams({
      cht: 'qr',
      chs: options.size || '200x200',
      chl: data,
      choe: options.encoding || 'UTF-8',
      chld: `${options.ecc || 'L'}|${options.margin || '0'}`
    });

    const url = `${this.chartUrl}?${params}`;
    
    if (options.returnUrl) {
      return { url: url, data: data, options: options };
    }

    const response = await fetch(url, {
      method: 'GET'
    });

    if (!response.ok) {
      throw new Error(`Google Charts QR API Error: ${response.status} ${response.statusText}`);
    }

    return {
      url: url,
      blob: await response.blob(),
      data: data,
      options: options
    };
  }

  async generateBatchQRCodes(dataArray, options = {}) {
    const results = [];
    
    for (const data of dataArray) {
      try {
        const qr = await this.generateQRCode(data, {
          ...options,
          returnUrl: true
        });
        results.push({
          success: true,
          data: data,
          qr: qr
        });
      } catch (error) {
        results.push({
          success: false,
          data: data,
          error: error.message
        });
      }
    }

    return results;
  }

  // Utility methods
  createVCard(contact) {
    let vcard = 'BEGIN:VCARD\nVERSION:3.0\n';
    
    if (contact.name) {
      vcard += `FN:${contact.name}\n`;
    }
    if (contact.phone) {
      vcard += `TEL:${contact.phone}\n`;
    }
    if (contact.email) {
      vcard += `EMAIL:${contact.email}\n`;
    }
    if (contact.organization) {
      vcard += `ORG:${contact.organization}\n`;
    }
    if (contact.title) {
      vcard += `TITLE:${contact.title}\n`;
    }
    if (contact.url) {
      vcard += `URL:${contact.url}\n`;
    }
    if (contact.address) {
      vcard += `ADR:;;${contact.address}\n`;
    }
    
    vcard += 'END:VCARD';
    return vcard;
  }

  createCalendarEvent(event) {
    let ical = 'BEGIN:VEVENT\n';
    
    if (event.title) {
      ical += `SUMMARY:${event.title}\n`;
    }
    if (event.description) {
      ical += `DESCRIPTION:${event.description}\n`;
    }
    if (event.location) {
      ical += `LOCATION:${event.location}\n`;
    }
    if (event.startDate) {
      ical += `DTSTART:${this.formatCalendarDate(event.startDate)}\n`;
    }
    if (event.endDate) {
      ical += `DTEND:${this.formatCalendarDate(event.endDate)}\n`;
    }
    
    ical += 'END:VEVENT';
    return ical;
  }

  formatCalendarDate(date) {
    // Format: YYYYMMDDTHHMMSSZ
    const d = new Date(date);
    return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
  }

  getSupportedFormats() {
    return ['png', 'gif', 'jpeg', 'jpg', 'svg'];
  }

  getSupportedSizes() {
    return ['100x100', '150x150', '200x200', '250x250', '300x300', '400x400', '500x500'];
  }

  getErrorCorrectionLevels() {
    return {
      'L': 'Low (~7%)',
      'M': 'Medium (~15%)',
      'Q': 'Quartile (~25%)', 
      'H': 'High (~30%)'
    };
  }

  validateData(data) {
    if (!data || data.length === 0) {
      throw new Error('Data cannot be empty');
    }
    
    if (data.length > 4296) {
      throw new Error('Data too long for QR code (max 4296 characters)');
    }
    
    return true;
  }

  async downloadQRCode(qrResult, filename = 'qrcode') {
    if (qrResult.blob) {
      const url = URL.createObjectURL(qrResult.blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${filename}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      return true;
    }
    return false;
  }
}

export default new QRCodeAPI();
