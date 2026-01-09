<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'openid' => 'wx_' . Str::random(28),
            'unionid' => 'union_' . Str::random(28),
            'nickname' => $this->faker->name(),
            'avatar_url' => $this->faker->imageUrl(100, 100),
            'phone' => $this->faker->numerify('1##########'),
            'gender' => $this->faker->randomElement([0, 1, 2]),
            'is_active' => true,
            'invite_code' => strtoupper(Str::random(8)),
        ];
    }
}
