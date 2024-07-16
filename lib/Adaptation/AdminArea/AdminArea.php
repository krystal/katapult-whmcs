<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation\AdminArea;

use Grizzlyware\Salmon\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\Katapult\API\APIKey;
use WHMCS\Module\Server\Katapult\Katapult\ParentOrganization;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

abstract class AdminArea
{
    public function __construct(
        protected readonly ParentOrganization $parentOrganization,
        protected readonly APIKey $APIKey,
    ) {
    }

    protected function productIsKatapult(int $productId): bool
    {
        return Product::where('id', $productId)->where('servertype', KatapultWHMCS::SERVER_MODULE)->count() > 0;
    }
}
