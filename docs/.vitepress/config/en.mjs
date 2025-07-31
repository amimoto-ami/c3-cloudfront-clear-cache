export default {
  lang: 'en',
  title: 'C3 CloudFront Cache Controller',
  description: 'WordPress plugin for managing CloudFront cache invalidation',
  
  themeConfig: {
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
  }
} 