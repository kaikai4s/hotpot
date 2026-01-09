<?php

/**
 * 丰富的火锅菜品数据 Seeder（带本地图片）
 * 从网络下载真实菜品图片并保存到本地存储
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RichDishWithLocalImagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('开始创建丰富的菜品数据（带本地图片）...');

        // 确保存储目录存在
        if (!Storage::disk('public')->exists('uploads/images/dishes')) {
            Storage::disk('public')->makeDirectory('uploads/images/dishes');
        }

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

        // 使用 foodish API 或 Lorem Picsum 获取食物图片
        // 这里使用稳定的图片源
        $imageUrls = [
            // 锅底类 - 使用火锅相关图片
            'hotpot' => [
                'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1617093727343-374698b1b08d?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
            ],
            // 牛肉类
            'beef' => [
                'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1546833998-877b37c2e5c6?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1603048297172-c92544798d5a?w=400&h=300&fit=crop',
            ],
            // 羊肉类
            'lamb' => [
                'https://images.unsplash.com/photo-1608039829572-f56e0e1e7c5c?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1602473812169-ede22f0a9f4d?w=400&h=300&fit=crop',
            ],
            // 猪肉类
            'pork' => [
                'https://images.unsplash.com/photo-1623653387945-2fd25214f8fc?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=400&h=300&fit=crop',
            ],
            // 海鲜类
            'seafood' => [
                'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1559737558-2f5a35f4523b?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?w=400&h=300&fit=crop',
            ],
            // 丸子类
            'meatball' => [
                'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1607532941433-304659e8198a?w=400&h=300&fit=crop',
            ],
            // 蔬菜类
            'vegetable' => [
                'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1597362925123-77861d3fbac7?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1518977676601-b53f82ber?w=400&h=300&fit=crop',
            ],
            // 豆制品
            'tofu' => [
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=400&h=300&fit=crop',
            ],
            // 主食
            'noodle' => [
                'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1552611052-33e04de081de?w=400&h=300&fit=crop',
            ],
            // 饮品
            'drink' => [
                'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?w=400&h=300&fit=crop',
            ],
        ];

        // 菜品数据
        $dishes = $this->getDishesData();

        $sortOrder = 1;
        $successCount = 0;
        $failCount = 0;

        foreach ($dishes as $dish) {
            $imageUrl = $this->downloadAndSaveImage($dish['image_type'], $dish['name']);
            
            if ($imageUrl) {
                $successCount++;
            } else {
                $failCount++;
                $this->command->warn("  图片下载失败: {$dish['name']}，使用默认图片");
            }

            Dish::create([
                'name' => $dish['name'],
                'description' => $dish['description'],
                'price' => $dish['price'],
                'image_url' => $imageUrl,
                'category_id' => $categoryMap[$dish['category']],
                'status' => 'available',
                'sort_order' => $sortOrder++,
                'sales_count' => rand(50, 500),
                'average_rating' => round(rand(40, 50) / 10, 1),
                'review_count' => rand(10, 100),
            ]);

            $this->command->info("创建菜品: {$dish['name']}");
        }

        $this->command->info("✅ 创建完成！共创建 " . count($categories) . " 个分类，" . count($dishes) . " 个菜品");
        $this->command->info("图片下载成功: {$successCount}，失败: {$failCount}");
    }

    /**
     * 下载图片并保存到本地
     */
    private function downloadAndSaveImage(string $type, string $dishName): ?string
    {
        try {
            // 使用 Lorem Picsum 作为稳定的图片源，根据菜品名称生成唯一的 seed
            $seed = md5($dishName);
            $imageUrl = "https://picsum.photos/seed/{$seed}/400/300";

            $response = Http::timeout(30)->get($imageUrl);

            if ($response->successful()) {
                $filename = Str::random(40) . '.jpg';
                $path = "uploads/images/dishes/{$filename}";
                
                Storage::disk('public')->put($path, $response->body());
                
                // 返回可访问的URL
                return '/storage/' . $path;
            }
        } catch (\Exception $e) {
            $this->command->warn("下载图片失败: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * 获取菜品数据
     */
    private function getDishesData(): array
    {
        return [
            // 招牌锅底
            ['name' => '麻辣牛油锅底', 'category' => '招牌锅底', 'price' => 68.00, 'description' => '精选牛油熬制，麻辣鲜香，回味无穷', 'image_type' => 'hotpot'],
            ['name' => '番茄养生锅底', 'category' => '招牌锅底', 'price' => 58.00, 'description' => '新鲜番茄熬制，酸甜可口，老少皆宜', 'image_type' => 'hotpot'],
            ['name' => '清汤菌菇锅底', 'category' => '招牌锅底', 'price' => 52.00, 'description' => '多种菌菇熬制，清淡鲜美，营养丰富', 'image_type' => 'hotpot'],
            ['name' => '酸菜鱼锅底', 'category' => '招牌锅底', 'price' => 78.00, 'description' => '正宗酸菜，鲜鱼熬制，酸爽开胃', 'image_type' => 'hotpot'],
            ['name' => '鸳鸯锅底', 'category' => '招牌锅底', 'price' => 88.00, 'description' => '一锅两味，麻辣与清汤完美结合', 'image_type' => 'hotpot'],
            ['name' => '藤椒鸡锅底', 'category' => '招牌锅底', 'price' => 72.00, 'description' => '藤椒清香，鸡汤浓郁，麻而不辣', 'image_type' => 'hotpot'],

            // 精品牛肉
            ['name' => '极品肥牛卷', 'category' => '精品牛肉', 'price' => 58.00, 'description' => '精选肥牛，肥瘦相间，入口即化', 'image_type' => 'beef'],
            ['name' => '雪花牛肉', 'category' => '精品牛肉', 'price' => 78.00, 'description' => '顶级雪花牛肉，纹理细腻，口感极佳', 'image_type' => 'beef'],
            ['name' => '嫩牛肉片', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '精选牛里脊，鲜嫩多汁', 'image_type' => 'beef'],
            ['name' => '牛肉粒', 'category' => '精品牛肉', 'price' => 52.00, 'description' => '精切牛肉粒，口感饱满', 'image_type' => 'beef'],
            ['name' => '吊龙牛肉', 'category' => '精品牛肉', 'price' => 88.00, 'description' => '牛脊背肉，肉质细嫩，涮煮即食', 'image_type' => 'beef'],
            ['name' => '牛舌片', 'category' => '精品牛肉', 'price' => 68.00, 'description' => '新鲜牛舌，口感脆嫩', 'image_type' => 'beef'],
            ['name' => '牛百叶', 'category' => '精品牛肉', 'price' => 42.00, 'description' => '精选牛百叶，爽脆可口', 'image_type' => 'beef'],
            ['name' => '毛肚', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '新鲜毛肚，七上八下，脆嫩爽口', 'image_type' => 'beef'],

            // 特色羊肉
            ['name' => '精品羊肉卷', 'category' => '特色羊肉', 'price' => 52.00, 'description' => '内蒙古羔羊肉，肥瘦均匀，无膻味', 'image_type' => 'lamb'],
            ['name' => '羊腿肉片', 'category' => '特色羊肉', 'price' => 58.00, 'description' => '精选羊腿肉，肉质紧实，鲜嫩可口', 'image_type' => 'lamb'],
            ['name' => '羊肉串', 'category' => '特色羊肉', 'price' => 38.00, 'description' => '现穿羊肉串，涮煮皆宜', 'image_type' => 'lamb'],
            ['name' => '羊排', 'category' => '特色羊肉', 'price' => 78.00, 'description' => '带骨羊排，肉质鲜美', 'image_type' => 'lamb'],

            // 猪肉系列
            ['name' => '精品五花肉', 'category' => '猪肉系列', 'price' => 38.00, 'description' => '层次分明，肥而不腻', 'image_type' => 'pork'],
            ['name' => '梅花肉片', 'category' => '猪肉系列', 'price' => 35.00, 'description' => '猪梅花肉，口感细腻', 'image_type' => 'pork'],
            ['name' => '猪脑花', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪脑，口感绵密', 'image_type' => 'pork'],
            ['name' => '猪黄喉', 'category' => '猪肉系列', 'price' => 32.00, 'description' => '精选黄喉，脆嫩爽口', 'image_type' => 'pork'],
            ['name' => '猪肝片', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪肝，营养丰富', 'image_type' => 'pork'],
            ['name' => '午餐肉', 'category' => '猪肉系列', 'price' => 22.00, 'description' => '经典午餐肉，香嫩可口', 'image_type' => 'pork'],

            // 海鲜拼盘
            ['name' => '鲜虾拼盘', 'category' => '海鲜拼盘', 'price' => 68.00, 'description' => '新鲜大虾，肉质饱满', 'image_type' => 'seafood'],
            ['name' => '鱿鱼须', 'category' => '海鲜拼盘', 'price' => 38.00, 'description' => '新鲜鱿鱼须，Q弹爽口', 'image_type' => 'seafood'],
            ['name' => '鲜贝片', 'category' => '海鲜拼盘', 'price' => 48.00, 'description' => '新鲜扇贝，鲜甜可口', 'image_type' => 'seafood'],
            ['name' => '蟹棒', 'category' => '海鲜拼盘', 'price' => 25.00, 'description' => '优质蟹棒，鲜美多汁', 'image_type' => 'seafood'],
            ['name' => '鲜鱼片', 'category' => '海鲜拼盘', 'price' => 42.00, 'description' => '新鲜鱼片，肉质细嫩', 'image_type' => 'seafood'],
            ['name' => '生蚝', 'category' => '海鲜拼盘', 'price' => 58.00, 'description' => '新鲜生蚝，鲜美肥嫩', 'image_type' => 'seafood'],
            ['name' => '海鲜拼盘', 'category' => '海鲜拼盘', 'price' => 128.00, 'description' => '多种海鲜组合，超值享受', 'image_type' => 'seafood'],

            // 丸滑系列
            ['name' => '手工牛肉丸', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '手工捶打，Q弹有嚼劲', 'image_type' => 'meatball'],
            ['name' => '鲜虾滑', 'category' => '丸滑系列', 'price' => 38.00, 'description' => '新鲜虾仁制作，鲜甜爽滑', 'image_type' => 'meatball'],
            ['name' => '墨鱼丸', 'category' => '丸滑系列', 'price' => 28.00, 'description' => '墨鱼制作，口感独特', 'image_type' => 'meatball'],
            ['name' => '鱼豆腐', 'category' => '丸滑系列', 'price' => 22.00, 'description' => '鱼肉豆腐，嫩滑可口', 'image_type' => 'meatball'],
            ['name' => '蟹味棒', 'category' => '丸滑系列', 'price' => 25.00, 'description' => '蟹肉风味，鲜美多汁', 'image_type' => 'meatball'],
            ['name' => '撒尿牛丸', 'category' => '丸滑系列', 'price' => 35.00, 'description' => '爆汁牛丸，一口爆浆', 'image_type' => 'meatball'],
            ['name' => '鱼滑', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '新鲜鱼肉制作，细腻爽滑', 'image_type' => 'meatball'],

            // 时蔬菌菇
            ['name' => '娃娃菜', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜娃娃菜，清甜爽口', 'image_type' => 'vegetable'],
            ['name' => '茼蒿', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '新鲜茼蒿，清香可口', 'image_type' => 'vegetable'],
            ['name' => '生菜', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '新鲜生菜，爽脆清甜', 'image_type' => 'vegetable'],
            ['name' => '土豆片', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '现切土豆片，软糯可口', 'image_type' => 'vegetable'],
            ['name' => '藕片', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜藕片，脆嫩爽口', 'image_type' => 'vegetable'],
            ['name' => '金针菇', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜金针菇，爽滑可口', 'image_type' => 'vegetable'],
            ['name' => '香菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜香菇，香气浓郁', 'image_type' => 'vegetable'],
            ['name' => '杏鲍菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜杏鲍菇，口感脆嫩', 'image_type' => 'vegetable'],
            ['name' => '木耳', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '精选木耳，爽脆可口', 'image_type' => 'vegetable'],
            ['name' => '菌菇拼盘', 'category' => '时蔬菌菇', 'price' => 38.00, 'description' => '多种菌菇组合，营养丰富', 'image_type' => 'vegetable'],

            // 豆制品
            ['name' => '老豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '传统老豆腐，口感扎实', 'image_type' => 'tofu'],
            ['name' => '嫩豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '嫩滑豆腐，入口即化', 'image_type' => 'tofu'],
            ['name' => '冻豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '冻豆腐，吸汁入味', 'image_type' => 'tofu'],
            ['name' => '豆皮', 'category' => '豆制品', 'price' => 15.00, 'description' => '薄脆豆皮，口感独特', 'image_type' => 'tofu'],
            ['name' => '腐竹', 'category' => '豆制品', 'price' => 15.00, 'description' => '优质腐竹，口感筋道', 'image_type' => 'tofu'],
            ['name' => '千张结', 'category' => '豆制品', 'price' => 15.00, 'description' => '千张打结，吸汁入味', 'image_type' => 'tofu'],

            // 主食小吃
            ['name' => '手工面条', 'category' => '主食小吃', 'price' => 15.00, 'description' => '手工拉面，劲道爽滑', 'image_type' => 'noodle'],
            ['name' => '红薯粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '红薯粉条，Q弹爽滑', 'image_type' => 'noodle'],
            ['name' => '土豆粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '土豆粉条，软糯可口', 'image_type' => 'noodle'],
            ['name' => '方便面', 'category' => '主食小吃', 'price' => 8.00, 'description' => '经典方便面，吸汁入味', 'image_type' => 'noodle'],
            ['name' => '油条', 'category' => '主食小吃', 'price' => 10.00, 'description' => '酥脆油条，涮煮皆宜', 'image_type' => 'noodle'],
            ['name' => '烧饼', 'category' => '主食小吃', 'price' => 8.00, 'description' => '香酥烧饼，外酥里嫩', 'image_type' => 'noodle'],
            ['name' => '蛋炒饭', 'category' => '主食小吃', 'price' => 18.00, 'description' => '粒粒分明，香气四溢', 'image_type' => 'noodle'],

            // 饮品甜点
            ['name' => '酸梅汤', 'category' => '饮品甜点', 'price' => 12.00, 'description' => '冰镇酸梅汤，解腻开胃', 'image_type' => 'drink'],
            ['name' => '王老吉', 'category' => '饮品甜点', 'price' => 8.00, 'description' => '清凉降火，经典凉茶', 'image_type' => 'drink'],
            ['name' => '可乐', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇可乐，清爽解渴', 'image_type' => 'drink'],
            ['name' => '雪碧', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇雪碧，透心凉', 'image_type' => 'drink'],
            ['name' => '鲜榨果汁', 'category' => '饮品甜点', 'price' => 22.00, 'description' => '新鲜水果现榨，健康美味', 'image_type' => 'drink'],
            ['name' => '豆浆', 'category' => '饮品甜点', 'price' => 10.00, 'description' => '现磨豆浆，香浓可口', 'image_type' => 'drink'],
            ['name' => '冰粉', 'category' => '饮品甜点', 'price' => 15.00, 'description' => '手搓冰粉，清凉解暑', 'image_type' => 'drink'],
            ['name' => '红糖糍粑', 'category' => '饮品甜点', 'price' => 18.00, 'description' => '软糯糍粑，红糖浇淋', 'image_type' => 'drink'],
        ];
    }
}
