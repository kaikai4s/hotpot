#!/bin/bash

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 * 
 * 数据库配置脚本
 */

echo "正在配置数据库..."

# 检查MySQL是否运行
if ! pgrep -x "mysqld" > /dev/null; then
    echo "错误: MySQL服务未运行，请先启动MySQL服务"
    exit 1
fi

# 提示输入MySQL root密码
read -sp "请输入MySQL root密码（如果为空直接回车）: " MYSQL_PASSWORD
echo ""

if [ -z "$MYSQL_PASSWORD" ]; then
    MYSQL_CMD="mysql -u root"
else
    MYSQL_CMD="mysql -u root -p$MYSQL_PASSWORD"
fi

# 创建数据库和用户
echo "创建数据库和用户..."
$MYSQL_CMD << EOF
CREATE DATABASE IF NOT EXISTS hotpot_miniapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hotpot_user'@'localhost' IDENTIFIED BY 'hotpot_password';
GRANT ALL PRIVILEGES ON hotpot_miniapp.* TO 'hotpot_user'@'localhost';
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo "✓ 数据库配置成功！"
    echo ""
    echo "数据库信息："
    echo "  数据库名: hotpot_miniapp"
    echo "  用户名: hotpot_user"
    echo "  密码: hotpot_password"
    echo ""
    echo "现在可以运行迁移："
    echo "  php artisan migrate"
else
    echo "✗ 数据库配置失败，请检查MySQL权限"
    exit 1
fi

