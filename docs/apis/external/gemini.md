---
sidebar_position: 2
---

# Gemini API Integration

The Gemini SDK provides access to Google's advanced AI models for text generation, vision analysis, and embeddings.

## 🔑 API Key Setup

### Getting Your API Key
1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click **"Create API Key"**
4. Choose **"Create API key in new project"** or select existing project
5. Copy the generated API key

### Environment Setup
Add your API key to your environment variables:

```bash
GEMINI_API_KEY=your-actual-api-key-here
```

## 📖 SDK Usage

```javascript
import geminiSDK from './backend/apis_sdk/geminiSDK.js';
```

## 🚀 Available Methods

### Text Generation

```javascript
const response = await geminiSDK.generateContent('Write a creative story about space exploration', {
  model: 'gemini-pro',
  temperature: 0.7,
  maxOutputTokens: 1024
});

console.log(response.candidates[0].content.parts[0].text);
```

### Chat with History

```javascript
const messages = [
  { role: 'user', content: 'Hello, what can you help me with?' },
  { role: 'model', content: 'I can help with various tasks like writing, analysis, and answering questions.' },
  { role: 'user', content: 'Can you write a poem about the ocean?' }
];

const response = await geminiSDK.generateContentWithHistory(messages, {
  model: 'gemini-pro',
  temperature: 0.8
});

console.log(response.candidates[0].content.parts[0].text);
```

### Vision Analysis

```javascript
// Base64 encoded image data
const imageData = 'iVBORw0KGgoAAAANSUhEUgAA...'; // Your base64 image

const response = await geminiSDK.generateContentWithImage(
  'Describe what you see in this image',
  imageData,
  'image/jpeg',
  {
    model: 'gemini-pro-vision',
    maxOutputTokens: 2048
  }
);

console.log(response.candidates[0].content.parts[0].text);
```

### Streaming Response

```javascript
const stream = await geminiSDK.streamGenerateContent('Tell me a long story about dragons', {
  model: 'gemini-pro',
  temperature: 0.9
});

// Handle streaming response
const reader = stream.body.getReader();
while (true) {
  const { done, value } = await reader.read();
  if (done) break;
  
  const chunk = new TextDecoder().decode(value);
  console.log('Chunk:', chunk);
}
```

### Text Embeddings

```javascript
const response = await geminiSDK.embedContent('This is text to embed', {
  model: 'embedding-001'
});

console.log('Embedding:', response.embedding.values);
```

### Batch Embeddings

```javascript
const texts = [
  'First text to embed',
  'Second text to embed',
  'Third text to embed'
];

const response = await geminiSDK.batchEmbedContents(texts, {
  model: 'embedding-001'
});

response.embeddings.forEach((embedding, index) => {
  console.log(`Embedding ${index}:`, embedding.values);
});
```

### Token Counting

```javascript
const response = await geminiSDK.countTokens('Count the tokens in this text', {
  model: 'gemini-pro'
});

console.log('Token count:', response.totalTokens);
```

### List Available Models

```javascript
const models = await geminiSDK.listModels();
console.log('Available models:', models.models);
```

## 🎛️ Configuration Options

### Generation Config
- `temperature`: Creativity level (0.0-1.0)
- `topK`: Top-K sampling (1-40)
- `topP`: Top-P sampling (0.0-1.0)
- `maxOutputTokens`: Maximum response length
- `stopSequences`: Array of stop sequences

### Safety Settings
```javascript
const safetySettings = [
  {
    category: "HARM_CATEGORY_HARASSMENT",
    threshold: "BLOCK_MEDIUM_AND_ABOVE"
  },
  {
    category: "HARM_CATEGORY_HATE_SPEECH", 
    threshold: "BLOCK_MEDIUM_AND_ABOVE"
  }
];

const response = await geminiSDK.generateContent('Your prompt', {
  safetySettings: safetySettings
});
```

## 🎯 Model Capabilities

### Gemini Pro
- **Best for**: Text generation, reasoning, code
- **Context length**: 32,768 tokens
- **Output length**: 8,192 tokens

### Gemini Pro Vision
- **Best for**: Image understanding, multimodal tasks
- **Supported formats**: JPEG, PNG, WebP, HEIC, HEIF
- **Max image size**: 20MB
- **Context length**: 16,384 tokens

### Embedding Models
- **embedding-001**: 768-dimensional embeddings
- **Best for**: Semantic search, clustering, classification

## 💰 Pricing Information

Google AI Studio offers generous free usage:
- **Free tier**: 60 requests per minute
- **Text generation**: First 1M tokens free per month
- **Vision**: First 1K images free per month

For production use, check [Google AI Pricing](https://ai.google.dev/pricing).

## 📊 Rate Limits

- **Free tier**: 60 requests per minute
- **Paid tier**: Higher limits available

## ⚠️ Error Handling

```javascript
try {
  const response = await geminiSDK.generateContent('Your prompt');
  console.log(response);
} catch (error) {
  if (error.message.includes('quota')) {
    console.log('Quota exceeded');
  } else if (error.message.includes('safety')) {
    console.log('Content blocked by safety filters');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 🛡️ Safety Features

Gemini includes built-in safety filters for:
- Harassment
- Hate speech
- Sexually explicit content
- Dangerous content

You can adjust safety thresholds:
- `BLOCK_NONE`
- `BLOCK_ONLY_HIGH`
- `BLOCK_MEDIUM_AND_ABOVE`
- `BLOCK_LOW_AND_ABOVE`

## 🔗 Useful Links

- [Google AI Studio](https://makersuite.google.com)
- [Gemini API Documentation](https://ai.google.dev/docs)
- [Google AI Pricing](https://ai.google.dev/pricing)
- [Gemini Models Overview](https://ai.google.dev/models/gemini)
