<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_key' => $this->license_key,
            'domain' => $this->domain,
            'product_name' => $this->product_name,
            'product_version' => $this->product_version,
            'type' => $this->type,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'check_count' => $this->check_count,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->is_expiring_soon,
            'days_until_expiration' => $this->days_until_expiration,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
