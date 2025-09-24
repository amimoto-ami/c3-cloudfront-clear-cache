# 設定

プラグインをインストールした後、AWS認証情報とCloudFrontディストリビューション設定でプラグインを設定する必要があります。

## AWS前提条件

プラグインを設定する前に、以下があることを確認してください：

1. **AWSアカウント** - CloudFrontアクセス権限付き
2. **CloudFrontディストリビューション** - WordPressサイト用に設定済み
3. **IAMユーザー**または**IAMロール** - 適切な権限付き

### 必要なIAM権限

以下の最小権限を持つIAMポリシーを作成してください：

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

## 設定方法

### 方法1: EC2インスタンスロール（AWS EC2推奨）

WordPressサイトがEC2インスタンスで実行されている場合、EC2インスタンスロールを使用することが**最も安全で推奨される方法**です。この方法により、アプリケーションにAWS認証情報を保存する必要がなくなります。

#### 前提条件

1. **EC2インスタンスロール**: EC2インスタンスに必要なCloudFront権限を持つIAMロールがアタッチされている必要があります
2. **インスタンスメタデータサービス**: インスタンスメタデータサービスにアクセス可能であることを確認（デフォルトで有効）

#### EC2インスタンスロールの設定

1. **IAMロールの作成**:
   - AWS IAMコンソールに移動
   - "EC2"信頼エンティティで新しいロールを作成
   - 以下のポリシーをアタッチ（またはカスタムポリシーを作成）：

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

2. **EC2インスタンスにロールをアタッチ**:
   - EC2コンソールに移動
   - インスタンスを選択
   - Actions → Security → Modify IAM role
   - 作成したロールを選択

3. **プラグインの設定**:
   - **設定 > C3 CloudFront Cache** に移動
   - **CloudFront Distribution ID**のみを入力
   - AWS認証情報フィールドは空のまま
   - **変更を保存** をクリック

プラグインは自動的にEC2インスタンスロール認証情報を検出して使用します。

::: tip セキュリティの利点
- アプリケーションコードやデータベースに認証情報を保存しない
- 自動的な認証情報ローテーション
- 認証情報漏洩のリスクなし
- AWSセキュリティベストプラクティスに従う
:::

### 方法2: WordPress管理画面

1. **設定 > C3 CloudFront Cache** に移動
2. 必要なフィールドを入力：
   - **CloudFront Distribution ID**
   - **AWS Access Key ID**
   - **AWS Secret Access Key**
3. **変更を保存** をクリック

プラグインは認証情報をテストし、WordPressオプションテーブルに安全に保存します。

### 方法3: wp-config.php定数（高度）

セキュリティを向上させるために、`wp-config.php`ファイルで定数を定義できます：

```php
// wp-config.phpに追加（"That's all, stop editing!"行の前）
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

#### 異なるホスティング環境用

以下の設定は、Shared Hosting、VPS/Dedicated Server、Dockerなど、どの環境でも同じように使用できます：

```php
// wp-config.phpに追加
define( 'AWS_ACCESS_KEY_ID', 'your_access_key_here' );
define( 'AWS_SECRET_ACCESS_KEY', 'your_secret_key_here' );
define( 'C3_DISTRIBUTION_ID', 'your_cloudfront_distribution_id' );
```

::: tip 環境固有の考慮事項
- **AWS EC2**: 最大のセキュリティのためにEC2インスタンスロールの使用を**強く推奨**
- **Shared Hosting**: ファイルシステムアクセスが制限される可能性があるため、WordPress管理画面の使用を検討
- **VPS/Dedicated Server**: セキュリティ向上のために環境変数の使用を検討
- **Docker**: コンテナ化環境のために環境変数やIAMロールの使用を検討
:::

::: info セキュリティ注意事項
`wp-config.php`定数を使用することで、認証情報をデータベースから除外してセキュリティを向上させることができます。ただし、WordPress管理画面はほとんどのユースケースに適しており、ユーザーフレンドリーな設定体験を提供します。
:::

### 方法4: AWS Systems Manager / Secrets Manager

本番環境でのセキュリティ向上のために、AWS Systems Manager Parameter StoreまたはSecrets Managerを使用して認証情報を管理できます。

#### Parameter Storeの使用

認証情報をParameter Storeに保存し、プログラムで取得：

```php
add_filter('c3_credential', function($credentials) {
    try {
        $ssm = new Aws\Ssm\SsmClient(['version' => 'latest', 'region' => 'us-east-1']);
        $result = $ssm->getParameters([
            'Names' => ['/c3-cloudfront/aws-access-key', '/c3-cloudfront/aws-secret-key', '/c3-cloudfront/distribution-id'],
            'WithDecryption' => true
        ]);
        
        $params = [];
        foreach ($result['Parameters'] as $param) {
            $params[$param['Name']] = $param['Value'];
        }
        
        return [
            'key' => $params['/c3-cloudfront/aws-access-key'],
            'secret' => $params['/c3-cloudfront/aws-secret-key'],
            'distribution_id' => $params['/c3-cloudfront/distribution-id'],
            'timeout' => 30
        ];
    } catch (Exception $e) {
        error_log('C3 CloudFront: Failed to retrieve credentials from SSM: ' . $e->getMessage());
        return $credentials;
    }
});
```

#### Secrets Managerの使用

認証情報をJSONシークレットとして保存し、取得：

```php
add_filter('c3_credential', function($credentials) {
    try {
        $secretsManager = new Aws\SecretsManager\SecretsManagerClient(['version' => 'latest', 'region' => 'us-east-1']);
        $result = $secretsManager->getSecretValue(['SecretId' => 'c3-cloudfront-credentials']);
        $secret = json_decode($result['SecretString'], true);
        
        return [
            'key' => $secret['aws_access_key_id'],
            'secret' => $secret['aws_secret_access_key'],
            'distribution_id' => $secret['distribution_id'],
            'timeout' => 30
        ];
    } catch (Exception $e) {
        error_log('C3 CloudFront: Failed to retrieve credentials from Secrets Manager: ' . $e->getMessage());
        return $credentials;
    }
});
```

::: info セキュリティ注意事項
Parameter StoreまたはSecrets Managerにアクセスするための適切なIAM権限を設定し、機密パラメータにはSecureStringタイプを使用することを忘れないでください。
:::

## CloudFront Distribution IDの見つけ方

1. [AWSコンソール](https://console.aws.amazon.com/)にログイン
2. **CloudFront** に移動
3. リストからディストリビューションを見つける
4. **ID**をコピー（例：`E1234567890123`）

## 設定のテスト

設定後、すべてが正常に動作することをテスト：

1. **設定 > C3 CloudFront Cache** に移動
2. **手動無効化** をクリック
3. テストパスを入力（例：`/`）
4. **無効化** をクリック

成功した場合、確認メッセージが表示され、無効化がCloudFrontコンソールに表示されるはずです。

### WP-CLIテスト

WP-CLIを使用して設定をテストすることもできます：

```bash
# 特定の投稿のキャッシュをフラッシュして設定をテスト
wp c3 flush 1

# 完全なキャッシュクリアをテスト
wp c3 flush all
```

## 高度な設定オプション

### カスタム無効化間隔

```php
// 無効化間隔を変更（デフォルト：1分）
add_filter('c3_invalidation_interval', function($interval_minutes) {
    return 5; // 5分
});

// 失敗した無効化の再試行間隔を変更（デフォルト：1分）
add_filter('c3_invalidation_cron_interval', function($interval_minutes) {
    return 2; // 2分
});
```

### 無効化制限

```php
// 無効化バッチあたりのパス数を変更（デフォルト：100）
add_filter('c3_invalidation_item_limits', function($limits) {
    return 300; // バッチあたり300パス
});
```

### ログ設定

```php
// 包括的な無効化ログを有効化
add_filter('c3_log_invalidation_list', '__return_true');

// cronジョブログを有効化（レガシー、包括的ログには上記を使用）
add_filter('c3_log_cron_invalidation_task', '__return_true');
```

## デバッグ設定

プラグインには、問題のトラブルシューティングと操作の監視に役立つ組み込みデバッグ設定が含まれています。

### デバッグ設定へのアクセス

1. WordPress管理画面で **設定 > 表示設定** に移動
2. **C3 CloudFront Debug Settings** までスクロール
3. 必要なデバッグオプションを有効化

### 利用可能なデバッグオプション

#### Log Cron Register Task（cron登録タスクのログ）
- cronジョブ操作の詳細ログを有効化
- 無効化タスクがスケジュールされ実行される際のログを出力
- cron関連の問題のトラブルシューティングに有用

#### Log Invalidation Parameters（無効化パラメータのログ）
- 無効化リクエストの詳細ログを有効化
- CloudFront APIリクエストとレスポンスをログ出力
- 無効化失敗のデバッグに有用

### デバッグ設定の移行

v7.3.0から、デバッグ設定はフィルターベースの設定からWordPress管理画面設定に移行されました：

**以前（v7.2.0以前）**:
```php
add_filter('c3_log_cron_register_task', '__return_true');
add_filter('c3_log_invalidation_params', '__return_true');
```

**以降（v7.3.0以降）**:
デバッグ設定はWordPress管理画面の **設定 > 表示設定 > C3 CloudFront Debug Settings** で管理されます。

### デバッグログの出力

デバッグ設定が有効になっている場合、詳細なログがWordPressエラーログに書き込まれます。以下の場所を確認してください：

- **WordPressデバッグログ**: `wp-content/debug.log`
- **サーバーエラーログ**: ホスティングプロバイダーのエラーログの場所を確認
- **WP-CLI**: `tail -f /var/log/php-fpm/www-error.log` を使用して最近のログエントリを表示、または `wp package install wp-cli/logs-command` でWP-CLI logs拡張をインストールしてから `wp log list` を使用

デバッグログ出力の例：
```
===== C3 CRON Job registration [START] ===
C3 Invalidation Started - Query: Array
(
    [DistributionId] => E1234567890123
    [InvalidationBatch] => Array
    (
        [Paths] => Array
        (
            [Items] => Array
            (
                [0] => /sample-post/
            )
        )
    )
)
===== C3 Invalidation cron has been COMPLETED ===
```

## 設定のトラブルシューティング

### 一般的な問題

**無効な認証情報**
- WordPress管理画面でAWS Access Key IDとSecret Access Keyを確認
- IAMユーザーに必要な権限があることを確認
- 特殊文字が含まれている場合、認証情報が適切にURLエンコードされていることを確認
- wp-config.php定数を使用している場合、正しく定義されていることを確認
- **EC2インスタンスロールの場合**: IAMロールがインスタンスにアタッチされており、必要なCloudFront権限があることを確認

**ディストリビューションが見つからない**
- CloudFront Distribution IDが正しいことを確認
- ディストリビューションが存在し、削除されていないことを確認
- ドメイン名ではなくディストリビューションIDを使用していることを確認

**権限拒否**
- IAMポリシー権限を確認
- ポリシーが正しいユーザー/ロールにアタッチされていることを確認
- CloudFormationリソース制限を確認

**タイムアウトエラー**
- `C3_HTTP_TIMEOUT`値を増加
- AWSサービスへのネットワーク接続を確認
- ファイアウォール設定でアウトバウンドHTTPSトラフィックが許可されていることを確認

より詳細なトラブルシューティングについては、[トラブルシューティングガイド](/ja/guide/troubleshooting)を参照してください。

## 次のステップ

設定が完了したら、[基本的な使用方法](/ja/guide/basic-usage)について学び、[高度な機能](/ja/development/filters)を探索してください。 