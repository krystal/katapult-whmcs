<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\WHMCS\User;

use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;

class Client extends \Grizzlyware\Salmon\WHMCS\User\Client
{
    use HasDataStoreValues;

    public const DS_MANAGED_ORG_ID = 'managed_org_id';

    protected function dataStoreRelType(): string
    {
        return 'client';
    }
}
