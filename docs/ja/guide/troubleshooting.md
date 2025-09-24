# トラブルシューティング

このガイドでは、C3 CloudFront Cache Controllerの一般的な問題を診断し、解決する方法について説明します。

## 一般的な問題

### プラグインが動作しない

#### 症状
- 投稿を公開してもキャッシュ無効化が発生しない
- 管理画面にアクティビティが表示されない
- エラーメッセージがない

#### 診断
1. プラグインの有効化をチェック：
   ```bash
   wp plugin list | grep c3-cloudfront
   ```

2. WordPressパーマリンク構造を確認：
   - **設定 > パーマリンク** に移動
   - 「プレーン」に設定されていないことを確認

3. PHPエラーログをチェック：
   ```bash
   tail -f /path/to/wp-content/debug.log
   ```

#### 解決策

**パーマリンク構造の問題:**
```php
// wp-config.phpに追加して現在の構造をチェック
echo get_option('permalink_structure');

// 空の場合、プレーンパーマリンクの問題です
```

**プラグインの競合:**
```bash
# 最小限のプラグインでテスト
wp plugin deactivate --all
wp plugin activate c3-cloudfront-clear-cache
```

**PHPバージョン:**
```bash
# PHPバージョンをチェック（最小7.4が必要、8.2まで対応）
php -v
```

::: info PHP 8.2 対応
プラグインにはセキュリティ強化機能とXMLパースィング改善が含まれており、PHP 8.2と完全に互換性があります。XML関連の問題が発生した場合は、最新バージョン（7.0.1以上）を使用していることを確認してください。
:::

### AWS認証情報の問題

#### 症状
- 「無効な認証情報」エラーメッセージ
- 403 Forbiddenエラー
- 認証失敗

#### 診断

1. 認証情報設定をチェック：
   ```bash
   wp c3 flush 1
   ```

2. 環境変数を確認：
   ```bash
   echo $AWS_ACCESS_KEY_ID
   echo $AWS_SECRET_ACCESS_KEY
   echo $C3_DISTRIBUTION_ID
   ```

3. AWS CLIアクセスをテスト（利用可能な場合）：
   ```bash
   aws sts get-caller-identity
   aws cloudfront list-distributions
   ```

#### 解決策

**環境変数が設定されていない:**
```bash
# .bashrc、.zshrc、またはサーバー設定に追加
export AWS_ACCESS_KEY_ID="your_access_key"
export AWS_SECRET_ACCESS_KEY="your_secret_key"
export C3_DISTRIBUTION_ID="your_distribution_id"
```

**IAM権限の問題:**

IAMユーザー/ロールに以下のポリシーがあることを確認：
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "cloudfront:CreateInvalidation",
                "cloudfront:GetInvalidation",
                "cloudfront:ListInvalidations"
            ],
            "Resource": "arn:aws:cloudfront::*:distribution/YOUR_DISTRIBUTION_ID"
        }
    ]
}
```

**EC2インスタンスロールの問題:**
```bash
# インスタンスメタデータサービスをテスト
curl http://169.254.169.254/latest/meta-data/iam/security-credentials/
```

### CloudFront Distributionの問題

#### 症状
- 「Distribution not found」エラー
- 無効化リクエストが失敗
- 404エラー

#### 診断

1. Distribution IDを確認：
   ```bash
   # WordPress管理画面で確認
   wp option get c3_distribution_id
   ```

2. AWSコンソールでディストリビューションの存在を確認

3. ディストリビューションのステータスをチェック：
   ```bash
   aws cloudfront get-distribution --id YOUR_DISTRIBUTION_ID
   ```

#### 解決策

**無効なDistribution ID:**
```bash
# 正しいDistribution IDを設定
wp c3 update distribution_id E1234567890123
```

**ディストリビューションが削除されている:**
- AWSコンソールで新しいディストリビューションを作成
- 新しいDistribution IDでプラグインを更新

### ネットワーク接続の問題

#### 症状
- タイムアウトエラー
- 接続エラー
- 遅いレスポンス

#### 診断

1. 基本的な接続性をテスト：
   ```bash
   curl -I https://cloudfront.amazonaws.com
   ```

2. ファイアウォール設定を確認：
   ```bash
   # アウトバウンドHTTPSトラフィックが許可されているか確認
   telnet cloudfront.amazonaws.com 443
   ```

3. DNS解決をテスト：
   ```bash
   nslookup cloudfront.amazonaws.com
   ```

#### 解決策

**タイムアウト設定の調整:**
```php
// wp-config.phpに追加
define('C3_HTTP_TIMEOUT', 60); // 60秒に増加
```

**プロキシ設定（必要な場合）:**
```php
// wp-config.phpに追加
define('C3_HTTP_PROXY', 'http://proxy.example.com:8080');
```

### 添付ファイルの問題

#### 症状
- 添付ファイル削除時に無効化が発生しない
- 画像が削除後もキャッシュされている
- 添付ファイルの無効化パスが作成されない

#### 診断

1. 添付ファイル無効化が動作しているかチェック：
   ```bash
   # テスト画像をアップロードしてから削除
   # 無効化が発生するかチェック
   wp c3 flush all
   ```

2. デバッグログを有効化してログをチェック：
   ```bash
   # WordPress管理画面でデバッグ設定を有効化
   # 設定 > 表示設定 > C3 CloudFront Debug Settings
   # "Log Invalidation Parameters" を有効化
   ```

3. 添付ファイルURL生成をテスト：
   ```php
   // テスト用にfunctions.phpに追加
   add_action('delete_attachment', function($attachment_id) {
       $url = wp_get_attachment_url($attachment_id);
       error_log("添付ファイルURL: " . $url);
   });
   ```

#### 解決策

**添付ファイルURL生成の確認:**
```php
// wp_get_attachment_url()が有効なURLを返すことを確認
add_action('delete_attachment', function($attachment_id) {
    $url = wp_get_attachment_url($attachment_id);
    if (!$url) {
        error_log("添付ファイルID " . $attachment_id . " のURL取得に失敗");
    }
});
```

**カスタム添付ファイル無効化:**
```php
// 必要に応じて添付ファイル無効化をオーバーライド
add_filter('c3_invalidation_items', function($items, $post) {
    // 添付ファイル用のカスタムロジックを追加
    if ($post && $post->post_type === 'attachment') {
        $items[] = '/wp-content/uploads/*';
    }
    return $items;
}, 10, 2);
```

**添付ファイル削除のデバッグ:**
```php
// 添付ファイル削除の詳細ログを追加
add_action('delete_attachment', function($attachment_id) {
    if (WP_DEBUG_LOG) {
        $url = wp_get_attachment_url($attachment_id);
        $parsed = parse_url($url);
        error_log("C3 添付ファイル削除デバッグ:");
        error_log("- 添付ファイルID: " . $attachment_id);
        error_log("- URL: " . $url);
        error_log("- パス: " . ($parsed['path'] ?? 'N/A'));
    }
});
```

### パフォーマンスの問題

#### 症状
- 無効化が遅い
- 大量の無効化リクエストが失敗
- メモリ使用量が高い

#### 診断

1. 現在の設定を確認：
   ```bash
   wp option get c3_invalidation_item_limits
   wp option get c3_invalidation_interval
   ```

2. サーバーリソースを監視：
   ```bash
   # メモリ使用量をチェック
   free -h
   
   # CPU使用量をチェック
   top
   ```

3. WordPress cronの状態を確認：
   ```bash
   wp cron event list
   ```

#### 解決策

**バッチサイズの調整:**
```php
// 高トラフィックサイト用にバッチサイズを増加
add_filter('c3_invalidation_item_limits', function($limits) {
    return 500; // バッチあたり500パス
});
```

**無効化頻度の調整:**
```php
// 無効化頻度を調整
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5; // 5分ごとに処理
});
```

**メモリ制限の増加:**
```php
// wp-config.phpに追加
define('WP_MEMORY_LIMIT', '256M');
```

### ログとデバッグ

#### デバッグログの有効化

**方法1: WordPress管理画面（v7.3.0以降推奨）**

1. WordPress管理画面で **設定 > 表示設定** に移動
2. **C3 CloudFront Debug Settings** までスクロール
3. 必要なデバッグオプションを有効化：
   - **Log Cron Register Task**: cronジョブのデバッグ用
   - **Log Invalidation Parameters**: 無効化リクエストのデバッグ用

**方法2: プログラム（レガシー）**

```php
// wp-config.phpに追加
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// C3ログを有効化（レガシー方法）
add_filter('c3_log_invalidation_list', '__return_true');
```

#### デバッグ設定の移行

v7.3.0から、デバッグ設定はフィルターベースの設定からWordPress管理画面設定に移行されました：

**以前（v7.2.0以前）**:
```php
add_filter('c3_log_cron_register_task', '__return_true');
add_filter('c3_log_invalidation_params', '__return_true');
```

**以降（v7.3.0以降）**:
デバッグ設定はWordPress管理画面の **設定 > 表示設定 > C3 CloudFront Debug Settings** で管理されます。

#### ログの確認

1. C3無効化ログ：
   - WordPress管理画面で **設定 > C3 CloudFront Cache**
   - **無効化ログ**セクションを確認

2. サーバーログ：
   ```bash
   # Apache
   tail -f /var/log/apache2/error.log
   
   # Nginx
   tail -f /var/log/nginx/error.log
   ```

### 一般的なエラーメッセージ

| エラーメッセージ | 原因 | 解決策 |
|----------------|------|--------|
| `Invalid credentials` | AWS認証情報が無効 | 認証情報を確認・更新 |
| `Distribution not found` | Distribution IDが無効 | 正しいDistribution IDを設定 |
| `Permission denied` | IAM権限が不足 | IAMポリシーを確認・更新 |
| `Network timeout` | ネットワーク接続の問題 | タイムアウト設定を調整 |
| `Rate limit exceeded` | API制限に達した | 無効化頻度を調整 |

### 予防的メンテナンス

#### 定期的なチェック

1. **週次チェック:**
   ```bash
   # プラグインの状態を確認
   wp plugin status c3-cloudfront-clear-cache
   
   # 設定を確認
   wp option get c3_distribution_id
   ```

2. **月次チェック:**
   - AWS CloudFrontコストを確認
   - 無効化ログを確認
   - パフォーマンスメトリクスを確認

#### バックアップと復元

```bash
# 設定のバックアップ
wp option get c3_distribution_id > c3_backup.txt
wp option get c3_access_key >> c3_backup.txt
wp option get c3_secret_key >> c3_backup.txt

# 設定の復元
wp c3 update distribution_id $(grep distribution_id c3_backup.txt | cut -d' ' -f2)
```

### XMLセキュリティとパースィング問題

#### 症状
- CloudFrontレスポンスでのXMLパースィングエラー
- XML処理に関するセキュリティ警告
- PHP 8.1以上での互換性問題

#### 背景
バージョン7.0.1以降、プラグインにはXXE（XML外部エンティティ）攻撃を防ぐセキュアなXMLパースィング機能が強化され、PHP 8.2との互換性が向上しています。

#### 解決策

**最新バージョンに更新:**
```bash
# バージョン7.0.1以上を使用していることを確認
wp plugin update c3-cloudfront-clear-cache
```

**XML処理をチェック:**
```php
// XML問題のデバッグのためにwp-config.phpに追加
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

```

**libxml設定を確認:**
```bash
# libxmlバージョンを確認（セキュアパースィングをサポートしている必要があります）
php -m | grep libxml
php -r "phpinfo();" | grep -i libxml
```

## 次のステップ

問題が解決しない場合は、以下を試してください：

1. [GitHub Issues](https://github.com/amimoto-ami/c3-cloudfront-clear-cache/issues)で問題を報告
2. [WordPress.org Support](https://wordpress.org/support/plugin/c3-cloudfront-clear-cache/)でサポートを受ける
3. [開発者ドキュメント](/ja/development/filters)で高度な設定を確認 