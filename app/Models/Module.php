<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * Determine whether the module with the given slug is enabled.
     *
     * A missing row or an unknown slug is always considered disabled.
     */
    public static function isEnabled(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->where('enabled', true)
            ->exists();
    }
}
