<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Returns
{
    public function __construct(
        public string $returns,
    ) {
    }
}
