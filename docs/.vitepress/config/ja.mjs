export default {
  lang: 'ja',
  title: 'C3 CloudFront Cache Controller',
  description: 'CloudFrontキャッシュ無効化を管理するWordPressプラグイン',
  
  themeConfig: {
    nav: [
      { text: 'ガイド', link: '/ja/guide/installation' },
      { text: '開発', link: '/ja/development/contributing' },
      { 
        text: 'GitHub', 
        link: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache' 
      }
    ],

    sidebar: {
      '/ja/guide/': [
        {
          text: 'はじめに',
          items: [
            { text: 'インストール', link: '/ja/guide/installation' },
            { text: '設定', link: '/ja/guide/configuration' },
            { text: '基本的な使用方法', link: '/ja/guide/basic-usage' }
          ]
        },
        {
          text: '高度な設定',
          items: [
            { text: 'WP-CLIコマンド', link: '/ja/guide/wp-cli' },
            { text: 'トラブルシューティング', link: '/ja/guide/troubleshooting' }
          ]
        }
      ],
      
      '/ja/development/': [
        {
          text: '開発',
          items: [
            { text: 'コントリビューション', link: '/ja/development/contributing' },
            { text: 'フィルターとフック', link: '/ja/development/filters' }
          ]
        }
      ]
    },

    editLink: {
      pattern: 'https://github.com/amimoto-ami/c3-cloudfront-clear-cache/edit/master/docs/:path',
      text: 'GitHubでこのページを編集'
    },

    lastUpdated: {
      text: '更新日時',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  }
} 