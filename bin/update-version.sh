#!/usr/bin/env bash

# C3 CloudFront Clear Cache Plugin Version Update Script
# 使用方法: ./bin/update-version.sh [新しいバージョン番号]

set -e

# 色付きの出力用関数
print_info() {
    echo -e "\033[34m[INFO]\033[0m $1"
}

print_success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1"
}

print_warning() {
    echo -e "\033[33m[WARNING]\033[0m $1"
}

print_error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
}

# 現在のバージョンを取得
get_current_version() {
    grep '"version":' package.json | sed 's/.*"version": "\([^"]*\)".*/\1/'
}

# ファイルのバージョンを更新
update_version_in_file() {
    local file_path="$1"
    local old_version="$2"
    local new_version="$3"
    
    # ドットをエスケープ
    local escaped_old_version=$(echo "$old_version" | sed 's/\./\\./g')
    
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS用
        sed -i '' "s/$escaped_old_version/$new_version/g" "$file_path"
    else
        # Linux用
        sed -i "s/$escaped_old_version/$new_version/g" "$file_path"
    fi
}

# メイン処理
main() {
    print_info "C3 CloudFront Clear Cache Plugin バージョン更新スクリプト"
    echo
    
    # 現在の変更内容を確認
    if ! git diff --quiet; then
        print_warning "未コミットの変更があります:"
        git diff --name-only
        echo
        read -p "変更内容を確認しますか？ (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git diff
            echo
        fi
    else
        print_info "未コミットの変更はありません"
    fi
    
    # 現在のバージョンを取得
    current_version=$(get_current_version)
    print_info "現在のバージョン: $current_version"
    
    # 新しいバージョンを入力
    if [ -n "$1" ]; then
        new_version="$1"
        print_info "指定された新しいバージョン: $new_version"
    else
        echo
        read -p "新しいバージョン番号を入力してください (例: 7.0.2): " new_version
    fi
    
    if [ -z "$new_version" ]; then
        print_error "バージョン番号が入力されていません"
        exit 1
    fi
    
    # バージョン形式の検証（x.y.z形式）
    if ! [[ $new_version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        print_error "バージョン番号は x.y.z 形式で入力してください"
        exit 1
    fi
    
    echo
    print_info "以下のファイルのバージョンを更新します:"
    echo "  - package.json"
    echo "  - c3-cloudfront-clear-cache.php"
    echo "  - readme.txt (Stable tagのみ)"
    echo
    
    read -p "続行しますか？ (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "更新をキャンセルしました"
        exit 0
    fi
    
    # 各ファイルのバージョンを更新
    print_info "バージョンを更新中..."
    
    # package.json
    update_version_in_file "package.json" "$current_version" "$new_version"
    
    # c3-cloudfront-clear-cache.php
    update_version_in_file "c3-cloudfront-clear-cache.php" "$current_version" "$new_version"
    
    # readme.txt (Stable tagのみ更新)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS用
        sed -i '' "s/Stable tag: $current_version/Stable tag: $new_version/g" "readme.txt"
    else
        # Linux用
        sed -i "s/Stable tag: $current_version/Stable tag: $new_version/g" "readme.txt"
    fi
    
    print_success "バージョン更新が完了しました"
    
    # 変更内容を確認
    echo
    print_info "変更内容を確認:"
    git diff
    
    # コミット確認
    echo
    read -p "変更をコミットしますか？ (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "コミットメッセージを入力してください (デフォルト: 'Bump version to $new_version'): " commit_message
        if [ -z "$commit_message" ]; then
            commit_message="Bump version to $new_version"
        fi
        
        git add package.json c3-cloudfront-clear-cache.php readme.txt
        git commit -m "$commit_message"
        print_success "コミットが完了しました"
        
        # タグ作成の確認
        echo
        read -p "バージョンタグを作成しますか？ (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git tag -a "v$new_version" -m "Version $new_version"
            print_success "タグ v$new_version を作成しました"
        fi
    else
        print_warning "コミットをスキップしました"
    fi
    
    echo
    print_success "バージョン更新作業が完了しました！"
    print_info "新しいバージョン: $new_version"
}

# スクリプト実行
main "$@" 