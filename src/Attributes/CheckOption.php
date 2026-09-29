<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CheckOption
{
    public function __construct(
        /** @var 'cascaded'|'local'|true */
        public string|true $option = true,
    ) {
    }
}
