<?php

use Krystal\Katapult\Katapult;
use Krystal\Katapult\Resources\Organization;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

function katapult(): Katapult
{
	return KatapultWhmcs::getKatapult();
}

function katapultOrg(): ? Organization
{
	return KatapultWhmcs::getParentOrganization();
}


