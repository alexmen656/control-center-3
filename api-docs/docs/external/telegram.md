---
sidebar_position: 8
---

# Telegram Bot API Integration

The Telegram Bot SDK provides comprehensive access to Telegram's Bot API for messaging, webhooks, and chat management.

## 🔑 API Key Setup

### Getting Your Bot Token
1. Open Telegram and search for **@BotFather**
2. Send `/newbot` command
3. Choose a name for your bot
4. Choose a username for your bot (must end with 'bot')
5. Copy the HTTP API token (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Environment Setup
Add your bot token to your environment variables:

```bash
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
```

## 📖 SDK Usage

```javascript
import telegramSDK from './backend/apis_sdk/telegramSDK.js';
```

## 🚀 Available Methods

### Bot Information

```javascript
// Get bot information
const botInfo = await telegramSDK.getMe();
console.log('Bot username:', botInfo.result.username);
console.log('Bot name:', botInfo.result.first_name);
```

### Send Messages

```javascript
// Send text message
const message = await telegramSDK.sendMessage(
  'CHAT_ID', // Chat ID or username
  'Hello from your bot! 🤖',
  {
    parse_mode: 'HTML', // or 'Markdown'
    disable_web_page_preview: true
  }
);

// Send message with inline keyboard
const keyboard = {
  inline_keyboard: [
    [
      { text: 'Button 1', callback_data: 'btn1' },
      { text: 'Button 2', callback_data: 'btn2' }
    ],
    [
      { text: 'Visit Website', url: 'https://example.com' }
    ]
  ]
};

await telegramSDK.sendMessage('CHAT_ID', 'Choose an option:', {
  reply_markup: keyboard
});
```

### Send Media

```javascript
// Send photo
const photoFile = new File([photoBlob], 'photo.jpg', { type: 'image/jpeg' });
await telegramSDK.sendPhoto('CHAT_ID', photoFile, {
  caption: 'Check out this photo! 📸',
  parse_mode: 'HTML'
});

// Send photo by URL
await telegramSDK.sendPhoto('CHAT_ID', 'https://example.com/photo.jpg', {
  caption: 'Photo from URL'
});

// Send document
const documentFile = new File([pdfBlob], 'document.pdf', { type: 'application/pdf' });
await telegramSDK.sendDocument('CHAT_ID', documentFile, {
  caption: 'Here is your document 📄'
});
```

### Send Location

```javascript
// Send location
await telegramSDK.sendLocation('CHAT_ID', 52.5200, 13.4050, {
  live_period: 3600 // Live location for 1 hour
});
```

### Message Updates

```javascript
// Get updates (polling method)
const updates = await telegramSDK.getUpdates({
  offset: 0,
  limit: 100,
  timeout: 0
});

updates.result.forEach(update => {
  if (update.message) {
    console.log('New message:', update.message.text);
    console.log('From:', update.message.from.first_name);
    console.log('Chat ID:', update.message.chat.id);
  }
});
```

### Webhook Management

```javascript
// Set webhook URL
await telegramSDK.setWebhook('https://your-server.com/webhook', {
  max_connections: 40,
  allowed_updates: ['message', 'callback_query']
});

// Get webhook info
const webhookInfo = await telegramSDK.getWebhookInfo();
console.log('Webhook URL:', webhookInfo.result.url);

// Delete webhook (return to polling)
await telegramSDK.deleteWebhook(true); // Drop pending updates
```

### Chat Management

```javascript
// Get chat information
const chat = await telegramSDK.getChat('CHAT_ID');
console.log('Chat title:', chat.result.title);
console.log('Chat type:', chat.result.type);

// Get chat member
const member = await telegramSDK.getChatMember('CHAT_ID', 'USER_ID');
console.log('Member status:', member.result.status);

// Kick chat member (ban user)
await telegramSDK.kickChatMember('CHAT_ID', 'USER_ID', {
  until_date: Math.floor(Date.now() / 1000) + 3600, // Ban for 1 hour
  revoke_messages: true
});
```

### Callback Queries

```javascript
// Answer callback query (from inline buttons)
await telegramSDK.answerCallbackQuery('CALLBACK_QUERY_ID', {
  text: 'Button clicked! ✅',
  show_alert: false
});

// Show alert popup
await telegramSDK.answerCallbackQuery('CALLBACK_QUERY_ID', {
  text: 'This is an alert popup! ⚠️',
  show_alert: true
});
```

### Edit Messages

```javascript
// Edit message text
await telegramSDK.editMessageText(
  'Updated message content 📝',
  {
    chat_id: 'CHAT_ID',
    message_id: 'MESSAGE_ID',
    parse_mode: 'HTML'
  }
);

// Delete message
await telegramSDK.deleteMessage('CHAT_ID', 'MESSAGE_ID');
```

## 🎛️ Message Formatting

### HTML Format
```javascript
const htmlMessage = `
<b>Bold text</b>
<i>Italic text</i>
<u>Underlined text</u>
<s>Strikethrough text</s>
<code>Monospace text</code>
<pre>Preformatted text</pre>
<a href="https://example.com">Link</a>
`;

await telegramSDK.sendMessage('CHAT_ID', htmlMessage, {
  parse_mode: 'HTML'
});
```

### Markdown Format
```javascript
const markdownMessage = `
*Bold text*
_Italic text_
\`Monospace text\`
\`\`\`
Preformatted text
\`\`\`
[Link](https://example.com)
`;

await telegramSDK.sendMessage('CHAT_ID', markdownMessage, {
  parse_mode: 'Markdown'
});
```

## 🎯 Bot Types & Use Cases

### Command Bot
```javascript
// Handle commands
if (message.text.startsWith('/start')) {
  await telegramSDK.sendMessage(message.chat.id, 'Welcome! 👋');
} else if (message.text.startsWith('/help')) {
  await telegramSDK.sendMessage(message.chat.id, 'Available commands: /start, /help');
}
```

### Echo Bot
```javascript
// Echo received messages
if (message.text) {
  await telegramSDK.sendMessage(message.chat.id, `You said: ${message.text}`);
}
```

### Notification Bot
```javascript
// Send notifications to multiple users
const subscribers = ['CHAT_ID_1', 'CHAT_ID_2', 'CHAT_ID_3'];

for (const chatId of subscribers) {
  await telegramSDK.sendMessage(chatId, '🔔 New notification!');
}
```

## 📊 Rate Limits

Telegram Bot API rate limits:
- **Messages**: 30 messages per second to different chats
- **Same chat**: 1 message per second
- **Groups**: 20 messages per minute

## ⚠️ Error Handling

```javascript
try {
  await telegramSDK.sendMessage('INVALID_CHAT_ID', 'Test message');
} catch (error) {
  if (error.message.includes('chat not found')) {
    console.log('Invalid chat ID');
  } else if (error.message.includes('bot was blocked')) {
    console.log('Bot was blocked by user');
  } else if (error.message.includes('Too Many Requests')) {
    console.log('Rate limit exceeded');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 🛡️ Security Best Practices

### Validate Updates
```javascript
// Always validate incoming updates
if (update.message && update.message.from && !update.message.from.is_bot) {
  // Process legitimate user message
}
```

### Rate Limiting
```javascript
// Implement your own rate limiting
const userLastMessage = new Map();
const RATE_LIMIT = 1000; // 1 second

function isRateLimited(userId) {
  const lastMessage = userLastMessage.get(userId);
  const now = Date.now();
  
  if (lastMessage && now - lastMessage < RATE_LIMIT) {
    return true;
  }
  
  userLastMessage.set(userId, now);
  return false;
}
```

## 🔗 Useful Links

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [BotFather](https://t.me/botfather)
- [Telegram Bot Examples](https://core.telegram.org/bots/samples)
- [Bot API Updates](https://core.telegram.org/bots/api-changelog)
