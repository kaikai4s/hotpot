#!/bin/bash

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

# 获取本机IP地址
LOCAL_IP=$(hostname -I | awk '{print $1}')

echo "=========================================="
echo "  火锅店管理系统 - 远程访问启动脚本"
echo "=========================================="
echo ""
echo "本机内网IP: $LOCAL_IP"
echo ""

# 检查防火墙
echo "检查防火墙状态..."
if command -v ufw &> /dev/null; then
    echo "检测到 ufw 防火墙"
    echo "请确保已开放端口: sudo ufw allow 8000/tcp && sudo ufw allow 5173/tcp"
elif command -v firewall-cmd &> /dev/null; then
    echo "检测到 firewalld 防火墙"
    echo "请确保已开放端口: sudo firewall-cmd --add-port=8000/tcp --permanent && sudo firewall-cmd --add-port=5173/tcp --permanent && sudo firewall-cmd --reload"
else
    echo "未检测到防火墙，请手动检查防火墙设置"
fi

echo ""
echo "=========================================="
echo "访问地址："
echo "  同一局域网: http://$LOCAL_IP:5173"
echo "  本机访问:   http://localhost:5173"
echo ""
echo "如果要从公网访问，请："
echo "  1. 配置路由器端口转发（8000 和 5173）"
echo "  2. 或使用内网穿透工具（如 ngrok）"
echo "=========================================="
echo ""

# 设置环境变量（如果前后端分离部署）
export VITE_API_TARGET="http://$LOCAL_IP:8000"

# 启动 Laravel 服务器（后台）
echo "启动 Laravel 后端服务器..."
php artisan serve --host=0.0.0.0 --port=8000 > /dev/null 2>&1 &
LARAVEL_PID=$!
echo "Laravel 后端已启动 (PID: $LARAVEL_PID)"

# 等待一下确保后端启动
sleep 2

# 启动 Vite 前端服务器
echo "启动 Vite 前端服务器..."
echo ""
npm run dev

# 清理：当脚本退出时停止后台进程
trap "kill $LARAVEL_PID 2>/dev/null" EXIT

