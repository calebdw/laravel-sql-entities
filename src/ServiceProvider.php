<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Support\Composer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Override;
use Symfony\Component\Finder\Finder;

class ServiceProvider extends IlluminateServiceProvider implements DeferrableProvider
{
    /** @return list<string> */
    #[Override]
    public function provides(): array
    {
        return [SqlEntityManager::class, 'sql-entities']; // @codeCoverageIgnore
    }

    #[Override]
    public function register(): void
    {
        $this->app->singleton(SqlEntityManager::class, function (Application $app) {
            return new SqlEntityManager($this->getEntities($app), $app->make('db'));
        });

        $this->app->alias(SqlEntityManager::class, 'sql-entities');
    }

    /** @return Collection<int, SqlEntity> */
    protected function getEntities(Application $app): Collection
    {
        $composer = new Composer($app->make('files'), $app->basePath());

        return collect()
            ->wrap(iterator_to_array(
                Finder::create()
                    ->files()
                    ->in($app->basePath())
                    ->path('database/entities'),
            ))
            ->map(fn ($file) => (string) $file->getRealPath())
            ->pipe(fn ($files) => collect($composer->classFromFile($files->all())))
            ->filter(fn ($class) => is_subclass_of($class, SqlEntity::class))
            ->map(fn ($class) => $app->make($class))
            ->values();
    }
}
