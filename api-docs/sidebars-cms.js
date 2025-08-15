// @ts-check

/**
 * CMS Features Sidebar Configuration
 * @type {import('@docusaurus/plugin-content-docs').SidebarsConfig}
 */
const sidebars = {
  cmsSidebar: [
    'index',
    {
      type: 'category',
      label: 'Core Features',
      items: [
        'projects',
        'services', 
        'codespaces',
      ],
    },
  ],
};

export default sidebars;
