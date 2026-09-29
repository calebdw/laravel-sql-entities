<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Events
{
    public function __construct(
        /** @var list<string>|string */
        public array|string $events,
    ) {
    }
}
