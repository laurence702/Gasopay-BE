<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Remove dd() calls
        // Check if application is bound before parent setup
        // dd('Before parent setup: ', app());

        parent::setUp();

        // Remove dd() calls
        // Check if application is bound after parent setup
        // dd('After parent setup: ', app());

        // Use file-based caching for testing
        Config::set('cache.default', 'file');
        Cache::flush();

        // Remove manual SQLite config and migrate:fresh call
        // Config::set('database.default', 'sqlite');
        // Config::set('database.connections.sqlite.database', ':memory:');
        // Artisan::call('migrate:fresh');
    }
}
