import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'C3 CloudFront Cache Controller',
  description: 'WordPress plugin for managing CloudFront cache invalidation',
  base: '/c3-cloudfront-clear-cache/',
  
  themeConfig: {
    search: {
      provider: 'local'
    },
    logo: '/logo.svg',
    
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Development', link: '/development/contributing' },
      { 
        text: 'GitHub', 
        link: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache' 
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Basic Usage', link: '/guide/basic-usage' }
          ]
        },
        {
          text: 'Advanced',
          items: [
            { text: 'WP-CLI Commands', link: '/guide/wp-cli' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' }
          ]
        }
      ],
      
      '/development/': [
        {
          text: 'Development',
          items: [
            { text: 'Contributing', link: '/development/contributing' },
            { text: 'Filters & Hooks', link: '/development/filters' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache' }
    ],

    footer: {
      message: 'Released under the GPL License.',
      copyright: 'Copyright © 2025 DigitalCube Co., Ltd.'
    },

    editLink: {
      pattern: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache/edit/master/docs/:path',
      text: 'Edit this page on GitHub'
    },

    lastUpdated: {
      text: 'Updated at',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  },

  head: [
    ['link', { rel: 'icon', href: '/c3-cloudfront-clear-cache/images/icon-256x256.jpg' }],
    ['meta', { name: 'theme-color', content: '#3c4043' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:locale', content: 'en' }],
    ['meta', { property: 'og:title', content: 'C3 CloudFront Cache Controller | WordPress Plugin Documentation' }],
    ['meta', { property: 'og:site_name', content: 'C3 CloudFront Cache Controller' }],
    ['meta', { property: 'og:url', content: 'https://amimoto-ami.github.io/c3-cloudfront-clear-cache/' }]
  ]
})