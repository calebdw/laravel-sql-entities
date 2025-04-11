<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    /** @inheritDoc */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            workbench_path('database/migrations'),
        );
    }
}
