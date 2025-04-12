<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Get the cache key for the model.
     */
    protected function getCacheKey(): string
    {
        return sprintf(
            '%s:%s',
            $this->getTable(),
            $this->getKey()
        );
    }

    /**
     * Get the cache TTL in seconds.
     */
    protected function getCacheTTL(): int
    {
        return 3600; // 1 hour default
    }

    /**
     * Get the cached model instance.
     */
    public static function findCached($id): ?Model
    {
        $instance = new static;
        $key = sprintf('%s:%s', $instance->getTable(), $id);

        return Cache::remember($key, $instance->getCacheTTL(), function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Clear the cache for this model instance.
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Boot the trait.
     */
    protected static function bootCacheable(): void
    {
        static::saved(function (Model $model) {
            $model->clearCache();
        });

        static::deleted(function (Model $model) {
            $model->clearCache();
        });
    }
} 