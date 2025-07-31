# WP-CLIコマンド

C3 CloudFront Cache Controllerは、コマンドラインからキャッシュ無効化を管理するためのWP-CLIサポートを提供します。この包括的なガイドでは、自動化、CI/CDパイプライン、サーバー管理のためのすべての利用可能なコマンド、ユースケース、ベストプラクティスについて説明します。

## コマンド概要

| コマンド | 説明 | バージョン |
|---------|-------------|-------|
| `wp c3 flush` | 特定の投稿またはすべてのCloudFrontキャッシュをクリア | 2.3.0 |
| `wp c3 update` | プラグイン設定を更新 | 2.4.0 |

## 利用可能なコマンド

### `wp c3 flush`

特定の投稿またはすべてのコンテンツのCloudFrontキャッシュをクリアします。

**構文:**
```bash
wp c3 flush <post_id|all> [--force]
```

**パラメータ:**

#### 必須
- `<post_id|all>`: 投稿ID（数値）、カンマ区切りの投稿IDリスト、または完全なキャッシュクリアのための'all'

#### オプション
- `--force`: フォースクリアモードを有効化（無効化フラグをバイパス）

**レスポンス:**

**成功:**
```
Success: Create Invalidation Request. Please wait few minutes to finished clear CloudFront Cache.
```

**エラー:**
```
Error: Please input parameter:post_id(numeric) or all
```

**終了コード:**
- `0` - 成功
- `1` - 一般的なエラー（無効なパラメータ、AWS APIエラーなど）

**例:**

```bash
# 特定の投稿のキャッシュをフラッシュ
wp c3 flush 1

# 複数の投稿のキャッシュをフラッシュ
wp c3 flush 1,2,4

# すべてのCloudFrontキャッシュをフラッシュ
wp c3 flush all

# すべてのキャッシュを強制フラッシュ
wp c3 flush all --force
```

### `wp c3 update`

C3 CloudFront Cache Controllerの設定を更新します。

**構文:**
```bash
wp c3 update <setting_type> <value>
```

**パラメータ:**

#### 必須
- `<setting_type>`: 更新する設定のタイプ: `distribution_id`、`access_key`、または`secret_key`
- `<value>`: 設定の新しい値

**利用可能な設定:**
- `distribution_id` - CloudFront Distribution ID
- `access_key` - AWS Access Key ID
- `secret_key` - AWS Secret Access Key

**レスポンス:**

**成功:**
```
Success: Update Option
```

**エラー:**
```
Error: No type selected
Error: No value defined
Error: No Match Setting Type.
```

**セキュリティの考慮事項:**
- アクセスキーとシークレットキーはWordPressオプションテーブルに保存されます
- 本番環境では環境変数の使用を検討してください
- 値は保存前に`esc_attr()`でエスケープされます

**例:**

```bash
# Distribution IDを更新
wp c3 update distribution_id E1234567890123

# アクセスキーを更新
wp c3 update access_key AKIAIOSFODNN7EXAMPLE

# シークレットキーを更新
wp c3 update secret_key wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
```

## 一般的なユースケース

### デプロイメント自動化

デプロイメント後にキャッシュを無効化：

```bash
#!/bin/bash
# deployment-script.sh

# アプリケーションをデプロイ
./deploy.sh

# デプロイメント後にすべてのキャッシュをクリア
wp c3 flush all
```

### コンテンツ公開パイプライン

公開後に特定のコンテンツを無効化：

```bash
#!/bin/bash
# publish-content.sh

POST_ID=$1

# 特定の投稿を無効化
wp c3 flush $POST_ID

echo "投稿ID $POST_ID のキャッシュが無効化されました"
```

### CI/CD統合

#### GitHub Actions例

```yaml
name: Deploy and Clear Cache
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        run: ./deploy.sh
        
      - name: Clear CloudFront Cache
        run: |
          wp c3 flush all
        env:
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          C3_DISTRIBUTION_ID: ${{ secrets.C3_DISTRIBUTION_ID }}
```

#### GitLab CI例

```yaml
deploy:
  stage: deploy
  script:
    - ./deploy.sh
    - wp c3 flush all
  environment:
    name: production
  variables:
    AWS_ACCESS_KEY_ID: $AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY: $AWS_SECRET_ACCESS_KEY
    C3_DISTRIBUTION_ID: $C3_DISTRIBUTION_ID
```

### 設定管理

コマンドラインでC3設定をセットアップ：

```bash
#!/bin/bash
# configure-c3.sh

# C3設定をセットアップ
DISTRIBUTION_ID="E1234567890123"
ACCESS_KEY="AKIAIOSFODNN7EXAMPLE"
SECRET_KEY="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"

echo "C3 CloudFront Cache Controllerを設定中..."

# Distribution IDを更新
wp c3 update distribution_id $DISTRIBUTION_ID

# アクセスキーを更新
wp c3 update access_key $ACCESS_KEY

# シークレットキーを更新
wp c3 update secret_key $SECRET_KEY

echo "設定完了"
```

## 高度な使用方法

### バッチ操作

複数の投稿を処理：

```bash
#!/bin/bash
# batch-invalidate.sh

# 過去24時間のすべての公開投稿を取得
RECENT_POST_IDS=$(wp post list --post_status=publish --after="24 hours ago" --field=ID)

# カンマ区切りリストに変換
POST_IDS=$(echo $RECENT_POST_IDS | tr ' ' ',')

if [ -n "$POST_IDS" ]; then
    echo "投稿を無効化中: $POST_IDS"
    wp c3 flush $POST_IDS
else
    echo "無効化する最近の投稿がありません"
fi
```

### 環境固有の操作

```bash
#!/bin/bash
# env-specific-invalidation.sh

ENVIRONMENT=$(wp option get environment_type)

case $ENVIRONMENT in
    "production")
        # 本番環境ではより保守的な無効化
        echo "本番環境 - 完全なキャッシュクリアをスキップ"
        ;;
    "staging")
        # ステージング環境では完全なキャッシュクリア
        wp c3 flush all
        ;;
    "development")
        echo "開発環境ではキャッシュ無効化をスキップ"
        exit 0
        ;;
esac
```

## エラーハンドリング

### 基本的なエラーハンドリング

```bash
#!/bin/bash
set -e  # エラー時に終了

if wp c3 flush all; then
    echo "キャッシュ無効化が成功しました"
else
    echo "キャッシュ無効化が失敗しました"
    exit 1
fi
```

### 高度なエラーハンドリング

```bash
#!/bin/bash
# robust-invalidation.sh

flush_with_retry() {
    local post_id=$1
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        echo "試行 $attempt: 投稿 $post_id のキャッシュをフラッシュ中"
        
        if wp c3 flush $post_id; then
            echo "成功: 投稿 $post_id のキャッシュがフラッシュされました"
            return 0
        else
            echo "失敗: 投稿 $post_id の試行 $attempt"
            attempt=$((attempt + 1))
            sleep 5
        fi
    done
    
    echo "エラー: 投稿 $post_id のキャッシュフラッシュが $max_attempts 回の試行後に失敗しました"
    return 1
}

# 使用例
flush_with_retry "1"
flush_with_retry "all"
```

## 設定管理

### 環境変数

異なる環境で異なる設定をセットアップ：

```bash
# production.env
export AWS_ACCESS_KEY_ID="prod_access_key"
export AWS_SECRET_ACCESS_KEY="prod_secret_key"
export C3_DISTRIBUTION_ID="prod_distribution_id"

# staging.env
export AWS_ACCESS_KEY_ID="staging_access_key"
export AWS_SECRET_ACCESS_KEY="staging_secret_key"
export C3_DISTRIBUTION_ID="staging_distribution_id"

# 環境固有の設定を読み込み
source "${ENVIRONMENT}.env"
wp c3 flush all
```

### 設定検証

```bash
#!/bin/bash
# validate-config.sh

echo "C3設定を検証中..."

# WP-CLIが利用可能かチェック
if ! command -v wp &> /dev/null; then
    echo "エラー: WP-CLIが見つかりません"
    exit 1
fi

# フラッシュ操作を試行して設定をテスト
if wp c3 flush 1 &> /dev/null; then
    echo "✓ C3設定が有効です"
else
    echo "✗ C3設定エラー"
    exit 1
fi
```

## パフォーマンス最適化

### バッチ投稿無効化

複数の投稿無効化では、カンマ区切りリストを使用：

```bash
#!/bin/bash
# batch-post-invalidation.sh

# 個別の呼び出しの代わりに
# wp c3 flush 1
# wp c3 flush 2
# wp c3 flush 3

# カンマ区切りリストで単一コマンドを使用
wp c3 flush 1,2,3
```

### フォースモードの使用

`--force`フラグは無効化フラグフィルターをバイパスします：

```bash
#!/bin/bash
# force-clear.sh

# 通常のフラッシュ（無効化フラグを尊重）
wp c3 flush all

# フォースフラッシュ（無効化フラグを無視）
wp c3 flush all --force
```

## トラブルシューティング

### デバッグモード

詳細な出力を有効化：

```bash
# 詳細な出力のための--debugフラグを追加
wp c3 flush all --debug

# WordPressデバッグログをチェック
wp c3 flush all && tail -f /path/to/wp-content/debug.log
```

### 一般的な問題

**権限拒否:**
```bash
# フラッシュ操作をテストしてAWS認証情報をチェック
wp c3 flush 1

# IAM権限を確認
aws sts get-caller-identity
```

**ネットワークタイムアウト:**
```bash
# 接続性をテスト
curl -I https://cloudfront.amazonaws.com
```

**無効なパラメータ:**
```bash
# パラメータ形式をチェック
wp c3 flush 1,2,3  # 正しい: カンマ区切り
wp c3 flush "1 2 3"  # 間違い: スペース区切り
```

## ベストプラクティス

1. **可能な限り特定の投稿IDを使用** - すべてのキャッシュをクリアする代わりに
2. **AWS CloudFrontコストを監視** - 無効化には関連するコストがあります
3. **本番環境で実行する前にステージングでテスト**
4. **フォースモードは控えめに使用** - 必要な場合のみ
5. **タイミングを考慮** - ピークトラフィック期間中のキャッシュクリアを避ける
6. **複数の投稿IDにはカンマ区切りリストを使用**
7. **自動化スクリプトでエラーを適切に処理**

## グローバルオプション

すべてのC3コマンドは以下のグローバルWP-CLIオプションをサポートします：

### 環境
- `--path=<path>` - WordPressインストールへのパス
- `--url=<url>` - WordPressサイトURL
- `--ssh=<ssh>` - SSH接続文字列

### 出力
- `--quiet` - 情報メッセージを抑制
- `--debug` - デバッグ出力を有効化

**例:**
```bash
# リモートWordPressインストール
wp --ssh=user@server.com --path=/var/www/html c3 flush all

# クワイエットモード
wp c3 flush 1 --quiet

# デバッグモード
wp c3 update distribution_id E1234567890123 --debug
```

## エラーハンドリング

### 一般的なエラーシナリオ

| シナリオ | エラーメッセージ | 解決策 |
|----------|---------------|------------|
| パラメータが提供されていない | `Please input parameter:post_id(numeric) or all` | 必要な投稿IDまたは'all'を提供 |
| 無効な設定タイプ | `No Match Setting Type.` | 有効な設定を使用: distribution_id, access_key, secret_key |
| 設定値が不足 | `No value defined` | 設定の値を提供 |
| 無効な投稿ID | `Please input parameter:post_id(numeric) or all` | 数値の投稿IDまたは'all'を使用 |

### AWS APIエラー

AWS API呼び出しが失敗した場合、AWSからのエラーメッセージが表示されます：

```
Error: [AWS Error Message]
```

一般的なAWSエラー：
- 無効な認証情報
- ディストリビューションが見つからない
- ネットワーク接続の問題
- レート制限

## 次のステップ

- [一般的な問題のトラブルシューティング](/ja/guide/troubleshooting)について学ぶ
- 高度なカスタマイズのための[フィルターとフック](/ja/development/filters)を探索する 