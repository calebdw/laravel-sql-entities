<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Recursive
{
    public function __construct(
        public bool $recursive = true,
    ) {
    }
}
