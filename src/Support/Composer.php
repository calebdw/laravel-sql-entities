<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Composer as IlluminateComposer;
use Illuminate\Support\Str;

/** @internal */
class Composer extends IlluminateComposer
{
    /**
     * The PSR-4 namespaces.
     *
     * @var array<string, string> Keys are namespaces, values are directories.
     */
    protected array $namespaces;

    /**
     * Get the current Composer config.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $contents = file_get_contents($this->findComposerFile());
        throw_if($contents === false, 'Unable to read composer.json file.');

        $config = json_decode(
            json: $contents,
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );
        assert(is_array($config));

        return $config;
    }

    /**
     * Get the Composer PSR-4 namespaces.
     *
     * @return array<string, string> Keys are namespaces, values are directories.
     */
    public function getNamespaces(): array
    {
        if (! isset($this->namespaces)) {
            $config = $this->getConfig();

            $this->namespaces = [
                ...Arr::get($config, 'autoload.psr-4', []),
                ...Arr::get($config, 'autoload-dev.psr-4', []),
            ];
        }

        return $this->namespaces;
    }

    /**
     * Get the PSR-4 class name for a file.
     *
     * @param array<int, string>|string $files The file(s) to convert.
     * @return ($files is string ? class-string : array<int, class-string>)
     */
    public function classFromFile(array|string $files): string|array
    {
        $namespaces = $this->getNamespaces();

        $basePath = $this->workingPath ?? getcwd();
        throw_if($basePath === false, 'Unable to get the base directory.');

        $realBasePath = realpath($basePath);
        throw_if($realBasePath === false, 'Unable to get the real base directory.');

        $realBasePath = Str::of($realBasePath)
            ->finish(DIRECTORY_SEPARATOR)
            ->toString();

        /** @phpstan-ignore return.type */
        return str_replace(
            search: [$realBasePath, ...array_values($namespaces), DIRECTORY_SEPARATOR, '.php'],
            replace: ['', ...array_keys($namespaces), '\\', ''],
            subject: $files,
        );
    }
}
