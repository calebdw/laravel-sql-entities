<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->layer('Source', 'src')
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Contracts', 'src/Contracts')
    ->layer('Support', 'src/Support')
    ->layer('Attributes', 'src/Attributes')
    ->layer('Concerns', 'src/Concerns')
    ->layer('Entity', [
        'src/Function_.php',
        'src/MaterializedView.php',
        'src/Procedure.php',
        'src/Trigger.php',
        'src/View.php',
    ])
    ->layer('Grammars', 'src/Grammars')
    ->layer('Manager', 'src/SqlEntityManager.php')
    ->layer('Console', 'src/Console')
    ->layer('Facades', 'src/Facades')
    ->layer('Listeners', 'src/Listeners')
    ->layer('PHPStan', 'src/PHPStan')
    ->layer('ServiceProvider', 'src/ServiceProvider.php')
    ->ruleset([
        'Contracts'       => [],
        'Support'         => [],
        'Attributes'      => ['Contracts'],
        'Concerns'        => ['+Attributes'],
        'Entity'          => ['+Concerns', 'Support'],
        'Grammars'        => ['+Entity'],
        'Manager'         => ['+Grammars'],
        'Console'         => ['+Manager'],
        'Facades'         => ['+Manager'],
        'Listeners'       => ['+Manager'],
        'PHPStan'         => ['+Entity'],
        'ServiceProvider' => ['+Console', '+Listeners'],
    ]);
