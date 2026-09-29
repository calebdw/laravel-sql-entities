<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;
use CalebDW\SqlEntities\Contracts\SqlEntity;

#[Attribute(Attribute::TARGET_CLASS)]
class DependsOn
{
    public function __construct(
        /** @var class-string<SqlEntity>|list<class-string<SqlEntity>> */
        public array|string $dependencies,
    ) {
    }
}
