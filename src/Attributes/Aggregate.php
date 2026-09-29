<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Aggregate
{
    public function __construct(
        public bool $aggregate = true,
    ) {
    }
}
