// @ts-check

/**
 * APIs Sidebar Configuration  
 * @type {import('@docusaurus/plugin-content-docs').SidebarsConfig}
 */
const sidebars = {
  apisSidebar: [
    'index',
    {
      type: 'category',
      label: 'Internal APIs',
      items: [
        'internal/database',
      ],
    },
    {
      type: 'category',
      label: 'AI & Machine Learning',
      items: [
        'external/openai',
        'external/gemini',
      ],
    },
    {
      type: 'category',
      label: 'Development Tools',
      items: [
        'external/github',
      ],
    },
    {
      type: 'category',
      label: 'Communication',
      items: [
        'external/telegram',
        'external/discord',
        'external/sendgrid',
      ],
    },
    {
      type: 'category',
      label: 'Payment Processing',
      items: [
        'external/stripe',
      ],
    },
    {
      type: 'category',
      label: 'Data Services',
      items: [
        'external/weather',
        'external/news',
        'external/currency',
        'external/geolocation',
      ],
    },
    {
      type: 'category',
      label: 'Utility Services',
      items: [
        'external/qrcode',
      ],
    },
  ],
};

export default sidebars;
