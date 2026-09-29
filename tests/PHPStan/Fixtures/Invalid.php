<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Tests\PHPStan\Fixtures;

use CalebDW\SqlEntities\Attributes\Table;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;

class MissingFunction extends Function_
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

class MissingTrigger extends Trigger
{
    public function definition(): string
    {
        return 'EXECUTE FUNCTION record_account_audit()';
    }
}

#[Table('accounts')]
class PartialTrigger extends Trigger
{
    public function definition(): string
    {
        return 'EXECUTE FUNCTION record_account_audit()';
    }
}
