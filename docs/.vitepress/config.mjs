import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'C3 CloudFront Cache Controller',
  description: 'WordPress plugin for managing CloudFront cache invalidation',
  base: '/c3-cloudfront-clear-cache/',
  
  themeConfig: {
    logo: '/logo.svg',
    
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'API', link: '/api/filters' },
      { text: 'Examples', link: '/examples/basic-usage' },
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
            { text: 'Filters & Hooks', link: '/guide/filters' },
            { text: 'WP-CLI Commands', link: '/guide/wp-cli' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' }
          ]
        }
      ],
      
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Filters', link: '/api/filters' },
            { text: 'Classes', link: '/api/classes' },
            { text: 'WP-CLI', link: '/api/wp-cli' }
          ]
        }
      ],
      
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Basic Usage', link: '/examples/basic-usage' },
            { text: 'Custom Invalidation', link: '/examples/custom-invalidation' },
            { text: 'Advanced Configuration', link: '/examples/advanced-config' },
            { text: 'Integration Examples', link: '/examples/integration' }
          ]
        }
      ],
      
      '/development/': [
        {
          text: 'Development',
          items: [
            { text: 'Contributing', link: '/development/contributing' },
            { text: 'Testing', link: '/development/testing' },
            { text: 'Architecture', link: '/development/architecture' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache' }
    ],

    footer: {
      message: 'Released under the GPL License.',
      copyright: 'Copyright © 2024 Hidetaka Okamoto'
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
    ['link', { rel: 'icon', href: '/c3-cloudfront-clear-cache/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#3c4043' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:locale', content: 'en' }],
    ['meta', { property: 'og:title', content: 'C3 CloudFront Cache Controller | WordPress Plugin Documentation' }],
    ['meta', { property: 'og:site_name', content: 'C3 CloudFront Cache Controller' }],
    ['meta', { property: 'og:url', content: 'https://amimoto-ami.github.io/c3-cloudfront-clear-cache/' }]
  ]
})