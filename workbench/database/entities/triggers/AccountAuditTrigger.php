<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\triggers;

use CalebDW\SqlEntities\Trigger;
use Override;

class AccountAuditTrigger extends Trigger
{
    public bool $constraint = false;

    public ?string $definition = null;

    public array $characteristics = [];

    public string $timing = 'AFTER';

    public array $events = [
        'UPDATE',
    ];

    public string $table = 'accounts';

    #[Override]
    public function definition(): string
    {
        return $this->definition ?? <<<'SQL'
            EXECUTE FUNCTION record_account_audit();
            SQL;
    }
}
