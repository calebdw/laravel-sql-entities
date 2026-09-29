<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('reports entities missing required configuration', function () {
    $process = new Process([
        PHP_BINARY,
        'vendor/bin/phpstan',
        'analyse',
        '--no-progress',
        '--error-format=json',
        '-c',
        'tests/PHPStan/phpstan.neon',
    ]);
    $process->setTimeout(120);
    $process->run();

    /** @var array{files: array<string, array{messages: list<array{message: string, identifier: string}>}>} $report */
    $report = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    $messages = [];

    foreach ($report['files'] as $file => $details) {
        foreach ($details['messages'] as $message) {
            $messages[basename($file)][] = $message['identifier'] . ': ' . $message['message'];
        }
    }

    expect($messages)->not->toHaveKey('Valid.php')
        ->and($messages['Invalid.php'])->toEqualCanonicalizing([
            'sqlEntities.missingReturns: Function entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\MissingFunction must define a return type via the #[Returns] attribute or the $returns property.',
            'sqlEntities.missingEvents: Trigger entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\MissingTrigger must define events via the #[Events] attribute or the $events property.',
            'sqlEntities.missingTable: Trigger entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\MissingTrigger must define a table via the #[Table] attribute or the $table property.',
            'sqlEntities.missingTiming: Trigger entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\MissingTrigger must define a timing via the #[Timing] attribute or the $timing property.',
            'sqlEntities.missingEvents: Trigger entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\PartialTrigger must define events via the #[Events] attribute or the $events property.',
            'sqlEntities.missingTiming: Trigger entity CalebDW\SqlEntities\Tests\PHPStan\Fixtures\PartialTrigger must define a timing via the #[Timing] attribute or the $timing property.',
        ]);
});
