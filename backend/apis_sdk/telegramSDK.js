// Telegram Bot API SDK
class TelegramAPI {
  constructor() {
    this.baseUrl = 'https://api.telegram.org/bot';
    this.botToken = process.env.TELEGRAM_BOT_TOKEN || '';
  }

  async getMe() {
    const response = await fetch(`${this.baseUrl}${this.botToken}/getMe`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async sendMessage(chatId, text, options = {}) {
    const requestBody = {
      chat_id: chatId,
      text: text,
      parse_mode: options.parse_mode || 'HTML',
      disable_web_page_preview: options.disable_web_page_preview || false,
      disable_notification: options.disable_notification || false,
      reply_to_message_id: options.reply_to_message_id || null,
      reply_markup: options.reply_markup || null
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/sendMessage`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async sendPhoto(chatId, photo, options = {}) {
    const formData = new FormData();
    formData.append('chat_id', chatId);
    
    if (typeof photo === 'string') {
      formData.append('photo', photo); // URL or file_id
    } else {
      formData.append('photo', photo); // File object
    }

    if (options.caption) formData.append('caption', options.caption);
    if (options.parse_mode) formData.append('parse_mode', options.parse_mode);
    if (options.disable_notification) formData.append('disable_notification', options.disable_notification);
    if (options.reply_to_message_id) formData.append('reply_to_message_id', options.reply_to_message_id);
    if (options.reply_markup) formData.append('reply_markup', JSON.stringify(options.reply_markup));

    const response = await fetch(`${this.baseUrl}${this.botToken}/sendPhoto`, {
      method: 'POST',
      body: formData
    });
    return this.handleResponse(response);
  }

  async sendDocument(chatId, document, options = {}) {
    const formData = new FormData();
    formData.append('chat_id', chatId);
    
    if (typeof document === 'string') {
      formData.append('document', document); // URL or file_id
    } else {
      formData.append('document', document); // File object
    }

    if (options.caption) formData.append('caption', options.caption);
    if (options.parse_mode) formData.append('parse_mode', options.parse_mode);
    if (options.disable_notification) formData.append('disable_notification', options.disable_notification);
    if (options.reply_to_message_id) formData.append('reply_to_message_id', options.reply_to_message_id);
    if (options.reply_markup) formData.append('reply_markup', JSON.stringify(options.reply_markup));

    const response = await fetch(`${this.baseUrl}${this.botToken}/sendDocument`, {
      method: 'POST',
      body: formData
    });
    return this.handleResponse(response);
  }

  async sendLocation(chatId, latitude, longitude, options = {}) {
    const requestBody = {
      chat_id: chatId,
      latitude: latitude,
      longitude: longitude,
      live_period: options.live_period || null,
      disable_notification: options.disable_notification || false,
      reply_to_message_id: options.reply_to_message_id || null,
      reply_markup: options.reply_markup || null
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/sendLocation`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getUpdates(options = {}) {
    const params = new URLSearchParams();
    if (options.offset) params.append('offset', options.offset);
    if (options.limit) params.append('limit', options.limit || '100');
    if (options.timeout) params.append('timeout', options.timeout || '0');
    if (options.allowed_updates) params.append('allowed_updates', JSON.stringify(options.allowed_updates));

    const response = await fetch(`${this.baseUrl}${this.botToken}/getUpdates?${params}`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async setWebhook(url, options = {}) {
    const requestBody = {
      url: url,
      certificate: options.certificate || null,
      ip_address: options.ip_address || null,
      max_connections: options.max_connections || 40,
      allowed_updates: options.allowed_updates || null,
      drop_pending_updates: options.drop_pending_updates || false
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/setWebhook`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async deleteWebhook(dropPendingUpdates = false) {
    const requestBody = {
      drop_pending_updates: dropPendingUpdates
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/deleteWebhook`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getWebhookInfo() {
    const response = await fetch(`${this.baseUrl}${this.botToken}/getWebhookInfo`, {
      method: 'GET'
    });
    return this.handleResponse(response);
  }

  async getChat(chatId) {
    const requestBody = {
      chat_id: chatId
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/getChat`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getChatMember(chatId, userId) {
    const requestBody = {
      chat_id: chatId,
      user_id: userId
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/getChatMember`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async kickChatMember(chatId, userId, options = {}) {
    const requestBody = {
      chat_id: chatId,
      user_id: userId,
      until_date: options.until_date || null,
      revoke_messages: options.revoke_messages || false
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/kickChatMember`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async answerCallbackQuery(callbackQueryId, options = {}) {
    const requestBody = {
      callback_query_id: callbackQueryId,
      text: options.text || null,
      show_alert: options.show_alert || false,
      url: options.url || null,
      cache_time: options.cache_time || 0
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/answerCallbackQuery`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async editMessageText(text, options = {}) {
    const requestBody = {
      text: text,
      chat_id: options.chat_id || null,
      message_id: options.message_id || null,
      inline_message_id: options.inline_message_id || null,
      parse_mode: options.parse_mode || 'HTML',
      disable_web_page_preview: options.disable_web_page_preview || false,
      reply_markup: options.reply_markup || null
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/editMessageText`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async deleteMessage(chatId, messageId) {
    const requestBody = {
      chat_id: chatId,
      message_id: messageId
    };

    const response = await fetch(`${this.baseUrl}${this.botToken}/deleteMessage`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Content-Type': 'application/json'
    };
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!data.ok) {
      throw new Error(data.description || `Telegram API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new TelegramAPI();
