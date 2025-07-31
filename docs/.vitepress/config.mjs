import { defineConfig } from 'vitepress'
import en from './config/en.mjs'
import ja from './config/ja.mjs'

export default defineConfig({
  title: 'C3 CloudFront Cache Controller',
  description: 'WordPress plugin for managing CloudFront cache invalidation',
  base: '/c3-cloudfront-clear-cache/',
  
  locales: {
    root: {
      label: 'English',
      lang: 'en',
      title: 'C3 CloudFront Cache Controller',
      description: 'WordPress plugin for managing CloudFront cache invalidation',
      themeConfig: en.themeConfig
    },
    ja: {
      label: '日本語',
      lang: 'ja',
      link: '/ja/',
      title: 'C3 CloudFront Cache Controller',
      description: 'CloudFrontキャッシュ無効化を管理するWordPressプラグイン',
      themeConfig: ja.themeConfig
    }
  },

  themeConfig: {
    search: {
      provider: 'local'
    },
    logo: '/images/icon-256x256.jpg',
    
    socialLinks: [
      { icon: 'github', link: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache' }
    ],

    footer: {
      message: 'Released under the GPL License.',
      copyright: 'Copyright © 2025 DigitalCube Co., Ltd.'
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