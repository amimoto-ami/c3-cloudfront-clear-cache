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

### `c3_invalidation_post_batch_home_path`

単一投稿の無効化時のホームパスをカスタマイズします。

**フックタイプ:** フィルター  
**バージョン:** 7.2.0  
**パラメータ:**
- `$home_path` (string): 無効化されるホームURL/パス
- `$post` (WP_Post|null): 無効化をトリガーした投稿オブジェクト

**戻り値:** `string` - 修正されたホームパス

**例:**

```php
// 特定の投稿タイプに異なるホームパスを使用
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    if ($post && $post->post_type === 'product') {
        return '/shop/'; // ホームの代わりにショップページを無効化
    }
    return $home_path;
}, 10, 2);

// 下書き投稿のホーム無効化をスキップ
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    if ($post && $post->post_status === 'draft') {
        return null; // ホーム無効化をスキップ
    }
    return $home_path;
}, 10, 2);
```

### `c3_invalidation_posts_batch_home_path`

複数投稿の無効化時のホームパスをカスタマイズします。

**フックタイプ:** フィルター  
**バージョン:** 7.2.0  
**パラメータ:**
- `$home_path` (string): 無効化されるホームURL/パス
- `$posts` (array): 無効化される投稿オブジェクトの配列

**戻り値:** `string` - 修正されたホームパス

**例:**

```php
// 一括操作に異なるホームパスを使用
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    if (count($posts) > 5) {
        return '/'; // 大規模な一括操作にはルートパスを使用
    }
    return $home_path;
}, 10, 2);

// バッチ内の投稿タイプに基づくカスタムパス
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    $post_types = array_unique(array_column($posts, 'post_type'));
    if (in_array('product', $post_types)) {
        return '/shop/';
    }
    return $home_path;
}, 10, 2);
```

### `c3_invalidation_manual_batch_all_path`

手動「全キャッシュクリア」操作のパスをカスタマイズします。

**フックタイプ:** フィルター  
**バージョン:** 7.2.0  
**パラメータ:**
- `$all_path` (string): 全キャッシュクリア用のパスパターン（デフォルト: '/*'）

**戻り値:** `string` - 修正されたパスパターン

**例:**

```php
// 手動全クリアにより具体的なパスを使用
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    // すべてではなくコンテンツディレクトリのみをクリア
    return '/content/*';
});

// 環境固有の全クリア動作
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    if (wp_get_environment_type() === 'staging') {
        return '/staging/*';
    }
    return $all_path;
});
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

## WordPressサブディレクトリインストール対応

新しいパス調整フック（`c3_invalidation_post_batch_home_path`、`c3_invalidation_posts_batch_home_path`、`c3_invalidation_manual_batch_all_path`）は、サブディレクトリにインストールされたWordPressを完全にサポートします。

### サブディレクトリ対応の仕組み

WordPressがサブディレクトリにインストールされている場合（例：`https://example.com/blog/`）、プラグインはWordPress標準の`home_url()`関数を通じてサブディレクトリパスを自動的に処理します：

**通常インストール:**
```
WordPress URL: https://example.com/
home_url('/') → https://example.com/
```

**サブディレクトリインストール:**
```
WordPress URL: https://example.com/blog/
home_url('/') → https://example.com/blog/
```

### パス生成ロジック

プラグインは`parse_url()`を使用してURLからパス成分を抽出し、サブディレクトリパスを自動的に含めます：

```php
// Invalidation_Batch.php内
public function make_invalidate_path( $url ) {
    $parse_url = parse_url( $url );
    return isset( $parse_url['path'] )
        ? $parse_url['path']  // サブディレクトリを含む
        : preg_replace( array( '#^https?://[^/]*#', '#\?.*$#' ), '', $url );
}
```

### サブディレクトリの例

#### 例1: サブディレクトリインストール用のカスタムホームパス

```php
// WordPressが https://example.com/blog/ にインストールされている場合
add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
    // $home_pathは自動的に"/blog/"を含む
    
    if ($post && $post->post_type === 'product') {
        return '/blog/shop/'; // サブディレクトリ + カスタムパス
    }
    return $home_path; // デフォルト: /blog/
}, 10, 2);
```

#### 例2: サブディレクトリ制限付きの手動全クリア

```php
// WordPressサブディレクトリ内のキャッシュのみクリア
add_filter('c3_invalidation_manual_batch_all_path', function($all_path) {
    // サブディレクトリのみに制限
    return '/blog/*'; // /blog/*パスのみ無効化
});
```

#### 例3: 環境固有のサブディレクトリ処理

```php
add_filter('c3_invalidation_posts_batch_home_path', function($home_path, $posts) {
    // 環境ごとに異なるサブディレクトリを処理
    $environment = wp_get_environment_type();
    
    switch ($environment) {
        case 'staging':
            return '/staging/blog/';
        case 'development':
            return '/dev/blog/';
        default:
            return $home_path; // 本番環境のサブディレクトリ
    }
}, 10, 2);
```

### サブディレクトリ対応のテスト

サブディレクトリ機能をテストするには、サブディレクトリインストールをシミュレートできます：

```php
// サブディレクトリ対応のテストケース
public function test_subdirectory_installation_support() {
    // サブディレクトリホームURLをモック
    add_filter('home_url', function($url) {
        return 'https://example.com/blog/';
    });
    
    add_filter('c3_invalidation_post_batch_home_path', function($home_path, $post) {
        // サブディレクトリパスが含まれることを確認
        return $home_path; // /blog/ であるべき
    }, 10, 2);
    
    $post = $this->factory->post->create_and_get();
    $target = new AWS\Invalidation_Batch_Service();
    $result = $target->create_batch_by_post('https://example.com/blog/', 'EXXXX', $post);
    
    // サブディレクトリパスが存在することをアサート
    $this->assertContains('/blog/', $result['InvalidationBatch']['Paths']['Items']);
}
```

### サブディレクトリインストールの主な利点

1. **自動パス検出**: 手動設定不要
2. **柔軟なカスタマイズ**: フックによりサブディレクトリパスの細かい制御が可能
3. **環境互換性**: 異なるデプロイメントシナリオでシームレスに動作
4. **後方互換性**: 既存の`c3_invalidation_items`フィルターも継続して動作

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