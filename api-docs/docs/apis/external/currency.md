---
sidebar_position: 5
---

# Currency Exchange API Integration

The Currency SDK provides real-time exchange rates, currency conversion, historical data, and cryptocurrency prices.

## 🔑 API Key Setup

### Free Tier (No API Key Required)
The SDK uses free APIs that don't require authentication:
- **ExchangeRate-API**: 2,000 requests/month free
- **Coinbase API**: Public endpoints for crypto rates

### Premium Options (Optional)
For higher limits, you can use premium services:

1. **ExchangeRate-API Pro**: Visit [exchangerate-api.com](https://exchangerate-api.com)
2. **Fixer.io**: Visit [fixer.io](https://fixer.io)
3. **CurrencyAPI**: Visit [currencyapi.com](https://currencyapi.com)

### Environment Setup (Optional)
```bash
CURRENCY_API_KEY=your-premium-api-key-here
```

## 📖 SDK Usage

```javascript
import currencySDK from './backend/apis_sdk/currencySDK.js';
```

## 🚀 Available Methods

### Latest Exchange Rates

```javascript
// Get all rates for USD base
const rates = await currencySDK.getLatestRates('USD');
console.log('EUR rate:', rates.rates.EUR);
console.log('Date:', rates.date);

// Get rates for different base currency
const eurRates = await currencySDK.getLatestRates('EUR');
```

### Currency Conversion

```javascript
// Convert 100 USD to EUR
const conversion = await currencySDK.convertCurrency('USD', 'EUR', 100);

console.log(`${conversion.amount} ${conversion.from} = ${conversion.converted} ${conversion.to}`);
console.log(`Exchange rate: ${conversion.rate}`);
```

### Historical Exchange Rates

```javascript
// Get rates for a specific date (YYYY-MM-DD)
const historicalRates = await currencySDK.getHistoricalRates('USD', '2024-01-01');

console.log('EUR rate on Jan 1, 2024:', historicalRates.rates.EUR);
```

### Supported Currencies

```javascript
const currencies = await currencySDK.getSupportedCurrencies();

console.log(`${currencies.count} currencies supported`);
console.log('Currencies:', currencies.currencies);
```

### Multiple Currency Rates

```javascript
// Get specific currency rates
const specificRates = await currencySDK.getMultipleRates('USD', ['EUR', 'GBP', 'JPY', 'CAD']);

console.log('Selected rates:', specificRates.rates);
```

### Time Series Data

```javascript
// Get historical data for date range (max 30 days for free tier)
const timeSeries = await currencySDK.getTimeSeries(
  'USD',
  ['EUR', 'GBP'],
  '2024-01-01',
  '2024-01-07'
);

console.log('Time series:', timeSeries.timeseries);
```

### Cryptocurrency Rates

```javascript
// Get crypto exchange rates (USD base)
const cryptoRates = await currencySDK.getCryptoRates();

console.log('Bitcoin (BTC):', cryptoRates.crypto_rates.BTC);
console.log('Ethereum (ETH):', cryptoRates.crypto_rates.ETH);
```

### Currency Information

```javascript
// Get currency details
const currencyInfo = await currencySDK.getCurrencyInfo('EUR');

console.log(`${currencyInfo.name} (${currencyInfo.symbol}) - ${currencyInfo.country}`);
```

## 🛠️ Utility Methods

### Format Currency

```javascript
// Format currency amounts
const formatted = currencySDK.formatCurrency(1234.56, 'EUR', 'de-DE');
console.log(formatted); // "1.234,56 €"

// Different locales
const usd = currencySDK.formatCurrency(1234.56, 'USD', 'en-US');
console.log(usd); // "$1,234.56"
```

### Popular Currencies

```javascript
const popular = currencySDK.getPopularCurrencies();
console.log('Popular currencies:', popular);
// ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD']
```

## 💱 Currency Codes

### Major Currencies
- **USD** - US Dollar
- **EUR** - Euro
- **GBP** - British Pound
- **JPY** - Japanese Yen
- **CHF** - Swiss Franc
- **CAD** - Canadian Dollar
- **AUD** - Australian Dollar
- **CNY** - Chinese Yuan
- **SEK** - Swedish Krona
- **NOK** - Norwegian Krone

### Emerging Markets
- **BRL** - Brazilian Real
- **INR** - Indian Rupee
- **RUB** - Russian Ruble
- **ZAR** - South African Rand
- **TRY** - Turkish Lira
- **MXN** - Mexican Peso

### Cryptocurrencies (via Coinbase API)
- **BTC** - Bitcoin
- **ETH** - Ethereum
- **LTC** - Litecoin
- **BCH** - Bitcoin Cash
- **ADA** - Cardano

## 📊 Rate Limits

### Free Tier
- **ExchangeRate-API**: 2,000 requests/month
- **Coinbase API**: No strict limits for public endpoints

### Premium Tiers
- **Basic**: $9/month - 100,000 requests
- **Pro**: $29/month - 1,000,000 requests
- **Ultra**: $99/month - 10,000,000 requests

## ⚠️ Error Handling

```javascript
try {
  const conversion = await currencySDK.convertCurrency('USD', 'INVALID', 100);
} catch (error) {
  if (error.message.includes('not found')) {
    console.log('Invalid currency code');
  } else if (error.message.includes('quota')) {
    console.log('API quota exceeded');
  } else {
    console.log('Error:', error.message);
  }
}
```

## 📈 Example Use Cases

### Currency Calculator

```javascript
async function currencyCalculator(amount, from, to) {
  try {
    const result = await currencySDK.convertCurrency(from, to, amount);
    const formatted = currencySDK.formatCurrency(result.converted, to);
    
    console.log(`${amount} ${from} = ${formatted}`);
    console.log(`Rate: 1 ${from} = ${result.rate} ${to}`);
    
    return result;
  } catch (error) {
    console.error('Conversion failed:', error.message);
  }
}

await currencyCalculator(100, 'USD', 'EUR');
```

### Multi-Currency Dashboard

```javascript
async function getCurrencyDashboard(baseCurrency = 'USD') {
  const rates = await currencySDK.getLatestRates(baseCurrency);
  const popular = currencySDK.getPopularCurrencies();
  
  console.log(`\n=== ${baseCurrency} Exchange Rates ===`);
  console.log(`Last updated: ${rates.date}\n`);
  
  for (const currency of popular) {
    if (currency !== baseCurrency && rates.rates[currency]) {
      const rate = rates.rates[currency];
      const formatted = currencySDK.formatCurrency(rate, currency);
      console.log(`1 ${baseCurrency} = ${formatted}`);
    }
  }
}

await getCurrencyDashboard('EUR');
```

### Historical Analysis

```javascript
async function analyzeRateChange(base, target, days = 7) {
  const endDate = new Date().toISOString().split('T')[0];
  const startDate = new Date(Date.now() - days * 24 * 60 * 60 * 1000)
    .toISOString().split('T')[0];
  
  const timeSeries = await currencySDK.getTimeSeries(base, [target], startDate, endDate);
  
  const dates = Object.keys(timeSeries.timeseries).sort();
  const firstRate = timeSeries.timeseries[dates[0]][target];
  const lastRate = timeSeries.timeseries[dates[dates.length - 1]][target];
  
  const change = ((lastRate - firstRate) / firstRate * 100).toFixed(2);
  
  console.log(`${base}/${target} change over ${days} days: ${change}%`);
  return { firstRate, lastRate, change };
}

await analyzeRateChange('USD', 'EUR', 7);
```

## 🔗 Useful Links

- [ExchangeRate-API](https://exchangerate-api.com)
- [Coinbase Exchange Rates](https://developers.coinbase.com/api/v2#exchange-rates)
- [Currency Codes (ISO 4217)](https://en.wikipedia.org/wiki/ISO_4217)
- [Fixer.io](https://fixer.io) (Premium alternative)
- [CurrencyAPI](https://currencyapi.com) (Premium alternative)
