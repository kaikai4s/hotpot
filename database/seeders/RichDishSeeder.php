<?php

/**
 * 丰富的火锅菜品数据 Seeder
 * 包含完整的分类和菜品，使用 Unsplash 免费图片
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RichDishSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始创建丰富的菜品数据...');

        // 先清空现有数据
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Dish::truncate();
        DishCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 创建分类
        $categories = [
            ['name' => '招牌锅底', 'description' => '精选秘制锅底，麻辣鲜香', 'sort_order' => 1],
            ['name' => '精品牛肉', 'description' => '新鲜牛肉，口感鲜嫩', 'sort_order' => 2],
            ['name' => '特色羊肉', 'description' => '精选羊肉，肥瘦相间', 'sort_order' => 3],
            ['name' => '猪肉系列', 'description' => '优质猪肉，鲜嫩多汁', 'sort_order' => 4],
            ['name' => '海鲜拼盘', 'description' => '新鲜海鲜，鲜美可口', 'sort_order' => 5],
            ['name' => '丸滑系列', 'description' => '手工丸子，Q弹爽滑', 'sort_order' => 6],
            ['name' => '时蔬菌菇', 'description' => '新鲜蔬菜，健康美味', 'sort_order' => 7],
            ['name' => '豆制品', 'description' => '优质豆制品，营养丰富', 'sort_order' => 8],
            ['name' => '主食小吃', 'description' => '特色主食，美味小吃', 'sort_order' => 9],
            ['name' => '饮品甜点', 'description' => '清凉饮品，精致甜点', 'sort_order' => 10],
        ];

        $categoryMap = [];
        foreach ($categories as $category) {
            $cat = DishCategory::create($category);
            $categoryMap[$category['name']] = $cat->id;
            $this->command->info("创建分类: {$category['name']}");
        }

        // 菜品数据 - 使用 picsum.photos 随机图片（更稳定）
        $dishes = [
            // 招牌锅底
            ['name' => '麻辣牛油锅底', 'category' => '招牌锅底', 'price' => 68.00, 'description' => '精选牛油熬制，麻辣鲜香，回味无穷', 'image' => 'https://picsum.photos/seed/hotpot1/400/300'],
            ['name' => '番茄养生锅底', 'category' => '招牌锅底', 'price' => 58.00, 'description' => '新鲜番茄熬制，酸甜可口，老少皆宜', 'image' => 'https://picsum.photos/seed/hotpot2/400/300'],
            ['name' => '清汤菌菇锅底', 'category' => '招牌锅底', 'price' => 52.00, 'description' => '多种菌菇熬制，清淡鲜美，营养丰富', 'image' => 'https://picsum.photos/seed/hotpot3/400/300'],
            ['name' => '酸菜鱼锅底', 'category' => '招牌锅底', 'price' => 78.00, 'description' => '正宗酸菜，鲜鱼熬制，酸爽开胃', 'image' => 'https://picsum.photos/seed/hotpot4/400/300'],
            ['name' => '鸳鸯锅底', 'category' => '招牌锅底', 'price' => 88.00, 'description' => '一锅两味，麻辣与清汤完美结合', 'image' => 'https://picsum.photos/seed/hotpot5/400/300'],
            ['name' => '藤椒鸡锅底', 'category' => '招牌锅底', 'price' => 72.00, 'description' => '藤椒清香，鸡汤浓郁，麻而不辣', 'image' => 'https://picsum.photos/seed/hotpot6/400/300'],

            // 精品牛肉
            ['name' => '极品肥牛卷', 'category' => '精品牛肉', 'price' => 58.00, 'description' => '精选肥牛，肥瘦相间，入口即化', 'image' => 'https://picsum.photos/seed/beef1/400/300'],
            ['name' => '雪花牛肉', 'category' => '精品牛肉', 'price' => 78.00, 'description' => '顶级雪花牛肉，纹理细腻，口感极佳', 'image' => 'https://picsum.photos/seed/beef2/400/300'],
            ['name' => '嫩牛肉片', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '精选牛里脊，鲜嫩多汁', 'image' => 'https://picsum.photos/seed/beef3/400/300'],
            ['name' => '牛肉粒', 'category' => '精品牛肉', 'price' => 52.00, 'description' => '精切牛肉粒，口感饱满', 'image' => 'https://picsum.photos/seed/beef4/400/300'],
            ['name' => '吊龙牛肉', 'category' => '精品牛肉', 'price' => 88.00, 'description' => '牛脊背肉，肉质细嫩，涮煮即食', 'image' => 'https://picsum.photos/seed/beef5/400/300'],
            ['name' => '牛舌片', 'category' => '精品牛肉', 'price' => 68.00, 'description' => '新鲜牛舌，口感脆嫩', 'image' => 'https://picsum.photos/seed/beef6/400/300'],
            ['name' => '牛百叶', 'category' => '精品牛肉', 'price' => 42.00, 'description' => '精选牛百叶，爽脆可口', 'image' => 'https://picsum.photos/seed/beef7/400/300'],
            ['name' => '毛肚', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '新鲜毛肚，七上八下，脆嫩爽口', 'image' => 'https://picsum.photos/seed/beef8/400/300'],

            // 特色羊肉
            ['name' => '精品羊肉卷', 'category' => '特色羊肉', 'price' => 52.00, 'description' => '内蒙古羔羊肉，肥瘦均匀，无膻味', 'image' => 'https://picsum.photos/seed/lamb1/400/300'],
            ['name' => '羊腿肉片', 'category' => '特色羊肉', 'price' => 58.00, 'description' => '精选羊腿肉，肉质紧实，鲜嫩可口', 'image' => 'https://picsum.photos/seed/lamb2/400/300'],
            ['name' => '羊肉串', 'category' => '特色羊肉', 'price' => 38.00, 'description' => '现穿羊肉串，涮煮皆宜', 'image' => 'https://picsum.photos/seed/lamb3/400/300'],
            ['name' => '羊排', 'category' => '特色羊肉', 'price' => 78.00, 'description' => '带骨羊排，肉质鲜美', 'image' => 'https://picsum.photos/seed/lamb4/400/300'],

            // 猪肉系列
            ['name' => '精品五花肉', 'category' => '猪肉系列', 'price' => 38.00, 'description' => '层次分明，肥而不腻', 'image' => 'https://picsum.photos/seed/pork1/400/300'],
            ['name' => '梅花肉片', 'category' => '猪肉系列', 'price' => 35.00, 'description' => '猪梅花肉，口感细腻', 'image' => 'https://picsum.photos/seed/pork2/400/300'],
            ['name' => '猪脑花', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪脑，口感绵密', 'image' => 'https://picsum.photos/seed/pork3/400/300'],
            ['name' => '猪黄喉', 'category' => '猪肉系列', 'price' => 32.00, 'description' => '精选黄喉，脆嫩爽口', 'image' => 'https://picsum.photos/seed/pork4/400/300'],
            ['name' => '猪肝片', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪肝，营养丰富', 'image' => 'https://picsum.photos/seed/pork5/400/300'],
            ['name' => '午餐肉', 'category' => '猪肉系列', 'price' => 22.00, 'description' => '经典午餐肉，香嫩可口', 'image' => 'https://picsum.photos/seed/pork6/400/300'],

            // 海鲜拼盘
            ['name' => '鲜虾拼盘', 'category' => '海鲜拼盘', 'price' => 68.00, 'description' => '新鲜大虾，肉质饱满', 'image' => 'https://picsum.photos/seed/seafood1/400/300'],
            ['name' => '鱿鱼须', 'category' => '海鲜拼盘', 'price' => 38.00, 'description' => '新鲜鱿鱼须，Q弹爽口', 'image' => 'https://picsum.photos/seed/seafood2/400/300'],
            ['name' => '鲜贝片', 'category' => '海鲜拼盘', 'price' => 48.00, 'description' => '新鲜扇贝，鲜甜可口', 'image' => 'https://picsum.photos/seed/seafood3/400/300'],
            ['name' => '蟹棒', 'category' => '海鲜拼盘', 'price' => 25.00, 'description' => '优质蟹棒，鲜美多汁', 'image' => 'https://picsum.photos/seed/seafood4/400/300'],
            ['name' => '鲜鱼片', 'category' => '海鲜拼盘', 'price' => 42.00, 'description' => '新鲜鱼片，肉质细嫩', 'image' => 'https://picsum.photos/seed/seafood5/400/300'],
            ['name' => '生蚝', 'category' => '海鲜拼盘', 'price' => 58.00, 'description' => '新鲜生蚝，鲜美肥嫩', 'image' => 'https://picsum.photos/seed/seafood6/400/300'],
            ['name' => '海鲜拼盘', 'category' => '海鲜拼盘', 'price' => 128.00, 'description' => '多种海鲜组合，超值享受', 'image' => 'https://picsum.photos/seed/seafood7/400/300'],

            // 丸滑系列
            ['name' => '手工牛肉丸', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '手工捶打，Q弹有嚼劲', 'image' => 'https://picsum.photos/seed/ball1/400/300'],
            ['name' => '鲜虾滑', 'category' => '丸滑系列', 'price' => 38.00, 'description' => '新鲜虾仁制作，鲜甜爽滑', 'image' => 'https://picsum.photos/seed/ball2/400/300'],
            ['name' => '墨鱼丸', 'category' => '丸滑系列', 'price' => 28.00, 'description' => '墨鱼制作，口感独特', 'image' => 'https://picsum.photos/seed/ball3/400/300'],
            ['name' => '鱼豆腐', 'category' => '丸滑系列', 'price' => 22.00, 'description' => '鱼肉豆腐，嫩滑可口', 'image' => 'https://picsum.photos/seed/ball4/400/300'],
            ['name' => '蟹味棒', 'category' => '丸滑系列', 'price' => 25.00, 'description' => '蟹肉风味，鲜美多汁', 'image' => 'https://picsum.photos/seed/ball5/400/300'],
            ['name' => '撒尿牛丸', 'category' => '丸滑系列', 'price' => 35.00, 'description' => '爆汁牛丸，一口爆浆', 'image' => 'https://picsum.photos/seed/ball6/400/300'],
            ['name' => '鱼滑', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '新鲜鱼肉制作，细腻爽滑', 'image' => 'https://picsum.photos/seed/ball7/400/300'],

            // 时蔬菌菇
            ['name' => '娃娃菜', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜娃娃菜，清甜爽口', 'image' => 'https://picsum.photos/seed/veg1/400/300'],
            ['name' => '茼蒿', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '新鲜茼蒿，清香可口', 'image' => 'https://picsum.photos/seed/veg2/400/300'],
            ['name' => '生菜', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '新鲜生菜，爽脆清甜', 'image' => 'https://picsum.photos/seed/veg3/400/300'],
            ['name' => '土豆片', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '现切土豆片，软糯可口', 'image' => 'https://picsum.photos/seed/veg4/400/300'],
            ['name' => '藕片', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜藕片，脆嫩爽口', 'image' => 'https://picsum.photos/seed/veg5/400/300'],
            ['name' => '金针菇', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜金针菇，爽滑可口', 'image' => 'https://picsum.photos/seed/veg6/400/300'],
            ['name' => '香菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜香菇，香气浓郁', 'image' => 'https://picsum.photos/seed/veg7/400/300'],
            ['name' => '杏鲍菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜杏鲍菇，口感脆嫩', 'image' => 'https://picsum.photos/seed/veg8/400/300'],
            ['name' => '木耳', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '精选木耳，爽脆可口', 'image' => 'https://picsum.photos/seed/veg9/400/300'],
            ['name' => '菌菇拼盘', 'category' => '时蔬菌菇', 'price' => 38.00, 'description' => '多种菌菇组合，营养丰富', 'image' => 'https://picsum.photos/seed/veg10/400/300'],

            // 豆制品
            ['name' => '老豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '传统老豆腐，口感扎实', 'image' => 'https://picsum.photos/seed/tofu1/400/300'],
            ['name' => '嫩豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '嫩滑豆腐，入口即化', 'image' => 'https://picsum.photos/seed/tofu2/400/300'],
            ['name' => '冻豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '冻豆腐，吸汁入味', 'image' => 'https://picsum.photos/seed/tofu3/400/300'],
            ['name' => '豆皮', 'category' => '豆制品', 'price' => 15.00, 'description' => '薄脆豆皮，口感独特', 'image' => 'https://picsum.photos/seed/tofu4/400/300'],
            ['name' => '腐竹', 'category' => '豆制品', 'price' => 15.00, 'description' => '优质腐竹，口感筋道', 'image' => 'https://picsum.photos/seed/tofu5/400/300'],
            ['name' => '千张结', 'category' => '豆制品', 'price' => 15.00, 'description' => '千张打结，吸汁入味', 'image' => 'https://picsum.photos/seed/tofu6/400/300'],

            // 主食小吃
            ['name' => '手工面条', 'category' => '主食小吃', 'price' => 15.00, 'description' => '手工拉面，劲道爽滑', 'image' => 'https://picsum.photos/seed/noodle1/400/300'],
            ['name' => '红薯粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '红薯粉条，Q弹爽滑', 'image' => 'https://picsum.photos/seed/noodle2/400/300'],
            ['name' => '土豆粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '土豆粉条，软糯可口', 'image' => 'https://picsum.photos/seed/noodle3/400/300'],
            ['name' => '方便面', 'category' => '主食小吃', 'price' => 8.00, 'description' => '经典方便面，吸汁入味', 'image' => 'https://picsum.photos/seed/noodle4/400/300'],
            ['name' => '油条', 'category' => '主食小吃', 'price' => 10.00, 'description' => '酥脆油条，涮煮皆宜', 'image' => 'https://picsum.photos/seed/snack1/400/300'],
            ['name' => '烧饼', 'category' => '主食小吃', 'price' => 8.00, 'description' => '香酥烧饼，外酥里嫩', 'image' => 'https://picsum.photos/seed/snack2/400/300'],
            ['name' => '蛋炒饭', 'category' => '主食小吃', 'price' => 18.00, 'description' => '粒粒分明，香气四溢', 'image' => 'https://picsum.photos/seed/rice1/400/300'],

            // 饮品甜点
            ['name' => '酸梅汤', 'category' => '饮品甜点', 'price' => 12.00, 'description' => '冰镇酸梅汤，解腻开胃', 'image' => 'https://picsum.photos/seed/drink1/400/300'],
            ['name' => '王老吉', 'category' => '饮品甜点', 'price' => 8.00, 'description' => '清凉降火，经典凉茶', 'image' => 'https://picsum.photos/seed/drink2/400/300'],
            ['name' => '可乐', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇可乐，清爽解渴', 'image' => 'https://picsum.photos/seed/drink3/400/300'],
            ['name' => '雪碧', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇雪碧，透心凉', 'image' => 'https://picsum.photos/seed/drink4/400/300'],
            ['name' => '鲜榨果汁', 'category' => '饮品甜点', 'price' => 22.00, 'description' => '新鲜水果现榨，健康美味', 'image' => 'https://picsum.photos/seed/drink5/400/300'],
            ['name' => '豆浆', 'category' => '饮品甜点', 'price' => 10.00, 'description' => '现磨豆浆，香浓可口', 'image' => 'https://picsum.photos/seed/drink6/400/300'],
            ['name' => '冰粉', 'category' => '饮品甜点', 'price' => 15.00, 'description' => '手搓冰粉，清凉解暑', 'image' => 'https://picsum.photos/seed/dessert1/400/300'],
            ['name' => '红糖糍粑', 'category' => '饮品甜点', 'price' => 18.00, 'description' => '软糯糍粑，红糖浇淋', 'image' => 'https://picsum.photos/seed/dessert2/400/300'],
        ];

        $sortOrder = 1;
        foreach ($dishes as $dish) {
            Dish::create([
                'name' => $dish['name'],
                'description' => $dish['description'],
                'price' => $dish['price'],
                'image_url' => $dish['image'],
                'category_id' => $categoryMap[$dish['category']],
                'status' => 'available',
                'sort_order' => $sortOrder++,
                'sales_count' => rand(50, 500),
                'average_rating' => round(rand(40, 50) / 10, 1),
                'review_count' => rand(10, 100),
            ]);
        }

        $this->command->info("✅ 创建完成！共创建 " . count($categories) . " 个分类，" . count($dishes) . " 个菜品");
    }
}
