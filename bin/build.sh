#!/usr/bin/env bash

set -e
git branch -D release
git checkout -b release
rm -rf .github node_modules/ vendor/ .distignore .editorconfig .phpcs.xml.dist .wp-env.json phpunix.xml tests/ renovate.json
git add ./
git commit -m "release package"
git push origin release -f