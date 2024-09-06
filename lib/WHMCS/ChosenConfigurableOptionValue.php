<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\WHMCS;

class ChosenConfigurableOptionValue
{
    public function __construct(
        public readonly mixed $rawValue,
        public readonly ?string $name = null,
    ) {
    }
}
