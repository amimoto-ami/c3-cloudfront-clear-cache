---
layout: home

hero:
  name: "C3 CloudFront Cache Controller"
  text: "WordPressプラグイン"
  tagline: "WordPressサイトのCloudFrontキャッシュ無効化を効率的に管理"
  image:
    src: /images/icon-256x256.jpg
    alt: C3 CloudFront Cache Controller
  actions:
    - theme: brand
      text: はじめる
      link: /ja/guide/installation
    - theme: alt
      text: GitHubで見る
      link: https://github.com/amimoto-ami/c3-cloudfront-clear-cache

features:
  - icon: ⚡
    title: 自動無効化
    details: 投稿が公開または更新された際にCloudFrontキャッシュを自動的に無効化
  - icon: 🎯
    title: ターゲット無効化
    details: キャッシュ全体をクリアする代わりに特定のパスを無効化
  - icon: ⚙️
    title: 高度な設定
    details: フィルターとフックで無効化動作をカスタマイズ
  - icon: 📱
    title: WP-CLI対応
    details: コマンドラインからキャッシュ無効化を管理
  - icon: 🔒
    title: セキュア
    details: IAMロール、環境変数、カスタムAWS実装をサポート
  - icon: 📊
    title: ログとモニタリング
    details: デバッグとモニタリングのための包括的なログ機能
---

## クイックスタート

プラグインをインストールしてAWS CloudFront設定を構成します：

### インストール

```bash
# WP-CLIを使用（推奨）
wp plugin install c3-cloudfront-clear-cache --activate

# またはWordPress管理画面から：プラグイン > 新規追加 > 「C3 CloudFront Clear Cache」を検索
```

### 設定

インストール後、WordPress管理画面でプラグインを設定します：

1. **設定 > C3 CloudFront Cache** に移動
2. CloudFront Distribution IDを入力
3. AWS Access KeyとSecret Keyを入力
4. **変更を保存** をクリック

または、セキュリティを向上させるために`wp-config.php`で定数を定義できます：

```php
// wp-config.phpに追加
define( 'AWS_ACCESS_KEY_ID', 'your_access_key' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key' );
define( 'C3_DISTRIBUTION_ID', 'your_distribution_id' );
```

## C3 CloudFront Cache Controllerを選ぶ理由

WordPressをAWS CloudFrontの背後で実行する場合、サイトが更新されたときにキャッシュされたコンテンツを無効化する信頼できる方法が必要です。このプラグインは以下を提供します：

- **自動キャッシュ無効化** - コンテンツが変更されたとき
- **バッチ処理** - 効率的なCloudFront API使用のため
- **カスタム無効化パス** - 強力なフィルターを通じて
- **本番環境対応** - 包括的なエラーハンドリング

## WordPress.orgプラグイン

[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![WordPress Plugin Version](https://img.shields.io/wordpress/v/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/c3-cloudfront-clear-cache.svg)](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)

プラグインは[WordPress Plugin Directory](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)で利用可能です。

## コミュニティ

- **GitHub Issues**: [バグの報告と機能リクエスト](https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues)
- **WordPress Support**: [WordPress.orgでサポートを受ける](https://wordpress.org/support/plugin/c3-cloudfront-clear-cache/) 