---
sidebar_position: 12
---

# News API Integration

The News SDK provides access to breaking news, historical articles, and news sources from around the world.

## API Key Setup

### Getting Your API Key
1. Go to [NewsAPI.org](https://newsapi.org/)
2. Click **"Get API Key"** or **"Register"**
3. Fill out the registration form
4. Choose your plan:
   - **Developer** (Free): 1,000 requests/day
   - **Business**: Up to 250,000 requests/day
   - **Enterprise**: Unlimited requests
5. Copy your API key from the dashboard

### Environment Setup
Add your API key to your environment variables:

```bash
NEWS_API_KEY=your-actual-api-key-here
```

### Rate Limits by Plan
- **Developer**: 1,000 requests/day, 500 requests/6 hours
- **Business**: 250,000 requests/day
- **Enterprise**: Unlimited

## SDK Usage

```javascript
import newsSDK from 'apis';
```

## Available Methods

### Top Headlines

```javascript
// Get top headlines (default: US)
const headlines = await newsSDK.getTopHeadlines();
console.log('Top stories:', headlines.articles);

// Get headlines by country
const ukHeadlines = await newsSDK.getTopHeadlines({
  country: 'gb'
});

// Get headlines by category
const techNews = await newsSDK.getTopHeadlines({
  category: 'technology',
  country: 'us'
});

// Get headlines by sources
const bbcNews = await newsSDK.getTopHeadlines({
  sources: 'bbc-news'
});

// Advanced filtering
const filteredHeadlines = await newsSDK.getTopHeadlines({
  q: 'artificial intelligence',
  category: 'technology',
  language: 'en',
  pageSize: 20,
  page: 1
});
```

### Search Everything

```javascript
// Search all articles
const articles = await newsSDK.searchEverything({
  q: 'climate change',
  sortBy: 'publishedAt',
  language: 'en'
});

// Advanced search with multiple parameters
const complexSearch = await newsSDK.searchEverything({
  q: 'bitcoin OR cryptocurrency',
  sources: 'coindesk,crypto-coins-news',
  domains: 'coindesk.com,cointelegraph.com',
  from: '2024-01-01',
  to: '2024-01-31',
  sortBy: 'popularity',
  pageSize: 50,
  page: 1
});

// Search by title only
const titleSearch = await newsSDK.searchEverything({
  qInTitle: 'space exploration',
  sortBy: 'publishedAt'
});

// Exclude certain domains
const filteredSearch = await newsSDK.searchEverything({
  q: 'technology',
  excludeDomains: 'spam-site.com,low-quality-news.com',
  language: 'en'
});
```

### News Sources

```javascript
// Get all available sources
const allSources = await newsSDK.getSources();
console.log('Available sources:', allSources.sources);

// Get sources by category
const techSources = await newsSDK.getSources({
  category: 'technology'
});

// Get sources by country and language
const germanSources = await newsSDK.getSources({
  country: 'de',
  language: 'de'
});

// Filter sources by multiple criteria
const businessSources = await newsSDK.getSources({
  category: 'business',
  language: 'en',
  country: 'us'
});
```

## Advanced Search Features

### Date Range Searches

```javascript
// Last 24 hours
const yesterday = new Date();
yesterday.setDate(yesterday.getDate() - 1);

const recentNews = await newsSDK.searchEverything({
  q: 'breaking news',
  from: yesterday.toISOString().split('T')[0],
  sortBy: 'publishedAt'
});

// Specific date range
const historicalNews = await newsSDK.searchEverything({
  q: 'election results',
  from: '2024-01-01',
  to: '2024-01-31',
  sortBy: 'relevancy'
});

// This week's news
const thisWeek = new Date();
thisWeek.setDate(thisWeek.getDate() - 7);

const weeklyNews = await newsSDK.searchEverything({
  q: 'technology trends',
  from: thisWeek.toISOString().split('T')[0],
  sortBy: 'popularity'
});
```

### Multi-language Support

```javascript
// Spanish news
const spanishNews = await newsSDK.getTopHeadlines({
  country: 'es',
  language: 'es'
});

// French technology news
const frenchTech = await newsSDK.searchEverything({
  q: 'intelligence artificielle',
  language: 'fr',
  sortBy: 'publishedAt'
});

// Multilingual search
const globalNews = await newsSDK.searchEverything({
  q: 'climate change',
  language: 'en'
});
```

### Complex Query Building

```javascript
// Boolean operators
const complexQuery = await newsSDK.searchEverything({
  q: '(artificial intelligence OR machine learning) AND (ethics OR regulation)',
  sortBy: 'relevancy'
});

// Phrase search
const phraseSearch = await newsSDK.searchEverything({
  q: '"renewable energy"',
  sortBy: 'publishedAt'
});

// Exclude terms
const excludeSearch = await newsSDK.searchEverything({
  q: 'cryptocurrency -bitcoin',
  sortBy: 'popularity'
});
```

## Data Processing & Analytics

### Article Analysis

```javascript
async function analyzeNewsData(query, days = 7) {
  const fromDate = new Date();
  fromDate.setDate(fromDate.getDate() - days);
  
  const news = await newsSDK.searchEverything({
    q: query,
    from: fromDate.toISOString().split('T')[0],
    sortBy: 'publishedAt',
    pageSize: 100
  });

  // Analyze sources
  const sourceCount = {};
  news.articles.forEach(article => {
    const source = article.source.name;
    sourceCount[source] = (sourceCount[source] || 0) + 1;
  });

  // Analyze publication times
  const hourlyDistribution = {};
  news.articles.forEach(article => {
    const hour = new Date(article.publishedAt).getHours();
    hourlyDistribution[hour] = (hourlyDistribution[hour] || 0) + 1;
  });

  return {
    totalArticles: news.totalResults,
    retrievedArticles: news.articles.length,
    topSources: Object.entries(sourceCount)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 10),
    publishingPattern: hourlyDistribution,
    articles: news.articles
  };
}

// Usage
const analysis = await analyzeNewsData('artificial intelligence', 30);
console.log('AI news analysis:', analysis);
```

### Trending Topics Detection

```javascript
async function detectTrendingTopics(category = 'technology') {
  const headlines = await newsSDK.getTopHeadlines({
    category: category,
    pageSize: 100
  });

  // Extract keywords from titles
  const keywords = {};
  headlines.articles.forEach(article => {
    const words = article.title
      .toLowerCase()
      .split(/\W+/)
      .filter(word => word.length > 3);
    
    words.forEach(word => {
      keywords[word] = (keywords[word] || 0) + 1;
    });
  });

  // Get trending keywords
  const trending = Object.entries(keywords)
    .sort(([,a], [,b]) => b - a)
    .slice(0, 20)
    .map(([word, count]) => ({ word, count }));

  return {
    category,
    totalArticles: headlines.totalResults,
    trendingKeywords: trending,
    articles: headlines.articles
  };
}
```

### Sentiment Analysis Helper

```javascript
function categorizeNewsByTone(articles) {
  const positiveWords = ['success', 'breakthrough', 'growth', 'achievement', 'positive', 'victory'];
  const negativeWords = ['crisis', 'failure', 'decline', 'problem', 'scandal', 'conflict'];
  
  return articles.map(article => {
    const text = (article.title + ' ' + (article.description || '')).toLowerCase();
    
    const positiveCount = positiveWords.reduce((count, word) => 
      count + (text.includes(word) ? 1 : 0), 0);
    const negativeCount = negativeWords.reduce((count, word) => 
      count + (text.includes(word) ? 1 : 0), 0);
    
    let tone = 'neutral';
    if (positiveCount > negativeCount) tone = 'positive';
    else if (negativeCount > positiveCount) tone = 'negative';
    
    return {
      ...article,
      sentiment: {
        tone,
        positiveScore: positiveCount,
        negativeScore: negativeCount
      }
    };
  });
}
```

## Use Cases

### News Dashboard

```javascript
async function createNewsDashboard() {
  // Get data for different sections
  const [
    topHeadlines,
    techNews,
    businessNews,
    sportsNews,
    healthNews
  ] = await Promise.all([
    newsSDK.getTopHeadlines({ pageSize: 5 }),
    newsSDK.getTopHeadlines({ category: 'technology', pageSize: 5 }),
    newsSDK.getTopHeadlines({ category: 'business', pageSize: 5 }),
    newsSDK.getTopHeadlines({ category: 'sports', pageSize: 5 }),
    newsSDK.getTopHeadlines({ category: 'health', pageSize: 5 })
  ]);

  return {
    topStories: topHeadlines.articles,
    technology: techNews.articles,
    business: businessNews.articles,
    sports: sportsNews.articles,
    health: healthNews.articles,
    lastUpdated: new Date().toISOString()
  };
}
```

### Real-time News Monitor

```javascript
class NewsMonitor {
  constructor(keywords, interval = 300000) { // 5 minutes
    this.keywords = keywords;
    this.interval = interval;
    this.lastCheck = new Date();
    this.seenArticles = new Set();
  }

  async checkForNewArticles() {
    const results = [];
    
    for (const keyword of this.keywords) {
      try {
        const news = await newsSDK.searchEverything({
          q: keyword,
          from: this.lastCheck.toISOString().split('T')[0],
          sortBy: 'publishedAt',
          pageSize: 20
        });

        const newArticles = news.articles.filter(article => {
          const articleId = article.url;
          if (this.seenArticles.has(articleId)) return false;
          
          this.seenArticles.add(articleId);
          return new Date(article.publishedAt) > this.lastCheck;
        });

        if (newArticles.length > 0) {
          results.push({
            keyword,
            newArticles: newArticles.length,
            articles: newArticles
          });
        }
      } catch (error) {
        console.error(`Error checking news for "${keyword}":`, error.message);
      }
    }

    this.lastCheck = new Date();
    return results;
  }

  startMonitoring(callback) {
    setInterval(async () => {
      const newNews = await this.checkForNewArticles();
      if (newNews.length > 0) {
        callback(newNews);
      }
    }, this.interval);
  }
}

// Usage
const monitor = new NewsMonitor(['breaking news', 'cryptocurrency', 'AI breakthrough']);
monitor.startMonitoring((newNews) => {
  console.log('New articles found:', newNews);
});
```

### News Aggregator

```javascript
async function aggregateNewsFromMultipleSources(topic, sources) {
  const sourceGroups = [];
  
  // Split sources into groups (NewsAPI allows comma-separated sources)
  for (let i = 0; i < sources.length; i += 20) {
    sourceGroups.push(sources.slice(i, i + 20));
  }

  const allResults = [];
  
  for (const sourceGroup of sourceGroups) {
    try {
      const result = await newsSDK.searchEverything({
        q: topic,
        sources: sourceGroup.join(','),
        sortBy: 'publishedAt',
        pageSize: 100
      });
      
      allResults.push(...result.articles);
    } catch (error) {
      console.error('Error fetching from sources:', sourceGroup, error.message);
    }
  }

  // Remove duplicates and sort by date
  const uniqueArticles = allResults.filter((article, index, self) =>
    index === self.findIndex(a => a.url === article.url)
  );

  return uniqueArticles.sort((a, b) => 
    new Date(b.publishedAt) - new Date(a.publishedAt)
  );
}
```

### Newsletter Generator

```javascript
async function generateNewsDigest(topics, categories) {
  const digest = {
    generatedAt: new Date().toISOString(),
    sections: {}
  };

  // Get top headlines by category
  for (const category of categories) {
    try {
      const news = await newsSDK.getTopHeadlines({
        category: category,
        pageSize: 5
      });
      
      digest.sections[category] = {
        title: `Top ${category.charAt(0).toUpperCase() + category.slice(1)} News`,
        articles: news.articles.map(article => ({
          title: article.title,
          description: article.description,
          url: article.url,
          source: article.source.name,
          publishedAt: article.publishedAt
        }))
      };
    } catch (error) {
      console.error(`Error fetching ${category} news:`, error.message);
    }
  }

  // Search for specific topics
  for (const topic of topics) {
    try {
      const news = await newsSDK.searchEverything({
        q: topic,
        sortBy: 'popularity',
        pageSize: 3
      });
      
      if (news.articles.length > 0) {
        digest.sections[`topic_${topic.replace(/\s+/g, '_')}`] = {
          title: `${topic} Updates`,
          articles: news.articles.map(article => ({
            title: article.title,
            description: article.description,
            url: article.url,
            source: article.source.name,
            publishedAt: article.publishedAt
          }))
        };
      }
    } catch (error) {
      console.error(`Error searching for "${topic}":`, error.message);
    }
  }

  return digest;
}

// Usage
const digest = await generateNewsDigest(
  ['artificial intelligence', 'renewable energy'],
  ['technology', 'business', 'science']
);
```

## Response Format

### Article Object Structure

```javascript
{
  "source": {
    "id": "techcrunch",
    "name": "TechCrunch"
  },
  "author": "Sarah Perez",
  "title": "AI startup raises $50M in Series B funding",
  "description": "The company plans to use the funding to expand...",
  "url": "https://techcrunch.com/article-url",
  "urlToImage": "https://example.com/image.jpg",
  "publishedAt": "2024-01-15T14:30:00Z",
  "content": "The full article content (truncated)..."
}
```

### API Response Structure

```javascript
{
  "status": "ok",
  "totalResults": 1250,
  "articles": [
    // Array of article objects
  ]
}
```

## Rate Limits & Best Practices

### Rate Limiting
- **Developer**: 1,000 requests/day
- **Business**: 250,000 requests/day
- Cache responses when possible
- Implement exponential backoff for retries

### Performance Optimization

```javascript
// Cache frequently requested data
const cache = new Map();

async function getCachedNews(cacheKey, fetchFunction, ttl = 300000) { // 5 min TTL
  if (cache.has(cacheKey)) {
    const { data, timestamp } = cache.get(cacheKey);
    if (Date.now() - timestamp < ttl) {
      return data;
    }
  }

  const data = await fetchFunction();
  cache.set(cacheKey, { data, timestamp: Date.now() });
  return data;
}

// Usage
const headlines = await getCachedNews(
  'top-headlines-tech',
  () => newsSDK.getTopHeadlines({ category: 'technology' }),
  600000 // 10 minutes
);
```

## Error Handling

```javascript
try {
  const news = await newsSDK.getTopHeadlines({
    country: 'invalid'
  });
} catch (error) {
  if (error.message.includes('rate limit')) {
    console.log('Rate limit exceeded - wait before retrying');
  } else if (error.message.includes('API key')) {
    console.log('Invalid or missing API key');
  } else if (error.message.includes('parameter')) {
    console.log('Invalid parameter provided');
  } else {
    console.log('News API error:', error.message);
  }
}
```

## Supported Countries & Languages

### Countries (ISO 3166-1 alpha-2)
- `us` - United States
- `gb` - United Kingdom  
- `ca` - Canada
- `au` - Australia
- `de` - Germany
- `fr` - France
- `it` - Italy
- `es` - Spain
- `jp` - Japan
- `kr` - South Korea
- And many more...

### Languages (ISO 639-1)
- `en` - English
- `es` - Spanish
- `fr` - French
- `de` - German
- `it` - Italian
- `pt` - Portuguese
- `ru` - Russian
- `zh` - Chinese
- `ja` - Japanese
- `ar` - Arabic

### Categories
- `business`
- `entertainment`
- `general`
- `health`
- `science`
- `sports`
- `technology`

## Useful Links

- [NewsAPI.org](https://newsapi.org/)
- [API Documentation](https://newsapi.org/docs)
- [Source List](https://newsapi.org/sources)
- [Pricing Plans](https://newsapi.org/pricing)
- [Status Page](https://newsapi.org/status)
