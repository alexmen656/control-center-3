---
sidebar_position: 11
---

# Stripe API Integration

The Stripe SDK provides comprehensive payment processing, subscription management, and financial operations.

## API Key Setup

### Getting Your API Keys
1. Go to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Navigate to **Developers** → **API keys**
3. You'll see two types of keys:
   - **Publishable key** (starts with `pk_`) - Safe for client-side use
   - **Secret key** (starts with `sk_`) - Keep this secure, server-side only
4. Use **Test keys** for development, **Live keys** for production
5. Copy the **Secret key** for server-side integration

### Environment Setup
Add your secret key to your environment variables:

```bash
# For testing
STRIPE_SECRET_KEY=sk_test_your-test-key-here

# For production (when ready)
STRIPE_SECRET_KEY=sk_live_your-live-key-here
```

### Webhook Endpoints (Optional)
For real-time event handling:
1. Go to **Developers** → **Webhooks**
2. Click **"Add endpoint"**
3. Enter your endpoint URL
4. Select events you want to receive
5. Copy the **Signing secret** for webhook verification

## SDK Usage

```javascript
import stripeSDK from 'apis';
```

## Available Methods

### Customer Management

```javascript
// Create customer
const customer = await stripeSDK.createCustomer({
  email: 'customer@example.com',
  name: 'John Doe',
  phone: '+1234567890',
  address: {
    line1: '123 Main St',
    city: 'San Francisco',
    state: 'CA',
    postal_code: '94111',
    country: 'US'
  },
  metadata: {
    userId: '12345',
    source: 'website'
  }
});

console.log('Customer created:', customer.id);

// Get customer
const retrievedCustomer = await stripeSDK.getCustomer(customer.id);

// Update customer
const updatedCustomer = await stripeSDK.updateCustomer(customer.id, {
  name: 'John Smith',
  email: 'johnsmith@example.com'
});

// List customers
const customers = await stripeSDK.listCustomers({
  limit: 10,
  email: 'customer@example.com'
});

// Delete customer
await stripeSDK.deleteCustomer(customer.id);
```

### Payment Processing

```javascript
// Create payment intent
const paymentIntent = await stripeSDK.createPaymentIntent({
  amount: 2000, // $20.00 in cents
  currency: 'usd',
  customer: customer.id,
  description: 'Purchase from Your Store',
  metadata: {
    orderId: 'order_123',
    productId: 'prod_456'
  },
  automatic_payment_methods: {
    enabled: true
  }
});

console.log('Client secret:', paymentIntent.client_secret);

// Confirm payment intent
const confirmedPayment = await stripeSDK.confirmPaymentIntent(
  paymentIntent.id,
  {
    payment_method: 'pm_card_visa', // Test payment method
    return_url: 'https://yoursite.com/return'
  }
);

// Capture payment intent (for manual capture)
const capturedPayment = await stripeSDK.capturePaymentIntent(
  paymentIntent.id,
  {
    amount_to_capture: 2000
  }
);

// Cancel payment intent
await stripeSDK.cancelPaymentIntent(paymentIntent.id);
```

### Subscription Management

```javascript
// Create product
const product = await stripeSDK.createProduct({
  name: 'Premium Plan',
  description: 'Access to premium features',
  type: 'service',
  metadata: {
    category: 'subscription'
  }
});

// Create price for product
const price = await stripeSDK.createPrice({
  product: product.id,
  unit_amount: 1999, // $19.99
  currency: 'usd',
  recurring: {
    interval: 'month',
    interval_count: 1
  },
  metadata: {
    plan_type: 'premium'
  }
});

// Create subscription
const subscription = await stripeSDK.createSubscription({
  customer: customer.id,
  items: [
    {
      price: price.id,
      quantity: 1
    }
  ],
  payment_behavior: 'default_incomplete',
  payment_settings: {
    save_default_payment_method: 'on_subscription'
  },
  expand: ['latest_invoice.payment_intent']
});

// Update subscription
const updatedSubscription = await stripeSDK.updateSubscription(
  subscription.id,
  {
    cancel_at_period_end: true,
    metadata: {
      cancellation_reason: 'user_request'
    }
  }
);

// Cancel subscription
await stripeSDK.cancelSubscription(subscription.id);

// List subscriptions
const subscriptions = await stripeSDK.listSubscriptions({
  customer: customer.id,
  status: 'active'
});
```

### Payment Methods

```javascript
// Create payment method
const paymentMethod = await stripeSDK.createPaymentMethod({
  type: 'card',
  card: {
    number: '4242424242424242',
    exp_month: 12,
    exp_year: 2025,
    cvc: '123'
  },
  billing_details: {
    name: 'John Doe',
    email: 'john@example.com'
  }
});

// Attach payment method to customer
await stripeSDK.attachPaymentMethod(paymentMethod.id, {
  customer: customer.id
});

// Set as default payment method
await stripeSDK.updateCustomer(customer.id, {
  invoice_settings: {
    default_payment_method: paymentMethod.id
  }
});

// List customer payment methods
const paymentMethods = await stripeSDK.listPaymentMethods({
  customer: customer.id,
  type: 'card'
});

// Detach payment method
await stripeSDK.detachPaymentMethod(paymentMethod.id);
```

### Invoice Management

```javascript
// Create invoice
const invoice = await stripeSDK.createInvoice({
  customer: customer.id,
  description: 'Monthly subscription',
  metadata: {
    subscription_id: subscription.id
  }
});

// Add invoice item
await stripeSDK.createInvoiceItem({
  customer: customer.id,
  price: price.id,
  quantity: 1,
  invoice: invoice.id
});

// Finalize invoice
const finalizedInvoice = await stripeSDK.finalizeInvoice(invoice.id);

// Send invoice
await stripeSDK.sendInvoice(invoice.id);

// Pay invoice
const paidInvoice = await stripeSDK.payInvoice(invoice.id, {
  payment_method: paymentMethod.id
});

// List invoices
const invoices = await stripeSDK.listInvoices({
  customer: customer.id,
  status: 'open'
});
```

### Refunds and Disputes

```javascript
// Create refund
const refund = await stripeSDK.createRefund({
  payment_intent: paymentIntent.id,
  amount: 1000, // Partial refund: $10.00
  reason: 'requested_by_customer',
  metadata: {
    refund_reason: 'Product defective'
  }
});

// Get refund
const retrievedRefund = await stripeSDK.getRefund(refund.id);

// List refunds
const refunds = await stripeSDK.listRefunds({
  payment_intent: paymentIntent.id
});

// Update refund metadata
await stripeSDK.updateRefund(refund.id, {
  metadata: {
    processed_by: 'support_agent_123'
  }
});
```

### Balance and Transfers

```javascript
// Get account balance
const balance = await stripeSDK.getBalance();
console.log('Available balance:', balance.available);
console.log('Pending balance:', balance.pending);

// Get balance transactions
const transactions = await stripeSDK.listBalanceTransactions({
  limit: 10,
  type: 'payment'
});

// Create transfer (for Connect accounts)
const transfer = await stripeSDK.createTransfer({
  amount: 1000,
  currency: 'usd',
  destination: 'acct_connected_account_id',
  metadata: {
    order_id: 'order_123'
  }
});
```

## Analytics and Reporting

### Revenue Reports

```javascript
// Get charges for period
const charges = await stripeSDK.listCharges({
  created: {
    gte: Math.floor(Date.now() / 1000) - (30 * 24 * 60 * 60) // Last 30 days
  },
  limit: 100
});

// Calculate revenue
const revenue = charges.data.reduce((total, charge) => {
  return charge.paid ? total + charge.amount : total;
}, 0);

console.log(`Revenue (last 30 days): $${revenue / 100}`);

// Get subscription metrics
const activeSubscriptions = await stripeSDK.listSubscriptions({
  status: 'active'
});

const mrr = activeSubscriptions.data.reduce((total, sub) => {
  return total + (sub.items.data[0]?.price?.unit_amount || 0);
}, 0);

console.log(`Monthly Recurring Revenue: $${mrr / 100}`);
```

### Customer Analytics

```javascript
async function getCustomerMetrics(customerId) {
  const customer = await stripeSDK.getCustomer(customerId);
  const subscriptions = await stripeSDK.listSubscriptions({
    customer: customerId
  });
  const charges = await stripeSDK.listCharges({
    customer: customerId
  });

  const totalSpent = charges.data.reduce((sum, charge) => {
    return charge.paid ? sum + charge.amount : sum;
  }, 0);

  return {
    customerSince: new Date(customer.created * 1000),
    totalSpent: totalSpent / 100,
    activeSubscriptions: subscriptions.data.filter(s => s.status === 'active').length,
    lifetimeValue: totalSpent / 100
  };
}
```

## Advanced Features

### Webhooks Processing

```javascript
// Verify webhook signature
function verifyWebhook(payload, signature, secret) {
  return stripeSDK.verifyWebhookSignature(payload, signature, secret);
}

// Process webhook events
async function handleWebhook(event) {
  switch (event.type) {
    case 'payment_intent.succeeded':
      console.log('Payment succeeded:', event.data.object.id);
      // Update order status, send confirmation email, etc.
      break;
      
    case 'payment_intent.payment_failed':
      console.log('Payment failed:', event.data.object.id);
      // Notify customer, retry payment, etc.
      break;
      
    case 'customer.subscription.created':
      console.log('New subscription:', event.data.object.id);
      // Provision access, send welcome email, etc.
      break;
      
    case 'customer.subscription.deleted':
      console.log('Subscription cancelled:', event.data.object.id);
      // Revoke access, send cancellation email, etc.
      break;
      
    case 'invoice.payment_succeeded':
      console.log('Invoice paid:', event.data.object.id);
      // Extend subscription, send receipt, etc.
      break;
      
    case 'invoice.payment_failed':
      console.log('Invoice payment failed:', event.data.object.id);
      // Retry payment, notify customer, etc.
      break;
      
    default:
      console.log('Unhandled event type:', event.type);
  }
}
```

### Coupons and Discounts

```javascript
// Create coupon
const coupon = await stripeSDK.createCoupon({
  id: 'SAVE20',
  percent_off: 20,
  duration: 'once',
  max_redemptions: 100,
  metadata: {
    campaign: 'summer_sale'
  }
});

// Apply coupon to subscription
const discountedSubscription = await stripeSDK.createSubscription({
  customer: customer.id,
  items: [{ price: price.id }],
  coupon: coupon.id
});

// Create promotion code
const promotionCode = await stripeSDK.createPromotionCode({
  coupon: coupon.id,
  code: 'SUMMER2024'
});
```

### Connect Platform Features

```javascript
// Create connected account
const account = await stripeSDK.createAccount({
  type: 'express',
  country: 'US',
  email: 'seller@example.com',
  capabilities: {
    card_payments: { requested: true },
    transfers: { requested: true }
  }
});

// Create account link for onboarding
const accountLink = await stripeSDK.createAccountLink({
  account: account.id,
  refresh_url: 'https://yoursite.com/reauth',
  return_url: 'https://yoursite.com/success',
  type: 'account_onboarding'
});

// Create payment with application fee
const paymentWithFee = await stripeSDK.createPaymentIntent({
  amount: 2000,
  currency: 'usd',
  application_fee_amount: 200, // $2.00 platform fee
  transfer_data: {
    destination: account.id
  }
});
```

## Use Cases

### E-commerce Checkout

```javascript
async function createCheckoutSession(items, customerEmail) {
  const session = await stripeSDK.createCheckoutSession({
    payment_method_types: ['card'],
    line_items: items.map(item => ({
      price_data: {
        currency: 'usd',
        product_data: {
          name: item.name,
          description: item.description,
          images: [item.image]
        },
        unit_amount: item.price * 100
      },
      quantity: item.quantity
    })),
    mode: 'payment',
    customer_email: customerEmail,
    success_url: 'https://yoursite.com/success?session_id={CHECKOUT_SESSION_ID}',
    cancel_url: 'https://yoursite.com/cancel',
    metadata: {
      orderId: generateOrderId()
    }
  });

  return session.url;
}
```

### Subscription Billing

```javascript
async function setupSubscriptionBilling(customerId, priceId, trialDays = 0) {
  const subscription = await stripeSDK.createSubscription({
    customer: customerId,
    items: [{ price: priceId }],
    trial_period_days: trialDays,
    payment_behavior: 'default_incomplete',
    payment_settings: {
      save_default_payment_method: 'on_subscription'
    },
    expand: ['latest_invoice.payment_intent']
  });

  return {
    subscriptionId: subscription.id,
    clientSecret: subscription.latest_invoice.payment_intent.client_secret
  };
}
```

### Marketplace Payments

```javascript
async function processMarketplacePayment(amount, platformFee, sellerId) {
  const paymentIntent = await stripeSDK.createPaymentIntent({
    amount: amount,
    currency: 'usd',
    application_fee_amount: platformFee,
    transfer_data: {
      destination: sellerId
    },
    metadata: {
      marketplace_transaction: 'true'
    }
  });

  return paymentIntent;
}
```

## Rate Limits

Stripe API rate limits:
- **Standard**: 100 requests per second per account
- **Read operations**: Higher limits
- **Write operations**: Standard limits
- **Test mode**: Same limits as live mode

## Error Handling

```javascript
try {
  await stripeSDK.createPaymentIntent({
    amount: 2000,
    currency: 'usd'
  });
} catch (error) {
  if (error.type === 'StripeCardError') {
    console.log('Card declined:', error.decline_code);
  } else if (error.type === 'StripeInvalidRequestError') {
    console.log('Invalid request:', error.message);
  } else if (error.type === 'StripeAPIError') {
    console.log('Stripe API error:', error.message);
  } else if (error.type === 'StripeConnectionError') {
    console.log('Network error:', error.message);
  } else if (error.type === 'StripeAuthenticationError') {
    console.log('Authentication failed - check API key');
  } else {
    console.log('Unknown error:', error.message);
  }
}
```

## Security Best Practices

### API Key Security
- Never expose secret keys in client-side code
- Use environment variables for API keys
- Rotate keys regularly
- Use restricted API keys when possible

### Webhook Security
- Always verify webhook signatures
- Use HTTPS endpoints only
- Implement idempotency for webhook handling
- Store and validate webhook timestamps

### PCI Compliance
- Never store raw card data
- Use Stripe Elements for secure card collection
- Implement proper error handling
- Log security events

## Useful Links

- [Stripe Dashboard](https://dashboard.stripe.com/)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Stripe Elements](https://stripe.com/docs/stripe-js)
- [Webhook Testing](https://stripe.com/docs/webhooks/test)
- [Connect Platform Guide](https://stripe.com/docs/connect)
- [Test Card Numbers](https://stripe.com/docs/testing#cards)
