# コントリビューション

C3 CloudFront Cache Controllerへの貢献にご興味をお持ちいただき、ありがとうございます！

## 開発環境のセットアップ

### 前提条件

- **PHP**: 7.4以上
- **Node.js**: 20以上
- **Git**: バージョン管理用

### セットアップ手順

1. **リポジトリをクローン:**
   ```bash
   git clone https://github.com/amimoto-ami/c3-cloudfront-clear-cache.git
   cd c3-cloudfront-clear-cache
   ```

2. **Node.js依存関係をインストール:**
   ```bash
   npm install
   ```

3. **wp-envで開発環境を開始:**
   ```bash
   npm run dev
   ```

4. **PHP依存関係をインストール:**
   ```bash
   npm run composer:install
   ```

## 開発ワークフロー

### ブランチ戦略

- `master` - 本番ブランチ
- `develop` - 開発ブランチ
- `feature/*` - 機能ブランチ
- `bugfix/*` - バグ修正ブランチ

### 機能ブランチの作成

```bash
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# 変更を行い、コミット
git add .
git commit -m "feat: add your feature description"

# プルリクエストを作成
git push origin feature/your-feature-name
```

## テスト

### テストの実行

```bash
# すべてのテストを実行
npm run test

# 特定のテストスイートを実行
wp-env run tests composer run test:unit
wp-env run tests composer run test:integration
```

## コード品質

### コードスタイルチェック

```bash
# PHP CodeSniffer
wp-env run tests composer run phpcs

# コードスタイルを自動修正
wp-env run tests composer run phpcbf
```

## プルリクエスト

### 提出前チェックリスト

- [ ] すべてのテストが通る
- [ ] コードがスタイルガイドラインに従っている
- [ ] ドキュメントが更新されている（必要な場合）

### プルリクエストテンプレート

```markdown
## 説明
変更の簡単な説明

## 変更の種類
- [ ] バグ修正
- [ ] 新機能
- [ ] 破壊的変更
- [ ] ドキュメント更新

## テスト
- [ ] ユニットテストが追加/更新されている
- [ ] 手動テストが完了している
```

## リソース

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [AWS CloudFront API Documentation](https://docs.aws.amazon.com/cloudfront/latest/APIReference/)

貢献していただき、ありがとうございます！ 