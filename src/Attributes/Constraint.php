<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Constraint
{
    public function __construct(
        public bool $constraint = true,
    ) {
    }
}
