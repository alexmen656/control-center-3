// Discord Webhook & Bot API SDK
class DiscordAPI {
  constructor() {
    this.baseUrl = 'https://discord.com/api/v10';
    this.botToken = process.env.DISCORD_BOT_TOKEN || '';
  }

  // Webhook Methods (No authentication needed for webhooks)
  async sendWebhookMessage(webhookUrl, content, options = {}) {
    const requestBody = {
      content: content,
      username: options.username || null,
      avatar_url: options.avatar_url || null,
      tts: options.tts || false,
      embeds: options.embeds || [],
      allowed_mentions: options.allowed_mentions || null
    };

    const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async sendWebhookEmbed(webhookUrl, embed, options = {}) {
    const requestBody = {
      content: options.content || null,
      username: options.username || null,
      avatar_url: options.avatar_url || null,
      embeds: [embed]
    };

    const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  // Bot API Methods (Require bot token)
  async getGuilds() {
    const response = await fetch(`${this.baseUrl}/users/@me/guilds`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getGuild(guildId) {
    const response = await fetch(`${this.baseUrl}/guilds/${guildId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getGuildChannels(guildId) {
    const response = await fetch(`${this.baseUrl}/guilds/${guildId}/channels`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getChannel(channelId) {
    const response = await fetch(`${this.baseUrl}/channels/${channelId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async sendMessage(channelId, content, options = {}) {
    const requestBody = {
      content: content,
      tts: options.tts || false,
      embeds: options.embeds || [],
      allowed_mentions: options.allowed_mentions || null,
      message_reference: options.message_reference || null,
      components: options.components || []
    };

    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async sendEmbed(channelId, embed, options = {}) {
    const requestBody = {
      content: options.content || null,
      embeds: [embed],
      tts: options.tts || false
    };

    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getMessages(channelId, options = {}) {
    const params = new URLSearchParams();
    if (options.around) params.append('around', options.around);
    if (options.before) params.append('before', options.before);
    if (options.after) params.append('after', options.after);
    if (options.limit) params.append('limit', options.limit);

    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getMessage(channelId, messageId) {
    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages/${messageId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async editMessage(channelId, messageId, content, options = {}) {
    const requestBody = {
      content: content,
      embeds: options.embeds || [],
      allowed_mentions: options.allowed_mentions || null,
      components: options.components || []
    };

    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages/${messageId}`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async deleteMessage(channelId, messageId) {
    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages/${messageId}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async addReaction(channelId, messageId, emoji) {
    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages/${messageId}/reactions/${encodeURIComponent(emoji)}/@me`, {
      method: 'PUT',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async removeReaction(channelId, messageId, emoji, userId = '@me') {
    const response = await fetch(`${this.baseUrl}/channels/${channelId}/messages/${messageId}/reactions/${encodeURIComponent(emoji)}/${userId}`, {
      method: 'DELETE',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getGuildMembers(guildId, options = {}) {
    const params = new URLSearchParams({
      limit: options.limit || '1',
      after: options.after || '0'
    });

    const response = await fetch(`${this.baseUrl}/guilds/${guildId}/members?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getGuildMember(guildId, userId) {
    const response = await fetch(`${this.baseUrl}/guilds/${guildId}/members/${userId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createRole(guildId, name, options = {}) {
    const requestBody = {
      name: name,
      permissions: options.permissions || '0',
      color: options.color || 0,
      hoist: options.hoist || false,
      mentionable: options.mentionable || false
    };

    const response = await fetch(`${this.baseUrl}/guilds/${guildId}/roles`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(requestBody)
    });
    return this.handleResponse(response);
  }

  async getUserInfo() {
    const response = await fetch(`${this.baseUrl}/users/@me`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  // Utility method to create embeds
  createEmbed(options = {}) {
    return {
      title: options.title || null,
      description: options.description || null,
      url: options.url || null,
      timestamp: options.timestamp || null,
      color: options.color || null,
      footer: options.footer || null,
      image: options.image || null,
      thumbnail: options.thumbnail || null,
      author: options.author || null,
      fields: options.fields || []
    };
  }

  getHeaders() {
    return {
      'Authorization': `Bot ${this.botToken}`,
      'Content-Type': 'application/json'
    };
  }

  async handleResponse(response) {
    if (response.status === 204) {
      return { success: true };
    }

    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || `Discord API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new DiscordAPI();
