---
sidebar_position: 9
---

# Discord API Integration

The Discord SDK provides access to Discord's API for bot functionality and webhook messaging.

## 🔑 API Key Setup

### Getting Your Bot Token
1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Click **"New Application"** and give it a name
3. Go to **"Bot"** section in the sidebar
4. Click **"Add Bot"**
5. Copy the **Token** (keep this secret!)
6. Enable required **Privileged Gateway Intents** if needed

### Environment Setup
Add your bot token to your environment variables:

```bash
DISCORD_BOT_TOKEN=your-actual-bot-token-here
```

### For Webhooks (No Token Required)
Discord webhooks don't require authentication - you just need a webhook URL.

## 📖 SDK Usage

```javascript
import discordSDK from './backend/apis_sdk/discordSDK.js';
```

## 🚀 Available Methods

### Webhook Messages (No Authentication)

```javascript
// Send webhook message
const webhookUrl = 'https://discord.com/api/webhooks/YOUR_WEBHOOK_URL';

await discordSDK.sendWebhookMessage(
  webhookUrl,
  'Hello from webhook! 👋',
  {
    username: 'Custom Bot Name',
    avatar_url: 'https://example.com/avatar.png'
  }
);
```

### Webhook Embeds

```javascript
// Create rich embed
const embed = discordSDK.createEmbed({
  title: 'Embed Title',
  description: 'This is an embed description',
  color: 0x00ff00, // Green color
  fields: [
    { name: 'Field 1', value: 'Value 1', inline: true },
    { name: 'Field 2', value: 'Value 2', inline: true }
  ],
  footer: { text: 'Footer text' },
  timestamp: new Date().toISOString()
});

await discordSDK.sendWebhookEmbed(webhookUrl, embed, {
  content: 'Check out this embed!'
});
```

### Bot API Methods (Requires Token)

### Guild Operations

```javascript
// Get bot's guilds
const guilds = await discordSDK.getGuilds();
guilds.forEach(guild => {
  console.log(`Guild: ${guild.name} (${guild.id})`);
});

// Get specific guild
const guild = await discordSDK.getGuild('GUILD_ID');
console.log('Guild info:', guild);

// Get guild channels
const channels = await discordSDK.getGuildChannels('GUILD_ID');
channels.forEach(channel => {
  console.log(`Channel: ${channel.name} (${channel.type})`);
});
```

### Channel Operations

```javascript
// Get channel info
const channel = await discordSDK.getChannel('CHANNEL_ID');
console.log('Channel:', channel.name);

// Send message to channel
const message = await discordSDK.sendMessage('CHANNEL_ID', 'Hello Discord! 🎮');

// Send embed to channel
const embed = discordSDK.createEmbed({
  title: 'Bot Message',
  description: 'Sent via Discord SDK',
  color: 0x7289da
});

await discordSDK.sendEmbed('CHANNEL_ID', embed);
```

### Message Management

```javascript
// Get messages from channel
const messages = await discordSDK.getMessages('CHANNEL_ID', {
  limit: 10
});

// Get specific message
const message = await discordSDK.getMessage('CHANNEL_ID', 'MESSAGE_ID');

// Edit message
await discordSDK.editMessage('CHANNEL_ID', 'MESSAGE_ID', 'Updated content');

// Delete message
await discordSDK.deleteMessage('CHANNEL_ID', 'MESSAGE_ID');
```

### Reactions

```javascript
// Add reaction to message
await discordSDK.addReaction('CHANNEL_ID', 'MESSAGE_ID', '👍');

// Remove reaction
await discordSDK.removeReaction('CHANNEL_ID', 'MESSAGE_ID', '👍');

// Remove user's reaction
await discordSDK.removeReaction('CHANNEL_ID', 'MESSAGE_ID', '👍', 'USER_ID');
```

### User & Member Operations

```javascript
// Get bot user info
const user = await discordSDK.getUserInfo();
console.log('Bot user:', user);

// Get guild members
const members = await discordSDK.getGuildMembers('GUILD_ID', {
  limit: 10
});

// Get specific member
const member = await discordSDK.getGuildMember('GUILD_ID', 'USER_ID');
console.log('Member:', member);
```

### Role Management

```javascript
// Create role
const role = await discordSDK.createRole('GUILD_ID', 'New Role', {
  color: 0xff0000, // Red
  hoist: true,
  mentionable: true,
  permissions: '8' // Administrator permission
});

console.log('Created role:', role);
```

## 🎨 Embed Customization

### Rich Embeds

```javascript
const richEmbed = discordSDK.createEmbed({
  title: 'Rich Embed Example',
  description: 'This embed has many features',
  url: 'https://example.com',
  color: 0x00ff00,
  timestamp: new Date().toISOString(),
  footer: {
    text: 'Footer text',
    icon_url: 'https://example.com/icon.png'
  },
  thumbnail: {
    url: 'https://example.com/thumbnail.png'
  },
  image: {
    url: 'https://example.com/image.png'
  },
  author: {
    name: 'Author Name',
    url: 'https://example.com',
    icon_url: 'https://example.com/author.png'
  },
  fields: [
    {
      name: 'Field 1',
      value: 'This is inline',
      inline: true
    },
    {
      name: 'Field 2', 
      value: 'This is also inline',
      inline: true
    },
    {
      name: 'Field 3',
      value: 'This spans full width',
      inline: false
    }
  ]
});
```

### Color Examples

```javascript
// Common Discord colors
const colors = {
  DEFAULT: 0x000000,
  WHITE: 0xffffff,
  AQUA: 0x1abc9c,
  GREEN: 0x57f287,
  BLUE: 0x3498db,
  YELLOW: 0xfee75c,
  PURPLE: 0x9b59b6,
  LUMINOUS_VIVID_PINK: 0xe91e63,
  GOLD: 0xf1c40f,
  ORANGE: 0xe67e22,
  RED: 0xed4245,
  GREY: 0x95a5a6,
  NAVY: 0x34495e,
  DARK_AQUA: 0x11806a,
  DARK_GREEN: 0x1f8b4c,
  DARK_BLUE: 0x206694,
  DARK_PURPLE: 0x71368a,
  DARK_VIVID_PINK: 0xad1457,
  DARK_GOLD: 0xc27c0e,
  DARK_ORANGE: 0xa84300,
  DARK_RED: 0x992d22,
  DARK_GREY: 0x979c9f,
  DARKER_GREY: 0x7f8c8d,
  LIGHT_GREY: 0xbcc0c0,
  DARK_NAVY: 0x2c3e50,
  BLURPLE: 0x5865f2,
  GREYPLE: 0x99aab5,
  DARK_BUT_NOT_BLACK: 0x2c2f33,
  NOT_QUITE_BLACK: 0x23272a
};

const embed = discordSDK.createEmbed({
  title: 'Colored Embed',
  color: colors.BLURPLE
});
```

## 🎯 Use Cases

### Status Updates

```javascript
async function sendStatusUpdate(webhookUrl, status, details) {
  const color = status === 'online' ? 0x00ff00 : 
                status === 'maintenance' ? 0xffaa00 : 0xff0000;
  
  const embed = discordSDK.createEmbed({
    title: `System Status: ${status.toUpperCase()}`,
    description: details,
    color: color,
    timestamp: new Date().toISOString(),
    footer: { text: 'Status Monitor' }
  });

  await discordSDK.sendWebhookEmbed(webhookUrl, embed);
}

await sendStatusUpdate(webhookUrl, 'online', 'All systems operational');
```

### Log Messages

```javascript
async function sendLogMessage(webhookUrl, level, message, details = {}) {
  const colors = {
    info: 0x3498db,
    warn: 0xf39c12,
    error: 0xe74c3c,
    success: 0x2ecc71
  };

  const embed = discordSDK.createEmbed({
    title: `${level.toUpperCase()} Log`,
    description: message,
    color: colors[level] || colors.info,
    fields: Object.entries(details).map(([key, value]) => ({
      name: key,
      value: String(value),
      inline: true
    })),
    timestamp: new Date().toISOString()
  });

  await discordSDK.sendWebhookEmbed(webhookUrl, embed);
}

await sendLogMessage(webhookUrl, 'error', 'Database connection failed', {
  'Error Code': 'CONN_TIMEOUT',
  'Retry Count': 3,
  'Server': 'db-primary'
});
```

### Notifications

```javascript
async function sendNotification(channelId, title, message, urgent = false) {
  const embed = discordSDK.createEmbed({
    title: urgent ? `🚨 ${title}` : `📢 ${title}`,
    description: message,
    color: urgent ? 0xff0000 : 0x00ff00,
    timestamp: new Date().toISOString()
  });

  await discordSDK.sendEmbed(channelId, embed);
}
```

## 📊 Rate Limits

Discord API rate limits:
- **Global**: 50 requests per second
- **Per route**: Varies by endpoint
- **Webhooks**: 30 requests per minute per webhook

## ⚠️ Error Handling

```javascript
try {
  await discordSDK.sendMessage('INVALID_CHANNEL', 'Test');
} catch (error) {
  if (error.message.includes('Missing Permissions')) {
    console.log('Bot lacks permissions');
  } else if (error.message.includes('Unknown Channel')) {
    console.log('Channel not found');
  } else if (error.message.includes('rate limited')) {
    console.log('Rate limited - wait before retry');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 🔐 Permissions

Common bot permissions:
- **Send Messages** (2048)
- **Embed Links** (16384)
- **Attach Files** (32768)
- **Read Message History** (65536)
- **Add Reactions** (64)
- **Manage Messages** (8192)

Calculate permissions: [Discord Permissions Calculator](https://discordapi.com/permissions.html)

## 🔗 Useful Links

- [Discord Developer Portal](https://discord.com/developers/applications)
- [Discord API Documentation](https://discord.com/developers/docs)
- [Discord Permissions Calculator](https://discordapi.com/permissions.html)
- [Discord Bot Guide](https://discord.com/developers/docs/getting-started)
