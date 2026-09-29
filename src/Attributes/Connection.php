<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    public function __construct(
        public UnitEnum|string $name,
    ) {
    }
}
