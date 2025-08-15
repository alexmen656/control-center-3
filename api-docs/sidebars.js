// @ts-check

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/**
 * Creating a sidebar enables you to:
 - create an ordered group of docs
 - render a sidebar for each doc of that group
 - provide next/previous navigation

 The sidebars can be generated from the filesystem, or explicitly defined here.

 Create as many sidebars as you want.

 @type {import('@docusaurus/plugin-content-docs').SidebarsConfig}
 */
const sidebars = {
  // API Documentation Sidebar
  apiSidebar: [
    'index',
    {
      type: 'category',
      label: '🏠 Internal CMS APIs',
      items: [
        'internal/database',
      ],
    },
    {
      type: 'category',
      label: '🤖 AI & Machine Learning',
      items: [
        'external/openai',
        'external/gemini',
      ],
    },
    {
      type: 'category',
      label: '👨‍💻 Development Tools',
      items: [
        'external/github',
      ],
    },
    {
      type: 'category',
      label: '💬 Communication',
      items: [
        'external/telegram',
        'external/discord',
        'external/sendgrid',
      ],
    },
    {
      type: 'category',
      label: '💳 Payment Processing',
      items: [
        'external/stripe',
      ],
    },
    {
      type: 'category',
      label: '📊 Data Services',
      items: [
        'external/weather',
        'external/news',
        'external/currency',
        'external/geolocation',
      ],
    },
    {
      type: 'category',
      label: '🛠️ Utility Services',
      items: [
        'external/qrcode',
      ],
    },
  ],
};export default sidebars;