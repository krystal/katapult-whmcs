<?php

use Krystal\Katapult\Katapult;
use Krystal\Katapult\Resources\Organization;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

function katapult(): Katapult
{
	return KatapultWhmcs::getKatapult();
}

/**
 * @param Client|null $client
 * @return Organization|Organization\ManagedOrganization|null
 */
function katapultOrg(Client $client = null)
{
	if ($client) {
		return $client->managed_organization;
	}

	return KatapultWhmcs::getParentOrganization();
}
