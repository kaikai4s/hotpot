#!/bin/bash

# 下载真实的 Valorant 段位图标
# 使用官方 Valorant API

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
EPISODE_UUID="03621f52-342b-cf4e-4f86-9350a49c6d04"  # Episode 5 (最新版本)
BASE_URL="https://media.valorant-api.com/competitivetiers/${EPISODE_UUID}"

# 段位映射：tier_编号 -> Valorant API tier 值
declare -A TIER_MAP=(
    ["tier_1.png"]="3"   # IRON 1 (铁牌)
    ["tier_2.png"]="6"   # BRONZE 1 (铜牌)
    ["tier_3.png"]="9"   # SILVER 1 (银牌)
    ["tier_4.png"]="12"  # GOLD 1 (金牌)
    ["tier_5.png"]="15"  # PLATINUM 1 (白金)
    ["tier_6.png"]="18"  # DIAMOND 1 (钻石)
    ["tier_7.png"]="21"  # ASCENDANT 1 (超凡入圣)
    ["tier_8.png"]="24"  # IMMORTAL 1 (不朽)
    ["tier_9.png"]="27"  # RADIANT (神话)
)

# 下载图标
download_icon() {
    local filename=$1
    local tier_value=$2
    local url="${BASE_URL}/${tier_value}/smallicon.png"
    
    echo "正在下载: $filename (Tier ${tier_value})"
    
    if curl -L -f -s -o "$BASE_DIR/$filename" "$url"; then
        # 验证下载的文件是否为有效的图片
        if file "$BASE_DIR/$filename" | grep -q "image"; then
            echo "✓ 下载成功: $filename"
            return 0
        else
            echo "✗ 下载的文件不是有效的图片: $filename"
            rm -f "$BASE_DIR/$filename"
            return 1
        fi
    else
        echo "✗ 下载失败: $filename"
        return 1
    fi
}

# 主流程
echo "开始下载真实的 Valorant 段位图标..."
echo "使用 Episode UUID: ${EPISODE_UUID}"
echo ""

success_count=0
fail_count=0

for filename in "${!TIER_MAP[@]}"; do
    if download_icon "$filename" "${TIER_MAP[$filename]}"; then
        ((success_count++))
    else
        ((fail_count++))
    fi
done

echo ""
echo "完成！"
echo "成功: ${success_count} 个"
echo "失败: ${fail_count} 个"
echo "图标已保存到: $BASE_DIR"

