# フィルターとフックAPIリファレンス

C3 CloudFront Cache Controllerは、無効化動作をカスタマイズするための強力なフィルターとフックを提供します。この包括的なリファレンスでは、開発者向けのすべての利用可能なカスタマイズオプションについて説明します。

## コアフィルター

### `c3_invalidation_items`

コンテンツが変更されたときに無効化されるパスをカスタマイズします。

**フックタイプ:** フィルター  
**バージョン:** 1.0.0  
**パラメータ:**
- `$items` (array): 無効化するパスの配列
- `$post` (WP_Post|null): 無効化をトリガーした投稿オブジェクト

**戻り値:** `array` - 無効化するパスの修正された配列

**例:**

```php
// すべての無効化パスを置き換え
add_filter('c3_invalidation_items', function($items) {
    return array('/*'); // キャッシュ全体をクリア
});

// カスタムパスを追加
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_type === 'product') {
        $items[] = '/shop/';
        $items[] = '/products/featured/';
    }
    return $items;
}, 10, 2);

// 条件付き無効化
add_filter('c3_invalidation_items', function($items, $post) {
    if ($post && $post->post_name === 'homepage-hero') {
        // この特定の投稿のホームページのみを無効化
        return ['/'];
    }
    return $items;
}, 10, 2);
```

### `c3_credential`

AWS認証情報をプログラムでオーバーライドします。

**フックタイプ:** フィルター  
**バージョン:** 1.0.0  
**パラメータ:**
- `$credentials` (array): 現在の認証情報設定

**戻り値:** `array` - 修正された認証情報設定

**認証情報配列構造:**
```php
[
    'key' => 'AWS_ACCESS_KEY_ID',
    'secret' => 'AWS_SECRET_ACCESS_KEY',
    'token' => 'AWS_SESSION_TOKEN' // オプション
]
```

**例:**

```php
add_filter('c3_credential', function($credentials) {
    // ステージング環境で異なる認証情報を使用
    if (wp_get_environment_type() === 'staging') {
        return [
            'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
            'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
            'token' => getenv('STAGING_AWS_SESSION_TOKEN')
        ];
    }
    return $credentials;
});
```

## パフォーマンスフィルター

### `c3_invalidation_interval`

無効化バッチが処理される頻度を制御します。

**フックタイプ:** フィルター  
**バージョン:** 1.0.0  
**パラメータ:**
- `$interval_minutes` (int): 分単位の間隔（デフォルト: 1）

**戻り値:** `int` - 分単位の修正された間隔

**例:**

```php
// 5分ごとに無効化を処理
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5;
});

// サイトトラフィックに基づく動的間隔
add_filter('c3_invalidation_interval', function($interval_minutes) {
    $current_hour = (int) current_time('H');
    
    // 営業時間中はより頻繁に
    if ($current_hour >= 9 && $current_hour <= 17) {
        return 1; // 毎分
    } else {
        return 5; // 5分ごと
    }
});
```

### `c3_invalidation_cron_interval`

失敗した無効化の再試行間隔を制御します。

**フックタイプ:** フィルター  
**バージョン:** 6.0.0  
**パラメータ:**
- `$interval_minutes` (int): 分単位の再試行間隔（デフォルト: 無効化で1分、cronで10分）

**戻り値:** `int` - 分単位の修正された再試行間隔

**例:**

```php
// 失敗した無効化を15分ごとに再試行
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    return 15;
});
```

## 高度なフィルター

### `c3_invalidation_batch_size`

無効化バッチあたりの最大パス数を制御します。

**フックタイプ:** フィルター  
**バージョン:** 3.0.0  
**パラメータ:**
- `$batch_size` (int): バッチあたりの最大パス数（デフォルト: 1000）

**戻り値:** `int` - 修正されたバッチサイズ

**例:**

```php
// より高速な処理のためにバッチサイズを削減
add_filter('c3_invalidation_cron_interval', function($batch_size) {
    return 500;
});
```

### `c3_invalidation_timeout`

AWS APIリクエストのタイムアウトを制御します。

**フックタイプ:** フィルター  
**バージョン:** 4.0.0  
**パラメータ:**
- `$timeout` (int): 秒単位のタイムアウト（デフォルト: 30）

**戻り値:** `int` - 修正されたタイムアウト

**例:**

```php
// 遅い接続のためにタイムアウトを増加
add_filter('c3_invalidation_timeout', function($timeout) {
    return 60;
});
```

### `c3_invalidation_retry_attempts`

失敗した無効化の再試行回数を制御します。

**フックタイプ:** フィルター  
**バージョン:** 5.0.0  
**パラメータ:**
- `$retry_attempts` (int): 再試行回数（デフォルト: 3）

**戻り値:** `int` - 修正された再試行回数

**例:**

```php
// 信頼性のために再試行回数を増加
add_filter('c3_invalidation_retry_attempts', function($retry_attempts) {
    return 5;
});
```

## アクションフック

### `c3_before_invalidation`

CloudFrontに無効化リクエストが送信される前に発火します。

**フックタイプ:** アクション  
**バージョン:** 2.0.0  
**パラメータ:**
- `$paths` (array): 無効化するパスの配列
- `$post_id` (int|null): 無効化をトリガーした投稿ID

**例:**

```php
add_action('c3_before_invalidation', function($paths, $post_id) {
    // 無効化リクエストをログに記録
    error_log("Invalidating paths: " . implode(', ', $paths));
    
    // 通知を送信
    if ($post_id) {
        wp_mail('admin@example.com', 'Cache Invalidation', "Invalidating cache for post ID: $post_id");
    }
}, 10, 2);
```

### `c3_after_invalidation`

CloudFrontに無効化リクエストが正常に送信された後に発火します。

**フックタイプ:** アクション  
**バージョン:** 2.0.0  
**パラメータ:**
- `$paths` (array): 無効化されたパスの配列
- `$invalidation_id` (string): CloudFront無効化ID
- `$post_id` (int|null): 無効化をトリガーした投稿ID

**例:**

```php
add_action('c3_after_invalidation', function($paths, $invalidation_id, $post_id) {
    // 成功した無効化をログに記録
    error_log("Invalidation successful. ID: $invalidation_id");
    
    // カスタム追跡を更新
    update_option('last_cache_invalidation', [
        'timestamp' => current_time('mysql'),
        'invalidation_id' => $invalidation_id,
        'paths_count' => count($paths)
    ]);
}, 10, 3);
```

### `c3_invalidation_failed`

無効化リクエストが失敗したときに発火します。

**フックタイプ:** アクション  
**バージョン:** 2.0.0  
**パラメータ:**
- `$paths` (array): 無効化に失敗したパスの配列
- `$error` (string): エラーメッセージ
- `$post_id` (int|null): 無効化をトリガーした投稿ID

**例:**

```php
add_action('c3_invalidation_failed', function($paths, $error, $post_id) {
    // エラーをログに記録
    error_log("Cache invalidation failed: $error");
    
    // アラートを送信
    wp_mail('admin@example.com', 'Cache Invalidation Failed', 
        "Failed to invalidate cache for paths: " . implode(', ', $paths) . "\nError: $error");
}, 10, 3);
```

## ベストプラクティス

### 1. パフォーマンスの考慮事項

- 無効化パスを最小限かつ具体的に保つ
- 不要な無効化を避けるために条件付きロジックを使用
- ワイルドカード（`/*`）は控えめに使用することを検討

### 2. エラーハンドリング

- フィルターコールバックで適切なエラーハンドリングを常に実装
- 外部API呼び出しにはtry-catchブロックを使用
- デバッグのためにエラーをログに記録

### 3. セキュリティ

- フィルター内のすべてのデータを検証・サニタイズ
- 管理者専用フィルターにはnonceを使用
- 適切な権限チェックを実装

### 4. テスト

- ステージング環境で最初にフィルターをテスト
- CloudFront無効化コストを監視
- デバッグのために`c3_before_invalidation`と`c3_after_invalidation`フックを使用

## 完全な例

以下は、カスタム無効化ロジックを実装する方法を示す完全な例です：

```php
<?php
/**
 * Eコマースサイト用のカスタムCloudFrontキャッシュ無効化
 */

// 商品用のカスタム無効化パス
add_filter('c3_invalidation_items', function($items, $post) {
    if (!$post) {
        return $items;
    }
    
    // 商品のカテゴリページを追加
    if ($post->post_type === 'product') {
        $categories = get_the_terms($post->ID, 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $items[] = '/category/' . $category->slug . '/';
            }
        }
        
        // ショップページを追加
        $items[] = '/shop/';
    }
    
    // 注目投稿のホームページを追加
    if (has_post_thumbnail($post->ID) && get_post_meta($post->ID, '_featured', true)) {
        $items[] = '/';
    }
    
    return $items;
}, 10, 2);

// 環境固有の認証情報
add_filter('c3_credential', function($credentials) {
    $environment = wp_get_environment_type();
    
    switch ($environment) {
        case 'production':
            return [
                'key' => getenv('PROD_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('PROD_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('PROD_CLOUDFRONT_DISTRIBUTION_ID')
            ];
        case 'staging':
            return [
                'key' => getenv('STAGING_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('STAGING_AWS_SECRET_ACCESS_KEY'),
                'distribution_id' => getenv('STAGING_CLOUDFRONT_DISTRIBUTION_ID')
            ];
        default:
            return $credentials;
    }
});

// すべての無効化をログに記録
add_action('c3_before_invalidation', function($paths, $post_id) {
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'post_id' => $post_id,
        'paths' => $paths,
        'user_id' => get_current_user_id()
    ];
    
    // デバッグのためにtransientに保存
    set_transient('c3_invalidation_log_' . time(), $log_entry, HOUR_IN_SECONDS);
}, 10, 2);
```

この包括的なリファレンスは、特定のユースケースに合わせてC3 CloudFront Cache Controllerをカスタマイズするために必要なすべてのツールを提供します。 