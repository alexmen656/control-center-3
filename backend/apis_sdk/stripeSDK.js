// Stripe Payment API SDK
class StripeAPI {
  constructor() {
    this.baseUrl = 'https://api.stripe.com/v1';
    this.apiKey = process.env.STRIPE_SECRET_KEY || '';
  }

  async createCustomer(email, options = {}) {
    const formData = new URLSearchParams();
    formData.append('email', email);
    if (options.name) formData.append('name', options.name);
    if (options.phone) formData.append('phone', options.phone);
    if (options.description) formData.append('description', options.description);
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/customers`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async getCustomer(customerId) {
    const response = await fetch(`${this.baseUrl}/customers/${customerId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async updateCustomer(customerId, options = {}) {
    const formData = new URLSearchParams();
    if (options.email) formData.append('email', options.email);
    if (options.name) formData.append('name', options.name);
    if (options.phone) formData.append('phone', options.phone);
    if (options.description) formData.append('description', options.description);
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/customers/${customerId}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async createPaymentIntent(amount, currency = 'eur', options = {}) {
    const formData = new URLSearchParams();
    formData.append('amount', amount.toString());
    formData.append('currency', currency);
    if (options.customer) formData.append('customer', options.customer);
    if (options.description) formData.append('description', options.description);
    if (options.payment_method) formData.append('payment_method', options.payment_method);
    if (options.confirm) formData.append('confirm', options.confirm.toString());
    if (options.return_url) formData.append('return_url', options.return_url);
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/payment_intents`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async confirmPaymentIntent(paymentIntentId, options = {}) {
    const formData = new URLSearchParams();
    if (options.payment_method) formData.append('payment_method', options.payment_method);
    if (options.return_url) formData.append('return_url', options.return_url);

    const response = await fetch(`${this.baseUrl}/payment_intents/${paymentIntentId}/confirm`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async getPaymentIntent(paymentIntentId) {
    const response = await fetch(`${this.baseUrl}/payment_intents/${paymentIntentId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createSubscription(customerId, priceId, options = {}) {
    const formData = new URLSearchParams();
    formData.append('customer', customerId);
    formData.append('items[0][price]', priceId);
    if (options.trial_period_days) formData.append('trial_period_days', options.trial_period_days.toString());
    if (options.coupon) formData.append('coupon', options.coupon);
    if (options.default_payment_method) formData.append('default_payment_method', options.default_payment_method);
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/subscriptions`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async getSubscription(subscriptionId) {
    const response = await fetch(`${this.baseUrl}/subscriptions/${subscriptionId}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async cancelSubscription(subscriptionId, options = {}) {
    const formData = new URLSearchParams();
    if (options.cancellation_details) {
      if (options.cancellation_details.comment) {
        formData.append('cancellation_details[comment]', options.cancellation_details.comment);
      }
      if (options.cancellation_details.feedback) {
        formData.append('cancellation_details[feedback]', options.cancellation_details.feedback);
      }
    }

    const response = await fetch(`${this.baseUrl}/subscriptions/${subscriptionId}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async createProduct(name, options = {}) {
    const formData = new URLSearchParams();
    formData.append('name', name);
    if (options.description) formData.append('description', options.description);
    if (options.images) {
      options.images.forEach((image, index) => {
        formData.append(`images[${index}]`, image);
      });
    }
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/products`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async createPrice(productId, unitAmount, currency = 'eur', options = {}) {
    const formData = new URLSearchParams();
    formData.append('product', productId);
    formData.append('unit_amount', unitAmount.toString());
    formData.append('currency', currency);
    if (options.recurring) {
      formData.append('recurring[interval]', options.recurring.interval);
      if (options.recurring.interval_count) {
        formData.append('recurring[interval_count]', options.recurring.interval_count.toString());
      }
    }
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/prices`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async getProducts(options = {}) {
    const params = new URLSearchParams();
    if (options.limit) params.append('limit', options.limit.toString());
    if (options.starting_after) params.append('starting_after', options.starting_after);
    if (options.ending_before) params.append('ending_before', options.ending_before);

    const response = await fetch(`${this.baseUrl}/products?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async getPrices(options = {}) {
    const params = new URLSearchParams();
    if (options.limit) params.append('limit', options.limit.toString());
    if (options.product) params.append('product', options.product);
    if (options.active !== undefined) params.append('active', options.active.toString());

    const response = await fetch(`${this.baseUrl}/prices?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createInvoice(customerId, options = {}) {
    const formData = new URLSearchParams();
    formData.append('customer', customerId);
    if (options.description) formData.append('description', options.description);
    if (options.due_date) formData.append('due_date', options.due_date.toString());
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/invoices`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  async getCharges(options = {}) {
    const params = new URLSearchParams();
    if (options.limit) params.append('limit', options.limit.toString());
    if (options.customer) params.append('customer', options.customer);
    if (options.payment_intent) params.append('payment_intent', options.payment_intent);

    const response = await fetch(`${this.baseUrl}/charges?${params}`, {
      method: 'GET',
      headers: this.getHeaders()
    });
    return this.handleResponse(response);
  }

  async createRefund(chargeId, options = {}) {
    const formData = new URLSearchParams();
    formData.append('charge', chargeId);
    if (options.amount) formData.append('amount', options.amount.toString());
    if (options.reason) formData.append('reason', options.reason);
    if (options.metadata) {
      Object.keys(options.metadata).forEach(key => {
        formData.append(`metadata[${key}]`, options.metadata[key]);
      });
    }

    const response = await fetch(`${this.baseUrl}/refunds`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: formData
    });
    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Authorization': `Bearer ${this.apiKey}`,
      'Content-Type': 'application/x-www-form-urlencoded'
    };
  }

  async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error?.message || `Stripe API Error: ${response.status} ${response.statusText}`);
    }
    
    return data;
  }
}

export default new StripeAPI();
