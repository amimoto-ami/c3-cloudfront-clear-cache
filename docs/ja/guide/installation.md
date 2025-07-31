# インストール

## 要件

- **WordPress**: 5.0以上
- **PHP**: 7.4以上
- **AWS CloudFront**: アクティブなディストリビューション
- **パーマリンク構造**: 「プレーン」以外に設定されている必要があります
- **WP-CLI**: オプションですが、コマンドラインインストールには推奨

::: warning 重要
プラグインは正常に動作するために、WordPressのパーマリンク設定が「プレーン」以外に設定されている必要があります。これは、プレーンパーマリンクがクエリパラメータ（`?p=123`）を使用し、CloudFrontで効果的に無効化できないためです。
:::

## インストール方法

### 方法1: WordPress管理画面（推奨）

1. WordPress管理ダッシュボードに移動
2. **プラグイン > 新規追加** に移動
3. 「C3 CloudFront Clear Cache」を検索
4. **今すぐインストール** をクリックし、その後 **有効化**

### 方法2: WP-CLI

WP-CLIを使用してプラグインをインストール・有効化：

```bash
# 1つのコマンドでインストール・有効化
wp plugin install c3-cloudfront-clear-cache --activate

# または最初にインストールし、その後個別に有効化
wp plugin install c3-cloudfront-clear-cache
wp plugin activate c3-cloudfront-clear-cache
```

### 方法3: 手動アップロード

1. [WordPress.org](https://wordpress.org/plugins/c3-cloudfront-clear-cache/)からプラグインをダウンロード
2. **プラグイン > 新規追加 > プラグインのアップロード** でzipファイルをアップロード
3. プラグインを有効化

## 開発用インストール

開発者とAI支援ワークフローのみ：

### Git Clone（開発用）

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/amimoto-ami/c3-cloudfront-clear-cache.git
cd c3-cloudfront-clear-cache
```

## 検証

インストール後、プラグインが正常に動作することを確認：

1. WordPress管理画面で **設定 > C3 CloudFront Cache** に移動
2. 設定ページが表示されることを確認
3. 必要なフィールドがすべて表示されていることを確認

### WP-CLI検証

WP-CLIを使用してインストールを確認することもできます：

```bash
# プラグインがアクティブかどうかを確認
wp plugin list | grep c3-cloudfront-clear-cache

# プラグインのステータスを確認
wp plugin status c3-cloudfront-clear-cache
```

## 次のステップ

インストール後、[プラグインの設定](/ja/guide/configuration)でAWS認証情報とCloudFrontディストリビューションIDを設定する必要があります。

## インストールのトラブルシューティング

### プラグインが表示されない

プラグインが管理画面に表示されない場合：

1. ファイル権限を確認（ディレクトリは`755`、ファイルは`644`）
2. すべてのプラグインファイルが正しくアップロードされていることを確認
3. エラーログでPHPエラーを確認

### 有効化エラー

有効化中にエラーが発生した場合：

1. PHPバージョンが最小要件（7.4+）を満たしていることを確認
2. 競合するプラグインがないか確認
3. WordPressが最小バージョン要件（5.0+）を満たしていることを確認

### パーマリンク構造の警告

パーマリンク構造に関する警告が表示される場合：

1. **設定 > パーマリンク** に移動
2. 「プレーン」以外の構造を選択
3. 「変更を保存」をクリック

より詳細なトラブルシューティングについては、[トラブルシューティングガイド](/ja/guide/troubleshooting)を参照してください。 