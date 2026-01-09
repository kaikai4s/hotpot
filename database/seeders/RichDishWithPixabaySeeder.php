<?php

/**
 * 丰富的火锅菜品数据 Seeder（使用 Pixabay 真实图片）
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

class RichDishWithPixabaySeeder extends Seeder
{
    private string $apiKey = '54118506-2903f3087b5dcc440d8f46808';
    private array $imageCache = [];

    public function run(): void
    {
        $this->command->info('开始创建丰富的菜品数据（使用 Pixabay 真实图片）...');

        // 确保存储目录存在
        if (!Storage::disk('public')->exists('uploads/images/dishes')) {
            Storage::disk('public')->makeDirectory('uploads/images/dishes');
        }

        // 先清空现有数据
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Dish::truncate();
        DishCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 预先搜索并缓存各类图片
        $this->command->info('正在从 Pixabay 搜索图片...');
        $this->preloadImages();

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

        // 菜品数据
        $dishes = $this->getDishesData();

        $sortOrder = 1;
        $successCount = 0;
        $failCount = 0;

        foreach ($dishes as $dish) {
            $imageUrl = $this->getAndSaveImage($dish['search_term'], $dish['name']);
            
            if ($imageUrl) {
                $successCount++;
                $this->command->info("✓ {$dish['name']} - 图片下载成功");
            } else {
                $failCount++;
                $this->command->warn("✗ {$dish['name']} - 图片下载失败");
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
        }

        $this->command->info("\n✅ 创建完成！");
        $this->command->info("分类: " . count($categories) . " 个");
        $this->command->info("菜品: " . count($dishes) . " 个");
        $this->command->info("图片成功: {$successCount}，失败: {$failCount}");
    }

    /**
     * 预加载各类图片
     */
    private function preloadImages(): void
    {
        $searchTerms = [
            'hot pot' => 'hot pot chinese food',
            'hot pot soup' => 'hot pot soup broth',
            'beef slices' => 'beef slices raw meat',
            'beef' => 'beef meat food',
            'lamb meat' => 'lamb meat slices',
            'pork belly' => 'pork belly meat',
            'pork' => 'pork meat food',
            'shrimp' => 'shrimp seafood',
            'seafood' => 'seafood platter',
            'squid' => 'squid seafood',
            'fish' => 'fish fillet',
            'oyster' => 'oyster seafood',
            'meatball' => 'meatball food',
            'fish ball' => 'fish ball food',
            'vegetables' => 'chinese vegetables',
            'cabbage' => 'chinese cabbage',
            'lettuce' => 'lettuce vegetable',
            'potato' => 'potato slices',
            'lotus root' => 'lotus root',
            'mushroom' => 'mushroom food',
            'enoki mushroom' => 'enoki mushroom',
            'tofu' => 'tofu food',
            'noodles' => 'chinese noodles',
            'rice' => 'fried rice',
            'drink' => 'chinese drink tea',
            'juice' => 'fruit juice',
            'cola' => 'cola drink',
            'dessert' => 'chinese dessert',
        ];

        foreach ($searchTerms as $key => $query) {
            $this->command->info("  搜索: {$query}");
            $images = $this->searchPixabay($query);
            if (!empty($images)) {
                $this->imageCache[$key] = $images;
                $this->command->info("    找到 " . count($images) . " 张图片");
            } else {
                $this->command->warn("    未找到图片");
            }
            usleep(100000); // 100ms 延迟，避免 API 限制
        }
    }

    /**
     * 搜索 Pixabay 图片
     */
    private function searchPixabay(string $query): array
    {
        try {
            $response = Http::timeout(30)->get('https://pixabay.com/api/', [
                'key' => $this->apiKey,
                'q' => $query,
                'image_type' => 'photo',
                'category' => 'food',
                'per_page' => 10,
                'safesearch' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['hits']) && count($data['hits']) > 0) {
                    return array_map(function ($hit) {
                        return $hit['webformatURL'];
                    }, $data['hits']);
                }
            }
        } catch (\Exception $e) {
            $this->command->warn("Pixabay API 错误: {$e->getMessage()}");
        }

        return [];
    }

    /**
     * 获取并保存图片
     */
    private function getAndSaveImage(string $searchTerm, string $dishName): ?string
    {
        // 从缓存中获取图片
        $images = $this->imageCache[$searchTerm] ?? [];
        
        if (empty($images)) {
            // 尝试直接搜索菜品名称
            $images = $this->searchPixabay($dishName);
        }

        if (empty($images)) {
            return null;
        }

        // 随机选择一张图片
        $imageUrl = $images[array_rand($images)];

        try {
            $response = Http::timeout(30)->get($imageUrl);

            if ($response->successful()) {
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = Str::random(40) . '.' . $extension;
                $path = "uploads/images/dishes/{$filename}";
                
                Storage::disk('public')->put($path, $response->body());
                
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
            ['name' => '麻辣牛油锅底', 'category' => '招牌锅底', 'price' => 68.00, 'description' => '精选牛油熬制，麻辣鲜香，回味无穷', 'search_term' => 'hot pot'],
            ['name' => '番茄养生锅底', 'category' => '招牌锅底', 'price' => 58.00, 'description' => '新鲜番茄熬制，酸甜可口，老少皆宜', 'search_term' => 'hot pot soup'],
            ['name' => '清汤菌菇锅底', 'category' => '招牌锅底', 'price' => 52.00, 'description' => '多种菌菇熬制，清淡鲜美，营养丰富', 'search_term' => 'hot pot soup'],
            ['name' => '酸菜鱼锅底', 'category' => '招牌锅底', 'price' => 78.00, 'description' => '正宗酸菜，鲜鱼熬制，酸爽开胃', 'search_term' => 'hot pot'],
            ['name' => '鸳鸯锅底', 'category' => '招牌锅底', 'price' => 88.00, 'description' => '一锅两味，麻辣与清汤完美结合', 'search_term' => 'hot pot'],
            ['name' => '藤椒鸡锅底', 'category' => '招牌锅底', 'price' => 72.00, 'description' => '藤椒清香，鸡汤浓郁，麻而不辣', 'search_term' => 'hot pot soup'],

            // 精品牛肉
            ['name' => '极品肥牛卷', 'category' => '精品牛肉', 'price' => 58.00, 'description' => '精选肥牛，肥瘦相间，入口即化', 'search_term' => 'beef slices'],
            ['name' => '雪花牛肉', 'category' => '精品牛肉', 'price' => 78.00, 'description' => '顶级雪花牛肉，纹理细腻，口感极佳', 'search_term' => 'beef'],
            ['name' => '嫩牛肉片', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '精选牛里脊，鲜嫩多汁', 'search_term' => 'beef slices'],
            ['name' => '牛肉粒', 'category' => '精品牛肉', 'price' => 52.00, 'description' => '精切牛肉粒，口感饱满', 'search_term' => 'beef'],
            ['name' => '吊龙牛肉', 'category' => '精品牛肉', 'price' => 88.00, 'description' => '牛脊背肉，肉质细嫩，涮煮即食', 'search_term' => 'beef slices'],
            ['name' => '牛舌片', 'category' => '精品牛肉', 'price' => 68.00, 'description' => '新鲜牛舌，口感脆嫩', 'search_term' => 'beef'],
            ['name' => '牛百叶', 'category' => '精品牛肉', 'price' => 42.00, 'description' => '精选牛百叶，爽脆可口', 'search_term' => 'beef'],
            ['name' => '毛肚', 'category' => '精品牛肉', 'price' => 48.00, 'description' => '新鲜毛肚，七上八下，脆嫩爽口', 'search_term' => 'beef'],

            // 特色羊肉
            ['name' => '精品羊肉卷', 'category' => '特色羊肉', 'price' => 52.00, 'description' => '内蒙古羔羊肉，肥瘦均匀，无膻味', 'search_term' => 'lamb meat'],
            ['name' => '羊腿肉片', 'category' => '特色羊肉', 'price' => 58.00, 'description' => '精选羊腿肉，肉质紧实，鲜嫩可口', 'search_term' => 'lamb meat'],
            ['name' => '羊肉串', 'category' => '特色羊肉', 'price' => 38.00, 'description' => '现穿羊肉串，涮煮皆宜', 'search_term' => 'lamb meat'],
            ['name' => '羊排', 'category' => '特色羊肉', 'price' => 78.00, 'description' => '带骨羊排，肉质鲜美', 'search_term' => 'lamb meat'],

            // 猪肉系列
            ['name' => '精品五花肉', 'category' => '猪肉系列', 'price' => 38.00, 'description' => '层次分明，肥而不腻', 'search_term' => 'pork belly'],
            ['name' => '梅花肉片', 'category' => '猪肉系列', 'price' => 35.00, 'description' => '猪梅花肉，口感细腻', 'search_term' => 'pork'],
            ['name' => '猪脑花', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪脑，口感绵密', 'search_term' => 'pork'],
            ['name' => '猪黄喉', 'category' => '猪肉系列', 'price' => 32.00, 'description' => '精选黄喉，脆嫩爽口', 'search_term' => 'pork'],
            ['name' => '猪肝片', 'category' => '猪肉系列', 'price' => 28.00, 'description' => '新鲜猪肝，营养丰富', 'search_term' => 'pork'],
            ['name' => '午餐肉', 'category' => '猪肉系列', 'price' => 22.00, 'description' => '经典午餐肉，香嫩可口', 'search_term' => 'pork'],

            // 海鲜拼盘
            ['name' => '鲜虾拼盘', 'category' => '海鲜拼盘', 'price' => 68.00, 'description' => '新鲜大虾，肉质饱满', 'search_term' => 'shrimp'],
            ['name' => '鱿鱼须', 'category' => '海鲜拼盘', 'price' => 38.00, 'description' => '新鲜鱿鱼须，Q弹爽口', 'search_term' => 'squid'],
            ['name' => '鲜贝片', 'category' => '海鲜拼盘', 'price' => 48.00, 'description' => '新鲜扇贝，鲜甜可口', 'search_term' => 'seafood'],
            ['name' => '蟹棒', 'category' => '海鲜拼盘', 'price' => 25.00, 'description' => '优质蟹棒，鲜美多汁', 'search_term' => 'seafood'],
            ['name' => '鲜鱼片', 'category' => '海鲜拼盘', 'price' => 42.00, 'description' => '新鲜鱼片，肉质细嫩', 'search_term' => 'fish'],
            ['name' => '生蚝', 'category' => '海鲜拼盘', 'price' => 58.00, 'description' => '新鲜生蚝，鲜美肥嫩', 'search_term' => 'oyster'],
            ['name' => '海鲜拼盘', 'category' => '海鲜拼盘', 'price' => 128.00, 'description' => '多种海鲜组合，超值享受', 'search_term' => 'seafood'],

            // 丸滑系列
            ['name' => '手工牛肉丸', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '手工捶打，Q弹有嚼劲', 'search_term' => 'meatball'],
            ['name' => '鲜虾滑', 'category' => '丸滑系列', 'price' => 38.00, 'description' => '新鲜虾仁制作，鲜甜爽滑', 'search_term' => 'shrimp'],
            ['name' => '墨鱼丸', 'category' => '丸滑系列', 'price' => 28.00, 'description' => '墨鱼制作，口感独特', 'search_term' => 'fish ball'],
            ['name' => '鱼豆腐', 'category' => '丸滑系列', 'price' => 22.00, 'description' => '鱼肉豆腐，嫩滑可口', 'search_term' => 'fish ball'],
            ['name' => '蟹味棒', 'category' => '丸滑系列', 'price' => 25.00, 'description' => '蟹肉风味，鲜美多汁', 'search_term' => 'seafood'],
            ['name' => '撒尿牛丸', 'category' => '丸滑系列', 'price' => 35.00, 'description' => '爆汁牛丸，一口爆浆', 'search_term' => 'meatball'],
            ['name' => '鱼滑', 'category' => '丸滑系列', 'price' => 32.00, 'description' => '新鲜鱼肉制作，细腻爽滑', 'search_term' => 'fish ball'],

            // 时蔬菌菇
            ['name' => '娃娃菜', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜娃娃菜，清甜爽口', 'search_term' => 'cabbage'],
            ['name' => '茼蒿', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '新鲜茼蒿，清香可口', 'search_term' => 'vegetables'],
            ['name' => '生菜', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '新鲜生菜，爽脆清甜', 'search_term' => 'lettuce'],
            ['name' => '土豆片', 'category' => '时蔬菌菇', 'price' => 12.00, 'description' => '现切土豆片，软糯可口', 'search_term' => 'potato'],
            ['name' => '藕片', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜藕片，脆嫩爽口', 'search_term' => 'lotus root'],
            ['name' => '金针菇', 'category' => '时蔬菌菇', 'price' => 15.00, 'description' => '新鲜金针菇，爽滑可口', 'search_term' => 'enoki mushroom'],
            ['name' => '香菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜香菇，香气浓郁', 'search_term' => 'mushroom'],
            ['name' => '杏鲍菇', 'category' => '时蔬菌菇', 'price' => 18.00, 'description' => '新鲜杏鲍菇，口感脆嫩', 'search_term' => 'mushroom'],
            ['name' => '木耳', 'category' => '时蔬菌菇', 'price' => 16.00, 'description' => '精选木耳，爽脆可口', 'search_term' => 'mushroom'],
            ['name' => '菌菇拼盘', 'category' => '时蔬菌菇', 'price' => 38.00, 'description' => '多种菌菇组合，营养丰富', 'search_term' => 'mushroom'],

            // 豆制品
            ['name' => '老豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '传统老豆腐，口感扎实', 'search_term' => 'tofu'],
            ['name' => '嫩豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '嫩滑豆腐，入口即化', 'search_term' => 'tofu'],
            ['name' => '冻豆腐', 'category' => '豆制品', 'price' => 12.00, 'description' => '冻豆腐，吸汁入味', 'search_term' => 'tofu'],
            ['name' => '豆皮', 'category' => '豆制品', 'price' => 15.00, 'description' => '薄脆豆皮，口感独特', 'search_term' => 'tofu'],
            ['name' => '腐竹', 'category' => '豆制品', 'price' => 15.00, 'description' => '优质腐竹，口感筋道', 'search_term' => 'tofu'],
            ['name' => '千张结', 'category' => '豆制品', 'price' => 15.00, 'description' => '千张打结，吸汁入味', 'search_term' => 'tofu'],

            // 主食小吃
            ['name' => '手工面条', 'category' => '主食小吃', 'price' => 15.00, 'description' => '手工拉面，劲道爽滑', 'search_term' => 'noodles'],
            ['name' => '红薯粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '红薯粉条，Q弹爽滑', 'search_term' => 'noodles'],
            ['name' => '土豆粉', 'category' => '主食小吃', 'price' => 12.00, 'description' => '土豆粉条，软糯可口', 'search_term' => 'noodles'],
            ['name' => '方便面', 'category' => '主食小吃', 'price' => 8.00, 'description' => '经典方便面，吸汁入味', 'search_term' => 'noodles'],
            ['name' => '油条', 'category' => '主食小吃', 'price' => 10.00, 'description' => '酥脆油条，涮煮皆宜', 'search_term' => 'noodles'],
            ['name' => '烧饼', 'category' => '主食小吃', 'price' => 8.00, 'description' => '香酥烧饼，外酥里嫩', 'search_term' => 'noodles'],
            ['name' => '蛋炒饭', 'category' => '主食小吃', 'price' => 18.00, 'description' => '粒粒分明，香气四溢', 'search_term' => 'rice'],

            // 饮品甜点
            ['name' => '酸梅汤', 'category' => '饮品甜点', 'price' => 12.00, 'description' => '冰镇酸梅汤，解腻开胃', 'search_term' => 'drink'],
            ['name' => '王老吉', 'category' => '饮品甜点', 'price' => 8.00, 'description' => '清凉降火，经典凉茶', 'search_term' => 'drink'],
            ['name' => '可乐', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇可乐，清爽解渴', 'search_term' => 'cola'],
            ['name' => '雪碧', 'category' => '饮品甜点', 'price' => 6.00, 'description' => '冰镇雪碧，透心凉', 'search_term' => 'cola'],
            ['name' => '鲜榨果汁', 'category' => '饮品甜点', 'price' => 22.00, 'description' => '新鲜水果现榨，健康美味', 'search_term' => 'juice'],
            ['name' => '豆浆', 'category' => '饮品甜点', 'price' => 10.00, 'description' => '现磨豆浆，香浓可口', 'search_term' => 'drink'],
            ['name' => '冰粉', 'category' => '饮品甜点', 'price' => 15.00, 'description' => '手搓冰粉，清凉解暑', 'search_term' => 'dessert'],
            ['name' => '红糖糍粑', 'category' => '饮品甜点', 'price' => 18.00, 'description' => '软糯糍粑，红糖浇淋', 'search_term' => 'dessert'],
        ];
    }
}
