<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationApiKey extends Model
{
    protected $table = 'integration_api_keys';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public static function generateKey(string $prefix = 'sk6_'): string
    {
        return $prefix . bin2hex(random_bytes(24));
    }

    public static function getOrCreateDefault(?int $userId = null): self
    {
        return static::getOrCreateForService(
            'sk6_external',
            'SK6 External Integration',
            'sk6_',
            $userId
        );
    }

    public static function getOrCreateForService(
        string $serviceKey,
        string $name,
        string $prefix = 'pij_',
        ?int $userId = null
    ): self {
        $record = static::where('service_key', $serviceKey)->first();
        if ($record) {
            return $record;
        }

        return static::create([
            'name' => $name,
            'service_key' => $serviceKey,
            'api_key' => static::generateKey($prefix),
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }
}
