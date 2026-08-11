<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.central_domains' => array_unique(array_merge(
            (array) config('tenancy.central_domains'),
            ['profitlens.test', 'localhost', '127.0.0.1']
        ))]);

        // Belt-and-braces: also clean up at setUp, because tearDown from a
        // CRASHED prior test won't have run.
        $this->fullyDisconnectAndWipeTenantFiles();

        $centralDb = database_path('testing.sqlite');
        if (! file_exists($centralDb)) {
            touch($centralDb);
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        $this->fullyDisconnectAndWipeTenantFiles();

        parent::tearDown();
    }

    private function fullyDisconnectAndWipeTenantFiles(): void
    {
        foreach (array_keys(DB::getConnections()) as $name) {
            DB::disconnect($name);
        }
        DB::purge();

        gc_collect_cycles();

        foreach (glob(database_path('tenant*')) ?: [] as $file) {
            @unlink($file);
        }
    }
}
