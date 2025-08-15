---
sidebar_position: 1
---

# OpenAI API Integration

The OpenAI SDK provides access to OpenAI's powerful AI models including ChatGPT, DALL-E, and Whisper.

## 🔑 API Key Setup

### Getting Your API Key
1. Visit [OpenAI Platform](https://platform.openai.com)
2. Sign up or log in to your account
3. Go to **API Keys** section
4. Click **"Create new secret key"**
5. Copy the generated key (starts with `sk-`)

### Environment Setup
Add your API key to your environment variables:

```bash
OPENAI_API_KEY=sk-your-actual-api-key-here
```

## 📖 SDK Usage

```javascript
import openaiSDK from './backend/apis_sdk/openaiSDK.js';
```

## 🚀 Available Methods

### Chat Completions (ChatGPT)

```javascript
const messages = [
  { role: 'user', content: 'Hello, how are you?' }
];

const response = await openaiSDK.createChatCompletion(messages, {
  model: 'gpt-4', // or 'gpt-3.5-turbo'
  max_tokens: 150,
  temperature: 0.7
});

console.log(response.choices[0].message.content);
```

### Text Completions

```javascript
const response = await openaiSDK.createCompletion('Write a story about', {
  model: 'gpt-3.5-turbo-instruct',
  max_tokens: 100,
  temperature: 0.8
});

console.log(response.choices[0].text);
```

### Image Generation (DALL-E)

```javascript
const response = await openaiSDK.createImage('A futuristic city at sunset', {
  n: 1,
  size: '1024x1024',
  response_format: 'url'
});

console.log(response.data[0].url);
```

### Text Embeddings

```javascript
const response = await openaiSDK.createEmbedding('Text to convert to embeddings', {
  model: 'text-embedding-ada-002'
});

console.log(response.data[0].embedding);
```

### Audio Transcription (Whisper)

```javascript
const audioFile = new File([audioBlob], 'audio.mp3', { type: 'audio/mp3' });

const response = await openaiSDK.transcribeAudio(audioFile, {
  model: 'whisper-1',
  language: 'en'
});

console.log(response.text);
```

### Audio Translation

```javascript
const audioFile = new File([audioBlob], 'audio.mp3', { type: 'audio/mp3' });

const response = await openaiSDK.translateAudio(audioFile, {
  model: 'whisper-1'
});

console.log(response.text);
```

### Content Moderation

```javascript
const response = await openaiSDK.moderateContent('Text to moderate');

if (response.results[0].flagged) {
  console.log('Content flagged:', response.results[0].categories);
}
```

### List Available Models

```javascript
const models = await openaiSDK.listModels();
console.log(models.data);
```

## 🎛️ Configuration Options

### Chat Completion Options
- `model`: Model to use (`gpt-4`, `gpt-3.5-turbo`, etc.)
- `max_tokens`: Maximum tokens in response
- `temperature`: Creativity level (0-2)
- `top_p`: Nucleus sampling parameter
- `frequency_penalty`: Reduce repetition (-2 to 2)
- `presence_penalty`: Encourage new topics (-2 to 2)
- `stop`: Stop sequences

### Image Generation Options
- `n`: Number of images (1-10)
- `size`: Image size (`256x256`, `512x512`, `1024x1024`)
- `response_format`: `url` or `b64_json`

## 💰 Pricing Information

OpenAI uses token-based pricing:
- **GPT-4**: ~$0.03 per 1K prompt tokens, ~$0.06 per 1K completion tokens
- **GPT-3.5 Turbo**: ~$0.0015 per 1K prompt tokens, ~$0.002 per 1K completion tokens
- **DALL-E 3**: ~$0.04 per image (1024×1024)
- **Whisper**: ~$0.006 per minute

Check [OpenAI Pricing](https://openai.com/pricing) for current rates.

## 📊 Rate Limits

- **Free Tier**: 20 requests per minute
- **Pay-as-you-go**: 60 requests per minute
- **Higher tiers**: Up to 5,000 requests per minute

## ⚠️ Error Handling

```javascript
try {
  const response = await openaiSDK.createChatCompletion(messages);
  console.log(response);
} catch (error) {
  if (error.message.includes('insufficient_quota')) {
    console.log('You have exceeded your quota');
  } else if (error.message.includes('rate_limit_exceeded')) {
    console.log('Rate limit exceeded, please wait');
  } else {
    console.log('Other error:', error.message);
  }
}
```

## 🔗 Useful Links

- [OpenAI Platform](https://platform.openai.com)
- [OpenAI Documentation](https://platform.openai.com/docs)
- [OpenAI Pricing](https://openai.com/pricing)
- [OpenAI Community](https://community.openai.com)
