<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // 获取用户（如果不存在则创建测试用户）
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('没有找到用户，请先运行 UserSeeder');
            return;
        }

        // 获取菜品
        $dishes = Dish::where('status', 'available')->get();
        if ($dishes->isEmpty()) {
            $this->command->warn('没有找到可用菜品，请先运行 DishSeeder');
            return;
        }

        // 清空现有订单数据（可选，根据需要决定是否保留）
        // OrderItem::truncate();
        // Order::truncate();

        // 创建测试订单
        $testOrders = [
            [
                'user_id' => $users->first()->id,
                'status' => 'completed',
                'payment_method' => 'mock',
                'total_amount' => 258.00,
                'items' => [
                    ['dish_id' => $dishes->where('name', '麻辣锅底')->first()?->id ?? $dishes->first()->id, 'quantity' => 1, 'price' => 58.00],
                    ['dish_id' => $dishes->where('name', '精品肥牛')->first()?->id ?? $dishes->skip(1)->first()->id, 'quantity' => 2, 'price' => 68.00],
                    ['dish_id' => $dishes->where('name', '鲜虾')->first()?->id ?? $dishes->skip(2)->first()->id, 'quantity' => 1, 'price' => 88.00],
                ],
                'created_at' => now()->subDays(1),
                'paid_at' => now()->subDays(1)->addMinutes(5),
                'completed_at' => now()->subDays(1)->addHours(2),
            ],
            [
                'user_id' => $users->first()->id,
                'status' => 'paid',
                'payment_method' => 'mock',
                'total_amount' => 188.00,
                'items' => [
                    ['dish_id' => $dishes->where('name', '清汤锅底')->first()?->id ?? $dishes->skip(1)->first()->id, 'quantity' => 1, 'price' => 48.00],
                    ['dish_id' => $dishes->where('name', '羊肉卷')->first()?->id ?? $dishes->skip(2)->first()->id, 'quantity' => 2, 'price' => 58.00],
                    ['dish_id' => $dishes->where('name', '生菜')->first()?->id ?? $dishes->skip(3)->first()->id, 'quantity' => 1, 'price' => 12.00],
                ],
                'created_at' => now()->subHours(2),
                'paid_at' => now()->subHours(2)->addMinutes(3),
            ],
            [
                'user_id' => $users->first()->id,
                'status' => 'pending',
                'payment_method' => null,
                'total_amount' => 116.00,
                'items' => [
                    ['dish_id' => $dishes->where('name', '番茄锅底')->first()?->id ?? $dishes->skip(2)->first()->id, 'quantity' => 1, 'price' => 52.00],
                    ['dish_id' => $dishes->where('name', '五花肉')->first()?->id ?? $dishes->skip(3)->first()->id, 'quantity' => 1, 'price' => 48.00],
                    ['dish_id' => $dishes->where('name', '可乐')->first()?->id ?? $dishes->skip(4)->first()->id, 'quantity' => 2, 'price' => 8.00],
                ],
                'created_at' => now()->subHours(1),
            ],
            [
                'user_id' => $users->count() > 1 ? $users->skip(1)->first()->id : $users->first()->id,
                'status' => 'paid',
                'payment_method' => 'wechat',
                'total_amount' => 150.00,
                'items' => [
                    ['dish_id' => $dishes->where('name', '麻辣锅底')->first()?->id ?? $dishes->first()->id, 'quantity' => 1, 'price' => 58.00],
                    ['dish_id' => $dishes->where('name', '扇贝')->first()?->id ?? $dishes->skip(2)->first()->id, 'quantity' => 1, 'price' => 78.00],
                    ['dish_id' => $dishes->where('name', '米饭')->first()?->id ?? $dishes->skip(4)->first()->id, 'quantity' => 2, 'price' => 5.00],
                ],
                'created_at' => now()->subDays(2),
                'paid_at' => now()->subDays(2)->addMinutes(10),
            ],
            [
                'user_id' => $users->first()->id,
                'status' => 'cancelled',
                'payment_method' => null,
                'total_amount' => 98.00,
                'items' => [
                    ['dish_id' => $dishes->where('name', '清汤锅底')->first()?->id ?? $dishes->skip(1)->first()->id, 'quantity' => 1, 'price' => 48.00],
                    ['dish_id' => $dishes->where('name', '白菜')->first()?->id ?? $dishes->skip(3)->first()->id, 'quantity' => 2, 'price' => 10.00],
                    ['dish_id' => $dishes->where('name', '酸梅汤')->first()?->id ?? $dishes->skip(4)->first()->id, 'quantity' => 2, 'price' => 12.00],
                ],
                'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($testOrders as $orderData) {
            try {
                DB::beginTransaction();

                // 生成订单号（确保唯一性）
                do {
                    $orderNo = 'ORD' . date('YmdHis', $orderData['created_at']->timestamp) . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                } while (Order::where('order_no', $orderNo)->exists());

                // 创建订单
                $order = Order::create([
                    'order_no' => $orderNo,
                    'user_id' => $orderData['user_id'],
                    'table_id' => null,
                    'total_amount' => $orderData['total_amount'],
                    'status' => $orderData['status'],
                    'payment_method' => $orderData['payment_method'],
                    'payment_transaction_id' => $orderData['payment_method'] ? ('TXN' . strtoupper($orderData['payment_method']) . date('YmdHis', $orderData['created_at']->timestamp) . mt_rand(1000, 9999)) : null,
                    'paid_at' => $orderData['paid_at'] ?? null,
                    'completed_at' => $orderData['completed_at'] ?? null,
                    'created_at' => $orderData['created_at'],
                    'updated_at' => $orderData['created_at'],
                ]);

                // 创建订单项
                foreach ($orderData['items'] as $itemData) {
                    $dish = Dish::find($itemData['dish_id']);
                    if ($dish) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'dish_id' => $dish->id,
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['price'],
                            'subtotal' => $itemData['price'] * $itemData['quantity'],
                            'created_at' => $orderData['created_at'],
                            'updated_at' => $orderData['created_at'],
                        ]);
                    }
                }

                DB::commit();
                $this->command->info("订单 {$orderNo} 创建成功");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("创建订单失败: " . $e->getMessage());
            }
        }

        $this->command->info('订单测试数据创建完成！');
    }
}

