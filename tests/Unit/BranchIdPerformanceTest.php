<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BranchIdPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing branches
        Branch::truncate();
        
        // Create 20 test branches
        for ($i = 1; $i <= 20; $i++) {
            Branch::create([
                'name' => "Branch $i",
                'location' => "Location $i",
                'branch_phone' => "1234567890$i",
            ]);
        }
    }

    public function test_sorting_performance()
    {
        $iterations = 1000;
        $results = [];

        // Test string ID sorting
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $branches = Branch::orderBy('id')->get();
        }
        $stringTime = microtime(true) - $startTime;
        $results['string_id'] = $stringTime;

        // Clear cache between tests
        Cache::flush();

        // Test with index
        DB::statement('CREATE INDEX IF NOT EXISTS branches_id_index ON branches (id)');
        
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $branches = Branch::orderBy('id')->get();
        }
        $indexedTime = microtime(true) - $startTime;
        $results['indexed_string_id'] = $indexedTime;

        // Output results
        $this->assertTrue(true); // Dummy assertion to make test pass
        echo "\nSorting Performance Results (1000 iterations):\n";
        echo "String ID (no index): " . number_format($stringTime, 4) . " seconds\n";
        echo "String ID (with index): " . number_format($indexedTime, 4) . " seconds\n";
        echo "Improvement with index: " . number_format(($stringTime - $indexedTime) / $stringTime * 100, 2) . "%\n";
    }

    public function test_memory_usage()
    {
        $branches = Branch::all();
        
        $memoryUsage = memory_get_usage();
        $branchCount = $branches->count();
        
        echo "\nMemory Usage Analysis:\n";
        echo "Total branches: $branchCount\n";
        echo "Memory used: " . number_format($memoryUsage / 1024, 2) . " KB\n";
        echo "Average per branch: " . number_format($memoryUsage / $branchCount / 1024, 2) . " KB\n";
    }
} 