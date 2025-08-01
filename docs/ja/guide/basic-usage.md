# 基本的な使用方法

設定が完了すると、C3 CloudFront Cache ControllerはWordPressサイトのキャッシュ無効化を自動的に管理します。このガイドでは基本的な機能と一般的なユースケースについて説明します。

## 自動無効化

プラグインは以下のシナリオでCloudFrontキャッシュを自動的に無効化します：

### 投稿ステータスの変更

- **投稿の公開**: 投稿URLと関連するアーカイブページを無効化
- **公開済み投稿の更新**: 投稿URLを無効化
- **投稿のゴミ箱への移動**: 投稿URLとアーカイブを無効化
- **投稿ステータスの変更**: 遷移に基づいて関連するURLを無効化

### サポートされている投稿タイプ

デフォルトで、プラグインは以下で動作します：
- 投稿（`post`）
- 固定ページ（`page`）
- カスタム投稿タイプ（設定可能）

## 手動無効化

### WordPress管理画面から

1. **設定 > C3 CloudFront Cache** に移動
2. **手動無効化**セクションを見つける
3. 無効化するパスを入力（1行に1つ）：
   ```
   /
   /about/
   /contact/
   /wp-content/themes/mytheme/style.css
   ```
4. **今すぐ無効化** をクリック

### ワイルドカード無効化

すべてのキャッシュをクリアするには：
```
/*
```

::: warning コストの考慮事項
ワイルドカード無効化（`/*`）はキャッシュ全体のクリアとしてカウントされ、無料の無効化クォータを多く使用します。可能な限り特定のパスを使用してください。
:::

## 何が無効化されるか

投稿が公開または更新されたとき、プラグインは以下を無効化します：

### 単一投稿
- 投稿のパーマリンク（例：`/my-post/`）
- ホームページ（`/`）
- カテゴリアーカイブページ
- タグアーカイブページ
- 著者アーカイブページ
- 日付ベースのアーカイブページ

### 無効化パスの例

「News」カテゴリの「My First Post」という投稿の場合：
```
/my-first-post/
/
/category/news/
/author/john-doe/
/2024/
/2024/01/
/2024/01/15/
```

## 無効化パスのカスタマイズ

### すべてのパスをオーバーライド

すべての自動無効化パスを置き換える：

```php
add_filter('c3_invalidation_items', function($items) {
    // すべてをクリア
    return array('/*');
});
```

### カスタムパスを追加

自動無効化に追加のパスを追加：

```php
add_filter('c3_invalidation_items', function($items, $post) {
    // 特定の投稿にカスタムパスを追加
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/products/';
    }
    return $items;
}, 10, 2);
```

### 条件付き無効化

投稿プロパティに基づいて無効化をカスタマイズ：

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_name === 'special-page') {
        // この投稿の特定のパスのみを無効化
        return ['/special-page/', '/'];
    }
    return $items;
}, 10, 2);
```

## バッチ処理

プラグインは大量の無効化リクエストを効率的に処理します：

### 動作方法

1. **即座の処理**: 最大100パス（設定可能）が即座に無効化されます
2. **バッチ処理**: 追加のパスはバックグラウンド処理のためにキューに入れられます
3. **Cronジョブ**: WordPress cronが1分ごとにキューに入れられた無効化を処理します

### バッチの監視

無効化ステータスを確認：

1. **設定 > C3 CloudFront Cache** に移動
2. **無効化ログ**セクションを表示
3. 最近の無効化リクエストとそのステータスを確認

## パフォーマンスの考慮事項

### 無効化制限

- **AWS CloudFront**: 無効化リクエストあたり1,000パス
- **プラグインデフォルト**: バッチあたり100パス（設定可能）
- **無料ティア**: 月1,000回の無効化

### パフォーマンスの最適化

```php
// 高トラフィックサイトのバッチサイズを増加
add_filter('c3_invalidation_item_limits', function($limits) {
    return 500; // バッチあたりより多くのパスを処理
});

// 無効化頻度を調整
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5; // 1分ではなく5分ごとに処理
});
```

## 一般的なユースケース

### Eコマースサイト

WooCommerceやその他のEコマースプラットフォーム用：

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/cart/';
        $items[] = '/checkout/';
        
        // カテゴリページをクリア
        $categories = wp_get_post_terms($post->ID, 'product_cat');
        foreach ($categories as $category) {
            $items[] = get_term_link($category);
        }
    }
    return $items;
}, 10, 2);
```

### ニュース/ブログサイト

複雑なアーカイブ構造を持つコンテンツ重視のサイト用：

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'post') {
        // 常にホームページとメインブログページをクリア
        $items[] = '/';
        $items[] = '/blog/';
        
        // RSSフィードをクリア
        $items[] = '/feed/';
        $items[] = '/comments/feed/';
    }
    return $items;
}, 10, 2);
```

### 多言語サイト

WPMLやPolylangサイト用：

```php
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post) {
        // 翻訳を取得し、すべての言語版を無効化
        $translations = pll_get_post_translations($post->ID);
        foreach ($translations as $lang => $translation_id) {
            if ($translation_id) {
                $items[] = get_permalink($translation_id);
            }
        }
    }
    return $items;
}, 10, 2);
```

## デバッグ

### ログの有効化

```php
// wp-config.phpまたはテーマのfunctions.phpに追加
add_filter('c3_log_invalidation_list', '__return_true');
```

### ログの確認

1. **設定 > C3 CloudFront Cache** に移動
2. **無効化ログ**セクションを表示
3. エラーメッセージや失敗した無効化を確認

### 手動テスト

WP-CLIで無効化をテスト：

```bash
wp c3 flush 1
```

## 無効化詳細ビュー

バージョン7.1.0以降、プラグインには各無効化リクエストに関する包括的な情報を提供する強化された無効化詳細ビューが含まれています。

### 無効化詳細へのアクセス

1. **設定 > C3 CloudFront Cache** に移動
2. **無効化ログ** セクションに移動
3. 任意の無効化エントリをクリックして詳細情報を表示

### 確認できる内容

詳細ビューには以下が表示されます：

- **無効化ID**: AWS CloudFront無効化リクエストID
- **ステータス**: 現在のステータス（進行中、完了、失敗）
- **作成時刻**: 無効化が開始された時刻
- **パス**: 無効化されたパスの完全なリスト
- **進行状況**: CloudFrontからのリアルタイムステータス更新
- **エラー詳細**: 該当する場合、詳細なエラー情報

### 機能

**リアルタイムステータス更新:**
- 無効化ステータスの自動更新
- 大きな無効化バッチの進行状況追跡
- 完了通知

**パス詳細:**
- 無効化されたURLの完全なリスト
- パスの検証とフォーマット
- 重複パスの検出

**エラーハンドリング:**
- 権限エラーの適切な処理
- トラブルシューティング用の詳細なエラーメッセージ
- 失敗した無効化の再試行提案

### 権限に関する考慮事項

詳細ビューで限定的な情報しか表示されない場合：
- AWS認証情報に `cloudfront:GetInvalidation` 権限があることを確認してください
- プラグインは詳細情報にアクセスできない場合も適切に処理します
- 詳細ビューの権限がない場合でも、基本的な無効化機能は継続して動作します

## 次のステップ

- [高度なフィルターとフック](/ja/development/filters)について学ぶ
- [WP-CLIコマンド](/ja/guide/wp-cli)を探索する
- より多くの例については[トラブルシューティングガイド](/ja/guide/troubleshooting)を参照 