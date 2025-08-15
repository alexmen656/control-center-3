// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import {themes as prismThemes} from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Control Center Documentation',
  tagline: 'Comprehensive documentation for CMS and API integration',
  favicon: 'img/logo.png',

  // Future flags, see https://docusaurus.io/docs/api/docusaurus-config#future
  future: {
    v4: true, // Improve compatibility with the upcoming Docusaurus v4
  },

  // Set the production url of your site here
  url: 'https://alex.polan.sk',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/control-center/api-docs/',

  // GitHub pages deployment config.
  // If you aren't using GitHub pages, you don't need these.
  organizationName: 'alexmen656', // Usually your GitHub org/user name.
  projectName: 'control-center-3', // Usually your repo name.

  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  plugins: [
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'apis',
        path: 'docs/apis',
        routeBasePath: 'apis',
        sidebarPath: './sidebars-apis.js',
        editUrl: 'https://github.com/alexmen656/control-center-3/tree/main/api-docs/',
      },
    ],
  ],

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          id: 'default',
          path: 'docs/cms',
          routeBasePath: 'cms',
          sidebarPath: './sidebars-cms.js',
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl:
            'https://github.com/alexmen656/control-center-3/tree/main/api-docs/',
        },
        blog: {
          showReadingTime: true,
          feedOptions: {
            type: ['rss', 'atom'],
            xslt: true,
          },
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl:
            'https://github.com/alexmen656/control-center-3/tree/main/api-docs/',
          // Useful options to enforce blogging best practices
          onInlineTags: 'warn',
          onInlineAuthors: 'warn',
          onUntruncatedBlogPosts: 'warn',
        },
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      // Replace with your project's social card
      image: 'img/cms-api-social-card.jpg',
      navbar: {
        title: 'API Docs',
        logo: {
          alt: 'CMS Logo',
          src: 'img/logo.png',
        },
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'cmsSidebar',
            position: 'left',
            label: 'CMS',
            docsPluginId: 'default',
          },
          {
            type: 'docSidebar',
            sidebarId: 'apisSidebar',
            position: 'left',
            label: 'APIs',
            docsPluginId: 'apis',
          },
          {to: '/blog', label: 'Changelog', position: 'left'},
          {
            href: 'https://github.com/alexmen656/control-center-3',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'CMS Documentation',
            items: [
              {
                label: 'Getting Started',
                to: '/cms',
              },
              {
                label: 'Projects',
                to: '/cms/projects',
              },
              {
                label: 'Services',
                to: '/cms/services',
              },
              {
                label: 'Codespaces',
                to: '/cms/codespaces',
              },
            ],
          },
          {
            title: 'API Documentation',
            items: [
              {
                label: 'APIs Overview',
                to: '/apis',
              },
              {
                label: 'External APIs',
                to: '/apis/external/openai',
              },
              {
                label: 'Internal APIs',
                to: '/apis/internal/database',
              },
            ],
          },
          {
            title: 'Resources',
            items: [
              {
                label: 'CMS Platform',
                href: 'https://alex.polan.sk/control-center',
              },
              {
                label: 'Support',
                href: 'https://github.com/alexmen656/control-center-3/issues',
              },
              {
                label: 'Status',
                href: 'https://status.alex.polan.sk',
              },
            ],
          },
          {
            title: 'Development',
            items: [
              {
                label: 'Changelog',
                to: '/blog',
              },
              {
                label: 'GitHub',
                href: 'https://github.com/alexmen656/control-center-3',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Alex Polan. Built with Docusaurus.`,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
      },
    }),
};

export default config;
