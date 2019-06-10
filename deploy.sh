#!/usr/bin/env sh

# 当发生错误时中止脚本
set -e

# 切换分支
git checkout master
git pull origin dev

# 构建
# npm run build

git add -A
git commit -m 'deploy'

# 部署到 https://<USERNAME>.github.io/<REPO>
git push origin master

# 切回分支
git checkout dev
