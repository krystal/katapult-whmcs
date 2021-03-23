<?php

use Krystal\Katapult\Katapult;
use Krystal\Katapult\Resources\Organization;
use WHMCS\Module\Server\Katapult\Exceptions\Exception as KatapultException;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

function katapult(): Katapult
{
	return KatapultWhmcs::getKatapult();
}

/**
 * @param Client|null $client
 * @return Organization|null
 * @throws KatapultException
 */
function katapultOrg(Client $client = null): ? Organization
{
	if ($client) {
		return $client->managed_organization;
	}

	return KatapultWhmcs::getParentOrganization();
}
