---
sidebar_position: 10
---

# SendGrid API Integration

The SendGrid SDK provides email delivery services with advanced features like templates, tracking, and analytics.

## 🔑 API Key Setup

### Getting Your API Key
1. Go to [SendGrid Console](https://app.sendgrid.com/)
2. Navigate to **Settings** → **API Keys**
3. Click **"Create API Key"**
4. Choose **"Restricted Access"** for security
5. Select permissions you need:
   - **Mail Send**: Full Access (required)
   - **Marketing**: Read/Write (for contacts)
   - **Templates**: Read/Write (for templates)
6. Copy your API key (save it immediately - you won't see it again!)

### Environment Setup
Add your API key to your environment variables:

```bash
SENDGRID_API_KEY=SG.your-actual-api-key-here
```

### Domain Authentication (Recommended)
Set up domain authentication for better deliverability:
1. Go to **Settings** → **Sender Authentication**
2. Click **"Authenticate Your Domain"**
3. Follow the DNS setup instructions

## 📖 SDK Usage

```javascript
import sendgridSDK from './backend/apis_sdk/sendgridSDK.js';
```

## 🚀 Available Methods

### Basic Email Sending

```javascript
// Send simple email
await sendgridSDK.sendEmail({
  to: 'recipient@example.com',
  from: 'sender@yourdomain.com',
  subject: 'Hello from SendGrid!',
  text: 'Plain text content',
  html: '<h1>HTML content</h1><p>This is a test email.</p>'
});
```

### Multiple Recipients

```javascript
// Send to multiple recipients
await sendgridSDK.sendEmail({
  to: [
    'user1@example.com',
    'user2@example.com',
    { email: 'user3@example.com', name: 'User Three' }
  ],
  from: 'sender@yourdomain.com',
  subject: 'Newsletter Update',
  html: '<h1>Weekly Newsletter</h1><p>Check out this week\'s updates!</p>'
});
```

### Advanced Email Options

```javascript
// Email with all options
await sendgridSDK.sendEmail({
  to: [
    { email: 'recipient@example.com', name: 'John Doe' }
  ],
  from: { email: 'sender@yourdomain.com', name: 'Your Company' },
  replyTo: 'noreply@yourdomain.com',
  subject: 'Advanced Email',
  text: 'Plain text version',
  html: '<h1>HTML Version</h1>',
  attachments: [
    {
      content: 'base64-encoded-content',
      filename: 'document.pdf',
      type: 'application/pdf',
      disposition: 'attachment'
    }
  ],
  categories: ['newsletter', 'marketing'],
  customArgs: {
    'campaign_id': 'spring_sale_2024',
    'user_id': '12345'
  },
  sendAt: Math.floor(Date.now() / 1000) + 3600, // Send in 1 hour
  batchId: 'batch-12345'
});
```

### Template Emails

```javascript
// Send using dynamic template
await sendgridSDK.sendTemplateEmail({
  to: 'recipient@example.com',
  from: 'sender@yourdomain.com',
  templateId: 'd-1234567890123456789',
  dynamicTemplateData: {
    firstName: 'John',
    lastName: 'Doe',
    orderNumber: 'ORD-12345',
    orderItems: [
      { name: 'Product 1', price: '$29.99' },
      { name: 'Product 2', price: '$19.99' }
    ],
    totalAmount: '$49.98'
  }
});
```

### Bulk Email Sending

```javascript
// Send personalized emails to multiple recipients
const recipients = [
  {
    email: 'john@example.com',
    name: 'John Doe',
    data: { firstName: 'John', plan: 'Pro' }
  },
  {
    email: 'jane@example.com', 
    name: 'Jane Smith',
    data: { firstName: 'Jane', plan: 'Basic' }
  }
];

await sendgridSDK.sendBulkTemplateEmail({
  from: 'sender@yourdomain.com',
  templateId: 'd-1234567890123456789',
  recipients: recipients
});
```

## 👥 Contact Management

### Create Contacts

```javascript
// Add single contact
const contact = await sendgridSDK.createContact({
  email: 'newuser@example.com',
  firstName: 'New',
  lastName: 'User',
  customFields: {
    city: 'San Francisco',
    age: 30
  }
});
console.log('Contact created:', contact.id);

// Add multiple contacts
const contacts = await sendgridSDK.createContacts([
  {
    email: 'user1@example.com',
    firstName: 'User',
    lastName: 'One'
  },
  {
    email: 'user2@example.com',
    firstName: 'User', 
    lastName: 'Two'
  }
]);
```

### Search and Manage Contacts

```javascript
// Search contacts
const contacts = await sendgridSDK.searchContacts({
  query: "email LIKE '%@example.com%' AND first_name = 'John'"
});

// Get all contacts
const allContacts = await sendgridSDK.getAllContacts();

// Update contact
await sendgridSDK.updateContact('contact-id', {
  firstName: 'Updated',
  lastName: 'Name',
  customFields: {
    subscription: 'premium'
  }
});

// Delete contact
await sendgridSDK.deleteContact('contact-id');
```

### Contact Lists

```javascript
// Create list
const list = await sendgridSDK.createList('VIP Customers');

// Add contacts to list
await sendgridSDK.addContactsToList(list.id, [
  'contact-id-1',
  'contact-id-2'
]);

// Remove contacts from list
await sendgridSDK.removeContactsFromList(list.id, [
  'contact-id-1'
]);

// Get lists
const lists = await sendgridSDK.getLists();
```

## 📋 Template Management

### Create Templates

```javascript
// Create email template
const template = await sendgridSDK.createTemplate({
  name: 'Welcome Email',
  generation: 'dynamic'
});

// Create template version
const version = await sendgridSDK.createTemplateVersion(template.id, {
  name: 'Version 1',
  subject: 'Welcome {{firstName}}!',
  htmlContent: `
    <h1>Welcome {{firstName}} {{lastName}}!</h1>
    <p>Thanks for signing up for our {{plan}} plan.</p>
    <a href="{{confirmationUrl}}">Confirm your email</a>
  `,
  textContent: `
    Welcome {{firstName}} {{lastName}}!
    Thanks for signing up for our {{plan}} plan.
    Confirm your email: {{confirmationUrl}}
  `,
  active: 1
});
```

### Manage Templates

```javascript
// Get all templates
const templates = await sendgridSDK.getTemplates();

// Get specific template
const template = await sendgridSDK.getTemplate('template-id');

// Update template
await sendgridSDK.updateTemplate('template-id', {
  name: 'Updated Welcome Email'
});

// Delete template
await sendgridSDK.deleteTemplate('template-id');
```

## 📊 Analytics and Tracking

### Email Statistics

```javascript
// Get email stats for date range
const stats = await sendgridSDK.getEmailStats({
  startDate: '2024-01-01',
  endDate: '2024-01-31',
  aggregatedBy: 'day'
});

console.log('Delivered:', stats.delivered);
console.log('Opens:', stats.opens);
console.log('Clicks:', stats.clicks);
console.log('Bounces:', stats.bounces);
```

### Bounce Management

```javascript
// Get bounces
const bounces = await sendgridSDK.getBounces({
  startTime: Math.floor(Date.now() / 1000) - 86400 // Last 24 hours
});

// Delete bounces
await sendgridSDK.deleteBounces(['email1@example.com', 'email2@example.com']);

// Get specific bounce
const bounce = await sendgridSDK.getBounce('user@example.com');
```

### Suppression Management

```javascript
// Get suppressions (unsubscribes)
const suppressions = await sendgridSDK.getSuppressions();

// Add to suppression list
await sendgridSDK.addSuppression('user@example.com');

// Remove from suppression list
await sendgridSDK.removeSuppression('user@example.com');

// Check if email is suppressed
const isSupressed = await sendgridSDK.checkSuppression('user@example.com');
```

## 🎨 Advanced Features

### Batch Processing

```javascript
// Create batch for scheduled sending
const batchId = await sendgridSDK.createBatch();

// Schedule emails with batch ID
await sendgridSDK.sendEmail({
  to: 'recipient@example.com',
  from: 'sender@yourdomain.com',
  subject: 'Scheduled Email',
  html: '<p>This email was scheduled</p>',
  batchId: batchId
});

// Cancel batch (if not sent yet)
await sendgridSDK.cancelBatch(batchId);

// Pause batch
await sendgridSDK.pauseBatch(batchId);

// Resume batch
await sendgridSDK.resumeBatch(batchId);
```

### Webhook Event Processing

```javascript
// Verify webhook signature (for incoming webhooks)
const isValid = sendgridSDK.verifyWebhookSignature(
  requestBody,
  signature,
  timestamp,
  publicKey
);

if (isValid) {
  // Process webhook events
  const events = JSON.parse(requestBody);
  events.forEach(event => {
    console.log(`Event: ${event.event} for ${event.email}`);
    
    switch(event.event) {
      case 'delivered':
        console.log('Email delivered successfully');
        break;
      case 'open':
        console.log('Email opened');
        break;
      case 'click':
        console.log('Link clicked:', event.url);
        break;
      case 'bounce':
        console.log('Email bounced:', event.reason);
        break;
      case 'unsubscribe':
        console.log('User unsubscribed');
        break;
    }
  });
}
```

### Custom Fields

```javascript
// Create custom field
const customField = await sendgridSDK.createCustomField({
  name: 'subscription_plan',
  fieldType: 'Text'
});

// Get custom fields
const customFields = await sendgridSDK.getCustomFields();

// Use custom fields in contacts
await sendgridSDK.createContact({
  email: 'user@example.com',
  customFields: {
    subscription_plan: 'premium',
    signup_date: '2024-01-15',
    lifetime_value: '299.99'
  }
});
```

## 🎯 Use Cases

### Welcome Email Series

```javascript
async function sendWelcomeSeries(userEmail, userData) {
  // Immediate welcome
  await sendgridSDK.sendTemplateEmail({
    to: userEmail,
    from: 'welcome@yourcompany.com',
    templateId: 'd-welcome-immediate',
    dynamicTemplateData: userData
  });

  // Day 3 follow-up (scheduled)
  await sendgridSDK.sendTemplateEmail({
    to: userEmail,
    from: 'welcome@yourcompany.com',
    templateId: 'd-welcome-day3',
    dynamicTemplateData: userData,
    sendAt: Math.floor(Date.now() / 1000) + (3 * 24 * 60 * 60)
  });

  // Day 7 follow-up (scheduled)
  await sendgridSDK.sendTemplateEmail({
    to: userEmail,
    from: 'welcome@yourcompany.com',
    templateId: 'd-welcome-day7',
    dynamicTemplateData: userData,
    sendAt: Math.floor(Date.now() / 1000) + (7 * 24 * 60 * 60)
  });
}
```

### Transaction Notifications

```javascript
async function sendOrderConfirmation(order) {
  await sendgridSDK.sendTemplateEmail({
    to: order.customerEmail,
    from: 'orders@yourstore.com',
    templateId: 'd-order-confirmation',
    dynamicTemplateData: {
      orderNumber: order.id,
      customerName: order.customerName,
      items: order.items,
      total: order.total,
      shippingAddress: order.shippingAddress,
      trackingUrl: `https://yourstore.com/track/${order.trackingNumber}`
    },
    categories: ['transactional', 'order-confirmation'],
    customArgs: {
      orderId: order.id,
      customerId: order.customerId
    }
  });
}
```

### Newsletter Campaign

```javascript
async function sendNewsletter(templateId, segmentId) {
  // Get contacts from specific segment
  const contacts = await sendgridSDK.searchContacts({
    query: `CONTAINS(list_ids, '${segmentId}')`
  });

  // Prepare bulk send
  const recipients = contacts.map(contact => ({
    email: contact.email,
    name: `${contact.first_name} ${contact.last_name}`,
    data: {
      firstName: contact.first_name,
      preferences: contact.custom_fields.preferences,
      location: contact.custom_fields.city
    }
  }));

  // Send to all recipients
  await sendgridSDK.sendBulkTemplateEmail({
    from: 'newsletter@yourcompany.com',
    templateId: templateId,
    recipients: recipients,
    categories: ['newsletter', 'marketing']
  });
}
```

## 📊 Rate Limits

SendGrid API rate limits:
- **Free Plan**: 100 emails/day
- **Essentials**: 40,000-100,000 emails/month
- **Pro**: 1.5M+ emails/month
- **API Requests**: 10,000 requests per hour

## ⚠️ Error Handling

```javascript
try {
  await sendgridSDK.sendEmail({
    to: 'invalid-email',
    from: 'sender@domain.com',
    subject: 'Test',
    text: 'Test content'
  });
} catch (error) {
  if (error.message.includes('invalid email')) {
    console.log('Invalid email address');
  } else if (error.message.includes('authentication')) {
    console.log('Invalid API key');
  } else if (error.message.includes('rate limit')) {
    console.log('Rate limit exceeded');
  } else {
    console.log('SendGrid error:', error.message);
  }
}
```

## 🔐 Best Practices

### Security
- Store API keys in environment variables
- Use restricted API keys with minimal permissions
- Rotate API keys regularly
- Implement webhook signature verification

### Deliverability
- Set up domain authentication
- Maintain good sender reputation
- Use double opt-in for subscriptions
- Monitor bounces and remove bad emails
- Implement proper unsubscribe handling

### Performance
- Use bulk sending for multiple recipients
- Batch API requests when possible
- Implement retry logic with exponential backoff
- Cache template IDs and contact lists

## 🔗 Useful Links

- [SendGrid Console](https://app.sendgrid.com/)
- [SendGrid API Documentation](https://docs.sendgrid.com/api-reference)
- [Dynamic Templates Guide](https://docs.sendgrid.com/ui/sending-email/how-to-send-an-email-with-dynamic-transactional-templates)
- [Webhook Event Reference](https://docs.sendgrid.com/for-developers/tracking-events/event)
- [Deliverability Best Practices](https://docs.sendgrid.com/ui/sending-email/deliverability)
