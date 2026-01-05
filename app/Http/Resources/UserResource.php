<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $level = null;
        if ($this->memberPoints) {
            $levelModel = \App\Models\PointLevel::where('code', $this->memberPoints->level)->first();
            if ($levelModel) {
                $level = [
                    'code' => $levelModel->code,
                    'name' => $levelModel->name,
                ];
            }
        }

        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'equipped_title' => $this->equipped_title,
            'level' => $level,
        ];
    }
}

