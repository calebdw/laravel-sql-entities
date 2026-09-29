<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Tests\PHPStan\Fixtures;

use CalebDW\SqlEntities\Attributes\Events;
use CalebDW\SqlEntities\Attributes\Returns;
use CalebDW\SqlEntities\Attributes\Table;
use CalebDW\SqlEntities\Attributes\Timing;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;

#[Returns('integer')]
class ConfiguredFunction extends Function_
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

class PropertyFunction extends Function_
{
    protected string $returns = 'integer';

    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Returns('integer')]
abstract class ReturningFunction extends Function_
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

class InheritedFunction extends ReturningFunction
{
}

#[Returns('integer')]
trait ReturnsInteger
{
}

class TraitFunction extends Function_
{
    use ReturnsInteger;

    public function definition(): string
    {
        return 'SELECT 1';
    }
}

abstract class AbstractFunction extends Function_
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

class PlainView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Events('UPDATE')]
#[Table('accounts')]
#[Timing('AFTER')]
class ConfiguredTrigger extends Trigger
{
    public function definition(): string
    {
        return 'EXECUTE FUNCTION record_account_audit()';
    }
}

class PropertyTrigger extends Trigger
{
    protected array $events = ['UPDATE'];

    protected string $table = 'accounts';

    protected string $timing = 'AFTER';

    public function definition(): string
    {
        return 'EXECUTE FUNCTION record_account_audit()';
    }
}
