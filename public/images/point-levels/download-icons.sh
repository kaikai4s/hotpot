#!/bin/bash

# 下载 Valorant 段位图标到本地
# 如果下载失败，将创建 SVG 占位符

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_URL="https://static.wikia.nocookie.net/valorant/images"

declare -A ICONS=(
    ["tier_1.png"]="1/1d/Tier_1.png"
    ["tier_2.png"]="4/4f/Tier_2.png"
    ["tier_3.png"]="9/9a/Tier_3.png"
    ["tier_4.png"]="6/6c/Tier_4.png"
    ["tier_5.png"]="0/0a/Tier_5.png"
    ["tier_6.png"]="8/8e/Tier_6.png"
    ["tier_7.png"]="7/7a/Tier_7.png"
    ["tier_8.png"]="3/3c/Tier_8.png"
    ["tier_9.png"]="2/2a/Tier_9.png"
)

# 创建占位符 SVG 图标
create_placeholder() {
    local filename=$1
    local tier_num=${filename//[^0-9]/}
    
    cat > "$BASE_DIR/$filename" <<EOF
<svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad${tier_num}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#4A5568;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#2D3748;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="64" height="64" rx="8" fill="url(#grad${tier_num})"/>
  <text x="32" y="40" font-family="Arial, sans-serif" font-size="24" font-weight="bold" fill="#FFFFFF" text-anchor="middle">T${tier_num}</text>
</svg>
EOF
}

# 下载图标
download_icon() {
    local filename=$1
    local url_path=$2
    local url="${BASE_URL}/${url_path}"
    
    echo "正在下载: $filename"
    
    if command -v curl &> /dev/null; then
        if curl -L -f -s -o "$BASE_DIR/$filename" "$url"; then
            echo "✓ 下载成功: $filename"
            return 0
        fi
    elif command -v wget &> /dev/null; then
        if wget -q -O "$BASE_DIR/$filename" "$url"; then
            echo "✓ 下载成功: $filename"
            return 0
        fi
    fi
    
    echo "✗ 下载失败，创建占位符: $filename"
    create_placeholder "$filename"
    return 1
}

# 主流程
echo "开始下载 Valorant 段位图标..."
echo ""

for filename in "${!ICONS[@]}"; do
    download_icon "$filename" "${ICONS[$filename]}"
done

echo ""
echo "完成！图标已保存到: $BASE_DIR"

