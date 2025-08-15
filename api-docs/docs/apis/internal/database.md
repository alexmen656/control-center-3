---
sidebar_position: 7
---

# Database API

The Database API provides secure access to perform database operations including queries, inserts, updates, and table management.

## 🔑 Authentication

The Database API requires authentication using your project API key:

```javascript
const headers = {
  'Authorization': `Bearer YOUR_PROJECT_API_KEY`,
  'Content-Type': 'application/json'
};
```

## 📖 SDK Usage

```javascript
import databaseSDK from './backend/apis_sdk/databaseSDK.js';
```

## 🚀 Available Methods

### List Available Tables

```javascript
const tables = await databaseSDK.listTables();
console.log('Available tables:', tables.data);
```

### Query Data

```javascript
// Simple query
const users = await databaseSDK.query('users');

// Query with conditions
const activeUsers = await databaseSDK.query('users', 
  { status: 'active' },
  { 
    limit: 10,
    offset: 0,
    orderBy: 'created_at',
    orderDirection: 'DESC'
  }
);

// Complex query with multiple conditions
const filteredData = await databaseSDK.query('products', 
  { 
    category: 'electronics',
    price: { operator: '>', value: 100 },
    in_stock: true
  },
  {
    select: ['id', 'name', 'price', 'description'],
    limit: 20
  }
);
```

### Insert Data

```javascript
// Insert single record
const newUser = await databaseSDK.insert('users', {
  name: 'John Doe',
  email: 'john@example.com',
  status: 'active'
});

console.log('Inserted user ID:', newUser.data.id);

// Insert multiple records
const newProducts = await databaseSDK.insert('products', [
  {
    name: 'Product 1',
    price: 29.99,
    category: 'electronics'
  },
  {
    name: 'Product 2', 
    price: 49.99,
    category: 'electronics'
  }
]);
```

### Update Data

```javascript
// Update by ID
const updatedUser = await databaseSDK.update('users', 123, {
  name: 'Jane Doe',
  last_login: new Date().toISOString()
});

// Bulk update with conditions
const bulkUpdate = await databaseSDK.query('products', 
  { category: 'electronics' },
  {
    operation: 'update',
    data: { discount: 10 }
  }
);
```

### Delete Data

```javascript
// Delete by ID
const result = await databaseSDK.delete('users', 123);

// Soft delete (if supported)
const softDelete = await databaseSDK.update('users', 123, {
  deleted_at: new Date().toISOString(),
  status: 'deleted'
});
```

## 🔍 Advanced Query Options

### Operators

```javascript
// Comparison operators
const expensiveProducts = await databaseSDK.query('products', {
  price: { operator: '>', value: 100 }
});

const discountedProducts = await databaseSDK.query('products', {
  discount: { operator: 'BETWEEN', value: [10, 50] }
});

// String operations
const searchResults = await databaseSDK.query('articles', {
  title: { operator: 'LIKE', value: '%javascript%' }
});

// Array operations
const selectedCategories = await databaseSDK.query('products', {
  category: { operator: 'IN', value: ['electronics', 'books', 'clothing'] }
});
```

### Sorting and Pagination

```javascript
const paginatedResults = await databaseSDK.query('users', {}, {
  select: ['id', 'name', 'email', 'created_at'],
  orderBy: 'created_at',
  orderDirection: 'DESC',
  limit: 25,
  offset: 50  // Page 3 (50 = 25 * 2)
});
```

### Joins and Relations

```javascript
// Get users with their orders
const usersWithOrders = await databaseSDK.query('users', {}, {
  include: ['orders'],
  limit: 10
});

// Custom join query
const orderData = await databaseSDK.query('orders', {}, {
  select: [
    'orders.id',
    'orders.total',
    'users.name as customer_name',
    'products.name as product_name'
  ],
  joins: [
    { table: 'users', on: 'orders.user_id = users.id' },
    { table: 'order_items', on: 'orders.id = order_items.order_id' },
    { table: 'products', on: 'order_items.product_id = products.id' }
  ]
});
```

## 📊 Data Types & Validation

### Supported Data Types

```javascript
const productData = {
  name: 'String value',
  price: 29.99,              // Float
  quantity: 100,             // Integer  
  is_active: true,           // Boolean
  tags: ['tag1', 'tag2'],    // Array/JSON
  metadata: {                // Object/JSON
    color: 'blue',
    size: 'large'
  },
  created_at: new Date().toISOString()  // DateTime
};
```

### Validation Rules

```javascript
// The API automatically validates:
// - Required fields
// - Data types
// - String lengths
// - Numeric ranges
// - Email formats
// - Date formats
```

## 🛡️ Security Features

### SQL Injection Protection
All queries are automatically sanitized and use prepared statements.

### Access Control
```javascript
// Row-level security example
const userOrders = await databaseSDK.query('orders', {
  user_id: currentUserId  // Only access user's own orders
});
```

### Field Filtering
```javascript
// Only select safe fields
const publicUserData = await databaseSDK.query('users', {}, {
  select: ['id', 'name', 'avatar']  // Exclude sensitive fields
});
```

## 📈 Performance Optimization

### Indexing
```javascript
// Query on indexed fields for better performance
const indexedQuery = await databaseSDK.query('users', {
  email: 'user@example.com'  // Assuming email is indexed
});
```

### Efficient Pagination
```javascript
// Use cursor-based pagination for large datasets
const cursorResults = await databaseSDK.query('posts', {
  id: { operator: '>', value: lastSeenId }
}, {
  orderBy: 'id',
  limit: 20
});
```

### Batch Operations
```javascript
// Insert multiple records in one request
const batchInsert = await databaseSDK.insert('logs', [
  { level: 'info', message: 'Log 1' },
  { level: 'error', message: 'Log 2' },
  { level: 'warning', message: 'Log 3' }
]);
```

## ⚠️ Error Handling

```javascript
try {
  const result = await databaseSDK.query('users', { email: 'test@example.com' });
  console.log(result);
} catch (error) {
  if (error.message.includes('unauthorized')) {
    console.log('Invalid API key or insufficient permissions');
  } else if (error.message.includes('not found')) {
    console.log('Table or record not found');
  } else if (error.message.includes('validation')) {
    console.log('Data validation failed');
  } else {
    console.log('Database error:', error.message);
  }
}
```

## 📊 Rate Limits

- **Default**: 100 requests per minute per project
- **Burst**: Up to 200 requests in short bursts
- **Daily limit**: 10,000 requests per day

## 🎯 Example Use Cases

### User Management
```javascript
async function getUserProfile(userId) {
  const user = await databaseSDK.query('users', { id: userId }, {
    select: ['id', 'name', 'email', 'avatar', 'created_at']
  });
  
  return user.data[0];
}

async function updateUserProfile(userId, updates) {
  return await databaseSDK.update('users', userId, {
    ...updates,
    updated_at: new Date().toISOString()
  });
}
```

### Content Management
```javascript
async function getPublishedPosts(page = 1, limit = 10) {
  const offset = (page - 1) * limit;
  
  return await databaseSDK.query('posts', 
    { status: 'published' },
    {
      select: ['id', 'title', 'excerpt', 'author', 'published_at'],
      orderBy: 'published_at',
      orderDirection: 'DESC',
      limit,
      offset
    }
  );
}
```

### Analytics
```javascript
async function getPageViews(startDate, endDate) {
  return await databaseSDK.query('page_views', {
    created_at: {
      operator: 'BETWEEN',
      value: [startDate, endDate]
    }
  }, {
    select: ['page', 'COUNT(*) as views'],
    groupBy: 'page',
    orderBy: 'views',
    orderDirection: 'DESC'
  });
}
```

## 🔗 Related APIs

For complete API integration, consider combining database operations with:

- **User Management** - For user-specific operations and authentication
- **File Storage** - For handling file attachments and media
- **Analytics** - For event tracking and user behavior analysis

These APIs work together to provide a complete backend solution for your applications.
